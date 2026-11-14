<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\TiyasResponse;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiyasSemuaPelatihanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * BUG 1 TEST: Diagram batang "Rata-rata Skor per Konstruk" menampilkan bars dengan height > 0
     * 
     * Masalah: Kode mengakses $pel_data['means'] (plural), seharusnya $pel_data['mean'] (singular).
     * Hasil: semua bar kosong height=0.
     * 
     * Fix: Ubah key 'means' menjadi 'mean', dan gunakan $colors (CSS var) bukan $colorFallback (hex).
     */
    public function test_bug1_diagram_batang_means_to_mean()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pel1 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => 'Pelatihan A',
        ]);
        $pel2 = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
            'nama' => 'Pelatihan B',
        ]);

        // Pelatihan 1: 6 optimal
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pend = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pel1->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create(['pendaftaran_id' => $pend->id]);
        }

        // Pelatihan 2: 6 optimal
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pend = Pendaftaran::factory()->hadir()->create([
                'status_verifikasi' => 'diverifikasi',
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pel2->id,
            ]);
            TiyasResponse::factory()->withProfil('optimal')->create(['pendaftaran_id' => $pend->id]);
        }

        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', ['pelatihan' => $pel1, 'cakupan' => 'semua']));
        $response->assertOk();

        $html = $response->getContent();
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_use_internal_errors(false);

        $xpath = new DOMXPath($dom);

        // Test: SVG diagram batang ada dan punya bars dengan height > 0
        $chartSvg = $xpath->query('//svg[@data-chart="antar-pelatihan-mean"]')->item(0);
        $this->assertNotNull($chartSvg, 'SVG diagram batang harus ada (data-chart="antar-pelatihan-mean")');

        $rects = $xpath->query('.//rect', $chartSvg);
        $nonZeroRects = 0;
        $totalRects = 0;

        foreach ($rects as $rect) {
            $height = (float) ($rect->getAttribute('height') ?? 0);
            $totalRects++;
            if ($height > 0.5) {
                $nonZeroRects++;
            }
        }

        $this->assertGreaterThan(0, $nonZeroRects,
            "Bug 1: Diagram batang kosong. Ditemukan hanya $nonZeroRects/$totalRects bars dengan height > 0.5. \n"
            .'Kemungkinan penyebab: key "means" (plural) bukan "mean" (singular), atau color tidak terdefinisi.');
    }
}
