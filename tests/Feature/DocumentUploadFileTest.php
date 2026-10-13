<?php

namespace Tests\Feature;

use App\FileRole;
use App\Models\Document;
use App\Models\Folder;
use App\Models\User;
use App\Services\DocumentFileInspector;
use App\Services\DocumentUploadService;
use Database\Seeders\FolderSeeder;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use ZipArchive;

class DocumentUploadFileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** @return array<string, mixed> */
    private function payload(UploadedFile $file): array
    {
        $this->seed(FolderSeeder::class);
        $folder = Folder::query()->where('code', '02_RENJA')->sole();

        return ['year' => 2026, 'group_folder_id' => $folder->parent_id, 'target_folder_id' => $folder->id,
            'title' => 'Laporan Kecamatan', 'timing' => 'none', 'version_code' => 'V01',
            'file_role' => FileRole::FinalReference->value, 'file' => $file];
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('laporan.pdf', "%PDF-1.4\n".str_repeat('0', 2048)."\n%%EOF");
    }

    /** @param array<string, string> $entries */
    private function office(string $name, array $entries): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'document-test-');
        try {
            $zip = new ZipArchive;
            $zip->open($path, ZipArchive::OVERWRITE);
            foreach ($entries as $entry => $content) {
                $zip->addFromString($entry, $content);
                $zip->setCompressionName($entry, ZipArchive::CM_STORE);
            }
            $zip->close();

            return UploadedFile::fake()->createWithContent($name, file_get_contents($path));
        } finally {
            unlink($path);
        }
    }

    private function assertNothingStored(): void
    {
        $this->assertNoMetadataStored();
        $this->assertSame([], Storage::disk('documents')->allFiles());
    }

    private function assertNoMetadataStored(): void
    {
        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_revisions', 0);
        $this->assertDatabaseCount('document_files', 0);
    }

    #[DataProvider('validFiles')]
    public function test_valid_content_is_stored_with_detected_mime(string $name, string $content, string $mime): void
    {
        Storage::fake('documents');
        $user = User::factory()->create();
        $payload = $this->payload(UploadedFile::fake()->createWithContent($name, $content));

        $response = $this->actingAs($user)->post('/documents', $payload);

        $document = Document::query()->sole();
        $response->assertRedirect(route('documents.show', $document))->assertSessionHasNoErrors();
        $file = $document->files()->sole();
        $this->assertSame($mime, $file->mime_type);
        $this->assertSame(hash('sha256', $content), $file->checksum_sha256);
        $this->assertSame($content, Storage::disk('documents')->get($file->path));
        $this->actingAs($user)->get(route('documents.download', $file))->assertOk()
            ->assertDownload($file->stored_filename)->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /** @return array<string, array{string, string, string}> */
    public static function validFiles(): array
    {
        return [
            'PDF' => ['laporan.pdf', "%PDF-1.4\n".str_repeat('0', 2048)."\n%%EOF", 'application/pdf'],
            'CSV' => ['data.csv', "nama,jumlah\n".str_repeat("Kecamatan,10\n", 100), 'text/plain'],
            'PNG without GD' => ['gambar.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=').str_repeat("\0", 2048), 'image/png'],
        ];
    }

    #[DataProvider('officeFormats')]
    public function test_valid_office_document_is_stored(string $extension, string $entry): void
    {
        Storage::fake('documents');
        $user = User::factory()->create();
        $file = $this->office('laporan.'.$extension, ['[Content_Types].xml' => '<Types/>', $entry => '<document>'.str_repeat('data', 512).'</document>']);
        $payload = $this->payload($file);

        $this->actingAs($user)->post('/documents', $payload)->assertRedirect()->assertSessionHasNoErrors();

        $stored = Document::query()->sole()->files()->sole();
        $this->assertSame($extension, $stored->extension);
        Storage::disk('documents')->assertExists($stored->path);
    }

    /** @return array<string, array{string, string}> */
    public static function officeFormats(): array
    {
        return ['DOCX' => ['docx', 'word/document.xml'], 'XLSX' => ['xlsx', 'xl/workbook.xml']];
    }

    #[DataProvider('invalidFiles')]
    public function test_invalid_content_is_rejected_without_saving(string $name, string $content, string $message): void
    {
        Storage::fake('documents');
        $user = User::factory()->create();
        $payload = $this->payload(UploadedFile::fake()->createWithContent($name, $content));

        $this->actingAs($user)->from('/documents/create')->post('/documents', $payload)
            ->assertRedirect('/documents/create')->assertSessionHasErrors(['file' => $message]);

        $this->assertNothingStored();
    }

    /** @return array<string, array{string, string, string}> */
    public static function invalidFiles(): array
    {
        $mismatch = 'Isi file dan extension tidak sesuai atau jenis file tidak diizinkan.';

        return [
            'text renamed PDF' => ['laporan.pdf', str_repeat('plain text ', 150), $mismatch],
            'PDF renamed PNG' => ['gambar.png', "%PDF-1.4\n".str_repeat('0', 2048), $mismatch],
            'unsupported PHP extension' => ['kode.php', str_repeat('plain text ', 150), $mismatch],
            'CSV script' => ['data.csv', "nama,jumlah\n<script>alert(1)</script>\n".str_repeat("Kecamatan,10\n", 100), $mismatch],
            'CSV PHP code' => ['data.csv', "nama,jumlah\n<?php echo 1; ?>\n".str_repeat("Kecamatan,10\n", 100), 'CSV harus berupa data teks tanpa kode program atau HTML.'],
            'empty file' => ['laporan.pdf', '', 'File tidak boleh kosong (minimal 1 KB).'],
        ];
    }

    #[DataProvider('unsafeOfficeEntries')]
    public function test_office_active_content_and_paths_are_rejected(string $entry): void
    {
        Storage::fake('documents');
        $user = User::factory()->create();
        $file = $this->office('laporan.docx', ['[Content_Types].xml' => '<Types/>', 'word/document.xml' => str_repeat('<document/>', 150), $entry => 'unsafe']);
        $payload = $this->payload($file);

        $this->actingAs($user)->post('/documents', $payload)->assertRedirect()->assertSessionHasErrors([
            'file' => 'Dokumen Office tidak valid atau berisi konten aktif yang tidak diizinkan.',
        ]);

        $this->assertNothingStored();
    }

    /** @return array<string, array{string}> */
    public static function unsafeOfficeEntries(): array
    {
        return ['macro' => ['word/vbaProject.bin'], 'script' => ['word/code.php'], 'traversal' => ['../file.xml'], 'absolute' => ['/file.xml']];
    }

    public function test_office_archive_missing_required_structure_is_rejected(): void
    {
        Storage::fake('documents');
        $user = User::factory()->create();
        $payload = $this->payload($this->office('laporan.docx', ['xl/workbook.xml' => str_repeat('<workbook/>', 150), '[Content_Types].xml' => '<Types/>']));

        $this->actingAs($user)->post('/documents', $payload)->assertRedirect()->assertSessionHasErrors('file');

        $this->assertNothingStored();
    }

    public function test_missing_fileinfo_returns_clear_form_error_without_saving(): void
    {
        Storage::shouldReceive('disk')->never();
        $user = User::factory()->create();
        $payload = $this->payload($this->pdf());
        if (class_exists(\finfo::class)) {
            $this->partialMock(DocumentFileInspector::class)->shouldReceive('supportsFileinfo')->andReturn(false);
        }

        $this->actingAs($user)->from('/documents/create')->followingRedirects()->post('/documents', $payload)
            ->assertOk()->assertSee('ekstensi PHP fileinfo')->assertSee('File belum disimpan.');

        $this->assertNoMetadataStored();
    }

    public function test_missing_fileinfo_returns_json_422_without_saving(): void
    {
        Storage::shouldReceive('disk')->never();
        $user = User::factory()->create();
        $payload = $this->payload($this->pdf());
        if (class_exists(\finfo::class)) {
            $this->partialMock(DocumentFileInspector::class)->shouldReceive('supportsFileinfo')->andReturn(false);
        }

        $this->actingAs($user)->postJson('/documents', $payload)->assertUnprocessable()
            ->assertJsonValidationErrors('file')->assertJsonPath('errors.file.0', 'Upload belum dapat diproses karena pemeriksaan keamanan file di server tidak tersedia. Hubungi administrator untuk mengaktifkan ekstensi PHP fileinfo. File belum disimpan.');

        $this->assertNoMetadataStored();
    }

    public function test_missing_zip_rejects_office_without_saving(): void
    {
        Storage::fake('documents');
        $user = User::factory()->create();
        $payload = $this->payload($this->office('laporan.docx', ['[Content_Types].xml' => '<Types/>', 'word/document.xml' => str_repeat('<document/>', 150)]));
        $this->partialMock(DocumentFileInspector::class)->shouldReceive('supportsZip')->andReturn(false);

        $this->actingAs($user)->post('/documents', $payload)->assertRedirect()->assertSessionHasErrors([
            'file' => 'Dokumen DOCX/XLSX belum dapat diperiksa karena ekstensi PHP zip di server tidak tersedia. Hubungi administrator. File belum disimpan.',
        ]);

        $this->assertNothingStored();
    }

    public function test_pdf_upload_does_not_require_zip(): void
    {
        Storage::fake('documents');
        $user = User::factory()->create();
        $payload = $this->payload($this->pdf());
        $this->partialMock(DocumentFileInspector::class)->shouldReceive('supportsZip')->never();

        $this->actingAs($user)->post('/documents', $payload)->assertRedirect()->assertSessionHasNoErrors();

        Storage::disk('documents')->assertExists(Document::query()->sole()->files()->sole()->path);
    }

    public function test_dependency_failure_during_service_recheck_does_not_save(): void
    {
        Storage::fake('documents');
        $user = User::factory()->create();
        $payload = $this->payload($this->pdf());
        $this->partialMock(DocumentFileInspector::class)->shouldReceive('supportsFileinfo')->twice()->andReturn(true, false);

        $this->actingAs($user)->post('/documents', $payload)->assertRedirect()->assertSessionHasErrors('file');

        $this->assertNothingStored();
    }

    public function test_service_rejects_unvalidated_content_before_storage(): void
    {
        Storage::fake('documents');
        $user = User::factory()->create();
        $file = UploadedFile::fake()->createWithContent('laporan.pdf', str_repeat('plain text ', 150));
        $payload = $this->payload($file);

        try {
            app(DocumentUploadService::class)->upload($user, $payload, $file);
            $this->fail('Unvalidated content was stored.');
        } catch (ValidationException $exception) {
            $this->assertSame(['file' => ['Isi file dan extension tidak sesuai atau jenis file tidak diizinkan.']], $exception->errors());
        }

        $this->assertNothingStored();
    }

    public function test_storage_failure_rolls_back_metadata_and_cleans_up(): void
    {
        $user = User::factory()->create();
        $payload = $this->payload($this->pdf());
        $disk = Mockery::mock(Filesystem::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        $disk->shouldReceive('deleteDirectory')->once()->andReturn(true);
        Storage::shouldReceive('disk')->with('documents')->twice()->andReturn($disk);

        $this->actingAs($user)->post('/documents', $payload)->assertRedirect()->assertSessionHasErrors([
            'file' => 'Dokumen belum tersimpan karena terjadi gangguan. Silakan coba kembali.',
        ]);

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_revisions', 0);
        $this->assertDatabaseCount('document_files', 0);
    }
}
