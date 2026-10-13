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

class CustomFolderTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FolderSeeder::class);
        $this->admin = User::factory()->create();
        Storage::fake('documents');
    }

    public function test_admin_can_create_a_normalized_custom_folder(): void
    {
        $parent = Folder::query()->where('year', 2026)->whereNull('parent_id')->where('is_temporary', false)->firstOrFail();
        $response = $this->actingAs($this->admin)->post('/folders', ['year' => 2026, 'parent_id' => $parent->id, 'name' => 'Dokumen  Kegiatan Kecamatan', 'code' => '08']);
        $folder = Folder::query()->where('is_custom', true)->sole();
        $response->assertRedirect(route('documents.index', ['folder_id' => $folder->id]));
        $this->assertSame('06_DOKUMEN_KEGIATAN_KECAMATAN', $folder->code);
        $this->assertFalse($folder->is_official);
    }

    public function test_folder_numbers_are_assigned_automatically_and_names_must_be_unique(): void
    {
        $parent = Folder::query()->where('year', 2026)->whereNull('parent_id')->where('is_temporary', false)->firstOrFail();
        $this->actingAs($this->admin)->post('/folders', ['year' => 2026, 'parent_id' => $parent->id, 'name' => 'Satu', 'code' => '99'])->assertRedirect();
        $this->actingAs($this->admin)->post('/folders', ['year' => 2026, 'parent_id' => $parent->id, 'name' => 'Dua', 'code' => '00'])->assertRedirect();
        $this->assertSame(['06_SATU', '07_DUA'], Folder::query()->where('is_custom', true)->orderBy('id')->pluck('code')->all());
        $this->actingAs($this->admin)->post('/folders', ['year' => 2026, 'parent_id' => $parent->id, 'name' => 'Satu'])->assertSessionHasErrors('name');
    }

    public function test_custom_folder_is_available_for_upload(): void
    {
        $parent = Folder::query()->where('year', 2026)->whereNull('parent_id')->where('is_temporary', false)->firstOrFail();
        $this->actingAs($this->admin)->post('/folders', ['year' => 2026, 'parent_id' => $parent->id, 'name' => 'Dokumen Custom', 'code' => '08']);
        $folder = Folder::query()->where('is_custom', true)->sole();
        $file = UploadedFile::fake()->createWithContent('custom.pdf', "%PDF-1.4\n".str_repeat('0', 2048)."\n%%EOF");
        $this->actingAs($this->admin)->post('/documents', ['year' => 2026, 'group_folder_id' => $parent->id, 'target_folder_id' => $folder->id, 'title' => 'Dokumen Custom', 'timing' => 'none', 'version_code' => 'V01', 'file_role' => FileRole::FinalReference->value, 'file' => $file])->assertRedirect();
        $document = Document::query()->sole();
        $this->assertSame($folder->id, $document->folder_id);
        Storage::disk('documents')->assertExists($document->files()->sole()->path);
    }

    public function test_custom_root_folder_can_be_created_without_parent(): void
    {
        $response = $this->actingAs($this->admin)->post('/folders', ['year' => 2026, 'parent_id' => null, 'name' => 'Folder Utama Baru', 'code' => '08'])->assertSessionHasNoErrors();
        $folder = Folder::query()->where('is_custom', true)->sole();

        $response->assertRedirect(route('documents.index', ['folder_id' => $folder->id]));
        $this->assertNull($folder->parent_id);
        $this->assertSame('group', $folder->folder_type->value);
    }

    public function test_archive_route_is_removed(): void
    {
        $this->actingAs($this->admin)->get('/arsip-sementara')->assertNotFound();
    }

    public function test_custom_folder_can_be_renamed_and_deleted_when_empty(): void
    {
        $parent = Folder::query()->where('year', 2026)->whereNull('parent_id')->where('is_temporary', false)->firstOrFail();
        $this->actingAs($this->admin)->post('/folders', ['year' => 2026, 'parent_id' => $parent->id, 'name' => 'Folder Lama']);
        $folder = Folder::query()->where('is_custom', true)->sole();

        $this->actingAs($this->admin)->get('/folders/'.$folder->id.'/edit')->assertOk()->assertSee('Edit Folder');
        $this->actingAs($this->admin)->put('/folders/'.$folder->id, ['name' => 'Folder Baru'])->assertRedirect();
        $this->assertSame('FOLDER_BARU', $folder->fresh()->name);
        $this->actingAs($this->admin)->delete('/folders/'.$folder->id)->assertRedirect();
        $this->assertDatabaseHas('folders', ['id' => $folder->id, 'is_active' => false]);
    }

    public function test_official_folder_can_be_edited_and_removed_when_empty(): void
    {
        $folder = Folder::query()->where('is_official', true)->whereNotNull('parent_id')->whereDoesntHave('children')->firstOrFail();
        $this->actingAs($this->admin)->get('/folders/'.$folder->id.'/edit')->assertOk();
        $this->actingAs($this->admin)->put('/folders/'.$folder->id, ['name' => $folder->name.' DIUBAH'])->assertRedirect();
        $this->actingAs($this->admin)->delete('/folders/'.$folder->id)->assertRedirect();
        $this->assertDatabaseHas('folders', ['id' => $folder->id, 'is_active' => false]);
    }
}
