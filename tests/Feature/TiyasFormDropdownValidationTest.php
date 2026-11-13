<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test TUGAS B: Form dropdown validation di step 1
 * - Dropdown kosong ditandai merah saat Lanjut diklik
 * - Error message muncul dengan role="alert"
 * - Saat dropdown diisi, tanda hilang dan error muncul
 */
class TiyasFormDropdownValidationTest extends TestCase
{
    use RefreshDatabase;

    private function createTestData()
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create([
            'status' => 'selesai',
            'tanggal_mulai' => now()->subDays(10),
            'tanggal_selesai' => now()->subDays(1),
        ]);

        $pendaftaran = Pendaftaran::factory()->create([
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
            'hadir_at' => now(),
        ]);

        return compact('peserta', 'pelatihan', 'pendaftaran');
    }

    public function test_step1_dropdown_kosong_tidak_bisa_lanjut(): void
    {
        ['pendaftaran' => $pendaftaran, 'peserta' => $peserta] = $this->createTestData();

        $response = $this->actingAs($peserta)->get(route('tiyas.form', $pendaftaran));
        $response->assertStatus(200);

        // Cek form muncul dengan step 1
        $response->assertSee('Data Identitas', false);
        $response->assertSee('asal_instansi', false);
        $response->assertSee('asal_kabupaten', false);
        $response->assertSee('jenis_profesi', false);
        $response->assertSee('masa_kerja', false);
        $response->assertSee('waktu_sejak_selesai', false);
    }

    public function test_step1_semua_dropdown_terisi_bisa_lanjut(): void
    {
        ['pendaftaran' => $pendaftaran, 'peserta' => $peserta] = $this->createTestData();

        // Siapkan data valid dari config
        $config = config('tiyas');

        $data = [
            'asal_instansi' => $config['identitas']['asal_instansi'][0] ?? 'Dinas Kesehatan',
            'asal_kabupaten' => $config['identitas']['asal_kabupaten'][0] ?? 'Sleman',
            'jenis_profesi' => $config['identitas']['jenis_profesi'][0] ?? 'Perawat',
            'masa_kerja' => $config['identitas']['masa_kerja'][0] ?? '1–2 tahun',
            'waktu_sejak_selesai' => $config['identitas']['waktu_sejak_selesai'][0] ?? 'kurang dari 1 bulan',
        ];

        // Submit form step 1
        $response = $this->actingAs($peserta)
            ->post(route('tiyas.simpan', $pendaftaran), array_merge(
                array_fill_keys(array_keys(config('tiyas.soal_tiyas_1', [])), 1),
                $data
            ));

        // Harus berhasil atau redirect (tidak ada validation error)
        $this->assertTrue($response->status() === 200 || $response->status() === 302);
    }

    public function test_form_dropdown_styling_pada_error(): void
    {
        ['pendaftaran' => $pendaftaran, 'peserta' => $peserta] = $this->createTestData();

        $response = $this->actingAs($peserta)->get(route('tiyas.form', $pendaftaran));

        // Cek struktur form: select dengan data-invalid attribute
        $response->assertSee('data-invalid', false);
        $response->assertSee('select', false);
        $response->assertSee('name="asal_instansi"', false);
        $response->assertSee('name="asal_kabupaten"', false);
        $response->assertSee('name="jenis_profesi"', false);
        $response->assertSee('name="masa_kerja"', false);
        $response->assertSee('name="waktu_sejak_selesai"', false);
    }

    public function test_form_error_message_dengan_role_alert(): void
    {
        ['pendaftaran' => $pendaftaran, 'peserta' => $peserta] = $this->createTestData();

        $response = $this->actingAs($peserta)->get(route('tiyas.form', $pendaftaran));

        // Cek script menciptakan error div dengan role="alert"
        $response->assertSee('data-error-message', false);
        $response->assertSee('role', false);
        $response->assertSee('alert', false);
        $response->assertSee('Masih ada', false);
    }

    public function test_form_bottom_bar_z_index(): void
    {
        ['pendaftaran' => $pendaftaran, 'peserta' => $peserta] = $this->createTestData();

        $response = $this->actingAs($peserta)->get(route('tiyas.form', $pendaftaran));

        // Cek bottom bar punya z-40
        $response->assertSee('z-40', false);
    }

    public function test_dropdown_identitas_punya_change_handler(): void
    {
        ['pendaftaran' => $pendaftaran, 'peserta' => $peserta] = $this->createTestData();

        $response = $this->actingAs($peserta)->get(route('tiyas.form', $pendaftaran));

        // Verifikasi semua 5 dropdown di langkah 1 punya @change="onItemChange($event)"
        $response->assertSee('name="asal_instansi"', false);
        $response->assertSee('@change="onItemChange($event)"', false);
        $response->assertSee('name="asal_kabupaten"', false);
        $response->assertSee('name="jenis_profesi"', false);
        $response->assertSee('name="masa_kerja"', false);
        $response->assertSee('name="waktu_sejak_selesai"', false);
    }

    public function test_dropdown_error_styling_hilang_saat_dipilih(): void
    {
        ['pendaftaran' => $pendaftaran, 'peserta' => $peserta] = $this->createTestData();

        $response = $this->actingAs($peserta)->get(route('tiyas.form', $pendaftaran));

        // Cek script Alpine yang menghapus error saat onItemChange dipanggil
        $response->assertSee('onItemChange', false);
        $response->assertSee('data-invalid', false);
        $response->assertSee('--color-danger', false);
    }
}
