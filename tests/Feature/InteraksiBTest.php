<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InteraksiBTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Halaman beranda memuat minimal 3 elemen data-reveal
     */
    public function test_homepage_loads_data_reveal_elements(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        $html = $response->getContent();
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);
        $reveals = $xpath->query('//*[@data-reveal]');

        $this->assertGreaterThanOrEqual(3, $reveals->length, 'Halaman beranda harus memuat minimal 3 elemen data-reveal');
    }

    /**
     * Test: Dashboard panitia memuat 4 elemen data-count-to dengan nilai benar
     */
    public function test_panitia_dashboard_loads_data_count_to_elements(): void
    {
        $user = User::factory()->create(['role' => 'panitia']);
        $pelatihan1 = Pelatihan::factory()->create();
        $pelatihan2 = Pelatihan::factory()->create();

        // Tambah beberapa pendaftaran
        Pendaftaran::factory(5)->create(['pelatihan_id' => $pelatihan1->id, 'status_verifikasi' => 'diverifikasi']);
        Pendaftaran::factory(2)->create(['pelatihan_id' => $pelatihan2->id, 'status_verifikasi' => 'ditolak']);

        $response = $this->actingAs($user)->get('/panitia/dashboard');
        $response->assertStatus(200);

        $html = $response->getContent();
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);

        // Cari semua elemen data-count-to
        $countElements = $xpath->query('//*[@data-count-to]');
        $this->assertGreaterThanOrEqual(4, $countElements->length, 'Dashboard panitia harus memuat minimal 4 elemen data-count-to');

        // Verifikasi bahwa nilai teks dalam elemen cocok dengan data-count-to
        for ($i = 0; $i < min(4, $countElements->length); $i++) {
            $el = $countElements->item($i);
            $countVal = (int) $el->getAttribute('data-count-to');
            $textVal = (int) trim($el->textContent);

            // Nilai teks awal harus sama dengan data-count-to (tanpa JS)
            $this->assertEquals($countVal, $textVal, "Elemen data-count-to index {$i} harus punya teks sama dengan nilai");
        }
    }

    /**
     * Test: Katalog memuat id="hasil-katalog"
     */
    public function test_katalog_memuat_hasil_katalog(): void
    {
        $p1 = Pelatihan::factory()->create(['nama' => 'Pelatihan Khusus']);
        $p2 = Pelatihan::factory()->create(['nama' => 'Pelatihan Umum']);

        $response = $this->get(route('pelatihan.katalog'));
        $response->assertStatus(200);
        $response->assertSee('id="hasil-katalog"', false);
    }

    /**
     * Test: Form login memuat data-loading-text="Masuk..."
     */
    public function test_login_form_has_loading_text(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('data-loading-text="Masuk..."', false);
    }

    /**
     * Test: Form register memuat data-loading-text="Mendaftar..."
     */
    public function test_register_form_has_loading_text(): void
    {
        $response = $this->get(route('register'));
        $response->assertStatus(200);
        $response->assertSee('data-loading-text="Mendaftar..."', false);
    }

    /**
     * Test: app.js memiliki IntersectionObserver logic
     */
    public function test_app_js_has_intersection_observer(): void
    {
        $appJsPath = resource_path('js/app.js');
        $this->assertFileExists($appJsPath);

        $content = file_get_contents($appJsPath);
        $this->assertStringContainsString('IntersectionObserver', $content, 'app.js harus memiliki IntersectionObserver');
        $this->assertStringContainsString('data-reveal', $content, 'app.js harus handling data-reveal');
        $this->assertStringContainsString('reveal-ready', $content, 'app.js harus set kelas reveal-ready');
    }

    /**
     * Test: app.css memiliki .reveal-ready [data-reveal] styles
     */
    public function test_app_css_has_reveal_styles(): void
    {
        $appCssPath = resource_path('css/app.css');
        $this->assertFileExists($appCssPath);

        $content = file_get_contents($appCssPath);
        $this->assertStringContainsString('.reveal-ready [data-reveal]', $content, 'app.css harus memiliki .reveal-ready [data-reveal] styles');
        $this->assertStringContainsString('opacity: 0', $content, 'reveal style harus punya opacity 0');
        $this->assertStringContainsString('translateY', $content, 'reveal style harus punya transform translateY');
    }

    /**
     * Test: app.js memiliki data-count-to dan easeOut animation
     */
    public function test_app_js_has_count_to_animation(): void
    {
        $appJsPath = resource_path('js/app.js');
        $this->assertFileExists($appJsPath);

        $content = file_get_contents($appJsPath);
        $this->assertStringContainsString('data-count-to', $content, 'app.js harus handling data-count-to');
        $this->assertStringContainsString('requestAnimationFrame', $content, 'app.js harus gunakan requestAnimationFrame');
    }

    /**
     * Test: app.js memiliki form loading state handling
     */
    public function test_app_js_has_form_loading_handler(): void
    {
        $appJsPath = resource_path('js/app.js');
        $this->assertFileExists($appJsPath);

        $content = file_get_contents($appJsPath);
        $this->assertStringContainsString('submit', $content, 'app.js harus handle form submit');
        $this->assertStringContainsString('disabled', $content, 'app.js harus disable tombol saat loading');
        $this->assertStringContainsString('pageshow', $content, 'app.js harus handle pageshow untuk bfcache');
    }
}
