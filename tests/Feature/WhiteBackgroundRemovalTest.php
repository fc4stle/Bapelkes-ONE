<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhiteBackgroundRemovalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that app layout includes theme-color meta tag
     */
    public function test_app_layout_has_theme_color_meta(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertSee('<meta name="theme-color" content="#FFF8E7">', escape: false);
    }

    /**
     * Test that guest layout includes theme-color meta tag
     */
    public function test_guest_layout_has_theme_color_meta(): void
    {
        $response = $this->get(route('login'));

        $response->assertSee('<meta name="theme-color" content="#FFF8E7">', escape: false);
    }

    /**
     * Test that CSS file contains html/body background-color rule
     */
    public function test_css_has_html_body_background_rule(): void
    {
        $cssPath = resource_path('css/app.css');
        $cssContent = file_get_contents($cssPath);

        // Check for html, body with background-color: var(--color-bg)
        $this->assertStringContainsString('html, body', $cssContent);
        $this->assertStringContainsString('background-color: var(--color-bg)', $cssContent);
        $this->assertStringContainsString('min-height: 100dvh', $cssContent);
    }

    /**
     * Test that asrama index page view file does not use bg-white
     */
    public function test_asrama_index_view_no_bg_white(): void
    {
        $viewPath = resource_path('views/panitia/asrama/index.blade.php');
        $content = file_get_contents($viewPath);

        $this->assertStringNotContainsString('bg-white', $content);
        $this->assertStringContainsString('var(--color-surface)', $content);
    }

    /**
     * Test that asrama show page view does not use bg-white in main div and modals
     */
    public function test_asrama_show_view_no_bg_white(): void
    {
        $viewPath = resource_path('views/panitia/asrama/show.blade.php');
        $content = file_get_contents($viewPath);

        // View should not have bg-white
        $this->assertStringNotContainsString('bg-white', $content);

        // Should reference var(--color-surface)
        $this->assertStringContainsString('var(--color-surface)', $content);
    }

    /**
     * Test that admin aktivitas index page view does not use bg-white
     */
    public function test_admin_aktivitas_index_view_no_bg_white(): void
    {
        $viewPath = resource_path('views/admin/aktivitas/index.blade.php');
        $content = file_get_contents($viewPath);

        $this->assertStringNotContainsString('bg-white', $content);
        $this->assertStringContainsString('var(--color-surface)', $content);
    }

    /**
     * Test that profile edit page view does not use bg-white in any card
     */
    public function test_profile_edit_view_no_bg_white(): void
    {
        $viewPath = resource_path('views/profile/edit.blade.php');
        $content = file_get_contents($viewPath);

        // Should not have bg-white anywhere
        $this->assertStringNotContainsString('bg-white', $content);

        // Should have rounded-lg (not sm:rounded-lg) and var(--color-surface)
        $this->assertStringContainsString('rounded-lg', $content);
        $this->assertStringContainsString('var(--color-surface)', $content);
    }

    /**
     * Test that sertifikat belum page does not use bg-white
     */
    public function test_sertifikat_belum_view_no_bg_white(): void
    {
        $viewPath = resource_path('views/pendaftarans/sertifikat_belum.blade.php');
        $content = file_get_contents($viewPath);

        $this->assertStringNotContainsString('bg-white', $content);
        $this->assertStringContainsString('var(--color-surface)', $content);
    }

    /**
     * Test that flash component does not use bg-white
     */
    public function test_flash_component_no_bg_white(): void
    {
        $componentPath = resource_path('views/components/flash.blade.php');
        $componentContent = file_get_contents($componentPath);

        // Component should not have bg-white
        $this->assertStringNotContainsString('bg-white', $componentContent);
        $this->assertStringContainsString('var(--color-surface)', $componentContent);
    }

    /**
     * Test that TIYAS form fixed button bar uses var(--color-bg) not var(--color-surface)
     */
    public function test_tiyas_form_bar_uses_color_bg(): void
    {
        $formPath = resource_path('views/tiyas/form.blade.php');
        $formContent = file_get_contents($formPath);

        // Should have var(--color-bg) for the fixed bar
        $this->assertStringContainsString('var(--color-bg)', $formContent);

        // Fixed bar should have the shadow upward
        $this->assertStringContainsString('box-shadow: 0 -2px 8px', $formContent);

        // Check that the fixed bottom section uses correct styling
        preg_match('/fixed bottom-0[^>]+background-color:\s*var\(--color-([^)]+)\)/', $formContent, $matches);

        if (count($matches) > 0) {
            $this->assertStringContainsString('--color-bg', $matches[0]);
        }
    }

    /**
     * Test that profile/edit.blade.php uses rounded-lg (not sm:rounded-lg)
     */
    public function test_profile_edit_rounded_lg_all_sizes(): void
    {
        $profileEditPath = resource_path('views/profile/edit.blade.php');
        $content = file_get_contents($profileEditPath);

        // Should have rounded-lg
        $this->assertStringContainsString('rounded-lg', $content);

        // Should NOT have sm:rounded-lg (means rounded only on small+)
        $this->assertStringNotContainsString('sm:rounded-lg', $content);
    }

    /**
     * Test that no views use bare bg-white class
     */
    public function test_no_critical_views_use_bg_white(): void
    {
        $criticalViews = [
            'panitia/asrama/index.blade.php',
            'panitia/asrama/show.blade.php',
            'admin/aktivitas/index.blade.php',
            'profile/edit.blade.php',
            'pendaftarans/sertifikat_belum.blade.php',
            'tiyas/form.blade.php',
            'components/flash.blade.php',
        ];

        foreach ($criticalViews as $viewFile) {
            $path = resource_path('views/'.$viewFile);
            $this->assertFileExists($path, "View file {$viewFile} should exist");

            $content = file_get_contents($path);
            $this->assertStringNotContainsString('bg-white', $content,
                "View {$viewFile} should not contain 'bg-white' class");
        }
    }
}
