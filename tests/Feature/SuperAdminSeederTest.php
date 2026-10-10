<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class SuperAdminSeederTest extends TestCase
{
    private function configureAdmin(): void
    {
        config(['super_admin' => ['name' => 'Admin Kecamatan', 'email' => ' ADMIN@example.test ', 'password' => 'Test-only-secret-2026!']]);
    }

    public function test_admin_is_created_with_hashed_environment_password(): void
    {
        $this->configureAdmin();
        $this->seed(SuperAdminSeeder::class);
        $user = User::query()->sole();
        $this->assertSame('admin@example.test', $user->email);
        $this->assertSame(UserRole::Admin, $user->role);
        $this->assertTrue(Hash::check('Test-only-secret-2026!', $user->password));
    }

    public function test_reseeding_preserves_existing_account_state(): void
    {
        $this->configureAdmin();
        $this->seed(SuperAdminSeeder::class);
        $user = User::query()->sole();
        $user->forceFill(['is_active' => false])->save();
        $password = $user->password;
        $this->seed(SuperAdminSeeder::class);
        $this->assertSame($password, $user->fresh()->password);
        $this->assertFalse($user->fresh()->is_active);
    }

    public function test_existing_account_with_admin_email_is_normalized_to_admin(): void
    {
        $this->configureAdmin();
        User::factory()->create(['email' => 'admin@example.test']);
        $this->seed(SuperAdminSeeder::class);
        $this->assertSame(UserRole::Admin, User::query()->sole()->role);
    }

    public function test_missing_credentials_do_not_create_account(): void
    {
        config(['super_admin' => ['name' => null, 'email' => null, 'password' => null]]);
        $this->expectException(RuntimeException::class);
        $this->seed(SuperAdminSeeder::class);
    }
}
