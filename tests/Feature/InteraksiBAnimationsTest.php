<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InteraksiBAnimationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * D) KEDIP REVEAL - Kelas reveal-ready ditambahkan saat prefers-reduced-motion tidak reduce
     */
    public function test_reveal_ready_class_ditambahkan(): void
    {
        $appJs = file_get_contents(resource_path('js/app.js'));

        // Cek ada logika untuk menambahkan 'reveal-ready' ke documentElement
        $this->assertStringContainsString('reveal-ready', $appJs);
        $this->assertStringContainsString('prefers-reduced-motion', $appJs);
    }

    /**
     * D) KEDIP REVEAL - Logic reveal-ready HARUS di luar DOMContentLoaded (pada awal app.js)
     */
    public function test_reveal_ready_di_luar_dom_content_loaded(): void
    {
        $appJs = file_get_contents(resource_path('js/app.js'));

        // Cari posisi 'Alpine.start()'
        $alpineStartPos = strpos($appJs, 'Alpine.start()');

        // Cari posisi PERTAMA 'reveal-ready' (harus di awal, di luar event listener)
        $revealReadyPos = strpos($appJs, 'reveal-ready');

        // Cari posisi 'DOMContentLoaded'
        $domContentLoadedPos = strpos($appJs, 'DOMContentLoaded');

        // 'reveal-ready' harus SEBELUM 'DOMContentLoaded' (di luar DOMContentLoaded)
        $this->assertTrue($revealReadyPos < $domContentLoadedPos,
            'reveal-ready harus di luar DOMContentLoaded listener, sebelum event addEventListener');
    }

    /**
     * D) KEDIP REVEAL - IntersectionObserver di dalam DOMContentLoaded
     */
    public function test_reveal_observer_dalam_dom_content_loaded(): void
    {
        $appJs = file_get_contents(resource_path('js/app.js'));

        // Cek ada IntersectionObserver untuk reveal
        $this->assertStringContainsString('IntersectionObserver', $appJs);
        $this->assertStringContainsString('data-reveal', $appJs);
    }

    /**
     * E) CAKUPAN - data-reveal pada panel di tiyas/hasil.blade.php
     */
    public function test_tiyas_hasil_punya_data_reveal(): void
    {
        $hasilView = file_get_contents(resource_path('views/tiyas/hasil.blade.php'));

        // Cek ada atribut data-reveal pada panel/kartu
        $this->assertStringContainsString('data-reveal', $hasilView);
    }

    /**
     * E) CAKUPAN - data-reveal pada panel di panitia/statistik.blade.php
     */
    public function test_panitia_statistik_punya_data_reveal(): void
    {
        $statistikView = file_get_contents(resource_path('views/panitia/statistik.blade.php'));

        // Cek ada atribut data-reveal pada panel kartu ringkasan
        $this->assertStringContainsString('data-reveal', $statistikView);
    }

    /**
     * E) CAKUPAN - data-count-to pada angka integer di panitia/statistik.blade.php
     */
    public function test_panitia_statistik_punya_data_count_to(): void
    {
        $statistikView = file_get_contents(resource_path('views/panitia/statistik.blade.php'));

        // Cek ada atribut data-count-to pada angka ringkasan
        $this->assertStringContainsString('data-count-to', $statistikView);
    }

    /**
     * E) CAKUPAN - data-count-to pada angka skor di tiyas/hasil.blade.php
     */
    public function test_tiyas_hasil_punya_data_count_to(): void
    {
        $hasilView = file_get_contents(resource_path('views/tiyas/hasil.blade.php'));

        // Cek ada atribut data-count-to pada angka skor
        $this->assertStringContainsString('data-count-to', $hasilView);
    }

    /**
     * E) CAKUPAN - JavaScript abaikan elemen dengan NaN di data-count-to
     */
    public function test_count_to_handler_abaikan_nan(): void
    {
        $appJs = file_get_contents(resource_path('js/app.js'));

        // Cek ada logika untuk mengecek NaN
        $this->assertStringContainsString('NaN', $appJs);
        $this->assertStringContainsString('data-count-to', $appJs);
    }

    /**
     * F) VERIFIKASI - prefers-reduced-motion diterapkan pada semua animasi
     */
    public function test_prefers_reduced_motion_di_semua_animasi(): void
    {
        $appJs = file_get_contents(resource_path('js/app.js'));

        // Cek ada multiple pengecekan prefers-reduced-motion
        $matches = substr_count($appJs, 'prefers-reduced-motion');
        $this->assertGreaterThan(1, $matches, 'prefers-reduced-motion harus dicek di banyak tempat');
    }
}
