<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentFile;
use App\Models\DocumentRevision;
use App\Models\Folder;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    public function test_super_admin_can_login_and_view_an_empty_dashboard(): void
    {
        $this->withoutVite();
        $user = User::factory()->role(UserRole::Admin)->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/dashboard')->assertSessionHasNoErrors();
        $response = $this->get('/dashboard');

        $this->assertAuthenticatedAs($user);
        $response->assertOk()->assertViewIs('dashboard')->assertViewHasAll([
            'documentTotal' => 0,
            'currentYearTotal' => 0,
            'storageSize' => '0 B',
            'folderCount' => 0,
        ])->assertViewHas('recentDocuments', fn (Collection $documents): bool => $documents->isEmpty())
            ->assertSee('0 B')->assertSee('Belum ada dokumen');
    }

    #[DataProvider('storageSizes')]
    public function test_dashboard_formats_storage_sizes(int $bytes, string $expected): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $folder = Folder::factory()->create();
        $document = Document::factory()->for($folder)->for($folder, 'targetFolder')->for($user, 'creator')->create();
        $revision = DocumentRevision::factory()->for($document)->for($user, 'submitter')->create();
        DocumentFile::factory()->for($document)->for($revision, 'revision')->for($user, 'uploader')->create(['size' => $bytes]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()->assertViewHas('storageSize', $expected)->assertSee($expected);
    }

    /** @return array<string, array{int, string}> */
    public static function storageSizes(): array
    {
        return [
            'zero bytes' => [0, '0 B'],
            'one byte' => [1, '1 B'],
            'below unit promotion' => [921, '921 B'],
            'unit promotion' => [922, '1 KB'],
            'below one kilobyte' => [1023, '1 KB'],
            'one kilobyte' => [1024, '1 KB'],
            'round half up to even' => [1536, '2 KB'],
            'round half down to even' => [2560, '2 KB'],
            'one megabyte' => [1048576, '1 MB'],
            'one gigabyte' => [1073741824, '1 GB'],
            'one terabyte' => [1099511627776, '1 TB'],
            'one petabyte' => [1125899906842624, '1 PB'],
            'one exabyte' => [1152921504606846976, '1 EB'],
            'largest integer' => [PHP_INT_MAX, '8 EB'],
        ];
    }

    public function test_dashboard_preserves_document_and_folder_statistics(): void
    {
        $this->withoutVite();
        $this->travelTo(now()->setDate(2026, 10, 13));
        $user = User::factory()->create();
        $folder = Folder::factory()->create();
        $temporaryFolder = Folder::factory()->create(['is_temporary' => true]);
        Folder::factory()->create(['is_active' => false]);
        $currentDocument = Document::factory()->for($folder)->for($folder, 'targetFolder')->for($user, 'creator')
            ->create(['year' => 2026]);
        $temporaryDocument = Document::factory()->for($temporaryFolder)->for($folder, 'targetFolder')->for($user, 'creator')
            ->create(['year' => 2025]);
        $deletedDocument = Document::factory()->for($folder)->for($folder, 'targetFolder')->for($user, 'creator')
            ->create(['year' => 2026]);
        foreach ([$currentDocument, $temporaryDocument, $deletedDocument] as $document) {
            $revision = DocumentRevision::factory()->for($document)->for($user, 'submitter')->create();
            DocumentFile::factory()->for($document)->for($revision, 'revision')->for($user, 'uploader')->create(['size' => 1024]);
        }
        $deletedDocument->delete();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()->assertViewHasAll([
            'documentTotal' => 2,
            'currentYearTotal' => 1,
            'storageSize' => '2 KB',
            'folderCount' => 1,
        ])->assertViewHas('recentDocuments', fn (Collection $documents): bool => $documents->modelKeys() === [$temporaryDocument->id, $currentDocument->id]);
    }
}
