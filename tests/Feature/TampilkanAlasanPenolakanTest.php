<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TampilkanAlasanPenolakanTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_alasan_penolakan_when_status_is_ditolak(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create();

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'ditolak',
            'alasan_penolakan' => 'Dokumen tidak lengkap. Mohon upload surat rekomendasi.',
            'verified_by' => $panitia->id,
        ]);

        $response = $this->actingAs($peserta)->get(route('pendaftarans.index'));

        $response->assertStatus(200);
        $response->assertSee('Alasan Penolakan:');
        $response->assertSee('Dokumen tidak lengkap. Mohon upload surat rekomendasi.');
    }

    public function test_index_does_not_show_alasan_penolakan_when_status_is_pending(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create();

        Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'pending',
            'alasan_penolakan' => null,
        ]);

        $response = $this->actingAs($peserta)->get(route('pendaftarans.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Alasan Penolakan:');
    }

    public function test_index_does_not_show_alasan_penolakan_when_status_is_diverifikasi(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create();

        Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'alasan_penolakan' => null,
            'verified_by' => $panitia->id,
        ]);

        $response = $this->actingAs($peserta)->get(route('pendaftarans.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Alasan Penolakan:');
    }

    public function test_index_does_not_show_alasan_penolakan_when_ditolak_but_empty(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create();

        Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'ditolak',
            'alasan_penolakan' => null,
            'verified_by' => $panitia->id,
        ]);

        $response = $this->actingAs($peserta)->get(route('pendaftarans.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Alasan Penolakan:');
    }
}
