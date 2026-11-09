<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresensisLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_presensi_page_loads_scan_result_before_scanner_container()
    {
        // Create a pelatihan
        $pelatihan = Pelatihan::factory()->create([
            'tanggal_mulai' => today(),
            'tanggal_selesai' => today()->addDays(5),
        ]);

        // Create panitia user
        $panitia = User::factory()->create(['role' => 'panitia']);

        // Get the presensi page
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.presensi', $pelatihan));

        $response->assertStatus(200);
        $response->assertSeeText('Scan QR Code');

        // Extract HTML and verify order: scan result should appear before scanner container
        $html = $response->getContent();

        // Find position of "Hasil Scan (Di atas video)" comment/result div
        $resultPosition = strpos($html, 'x-show="scanResult"');
        // Find position of qr_scanner_container
        $scannerPosition = strpos($html, 'id="qr_scanner_container"');

        // Result should appear before scanner in HTML structure
        $this->assertNotFalse($resultPosition, 'Scan result div not found in HTML');
        $this->assertNotFalse($scannerPosition, 'Scanner container not found in HTML');
        $this->assertLessThan($scannerPosition, $resultPosition, 'Scan result should appear before scanner container in HTML');
    }

    public function test_presensi_page_contains_continue_scanning_button()
    {
        // Create a pelatihan
        $pelatihan = Pelatihan::factory()->create([
            'tanggal_mulai' => today(),
            'tanggal_selesai' => today()->addDays(5),
        ]);

        // Create panitia user
        $panitia = User::factory()->create(['role' => 'panitia']);

        // Get the presensi page
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.presensi', $pelatihan));

        $response->assertStatus(200);
        // Check that the "Scan peserta berikutnya" button text exists
        $response->assertSeeText('Scan peserta berikutnya');

        // Verify it's a full-width button
        $html = $response->getContent();
        $this->assertStringContainsString('class="w-full inline-flex items-center justify-center', $html);
    }

    public function test_presensi_scanner_container_has_responsive_styles()
    {
        // Create a pelatihan
        $pelatihan = Pelatihan::factory()->create([
            'tanggal_mulai' => today(),
            'tanggal_selesai' => today()->addDays(5),
        ]);

        // Create panitia user
        $panitia = User::factory()->create(['role' => 'panitia']);

        // Get the presensi page
        $response = $this->actingAs($panitia)
            ->get(route('pelatihan.presensi', $pelatihan));

        $response->assertStatus(200);

        $html = $response->getContent();

        // Verify scanner container has responsive styles
        $this->assertStringContainsString('width: 100%; overflow: hidden; position: relative;', $html, 'Scanner container missing responsive styles');
    }
}
