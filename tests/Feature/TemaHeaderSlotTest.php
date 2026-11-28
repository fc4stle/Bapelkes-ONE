<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class TemaHeaderSlotTest extends TestCase
{
    /**
     * Test: Semua x-slot name="header" tidak mengandung warna gelap eksplisit
     * Exception: beranda.blade.php memiliki tombol gold yang memerlukan teks gelap untuk kontras
     */
    public function test_all_header_slots_do_not_have_dark_color_classes(): void
    {
        $viewsPath = resource_path('views');
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($viewsPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $files_with_header = [];
        $problematic_files = [];

        // Files yang diizinkan memiliki dark color (exception untuk gold buttons, dsb)
        $allowed_dark_color_files = ['beranda.blade.php'];

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getRealPath());

            // Cek apakah file mengandung x-slot name="header"
            if (strpos($content, 'x-slot name="header"') === false) {
                continue;
            }

            $relative = str_replace($viewsPath.'/', '', $file->getRealPath());
            $files_with_header[] = $relative;

            // Skip check untuk file yang diizinkan
            if (in_array($relative, $allowed_dark_color_files, true)) {
                continue;
            }

            // Extract slot content
            if (preg_match('/<x-slot name="header">(.*?)<\/x-slot>/s', $content, $matches)) {
                $slot_content = $matches[1];

                // Check untuk dark color classes
                if (preg_match('/text-\[var\(--color-text\)\]|text-\[var\(--color-primary\)\]|text-gray-|text-black|style="color:\s*var\([^)]+\)"/', $slot_content)) {
                    $problematic_files[] = $relative;
                }
            }
        }

        // Assert: minimal 20 file ditemukan dengan x-slot name="header"
        $this->assertGreaterThanOrEqual(20, count($files_with_header),
            sprintf('Harus ditemukan minimal 20 file dengan x-slot name="header", hanya ditemukan %d', count($files_with_header)));

        // Assert: tidak ada file dengan styling gelap dalam header (kecuali yang diizinkan)
        $this->assertEmpty($problematic_files,
            sprintf('File dengan dark color classes dalam header: %s', implode(', ', $problematic_files)));
    }

    /**
     * Test: admin/aktivitas/index.blade.php judul dibungkus <h2>
     */
    public function test_admin_aktivitas_header_uses_h2_tag(): void
    {
        $filePath = resource_path('views/admin/aktivitas/index.blade.php');
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);

        // Extract header slot
        preg_match('/<x-slot name="header">(.*?)<\/x-slot>/s', $content, $matches);
        $this->assertNotEmpty($matches, 'x-slot name="header" harus ada');

        $slot_content = $matches[1];

        // Assert: "Riwayat Aktivitas" dibungkus dalam <h2>
        $this->assertStringContainsString('<h2', $slot_content);
        $this->assertStringContainsString('Riwayat Aktivitas', $slot_content);
        $this->assertStringContainsString('</h2>', $slot_content);
    }
}
