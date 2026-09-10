<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifikasiPendaftaranTest extends TestCase
{
    use RefreshDatabase;

    protected User $panitia;

    protected User $peserta;

    protected Pelatihan $pelatihan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->panitia = User::factory()->create(['role' => 'panitia']);
        $this->peserta = User::factory()->create(['role' => 'peserta']);
        $this->pelatihan = Pelatihan::factory()->create(['status' => 'dibuka']);
    }

    public function test_panitia_can_access_pendaftar_page(): void
    {
        Pendaftaran::factory()->count(3)->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('pelatihan.pendaftar', $this->pelatihan));

        $response->assertStatus(200);
        $response->assertViewIs('pelatihans.pendaftar');
    }

    public function test_peserta_cannot_access_pendaftar_page(): void
    {
        Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);

        $response = $this->actingAs($this->peserta)
            ->get(route('pelatihan.pendaftar', $this->pelatihan));

        $response->assertForbidden();
    }

    public function test_guest_cannot_access_pendaftar_page(): void
    {
        $response = $this->get(route('pelatihan.pendaftar', $this->pelatihan));

        $response->assertRedirect(route('login'));
    }

    public function test_panitia_can_verify_pendaftar(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);

        $response = $this->actingAs($this->panitia)
            ->patch(route('pendaftarans.verifikasi', $pendaftaran));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pendaftarans', [
            'id' => $pendaftaran->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
        ]);
    }

    public function test_panitia_can_reject_pendaftaran_with_reason(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);

        $response = $this->actingAs($this->panitia)
            ->patch(route('pendaftarans.tolak', $pendaftaran), [
                'alasan_penolakan' => 'Data tidak lengkap',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pendaftarans', [
            'id' => $pendaftaran->id,
            'status_verifikasi' => 'ditolak',
            'verified_by' => $this->panitia->id,
            'alasan_penolakan' => 'Data tidak lengkap',
        ]);
    }

    public function test_rejection_requires_alasan(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);

        $response = $this->actingAs($this->panitia)
            ->patch(route('pendaftarans.tolak', $pendaftaran), [
                'alasan_penolakan' => '',
            ]);

        $response->assertSessionHasErrors('alasan_penolakan');
        $this->assertDatabaseHas('pendaftarans', [
            'id' => $pendaftaran->id,
            'status_verifikasi' => 'pending',
        ]);
    }

    public function test_peserta_cannot_verify_pendaftaran(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);

        $response = $this->actingAs($this->peserta)
            ->patch(route('pendaftarans.verifikasi', $pendaftaran));

        $response->assertForbidden();
        $this->assertDatabaseHas('pendaftarans', [
            'id' => $pendaftaran->id,
            'status_verifikasi' => 'pending',
        ]);
    }

    public function test_peserta_cannot_reject_pendaftaran(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);

        $response = $this->actingAs($this->peserta)
            ->patch(route('pendaftarans.tolak', $pendaftaran), [
                'alasan_penolakan' => 'Alasan test',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('pendaftarans', [
            'id' => $pendaftaran->id,
            'status_verifikasi' => 'pending',
        ]);
    }

    public function test_pendaftar_page_shows_pendaftar_data(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'pending',
            'data_diri' => [
                'nama' => 'Budi Santoso',
                'nik' => '1234567890123456',
                'kontak' => '08123456789',
                'profesi' => 'Perawat',
                'instansi' => 'RS Dr. Sardjito',
            ],
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('pelatihan.pendaftar', $this->pelatihan));

        $response->assertStatus(200);
        $response->assertSee('Budi Santoso');
        $response->assertSee('1234567890123456');
        $response->assertSee('08123456789');
        $response->assertSee('Perawat');
        $response->assertSee('RS Dr. Sardjito');
        $response->assertSee('Belum Diverifikasi');
    }

    public function test_pendaftar_page_shows_verified_status(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'data_diri' => [
                'nama' => 'Andi Wijaya',
                'nik' => '6543210987654321',
                'kontak' => '08765432109',
                'profesi' => 'Dokter',
                'instansi' => 'RSUP Dr. M. Djamil',
            ],
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('pelatihan.pendaftar', $this->pelatihan));

        $response->assertStatus(200);
        $response->assertSee('Terverifikasi');
    }

    public function test_pendaftar_page_shows_rejected_status_with_reason(): void
    {
        $pendaftaran = Pendaftaran::factory()->create([
            'pelatihan_id' => $this->pelatihan->id,
            'status_verifikasi' => 'ditolak',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'alasan_penolakan' => 'Surat tugas tidak valid',
            'data_diri' => [
                'nama' => 'Citra Dewi',
                'nik' => '1122334455667788',
                'kontak' => '081122334455',
                'profesi' => 'Bidan',
                'instansi' => 'Puskesmas Sewon',
            ],
        ]);

        $response = $this->actingAs($this->panitia)
            ->get(route('pelatihan.pendaftar', $this->pelatihan));

        $response->assertStatus(200);
        $response->assertSee('Ditolak');
        $response->assertSee('Surat tugas tidak valid');
    }

    public function test_pendaftar_page_shows_empty_state(): void
    {
        $response = $this->actingAs($this->panitia)
            ->get(route('pelatihan.pendaftar', $this->pelatihan));

        $response->assertStatus(200);
        $response->assertSee('Belum ada pendaftar');
    }
}
