<?php

namespace Tests\Feature;

use App\Models\Asrama;
use App\Models\Kamar;
use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KelolaAsramaTest extends TestCase
{
    use RefreshDatabase;

    protected User $panitia;

    protected User $admin;

    protected User $peserta;

    protected Asrama $asrama;

    protected Kamar $kamar;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup users
        $this->panitia = User::factory()->create(['role' => 'panitia']);
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->peserta = User::factory()->create(['role' => 'peserta']);

        // Setup data
        $this->asrama = Asrama::factory()->create(['nama' => 'Asrama Kunthi']);
        $this->kamar = Kamar::factory()->create([
            'asrama_id' => $this->asrama->id,
            'nomor_kamar' => '101',
            'kapasitas' => 3,
        ]);
    }

    /* === ACCESS CONTROL TESTS === */

    public function test_guest_redirected_to_login_for_index(): void
    {
        $response = $this->get(route('panitia.asrama.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_guest_redirected_to_login_for_show(): void
    {
        $response = $this->get(route('panitia.asrama.show', $this->asrama));
        $response->assertRedirect(route('login'));
    }

    public function test_peserta_forbidden_to_index(): void
    {
        $response = $this->actingAs($this->peserta)->get(route('panitia.asrama.index'));
        $response->assertStatus(403);
    }

    public function test_peserta_forbidden_to_show(): void
    {
        $response = $this->actingAs($this->peserta)->get(route('panitia.asrama.show', $this->asrama));
        $response->assertStatus(403);
    }

    public function test_panitia_can_access_index(): void
    {
        $response = $this->actingAs($this->panitia)->get(route('panitia.asrama.index'));
        $response->assertStatus(200);
        $response->assertViewIs('panitia.asrama.index');
    }

    public function test_panitia_can_access_show(): void
    {
        $response = $this->actingAs($this->panitia)->get(route('panitia.asrama.show', $this->asrama));
        $response->assertStatus(200);
        $response->assertViewIs('panitia.asrama.show');
    }

    public function test_admin_can_access_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('panitia.asrama.index'));
        $response->assertStatus(200);
        $response->assertViewIs('panitia.asrama.index');
    }

    public function test_admin_can_access_show(): void
    {
        $response = $this->actingAs($this->admin)->get(route('panitia.asrama.show', $this->asrama));
        $response->assertStatus(200);
        $response->assertViewIs('panitia.asrama.show');
    }

    /* === INDEX TESTS === */

    public function test_index_displays_asrama_summary(): void
    {
        $response = $this->actingAs($this->panitia)->get(route('panitia.asrama.index'));

        $asramas = $response->viewData('asramas');
        $this->assertCount(1, $asramas);
        $this->assertEquals('Asrama Kunthi', $asramas[0]['nama']);
        $this->assertEquals(1, $asramas[0]['jumlah_kamar']);
        $this->assertEquals(3, $asramas[0]['total_kapasitas']);
    }

    public function test_active_occupants_excludes_rejected_registrations(): void
    {
        $pelatihan = Pelatihan::factory()->create();
        $user = User::factory()->create(['role' => 'peserta']);

        Pendaftaran::create([
            'peserta_id' => $user->id,
            'pelatihan_id' => $pelatihan->id,
            'kamar_id' => $this->kamar->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
            'status_verifikasi' => 'ditolak',
        ]);

        $response = $this->actingAs($this->panitia)->get(route('panitia.asrama.index'));
        $asramas = $response->viewData('asramas');
        $this->assertEquals(0, $asramas[0]['jumlah_penghuni_aktif']);
    }

    public function test_active_occupants_excludes_expired_registrations(): void
    {
        $pelatihan = Pelatihan::factory()->create();
        $user = User::factory()->create(['role' => 'peserta']);

        Pendaftaran::create([
            'peserta_id' => $user->id,
            'pelatihan_id' => $pelatihan->id,
            'kamar_id' => $this->kamar->id,
            'check_in' => now()->subDays(10)->toDateString(),
            'check_out' => now()->subDays(1)->toDateString(),
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($this->panitia)->get(route('panitia.asrama.index'));
        $asramas = $response->viewData('asramas');
        $this->assertEquals(0, $asramas[0]['jumlah_penghuni_aktif']);
    }

    public function test_date_filter_counts_occupants_on_specific_date(): void
    {
        $pelatihan = Pelatihan::factory()->create();
        $user = User::factory()->create(['role' => 'peserta']);

        Pendaftaran::create([
            'peserta_id' => $user->id,
            'pelatihan_id' => $pelatihan->id,
            'kamar_id' => $this->kamar->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
            'status_verifikasi' => 'diverifikasi',
        ]);

        $filterDate = now()->addDays(2)->toDateString();
        $response = $this->actingAs($this->panitia)->get(route('panitia.asrama.index', ['tanggal' => $filterDate]));

        $asramas = $response->viewData('asramas');
        $this->assertEquals(1, $asramas[0]['jumlah_penghuni_aktif']);
    }

    public function test_date_filter_excludes_occupants_outside_range(): void
    {
        $pelatihan = Pelatihan::factory()->create();
        $user = User::factory()->create(['role' => 'peserta']);

        Pendaftaran::create([
            'peserta_id' => $user->id,
            'pelatihan_id' => $pelatihan->id,
            'kamar_id' => $this->kamar->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
            'status_verifikasi' => 'diverifikasi',
        ]);

        $filterDate = now()->addDays(10)->toDateString();
        $response = $this->actingAs($this->panitia)->get(route('panitia.asrama.index', ['tanggal' => $filterDate]));

        $asramas = $response->viewData('asramas');
        $this->assertEquals(0, $asramas[0]['jumlah_penghuni_aktif']);
    }

    /* === ADD ROOM TESTS === */

    public function test_can_add_room_to_asrama(): void
    {
        $response = $this->actingAs($this->panitia)->post(
            route('panitia.kamar.store', $this->asrama),
            [
                'nomor_kamar' => '102',
                'kapasitas' => 4,
            ]
        );

        $response->assertRedirect(route('panitia.asrama.show', $this->asrama));
        $this->assertDatabaseHas('kamars', [
            'asrama_id' => $this->asrama->id,
            'nomor_kamar' => '102',
            'kapasitas' => 4,
        ]);
    }

    public function test_duplicate_room_number_rejected(): void
    {
        $response = $this->actingAs($this->panitia)->post(
            route('panitia.kamar.store', $this->asrama),
            [
                'nomor_kamar' => '101',
                'kapasitas' => 4,
            ]
        );

        $response->assertSessionHasErrors('nomor_kamar');
    }

    public function test_room_capacity_min_validation(): void
    {
        $response = $this->actingAs($this->panitia)->post(
            route('panitia.kamar.store', $this->asrama),
            [
                'nomor_kamar' => '102',
                'kapasitas' => 0,
            ]
        );

        $response->assertSessionHasErrors('kapasitas');
    }

    public function test_room_capacity_max_validation(): void
    {
        $response = $this->actingAs($this->panitia)->post(
            route('panitia.kamar.store', $this->asrama),
            [
                'nomor_kamar' => '102',
                'kapasitas' => 11,
            ]
        );

        $response->assertSessionHasErrors('kapasitas');
    }

    /* === UPDATE ROOM CAPACITY TESTS === */

    public function test_can_update_room_capacity(): void
    {
        $response = $this->actingAs($this->panitia)->patch(
            route('panitia.kamar.update', $this->kamar),
            ['kapasitas' => 5]
        );

        $response->assertRedirect();
        $this->assertEquals(5, $this->kamar->fresh()->kapasitas);
    }

    public function test_capacity_below_current_occupants_rejected(): void
    {
        $pelatihan = Pelatihan::factory()->create();
        for ($i = 0; $i < 2; $i++) {
            $user = User::factory()->create(['role' => 'peserta']);
            Pendaftaran::create([
                'peserta_id' => $user->id,
                'pelatihan_id' => $pelatihan->id,
                'kamar_id' => $this->kamar->id,
                'check_in' => now()->toDateString(),
                'check_out' => now()->addDays(5)->toDateString(),
                'status_verifikasi' => 'diverifikasi',
            ]);
        }

        $response = $this->actingAs($this->panitia)->patch(
            route('panitia.kamar.update', $this->kamar),
            ['kapasitas' => 1]
        );

        $response->assertSessionHasErrors('kapasitas');
    }

    /* === MOVE RESIDENT TESTS === */

    public function test_can_move_resident_to_available_room(): void
    {
        $kamarTujuan = Kamar::factory()->create([
            'asrama_id' => $this->asrama->id,
            'nomor_kamar' => '102',
            'kapasitas' => 3,
        ]);

        $pelatihan = Pelatihan::factory()->create();
        $user = User::factory()->create(['role' => 'peserta']);
        $pendaftaran = Pendaftaran::create([
            'peserta_id' => $user->id,
            'pelatihan_id' => $pelatihan->id,
            'kamar_id' => $this->kamar->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
            'status_verifikasi' => 'diverifikasi',
            'butuh_asrama' => true,
        ]);

        $response = $this->actingAs($this->panitia)->patch(
            route('panitia.pendaftaran.pindah-kamar', $pendaftaran),
            ['kamar_id' => $kamarTujuan->id]
        );

        $response->assertRedirect();
        $this->assertEquals($kamarTujuan->id, $pendaftaran->fresh()->kamar_id);
    }

    public function test_cannot_move_to_full_room(): void
    {
        $kamarTujuan = Kamar::factory()->create([
            'asrama_id' => $this->asrama->id,
            'nomor_kamar' => '102',
            'kapasitas' => 1,
        ]);

        $pelatihan = Pelatihan::factory()->create();

        // Fill the destination room
        for ($i = 0; $i < 1; $i++) {
            $user = User::factory()->create(['role' => 'peserta']);
            Pendaftaran::create([
                'peserta_id' => $user->id,
                'pelatihan_id' => $pelatihan->id,
                'kamar_id' => $kamarTujuan->id,
                'check_in' => now()->toDateString(),
                'check_out' => now()->addDays(5)->toDateString(),
                'status_verifikasi' => 'diverifikasi',
                'butuh_asrama' => true,
            ]);
        }

        // Try to move a resident
        $user = User::factory()->create(['role' => 'peserta']);
        $pendaftaran = Pendaftaran::create([
            'peserta_id' => $user->id,
            'pelatihan_id' => $pelatihan->id,
            'kamar_id' => $this->kamar->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
            'status_verifikasi' => 'diverifikasi',
            'butuh_asrama' => true,
        ]);

        $response = $this->actingAs($this->panitia)->patch(
            route('panitia.pendaftaran.pindah-kamar', $pendaftaran),
            ['kamar_id' => $kamarTujuan->id]
        );

        $response->assertSessionHasErrors('kamar_id');
    }

    /* === SHOW VIEW TESTS === */

    public function test_show_displays_empty_state_when_no_rooms(): void
    {
        $asramaEmpty = Asrama::factory()->create(['nama' => 'Asrama Empty']);

        $response = $this->actingAs($this->panitia)->get(route('panitia.asrama.show', $asramaEmpty));
        $response->assertSee('Belum ada kamar');
    }

    public function test_show_displays_empty_state_when_no_occupants(): void
    {
        $response = $this->actingAs($this->panitia)->get(route('panitia.asrama.show', $this->asrama));
        $response->assertSee('Belum ada penghuni');
    }

    public function test_show_displays_room_occupants(): void
    {
        $pelatihan = Pelatihan::factory()->create(['nama' => 'Pelatihan A']);
        $user = User::factory()->create(['role' => 'peserta', 'name' => 'John Doe']);
        Pendaftaran::create([
            'peserta_id' => $user->id,
            'pelatihan_id' => $pelatihan->id,
            'kamar_id' => $this->kamar->id,
            'check_in' => '2024-11-08',
            'check_out' => '2024-11-13',
            'status_verifikasi' => 'diverifikasi',
        ]);

        $response = $this->actingAs($this->panitia)->get(route('panitia.asrama.show', $this->asrama));
        $response->assertSee('John Doe');
        $response->assertSee('Pelatihan A');
        $response->assertSee('08/11/2024');
    }

    public function test_show_displays_room_status_full(): void
    {
        $pelatihan = Pelatihan::factory()->create();

        // Fill the room to capacity
        for ($i = 0; $i < 3; $i++) {
            $user = User::factory()->create(['role' => 'peserta']);
            Pendaftaran::create([
                'peserta_id' => $user->id,
                'pelatihan_id' => $pelatihan->id,
                'kamar_id' => $this->kamar->id,
                'check_in' => now()->toDateString(),
                'check_out' => now()->addDays(5)->toDateString(),
                'status_verifikasi' => 'diverifikasi',
            ]);
        }

        $response = $this->actingAs($this->panitia)->get(route('panitia.asrama.show', $this->asrama));
        $response->assertSee('Penuh');
    }
}
