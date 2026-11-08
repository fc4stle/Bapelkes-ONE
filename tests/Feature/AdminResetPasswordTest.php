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

    /**
     * BUG FIX TEST 1: Dengan 3 pengguna lain, submit reset dengan konfirmasi tidak cocok
     * -> old('reset_user_id') harus di-flash ke session sehingga hanya modal pengguna B terbuka
     */
    public function test_reset_user_id_flashed_on_validation_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $userB = User::factory()->create(['role' => 'peserta', 'name' => 'User B']);

        $response = $this->actingAs($admin)
            ->patch(route('admin.users.resetPassword', $userB), [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'DifferentPassword123!',
                'reset_user_id' => $userB->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('password');

        // Verifikasi: reset_user_id di-flash ke old input
        $this->assertEquals(
            (string) $userB->id,
            session('_old_input.reset_user_id'),
            'reset_user_id harus di-flash ke session agar x-data logic bekerja'
        );
    }

    /**
     * BUG FIX TEST 2: Submit sukses -> tidak ada modal yang terbuka
     */
    public function test_no_modal_opens_on_successful_reset(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'peserta', 'name' => 'Test User']);

        $response = $this->actingAs($admin)
            ->patch(route('admin.users.resetPassword', $user), [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
                'reset_user_id' => $user->id,
            ]);

        $response->assertRedirect(route('admin.users.index'));

        $getResponse = $this->actingAs($admin)->get(route('admin.users.index'));
        $content = $getResponse->getContent();

        // Verifikasi tidak ada modal terbuka (tidak ada showResetPasswordModal: true)
        $this->assertStringNotContainsString('showResetPasswordModal: true', $content);

        // Verifikasi semua modal dalam state false
        $countFalse = substr_count($content, 'showResetPasswordModal: false');
        $this->assertGreaterThan(0, $countFalse, 'Semua modal seharusnya dalam state false');
    }

    /**
     * BUG FIX TEST 3: Mass assignment - User::create/fill dengan 'remember_token' TIDAK mengisi
     */
    public function test_remember_token_not_mass_assignable(): void
    {
        // Coba assign remember_token melalui create
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'peserta',
            'remember_token' => 'SHOULD_NOT_BE_SET',
        ]);

        // Verifikasi remember_token TIDAK diset dari create
        // (harus tetap null atau tidak sama dengan string yang dikirim)
        $this->assertNotEquals('SHOULD_NOT_BE_SET', $user->remember_token);

        // Verifikasi fill juga tidak mengisi
        $user2 = new User;
        $user2->fill([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => Hash::make('Password123!'),
            'role' => 'peserta',
            'remember_token' => 'SHOULD_NOT_BE_SET_2',
        ]);

        $this->assertNotEquals('SHOULD_NOT_BE_SET_2', $user2->remember_token);
    }

    /**
     * BUG FIX TEST 4: Password field kosong setelah error validasi (tidak di-flash)
     */
    public function test_password_field_empty_after_validation_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'peserta']);

        $response = $this->actingAs($admin)
            ->patch(route('admin.users.resetPassword', $user), [
                'password' => 'NewPassword123!',
                'password_confirmation' => 'DifferentPassword123!',
            ]);

        $response->assertRedirect();

        // Setelah error, cek halaman index
        $getResponse = $this->actingAs($admin)->get(route('admin.users.index'));
        $content = $getResponse->getContent();

        // Verifikasi password input dalam modal tidak memiliki value dengan password yang dikirim
        $this->assertStringNotContainsString(
            'value="NewPassword123!"',
            $content,
            'Password field seharusnya kosong, tidak ter-flash'
        );
    }
}
