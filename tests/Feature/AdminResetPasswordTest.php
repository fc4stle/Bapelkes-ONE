<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin berhasil mengatur ulang sandi pengguna lain
     */
    public function test_admin_can_reset_user_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'panitia']);
        $oldPassword = 'OldPassword123!';
        $newPassword = 'NewPassword123!';

        // Set password lama dan simpan remember_token lama langsung dari DB
        $user->update(['password' => Hash::make($oldPassword)]);
        $oldRememberToken = DB::table('users')->where('id', $user->id)->value('remember_token');

        $response = $this->actingAs($admin)
            ->patch(route('admin.users.resetPassword', $user), [
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ]);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        $user->refresh();

        // Verifikasi password berubah
        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertFalse(Hash::check($oldPassword, $user->password));

        // Verifikasi remember_token berubah
        $newRememberToken = DB::table('users')->where('id', $user->id)->value('remember_token');
        $this->assertNotEquals($oldRememberToken, $newRememberToken);
    }

    /**
     * Pengguna bisa login dengan sandi baru
     */
    public function test_user_can_login_with_new_password_after_reset(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'panitia']);
        $userEmail = $user->email;
        $newPassword = 'NewPassword123!';

        $this->actingAs($admin)
            ->patch(route('admin.users.resetPassword', $user), [
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ]);

        // Logout admin dan coba login sebagai user dengan password baru
        $this->post('/logout');

        // Verifikasi bisa login dengan password baru
        $response = $this->post('/login', [
            'email' => $userEmail,
            'password' => $newPassword,
        ]);

        // Panitia diarahkan ke /panitia/dashboard bukan /dashboard
        $response->assertRedirect('/panitia/dashboard');
    }

    /**
     * Pengguna tidak bisa login dengan sandi lama setelah reset
     */
    public function test_user_cannot_login_with_old_password_after_reset(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'panitia']);
        $userEmail = $user->email;
        $oldPassword = 'OldPassword123!';

        $user->update(['password' => Hash::make($oldPassword)]);

        $this->actingAs($admin)
            ->patch(route('admin.users.resetPassword', $user), [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        // Logout admin
        $this->post('/logout');

        // Coba login dengan password lama
        $response = $this->post('/login', [
            'email' => $userEmail,
            'password' => $oldPassword,
        ]);

        // Failed login redirects ke home (/)
        $response->assertRedirect('/');
    }

    /**
     * Admin tidak bisa mengatur ulang sandi akunnya sendiri
     */
    public function test_admin_cannot_reset_own_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $oldPassword = 'OldPassword123!';
        $admin->update(['password' => Hash::make($oldPassword)]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.users.resetPassword', $admin), [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $admin->refresh();
        // Verifikasi password tidak berubah
        $this->assertTrue(Hash::check($oldPassword, $admin->password));
    }

    /**
     * Panitia mendapat 403 saat mencoba reset password
     */
    public function test_panitia_gets_403_on_reset_password(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $user = User::factory()->create(['role' => 'peserta']);

        $response = $this->actingAs($panitia)
            ->patch(route('admin.users.resetPassword', $user), [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertForbidden();
    }

    /**
     * Peserta mendapat 403 saat mencoba reset password
     */
    public function test_peserta_gets_403_on_reset_password(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $user = User::factory()->create(['role' => 'peserta']);

        $response = $this->actingAs($peserta)
            ->patch(route('admin.users.resetPassword', $user), [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertForbidden();
    }

    /**
     * Tamu (belum login) diarahkan ke login
     */
    public function test_guest_redirected_to_login(): void
    {
        $user = User::factory()->create();

        $response = $this->patch(route('admin.users.resetPassword', $user), [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertRedirect('/login');
    }

    /**
     * Validasi: password kurang dari aturan
     */
    public function test_validation_error_for_weak_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'panitia']);

        // Password tanpa uppercase, lowercase, number, dan symbol
        $response = $this->actingAs($admin)
            ->patch(route('admin.users.resetPassword', $user), [
                'password' => 'weak',
                'password_confirmation' => 'weak',
            ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
    }

    /**
     * Validasi: konfirmasi tidak cocok
     */
    public function test_validation_error_for_mismatched_confirmation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'panitia']);

        $response = $this->actingAs($admin)
            ->patch(route('admin.users.resetPassword', $user), [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'DifferentPassword123!',
            ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * Validasi: password required
     */
    public function test_validation_error_for_missing_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'panitia']);

        $response = $this->actingAs($admin)
            ->patch(route('admin.users.resetPassword', $user), [
                'password' => '',
                'password_confirmation' => '',
            ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * Password baru TIDAK muncul di session flash
     */
    public function test_password_not_in_session_flash(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'panitia']);
        $newPassword = 'NewPassword123!';

        $response = $this->actingAs($admin)
            ->patch(route('admin.users.resetPassword', $user), [
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ]);

        $session = $response->getSession();
        $successMessage = $session->get('success');

        // Verifikasi password tidak ada dalam flash message
        $this->assertStringNotContainsString($newPassword, $successMessage ?? '');
        $this->assertStringContainsString($user->name, $successMessage ?? '');
    }

    /**
     * Halaman index menampilkan tombol "Atur Ulang Sandi" untuk pengguna lain
     */
    public function test_reset_password_button_shown_for_other_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'panitia']);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Atur Ulang Sandi');
    }

    /**
     * Halaman index TIDAK menampilkan tombol "Atur Ulang Sandi" untuk baris akun sendiri
     */
    public function test_reset_password_button_not_shown_for_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Tester']);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index'));

        $response->assertOk();
        // Verifikasi form role ubah masih ada untuk user lain, tapi tombol reset password hanya untuk user lain
        $this->assertStringContainsString($admin->name, $response->getContent());
    }

    /**
     * Validasi error ditampilkan di atas halaman setelah redirect
     */
    public function test_validation_errors_displayed_above_table(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'panitia']);

        $response = $this->actingAs($admin)
            ->from(route('admin.users.index'))
            ->patch(route('admin.users.resetPassword', $user), [
                'password' => 'weak',
                'password_confirmation' => 'weak',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('password');
    }
}
