<?php

namespace Tests\Feature;

use App\Models\Asrama;
use App\Models\Kamar;
use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsramaFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected User $panitia;

    protected Asrama $asrama;

    protected Kamar $kamar;

    protected function setUp(): void
    {
        parent::setUp();

        $this->panitia = User::factory()->create(['role' => 'panitia']);
        $this->asrama = Asrama::factory()->create();
        $this->kamar = Kamar::factory()->create([
            'asrama_id' => $this->asrama->id,
            'nomor_kamar' => 'A-001',
            'kapasitas' => 3,
        ]);
    }

    public function test_show_displays_success_message_after_kapasitas_update(): void
    {
        $response = $this->actingAs($this->panitia)->patch(
            route('panitia.kamar.update', $this->kamar),
            ['kapasitas' => 5]
        );

        $response->assertRedirect(route('panitia.asrama.show', $this->asrama));

        $response = $this->actingAs($this->panitia)->get(route('panitia.asrama.show', $this->asrama));
        $response->assertSee('Kapasitas kamar berhasil diperbarui.');
    }

    public function test_show_displays_error_message_for_invalid_kapasitas(): void
    {
        $response = $this->actingAs($this->panitia)->patch(
            route('panitia.kamar.update', $this->kamar),
            ['kapasitas' => 99]
        );

        $response->assertRedirect();

        $response = $this->actingAs($this->panitia)->get(route('panitia.asrama.show', $this->asrama));
        $response->assertSee('Kapasitas maksimal 10.');
    }

    public function test_show_displays_success_message_after_tambah_kamar(): void
    {
        $response = $this->actingAs($this->panitia)->post(
            route('panitia.kamar.store', $this->asrama),
            [
                'nomor_kamar' => 'A-002',
                'kapasitas' => 2,
            ]
        );

        $response->assertRedirect(route('panitia.asrama.show', $this->asrama));

        $response = $this->actingAs($this->panitia)->get(route('panitia.asrama.show', $this->asrama));
        $response->assertSee('Kamar berhasil ditambahkan.');
    }

    public function test_badge_status_kamar_tidak_mengandung_backslash(): void
    {
        // Create a full kamar setup with occupant to check badge rendering
        $pelatihan = Pelatihan::factory()->create();
        $user = User::factory()->create(['role' => 'peserta']);
        Pendaftaran::factory()->create([
            'peserta_id' => $user->id,
            'pelatihan_id' => $pelatihan->id,
            'kamar_id' => $this->kamar->id,
            'check_in' => now()->format('Y-m-d'),
            'check_out' => now()->addDays(3)->format('Y-m-d'),
            'status_verifikasi' => 'diverifikasi',
        ]);

        // Fill kamar to capacity to trigger 'Penuh' status
        for ($i = 0; $i < 2; $i++) {
            Pendaftaran::factory()->create([
                'peserta_id' => User::factory()->create(['role' => 'peserta'])->id,
                'pelatihan_id' => $pelatihan->id,
                'kamar_id' => $this->kamar->id,
                'check_in' => now()->format('Y-m-d'),
                'check_out' => now()->addDays(3)->format('Y-m-d'),
                'status_verifikasi' => 'diverifikasi',
            ]);
        }

        $response = $this->actingAs($this->panitia)->get(route('panitia.asrama.show', $this->asrama));

        // Verify that badge contains the expected style without backslash
        $response->assertSee('style="background-color: var(--color-danger);', false);

        // Verify that there's no backslash-quote in the HTML response for the badge
        $content = $response->getContent();
        // Check that we don't have text-white\" style=\"background-color in the response
        $this->assertStringNotContainsString('text-white\" style=\"background-color', $content, 
            'Badge HTML contains backslash before style attribute (HTML malformed)');
    }
}
