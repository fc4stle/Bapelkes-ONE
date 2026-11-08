<?php

namespace Tests\Feature;

use App\Enums\MetodePelatihan;
use App\Enums\StatusPelatihan;
use App\Models\Asrama;
use App\Models\Kamar;
use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use App\Services\TiyasScorer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AlurLengkapTest extends TestCase
{
    use RefreshDatabase;

    /**
     * End-to-end workflow test: 10 langkah dari pendaftaran sampai TIYAS.
     *
     * Langkah:
     * 1. Admin login → /admin/users → buat panitia baru
     * 2. Panitia buat pelatihan (status 'dibuka')
     * 3. Peserta register lewat form → daftar pelatihan → kamar dialokasikan
     * 4. Peserta akses control: 403 pada /admin/users, /panitia/dashboard
     * 5. Panitia verifikasi pendaftaran
     * 6. Panitia presensi (peserta hadir)
     * 7. Panitia ubah status pelatihan jadi 'selesai'
     * 8. Peserta isi TIYAS (65 item dengan nilai berbeda per konstruk)
     * 9. Panitia lihat hasil TIYAS dan export
     * 10. Admin ubah role panitia jadi peserta, panitia akses 403
     */
    public function test_alur_lengkap_dari_pendaftaran_sampai_tiyas()
    {
        // ===== SETUP =====
        $admin = User::factory()->create(['role' => 'admin']);
        $asrama = Asrama::factory()->create(['kapasitas' => 2]);
        // Buat kamar dengan kapasitas cukup - PENTING: nomor_kamar harus unik
        $kamar = Kamar::factory()->create([
            'asrama_id' => $asrama->id,
            'kapasitas' => 2,
            'nomor_kamar' => 'A101',
        ]);

        // ===== STEP 1: Admin buat panitia =====
        $panitiaData = [
            'name' => 'Panitia Test',
            'email' => 'panitia.test@local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'panitia',
        ];
        $response = $this->actingAs($admin)->post(route('admin.users.store'), $panitiaData);
        $response->assertRedirect(route('admin.users.index'));

        $panitia = User::where('email', 'panitia.test@local')->first();
        $this->assertNotNull($panitia);
        $this->assertEquals('panitia', $panitia->role);

        // Verify panitia tampil di admin.users.index
        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        $response->assertOk();
        $response->assertSee('Panitia Test');

        // ===== STEP 2: Panitia buat pelatihan =====
        $pelatihanData = [
            'name' => 'Pelatihan Uji Alur',
            'nama' => 'Pelatihan Uji Alur',
            'deskripsi' => 'Pelatihan untuk uji alur lengkap',
            'metode' => MetodePelatihan::Luring->value,
            'kuota' => 10,
            'tanggal_mulai' => now()->addDay()->format('Y-m-d'),
            'tanggal_selesai' => now()->addDays(3)->format('Y-m-d'),
            'jam_mulai' => '09:00',
            'jam_selesai' => '17:00',
            'lokasi' => 'Ruang Pelatihan A',
            'status' => StatusPelatihan::Dibuka->value,
            'sks' => 2,
        ];
        $response = $this->actingAs($panitia)->post(route('pelatihans.store'), $pelatihanData);
        $response->assertRedirect(route('pelatihans.index'));

        $pelatihan = Pelatihan::where('nama', 'Pelatihan Uji Alur')->first();
        $this->assertNotNull($pelatihan);
        $this->assertEquals(StatusPelatihan::Dibuka, $pelatihan->status);

        // ===== STEP 3: Peserta register via register form =====
        // PENTING: Logout dari context sebelumnya untuk reset
        Auth::logout();

        $pesertaRegisterData = [
            'name' => 'Peserta Test',
            'email' => 'peserta.test@local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];
        $response = $this->post(route('register'), $pesertaRegisterData);
        $response->assertRedirect(route('dashboard'));

        $peserta = User::where('email', 'peserta.test@local')->first();
        $this->assertNotNull($peserta);
        $this->assertEquals('peserta', $peserta->role); // Verify role default 'peserta'

        // Check katalog menampilkan nama pelatihan
        $response = $this->actingAs($peserta)->get(route('pelatihan.katalog'));
        $response->assertOk();
        $response->assertSee('Pelatihan Uji Alur');

        // Peserta daftar pelatihan
        $pendaftaranData = [
            'nama' => $peserta->name,
            'nik' => '1234567890123456',
            'kontak' => '081234567890',
            'profesi' => 'Perawat',
            'instansi' => 'Puskesmas',
            'check_in' => now()->addDay()->format('Y-m-d'),
            'check_out' => now()->addDays(3)->format('Y-m-d'),
            'butuh_asrama' => '1', // HTML form mengirim string '1' atau '0', bukan boolean
        ];
        $response = $this->actingAs($peserta)->post(route('pelatihans.daftar', $pelatihan), $pendaftaranData);
        $response->assertRedirect(route('pendaftarans.index'));

        $pendaftaran = Pendaftaran::where('peserta_id', $peserta->id)->first();
        $this->assertNotNull($pendaftaran);
        $this->assertEquals('pending', $pendaftaran->status_verifikasi);

        // CRITICAL: Kamar harus dialokasikan saat pendaftaran
        $this->assertNotNull($pendaftaran->kamar_id, 'Kamar seharusnya dialokasikan saat pendaftaran');
        $this->assertEquals($kamar->id, $pendaftaran->kamar_id);

        // Check dashboard peserta: "Menunggu verifikasi panitia"
        $response = $this->actingAs($peserta)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Menunggu Verifikasi');

        // ===== STEP 4: Peserta akses control =====
        $response = $this->actingAs($peserta)->get(route('admin.users.index'));
        $response->assertStatus(403);

        $response = $this->actingAs($peserta)->get(route('panitia.dashboard'));
        $response->assertStatus(403);

        // ===== STEP 5: Panitia verifikasi =====
        $response = $this->actingAs($panitia)->patch(route('pendaftarans.verifikasi', $pendaftaran), []);
        $response->assertRedirect();

        $pendaftaran->refresh();
        $this->assertEquals('diverifikasi', $pendaftaran->status_verifikasi);
        $this->assertNotNull($pendaftaran->kode_presensi);

        // Check panitia dashboard: kartu verifikasi menunjukkan 0 (sudah diverifikasi)
        $response = $this->actingAs($panitia)->get(route('panitia.dashboard'));
        $response->assertOk();
        $response->assertSee('Menunggu Verifikasi');
        $response->assertSee('0'); // 0 pending setelah verifikasi

        // Check peserta dashboard: "Pendaftaran terverifikasi"
        $response = $this->actingAs($peserta)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Terverifikasi');

        // ===== STEP 6: Panitia presensi =====
        // Set waktu test ke dalam rentang tanggal pelatihan
        Carbon::setTestNow($pelatihan->tanggal_mulai->startOfDay());

        $response = $this->actingAs($panitia)->post(route('pelatihan.presensi.proses', $pelatihan), [
            'kode_presensi' => $pendaftaran->kode_presensi,
        ]);
        $response->assertRedirect();

        $pendaftaran->refresh();
        $this->assertNotNull($pendaftaran->hadir_at);

        // ===== STEP 7: Panitia ubah status pelatihan jadi 'selesai' =====
        $updatePelatihanData = [
            'nama' => $pelatihan->nama,
            'deskripsi' => $pelatihan->deskripsi,
            'metode' => $pelatihan->metode->value,
            'kuota' => $pelatihan->kuota,
            'tanggal_mulai' => now()->subDays(2)->format('Y-m-d'),  // Set 2 hari yang lalu
            'tanggal_selesai' => now()->subDay()->format('Y-m-d'),  // Set kemarin
            'lokasi' => $pelatihan->lokasi,
            'status' => StatusPelatihan::Selesai->value,
        ];
        $response = $this->actingAs($panitia)->patch(route('pelatihans.update', $pelatihan), $updatePelatihanData);
        $response->assertRedirect();

        $pelatihan->refresh();
        $this->assertEquals(StatusPelatihan::Selesai, $pelatihan->status);

        // Check peserta dashboard: "Isi Evaluasi TIYAS"
        $response = $this->actingAs($peserta)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Isi Evaluasi TIYAS');

        // ===== STEP 8: Peserta isi TIYAS dengan nilai berbeda per konstruk =====
        $tiyasData = [
            'asal_instansi' => 'Puskesmas',
            'asal_kabupaten' => 'Kota Yogyakarta',
            'jenis_profesi' => 'Perawat',
            'masa_kerja' => '3–5 tahun',
            'waktu_sejak_selesai' => '3–6 bulan',
        ];

        // Generate 65 items dengan nilai berbeda per konstruk
        $tiyas_config = config('tiyas');
        $nilai_per_konstruk = ['TC' => 4, 'TE' => 3, 'ITT' => 2, 'YB' => 1];

        foreach ($tiyas_config['konstruk'] as $konstruk_key => $konstruk) {
            $dimensi_list = $konstruk['dimensi'] ?? [];
            foreach ($dimensi_list as $dimensi_name => $butirs) {
                foreach ($butirs as $butir_id => $butir_text) {
                    $tiyasData[$butir_id] = $nilai_per_konstruk[$konstruk_key];
                }
            }
        }

        $response = $this->actingAs($peserta)->post(route('tiyas.simpan', $pendaftaran), $tiyasData);
        $response->assertRedirect();

        // Verify TIYAS tersimpan
        $tiyasResponse = $pendaftaran->tiyasResponse;
        $this->assertNotNull($tiyasResponse);

        // Verify POST kedua ditolak atau redirect dengan error
        $response = $this->actingAs($peserta)->post(route('tiyas.simpan', $pendaftaran), $tiyasData);
        // Should be 403 atau redirect dengan error message
        $this->assertTrue(
            $response->status() === 403 || $response->status() === 302,
            'POST TIYAS kedua seharusnya ditolak (403 atau redirect)'
        );

        // Verify TiyasResponse tetap 1
        $countTiyas = $pendaftaran->tiyasResponse()->count();
        $this->assertEquals(1, $countTiyas);

        // ===== STEP 9: Panitia lihat hasil TIYAS dan skor =====
        $response = $this->actingAs($panitia)->get(route('tiyas.hasil', $pelatihan));
        $response->assertOk();

        // Verify respons count
        $response->assertSee('peserta terverifikasi sudah mengisi evaluasi');

        // Verify skor dihitung dengan TiyasScorer
        $scorer = new TiyasScorer;
        $skorKonstruk = $scorer->hitungSkor($tiyasData);

        // Verify skor ditampilkan (format 2 decimal: e.g., "3.00")
        foreach ($skorKonstruk as $kode => $hasil) {
            $skorStr = number_format($hasil['skor'], 2);
            $response->assertSee($skorStr);
        }

        // Verify kategori ditampilkan
        $response->assertSee('Tinggi');
        $response->assertSee('Rendah');

        // Detail route
        $response = $this->actingAs($panitia)->get(route('tiyas.detail', $tiyasResponse->id));
        $response->assertOk();

        // Catatan: update catatan
        $response = $this->actingAs($panitia)->patch(route('tiyas.catatan', $tiyasResponse), [
            'catatan_supervisor' => 'Hasil baik, perlu monitoring lanjutan.',
        ]);
        $response->assertRedirect();

        $tiyasResponse->refresh();
        $this->assertEquals('Hasil baik, perlu monitoring lanjutan.', $tiyasResponse->catatan_supervisor);

        // Export
        $response = $this->actingAs($panitia)->get(route('tiyas.export', $pelatihan));
        $response->assertOk();
        // Check CSV header
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        // ===== STEP 10: Sertifikat (setelah selesai + hadir) =====
        $response = $this->actingAs($peserta)->get(route('pendaftarans.sertifikat', $pendaftaran));
        $response->assertOk();

        $pendaftaran->refresh();
        $this->assertNotNull($pendaftaran->kode_sertifikat);

        // ===== STEP 11: Admin ubah role panitia jadi peserta =====
        $response = $this->actingAs($admin)->patch(route('admin.users.updateRole', $panitia), [
            'role' => 'peserta',
        ]);
        $response->assertRedirect();

        $panitia->refresh();
        $this->assertEquals('peserta', $panitia->role);

        // Verify panitia sekarang 403 pada /panitia/dashboard
        $response = $this->actingAs($panitia)->get(route('panitia.dashboard'));
        $response->assertStatus(403);

        // ===== STEP 12: Admin tidak boleh ubah role dirinya sendiri =====
        $response = $this->actingAs($admin)->patch(route('admin.users.updateRole', $admin), [
            'role' => 'panitia',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Anda tidak bisa mengubah role diri sendiri.');

        $admin->refresh();
        $this->assertEquals('admin', $admin->role);

        // Final: Peserta dashboard tidak lagi melihat "Isi Evaluasi TIYAS"
        $response = $this->actingAs($peserta)->get(route('dashboard'));
        $response->assertOk();
        $response->assertDontSee('Isi Evaluasi TIYAS');
    }
}
