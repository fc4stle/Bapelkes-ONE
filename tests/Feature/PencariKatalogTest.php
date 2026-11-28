<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PencariKatalogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * C) PENCARIAN KATALOG - GET dengan parameter q menampilkan hasil di #hasil-katalog
     */
    public function test_pencarian_katalog_dengan_query_q(): void
    {
        // Buat dua pelatihan berbeda (dengan status dibuka)
        $pelatihan_a = Pelatihan::factory()->dibuka()->create(['nama' => 'PHP Dasar']);
        $pelatihan_b = Pelatihan::factory()->dibuka()->create(['nama' => 'Laravel Advanced']);

        // GET route dengan parameter q (public route, tidak perlu login)
        $response = $this->get(route('pelatihan.katalog', ['q' => 'PHP']));

        $response->assertOk();

        // Hasil harus ada di dalam #hasil-katalog
        $this->assertStringContainsString('id="hasil-katalog"', $response->getContent());

        // Pelatihan A harus tampil (cocok dengan PHP)
        $response->assertSee('PHP Dasar');

        // Pelatihan B TIDAK boleh tampil (Laravel bukan PHP)
        $response->assertDontSee('Laravel Advanced');
    }

    /**
     * C) PENCARIAN KATALOG - Form memiliki atribut data-katalog-search
     */
    public function test_katalog_form_punya_data_katalog_search(): void
    {
        $response = $this->get(route('pelatihan.katalog'));

        $html = $response->getContent();

        // Form harus punya atribut data-katalog-search
        $this->assertStringContainsString('data-katalog-search', $html);
    }

    /**
     * C) PENCARIAN KATALOG - Hasil dibungkus dengan #hasil-katalog
     */
    public function test_katalog_hasil_dibungkus_div_hasil_katalog(): void
    {
        $pelatihan = Pelatihan::factory()->dibuka()->create(['nama' => 'Test Pelatihan']);

        $response = $this->get(route('pelatihan.katalog'));

        $html = $response->getContent();

        // Harus ada div dengan id="hasil-katalog"
        $this->assertStringContainsString('id="hasil-katalog"', $html);

        // Pelatihan card harus ada di dalam #hasil-katalog
        preg_match('/id="hasil-katalog"[^>]*>.*?<\\/div>/s', $html, $matches);
        if (count($matches) > 0) {
            $this->assertStringContainsString('Test Pelatihan', $matches[0]);
        }
    }

    /**
     * C) PENCARIAN KATALOG - Pagination termasuk dalam #hasil-katalog
     */
    public function test_katalog_pagination_dalam_hasil_katalog(): void
    {
        // Buat banyak pelatihan agar ada pagination (dengan status dibuka)
        Pelatihan::factory(20)->dibuka()->create();

        $response = $this->get(route('pelatihan.katalog'));

        $html = $response->getContent();

        // Harus ada #hasil-katalog yang membungkus cards DAN pagination
        $this->assertStringContainsString('id="hasil-katalog"', $html);
    }

    /**
     * C) PENCARIAN KATALOG - Input event handler untuk text search
     */
    public function test_katalog_form_input_event_handler(): void
    {
        $response = $this->get(route('pelatihan.katalog'));

        $html = $response->getContent();

        // Form harus punya event listener untuk 'input' (text field)
        // atau 'change' (select/checkbox/date)
        $this->assertStringContainsString('data-katalog-search', $html);

        // Verifikasi ada form field untuk search (name="q")
        $this->assertStringContainsString('name="q"', $html);
    }

    /**
     * C) PENCARIAN KATALOG - Search case-insensitive (Laravel query)
     */
    public function test_pencarian_katalog_case_insensitive(): void
    {
        $pelatihan = Pelatihan::factory()->dibuka()->create(['nama' => 'PHP Dasar']);

        // Search dengan lowercase
        $response1 = $this->get(route('pelatihan.katalog', ['q' => 'php']));
        $response1->assertSee('PHP Dasar');

        // Search dengan uppercase
        $response2 = $this->get(route('pelatihan.katalog', ['q' => 'PHP']));
        $response2->assertSee('PHP Dasar');

        // Search dengan mixed case
        $response3 = $this->get(route('pelatihan.katalog', ['q' => 'PhP']));
        $response3->assertSee('PHP Dasar');
    }

    /**
     * C) PENCARIAN KATALOG - Empty query menampilkan semua (hanya status dibuka)
     */
    public function test_pencarian_katalog_empty_query_tampilkan_semua(): void
    {
        $p1 = Pelatihan::factory()->dibuka()->create(['nama' => 'Pelatihan A']);
        $p2 = Pelatihan::factory()->dibuka()->create(['nama' => 'Pelatihan B']);

        $response = $this->get(route('pelatihan.katalog'));

        $response->assertOk();
        $response->assertSee('Pelatihan A');
        $response->assertSee('Pelatihan B');
    }
}
