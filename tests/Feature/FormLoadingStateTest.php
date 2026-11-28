<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormLoadingStateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * B) STATUS MEMPROSES - Halaman login menampilkan form dengan submit button
     */
    public function test_login_page_memiliki_form_dan_submit_button(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $html = $response->getContent();

        // Cek form ada
        $this->assertStringContainsString('<form', $html);
        // Cek submit button ada
        $this->assertStringContainsString('type="submit"', $html);
    }

    /**
     * B) STATUS MEMPROSES - Simpan innerHTML asli tombol (bukan textContent)
     * Agar ikon tidak hilang saat disimpan dan dikembalikan
     */
    public function test_form_button_menyimpan_inner_html_asli(): void
    {
        // JavaScript di app.js harus menyimpan innerHTML asli, bukan textContent
        // Ini memastikan ikon SVG atau elemen child lain tersimpan
        $appJs = file_get_contents(resource_path('js/app.js'));

        // Cek ada 'dataset.originalHtml' atau 'innerHTML' di Map
        $this->assertStringContainsString('innerHTML', $appJs);
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
        // Cek ada event.persisted untuk bfcache detection
        $this->assertStringContainsString('persisted', $appJs);
    }

    /**
     * B) STATUS MEMPROSES - Gunakan e.submitter jika tersedia
     */
    public function test_form_handler_gunakan_e_submitter(): void
    {
        $appJs = file_get_contents(resource_path('js/app.js'));

        // Cek ada reference ke e.submitter
        $this->assertStringContainsString('e.submitter', $appJs);
    }

    /**
     * B) STATUS MEMPROSES - Selektor tombol fallback jika submitter tidak ada
     */
    public function test_form_button_selector_fallback(): void
    {
        $appJs = file_get_contents(resource_path('js/app.js'));

        // Selektor fallback harus ada: 'button[type="submit"], button:not([type]), input[type="submit"]'
        $this->assertStringContainsString('button[type="submit"]', $appJs);
        $this->assertStringContainsString('button:not([type])', $appJs);
        $this->assertStringContainsString('input[type="submit"]', $appJs);
    }
}
