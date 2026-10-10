<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $credentials = config('super_admin');
        $credentials['email'] = Str::lower(trim((string) $credentials['email']));

        $validator = Validator::make($credentials, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:12', 'max:128'],
        ]);

        if ($validator->fails()) {
            throw new RuntimeException('Isi SUPER_ADMIN_NAME, SUPER_ADMIN_EMAIL, dan SUPER_ADMIN_PASSWORD (12–128 karakter) pada environment. Akun tidak dibuat.');
        }

        $existing = User::query()->where('email', $credentials['email'])->first();
        if ($existing) {
            $existing->forceFill(['role' => UserRole::Admin])->save();
            $this->command?->info('Akun super admin sudah ada; kredensial dan status akun tidak diubah.');

            return;
        }

        $user = new User($credentials);
        $user->role = UserRole::Admin;
        $user->is_active = true;
        $user->save();
        $this->command?->info('Akun super admin berhasil dibuat dari environment.');
    }
}
