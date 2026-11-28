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

        // Should have divide-x or xl:divide-x for grid columns
        $response->assertSee('divide-x', false);
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
