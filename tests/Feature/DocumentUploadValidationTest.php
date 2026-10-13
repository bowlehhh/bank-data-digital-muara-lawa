<?php

namespace Tests\Feature;

use App\FileRole;
use App\FolderType;
use App\Models\Document;
use App\Models\DocumentFile;
use App\Models\Folder;
use App\Models\User;
use App\Services\FolderStructureService;
use Database\Seeders\FolderSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class DocumentUploadValidationTest extends TestCase
{
    private function prepareUpload(): Folder
    {
        $this->seed(FolderSeeder::class);
        Storage::fake('documents');
        $this->actingAs(User::factory()->create());

        return Folder::query()->where('year', 2026)->where('code', '01_RENSTRA')->sole();
    }

    /** @return array<string, mixed> */
    private function payload(Folder $folder): array
    {
        return [
            'year' => $folder->year ?? 2026,
            'group_folder_id' => $folder->parent_id ?? $folder->id,
            'target_folder_id' => $folder->id,
            'title' => 'Rencana Kecamatan Muara Lawa',
            'description' => 'Dokumen perencanaan tahunan',
            'timing' => 'none',
            'version_code' => 'V01',
            'file_role' => FileRole::FinalReference->value,
            'file' => UploadedFile::fake()->createWithContent('rencana.pdf', "%PDF-1.4\n".str_repeat('0', 2048)."\n%%EOF"),
        ];
    }

    private function assertNothingStored(): void
    {
        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('document_revisions', 0);
        $this->assertDatabaseCount('document_files', 0);
        $this->assertSame([], Storage::disk('documents')->allFiles());
    }

    public function test_official_group_is_not_a_destination_and_error_preserves_metadata(): void
    {
        $folder = $this->prepareUpload()->parent;
        $payload = $this->payload($folder);

        $this->from('/documents/create')->post('/documents', $payload)
            ->assertRedirect('/documents/create')
            ->assertSessionHasErrors(['target_folder_id' => 'Pilih subfolder jenis dokumen yang aktif, bukan folder induk resmi.'])
            ->assertSessionHasInput('title', $payload['title'])
            ->assertSessionHasInput('description', $payload['description']);
        $this->assertNothingStored();
    }

    public function test_validation_redirect_displays_the_field_error(): void
    {
        $folder = $this->prepareUpload()->parent;

        $this->followingRedirects()->from('/documents/create')->post('/documents', $this->payload($folder))
            ->assertOk()->assertSee('Pilih subfolder jenis dokumen yang aktif, bukan folder induk resmi.')
            ->assertSee('id="target_folder_id-error"', false);
        $this->assertNothingStored();
    }

    public function test_form_only_offers_valid_destinations_with_their_root_groups(): void
    {
        $folder = $this->prepareUpload();
        $inactive = Folder::factory()->create(['parent_id' => $folder->parent_id, 'folder_type' => FolderType::DocumentType, 'is_active' => false]);
        $nested = Folder::factory()->create(['parent_id' => $folder->id, 'folder_type' => FolderType::DocumentType, 'is_custom' => true]);

        $this->get('/documents/create')->assertOk()
            ->assertViewHas('targets', fn ($targets): bool => $targets->contains('id', $folder->id)
                && $targets->contains('id', $nested->id)
                && ! $targets->contains('id', $folder->parent_id)
                && ! $targets->contains('id', $inactive->id))
            ->assertSee('data-group="'.$folder->parent_id.'"', false);
        $this->assertNothingStored();
    }

    #[TestWith(['inactive'])]
    #[TestWith(['inactive_parent'])]
    #[TestWith(['wrong_group'])]
    #[TestWith(['wrong_year'])]
    #[TestWith(['missing'])]
    public function test_invalid_folder_is_rejected_without_storing_anything(string $case): void
    {
        $folder = $this->prepareUpload();
        $payload = $this->payload($folder);
        match ($case) {
            'inactive' => $folder->update(['is_active' => false]),
            'inactive_parent' => $folder->parent->update(['is_active' => false]),
            'wrong_group' => $payload['group_folder_id'] = Folder::query()->where('year', 2026)->where('code', '02_PENGANGGARAN')->sole()->id,
            'wrong_year' => $payload['year'] = 2027,
            'missing' => $payload['target_folder_id'] = 999999,
        };

        $this->post('/documents', $payload)->assertSessionHasErrors('target_folder_id');
        $this->assertNothingStored();
    }

    public function test_custom_root_cannot_bypass_group_validation(): void
    {
        $this->prepareUpload();
        $folder = Folder::factory()->create(['is_custom' => true, 'is_official' => false]);
        $payload = [...$this->payload($folder), 'group_folder_id' => 999999];

        $this->post('/documents', $payload)->assertSessionHasErrors('target_folder_id');
        $this->assertNothingStored();
    }

    public function test_new_nested_folder_can_be_selected_and_uploaded_under_its_root_group(): void
    {
        $parent = $this->prepareUpload();
        $this->post('/folders', ['year' => 2026, 'parent_id' => $parent->id, 'name' => 'Rencana Kegiatan'])
            ->assertSessionHasNoErrors();
        $folder = Folder::query()->where('is_custom', true)->sole();
        $this->get('/documents/create')->assertOk()->assertViewHas('targets', fn ($targets): bool => $targets->contains('id', $folder->id));

        $this->post('/documents', [...$this->payload($folder), 'group_folder_id' => $parent->parent_id])
            ->assertSessionHasNoErrors()->assertSessionHas('status', 'Dokumen berhasil disimpan.');
        $document = Document::query()->sole();
        $this->assertSame($folder->id, $document->folder_id);
        Storage::disk('documents')->assertExists($document->files()->sole()->path);
    }

    public function test_new_root_folder_can_be_created_without_parent_field_and_used_for_upload(): void
    {
        $this->prepareUpload();
        $this->post('/folders', ['year' => 2026, 'name' => 'Dokumen Kecamatan'])->assertSessionHasNoErrors();
        $folder = Folder::query()->where('is_custom', true)->sole();

        $this->post('/documents', $this->payload($folder))->assertSessionHasNoErrors();
        $document = Document::query()->sole();
        $this->assertSame($folder->id, $document->folder_id);
        Storage::disk('documents')->assertExists($document->files()->sole()->path);
    }

    public function test_parent_document_type_remains_valid_when_it_has_children(): void
    {
        $folder = $this->prepareUpload();
        Folder::factory()->create(['parent_id' => $folder->id, 'folder_type' => FolderType::DocumentType]);

        $this->post('/documents', $this->payload($folder))->assertSessionHasNoErrors();
        $this->assertSame($folder->id, Document::query()->sole()->folder_id);
        Storage::disk('documents')->assertExists(DocumentFile::query()->sole()->path);
    }

    public function test_upload_uses_the_selected_year_for_direct_folder(): void
    {
        $this->prepareUpload();
        app(FolderStructureService::class)->createForYear(2027);
        $folder = Folder::query()->where('year', 2027)->where('code', '01_RENSTRA')->sole();
        $this->get('/documents/create')->assertOk()->assertSee('data-year="2027"', false);

        $this->post('/documents', $this->payload($folder))->assertSessionHasNoErrors();
        $document = Document::query()->sole();
        $this->assertSame(2027, $document->year);
        $this->assertSame($folder->id, $document->folder_id);
        Storage::disk('documents')->assertExists($document->files()->sole()->path);
    }

    #[TestWith(['program.php', '<?php echo "unsafe";'])]
    #[TestWith(['fake.pdf', '<?php echo "unsafe";'])]
    #[TestWith(['empty.pdf', ''])]
    #[TestWith(['data.csv', '<script>alert(1)</script>'])]
    public function test_invalid_file_is_rejected(string $name, string $content): void
    {
        $folder = $this->prepareUpload();
        $payload = [...$this->payload($folder), 'file' => UploadedFile::fake()->createWithContent($name, $content)];

        $this->post('/documents', $payload)->assertSessionHasErrors('file');
        $this->assertNothingStored();
    }

    public function test_csv_is_accepted_and_download_requires_an_active_session(): void
    {
        $folder = $this->prepareUpload();
        $content = "nama,jumlah\n".str_repeat("kecamatan,123\n", 100);
        $payload = [...$this->payload($folder), 'file' => UploadedFile::fake()->createWithContent('data.csv', $content)];

        $this->post('/documents', $payload)->assertSessionHasNoErrors();
        $document = Document::query()->sole();
        $file = $document->files()->sole();
        Storage::disk('documents')->assertExists($file->path);
        $this->assertSame('private', Storage::disk('documents')->getVisibility($file->path));
        $this->get('/documents/'.$document->id)->assertOk()->assertSee($document->title);
        $this->get('/document-files/'.$file->id.'/download')->assertOk()->assertDownload($file->stored_filename)->assertStreamedContent($content);
        $this->post('/logout');
        $this->get('/documents/'.$document->id)->assertRedirect('/login');
        $this->get('/document-files/'.$file->id.'/download')->assertRedirect('/login');
        $this->actingAs(User::factory()->inactive()->create())->get('/document-files/'.$file->id.'/download')->assertRedirect('/login');
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_guest_and_inactive_user_cannot_upload(bool $inactive): void
    {
        $folder = $this->prepareUpload();
        $this->post('/logout');
        if ($inactive) {
            $this->actingAs(User::factory()->inactive()->create());
        }

        $this->post('/documents', $this->payload($folder))->assertRedirect('/login');
        $this->assertNothingStored();
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_failed_storage_rolls_back_database_and_removes_partial_file(bool $throws): void
    {
        $folder = $this->prepareUpload();
        $disk = Storage::disk('documents');
        $failingDisk = Mockery::mock($disk);
        $failingDisk->shouldReceive('putFileAs')->once()->andReturnUsing(function (string $directory) use ($disk, $throws): bool {
            $disk->put($directory.'/partial.pdf', 'partial');
            if ($throws) {
                throw new RuntimeException('Internal storage path must not be exposed');
            }

            return false;
        });
        Storage::set('documents', $failingDisk);

        $this->from('/documents/create')->post('/documents', $this->payload($folder))
            ->assertRedirect('/documents/create')
            ->assertSessionHasErrors(['file' => 'Dokumen belum tersimpan karena terjadi gangguan. Silakan coba kembali.'])
            ->assertSessionHasInput('title', 'Rencana Kecamatan Muara Lawa');
        $this->assertNothingStored();
    }

    public function test_failure_after_file_write_removes_file_and_rolls_back_metadata(): void
    {
        $folder = $this->prepareUpload();
        DocumentFile::creating(function (): void {
            throw new RuntimeException('Simulated metadata failure');
        });
        try {
            $this->post('/documents', $this->payload($folder))->assertSessionHasErrors('file');
            $this->assertNothingStored();
        } finally {
            DocumentFile::flushEventListeners();
        }
    }

    #[TestWith(['inactive'])]
    #[TestWith(['year'])]
    #[TestWith(['global'])]
    public function test_new_folder_rejects_a_parent_that_cannot_accept_uploads(string $case): void
    {
        $parent = $this->prepareUpload()->parent;
        if ($case === 'inactive') {
            $parent->update(['is_active' => false]);
        } elseif ($case === 'global') {
            $parent = Folder::query()->whereNull('year')->whereNull('parent_id')->sole();
        }

        $this->post('/folders', ['year' => $case === 'year' ? 2027 : 2026, 'parent_id' => $parent->id, 'name' => 'Rencana Tambahan'])
            ->assertSessionHasErrors('parent_id');
        $this->assertDatabaseMissing('folders', ['name' => 'RENCANA_TAMBAHAN']);
        $this->assertNothingStored();
    }

    public function test_invalid_folder_fields_return_validation_errors_instead_of_server_error(): void
    {
        $this->prepareUpload();

        $this->post('/folders', ['year' => 'invalid', 'parent_id' => ['bad'], 'name' => ['bad']])
            ->assertSessionHasErrors(['year', 'parent_id', 'name']);
        $this->assertNothingStored();
    }

    public function test_file_larger_than_configured_limit_is_rejected(): void
    {
        $folder = $this->prepareUpload();
        config(['documents.max_size_kb' => 1]);

        $this->post('/documents', $this->payload($folder))->assertSessionHasErrors('file');
        $this->assertNothingStored();
    }

    public function test_preview_uses_the_same_destination_rules_as_upload(): void
    {
        $folder = $this->prepareUpload();
        $payload = $this->payload($folder);
        unset($payload['file']);
        $payload['extension'] = 'pdf';

        $this->postJson('/documents/filename-preview', $payload)->assertOk()->assertJsonStructure(['filename']);
        $this->postJson('/documents/filename-preview', [...$payload, 'target_folder_id' => $folder->parent_id])
            ->assertUnprocessable()->assertJsonValidationErrors('target_folder_id');
        $this->assertNothingStored();
    }
}
