<?php

namespace Tests\Feature;

use App\FileRole;
use App\Models\Document;
use App\Models\Folder;
use App\Models\User;
use Database\Seeders\FolderSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentsTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FolderSeeder::class);
        $this->admin = User::factory()->create();
        Storage::fake('documents');
    }

    private function payload(int $folderId, string $name = 'laporan.pdf'): array
    {
        return ['year' => 2026, 'group_folder_id' => Folder::find($folderId)->parent_id ?? $folderId,
            'target_folder_id' => $folderId, 'title' => 'Laporan Keuangan', 'timing' => 'none', 'version_code' => 'V01',
            'file_role' => FileRole::FinalReference->value, 'file' => UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n".str_repeat('0', 2048)."\n%%EOF")];
    }

    public function test_admin_can_upload_directly_to_selected_official_folder(): void
    {
        $folder = Folder::query()->where('is_temporary', false)->where('is_official', true)->whereNotNull('parent_id')->firstOrFail();
        $this->actingAs($this->admin)->post('/documents', $this->payload($folder->id))->assertRedirect()->assertSessionHasNoErrors();
        $document = Document::query()->sole();
        $this->assertSame($folder->id, $document->folder_id);
        $this->assertSame($folder->id, $document->target_folder_id);
        $this->assertNotNull($document->version_code);
        Storage::disk('documents')->assertExists($document->files()->sole()->path);
    }

    public function test_archive_folder_is_hidden_and_rejected_as_upload_target(): void
    {
        $folder = Folder::factory()->create(['code' => '99_ARSIP_SEMENTARA', 'is_temporary' => true, 'folder_type' => 'temporary', 'parent_id' => null, 'year' => 2026]);
        $this->actingAs($this->admin)->get('/documents/create')->assertOk()->assertDontSee('Arsip Sementara')->assertDontSee('99_ARSIP_SEMENTARA');
        $this->actingAs($this->admin)->post('/documents', $this->payload($folder->id))->assertRedirect()->assertSessionHasErrors('target_folder_id');
        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], Storage::disk('documents')->allFiles());
    }

    public function test_duplicate_checksum_is_rejected(): void
    {
        $folder = Folder::query()->where('is_temporary', false)->where('is_official', true)->whereNotNull('parent_id')->firstOrFail();
        $payload = $this->payload($folder->id);
        $this->actingAs($this->admin)->post('/documents', $payload)->assertRedirect();
        $this->actingAs($this->admin)->post('/documents', $this->payload($folder->id))
            ->assertSessionHasErrors(['file' => 'File yang sama masih tersimpan pada dokumen aktif. Hapus dokumen tersebut atau gunakan file lain.']);
        $this->assertDatabaseCount('documents', 1);
    }

    public function test_soft_deleted_document_does_not_block_reupload_of_the_same_file(): void
    {
        $folder = Folder::query()->where('is_temporary', false)->where('is_official', true)->whereNotNull('parent_id')->firstOrFail();
        $this->actingAs($this->admin)->post('/documents', $this->payload($folder->id))->assertSessionHasNoErrors();
        $deleted = Document::query()->sole();
        $oldFile = $deleted->files()->sole();

        $this->actingAs($this->admin)->delete('/documents/'.$deleted->id)->assertRedirect('/documents');
        $this->assertSoftDeleted('documents', ['id' => $deleted->id]);
        Storage::disk('documents')->assertExists($oldFile->path);

        $this->actingAs($this->admin)->post('/documents', $this->payload($folder->id))->assertSessionHasNoErrors();
        $replacement = Document::query()->sole();
        $this->assertNotSame($deleted->id, $replacement->id);
        $this->assertSame('V01', $replacement->version_code);
        $this->assertSame(2, Document::withTrashed()->count());
        $this->assertDatabaseCount('document_files', 2);
        Storage::disk('documents')->assertExists($replacement->files()->sole()->path);
    }

    public function test_upload_version_is_a_dropdown_and_preserves_selected_value_after_error(): void
    {
        $this->withoutVite();
        $folder = Folder::query()->where('code', '02_RENJA')->sole();
        $payload = [...$this->payload($folder->id), 'version_code' => 'V25', 'title' => ''];

        $response = $this->actingAs($this->admin)->from('/documents/create')->followingRedirects()->post('/documents', $payload);

        $response->assertOk()->assertSee('<select id="version_code" name="version_code"', false)
            ->assertSee('<option value="V25" selected>', false)->assertSee('FINAL_TTD')->assertDontSee('<datalist', false);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_existing_archive_documents_remain_downloadable_and_cannot_move_back_to_archive(): void
    {
        $this->withoutVite();
        $folder = Folder::query()->where('code', '02_RENJA')->sole();
        $this->actingAs($this->admin)->post('/documents', $this->payload($folder->id))->assertSessionHasNoErrors();
        $document = Document::query()->sole();
        $archive = Folder::factory()->create(['code' => '99_ARSIP_SEMENTARA', 'name' => 'ARSIP SEMENTARA', 'is_temporary' => true, 'folder_type' => 'temporary', 'parent_id' => null, 'year' => 2026]);
        $document->update(['folder_id' => $archive->id]);

        $this->get('/documents')->assertOk()->assertSee('Download File')->assertSee('Laporan Keuangan')->assertDontSee('ARSIP SEMENTARA');
        $this->get(route('documents.download', $document->files()->sole()))->assertOk()->assertDownload();
        $this->post(route('documents.move', $document), ['folder_id' => $archive->id])->assertRedirect()->assertSessionHasErrors('folder_id');
        $this->assertSame($archive->id, $document->fresh()->folder_id);
        Storage::disk('documents')->assertExists($document->files()->sole()->path);
    }

    public function test_documents_can_be_searched_and_filtered(): void
    {
        $folder = Folder::query()->where('is_temporary', false)->where('is_official', true)->whereNotNull('parent_id')->firstOrFail();
        $this->actingAs($this->admin)->post('/documents', $this->payload($folder->id))->assertRedirect();
        $this->actingAs($this->admin)->get('/documents?q=Keuangan&year=2026&folder_id='.$folder->id)->assertOk()->assertSee('Laporan Keuangan');
    }

    public function test_document_can_be_moved_and_soft_deleted(): void
    {
        $folders = Folder::query()->where('year', 2026)->where('is_temporary', false)->where('is_official', true)->whereNotNull('parent_id')->limit(2)->get();
        $this->actingAs($this->admin)->post('/documents', $this->payload($folders[0]->id))->assertRedirect();
        $document = Document::query()->sole();
        $this->actingAs($this->admin)->post('/documents/'.$document->id.'/move', ['folder_id' => $folders[1]->id])->assertRedirect();
        $this->assertSame($folders[1]->id, $document->fresh()->folder_id);
        $this->actingAs($this->admin)->delete('/documents/'.$document->id)->assertRedirect('/documents');
        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }

    public function test_all_official_folders_are_seeded(): void
    {
        $this->assertSame(47, Folder::query()->where('is_official', true)->count());
    }
}
