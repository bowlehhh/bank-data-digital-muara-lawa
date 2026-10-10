<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\UserRole;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticatedSessionControllerTest extends TestCase
{
    public function test_login_page_is_available_without_public_registration(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk ke aplikasi')->assertDontSee('Daftar akun');
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
        $this->get('/logout')->assertMethodNotAllowed();
    }

    public function test_private_storage_has_no_direct_http_routes(): void
    {
        $this->get('/storage/example.pdf')->assertNotFound();
        $this->put('/storage/example.pdf')->assertNotFound();
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_regenerates_session_and_records_last_login(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = User::factory()->create(['email' => 'pegawai@example.test']);
        $this->withSession(['before_login' => true]);
        $sessionId = session()->getId();
        $token = session()->token();

        $this->post('/login', ['email' => ' PEGAWAI@example.test ', 'password' => 'password', 'role' => 'super_admin'])
            ->assertRedirect('/dashboard')->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionId, session()->getId());
        $this->assertNotSame($token, session()->token());
        $this->assertTrue($user->fresh()->last_login_at->equalTo(now()));
        $this->assertSame(UserRole::Admin, $user->fresh()->role);
    }

    public function test_wrong_password_is_rejected_without_recording_login(): void
    {
        $user = User::factory()->create();

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'incorrect'])
            ->assertRedirect('/login')->assertSessionHasErrors('email')->assertSessionMissing('_old_input.password');

        $this->assertGuest();
        $this->assertNull($user->fresh()->last_login_at);
    }

    public function test_inactive_account_cannot_login(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertNull($user->fresh()->last_login_at);
    }

    public function test_active_session_is_revoked_when_account_is_deactivated(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->forceFill(['is_active' => false])->save();

        $this->get('/dashboard')->assertRedirect('/login')->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_blocked_after_five_failures_then_recovers(): void
    {
        $this->travelTo(now()->startOfSecond());
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Terlalu banyak percobaan. Coba lagi dalam 60 detik.']);
        $this->assertGuest();

        $this->travel(61)->seconds();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_successful_login_clears_failed_attempts(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/dashboard');

        $this->assertSame(0, RateLimiter::attempts('login:'.hash('sha256', $user->email.'|127.0.0.1')));
    }

    public function test_logout_invalidates_session_and_csrf_token(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['private_data' => 'test']);
        $oldToken = session()->token();
        $oldId = session()->getId();

        $this->post('/logout')->assertRedirect('/login')->assertSessionMissing('private_data');

        $this->assertGuest();
        $this->assertNotSame($oldToken, session()->token());
        $this->assertNotSame($oldId, session()->getId());
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_user_is_redirected_from_login(): void
    {
        $this->actingAs(User::factory()->create())->get('/login')->assertRedirect('/dashboard');
    }

    #[DataProvider('invalidCredentials')]
    public function test_invalid_login_input_has_indonesian_validation_message(array $data, array $errors): void
    {
        $this->post('/login', $data)->assertSessionHasErrors($errors);
        $this->assertGuest();
    }

    public static function invalidCredentials(): array
    {
        return [
            'required' => [[], ['email' => 'Alamat email wajib diisi.', 'password' => 'Kata sandi wajib diisi.']],
            'email format' => [['email' => 'bukan-email', 'password' => 'secret'], ['email' => 'Masukkan alamat email yang valid.']],
            'email string' => [['email' => ['injection'], 'password' => 'secret'], ['email' => 'Alamat email harus berupa teks.']],
            'email length' => [['email' => str_repeat('a', 250).'@example.test', 'password' => 'secret'], ['email' => 'Alamat email maksimal 255 karakter.']],
            'password string' => [['email' => 'a@example.test', 'password' => ['secret']], ['password' => 'Kata sandi harus berupa teks.']],
            'password length' => [['email' => 'a@example.test', 'password' => str_repeat('a', 4097)], ['password' => 'Kata sandi terlalu panjang.']],
        ];
    }

    public function test_login_and_logout_require_csrf_protection(): void
    {
        $this->app->instance('env', 'local');
        $this->post('/login', ['email' => 'a@example.test', 'password' => 'secret'])->assertStatus(419)->assertSee('Sesi Anda telah berakhir');
        $this->actingAs(User::factory()->create())->post('/logout')->assertStatus(419)->assertSee('Sesi Anda telah berakhir');
    }
}
