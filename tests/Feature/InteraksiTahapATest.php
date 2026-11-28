<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InteraksiTahapATest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Token durasi (--dur-fast, --dur-base, --ease) ada di app.css
     */
    public function test_duration_tokens_in_css(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('--dur-fast: 150ms', $css);
        $this->assertStringContainsString('--dur-base: 200ms', $css);
        $this->assertStringContainsString('--ease: cubic-bezier(.2,.7,.2,1)', $css);
    }

    /**
     * Test: Focus visible style ada (outline + offset)
     */
    public function test_focus_visible_styles_in_css(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString(':focus-visible {', $css);
        $this->assertStringContainsString('outline: 2px solid var(--color-accent)', $css);
        $this->assertStringContainsString('outline-offset: 2px', $css);
    }

    /**
     * Test: Focus visible di band dengan warna emas lembut
     */
    public function test_focus_visible_band_color(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('[data-brand="band"] :focus-visible', $css);
        $this->assertStringContainsString('outline: 2px solid var(--color-gold-soft)', $css);
    }

    /**
     * Test: prefers-reduced-motion global ada di CSS
     */
    public function test_prefers_reduced_motion_in_css(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertStringContainsString('animation-duration: .01ms !important', $css);
        $this->assertStringContainsString('transition-duration: .01ms !important', $css);
    }

    /**
     * Test: Tombol primary punya transition dan hover
     */
    public function test_primary_button_transitions(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        // Tombol ada transition dengan duration var
        $this->assertStringContainsString('transition: color var(--dur-fast)', $css);

        // Hover state ada transform
        $this->assertStringContainsString('.primary-button:hover', $css);
    }

    /**
     * Test: Tombol danger ada (bukan hanya primary/secondary)
     */
    public function test_danger_button_in_css(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('.danger-button', $css);
        $this->assertStringContainsString('.danger-button:hover', $css);
        $this->assertStringContainsString('.danger-button:active', $css);
    }

    /**
     * Test: Tombol active scale 0.98
     */
    public function test_button_active_scale(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        // Active state harus ada scale (bukan hanya hover)
        $this->assertStringContainsString(':active', $css);
        $this->assertStringContainsString('scale(0.98)', $css);
    }

    /**
     * Test: Tombol disabled opacity 0.5
     */
    public function test_button_disabled_opacity(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('button:disabled', $css);
        $this->assertStringContainsString('opacity: 0.5', $css);
    }

    /**
     * Test: Card component punya prop interactive
     */
    public function test_card_component_has_interactive_prop(): void
    {
        $card = file_get_contents(resource_path('views/components/card.blade.php'));

        $this->assertStringContainsString("'interactive' => false", $card);
        $this->assertStringContainsString('$interactive_classes', $card);
    }

    /**
     * Test: Card interactive menambah hover:-translate-y-0.5
     */
    public function test_card_interactive_hover_translate(): void
    {
        $card = file_get_contents(resource_path('views/components/card.blade.php'));

        $this->assertStringContainsString('hover:-translate-y-0.5', $card);
        $this->assertStringContainsString('hover:border-[var(--color-primary)]/40', $card);
    }

    /**
     * Test: Tabel institusi CSS ada di app.css
     */
    public function test_tabel_institusi_css(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('.tabel-institusi tbody tr {', $css);
        $this->assertStringContainsString('transition: background-color var(--dur-base) var(--ease)', $css);
        $this->assertStringContainsString('.tabel-institusi tbody tr:hover {', $css);
        $this->assertStringContainsString('background-color: color-mix(in srgb, var(--color-primary) 6%, white)', $css);
    }

    /**
     * Test: Minimal 13 tabel punya class tabel-institusi (5 di tiyas + 8 di file lain)
     */
    public function test_minimum_13_tables_have_tabel_institusi_class(): void
    {
        $files = [
            'admin/aktivitas/index.blade.php',
            'admin/users/index.blade.php',
            'panitia/asrama/show.blade.php',
            'panitia/statistik.blade.php',
            'pelatihans/dashboard.blade.php',
            'pelatihans/index.blade.php',
            'pelatihans/pendaftar.blade.php',
            'pendaftarans/index.blade.php',
            'tiyas/hasil.blade.php',
        ];

        $count = 0;
        foreach ($files as $file) {
            $content = file_get_contents(resource_path("views/$file"));
            $count += substr_count($content, 'tabel-institusi');
        }

        $this->assertGreaterThanOrEqual(13, $count, "Expected at least 13 tabel-institusi instances, found $count");
    }

    /**
     * Test: Tidak ada onmouseover atau this.style di view files
     */
    public function test_no_onmouseover_or_this_style_in_views(): void
    {
        $viewDir = resource_path('views');
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($viewDir),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        $forbidden = [];
        foreach ($files as $file) {
            if ($file->getExtension() !== 'blade' && $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file);

            if (strpos($content, 'onmouseover') !== false) {
                $forbidden[] = $file->getRealPath().' (onmouseover)';
            }
            if (preg_match('/this\.style\s*[=\.]/', $content)) {
                $forbidden[] = $file->getRealPath().' (this.style)';
            }
        }

        $this->assertEmpty($forbidden, 'Found forbidden patterns: '.implode(', ', $forbidden));
    }

    /**
     * Test: Header navigation sticky kaca (blur)
     */
    public function test_navigation_sticky_blur(): void
    {
        $nav = file_get_contents(resource_path('views/layouts/navigation.blade.php'));

        $this->assertStringContainsString('sticky top-0 z-40', $nav);
        $this->assertStringContainsString('backdrop-filter: blur(12px)', $nav);
        $this->assertStringContainsString(':style="scrolled ?', $nav);
    }

    /**
     * Test: Tombol naik ke atas ada (back to top)
     */
    public function test_back_to_top_button_exists(): void
    {
        $app = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString('x-data="{ scrollTop: false }"', $app);
        $this->assertStringContainsString('x-show="scrollTop"', $app);
        $this->assertStringContainsString('x-transition', $app);
        $this->assertStringContainsString('aria-label="Kembali ke atas"', $app);
    }

    /**
     * Test: Tombol naik ke atas trigger di scroll > 400px
     */
    public function test_back_to_top_scroll_trigger(): void
    {
        $app = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString('window.scrollY > 400', $app);
    }

    /**
     * Test: Tombol naik ke atas smooth scroll ke top
     */
    public function test_back_to_top_smooth_scroll(): void
    {
        $app = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString("window.scrollTo({ top: 0, behavior: 'smooth' })", $app);
    }

    /**
     * Test: Dropdown "Lainnya" ada di navigation dengan x-dropdown
     */
    public function test_dropdown_lainnya_exists(): void
    {
        $nav = file_get_contents(resource_path('views/layouts/navigation.blade.php'));

        $this->assertStringContainsString('Lainnya', $nav);
        $this->assertStringContainsString('<x-dropdown', $nav);
        $this->assertStringContainsString('x-slot name="trigger"', $nav);
        $this->assertStringContainsString('x-slot name="content"', $nav);
    }

    /**
     * Test: Navigation responsive breakpoint xl (bukan sm)
     */
    public function test_navigation_responsive_xl_breakpoint(): void
    {
        $nav = file_get_contents(resource_path('views/layouts/navigation.blade.php'));

        // Harus ada xl: prefix, bukan sm:
        $this->assertStringContainsString('xl:flex', $nav);
        $this->assertStringContainsString('xl:hidden', $nav);
        $this->assertStringContainsString('xl:flex-nowrap', $nav);
    }

    /**
     * Test: GET / memuat halaman dengan sticky nav dan scroll-to-top button
     */
    public function test_homepage_loads_with_sticky_nav_and_scroll_button(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $html = $response->getContent();
        // Cek x-transition di scroll-to-top button
        $response->assertSee('x-transition');
        // Hanya cek bahwa aria-label ada di response (tidak peduli quote style)
        $this->assertStringContainsString('aria-label=', $html);
        $this->assertStringContainsString('Kembali ke atas', $html);
    }

    /**
     * Test: Link hover di main punya text-decoration dan offset
     */
    public function test_main_link_hover_styling(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('main a:hover', $css);
        $this->assertStringContainsString('text-decoration: underline', $css);
        $this->assertStringContainsString('text-decoration-offset: 4px', $css);
    }

    /**
     * Test: Brand band focus-visible override exists
     */
    public function test_band_section_focus_visible(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('.band-section :focus-visible', $css);
    }

    /**
     * Test: Primary dan secondary button disabled sama
     */
    public function test_all_buttons_disabled_styling(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        // Harus mention primary, secondary, danger di disabled
        $this->assertStringContainsString('button:disabled', $css);
        $this->assertStringContainsString('.primary-button:disabled', $css);
        $this->assertStringContainsString('.secondary-button:disabled', $css);
        $this->assertStringContainsString('.danger-button:disabled', $css);
    }

    /**
     * Test: Brand band styling punya padding-top dan margin-top offset
     */
    public function test_brand_band_scroll_offset(): void
    {
        $app = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertStringContainsString('padding-top: 72px', $app);
        $this->assertStringContainsString('margin-top: -72px', $app);
    }
}
