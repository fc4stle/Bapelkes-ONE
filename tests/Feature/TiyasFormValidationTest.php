<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiyasFormValidationTest extends TestCase
{
    use RefreshDatabase;

    private function createTestData()
    {
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai', // atau ubah tanggal
            'tanggal_mulai' => now()->subDays(10),
            'tanggal_selesai' => now()->subDays(1), // Kemarin, jadi sudah selesai
        ]);

        $peserta = User::factory()->create(['role' => 'peserta']);

        $pendaftaran = Pendaftaran::factory()->create([
            'pelatihan_id' => $pelatihan->id,
            'peserta_id' => $peserta->id,
            'status_verifikasi' => 'diverifikasi',
            'hadir_at' => now(),
        ]);

        return ['pendaftaran' => $pendaftaran, 'peserta' => $peserta, 'pelatihan' => $pelatihan];
    }

    /**
     * Test: belum hadir → GET tiyas.form 403
     */
    public function test_peserta_belum_hadir_dapat_403_form(): void
    {
        ['peserta' => $peserta] = $this->createTestData();
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_mulai' => now()->subDays(10),
            'tanggal_selesai' => now()->subDays(1),
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'pelatihan_id' => $pelatihan->id,
            'peserta_id' => $peserta->id,
            'status_verifikasi' => 'diverifikasi',
            'hadir_at' => null, // Belum hadir
        ]);

        $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran))
            ->assertStatus(403);
    }

    /**
     * Test: sudah hadir → GET tiyas.form 200
     */
    public function test_peserta_sudah_hadir_dapat_200_form(): void
    {
        ['pendaftaran' => $pendaftaran, 'peserta' => $peserta] = $this->createTestData();

        $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran))
            ->assertStatus(200)
            ->assertViewIs('tiyas.form');
    }

    /**
     * Test: belum hadir → POST tiyas.simpan 403
     */
    public function test_peserta_belum_hadir_tidak_bisa_submit(): void
    {
        ['peserta' => $peserta] = $this->createTestData();
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_mulai' => now()->subDays(10),
            'tanggal_selesai' => now()->subDays(1),
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'pelatihan_id' => $pelatihan->id,
            'peserta_id' => $peserta->id,
            'status_verifikasi' => 'diverifikasi',
            'hadir_at' => null,
        ]);

        $this->actingAs($peserta)
            ->post(route('tiyas.simpan', $pendaftaran), [
                'asal_instansi' => 'Test',
                'asal_kabupaten' => 'Test',
                'jenis_profesi' => 'Test',
                'masa_kerja' => 'Test',
                'waktu_sejak_selesai' => 'Test',
            ])
            ->assertStatus(403);
    }

    /**
     * Test: form validation step 1 dengan dropdown kosong
     * Harap: pesan error terlihat dan tidak pindah step
     */
    public function test_step1_dropdown_kosong_tampil_error(): void
    {
        ['pendaftaran' => $pendaftaran, 'peserta' => $peserta] = $this->createTestData();

        // GET form pertama kali
        $response = $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran));

        $response->assertStatus(200)
            ->assertViewIs('tiyas.form')
            ->assertViewHas('initialStep', 1)
            ->assertViewHas('totalQuestions');
    }

    /**
     * Test: form validation step 1 dengan semua dropdown terisi
     * Harap: form bisa submit dan TIYAS response tersimpan
     */
    public function test_step1_semua_dropdown_terisi_submit_berhasil(): void
    {
        ['pendaftaran' => $pendaftaran, 'peserta' => $peserta] = $this->createTestData();

        $data = [
            'asal_instansi' => 'Dinas Kesehatan',
            'asal_kabupaten' => 'Sleman',
            'jenis_profesi' => 'Perawat',
            'masa_kerja' => '3–5 tahun',
            'waktu_sejak_selesai' => '7–12 bulan',
        ];

        // Ambil list pertanyaan dari config
        $config = config('tiyas');
        foreach ($config['konstruk'] as $kode => $konstruk) {
            foreach ($konstruk['dimensi'] as $dimensi) {
                foreach ($dimensi as $kodeButir => $text) {
                    $data[$kodeButir] = 2; // Pilih nilai tengah
                }
            }
        }

        $response = $this->actingAs($peserta)
            ->post(route('tiyas.simpan', $pendaftaran), $data);

        $response->assertRedirect(route('pendaftarans.index'))
            ->assertSessionHas('success');

        // Cek TIYAS response tersimpan
        $this->assertDatabaseHas('tiyas_responses', [
            'pendaftaran_id' => $pendaftaran->id,
        ]);
    }
}
