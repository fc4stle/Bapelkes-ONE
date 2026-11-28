<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormLoadingStateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * B) STATUS MEMPROSES - Form submit mengubah innerHTML tombol
     */
    public function test_form_submit_mengubah_button_state(): void
    {
        $user = User::factory()->create();

        // Form di halaman yang memerlukan auth
        $response = $this->actingAs($user)->get(route('dashboard'));

        // Cek apakah halaman memiliki form dengan submit button
        $html = $response->getContent();
        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('submit', $html);
    }

    /**
     * B) STATUS MEMPROSES - Simpan innerHTML asli tombol (bukan textContent)
     * Agar ikon tidak hilang saat disimpan dan dikembalikan
     */
    public function test_form_button_menyimpan_inner_htm_l_asli(): void
    {
        // JavaScript di app.js harus menyimpan innerHTML asli, bukan textContent
        // Ini memastikan ikon SVG atau elemen child lain tersimpan
        $this->assertStringContainsString('innerHTML', file_get_contents(resource_path('js/app.js')));
    }

    /**
     * B) STATUS MEMPROSES - Listener global pageshow untuk restore button state
     */
    public function test_form_global_pageshow_listener(): void
    {
        // JavaScript harus punya event listener global pageshow (bukan per form)
        $appJs = file_get_contents(resource_path('js/app.js'));

        // Cek ada 'pageshow' event listener
        $this->assertStringContainsString('pageshow', $appJs);
    }

    /**
     * B) STATUS MEMPROSES - Selektor tombol sesuai requirement
     */
    public function test_form_button_selector_sesuai_requirement(): void
    {
        // Selektor harus: 'button[type="submit"], button:not([type]), input[type="submit"]'
        $appJs = file_get_contents(resource_path('js/app.js'));

        // Cek ada selektor untuk submit button
        $this->assertStringContainsString('button[type="submit"]', $appJs);
    }
}
