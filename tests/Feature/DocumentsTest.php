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

    public function test_archive_folder_is_available_as_optional_target(): void
    {
        $folder = Folder::query()->where('is_temporary', true)->firstOrFail();
        $this->actingAs($this->admin)->get('/documents/create')->assertOk()->assertSee('Arsip Sementara');
        $this->actingAs($this->admin)->post('/documents', $this->payload($folder->id))->assertRedirect();
        $this->assertSame($folder->id, Document::query()->sole()->folder_id);
    }

    public function test_duplicate_checksum_is_rejected(): void
    {
        $folder = Folder::query()->where('is_temporary', false)->where('is_official', true)->whereNotNull('parent_id')->firstOrFail();
        $payload = $this->payload($folder->id);
        $this->actingAs($this->admin)->post('/documents', $payload)->assertRedirect();
        $this->actingAs($this->admin)->post('/documents', $this->payload($folder->id))->assertSessionHasErrors('file');
        $this->assertDatabaseCount('documents', 1);
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
        $this->assertSame(48, Folder::query()->where('is_official', true)->count());
    }
}
