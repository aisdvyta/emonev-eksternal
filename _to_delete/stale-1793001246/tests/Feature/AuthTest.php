<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\SeedsPortal;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, SeedsPortal;

    public function test_login_page_is_public_and_there_is_no_registration(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk');
        $this->get('/register')->assertNotFound();
        $this->get('/auth/sso')->assertNotFound();
    }

    public function test_login_with_email_or_username(): void
    {
        $this->seedPortal();

        $this->post('/login', ['login' => 'penilai100@example.test', 'password' => self::PASSWORD])->assertRedirect(route('penilai-eksternal.index'));
        $this->assertAuthenticatedAs(User::find(100));
        $this->post('/logout')->assertRedirect('/login');

        $this->post('/login', ['login' => 'penilai100', 'password' => self::PASSWORD])->assertRedirect(route('penilai-eksternal.index'));
        $this->assertAuthenticatedAs(User::find(100));
    }

    public function test_wrong_password_or_unknown_account_gives_same_message(): void
    {
        $this->seedPortal();

        $a = $this->post('/login', ['login' => 'penilai100@example.test', 'password' => 'salah-salah-1']);
        $b = $this->post('/login', ['login' => 'tidak-ada@example.test', 'password' => 'salah-salah-1']);

        $a->assertSessionHasErrors(['login' => 'Akun atau kata sandi salah.']);
        $b->assertSessionHasErrors(['login' => 'Akun atau kata sandi salah.']);
        $this->assertGuest();
    }

    public function test_inactive_account_cannot_login(): void
    {
        $this->seedPortal();
        DB::table('users')->where('id_user', 100)->update(['is_active' => false]);

        $this->post('/login', ['login' => 'penilai100@example.test', 'password' => self::PASSWORD])->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        $this->seedPortal();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['login' => 'penilai100@example.test', 'password' => 'salah-salah-1']);
        }

        $this->post('/login', ['login' => 'penilai100@example.test', 'password' => self::PASSWORD])->assertStatus(429);
        $this->assertGuest();
    }

    public function test_otp_flow_requires_emailed_code(): void
    {
        $this->seedPortal();
        config(['external.login_otp' => true]);

        $this->post('/login', ['login' => 'penilai100@example.test', 'password' => self::PASSWORD])->assertRedirect(route('login.otp'));
        $this->assertGuest();

        $messages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $this->assertTrue((bool) preg_match('/Anda: (\d{6})/', $messages[0]->toString(), $m), 'kode OTP tidak ada pada email');
        $code = $m[1];
        $wrong = $code === '000000' ? '111111' : '000000';

        $this->post('/login/kode', ['kode' => $wrong])->assertSessionHasErrors('kode');
        $this->assertGuest();

        $this->post('/login/kode', ['kode' => $code])->assertRedirect(route('penilai-eksternal.index'));
        $this->assertAuthenticatedAs(User::find(100));
    }

    public function test_otp_page_without_password_step_redirects_to_login(): void
    {
        config(['external.login_otp' => true]);

        $this->get('/login/kode')->assertRedirect('/login');
        $this->post('/login/kode', ['kode' => '123456'])->assertRedirect('/login');
    }

    public function test_otp_is_locked_after_too_many_wrong_attempts(): void
    {
        $this->seedPortal();
        config(['external.login_otp' => true, 'external.otp_max_attempts' => 3]);

        $this->post('/login', ['login' => 'penilai100@example.test', 'password' => self::PASSWORD]);
        preg_match('/Anda: (\d{6})/', app('mailer')->getSymfonyTransport()->messages()[0]->toString(), $m);
        $code = $m[1];
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 3; $i++) {
            $this->post('/login/kode', ['kode' => $wrong]);
        }

        // Percobaan ke-4 dengan kode BENAR tetap ditolak karena kode sudah dihanguskan.
        $this->post('/login/kode', ['kode' => $code])->assertSessionHasErrors('kode');
        $this->assertGuest();
    }

    public function test_password_change_requires_current_password_and_strength(): void
    {
        $this->seedPortal();
        $user = User::find(100);

        $this->actingAs($user)->put('/profil/password', [
            'current_password' => 'salah', 'password' => 'BaruBaru12345', 'password_confirmation' => 'BaruBaru12345',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($user)->put('/profil/password', [
            'current_password' => self::PASSWORD, 'password' => 'pendek1', 'password_confirmation' => 'pendek1',
        ])->assertSessionHasErrors('password');

        $this->actingAs($user)->put('/profil/password', [
            'current_password' => self::PASSWORD, 'password' => 'BaruBaru12345', 'password_confirmation' => 'BaruBaru12345',
        ])->assertSessionHas('success');

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('BaruBaru12345', User::find(100)->password));
    }

    public function test_security_headers_are_present(): void
    {
        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_dashboard_renders_for_assigned_user(): void
    {
        $this->seedPortal();

        $this->actingAs(User::find(100))->get('/penilai-eksternal')->assertOk()->assertSee('Beranda Penilai Eksternal');
        $this->actingAs(User::find(100))->get('/')->assertOk();
    }
}
