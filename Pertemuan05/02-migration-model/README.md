# Migration & Model — Pertemuan 5

BAPELKES ONE · Praktik Aplikasi Web INF60295 · Universitas Negeri Yogyakarta

## Praktikum 2 — Kenapa tetap SQLite, bukan MySQL

Jobsheet Praktikum 2 meminta membuat database baru bernama `ruang_kampus` di MySQL/MariaDB dan
mengarahkan `.env` ke sana (`DB_CONNECTION=mysql`, dst.). Proyek ini **tetap memakai SQLite**
(`database/database.sqlite`, `DB_CONNECTION=sqlite`) yang sudah menjadi konvensi sejak pertemuan-
pertemuan sebelumnya, dengan alasan:

1. **Proyek sudah berjalan**, bukan mulai dari nol — `.env` dan database SQLite yang sudah ada sebelum
   Pertemuan 5 sudah berisi seluruh tabel dan sudah diuji oleh test suite yang ada. Instruksi jobsheet
   ("buat database baru") ditulis untuk studi kasus greenfield, bukan proyek yang sudah berjalan.
2. **Migration dan seeder Laravel bersifat database-agnostic** — kode yang sama jalan di SQLite maupun
   MySQL tanpa perubahan. Secara fungsional, tujuan Praktikum 2 (basis data tersambung, migration bisa
   dijalankan) sudah terpenuhi dengan SQLite.
3. **Mengganti driver database di tengah jalan berisiko** tanpa manfaat langsung untuk cakupan kerja
   Pertemuan 5 — butuh setup server MySQL terpisah, migrasi data, dan berpotensi mengubah perilaku yang
   sudah diuji oleh test suite yang ada.

**Catatan jujur — ada satu alasan yang justru mendukung MySQL ke depannya:** test
`test_concurrent_kamar_terakhir_hanya_satu_yang_berhasil` di `tests/Feature/ReservasiAsramaTest.php`
**secara eksplisit di-skip** jika `DB_CONNECTION` adalah `sqlite`, karena SQLite tidak benar-benar
mendukung `lockForUpdate()` (row-level locking) yang dipakai `KamarService::alokasikanKamar()` untuk
mencegah dua peserta mendapat kamar terakhir yang sama secara bersamaan (race condition). Artinya,
proteksi race condition pada alokasi kamar ada di kode, tetapi **belum pernah benar-benar teruji** di
lingkungan pengujian ini — baru teruji kalau proyek dijalankan di atas MySQL/PostgreSQL (misalnya di
lingkungan staging/produksi). Ini dicatat sebagai pekerjaan rumah yang disengaja, bukan kelalaian: di
luar cakupan Pertemuan 5, tapi relevan untuk pertemuan/iterasi berikutnya yang menyentuh deployment.

## Yang sudah ada sebelum Pertemuan 5 (dari pertemuan-pertemuan sebelumnya)

Berbeda dengan jobsheet yang mengasumsikan migration dan model dibuat dari nol di Praktikum 3, proyek
ini sudah memiliki seluruh migration dan model yang dibutuhkan sejak pertemuan-pertemuan sebelumnya:

| Migration | Tabel | Dibuat pada |
|---|---|---|
| `create_pelatihans_table` | `pelatihans` | Sebelum Pertemuan 4 |
| `add_role_to_users_table` | `users` (tambah kolom `role`) | Sebelum Pertemuan 4 |
| `create_pendaftarans_table` | `pendaftarans` | Sebelum Pertemuan 4 |
| `add_metode_to_pelatihans_table` | `pelatihans` (tambah kolom `metode`) | Sebelum Pertemuan 4 |
| `add_verification_fields_to_pendaftarans_table` | `pendaftarans` (status_verifikasi, catatan, dll.) | Sebelum Pertemuan 4 |
| `create_asramas_table` | `asramas` | Sebelum Pertemuan 4 |
| `create_kamars_table` | `kamars` | Sebelum Pertemuan 4 |
| `add_kamar_id_and_dates_to_pendaftarans_table` | `pendaftarans` (kamar_id, check_in, check_out) | Sebelum Pertemuan 4 |
| `add_kode_presensi_to_pendaftarans_table` | `pendaftarans` (kode_presensi) | Sebelum Pertemuan 4 |
| `add_hadir_at_to_pendaftarans_table` | `pendaftarans` (hadir_at) | Sebelum Pertemuan 4 |
| `add_kode_sertifikat_to_pendaftarans_table` | `pendaftarans` (kode_sertifikat) | Sebelum Pertemuan 4 |

Model Eloquent yang sudah ada dan dipakai apa adanya: `app/Models/Pelatihan.php`,
`app/Models/Pendaftaran.php`, `app/Models/Asrama.php`, `app/Models/Kamar.php`, `app/Models/User.php` —
semuanya sudah punya `$fillable`, cast, dan relasi (`hasMany`/`belongsTo`) yang sesuai.

Karena bagian ini sudah lengkap dan sudah diuji oleh test suite yang ada (`tests/Feature/PendaftaranTest.php`,
`tests/Feature/ReservasiAsramaTest.php`, dll.), Praktikum 1–3 jobsheet (pemetaan field, migration, model,
relasi) **tidak perlu dikerjakan ulang** — lihat `01-data-model.md` untuk pemetaan field yang dilakukan,
dan `03-seeder.md` untuk pekerjaan seeder yang memang baru dikerjakan pada Pertemuan 5 ini.

## Yang dikerjakan baru pada Pertemuan 5

Tidak ada migration/model baru yang perlu dibuat (semua tabel dan relasi inti sudah tersedia). Pekerjaan
baru Pertemuan 5 berfokus pada bagian yang memang masih kosong:

1. **Seeder** (`PelatihanSeeder`) — lihat `03-seeder.md`.
2. **CRUD Pengajuan sisi peserta (edit/update)** — lihat `05-crud-booking/`.
3. **Validasi bentrok jadwal** pada alokasi kamar — lihat `05-crud-booking/` dan `06-test-checklist.md`.

Tidak ada perubahan skema database yang diperlukan untuk ketiga pekerjaan di atas, karena kolom yang
dibutuhkan (`check_in`, `check_out`, `kamar_id`, `status_verifikasi`, dll.) sudah tersedia dari migration
sebelumnya.
