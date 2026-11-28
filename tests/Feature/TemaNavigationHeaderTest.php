<?php

namespace Tests\Feature;

use Tests\TestCase;

class TemaNavigationHeaderTest extends TestCase
{
    /**
     * Test: navigation.blade.php memiliki tinggi nav h-[72px]
     */
    public function test_navigation_has_height_72px(): void
    {
        $filePath = base_path('resources/views/layouts/navigation.blade.php');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('h-[72px]', $content, 'Navigation harus memiliki kelas h-[72px]');
    }

    /**
     * Test: navigation.blade.php NOT ada height: 64px inline style
     */
    public function test_navigation_does_not_have_height_64px(): void
    {
        $filePath = base_path('resources/views/layouts/navigation.blade.php');
        $content = file_get_contents($filePath);

        $this->assertStringNotContainsString('height: 64px', $content, 'Navigation tidak boleh memiliki height: 64px inline style');
    }

    /**
     * Test: navigation.blade.php logo img memiliki kelas h-11 (desktop)
     */
    public function test_navigation_logo_has_h11_class(): void
    {
        $filePath = base_path('resources/views/layouts/navigation.blade.php');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('h-9 sm:h-11', $content, 'Logo img harus memiliki responsive height h-9 sm:h-11');
    }

    /**
     * Test: navigation.blade.php logo img NOT ada height: 34px inline style
     */
    public function test_navigation_logo_does_not_have_height_34px(): void
    {
        $filePath = base_path('resources/views/layouts/navigation.blade.php');
        $content = file_get_contents($filePath);

        $this->assertStringNotContainsString('height: 34px', $content, 'Logo img tidak boleh memiliki height: 34px inline style');
    }

    /**
     * Test: navigation.blade.php NOT ada text-[13px] breakpoint classes
     */
    public function test_navigation_does_not_have_text_13px(): void
    {
        $filePath = base_path('resources/views/layouts/navigation.blade.php');
        $content = file_get_contents($filePath);

        $this->assertStringNotContainsString('text-[13px]', $content, 'Navigation menu tidak boleh memiliki text-[13px] classes');
    }

    /**
     * Test: navigation.blade.php menu links memiliki text-sm class
     */
    public function test_navigation_menu_has_text_sm(): void
    {
        $filePath = base_path('resources/views/layouts/navigation.blade.php');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('text-sm', $content, 'Navigation menu links harus memiliki text-sm class');
    }

    /**
     * Test: navigation.blade.php ada pembatas vertikal (divider) gap-8 dan h-7
     */
    public function test_navigation_has_vertical_divider(): void
    {
        $filePath = base_path('resources/views/layouts/navigation.blade.php');
        $content = file_get_contents($filePath);

        // Check for divider dengan h-7 (28px) dan bg-white/25
        $this->assertStringContainsString('h-7', $content, 'Divider vertikal harus memiliki tinggi h-7');
        $this->assertStringContainsString('bg-white/25', $content, 'Divider vertikal harus memiliki warna bg-white/25');
    }

    /**
     * Test: navigation.blade.php memiliki gap-8 antara logo dan menu
     */
    public function test_navigation_has_gap_8(): void
    {
        $filePath = base_path('resources/views/layouts/navigation.blade.php');
        $content = file_get_contents($filePath);

        $this->assertStringContainsString('gap-8', $content, 'Logo dan menu harus memiliki gap-8');
    }
}
