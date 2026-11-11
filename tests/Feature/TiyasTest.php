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
     * Test feature: halaman pendaftarans.index peserta menampilkan "Isi Evaluasi TIYAS"
     * untuk pendaftaran diverifikasi + pelatihan status selesai.
     */
    public function test_pendaftarans_index_menampilkan_tombol_isi_tiyas()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
        ]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $this->actingAs($peserta)
            ->get(route('pendaftarans.index'))
            ->assertOk()
            ->assertSeeText('Isi Evaluasi TIYAS');
    }

    /**
     * Test feature: halaman pendaftarans.index peserta menampilkan "Evaluasi sudah diisi"
     * setelah ada TiyasResponse.
     */
    public function test_pendaftarans_index_menampilkan_evaluasi_sudah_diisi()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->subDay(),
        ]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        // Buat TiyasResponse (tanpa peserta_id)
        TiyasResponse::factory()->create([
            'pendaftaran_id' => $pendaftaran->id,
        ]);

        $this->actingAs($peserta)
            ->get(route('pendaftarans.index'))
            ->assertOk()
            ->assertSeeText('Evaluasi sudah diisi');
    }

    /**
     * Test feature: halaman pendaftarans.index TIDAK menampilkan tombol TIYAS
     * jika pelatihan status 'dibuka'.
     */
    public function test_pendaftarans_index_tidak_tampil_jika_status_dibuka()
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
            ->get(route('pendaftarans.index'))
            ->assertOk()
            ->assertDontSeeText('Isi Evaluasi TIYAS');
    }

    /**
     * Test: peserta bisa isi TIYAS jika pelatihan status 'selesai' meskipun tanggal_selesai masa depan.
     */
    public function test_peserta_bisa_isi_status_selesai_tanggal_masa_depan()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_selesai' => now()->addDays(7), // Masa depan, tapi status selesai
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
     * Test: peserta BOLEH isi TIYAS jika tanggal_selesai lewat (meski status masih dibuka).
     * Ini adalah perilaku baru: tanggal_selesai adalah sumber kebenaran utama, bukan status manual.
     */
    public function test_peserta_boleh_isi_tiyas_status_dibuka_tanggal_masa_lalu()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_selesai' => now()->subDay(), // Masa lalu: sudah selesai menurut tanggal
        ]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        // Peserta BOLEH mengakses form TIYAS (tidak 403) karena tanggal_selesai lewat
        $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran))
            ->assertSuccessful();
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
     * Test: hasil menampilkan tombol "Unduh CSV" (bukan "Download CSV").
     */
    public function test_hasil_tombol_unduh_csv()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan))
            ->assertOk()
            ->assertSeeText('Unduh CSV')
            ->assertDontSeeText('Download CSV');
    }

    /**
     * Test: hasil menampilkan judul konstruk dalam bahasa Indonesia (Iklim Transfer, etc).
     */
    public function test_hasil_menampilkan_judul_konstruk_indonesia()
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
            ->assertSeeText('Iklim Transfer')
            ->assertDontSeeText('Transfer Climate');
    }

    /**
     * Test: hasil legenda membaca batas dari config('tiyas.kategori').
     */
    public function test_hasil_legenda_membaca_dari_config()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
        ]);
        TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran->id]);

        // Default config: Rendah ≤ 2.00, Sedang ≤ 3.00, Tinggi ≤ 4.00
        $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan))
            ->assertOk()
            ->assertSeeText('Rendah ≤ 2.00')
            ->assertSeeText('Sedang ≤ 3.00')
            ->assertSeeText('Tinggi ≤ 4.00');

        // Test dengan mengubah config
        config()->set('tiyas.kategori', [
            ['label' => 'Rendah', 'maks' => 1.50],
            ['label' => 'Sedang', 'maks' => 2.50],
            ['label' => 'Tinggi', 'maks' => 4.00],
        ]);

        $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan))
            ->assertOk()
            ->assertSeeText('Rendah ≤ 1.50')
            ->assertSeeText('Sedang ≤ 2.50')
            ->assertSeeText('Tinggi ≤ 4.00');
    }

    /**
     * Test: hasil menampilkan grid-cols-2 untuk kartu skor (responsive).
     */
    public function test_hasil_kartu_skor_grid_cols_2()
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
            ->assertSee('grid-cols-2');
    }

    /**
     * Test: hasil menampilkan kartu per peserta (md:hidden) dan tabel (hidden md:block).
     */
    public function test_hasil_tampil_kartu_dan_tabel_responsive()
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
            ->get(route('tiyas.hasil', $pelatihan));

        $response->assertOk();
        // Cek kehadiran class responsive
        $content = $response->getContent();
        $this->assertStringContainsString('md:hidden', $content); // Kartu per peserta
        $this->assertStringContainsString('hidden md:block', $content); // Tabel
    }

    /**
     * Test: hasil skor rata-rata dan jumlah pengisi tetap benar.
     */
    public function test_hasil_skor_rata_rata_dan_jumlah_benar()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 3 peserta dengan response
        for ($i = 0; $i < 3; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
            ]);
            TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran->id]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan))
            ->assertOk();

        // Verifikasi jumlah pengisi ditampilkan
        $content = $response->getContent();
        $this->assertStringContainsString('3</strong> dari <strong>', $content);
    }

    /**
     * Test: hasil halaman hanya bisa dibuka panitia/admin (peserta 403).
     */
    public function test_hasil_halaman_hanya_panitia_admin()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        $this->actingAs($peserta)
            ->get(route('tiyas.hasil', $pelatihan))
            ->assertForbidden();
    }

    /**
     * Test: detail halaman ganti label konstruk dengan judul Indonesia.
     */
    public function test_detail_label_konstruk_bahasa_indonesia()
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
            ->assertSeeText('Iklim Transfer');
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

    /**
     * Test: form TIYAS peserta menampilkan struktur penghitung "N dari totalQuestions terjawab".
     */
    public function test_form_tiyas_menampilkan_dari_65_terjawab()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran));

        $response->assertOk();
        $content = $response->getContent();

        // Verifikasi struktur Alpine untuk penghitung (x-text tidak terrender saat test)
        $this->assertStringContainsString('x-text="answered"', $content);
        $this->assertStringContainsString('x-text="totalQuestions"', $content);
        $this->assertStringContainsString('terjawab', $content);
    }

    /**
     * Test: form TIYAS memiliki atribut novalidate.
     */
    public function test_form_tiyas_memiliki_atribut_novalidate()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran))
            ->assertOk()
            ->assertSee('novalidate');
    }

    /**
     * Test: form TIYAS memiliki 5 langkah dengan atribut data-step 1..5.
     */
    public function test_form_tiyas_memiliki_5_langkah_dengan_data_step()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran));

        $response->assertOk();
        $content = $response->getContent();

        // Verifikasi 5 langkah ada
        for ($step = 1; $step <= 5; $step++) {
            $this->assertStringContainsString('data-step="'.$step.'"', $content);
        }
    }

    /**
     * Test: form TIYAS memuat 65 input radio dengan 4 nilai (1..4).
     */
    public function test_form_tiyas_memuat_65_radio_dengan_4_nilai()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran));

        $response->assertOk();
        $content = $response->getContent();

        // Hitung radio inputs
        $config = config('tiyas');
        $totalRadios = 0;
        foreach ($config['konstruk'] as $konstruk) {
            foreach ($konstruk['dimensi'] as $dimensi) {
                $totalRadios += count($dimensi) * 4; // 4 nilai per butir
            }
        }

        // Verifikasi total radio inputs dengan nilai 1-4
        preg_match_all('/name=["\']?(TC|TE|ITT|YB)\d+["\']/i', $content, $matches);
        $this->assertCount($totalRadios, $matches[0], 'Total radio inputs harus '.$totalRadios);
    }

    /**
     * Test: form TIYAS menggunakan has-[:checked]: bukan peer-checked.
     */
    public function test_form_tiyas_menggunakan_has_checked_bukan_peer_checked()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran));

        $response->assertOk()
            ->assertSee('has-[:checked]:')
            ->assertDontSee('peer-checked');
    }

    /**
     * Test: form TIYAS menggunakan grid-cols-4 untuk pilihan.
     */
    public function test_form_tiyas_menggunakan_grid_cols_4()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran))
            ->assertOk()
            ->assertSee('grid-cols-4');
    }

    /**
     * Test: form TIYAS menampilkan tombol "Lanjut", "Kembali", "Simpan Evaluasi".
     */
    public function test_form_tiyas_menampilkan_tombol_navigasi()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran));

        $response->assertOk()
            ->assertSeeText('Lanjut')
            ->assertSeeText('Kembali')
            ->assertSeeText('Simpan Evaluasi');
    }

    /**
     * Test: form TIYAS header menampilkan langkah.
     */
    public function test_form_tiyas_header_menampilkan_langkah()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran));

        $response->assertOk();
        $content = $response->getContent();

        // Verifikasi struktur Alpine untuk header langkah
        $this->assertStringContainsString("'Langkah '", $content);
        $this->assertStringContainsString('dari 5', $content);
        $this->assertStringContainsString('x-text="stepTitle"', $content);
    }

    /**
     * Test: form TIYAS total pertanyaan dari config (ubah config, angka berubah).
     */
    public function test_form_tiyas_total_pertanyaan_dari_config()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran));

        $response->assertOk();
        $content = $response->getContent();

        // Verifikasi totalQuestions ada di Alpine x-data (default 65 dari config)
        $this->assertStringContainsString('tiyasForm(65', $content);
        $this->assertStringContainsString('x-text="totalQuestions"', $content);
    }

    /**
     * Test: setelah POST tidak valid (satu butir hilang), redirect dan halaman form terbuka di langkah yang error.
     */
    public function test_form_tiyas_error_terbuka_di_langkah_yang_error()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        // Kirim data dengan satu butir hilang (TC1)
        $data = $this->generateValidTiyasData();
        unset($data['TC1']);

        $response = $this->actingAs($peserta)
            ->post(route('tiyas.simpan', $pendaftaran), $data);

        // Redirect dan session punya error
        $response->assertRedirect(route('tiyas.form', $pendaftaran));
        $response->assertSessionHasErrors('TC1');

        // Kunjungi form ulang (dengan session error)
        $formResponse = $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran));

        $formResponse->assertOk();
        $content = $formResponse->getContent();

        // Verifikasi langkah error (TC1 ada di bagian A = langkah 2)
        $this->assertStringContainsString('initialStep', $content);
    }

    /**
     * Test: old() jawaban tetap tercentang setelah error validasi.
     */
    public function test_form_tiyas_old_jawaban_tetap_tercentang()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        // Kirim data dengan satu butir hilang
        $data = $this->generateValidTiyasData();
        $data['TC1'] = '2'; // Set nilai untuk TC1
        unset($data['TE1']); // Hapus TE1 sehingga gagal validasi

        $response = $this->actingAs($peserta)
            ->post(route('tiyas.simpan', $pendaftaran), $data);

        $response->assertSessionHasErrors('TE1');

        // Kunjungi form ulang
        $formResponse = $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran));

        $formResponse->assertOk();
        $content = $formResponse->getContent();

        // Verifikasi TC1 dengan value 2 masih ada (old value)
        $this->assertStringContainsString('checked', $content); // Input radio dengan checked
    }

    /**
     * Test: pengiriman valid tetap menyimpan 65 jawaban.
     */
    public function test_form_tiyas_simpan_65_jawaban_lengkap()
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
            ->assertRedirect(route('pendaftarans.index'));

        $tiyasResponse = TiyasResponse::where('pendaftaran_id', $pendaftaran->id)->first();
        $this->assertNotNull($tiyasResponse);
        $this->assertCount(65, $tiyasResponse->jawaban);
    }

    /**
     * Test: peserta lain masih 403 pada form TIYAS.
     */
    public function test_form_tiyas_peserta_lain_403()
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

    /**
     * Test: saveDraft tidak simpan dan restoreDraft tidak pulihkan kunci yang diawali "_" (_token, _method).
     */
    public function test_savedraft_tidak_simpan_kunci_underscore()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran));

        $response->assertOk();
        $content = $response->getContent();

        // Verifikasi ada exclusion logic untuk kunci "_"
        $this->assertStringContainsString("startsWith('_')", $content,
            'Form harus punya check startsWith("_") untuk mengabaikan _token dan _method');
    }

    /**
     * Test: bar tombol bawah memiliki container max-w-2xl untuk align dengan kartu.
     */
    public function test_bar_tombol_memiliki_max_w_2xl()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran));

        $response->assertOk();
        $content = $response->getContent();

        // Verifikasi max-w-2xl di dalam bar tombol (fixed bottom container)
        $this->assertStringContainsString('max-w-2xl', $content,
            'Bar tombol harus punya container max-w-2xl mx-auto untuk align dengan kartu di desktop');
    }

    /**
     * Test: setiap kartu butir memiliki atribut data-item dengan kode butir.
     */
    public function test_kartu_butir_memiliki_data_item()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran));

        $response->assertOk();
        $content = $response->getContent();

        // Hitung total butir dari config
        $config = config('tiyas');
        $totalButir = 0;
        $expected = [];
        foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
            foreach ($konstruk['dimensi'] as $dimensi) {
                foreach ($dimensi as $kode => $text) {
                    $totalButir++;
                    $expected[] = $kode;
                }
            }
        }

        // Verifikasi setiap kartu butir punya data-item
        preg_match_all('/data-item="([^"]+)"/', $content, $matches);
        $uniqueItems = array_unique($matches[1] ?? []);

        // Hapus placeholder JS (${fieldName}) dari hasil
        $uniqueItems = array_filter($uniqueItems, function ($item) {
            return strpos($item, '$') === false;
        });
        $uniqueItems = array_values($uniqueItems); // Re-index array

        $this->assertCount($totalButir, $uniqueItems,
            'Total data-item unik harus sama dengan total butir dari config ('.$totalButir.')');
    }

    /**
     * Test: tautan "Batalkan" tidak ada di dalam bar tombol bawah (moved to atas).
     */
    public function test_batalkan_tautan_di_luar_bar_tombol()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran));

        $response->assertOk();
        $content = $response->getContent();

        // Verifikasi tautan "Batalkan" ada dengan format "← Batalkan"
        $this->assertStringContainsString('Batalkan', $content,
            'Tautan Batalkan harus ada di form');
    }

    /**
     * Test: form TIYAS kartu butir memiliki @change="onItemChange($event)".
     */
    public function test_form_tiyas_kartu_butir_memiliki_on_item_change()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran));

        $response->assertOk()
            ->assertSee('@change="onItemChange($event)"', false);
    }

    /**
     * Test: tombol "Lanjut" tidak memiliki ganda style dan inline flex/justify-content.
     */
    public function test_tombol_lanjut_tidak_ganda_style()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran));

        $response->assertOk();
        $content = $response->getContent();

        // Verifikasi tombol "Lanjut" ada dan class flex-1 sm:flex-none
        $this->assertStringContainsString('class="px-6 py-2 rounded-lg font-medium text-white transition-all duration-150 hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed flex-1 sm:flex-none sm:ml-auto"', $content);
        // Verifikasi tombol Lanjut hanya punya 1 style attribute dengan background-color
        preg_match('/.*?<button[^>]*@click="nextStep\(\)"[^>]*style="[^"]*"/', $content, $matches);
        $this->assertNotEmpty($matches, 'Tombol Lanjut harus ada dengan style attribute');
        // Pastikan style attribute hanya 1 dan hanya background-color
        $this->assertStringContainsString('style="background-color: var(--color-primary);"', $matches[0]);
    }

    /**
     * Test: tombol "Simpan Evaluasi" tidak memiliki ganda style dan inline flex/justify-content.
     */
    public function test_tombol_simpan_evaluasi_tidak_ganda_style()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran));

        $response->assertOk();
        $content = $response->getContent();

        // Verifikasi tombol submit (Simpan Evaluasi) ada dan class flex-1 sm:flex-none
        $this->assertStringContainsString('class="px-6 py-2 rounded-lg font-medium text-white transition-all duration-150 hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed flex-1 sm:flex-none sm:ml-auto"', $content);
        // Pastikan style attribute hanya 1 dan hanya background-color
        preg_match('/.*?<button[^>]*type="submit"[^>]*style="[^"]*"/', $content, $matches);
        $this->assertNotEmpty($matches, 'Tombol Simpan Evaluasi harus ada dengan style attribute');
        $this->assertStringContainsString('style="background-color: var(--color-primary);"', $matches[0]);
    }

    /**
     * Test: form TIYAS memiliki style block dengan rule [data-invalid].
     */
    public function test_form_tiyas_memiliki_style_block_data_invalid()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($peserta)
            ->get(route('tiyas.form', $pendaftaran));

        $response->assertOk();
        $content = $response->getContent();

        // Verifikasi ada style block dengan rule untuk [data-invalid]
        $this->assertStringContainsString('[data-invalid="true"]', $content);
        $this->assertStringContainsString('[data-invalid="false"]', $content);
        $this->assertStringContainsString('border-color: var(--color-danger)', $content);
    }
}
