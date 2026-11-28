<?php

namespace Tests\Feature;

use Tests\TestCase;

class TemaNavigasiTest extends TestCase
{
    /**
     * Test: Komponen nav-link.blade.php tidak memiliki inline style
     */
    public function test_nav_link_component_has_no_inline_styles(): void
    {
        $filePath = resource_path('views/components/nav-link.blade.php');
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);

        // Assert: nav-link tidak punya style="..."
        $this->assertStringNotContainsString('style=', $content,
            'nav-link.blade.php harus tidak punya inline style');
    }

    /**
     * Test: Komponen nav-link.blade.php memiliki kelas active/inactive yang benar
     */
    public function test_nav_link_component_has_correct_classes(): void
    {
        $filePath = resource_path('views/components/nav-link.blade.php');
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);

        // Assert: active state memiliki bg-white/20
        $this->assertStringContainsString('bg-white/20', $content);

        // Assert: inactive state memiliki hover:bg-white/10
        $this->assertStringContainsString('hover:bg-white/10', $content);

        // Assert: border border-transparent atau border-white/35
        $this->assertStringContainsString('border', $content);
    }

    /**
     * Test: navigation.blade.php tidak punya kelas duplikat atau style inline di nav-link
     */
    public function test_navigation_does_not_have_duplicate_classes(): void
    {
        $filePath = resource_path('views/layouts/navigation.blade.php');
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);

        // Assert: x-nav-link tidak punya text-white opacity-90 text-white duplikat
        $this->assertStringNotContainsString('text-white opacity-90 rounded-full px-4 py-2', $content);

        // Assert: x-nav-link tidak punya style="..."
        $matches = preg_match_all('/<x-nav-link[^>]*style=/', $content);
        $this->assertSame(0, $matches,
            'x-nav-link di navigation.blade.php harus tidak punya style attribute');
    }

    /**
     * Test: Dropdown link pakai text-[var(--color-text)]
     */
    public function test_dropdown_link_has_correct_text_color_class(): void
    {
        $filePath = resource_path('views/components/dropdown-link.blade.php');
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);

        // Assert: dropdown-link pakai text-[var(--color-text)]
        $this->assertStringContainsString('text-[var(--color-text)]', $content);
    }
}
