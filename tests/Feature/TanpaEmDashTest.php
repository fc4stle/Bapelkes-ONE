<?php

namespace Tests\Feature;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class TanpaEmDashTest extends TestCase
{
    /**
     * Test penjaga: tidak ada em dash (U+2014, &mdash;, \u2014) di kode, view,
     * test, dan dokumentasi. Menggunakan RecursiveIteratorIterator untuk
     * pemindaian folder yang sebenarnya (bukan glob ** yang tidak didukung PHP).
     */
    public function test_tidak_ada_em_dash_di_kode_dan_dokumentasi()
    {
        // Karakter em dash dibangun tanpa literal, supaya file ini sendiri
        // tidak mengandungnya (akan di-skip dari check)
        $emDash = "\u{2014}";
        $mdash = '&md'.'ash;';
        $uEscape = '\u20'.'14';

        $basePath = base_path();
        $dirsToScan = [
            'app',
            'resources',
            'config',
            'routes',
            'database',
            'lang',
            'tests',
            'docs',
        ];

        // Validasi folder wajib ada
        $folderWajib = ['app', 'resources', 'config', 'routes', 'database', 'tests'];
        foreach ($folderWajib as $dir) {
            $this->assertDirectoryExists(
                $basePath.'/'.$dir,
                "Folder wajib $dir tidak ditemukan di base_path"
            );
        }

        // Tambahkan Pertemuan* folders jika ada (opsional)
        $pertemuanDirs = glob($basePath.'/Pertemuan*', GLOB_ONLYDIR);
        if ($pertemuanDirs) {
            $dirsToScan = array_merge($dirsToScan, array_map(function ($path) use ($basePath) {
                return str_replace($basePath.'/', '', $path);
            }, $pertemuanDirs));
        }

        // Ekstensi file yang akan dipindai
        $extensions = ['php', 'js', 'css', 'md', 'json'];

        // Daftar file yang akan dipindai (untuk validasi rekursif)
        $filesToCheck = [];

        // Scan folder rekursif
        foreach ($dirsToScan as $dir) {
            $fullPath = $basePath.'/'.$dir;

            // Skip folder yang tidak ada (lang, docs, Pertemuan* boleh tidak ada)
            if (! is_dir($fullPath)) {
                // Komentar eksplisit untuk folder optional
                if (in_array($dir, ['lang', 'docs']) || strpos($dir, 'Pertemuan') === 0) {
                    continue;
                }

                // Folder wajib harus ada (sudah di-assert di atas)
                continue;
            }

            // RecursiveIteratorIterator untuk pemindaian dalam-dalam
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $fullPath,
                    FilesystemIterator::SKIP_DOTS
                ),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                // Ambil ekstensi file
                $ext = strtolower(pathinfo($file->getPathname(), PATHINFO_EXTENSION));

                // Filter ekstensi (blade.php masuk lewat php)
                if (! in_array($ext, $extensions)) {
                    continue;
                }

                $filesToCheck[] = $file->getRealPath();
            }
        }

        // Tambahkan file root jika ada
        $rootFiles = ['README.md', 'CLAUDE.md', 'AGENTS.md'];
        foreach ($rootFiles as $fname) {
            $fpath = $basePath.'/'.$fname;
            if (file_exists($fpath)) {
                $filesToCheck[] = realpath($fpath);
            }
        }

        // Deduplicate
        $filesToCheck = array_unique($filesToCheck);

        // Validasi bahwa pemindaian menjangkau file yang diharapkan
        $expectedFiles = [
            base_path('resources/views/admin/users/index.blade.php'),
            base_path('app/Http/Controllers/TiyasController.php'),
        ];
        foreach ($expectedFiles as $expected) {
            $this->assertContains(
                realpath($expected),
                $filesToCheck,
                "File $expected seharusnya ada dalam daftar pemindaian rekursif"
            );
        }

        // Dapatkan realpath file tes ini sendiri (untuk di-skip)
        $testFilePath = realpath(__FILE__);

        // Pemindaian: cari em dash
        $ditemukan = [];

        foreach ($filesToCheck as $file) {
            // Skip file tes ini sendiri berdasarkan realpath, bukan strpos nama
            if (realpath($file) === $testFilePath) {
                continue;
            }

            $content = file_get_contents($file);

            // Cek varian em dash
            if (strpos($content, $emDash) !== false ||
                strpos($content, $mdash) !== false ||
                strpos($content, $uEscape) !== false) {
                $ditemukan[] = $file;
            }
        }

        $this->assertEmpty(
            $ditemukan,
            'Ada em dash di file: '.implode(', ', $ditemukan)
        );
    }
}
