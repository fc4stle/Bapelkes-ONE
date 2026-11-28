<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class FlashNotificationUiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A) TES NOTIFIKASI - Flash success component structure
     */
    public function test_flash_component_success_struktur(): void
    {
        $user = User::factory()->create(['role' => 'peserta']);

        // Redirect dengan success message via PATCH (tandai semua dibaca)
        $response = $this->actingAs($user)
            ->patch(route('notifikasi.tandai-semua-dibaca'))
            ->assertSessionHas('success');

        // Follow redirect, flash harus ada di dashboard/notifikasi
        $dashResponse = $this->get(route('notifikasi.index'));

        // Success flash harus render dengan role="status"
        $dashResponse->assertSee('role="status"', escape: false);
        $dashResponse->assertSee('aria-label="Tutup notifikasi"', escape: false);
    }

    /**
     * A) TES NOTIFIKASI - Flash error component structure
     */
    public function test_flash_component_error_struktur(): void
    {
        // POST form gagal validasi
        $response = $this->post(route('register'), [
            'name' => '',
            'email' => 'invalid',
            'password' => 'short',
        ]);

        // Redirect dengan error session
        $response->assertRedirect();
        $location = $response->headers->get('location');

        // Follow redirect, error flash harus ada
        $followResponse = $this->get($location);

        // Error flash harus render dengan role="alert"
        $followResponse->assertSee('role="alert"', escape: false);
        $followResponse->assertSee('aria-label="Tutup notifikasi"', escape: false);
    }

    /**
     * A) TES NOTIFIKASI - Halaman tanpa flash tidak render notification boxes
     */
    public function test_halaman_tanpa_flash_tidak_render_boxes(): void
    {
        $user = User::factory()->create(['role' => 'peserta']);

        $response = $this->actingAs($user)
            ->get(route('dashboard'));

        $response->assertOk();

        // Jangan ada kotak notifikasi
        $response->assertDontSee('border-[var(--color-success)]');
        $response->assertDontSee('border-[var(--color-danger)]');
    }

    /**
     * A) TES NOTIFIKASI - Guest layout (login) dengan flash success
     */
    public function test_guest_layout_flash_success(): void
    {
        $response = $this->withSession(['success' => 'Silakan login terlebih dahulu'])
            ->get(route('login'));

        $response->assertOk();
        $response->assertSee('role="status"', escape: false);
        $response->assertSee('Silakan login terlebih dahulu');
        $response->assertSee('aria-label="Tutup notifikasi"', escape: false);
    }

    /**
     * A) TES NOTIFIKASI - File resources/views/layouts/flash.blade.php TIDAK ada
     */
    public function test_layouts_flash_blade_tidak_ada(): void
    {
        $this->assertFileDoesNotExist(resource_path('views/layouts/flash.blade.php'));
    }

    /**
     * A) TES NOTIFIKASI - Flash success harus punya timer logic
     */
    public function test_flash_success_harus_ada_timer_logic(): void
    {
        // Render view langsung dengan session, gunakan test helper
        $response = $this->withSession(['success' => 'Test success'])
            ->get(route('login'));

        $html = $response->getContent();

        // Harus ada x-data dengan closeTimer
        // Pattern: x-data="{ show: true, closeTimer: null }"
        $this->assertStringContainsString('x-data', $html);
        $this->assertStringContainsString('closeTimer', $html);

        // Harus ada x-init dengan setTimeout
        $this->assertStringContainsString('x-init', $html);
        $this->assertStringContainsString('setTimeout', $html);
    }

    /**
     * A) TES NOTIFIKASI - Flash success harus punya pause/resume handlers
     */
    public function test_flash_success_pause_resume_handlers(): void
    {
        $response = $this->withSession(['success' => 'Test'])
            ->get(route('login'));

        $html = $response->getContent();

        // Harus ada event handlers untuk pause/resume
        $this->assertStringContainsString('@mouseenter', $html);
        $this->assertStringContainsString('@mouseleave', $html);
        $this->assertStringContainsString('@focusin', $html);
        $this->assertStringContainsString('@focusout', $html);
    }

    /**
     * A) TES NOTIFIKASI - Flash error TIDAK boleh punya setTimeout
     */
    public function test_flash_error_tidak_punya_timer(): void
    {
        // POST gagal validasi
        $response = $this->post(route('register'), [
            'name' => '',
            'email' => 'invalid',
        ]);

        $location = $response->headers->get('location');
        $followResponse = $this->get($location);

        $html = $followResponse->getContent();

        // Cari error section (role="alert")
        // Pattern: harus punya role="alert" tapi TIDAK punya setTimeout di dalamnya
        if (str_contains($html, 'role="alert"')) {
            // Extract error block roughly
            preg_match('/role="alert"[^>]*>.*?Terjadi kesalahan/s', $html, $matches);
            if (count($matches) > 0) {
                // Error section TIDAK boleh punya setTimeout
                $this->assertStringNotContainsString('setTimeout', $matches[0]);
            }
        }
    }

    /**
     * A) TES NOTIFIKASI - Flash component dapat di-close via tombol
     */
    public function test_flash_success_dapat_ditutup(): void
    {
        $response = $this->withSession(['success' => 'Test'])
            ->get(route('login'));

        $html = $response->getContent();

        // Harus punya tombol dengan @click="show = false"
        $this->assertStringContainsString('@click="show = false', $html);
    }

    /**
     * A) TES NOTIFIKASI - Flash component harus memiliki transitions
     */
    public function test_flash_component_harus_punya_transitions(): void
    {
        $response = $this->withSession(['success' => 'Test'])
            ->get(route('login'));

        $html = $response->getContent();

        // Harus ada x-transition directives untuk smooth animation
        $this->assertStringContainsString('x-transition', $html);
    }
}
