<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\ResetKataSandi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetKataSandi::class);
    }

    public function test_unregistered_email_does_not_send_reset_notification(): void
    {
        Notification::fake();

        $this->post('/forgot-password', ['email' => 'nonexistent@example.com']);

        // Verifikasi tidak ada notifikasi yang dikirim kepada siapa pun
        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetKataSandi::class);

        // Gunakan Password broker untuk create token yang valid
        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        // Verify password berubah
        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
    }

    public function test_reset_kata_sandi_mail_has_indonesian_subject(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'name' => 'Bambang Panitia',
        ]);

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetKataSandi::class, function ($notification) use ($user) {
            $mailMessage = $notification->toMail($user);

            // Verifikasi subjek berbahasa Indonesia
            $this->assertStringContainsString('Atur Ulang Kata Sandi', $mailMessage->subject);
            $this->assertStringContainsString('Portal Pelatihan Terpadu', $mailMessage->subject);

            return true;
        });
    }

    public function test_reset_kata_sandi_greeting_includes_user_name(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'name' => 'Bambang Panitia',
        ]);

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetKataSandi::class, function ($notification) use ($user) {
            $mailMessage = $notification->toMail($user);

            // Verifikasi greeting menggunakan nama
            $this->assertStringContainsString('Bambang Panitia', $mailMessage->greeting);

            return true;
        });
    }

    public function test_reset_kata_sandi_includes_expiration_and_ignore_message(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetKataSandi::class, function ($notification) use ($user) {
            $mailMessage = $notification->toMail($user);

            $expireMinutes = config('auth.passwords.users.expire');
            $outroText = implode(' ', $mailMessage->outroLines);

            // Verifikasi masa berlaku ada di email
            $this->assertStringContainsString((string) $expireMinutes, $outroText);
            $this->assertStringContainsString('menit', $outroText);

            // Verifikasi pesan abaikan ada
            $this->assertStringContainsString('Abaikan email ini', $outroText);

            return true;
        });
    }

    public function test_reset_kata_sandi_includes_valid_reset_button(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetKataSandi::class, function ($notification) use ($user) {
            $mailMessage = $notification->toMail($user);

            // Verifikasi action button ada
            $this->assertNotNull($mailMessage->actionUrl);

            // Verifikasi URL berisi path /reset-password/{token} dan email parameter
            $this->assertStringContainsString('/reset-password/', $mailMessage->actionUrl);
            $this->assertStringContainsString('email=', $mailMessage->actionUrl);
            $this->assertStringContainsString(urlencode($user->email), $mailMessage->actionUrl);

            return true;
        });
    }

    public function test_registered_email_shows_success_message(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->post('/forgot-password', [
            'email' => 'existing@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
    }

    public function test_unregistered_email_shows_error(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_email_preserved_after_error(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHas('_old_input.email', 'test@example.com');
    }

    public function test_validation_requires_email(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => '',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_validation_requires_valid_email(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors(['email']);
    }
}
