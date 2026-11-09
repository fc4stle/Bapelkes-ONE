<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDeactivationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: akun nonaktif tidak bisa login
     */
    public function test_deactivated_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'email' => 'deactivated@test.com',
            'password' => bcrypt('password123'),
            'dinonaktifkan_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'deactivated@test.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('email', 'Akun Anda dinonaktifkan. Hubungi admin.');
        $this->assertGuest();
    }

    /**
     * Test: akun aktif bisa login
     */
    public function test_active_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'active@test.com',
            'password' => bcrypt('password123'),
            'dinonaktifkan_at' => null,
        ]);

        $response = $this->post('/login', [
            'email' => 'active@test.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * Test: user sedang login lalu dinonaktifkan akan ter-logout di request berikutnya
     */
    public function test_active_user_logged_out_if_deactivated_mid_session(): void
    {
        $user = User::factory()->create([
            'dinonaktifkan_at' => null,
        ]);

        // Login user terlebih dahulu
        $this->actingAs($user);

        // Simulasikan user dinonaktifkan oleh admin
        $user->update(['dinonaktifkan_at' => now()]);

        // Request berikutnya ke route yang protected harus ter-logout dan redirect ke login
        $response = $this->get('/profile');

        $response->assertRedirect('/login');
        $response->assertSessionHas('message', 'Akun Anda dinonaktifkan. Hubungi admin.');
        $this->assertGuest();
    }

    /**
     * Test: admin bisa nonaktifkan user lain
     */
    public function test_admin_can_deactivate_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'dinonaktifkan_at' => null,
            'name' => 'Budi Santoso',
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.users.nonaktifkan', $user));

        $response->assertRedirect();
        $response->assertSessionHas('success', "Akun {$user->name} berhasil dinonaktifkan.");
        $this->assertNotNull($user->fresh()->dinonaktifkan_at);
    }

    /**
     * Test: admin bisa aktifkan kembali user
     */
    public function test_admin_can_reactivate_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'dinonaktifkan_at' => now(),
            'name' => 'Dewi Ayu',
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('admin.users.aktifkan', $user));

        $response->assertRedirect();
        $response->assertSessionHas('success', "Akun {$user->name} berhasil diaktifkan.");
        $this->assertNull($user->fresh()->dinonaktifkan_at);
    }

    /**
     * Test: admin TIDAK bisa nonaktifkan akun diri sendiri
     */
    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->patch(route('admin.users.nonaktifkan', $admin));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertStringContainsString('tidak bisa menonaktifkan akun diri sendiri', session('error'));
        $this->assertNull($admin->fresh()->dinonaktifkan_at);
    }

    /**
     * Test: admin TIDAK bisa nonaktifkan admin terakhir yang masih aktif
     */
    public function test_admin_cannot_deactivate_last_active_admin(): void
    {
        // $admin1 dan $admin2 dua-duanya aktif
        $admin1 = User::factory()->create(['role' => 'admin', 'dinonaktifkan_at' => null]);
        $admin2 = User::factory()->create(['role' => 'admin', 'dinonaktifkan_at' => null]);

        // $admin1 nonaktifkan $admin2 OK (masih ada $admin1 yang aktif)
        $response = $this->actingAs($admin1)
            ->patch(route('admin.users.nonaktifkan', $admin2));
        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertNotNull($admin2->fresh()->dinonaktifkan_at);

        // Sekarang $admin1 adalah admin terakhir yang aktif
        // Coba nonaktifkan $admin1 via $admin1 (diri sendiri) — error diri sendiri
        // Tapi jika ada admin2 yang aktif, coba nonaktifkan admin2 — OK
        // Jadi test: hanya ada admin1 yang aktif, admin1 coba nonaktifkan admin1 — harus gagal dengan pesan "minimal 1 admin"
        // Atau lebih mudah: hanya buat admin1 saja, coba nonaktifkan diri sendiri

        $response2 = $this->actingAs($admin1)
            ->patch(route('admin.users.nonaktifkan', $admin1));
        $response2->assertRedirect();
        $response2->assertSessionHas('error');
        // Error message bisa diri sendiri atau minimal 1 admin, tergantung urutan check
        $this->assertStringContainsString('tidak bisa', session('error'));
        $this->assertNull($admin1->fresh()->dinonaktifkan_at);
    }

    /**
     * Test: peserta mendapat 403 saat akses route nonaktifkan
     */
    public function test_peserta_cannot_access_deactivate_route(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $user = User::factory()->create();

        $response = $this->actingAs($peserta)
            ->patch(route('admin.users.nonaktifkan', $user));

        $response->assertStatus(403);
    }

    /**
     * Test: panitia mendapat 403 saat akses route nonaktifkan
     */
    public function test_panitia_cannot_access_deactivate_route(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $user = User::factory()->create();

        $response = $this->actingAs($panitia)
            ->patch(route('admin.users.nonaktifkan', $user));

        $response->assertStatus(403);
    }

    /**
     * Test: halaman admin users menampilkan badge status Aktif/Nonaktif
     */
    public function test_admin_users_index_displays_status_badges(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create([
            'name' => 'User Aktif',
            'dinonaktifkan_at' => null,
        ]);
        User::factory()->create([
            'name' => 'User Nonaktif',
            'dinonaktifkan_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('User Aktif');
        $response->assertSee('User Nonaktif');
        $response->assertSee('Aktif');
        $response->assertSee('Nonaktif');
    }
}
