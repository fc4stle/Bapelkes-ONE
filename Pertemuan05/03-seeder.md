# Seeder — Pertemuan 5

BAPELKES ONE · Praktik Aplikasi Web INF60295 · Universitas Negeri Yogyakarta

## Temuan

Sebelum Pertemuan 5, `database/seeders/` sudah punya `AsramaKamarSeeder.php` (3 asrama, 50 kamar) tetapi
**seeder ini tidak pernah didaftarkan** di `DatabaseSeeder.php` — artinya `php artisan db:seed` hanya
membuat 1 user percobaan (`test@example.com`) dan tidak pernah mengisi data asrama/kamar maupun data
pelatihan sama sekali. Katalog pelatihan akan selalu kosong di database baru kecuali diisi manual lewat
form.

## Pekerjaan yang dilakukan

`PelatihanSeeder` dikerjakan bertahap oleh dua anggota:

1. **Marshall Raihan Sahirman** merintis `PelatihanSeeder.php` pertama kali (3 data pelatihan, status
   "dibuka") dan mendaftarkannya di `DatabaseSeeder.php`.
2. **Alysa Salsabila Irfan Putri** melengkapi seeder tersebut menjadi 6 data pelatihan yang mencakup
   seluruh kombinasi status (`draft`, `dibuka`, `ditutup`, `selesai`) dan metode (`luring`, `daring`,
   `blended`) — supaya katalog, dashboard panitia, dan alur verifikasi bisa langsung diuji tanpa input
   manual — sekaligus menambahkan `AsramaKamarSeeder` (yang sudah ada sejak sebelum Pertemuan 5, tapi
   belum pernah didaftarkan) ke `DatabaseSeeder.php`.

```php
$this->call([
    AsramaKamarSeeder::class,
    PelatihanSeeder::class,
]);
```

## Perintah pembuatan

```bash
php artisan make:seeder PelatihanSeeder
```

## Jalankan dan periksa hasil

```bash
php artisan migrate:fresh --seed
php artisan tinker --execute 'echo App\Models\Pelatihan::count();'
# => 6
php artisan tinker --execute 'echo App\Models\Asrama::count().":".App\Models\Kamar::count();'
# => 3:50
```

Dikonfirmasi lewat pengujian end-to-end (lihat `06-test-checklist.md`): setelah `migrate:fresh --seed`,
halaman katalog pelatihan langsung menampilkan 3 pelatihan berstatus "dibuka" tanpa perlu input manual
(lihat `07-screenshots.pdf`, halaman "Katalog Pelatihan").
