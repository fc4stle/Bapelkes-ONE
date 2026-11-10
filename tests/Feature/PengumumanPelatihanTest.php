<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\Pengumuman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PengumumanPelatihanTest extends TestCase
{
    use RefreshDatabase;

    protected Pelatihan $pelatihan;

    protected User $admin;

    protected User $panitia;

    protected User $peserta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->panitia = User::factory()->create(['role' => 'panitia']);
        $this->peserta = User::factory()->create(['role' => 'peserta']);

        $this->pelatihan = Pelatihan::factory()->create(['status' => 'dibuka']);
    }

    public function test_admin_can_access_pengumuman_index_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('pelatihan.pengumuman.index', $this->pelatihan));

        $response->assertStatus(200)
            ->assertViewIs('pelatihans.pengumuman.index')
            ->assertViewHas('pelatihan', $this->pelatihan);
    }

    public function test_pengumuman_link_appears_on_pendaftar_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('pelatihan.pendaftar', $this->pelatihan));

        $response->assertStatus(200)
            ->assertSee(route('pelatihan.pengumuman.index', $this->pelatihan))
            ->assertSee('Pengumuman');
    }

    public function test_panitia_can_access_pengumuman_index_page(): void
    {
        $response = $this->actingAs($this->panitia)
            ->get(route('pelatihan.pengumuman.index', $this->pelatihan));

        $response->assertStatus(200);
    }

    public function test_peserta_gets_403_on_pengumuman_index(): void
    {
        $response = $this->actingAs($this->peserta)
            ->get(route('pelatihan.pengumuman.index', $this->pelatihan));

        $response->assertStatus(403);
    }

    public function test_guest_redirects_to_login_on_pengumuman_index(): void
    {
        $response = $this->get(route('pelatihan.pengumuman.index', $this->pelatihan));

        $response->assertRedirect(route('login'));
    }

    public function test_peserta_gets_403_on_pengumuman_store(): void
    {
        $response = $this->actingAs($this->peserta)
            ->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
                'judul' => 'Test',
                'isi' => 'Test isi',
            ]);

        $response->assertStatus(403);
    }

    public function test_guest_redirects_to_login_on_pengumuman_store(): void
    {
        $response = $this->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
            'judul' => 'Test',
            'isi' => 'Test isi',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_send_pengumuman(): void
    {
        $pendaftaran = Pendaftaran::factory()
            ->for($this->pelatihan)
            ->for($this->peserta, 'peserta')
            ->create(['status_verifikasi' => 'diverifikasi']);

        $response = $this->actingAs($this->admin)
            ->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
                'judul' => 'Pengumuman Penting',
                'isi' => 'Ini adalah pengumuman penting untuk pelatihan.',
            ]);

        $response->assertRedirect(route('pelatihan.pengumuman.index', $this->pelatihan))
            ->assertSessionHas('success', 'Pengumuman terkirim ke 1 peserta.');

        $pengumuman = Pengumuman::where('pelatihan_id', $this->pelatihan->id)
            ->where('judul', 'Pengumuman Penting')
            ->first();

        $this->assertNotNull($pengumuman);
        $this->assertEquals('Pengumuman Penting', $pengumuman->judul);
        $this->assertEquals(1, $pengumuman->jumlah_penerima);
    }

    public function test_pengumuman_creates_notification_for_each_verified_peserta(): void
    {
        $peserta1 = User::factory()->create(['role' => 'peserta']);
        $peserta2 = User::factory()->create(['role' => 'peserta']);

        Pendaftaran::factory()
            ->for($this->pelatihan)
            ->for($peserta1, 'peserta')
            ->create(['status_verifikasi' => 'diverifikasi']);

        Pendaftaran::factory()
            ->for($this->pelatihan)
            ->for($peserta2, 'peserta')
            ->create(['status_verifikasi' => 'diverifikasi']);

        $this->actingAs($this->admin)
            ->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
                'judul' => 'Pengumuman Test',
                'isi' => 'Isi pengumuman test untuk verifikasi.',
            ]);

        // Check notifications table directly
        $notifications = DB::table('notifications')
            ->where('type', 'pengumuman-pelatihan')
            ->whereIn('notifiable_id', [$peserta1->id, $peserta2->id])
            ->get();

        $this->assertCount(2, $notifications);

        // Verify notification data structure
        foreach ($notifications as $notif) {
            $data = json_decode($notif->data, true);
            $this->assertArrayHasKey('judul_pelatihan', $data);
            $this->assertArrayHasKey('pesan', $data);
            $this->assertArrayHasKey('tipe', $data);
            $this->assertEquals('pengumuman', $data['tipe']);
            $this->assertEquals($this->pelatihan->nama, $data['judul_pelatihan']);
        }
    }

    public function test_notification_contains_correct_data(): void
    {
        $pendaftaran = Pendaftaran::factory()
            ->for($this->pelatihan)
            ->for($this->peserta, 'peserta')
            ->create(['status_verifikasi' => 'diverifikasi']);

        $this->actingAs($this->admin)
            ->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
                'judul' => 'Update Penting',
                'isi' => 'Lokasi pelatihan telah berubah menjadi gedung baru.',
            ]);

        $notifications = DB::table('notifications')
            ->where('notifiable_id', $this->peserta->id)
            ->orderByDesc('created_at')
            ->first();

        $this->assertNotNull($notifications);
        $data = json_decode($notifications->data, true);

        $this->assertEquals('pengumuman', $data['tipe']);
        $this->assertEquals($this->pelatihan->nama, $data['judul_pelatihan']);
        $this->assertStringContainsString('Update Penting', $data['pesan']);
        $this->assertEquals(route('pendaftarans.index', [], false), $data['url']);
    }

    public function test_only_verified_peserta_receive_notification(): void
    {
        $verified = User::factory()->create(['role' => 'peserta']);
        $pending = User::factory()->create(['role' => 'peserta']);
        $rejected = User::factory()->create(['role' => 'peserta']);

        Pendaftaran::factory()
            ->for($this->pelatihan)
            ->for($verified, 'peserta')
            ->create(['status_verifikasi' => 'diverifikasi']);

        Pendaftaran::factory()
            ->for($this->pelatihan)
            ->for($pending, 'peserta')
            ->create(['status_verifikasi' => 'pending']);

        Pendaftaran::factory()
            ->for($this->pelatihan)
            ->for($rejected, 'peserta')
            ->create(['status_verifikasi' => 'ditolak']);

        $this->actingAs($this->admin)
            ->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
                'judul' => 'Test',
                'isi' => 'Isi test',
            ]);

        // Check only verified peserta got notification
        $verifiedNotif = DB::table('notifications')
            ->where('notifiable_id', $verified->id)
            ->where('type', 'pengumuman-pelatihan')
            ->count();

        $pendingNotif = DB::table('notifications')
            ->where('notifiable_id', $pending->id)
            ->where('type', 'pengumuman-pelatihan')
            ->count();

        $rejectedNotif = DB::table('notifications')
            ->where('notifiable_id', $rejected->id)
            ->where('type', 'pengumuman-pelatihan')
            ->count();

        $this->assertEquals(1, $verifiedNotif);
        $this->assertEquals(0, $pendingNotif);
        $this->assertEquals(0, $rejectedNotif);
    }

    public function test_peserta_with_multiple_registrations_receives_one_notification(): void
    {
        // Create second pelatihan untuk test dengan peserta yang sama
        $pelatihan2 = Pelatihan::factory()->create(['status' => 'dibuka']);

        // Peserta mendaftar ke dua pelatihan
        Pendaftaran::factory()
            ->for($this->pelatihan)
            ->for($this->peserta, 'peserta')
            ->create(['status_verifikasi' => 'diverifikasi']);

        Pendaftaran::factory()
            ->for($pelatihan2)
            ->for($this->peserta, 'peserta')
            ->create(['status_verifikasi' => 'diverifikasi']);

        // Kirim pengumuman untuk pelatihan pertama saja
        $this->actingAs($this->admin)
            ->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
                'judul' => 'Test',
                'isi' => 'Isi test',
            ]);

        // Peserta hanya menerima satu notifikasi untuk pelatihan pertama
        $notificationCount = DB::table('notifications')
            ->where('notifiable_id', $this->peserta->id)
            ->count();

        $this->assertEquals(1, $notificationCount);
    }

    public function test_validation_rejects_empty_judul(): void
    {
        Pendaftaran::factory()
            ->for($this->pelatihan)
            ->for($this->peserta, 'peserta')
            ->create(['status_verifikasi' => 'diverifikasi']);

        $response = $this->actingAs($this->admin)
            ->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
                'judul' => '',
                'isi' => 'Isi yang valid',
            ]);

        $response->assertSessionHasErrors('judul');
    }

    public function test_validation_rejects_judul_over_120_chars(): void
    {
        Pendaftaran::factory()
            ->for($this->pelatihan)
            ->for($this->peserta, 'peserta')
            ->create(['status_verifikasi' => 'diverifikasi']);

        $response = $this->actingAs($this->admin)
            ->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
                'judul' => str_repeat('a', 121),
                'isi' => 'Isi yang valid',
            ]);

        $response->assertSessionHasErrors('judul');
    }

    public function test_validation_rejects_empty_isi(): void
    {
        Pendaftaran::factory()
            ->for($this->pelatihan)
            ->for($this->peserta, 'peserta')
            ->create(['status_verifikasi' => 'diverifikasi']);

        $response = $this->actingAs($this->admin)
            ->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
                'judul' => 'Judul yang valid',
                'isi' => '',
            ]);

        $response->assertSessionHasErrors('isi');
    }

    public function test_validation_rejects_isi_over_1000_chars(): void
    {
        Pendaftaran::factory()
            ->for($this->pelatihan)
            ->for($this->peserta, 'peserta')
            ->create(['status_verifikasi' => 'diverifikasi']);

        $response = $this->actingAs($this->admin)
            ->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
                'judul' => 'Judul yang valid',
                'isi' => str_repeat('a', 1001),
            ]);

        $response->assertSessionHasErrors('isi');
    }

    public function test_no_peserta_terverifikasi_returns_error(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
                'judul' => 'Test',
                'isi' => 'Isi test',
            ]);

        $response->assertSessionHasErrors('pengumuman')
            ->assertSessionMissing('success');

        $pengumuman = Pengumuman::where('pelatihan_id', $this->pelatihan->id)->first();
        $this->assertNull($pengumuman);
    }

    public function test_duplicate_pengumuman_within_2_minutes_rejected(): void
    {
        Pendaftaran::factory()
            ->for($this->pelatihan)
            ->for($this->peserta, 'peserta')
            ->create(['status_verifikasi' => 'diverifikasi']);

        // First pengumuman
        $this->actingAs($this->admin)
            ->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
                'judul' => 'Duplicate Test',
                'isi' => 'This is a test for duplicate check.',
            ]);

        // Second pengumuman dengan judul dan isi sama dalam 2 menit
        $response = $this->actingAs($this->admin)
            ->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
                'judul' => 'Duplicate Test',
                'isi' => 'This is a test for duplicate check.',
            ]);

        $response->assertSessionHasErrors('pengumuman')
            ->assertSessionMissing('success');

        // Hanya satu pengumuman yang tersimpan
        $count = Pengumuman::where('pelatihan_id', $this->pelatihan->id)->count();
        $this->assertEquals(1, $count);
    }

    public function test_duplicate_pengumuman_after_2_minutes_accepted(): void
    {
        Pendaftaran::factory()
            ->for($this->pelatihan)
            ->for($this->peserta, 'peserta')
            ->create(['status_verifikasi' => 'diverifikasi']);

        // First pengumuman
        $this->actingAs($this->admin)
            ->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
                'judul' => 'Duplicate Test',
                'isi' => 'This is a test for duplicate check.',
            ]);

        // Travel time forward 3 minutes
        Carbon::setTestNow(now()->addMinutes(3));

        // Second pengumuman dengan judul dan isi sama setelah 3 menit
        $response = $this->actingAs($this->admin)
            ->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
                'judul' => 'Duplicate Test',
                'isi' => 'This is a test for duplicate check.',
            ]);

        $response->assertSessionHas('success', 'Pengumuman terkirim ke 1 peserta.');

        $count = Pengumuman::where('pelatihan_id', $this->pelatihan->id)->count();
        $this->assertEquals(2, $count);
    }

    public function test_html_content_is_escaped_in_history(): void
    {
        Pendaftaran::factory()
            ->for($this->pelatihan)
            ->for($this->peserta, 'peserta')
            ->create(['status_verifikasi' => 'diverifikasi']);

        Pengumuman::create([
            'pelatihan_id' => $this->pelatihan->id,
            'user_id' => $this->admin->id,
            'judul' => '<script>alert("xss")</script>',
            'isi' => '<img src=x onerror="alert(1)">',
            'jumlah_penerima' => 1,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('pelatihan.pengumuman.index', $this->pelatihan));

        $content = $response->getContent();

        // Pastikan raw script tag tidak ada (not executed)
        $this->assertStringNotContainsString('<script>', $content);

        // Pastikan HTML escaped version ada
        $this->assertStringContainsString('&lt;script&gt;', $content);
        $this->assertStringContainsString('&lt;img', $content);
        $this->assertStringContainsString('&quot;', $content);
    }

    public function test_flash_message_shows_recipient_count(): void
    {
        $peserta1 = User::factory()->create(['role' => 'peserta']);
        $peserta2 = User::factory()->create(['role' => 'peserta']);

        Pendaftaran::factory()
            ->for($this->pelatihan)
            ->for($peserta1, 'peserta')
            ->create(['status_verifikasi' => 'diverifikasi']);

        Pendaftaran::factory()
            ->for($this->pelatihan)
            ->for($peserta2, 'peserta')
            ->create(['status_verifikasi' => 'diverifikasi']);

        $response = $this->actingAs($this->admin)
            ->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
                'judul' => 'Test Broadcast',
                'isi' => 'Isi test broadcast',
            ]);

        $response->assertSessionHas('success', 'Pengumuman terkirim ke 2 peserta.')
            ->assertRedirect(route('pelatihan.pengumuman.index', $this->pelatihan));
    }

    public function test_no_n_plus_one_queries_on_pengumuman_send(): void
    {
        // Create first set with 3 peserta
        for ($i = 0; $i < 3; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            Pendaftaran::factory()
                ->for($this->pelatihan)
                ->for($peserta, 'peserta')
                ->create(['status_verifikasi' => 'diverifikasi']);
        }

        DB::enableQueryLog();
        $this->actingAs($this->admin)
            ->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
                'judul' => 'Query Test 1',
                'isi' => 'Testing query count with 3 peserta.',
            ]);
        $queryCount1 = count(DB::getQueryLog());
        DB::flushQueryLog();

        // Create second set with 10 peserta total (7 more)
        Pengumuman::query()->delete();
        Carbon::setTestNow(now()->addMinutes(5));

        for ($i = 0; $i < 7; $i++) {
            $peserta = User::factory()->create(['role' => 'peserta']);
            Pendaftaran::factory()
                ->for($this->pelatihan)
                ->for($peserta, 'peserta')
                ->create(['status_verifikasi' => 'diverifikasi']);
        }

        // Flush log AFTER setup to only count store operation
        DB::flushQueryLog();

        DB::enableQueryLog();
        $this->actingAs($this->admin)
            ->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
                'judul' => 'Query Test 2',
                'isi' => 'Testing query count with 10 peserta total.',
            ]);
        $queryCount2 = count(DB::getQueryLog());
        DB::flushQueryLog();

        // Query count must be equal (no N+1 growth)
        $this->assertEquals(
            $queryCount1,
            $queryCount2,
            "Query count increased from {$queryCount1} to {$queryCount2}: N+1 detected"
        );
    }

    public function test_peserta_can_click_notification_to_open_url_and_mark_as_read(): void
    {
        $peserta = User::factory()->create(['role' => 'peserta']);

        Pendaftaran::factory()
            ->for($this->pelatihan)
            ->for($peserta, 'peserta')
            ->create(['status_verifikasi' => 'diverifikasi']);

        // Send announcement
        $this->actingAs($this->admin)
            ->post(route('pelatihan.pengumuman.store', $this->pelatihan), [
                'judul' => 'Test Click Notification',
                'isi' => 'Isi untuk test klik notifikasi.',
            ]);

        // Get notification from table
        $notification = DB::table('notifications')
            ->where('notifiable_id', $peserta->id)
            ->where('type', 'pengumuman-pelatihan')
            ->first();

        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);

        // Click notification as peserta
        $response = $this->actingAs($peserta)
            ->get(route('notifikasi.buka', $notification->id));

        // Should redirect to the URL specified in notification data
        $data = json_decode($notification->data, true);
        $response->assertRedirect($data['url']);

        // Verify notification is marked as read
        $updatedNotif = DB::table('notifications')
            ->where('id', $notification->id)
            ->first();

        $this->assertNotNull($updatedNotif->read_at);
    }
}
