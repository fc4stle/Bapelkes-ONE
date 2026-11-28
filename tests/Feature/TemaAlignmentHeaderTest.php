<?php

namespace Tests\Feature;

use Tests\TestCase;

class TemaAlignmentHeaderTest extends TestCase
{
    /**
     * Test: app.blade.php dan navigation.blade.php memiliki container class yang sama
     */
    public function test_header_and_navigation_container_match()
    {
        $appPath = base_path('resources/views/layouts/app.blade.php');
        $appContent = file_get_contents($appPath);

        $navPath = base_path('resources/views/layouts/navigation.blade.php');
        $navContent = file_get_contents($navPath);

        // Ambil container class dari navigation
        $containerClass = 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8';

        // Assert navigation memiliki container class
        $this->assertStringContainsString($containerClass, $navContent,
            'navigation.blade.php tidak memuat container class: '.$containerClass);

        // Assert app.blade.php juga memiliki container class (di dalam header div)
        $this->assertStringContainsString($containerClass, $appContent,
            'app.blade.php tidak memuat container class: '.$containerClass);
    }

    /**
     * Test: <header> tag di app.blade.php TIDAK memuat px-4 sm:px-6 lg:px-8
     */
    public function test_header_tag_does_not_have_horizontal_padding_classes()
    {
        $appPath = base_path('resources/views/layouts/app.blade.php');
        $content = file_get_contents($appPath);

        // Ambil header tag (regex sederhana)
        preg_match('/<header[^>]*class="([^"]*)"/', $content, $matches);

        if (! empty($matches[1])) {
            $headerClasses = $matches[1];

            // Assert px-4, sm:px-6, lg:px-8 TIDAK ada di header class
            $this->assertStringNotContainsString('px-4', $headerClasses,
                '<header> tag memiliki px-4 (padding horizontal harus di dalam div)');
            $this->assertStringNotContainsString('sm:px-6', $headerClasses,
                '<header> tag memiliki sm:px-6 (padding horizontal harus di dalam div)');
            $this->assertStringNotContainsString('lg:px-8', $headerClasses,
                '<header> tag memiliki lg:px-8 (padding horizontal harus di dalam div)');
        }
    }

    /**
     * Test: app.blade.php header div dalam header MEMUAT max-w-7xl mx-auto px-4 sm:px-6 lg:px-8
     */
    public function test_header_inner_div_has_full_container_classes()
    {
        $appPath = base_path('resources/views/layouts/app.blade.php');
        $content = file_get_contents($appPath);

        // Pattern: <header...> <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        $pattern = '/<header[^>]*>\s*<div[^>]*class="[^"]*max-w-7xl[^"]*mx-auto[^"]*px-4[^"]*sm:px-6[^"]*lg:px-8/';

        $this->assertMatchesRegularExpression($pattern, $content,
            'app.blade.php header inner div tidak memiliki lengkap: max-w-7xl mx-auto px-4 sm:px-6 lg:px-8');
    }

    /**
     * Test: beranda.blade.php wrapper hero TIDAK memuat 'max-w-7xl mx-auto px-4'
     */
    public function test_beranda_hero_wrapper_no_container_padding()
    {
        $berandaPath = base_path('resources/views/beranda.blade.php');
        $content = file_get_contents($berandaPath);

        // Cek di keseluruhan content beranda: tidak boleh ada kombinasi max-w-7xl mx-auto px-4 dalam x-slot header
        // Ambil konten header slot
        if (preg_match('/<x-slot name="header">(.*?)<\/x-slot>/s', $content, $slotMatch)) {
            $slotContent = $slotMatch[1];

            // Wrapper pertama harus hanya text-left, bukan max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-left
            $this->assertStringNotContainsString('max-w-7xl mx-auto px-4 sm:px-6 lg:px-8', $slotContent,
                'beranda.blade.php header slot tidak boleh memiliki max-w-7xl mx-auto px-4 sm:px-6 lg:px-8');

            // Wrapper harus ada text-left
            $this->assertStringContainsString('class="text-left"', $slotContent,
                'beranda.blade.php hero wrapper harus memiliki class="text-left"');
        } else {
            $this->fail('Tidak bisa menemukan x-slot name="header" di beranda.blade.php');
        }
    }

    /**
     * Test: beranda.blade.php tidak memiliki div ekstra penutup
     */
    public function test_beranda_no_extra_closing_div()
    {
        $berandaPath = base_path('resources/views/beranda.blade.php');
        $content = file_get_contents($berandaPath);

        // Cek jumlah </div> di dalam x-slot
        preg_match('/<x-slot name="header">(.*?)<\/x-slot>/s', $content, $matches);

        if (! empty($matches[1])) {
            $slotContent = $matches[1];

            // Hitung opening div dan closing div
            $openingDivs = substr_count($slotContent, '<div');
            $closingDivs = substr_count($slotContent, '</div>');

            // Seharusnya ada 2 opening (wrapper dan h1+p wrapper) dan 2 closing
            $this->assertEquals($openingDivs, $closingDivs,
                'beranda.blade.php header slot: jumlah opening dan closing div tidak seimbang');
        }
    }
}
