# Migration & Model — Pertemuan 5

BAPELKES ONE · Praktik Aplikasi Web INF60295 · Universitas Negeri Yogyakarta

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
