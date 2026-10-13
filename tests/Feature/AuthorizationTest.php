<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    public function test_active_admin_can_use_application_abilities(): void
    {
        $gate = Gate::forUser(User::factory()->create());
        foreach (['view-dashboard', 'upload-documents'] as $ability) {
            $this->assertTrue($gate->allows($ability));
        }
    }

    public function test_inactive_admin_is_denied_all_abilities(): void
    {
        $gate = Gate::forUser(User::factory()->inactive()->make());
        $this->assertFalse($gate->allows('view-dashboard'));
        $this->assertFalse($gate->allows('upload-documents'));
    }

    public function test_dashboard_navigation_has_no_role_workflow_menu(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk();
        $response->assertSee('Upload Dokumen')->assertSee('Download File')->assertDontSee('Arsip Sementara');
        $response->assertDontSee('Antrean Verifikasi')->assertDontSee('Manajemen Pengguna');
    }

    public function test_dashboard_calls_authorization_gate(): void
    {
        Gate::define('view-dashboard', fn (User $user): bool => false);
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertForbidden();
    }
}
