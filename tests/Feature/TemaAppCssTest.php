<?php

namespace Tests\Feature;

use Tests\TestCase;

class TemaAppCssTest extends TestCase
{
    /**
     * Test: app.css tidak mengandung selektor [data-brand="band"] untuk p, span, a
     */
    public function test_app_css_does_not_have_problematic_band_selectors(): void
    {
        $cssPath = resource_path('css/app.css');
        $this->assertFileExists($cssPath);

        $content = file_get_contents($cssPath);

        // Assert: selektor [data-brand="band"] a tidak ada
        $this->assertStringNotContainsString('[data-brand="band"] a', $content,
            'app.css harus tidak mengandung [data-brand="band"] a yang membuat teks dropdown putih');

        // Assert: selektor [data-brand="band"] span tidak ada
        $this->assertStringNotContainsString('[data-brand="band"] span', $content,
            'app.css harus tidak mengandung [data-brand="band"] span');

        // Assert: selektor [data-brand="band"] p tidak ada
        $this->assertStringNotContainsString('[data-brand="band"] p', $content,
            'app.css harus tidak mengandung [data-brand="band"] p');

        // Assert: .band-sub class harus ada
        $this->assertStringContainsString('.band-sub', $content,
            'app.css harus mengandung .band-sub untuk teks sekunder di band');
    }

    /**
     * Test: Heading dalam band masih punya warna putih
     */
    public function test_band_heading_color_is_white(): void
    {
        $cssPath = resource_path('css/app.css');
        $this->assertFileExists($cssPath);

        $content = file_get_contents($cssPath);

        // Assert: selektor [data-brand="band"] h1 masih ada
        $this->assertStringContainsString('[data-brand="band"] h1', $content);
        $this->assertStringContainsString('color: #ffffff', $content);
    }
}
