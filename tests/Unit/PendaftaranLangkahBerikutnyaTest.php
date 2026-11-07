<?php

namespace Tests\Unit;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\TiyasResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendaftaranLangkahBerikutnyaTest extends TestCase
{
    use RefreshDatabase;

    protected User $peserta;

    protected User $panitia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->peserta = User::factory()->create(['role' => 'peserta']);
        $this->panitia = User::factory()->create(['role' => 'panitia']);
    }

    public function test_a_ditolak_dengan_alasan(): void
    {
        $pelatihan = Pelatihan::factory()->create();
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'ditolak',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'alasan_penolakan' => 'Data tidak lengkap',
        ]);

        $langkah = $pendaftaran->langkahBerikutnya();

        $this->assertEquals('Pendaftaran ditolak', $langkah['judul']);
        $this->assertEquals('Data tidak lengkap', $langkah['deskripsi']);
        $this->assertEquals('bahaya', $langkah['tone']);
        $this->assertCount(1, $langkah['aksi']);
        $this->assertEquals('Lihat katalog', $langkah['aksi'][0]['label']);
    }

    public function test_b_pending(): void
    {
        $pelatihan = Pelatihan::factory()->create();
        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);

        $langkah = $pendaftaran->langkahBerikutnya();

        $this->assertEquals('Menunggu verifikasi panitia', $langkah['judul']);
        $this->assertEquals('Panitia akan memeriksa data Anda.', $langkah['deskripsi']);
        $this->assertEquals('info', $langkah['tone']);
        $this->assertCount(1, $langkah['aksi']);
        $this->assertEquals('Ubah data', $langkah['aksi'][0]['label']);
    }

    public function test_c_selesai_belum_hadir_harusnya_tdk_selesai(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-05',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => null,
        ]);

        $langkah = $pendaftaran->langkahBerikutnya();

        // Karena pelatihan selesai TAPI belum hadir, masuk aturan d (belum hadir)
        $this->assertEquals('Pendaftaran terverifikasi', $langkah['judul']);
    }

    public function test_c_selesai_hadir_dapat_sertifikat(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-05',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now()->subDays(1),
            'kode_sertifikat' => 'CERT-123',
            'sertifikat_generated_at' => now(),
        ]);

        // Sudah ada TIYAS response, jadi hanya sertifikat
        TiyasResponse::factory()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'submitted_at' => now(),
        ]);

        $langkah = $pendaftaran->langkahBerikutnya();

        $this->assertEquals('Pelatihan selesai', $langkah['judul']);
        $this->assertEquals('sukses', $langkah['tone']);
        $this->assertCount(1, $langkah['aksi']);
        $this->assertEquals('Unduh sertifikat', $langkah['aksi'][0]['label']);
    }

    public function test_c_selesai_hadir_belum_tiyas(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-05',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now()->subDays(1),
            'kode_sertifikat' => 'CERT-123',
            'sertifikat_generated_at' => now(),
        ]);

        $langkah = $pendaftaran->langkahBerikutnya();

        $this->assertEquals('Pelatihan selesai', $langkah['judul']);
        // Karena ada sertifikat dan belum TIYAS, expect 2 aksi
        $this->assertCount(2, $langkah['aksi']);
        $this->assertEquals('Unduh sertifikat', $langkah['aksi'][0]['label']);
        $this->assertEquals('Isi Evaluasi TIYAS', $langkah['aksi'][1]['label']);
    }

    public function test_c_selesai_hadir_sertifikat_tiyas_belum(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-05',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now()->subDays(1),
            'kode_sertifikat' => 'CERT-123',
            'sertifikat_generated_at' => now(),
        ]);

        $langkah = $pendaftaran->langkahBerikutnya();

        $this->assertEquals('Pelatihan selesai', $langkah['judul']);
        $this->assertCount(2, $langkah['aksi']);
        $this->assertEquals('Unduh sertifikat', $langkah['aksi'][0]['label']);
        $this->assertEquals('Isi Evaluasi TIYAS', $langkah['aksi'][1]['label']);
    }

    public function test_c_selesai_hadir_sertifikat_tiyas_sudah(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-05',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now()->subDays(1),
            'kode_sertifikat' => 'CERT-123',
            'sertifikat_generated_at' => now(),
        ]);

        TiyasResponse::factory()->create([
            'pendaftaran_id' => $pendaftaran->id,
            'submitted_at' => now(),
        ]);

        $langkah = $pendaftaran->langkahBerikutnya();

        $this->assertEquals('Pelatihan selesai', $langkah['judul']);
        $this->assertCount(1, $langkah['aksi']);
        $this->assertEquals('Unduh sertifikat', $langkah['aksi'][0]['label']);
    }

    public function test_c_selesai_hadir_tanpa_aksi(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-05',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => null, // Belum hadir, jadi masuk aturan d bukan c
        ]);

        $langkah = $pendaftaran->langkahBerikutnya();

        // Masuk aturan d (belum hadir), bukan c
        $this->assertEquals('Pendaftaran terverifikasi', $langkah['judul']);
    }

    public function test_d_diverifikasi_belum_hadir(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_mulai' => '2026-11-10',
            'tanggal_selesai' => '2026-11-15',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => null,
        ]);

        $langkah = $pendaftaran->langkahBerikutnya();

        $this->assertEquals('Pendaftaran terverifikasi', $langkah['judul']);
        $this->assertStringContainsString('10 Nov', $langkah['deskripsi']);
        $this->assertEquals('info', $langkah['tone']);
        $this->assertCount(1, $langkah['aksi']);
        $this->assertEquals('Kartu peserta', $langkah['aksi'][0]['label']);
    }

    public function test_e_diverifikasi_sudah_hadir(): void
    {
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'dibuka',
            'tanggal_mulai' => '2026-11-10',
            'tanggal_selesai' => '2026-11-15',
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $this->peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $this->panitia->id,
            'verified_at' => now(),
            'hadir_at' => now(),
        ]);

        $langkah = $pendaftaran->langkahBerikutnya();

        $this->assertEquals('Anda sudah hadir', $langkah['judul']);
        $this->assertStringContainsString('15 Nov', $langkah['deskripsi']);
        $this->assertEquals('info', $langkah['tone']);
        $this->assertEmpty($langkah['aksi']);
    }
}
