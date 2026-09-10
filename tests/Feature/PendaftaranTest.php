<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendaftaranTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_shows_authenticated_users_own_registrations(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['nama' => 'Pelatihan K3 Dasar']);
        Pendaftaran::factory()->create(['peserta_id' => $peserta->id, 'pelatihan_id' => $pelatihan->id]);

        $response = $this->actingAs($peserta)->get(route('pendaftarans.index'));

        $response->assertStatus(200);
        $response->assertViewIs('pendaftarans.index');
        $response->assertSee('Pelatihan K3 Dasar');
    }

    public function test_index_shows_empty_state_when_no_registrations(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);

        $response = $this->actingAs($peserta)->get(route('pendaftarans.index'));

        $response->assertStatus(200);
        $response->assertSee('Belum ada pendaftaran');
    }

    public function test_peserta_can_register_to_an_open_pelatihan(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'dibuka', 'kuota' => 10]);

        $response = $this->actingAs($peserta)->post(route('pelatihans.daftar', $pelatihan), [
            'nama' => 'John Doe',
            'nik' => '3201234567890001',
            'kontak' => '08123456789',
            'profesi' => 'Perawat',
            'instansi' => 'RSUD Kota',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('pendaftarans', [
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);
    }

    public function test_store_fails_when_pelatihan_is_not_open(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'draft']);
        $response = $this->actingAs($peserta)->post(route('pelatihans.daftar', $pelatihan));

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('pendaftarans', [
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
        ]);
    }

    public function test_store_fails_when_pelatihan_is_full(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'dibuka', 'kuota' => 1]);
        Pendaftaran::factory()->create([
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($peserta)->post(route('pelatihans.daftar', $pelatihan));

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('pendaftarans', [
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
        ]);
    }

    public function test_store_fails_when_already_registered(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'dibuka', 'kuota' => 10]);
        Pendaftaran::factory()->create(['peserta_id' => $peserta->id, 'pelatihan_id' => $pelatihan->id]);

        $response = $this->actingAs($peserta)->post(route('pelatihans.daftar', $pelatihan));

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('pendaftarans', 1);
    }

    public function test_panitia_cannot_register(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'dibuka', 'kuota' => 10]);

        $response = $this->actingAs($panitia)->post(route('pelatihans.daftar', $pelatihan));

        $response->assertForbidden();
    }

    public function test_panitia_can_update_pendaftaran_status(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pendaftaran = Pendaftaran::factory()->create(['status_verifikasi' => 'pending']);

        $response = $this->actingAs($panitia)->patch(route('pendaftarans.update', $pendaftaran), [
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('pendaftarans', [
            'id' => $pendaftaran->id,
            'status_verifikasi' => 'diverifikasi',
        ]);
    }

    public function test_peserta_cannot_update_pendaftaran_status(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pendaftaran = Pendaftaran::factory()->create(['status_verifikasi' => 'pending']);

        $response = $this->actingAs($peserta)->patch(route('pendaftarans.update', $pendaftaran), [
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response->assertForbidden();
    }

    public function test_update_validates_status(): void
    {
        $panitia = User::factory()->create(['role' => 'panitia']);
        $pendaftaran = Pendaftaran::factory()->create(['status_verifikasi' => 'pending']);

        $response = $this->actingAs($panitia)->patch(route('pendaftarans.update', $pendaftaran), [
            'status_verifikasi' => 'invalid-status',
        ]);

        $response->assertSessionHasErrors('status_verifikasi');
    }

    public function test_peserta_can_cancel_own_registration(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pendaftaran = Pendaftaran::factory()->create(['peserta_id' => $peserta->id]);

        $response = $this->actingAs($peserta)->delete(route('pendaftarans.destroy', $pendaftaran));

        $response->assertRedirect();
        $this->assertDatabaseMissing('pendaftarans', ['id' => $pendaftaran->id]);
    }

    public function test_peserta_cannot_cancel_another_users_registration(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $other = User::factory()->create(['role' => 'peserta']);
        $pendaftaran = Pendaftaran::factory()->create(['peserta_id' => $other->id]);

        $response = $this->actingAs($peserta)->delete(route('pendaftarans.destroy', $pendaftaran));

        $response->assertForbidden();
        $this->assertDatabaseHas('pendaftarans', ['id' => $pendaftaran->id]);
    }

    public function test_peserta_can_submit_pendaftaran_with_asrama(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'dibuka', 'kuota' => 10]);

        $response = $this->actingAs($peserta)->post(route('pelatihans.daftar', $pelatihan), [
            'nama' => 'Budi Santoso',
            'nik' => '3201234567890001',
            'kontak' => '08123456789',
            'profesi' => 'Perawat',
            'instansi' => 'RSUD Kota',
            'butuh_asrama' => true,
            'check_in' => '2026-10-01',
            'check_out' => '2026-10-03',
        ]);

        $response->assertRedirect(route('pendaftarans.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('pendaftarans', [
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'pending',
            'butuh_asrama' => true,
        ]);
    }

    public function test_peserta_can_submit_pendaftaran_without_asrama(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);
        $pelatihan = Pelatihan::factory()->create(['status' => 'dibuka', 'kuota' => 10]);

        $response = $this->actingAs($peserta)->post(route('pelatihans.daftar', $pelatihan), [
            'nama' => 'Ani Wijaya',
            'nik' => '3201234567890002',
            'kontak' => '08123456788',
            'profesi' => 'Bidan',
            'instansi' => 'Puskesmas',
        ]);

        $response->assertRedirect(route('pendaftarans.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('pendaftarans', [
            'peserta_id' => $peserta->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'pending',
            'butuh_asrama' => false,
        ]);
    }
}
