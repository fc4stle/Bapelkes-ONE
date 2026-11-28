<?php

namespace Tests\Feature;

use Tests\TestCase;

class TemaDropdownTriggerTest extends TestCase
{
    /**
     * Test: navigation.blade.php tombol trigger dropdown "Lainnya" tidak memuat
     * text-[var(--color-text)] dan harus memuat text-white
     */
    public function test_dropdown_trigger_button_no_color_text_class(): void
    {
        $navFile = file_get_contents(resource_path('views/layouts/navigation.blade.php'));

        // Cari button trigger dropdown "Lainnya" dengan pattern baru: inline-flex items-center h-10
        $pattern = '/<button class="inline-flex items-center h-10 px-3 whitespace-nowrap leading-none text-sm rounded-full border[^>]*>[^<]*Lainnya/s';

        preg_match($pattern, $navFile, $matches);

        if (! empty($matches[0])) {
            $triggerButton = $matches[0];

            // Assert TIDAK memuat text-[var(--color-text)]
            $this->assertStringNotContainsString(
                'text-[var(--color-text)]',
                $triggerButton,
                'Dropdown trigger button should not have text-[var(--color-text)]'
            );

            // Assert memuat text-white
            $this->assertStringContainsString(
                'text-white',
                $triggerButton,
                'Dropdown trigger button should have text-white'
            );
        } else {
            $this->fail('Could not find dropdown trigger button "Lainnya" in navigation.blade.php');
        }
    }

    /**
     * Test: Tombol trigger menggunakan Tailwind classes (tidak inline style)
     * Pattern diperbarui untuk mencari button "Lainnya" dengan class baru
     */
    public function test_dropdown_trigger_uses_tailwind_classes(): void
    {
        $navFile = file_get_contents(resource_path('views/layouts/navigation.blade.php'));

        // Cari button trigger "Lainnya" dengan pattern baru: h-10 px-3 whitespace-nowrap rounded-full border
        $pattern = '/<button class="inline-flex items-center h-10 px-3 whitespace-nowrap leading-none text-sm rounded-full border[^>]*>.*?Lainnya/s';

        preg_match($pattern, $navFile, $matches);

        if (! empty($matches[0])) {
            $triggerButton = $matches[0];

            // Assert menggunakan Tailwind classes
            $this->assertStringContainsString(
                'rounded-full',
                $triggerButton,
                'Should use rounded-full Tailwind class'
            );

            $this->assertStringContainsString(
                'border',
                $triggerButton,
                'Should use border Tailwind class'
            );

            // Conditional: jika active, ada bg-white/20 border-white/35; jika inactive, ada border-transparent hover:bg-white/10
            $hasActiveStyle = strpos($triggerButton, 'bg-white/20') !== false && strpos($triggerButton, 'border-white/35') !== false;
            $hasInactiveStyle = strpos($triggerButton, 'border-transparent') !== false && strpos($triggerButton, 'hover:bg-white/10') !== false;

            $this->assertTrue(
                $hasActiveStyle || $hasInactiveStyle,
                'Should have either active (bg-white/20 border-white/35) or inactive (border-transparent hover:bg-white/10) style'
            );

            // Assert TIDAK memuat style inline dengan rgba
            $this->assertStringNotContainsString(
                'style="border-color: rgba',
                $triggerButton,
                'Should not have inline style with border-color'
            );

            $this->assertStringNotContainsString(
                'style="background-color: rgba',
                $triggerButton,
                'Should not have inline style with background-color'
            );
        } else {
            $this->fail('Could not find dropdown trigger button "Lainnya" in navigation.blade.php');
        }
    }

    /**
     * Test: Ikon lonceng notifikasi memuat text-white
     */
    public function test_notification_bell_icon_has_text_white(): void
    {
        $navFile = file_get_contents(resource_path('views/layouts/navigation.blade.php'));

        // Cari SVG bell dengan class text-white
        $pattern = '/<svg class="w-6 h-6 text-white"[^>]*>[^<]*<path[^>]*d="M15 17h5l-1\.405/s';

        $this->assertMatchesRegularExpression(
            $pattern,
            $navFile,
            'Notification bell SVG should have text-white class'
        );
    }

    /**
     * Test: Dropdown arrow icon memuat text-white
     */
    public function test_dropdown_arrow_icon_has_text_white(): void
    {
        $navFile = file_get_contents(resource_path('views/layouts/navigation.blade.php'));

        // Cari SVG dropdown arrow dengan class text-white di dalam trigger button
        $pattern = '/<svg class="fill-current h-4 w-4 text-white"[^>]*>[^<]*<path fill-rule="evenodd"/s';

        $this->assertMatchesRegularExpression(
            $pattern,
            $navFile,
            'Dropdown arrow icon should have text-white class'
        );
    }

    /**
     * Test: Tombol "Daftar" (guest) menggunakan Tailwind classes
     */
    public function test_register_button_guest_uses_tailwind_classes(): void
    {
        $navFile = file_get_contents(resource_path('views/layouts/navigation.blade.php'));

        // Cari button Daftar dengan route register (double quotes di href)
        $pattern = '/<a href="[^"]*route\(.*?register.*?\)[^"]*" class="[^"]*"[^>]*>Daftar<\/a>/s';

        preg_match($pattern, $navFile, $matches);

        if (! empty($matches[0])) {
            $registerButton = $matches[0];

            // Assert menggunakan Tailwind classes
            $this->assertStringContainsString(
                'border-white/35',
                $registerButton,
                'Register button should use border-white/35 Tailwind class'
            );

            $this->assertStringContainsString(
                'bg-white/20',
                $registerButton,
                'Register button should use bg-white/20 Tailwind class'
            );

            // Assert TIDAK memuat style inline
            $this->assertStringNotContainsString(
                'style="background-color: rgba',
                $registerButton,
                'Register button should not have inline background-color style'
            );

            $this->assertStringNotContainsString(
                'style="border: 1px solid rgba',
                $registerButton,
                'Register button should not have inline border style'
            );
        } else {
            $this->fail('Could not find register button in navigation.blade.php');
        }
    }
}
