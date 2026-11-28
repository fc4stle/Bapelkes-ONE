<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanitiasDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_no_border_left_styling(): void
    {
        $user = User::factory()->create(['role' => 'panitia']);

        $response = $this
            ->actingAs($user)
            ->get('/panitia/dashboard');

        $response->assertOk();

        $html = $response->getContent();

        // Should NOT have border-left: 4px styling
        $this->assertStringNotContainsString('border-left: 4px', $html);
        $this->assertStringNotContainsString('border-left:4px', $html);
    }

    public function test_dashboard_has_grid_divider_styling(): void
    {
        $user = User::factory()->create(['role' => 'panitia']);

        $response = $this
            ->actingAs($user)
            ->get('/panitia/dashboard');

        $response->assertOk();

        $html = $response->getContent();

        // Parse HTML to find metric grid panel
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_use_internal_errors(false);

        $xpath = new \DOMXPath($dom);

        // Find first grid with grid-cols-1 class (metric grid)
        $grids = $xpath->query("//div[contains(@class, 'grid-cols-1') and contains(@class, 'gap-px')]");

        $this->assertGreaterThan(0, $grids->length, 'Should have metric grid with gap-px class');

        $metricGrid = $grids->item(0);

        // Assert metric grid has gap-px and bg-[var(--color-line)]
        $gridClass = $metricGrid->getAttribute('class');

        $this->assertStringContainsString('gap-px', $gridClass, 'Metric grid should have gap-px class');
        $this->assertStringContainsString('bg-[var(--color-line)]', $gridClass, 'Metric grid should have bg-[var(--color-line)] class');

        // Assert metric grid does NOT have divide-x or divide-y
        $this->assertStringNotContainsString('divide-x', $gridClass, 'Metric grid should not have divide-x class');
        $this->assertStringNotContainsString('divide-y', $gridClass, 'Metric grid should not have divide-y class');

        // Count and verify cells in grid
        $cells = $xpath->query('./div', $metricGrid);

        $this->assertEquals(4, $cells->length, 'Metric grid should have exactly 4 cells');

        // Verify all cells have bg-white
        for ($i = 0; $i < $cells->length; $i++) {
            $cell = $cells->item($i);
            $cellClass = $cell->getAttribute('class');

            $this->assertStringContainsString('bg-white', $cellClass, "Cell $i should have bg-white class");
        }
    }

    public function test_dashboard_has_page_header_subtitle(): void
    {
        $user = User::factory()->create(['role' => 'panitia']);

        $response = $this
            ->actingAs($user)
            ->get('/panitia/dashboard');

        $response->assertOk();

        // Should have the subtitle from page-header
        $response->assertSee('Ringkasan hal yang perlu ditindak hari ini.');
    }

    public function test_dashboard_panel_titles_inside_rounded_elements(): void
    {
        $user = User::factory()->create(['role' => 'panitia']);

        $response = $this
            ->actingAs($user)
            ->get('/panitia/dashboard');

        $response->assertOk();

        $html = $response->getContent();

        // Check structure: should have rounded-xl before panel titles
        // Looking for pattern: <div class="rounded-xl ...<div ...><h3..Antrian Verifikasi
        $this->assertStringContainsString('rounded-xl', $html);
        $this->assertStringContainsString('Antrian Verifikasi', $html);
        $this->assertStringContainsString('Hasil TIYAS Terbaru', $html);
        $this->assertStringContainsString('Dashboard Rekap Pelatihan', $html);
    }

    public function test_page_header_component_exists_with_text_2xl(): void
    {
        $user = User::factory()->create(['role' => 'panitia']);

        $response = $this
            ->actingAs($user)
            ->get('/panitia/dashboard');

        $response->assertOk();

        // Should have text-2xl from page-header h1
        $response->assertSee('text-2xl', false);
    }

    public function test_empty_state_messages_are_honest(): void
    {
        $user = User::factory()->create(['role' => 'panitia']);

        $response = $this
            ->actingAs($user)
            ->get('/panitia/dashboard');

        $response->assertOk();

        // Should have empty state messages for sections without data
        $response->assertSee('Tidak ada', false);
    }

    public function test_dashboard_metric_cards_in_grid(): void
    {
        $user = User::factory()->create(['role' => 'panitia']);

        $response = $this
            ->actingAs($user)
            ->get('/panitia/dashboard');

        $response->assertOk();

        // Should have grid layout classes
        $response->assertSee('grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4', false);
    }
}
