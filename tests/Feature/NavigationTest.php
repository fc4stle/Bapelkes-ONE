<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_menu_has_proper_pill_styling(): void
    {
        $user = User::factory()->create(['role' => 'panitia']);

        $response = $this
            ->actingAs($user)
            ->get('/panitia/dashboard');

        $response->assertOk();

        // Nav menu container should have items-center
        $response->assertSee('gap-1 xl:flex items-center', false);

        // Nav pills should have h-10, whitespace-nowrap, leading-none
        $response->assertSee('whitespace-nowrap', false);
        $response->assertSee('leading-none', false);
    }

    public function test_lainnya_button_has_correct_inactive_styling(): void
    {
        $user = User::factory()->create(['role' => 'panitia']);

        $response = $this
            ->actingAs($user)
            ->get('/panitia/dashboard');

        $response->assertOk();

        $html = $response->getContent();

        // Should have border-transparent for inactive state
        $response->assertSee('border-transparent hover:bg-white/10', false);
    }

    public function test_divider_visibility_at_breakpoints(): void
    {
        $user = User::factory()->create(['role' => 'panitia']);

        $response = $this
            ->actingAs($user)
            ->get('/panitia/dashboard');

        $response->assertOk();

        // Divider should be hidden below 2xl
        $response->assertSee('hidden 2xl:block w-px h-7 bg-white/25', false);
    }

    public function test_user_button_displays_name_only_desktop(): void
    {
        $user = User::factory()->create(['role' => 'panitia', 'name' => 'Test Panitia']);

        $response = $this
            ->actingAs($user)
            ->get('/panitia/dashboard');

        $response->assertOk();

        // Name should be truncated
        $response->assertSee('truncate max-w-[9rem]', false);

        // Role badge should be hidden below 2xl
        $response->assertSee('hidden 2xl:inline-block', false);
    }

    public function test_page_header_component_exists(): void
    {
        $user = User::factory()->create(['role' => 'panitia']);

        $response = $this
            ->actingAs($user)
            ->get('/panitia/dashboard');

        $response->assertOk();

        // Page header should have text-2xl
        $response->assertSee('text-2xl font-semibold leading-tight', false);
    }
}
