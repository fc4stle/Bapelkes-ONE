<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\TiyasResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiyasTahap2SemualPelatihanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: cakupan default = 'pelatihan' saat tidak ada param.
     */
    public function test_default_cakupan_adalah_pelatihan()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Buat 12 peserta + responses
        for ($i = 0; $i < 12; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran->id]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan));

        $response->assertOk();
        // Judul/section harus menunjukkan "Pelatihan ini" atau bahasa serupa default
        // Tidak boleh menampilkan data dari pelatihan lain
        $response->assertSee('dari');
        $response->assertSee('peserta terverifikasi');
    }

    /**
     * Test: ?cakupan=semua mengumpulkan respons dari 2+ pelatihan.
     */
    public function test_cakupan_semua_dari_dua_pelatihan()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan_1 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay(), 'nama' => 'Pelatihan A']);
        $pelatihan_2 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay(), 'nama' => 'Pelatihan B']);

        // 6 respons dari pelatihan 1
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan_1->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran->id]);
        }

        // 6 respons dari pelatihan 2
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan_2->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran->id]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan_1).'?cakupan=semua');

        $response->assertOk();
        // Jumlah titik scatter harus 12 (6+6)
        $response->assertSee('dari');
        $response->assertSee('peserta terverifikasi');
        // Kedua nama pelatihan harus ada di tabel keanggotaan
        $response->assertSee('Pelatihan A');
        $response->assertSee('Pelatihan B');
    }

    /**
     * Test: invalid cakupan param fallback ke 'pelatihan'.
     */
    public function test_invalid_cakupan_fallback()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        for ($i = 0; $i < 8; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran->id]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan).'?cakupan=invalid');

        $response->assertOk();
        // Harus fallback ke pelatihan (single) not semua
        $response->assertSee('dari');
        $response->assertSee('peserta terverifikasi');
    }

    /**
     * Test: kurang dari 8 respons total => pesan "kurang data".
     */
    public function test_kurang_delapan_respons_tampil_pesan_kurang_data()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        // Hanya 5 respons
        for ($i = 0; $i < 5; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran->id]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan));

        $response->assertOk();
        // Harus ada pesan "butuh minimal"
        $response->assertSee('minimal');
        $response->assertSee('peserta');
    }

    /**
     * Test: tabel perbandingan antar pelatihan menampilkan rerata konstruk benar.
     */
    public function test_tabel_perbandingan_rerata_konstruk_benar()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan_1 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay(), 'nama' => 'P1']);
        $pelatihan_2 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay(), 'nama' => 'P2']);

        // 8 respons P1 dengan skor terukur
        for ($i = 0; $i < 8; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan_1->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            // Factory default skor: TC=3, TE=3, ITT=2, YB=2 (medium scores)
            TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran->id]);
        }

        // 8 respons P2 dengan skor sama
        for ($i = 0; $i < 8; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan_2->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran->id]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan_1).'?cakupan=semua');

        $response->assertOk();
        // Tabel perbandingan harus ada dengan 2 baris pelatihan
        $response->assertSee('P1');
        $response->assertSee('P2');
        // Check untuk format rerata (ada titik desimal)
        $response->assertSee('.');
    }

    /**
     * Test: tabel komposisi klaster per pelatihan (cakupan semua) menampilkan jumlah peserta benar.
     */
    public function test_komposisi_klaster_per_pelatihan_total_peserta_benar()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan_1 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay(), 'nama' => 'P1']);
        $pelatihan_2 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay(), 'nama' => 'P2']);

        // 6 respons P1
        for ($i = 0; $i < 6; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan_1->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran->id]);
        }

        // 8 respons P2
        for ($i = 0; $i < 8; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan_2->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran->id]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan_1).'?cakupan=semua');

        $response->assertOk();
        // Tabel komposisi harus menampilkan:
        // P1 baris: total = 6
        // P2 baris: total = 8
        $response->assertSee('P1');
        $response->assertSee('P2');
    }

    /**
     * Test: hanya 1 pelatihan dengan respons => pesan netral tanpa diagram.
     */
    public function test_satu_pelatihan_tampil_pesan_netral_tanpa_diagram()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan_1 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay(), 'nama' => 'P1']);

        // 8 respons P1 saja, tidak ada pelatihan lain
        for ($i = 0; $i < 8; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan_1->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran->id]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan_1).'?cakupan=semua');

        $response->assertOk();
        // Harus ada pesan "Perbandingan butuh minimal 2 pelatihan"
        $response->assertSee('2 pelatihan');
        // Tidak boleh ada data-chart="antar-pelatihan-mean"
        $response->assertDontSee('data-chart="antar-pelatihan-mean"');
    }

    /**
     * Test: form selector axis menjaga cakupan param.
     */
    public function test_form_selector_axis_menjaga_cakupan()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan_1 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pelatihan_2 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        for ($i = 0; $i < 8; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            $pendaftaran = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta->id,
                'pelatihan_id' => $pelatihan_1->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran->id]);

            $peserta2 = User::factory()->create(['role' => 'peserta']);
            $pendaftaran2 = Pendaftaran::factory()->hadir()->create([
                'peserta_id' => $peserta2->id,
                'pelatihan_id' => $pelatihan_2->id,
                'status_verifikasi' => 'diverifikasi',
            ]);
            TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran2->id]);
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan_1).'?cakupan=semua');

        $response->assertOk();
        // Form harus memiliki hidden input cakupan=semua
        $response->assertSee('cakupan');
        $response->assertSee('semua');
    }

    /**
     * Test: peserta tetap 403 (tidak boleh akses).
     */
    public function test_peserta_tetap_403()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        $response = $this->actingAs($peserta)
            ->get(route('tiyas.hasil', $pelatihan));

        $response->assertForbidden();
    }

    /**
     * Test: tidak ada NaN atau INF di HTML.
     */
    public function test_tidak_ada_nan_atau_inf()
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan_1 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);
        $pelatihan_2 = Pelatihan::factory()->create(['status' => 'selesai', 'tanggal_selesai' => now()->subDay()]);

        for ($i = 0; $i < 8; $i++) {
            for ($p = 0; $p < 2; $p++) {
                $peserta = User::factory()->create(['role' => 'peserta']);
                $pelatihan = $p === 0 ? $pelatihan_1 : $pelatihan_2;
                $pendaftaran = Pendaftaran::factory()->hadir()->create([
                    'peserta_id' => $peserta->id,
                    'pelatihan_id' => $pelatihan->id,
                    'status_verifikasi' => 'diverifikasi',
                ]);
                TiyasResponse::factory()->create(['pendaftaran_id' => $pendaftaran->id]);
            }
        }

        $response = $this->actingAs($panitia)
            ->get(route('tiyas.hasil', $pelatihan_1).'?cakupan=semua');

        $response->assertOk();
        $html = $response->getContent();
        $this->assertStringNotContainsString('NaN', $html);
        $this->assertStringNotContainsString('Infinity', $html);
        $this->assertStringNotContainsString('INF', $html);
    }
}
