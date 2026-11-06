<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\TiyasResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiyasTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: peserta bisa isi form TIYAS jika pelatihan selesai + diverifikasi.
     */
    public function test_peserta_bisa_akses_form_tiyas()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(), // Sudah lewat
        ]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran))
            ->assertOk()
            ->assertViewIs('tiyas.form');
    }

    /**
     * Test: peserta 403 jika pelatihan belum selesai.
     */
    public function test_peserta_403_pelatihan_belum_selesai()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_selesai' => now()->addDays(7),
        ]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran))
            ->assertForbidden();
    }

    /**
     * Test: peserta 403 jika belum diverifikasi.
     */
    public function test_peserta_403_belum_diverifikasi()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);

        $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran))
            ->assertForbidden();
    }

    /**
     * Test: peserta 403 jika sudah pernah isi.
     */
    public function test_peserta_403_sudah_isi_tiyas()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);
        TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran->id]);

        $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran))
            ->assertForbidden();
    }

    /**
     * Test: peserta 403 jika mencoba isi milik orang lain.
     */
    public function test_peserta_403_bukan_pemilik()
    {
        $peserta1 = User::factory()->create(['role' => 'peserta']);
        $peserta2 = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta1->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $this->actingAs($peserta2)
            ->get(route('tiyas.form', $pendaftaran))
            ->assertForbidden();
    }

    /**
     * Test: panitia 403 tidak bisa isi TIYAS.
     */
    public function test_panitia_403_tidak_bisa_isi()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $this->actingAs($panitia)
            ->get(route('tiyas.form', $pendaftaran))
            ->assertForbidden();
    }

    /**
     * Test: validasi identitas wajib diisi dan dari pilihan.
     */
    public function test_validasi_identitas_wajib()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $this->actingAs($peserta)
            ->post(route('tiyas.simpan', $pendaftaran), [
                // Tidak isi identitas dan jawaban
            ])
            ->assertSessionHasErrors(['asal_instansi', 'asal_kabupaten', 'jenis_profesi', 'masa_kerja', 'waktu_sejak_selesai']);
    }

    /**
     * Test: validasi identitas tidak valid gagal.
     */
    public function test_validasi_identitas_tidak_valid_gagal()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $data = $this->generateValidTiyasData();
        $data['asal_instansi'] = 'INVALID OPTION'; // Tidak ada di config

        $this->actingAs($peserta)
            ->post(route('tiyas.simpan', $pendaftaran), $data)
            ->assertSessionHasErrors('asal_instansi');
    }

    /**
     * Test: validasi butir 1-4 saja.
     */
    public function test_validasi_butir_harus_1_sampai_4()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $data = $this->generateValidTiyasData();
        $data['TC1'] = 5; // Nilai di luar 1-4

        $this->actingAs($peserta)
            ->post(route('tiyas.simpan', $pendaftaran), $data)
            ->assertSessionHasErrors('TC1');
    }

    /**
     * Test: simpan sukses membuat TiyasResponse.
     */
    public function test_simpan_sukses_buat_tiyas_response()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $data = $this->generateValidTiyasData();

        $this->actingAs($peserta)
            ->post(route('tiyas.simpan', $pendaftaran), $data)
            ->assertRedirect(route('pendaftarans.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tiyas_responses', [
            'pendaftaran_id' => $pendaftaran->id,
        ]);
    }

    /**
     * Test: panitia bisa lihat hasil TIYAS untuk pelatihan.
     */
    public function test_hasil_panitia_bisa_akses()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan))
            ->assertOk()
            ->assertViewIs('tiyas.hasil');
    }

    /**
     * Test: peserta 403 di halaman hasil TIYAS.
     */
    public function test_peserta_403_hasil_tiyas()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        $this->actingAs($peserta)
            ->get(route('tiyas.hasil', $pelatihan))
            ->assertForbidden();
    }

    /**
     * Test: hasil menampilkan empty state jika tidak ada.
     */
    public function test_hasil_menampilkan_empty_state()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan))
            ->assertOk()
            ->assertSee('Belum ada peserta yang mengisi evaluasi');
    }

    /**
     * Test: hasil menampilkan skor benar.
     */
    public function test_hasil_menampilkan_skor_benar()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
        ]);
        TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran->id]);

        $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan))
            ->assertOk()
            ->assertSee('dari');
    }

    /**
     * Test: panitia bisa lihat detail TIYAS.
     */
    public function test_detail_panitia_bisa_akses()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
        ]);
        $tiyas = TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran->id]);

        $this->actingAs($panitia)
            ->get(route('tiyas.detail', $tiyas))
            ->assertOk()
            ->assertViewIs('tiyas.detail');
    }

    /**
     * Test: panitia bisa simpan catatan.
     */
    public function test_panitia_simpan_catatan()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
        ]);
        $tiyas = TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran->id]);

        $this->actingAs($panitia)
            ->patch(route('tiyas.catatan', $tiyas), [
                'catatan_supervisor' => 'Catatan supervisor',
                'catatan_rekan_kerja' => 'Catatan rekan kerja',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tiyas_responses', [
            'id' => $tiyas->id,
            'catatan_supervisor' => 'Catatan supervisor',
            'catatan_rekan_kerja' => 'Catatan rekan kerja',
        ]);
    }

    /**
     * Test: export CSV punya header dan data.
     */
    public function test_export_csv_punya_header_dan_data()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
        ]);
        TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran->id]);

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.export', $pelatihan));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // Check response merupakan StreamedResponse
        $this->assertNotNull($response->getContent());
    }

    // Helper methods

    private function generateValidTiyasData(): array
    {
        $config = config('tiyas');
        $data = [
            'asal_instansi' => 'Dinas Kesehatan',
            'asal_kabupaten' => 'Kota Yogyakarta',
            'jenis_profesi' => 'Perawat',
            'masa_kerja' => '3–5 tahun',
            'waktu_sejak_selesai' => '3–6 bulan',
        ];

        // Tambah semua butir dengan nilai 3
        foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
            foreach ($konstruk['dimensi'] as $dimensi) {
                foreach ($dimensi as $kode => $text) {
                    $data[$kode] = '3';
                }
            }
        }

        return $data;
    }
}
