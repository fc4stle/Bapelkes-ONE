<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageHeaderComponentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: components/page-header.blade.php memuat 'text-white' pada h1
     */
    public function test_page_header_component_has_text_white(): void
    {
        $componentFile = file_get_contents(resource_path('views/components/page-header.blade.php'));

        // Assert memuat text-white pada h1
        $this->assertStringContainsString(
            'text-white',
            $componentFile,
            'page-header component h1 should have text-white class'
        );

        // Assert TIDAK memuat text-[var(--color-text)] pada h1
        $this->assertStringNotContainsString(
            'text-[var(--color-text)]',
            $componentFile,
            'page-header component should not have text-[var(--color-text)] on h1'
        );

        // Assert subtitle TIDAK memuat text-[var(--color-muted)]
        $this->assertStringNotContainsString(
            'text-[var(--color-muted)]',
            $componentFile,
            'page-header component should not have text-[var(--color-muted)] on subtitle'
        );
    }

    /**
     * Test: Render dashboard panitia, h1 dalam header harus text-white
     */
    public function test_dashboard_header_h1_has_text_white(): void
    {
        $user = User::factory()->create(['role' => 'panitia']);

        $response = $this->actingAs($user)->get('/panitia/dashboard');

        $response->assertStatus(200);

        // Parse HTML untuk cari h1 dalam header
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($response->getContent());
        libxml_use_internal_errors(false);

        $xpath = new \DOMXPath($dom);
        $headers = $xpath->query('//header');

        $this->assertGreaterThan(0, $headers->length, 'Should have header element');

        // Cari h1 dalam header
        $h1InHeader = null;
        foreach ($headers as $header) {
            $h1s = $header->getElementsByTagName('h1');
            if ($h1s->length > 0) {
                $h1InHeader = $h1s->item(0);
                break;
            }
        }

        $this->assertNotNull($h1InHeader, 'Should have h1 element in header');

        // Cek class attribute
        $classes = $h1InHeader->getAttribute('class');

        $this->assertStringContainsString(
            'text-white',
            $classes,
            'h1 in header should have text-white class'
        );

        $this->assertStringNotContainsString(
            'text-[var(--color-text)]',
            $classes,
            'h1 in header should not have text-[var(--color-text)] class'
        );
    }

    /**
     * Test: Dashboard HTML TIDAK memuat --tw-divide-opacity inline style
     */
    public function test_dashboard_no_tw_divide_opacity(): void
    {
        $user = User::factory()->create(['role' => 'panitia']);

        $response = $this->actingAs($user)->get('/panitia/dashboard');

        $response->assertStatus(200);

        $this->assertStringNotContainsString(
            '--tw-divide-opacity',
            $response->getContent(),
            'Dashboard should not have --tw-divide-opacity inline style'
        );
    }

    /**
     * Test: Dashboard HTML TIDAK memuat text-green-, text-yellow-, text-red-
     */
    public function test_dashboard_table_no_colored_text(): void
    {
        $user = User::factory()->create(['role' => 'panitia']);

        $response = $this->actingAs($user)->get('/panitia/dashboard');

        $response->assertStatus(200);

        $content = $response->getContent();

        $this->assertStringNotContainsString(
            'text-green-',
            $content,
            'Dashboard should not have text-green-* classes'
        );

        $this->assertStringNotContainsString(
            'text-yellow-',
            $content,
            'Dashboard should not have text-yellow-* classes'
        );

        $this->assertStringNotContainsString(
            'text-red-',
            $content,
            'Dashboard should not have text-red-* classes'
        );
    }

    /**
     * Test: Dashboard HTML TIDAK memuat uppercase tracking-wider
     */
    public function test_dashboard_table_header_no_uppercase(): void
    {
        $user = User::factory()->create(['role' => 'panitia']);

        $response = $this->actingAs($user)->get('/panitia/dashboard');

        $response->assertStatus(200);

        $this->assertStringNotContainsString(
            'uppercase tracking-wider',
            $response->getContent(),
            'Dashboard table headers should not have uppercase tracking-wider'
        );
    }
}
