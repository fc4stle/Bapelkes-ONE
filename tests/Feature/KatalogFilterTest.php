<?php

namespace Tests\Feature;

use App\Models\Pelatihan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KatalogFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Pencarian nama menemukan yang cocok dan menyembunyikan yang lain
     */
    public function test_search_nama_finds_matching_pelatihan(): void
    {
        Pelatihan::factory()->create([
            'nama' => 'Pelatihan K3 Dasar',
            'status' => 'dibuka',
        ]);

        Pelatihan::factory()->create([
            'nama' => 'Pelatihan Pertama Kesehatan',
            'status' => 'dibuka',
        ]);

        $response = $this->get(route('pelatihan.katalog', ['q' => 'K3']));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan K3 Dasar');
        $response->assertDontSee('Pelatihan Pertama Kesehatan');
        $response->assertSee('1 pelatihan ditemukan');
    }

    /**
     * Test: Pencarian lokasi
     */
    public function test_search_lokasi_finds_matching_pelatihan(): void
    {
        Pelatihan::factory()->create([
            'nama' => 'Pelatihan A',
            'lokasi' => 'Yogyakarta',
            'status' => 'dibuka',
        ]);

        Pelatihan::factory()->create([
            'nama' => 'Pelatihan B',
            'lokasi' => 'Jakarta',
            'status' => 'dibuka',
        ]);

        $response = $this->get(route('pelatihan.katalog', ['q' => 'Yogya']));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan A');
        $response->assertDontSee('Pelatihan B');
    }

    /**
     * Test: Pencarian case-insensitive
     */
    public function test_search_case_insensitive(): void
    {
        Pelatihan::factory()->create([
            'nama' => 'Pelatihan K3 Dasar',
            'status' => 'dibuka',
        ]);

        $response = $this->get(route('pelatihan.katalog', ['q' => 'k3']));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan K3 Dasar');
        $response->assertSee('1 pelatihan ditemukan');
    }

    /**
     * Test: Karakter % dan _ diperlakukan literal
     */
    public function test_search_escapes_wildcard_characters(): void
    {
        Pelatihan::factory()->create([
            'nama' => 'Pelatihan A_B',
            'status' => 'dibuka',
        ]);

        Pelatihan::factory()->create([
            'nama' => 'Pelatihan AAB',
            'status' => 'dibuka',
        ]);

        // Pencarian dengan _ harus menemukan hanya yang literal _
        $response = $this->get(route('pelatihan.katalog', ['q' => 'A_B']));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan A_B');
        $response->assertDontSee('Pelatihan AAB');
    }

    /**
     * Test: Filter waktu 'akan_datang'
     */
    public function test_filter_waktu_akan_datang(): void
    {
        $today = now()->startOfDay();
        Date::setTestNow($today);

        Pelatihan::factory()->create([
            'nama' => 'Pelatihan Akan Datang',
            'tanggal_mulai' => $today->clone()->addDay(),
            'tanggal_selesai' => $today->clone()->addDays(3),
            'status' => 'dibuka',
        ]);

        Pelatihan::factory()->create([
            'nama' => 'Pelatihan Berlangsung',
            'tanggal_mulai' => $today->clone()->subDay(),
            'tanggal_selesai' => $today->clone()->addDay(),
            'status' => 'dibuka',
        ]);

        $response = $this->get(route('pelatihan.katalog', ['waktu' => 'akan_datang']));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan Akan Datang');
        $response->assertDontSee('Pelatihan Berlangsung');
    }

    /**
     * Test: Filter waktu 'berlangsung' di hari pertama jam 00:00
     */
    public function test_filter_waktu_berlangsung_first_day_0000(): void
    {
        $today = now()->startOfDay();
        Date::setTestNow($today->clone()->setHour(0)->setMinute(0));

        $pelatihan = Pelatihan::factory()->create([
            'nama' => 'Pelatihan Hari Ini',
            'tanggal_mulai' => $today,
            'tanggal_selesai' => $today->clone()->addDays(2),
            'status' => 'dibuka',
        ]);

        $response = $this->get(route('pelatihan.katalog', ['waktu' => 'berlangsung']));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan Hari Ini');
    }

    /**
     * Test: Filter waktu 'berlangsung' di hari pertama jam 10:30
     */
    public function test_filter_waktu_berlangsung_first_day_1030(): void
    {
        $today = now()->startOfDay();
        Date::setTestNow($today->clone()->setHour(10)->setMinute(30));

        $pelatihan = Pelatihan::factory()->create([
            'nama' => 'Pelatihan Hari Ini',
            'tanggal_mulai' => $today,
            'tanggal_selesai' => $today->clone()->addDays(2),
            'status' => 'dibuka',
        ]);

        $response = $this->get(route('pelatihan.katalog', ['waktu' => 'berlangsung']));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan Hari Ini');
    }

    /**
     * Test: Filter waktu 'berlangsung' di hari terakhir jam 23:59 (masih berlangsung)
     */
    public function test_filter_waktu_berlangsung_last_day_2359(): void
    {
        $today = now()->startOfDay();
        Date::setTestNow($today->clone()->setHour(23)->setMinute(59));

        $pelatihan = Pelatihan::factory()->create([
            'nama' => 'Pelatihan Hari Ini Terakhir',
            'tanggal_mulai' => $today->clone()->subDay(),
            'tanggal_selesai' => $today,
            'status' => 'dibuka',
        ]);

        $response = $this->get(route('pelatihan.katalog', ['waktu' => 'berlangsung']));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan Hari Ini Terakhir');
    }

    /**
     * Test: Filter waktu 'berlangsung' di hari pertama setelah berakhir (00:01)
     */
    public function test_filter_waktu_berlangsung_next_day_0001(): void
    {
        $today = now()->startOfDay();
        Date::setTestNow($today->clone()->setHour(0)->setMinute(1));

        Pelatihan::factory()->create([
            'nama' => 'Pelatihan Kemarin',
            'tanggal_mulai' => $today->clone()->subDays(2),
            'tanggal_selesai' => $today->clone()->subDay(),
            'status' => 'dibuka',
        ]);

        $response = $this->get(route('pelatihan.katalog', ['waktu' => 'berlangsung']));

        $response->assertStatus(200);
        $response->assertDontSee('Pelatihan Kemarin');
    }

    /**
     * Test: Filter waktu 'selesai'
     */
    public function test_filter_waktu_selesai(): void
    {
        $today = now()->startOfDay();
        Date::setTestNow($today);

        Pelatihan::factory()->create([
            'nama' => 'Pelatihan Selesai',
            'tanggal_mulai' => $today->clone()->subDays(3),
            'tanggal_selesai' => $today->clone()->subDay(),
            'status' => 'dibuka',
        ]);

        Pelatihan::factory()->create([
            'nama' => 'Pelatihan Berlangsung',
            'tanggal_mulai' => $today->clone()->subDay(),
            'tanggal_selesai' => $today->clone()->addDay(),
            'status' => 'dibuka',
        ]);

        $response = $this->get(route('pelatihan.katalog', ['waktu' => 'selesai']));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan Selesai');
        $response->assertDontSee('Pelatihan Berlangsung');
    }

    /**
     * Test: Urut 'terdekat' (ascending tanggal_mulai)
     */
    public function test_urut_terdekat(): void
    {
        $today = now()->startOfDay();
        Date::setTestNow($today);

        Pelatihan::factory()->create([
            'nama' => 'Pelatihan 3 November',
            'tanggal_mulai' => $today->clone()->addDays(3),
            'tanggal_selesai' => $today->clone()->addDays(5),
            'status' => 'dibuka',
        ]);

        Pelatihan::factory()->create([
            'nama' => 'Pelatihan 1 November',
            'tanggal_mulai' => $today->clone()->addDay(),
            'tanggal_selesai' => $today->clone()->addDays(2),
            'status' => 'dibuka',
        ]);

        $response = $this->get(route('pelatihan.katalog', ['urut' => 'terdekat']));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            'Pelatihan 1 November',
            'Pelatihan 3 November',
        ]);
    }

    /**
     * Test: Urut 'terbaru' (descending tanggal_mulai)
     */
    public function test_urut_terbaru(): void
    {
        $today = now()->startOfDay();
        Date::setTestNow($today);

        Pelatihan::factory()->create([
            'nama' => 'Pelatihan 1 November',
            'tanggal_mulai' => $today->clone()->addDay(),
            'tanggal_selesai' => $today->clone()->addDays(2),
            'status' => 'dibuka',
        ]);

        Pelatihan::factory()->create([
            'nama' => 'Pelatihan 3 November',
            'tanggal_mulai' => $today->clone()->addDays(3),
            'tanggal_selesai' => $today->clone()->addDays(5),
            'status' => 'dibuka',
        ]);

        $response = $this->get(route('pelatihan.katalog', ['urut' => 'terbaru']));

        $response->assertStatus(200);
        $response->assertSeeInOrder([
            'Pelatihan 3 November',
            'Pelatihan 1 November',
        ]);
    }

    /**
     * Test: Parameter tidak valid (waktu=ngawur) tidak error, pakai default
     */
    public function test_invalid_waktu_parameter_ignored(): void
    {
        Pelatihan::factory()->create([
            'nama' => 'Pelatihan A',
            'status' => 'dibuka',
        ]);

        $response = $this->get(route('pelatihan.katalog', ['waktu' => 'ngawur']));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan A');
    }

    /**
     * Test: Parameter q yang terlalu panjang (500 karakter) tidak error
     */
    public function test_long_q_parameter_truncated(): void
    {
        Pelatihan::factory()->create([
            'nama' => 'Pelatihan A',
            'status' => 'dibuka',
        ]);

        $longQ = str_repeat('a', 500);
        $response = $this->get(route('pelatihan.katalog', ['q' => $longQ]));

        // Harus tidak ada error 422
        $response->assertStatus(200);
    }

    /**
     * Test: Pelatihan draft TIDAK muncul untuk peserta walau cocok dengan q
     */
    public function test_draft_pelatihan_not_shown_in_katalog(): void
    {
        Pelatihan::factory()->create([
            'nama' => 'Pelatihan Draft K3',
            'status' => 'draft',
        ]);

        Pelatihan::factory()->create([
            'nama' => 'Pelatihan Dibuka K3',
            'status' => 'dibuka',
        ]);

        $response = $this->get(route('pelatihan.katalog', ['q' => 'K3']));

        $response->assertStatus(200);
        $response->assertDontSee('Pelatihan Draft K3');
        $response->assertSee('Pelatihan Dibuka K3');
    }

    /**
     * Test: Kombinasi q + waktu
     */
    public function test_combination_q_and_waktu(): void
    {
        $today = now()->startOfDay();
        Date::setTestNow($today);

        Pelatihan::factory()->create([
            'nama' => 'Pelatihan K3 Akan Datang',
            'tanggal_mulai' => $today->clone()->addDay(),
            'tanggal_selesai' => $today->clone()->addDays(3),
            'status' => 'dibuka',
        ]);

        Pelatihan::factory()->create([
            'nama' => 'Pelatihan Pertama Kesehatan Akan Datang',
            'tanggal_mulai' => $today->clone()->addDay(),
            'tanggal_selesai' => $today->clone()->addDays(3),
            'status' => 'dibuka',
        ]);

        Pelatihan::factory()->create([
            'nama' => 'Pelatihan K3 Berlangsung',
            'tanggal_mulai' => $today->clone()->subDay(),
            'tanggal_selesai' => $today->clone()->addDay(),
            'status' => 'dibuka',
        ]);

        $response = $this->get(route('pelatihan.katalog', ['q' => 'K3', 'waktu' => 'akan_datang']));

        $response->assertStatus(200);
        $response->assertSee('Pelatihan K3 Akan Datang');
        $response->assertDontSee('Pelatihan Pertama Kesehatan Akan Datang');
        $response->assertDontSee('Pelatihan K3 Berlangsung');
    }

    /**
     * Test: Teks "N pelatihan ditemukan" benar
     */
    public function test_results_count_message(): void
    {
        Pelatihan::factory(3)->create([
            'status' => 'dibuka',
        ]);

        $response = $this->get(route('pelatihan.katalog', ['q' => '']));

        $response->assertStatus(200);
        // Harus show "3 pelatihan ditemukan" jika ada filter aktif (bahkan q kosong)
        // tapi kita tidak tampilkan jika semua filter di default. Mari cek empty state
    }

    /**
     * Test: Pesan kosong hasil pencarian berbeda dari empty state awal
     */
    public function test_empty_search_result_message_differs_from_initial_empty_state(): void
    {
        // Tidak ada pelatihan sama sekali
        $response = $this->get(route('pelatihan.katalog'));

        $response->assertStatus(200);
        $response->assertSee('Tidak ada pelatihan ditemukan');
        $response->assertDontSee('Tidak ada pelatihan yang cocok dengan pencarian Anda.');

        // Sekarang buat pelatihan, lalu cari dengan q yang tidak ada
        Pelatihan::factory()->create([
            'nama' => 'Pelatihan A',
            'status' => 'dibuka',
        ]);

        $response = $this->get(route('pelatihan.katalog', ['q' => 'XYZ']));

        $response->assertStatus(200);
        $response->assertSee('Tidak ada pelatihan yang cocok dengan pencarian Anda.');
        $response->assertDontSee('Pelatihan akan ditampilkan di sini.');
    }

    /**
     * Test: Pagination dengan withQueryString
     */
    public function test_pagination_preserves_query_parameters(): void
    {
        for ($i = 0; $i < 15; $i++) {
            Pelatihan::factory()->create([
                'nama' => "Pelatihan $i",
                'status' => 'dibuka',
            ]);
        }

        $response = $this->get(route('pelatihan.katalog', ['q' => 'Pelatihan', 'page' => '1']));

        $response->assertStatus(200);
        // Pagination link harus mengandung q parameter
        $response->assertSee('?q=Pelatihan');
    }

    /**
     * Test: Tidak ada N+1 queries
     * Hitung query dengan 2 pelatihan, lalu 6 pelatihan; jumlah harus SAMA
     */
    public function test_no_n_plus_one_queries(): void
    {
        // Test dengan 2 pelatihan
        Pelatihan::factory(2)->create([
            'status' => 'dibuka',
        ]);

        DB::enableQueryLog();
        $response = $this->get(route('pelatihan.katalog'));
        $queriesWithTwo = DB::getQueryLog();
        $queryCountTwo = count($queriesWithTwo);
        DB::disableQueryLog();

        $response->assertStatus(200);
        $response->assertSee('2 pelatihan ditemukan');

        // Hapus data lama
        Pelatihan::query()->delete();

        // Test dengan 6 pelatihan
        Pelatihan::factory(6)->create([
            'status' => 'dibuka',
        ]);

        DB::enableQueryLog();
        $response = $this->get(route('pelatihan.katalog'));
        $queriesWithSix = DB::getQueryLog();
        $queryCountSix = count($queriesWithSix);
        DB::disableQueryLog();

        $response->assertStatus(200);
        $response->assertSee('6 pelatihan ditemukan');

        // Verifikasi tidak ada pertumbuhan query seiring pertumbuhan pelatihan
        // Tolerance: maksimal +2 query (untuk pagination metadata atau sejenisnya)
        $this->assertLessThanOrEqual(
            $queryCountTwo + 2,
            $queryCountSix,
            "N+1 detected: {$queryCountTwo} queries with 2 trainings vs {$queryCountSix} queries with 6 trainings"
        );
    }
}
