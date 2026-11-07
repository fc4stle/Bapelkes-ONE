<?php

namespace Tests\Feature;

use Tests\TestCase;

class TokenWarnaTest extends TestCase
{
    /**
     * Verifikasi bahwa semua variabel CSS var(--color-xxx) di Blade files
     * benar-benar terdefinisikan di resources/css/app.css.
     */
    public function test_all_color_tokens_are_defined(): void
    {
        // Baca resources/css/app.css dan ekstrak daftar token yang valid
        $cssFile = file_get_contents(base_path('resources/css/app.css'));
        preg_match_all('/--color-([a-z-]+):/i', $cssFile, $matches);
        $validTokens = array_map(fn ($token) => "color-{$token}", $matches[1]);
        sort($validTokens);

        // Scan semua Blade files
        $viewDir = base_path('resources/views');
        $bladeFiles = $this->getBladeFiles($viewDir);

        $undefinedTokens = [];
        $usedTokens = [];

        foreach ($bladeFiles as $filePath) {
            $content = file_get_contents($filePath);

            // Cari semua var(--color-xxx)
            preg_match_all('/var\(--color-([a-z-]+)\)/i', $content, $matches);

            foreach ($matches[1] as $token) {
                $fullToken = "color-{$token}";
                $usedTokens[$fullToken] = true;

                if (! in_array($fullToken, $validTokens)) {
                    $undefinedTokens[$fullToken] = true;
                }
            }
        }

        // Report
        $this->assertEmpty(
            $undefinedTokens,
            "Ditemukan variabel CSS yang tidak terdefinisikan di app.css:\n".
            '- '.implode("\n- ", array_keys($undefinedTokens))."\n\n".
            "Token yang valid:\n".
            '- '.implode("\n- ", $validTokens)
        );
    }

    /**
     * Rekursif scan directory untuk mencari file .blade.php
     */
    private function getBladeFiles(string $dir): array
    {
        $files = [];
        $items = scandir($dir);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = "{$dir}/{$item}";

            if (is_dir($path)) {
                $files = array_merge($files, $this->getBladeFiles($path));
            } elseif (str_ends_with($item, '.blade.php')) {
                $files[] = $path;
            }
        }

        return $files;
    }
}
