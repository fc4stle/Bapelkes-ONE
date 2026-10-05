# Pertemuan 5 — Basis Data, Model Data, Migration, Seeder, dan CRUD

**Mata Kuliah:** Praktik Aplikasi Web (INF60295)
**Program Studi:** Teknologi Informasi S1, Fakultas Teknik, Universitas Negeri Yogyakarta
**Dosen Pengampu:** Zaenal Mustofa, M.Kom.
**Proyek:** BAPELKES ONE — portal terpadu pendaftaran pelatihan Bapelkes DIY
**Branch:** `feature/database-crud`

## Anggota Kelompok

| Nama | NIM |
|---|---|
| Alysa Salsabila Irfan Putri | 24051130049 |
| Marshall Raihan Sahirman | 24051130054 |

## Daftar Berkas

| Berkas | Isi |
|---|---|
| `01-data-model.md` | Praktikum 1 — pemetaan field antarmuka Pertemuan 4 ke kolom database, termasuk penjelasan pemetaan studi kasus jobsheet (Room/Booking) ke entitas nyata proyek (Pelatihan/Pendaftaran/Kamar/Asrama) |
| `02-migration-model/README.md` | Praktikum 2–3 — keputusan tetap pakai SQLite (bukan MySQL seperti jobsheet), daftar migration & model yang sudah ada dari pertemuan sebelumnya |
| `03-seeder.md` | Praktikum 3 — `PelatihanSeeder` (dirintis Marshall, dilengkapi Alysa) + pendaftaran `AsramaKamarSeeder` yang sebelumnya tidak terdaftar |
| `04-crud-room/README.md` | Praktikum 4 — pemetaan CRUD "Room" ke CRUD Pelatihan (sudah ada) |
| `05-crud-booking/README.md` | Praktikum 5–6 — CRUD Pendaftaran sisi peserta (edit/update, fitur baru) dan validasi bentrok jadwal asrama |
| `06-test-checklist.md` | Praktikum 7 — checklist uji end-to-end 10 poin, bug ditemukan & diperbaiki |
| `06b-bukti-database-crud.md` | Bukti tabel database sebelum/sesudah operasi CRUD lewat `tinker` (poin "I. Hasil Akhir") |
| `07-screenshots.pdf` | Bukti uji tampilan desktop (1280px) dan mobile (390px) untuk tiap langkah alur |
| `README.md` | Berkas ini |

## Studi Kasus

Melanjutkan Pertemuan 4: antarmuka yang sebelumnya memakai data contoh (seeder belum berjalan)
dihubungkan sepenuhnya ke database. Katalog pelatihan kini menampilkan data nyata dari `PelatihanSeeder`,
dan form pendaftaran benar-benar menyimpan record — bukan simulasi. Fitur baru: peserta dapat mengubah
(edit) pengajuan pendaftaran miliknya sendiri selama belum diverifikasi, dan validasi bentrok jadwal
asrama (yang sebelumnya hanya berjalan saat mendaftar pertama kali) kini juga berlaku konsisten saat
mengubah tanggal menginap.

## Pembagian Kerja

Marshall merintis `PelatihanSeeder` awal di branch `feature/database-crud`. Pekerjaan selebihnya
(melengkapi seeder, analisis gap terhadap jobsheet, CRUD edit pengajuan, validasi bentrok, pengujian, dan
dokumentasi) dikerjakan oleh Alysa Salsabila Irfan Putri di atas rintisan tersebut, melanjutkan
implementasi Pelatihan/Pendaftaran/Kamar/Asrama yang sudah dibangun kelompok pada pertemuan-pertemuan
sebelumnya.

| Anggota | Bagian Pekerjaan |
|---|---|
| Marshall Raihan Sahirman | Merintis `PelatihanSeeder` awal (3 data pelatihan) dan mendaftarkannya di `DatabaseSeeder` |
| Alysa Salsabila Irfan Putri | Analisis kesenjangan antara jobsheet dan kondisi nyata proyek, melengkapi `PelatihanSeeder` (6 data, seluruh kombinasi status/metode) dan menambahkan pendaftaran `AsramaKamarSeeder` yang sebelumnya terlewat, fitur edit/update pengajuan sisi peserta, validasi bentrok jadwal pada alur edit, perbaikan bug `@stack('scripts')` yang ditemukan saat pengujian, pengujian end-to-end desktop & mobile, dan seluruh dokumentasi `Pertemuan05/` |

Tautan commit: lihat riwayat commit branch `feature/database-crud` (commit `5ce97b5` adalah rintisan
Marshall; commit-commit setelahnya adalah lanjutan Alysa).

## Refleksi Individu — Alysa Salsabila Irfan Putri

**Apa perbedaan data statis pada Pertemuan 4 dengan data dinamis pada Pertemuan 5?**
Di Pertemuan 4, halaman katalog dan form pendaftaran sebenarnya sudah terhubung ke model Eloquent
(bukan array PHP statis), tapi database-nya kosong karena `AsramaKamarSeeder` yang sudah ada sejak
sebelum Pertemuan 4 ternyata tidak pernah didaftarkan di `DatabaseSeeder`, dan belum ada seeder untuk
`Pelatihan` sama sekali — jadi tampilannya terasa seperti data contoh yang "menunggu diisi", bukan
benar-benar ditampilkan dari database. Di Pertemuan 5, setelah `PelatihanSeeder` dibuat dan kedua seeder
didaftarkan, satu perintah `php artisan migrate:fresh --seed` langsung mengisi 6 pelatihan, 3 asrama, dan
50 kamar — katalog dan seluruh alur jadi benar-benar reproducible dari database, bukan tergantung pada
siapa yang kebetulan pernah mendaftar manual lewat form sebelumnya.

**Mengapa migration lebih baik daripada membuat tabel manual tanpa dokumentasi?**
Karena proyek ini dikerjakan berkelompok dan diuji ulang oleh anggota lain (dan oleh saya sendiri di
lingkungan terpisah), migration memastikan struktur tabel yang sama persis bisa dibuat ulang kapan saja
dengan `php artisan migrate:fresh` — tanpa migration, setiap anggota harus membuat tabel manual lewat
phpMyAdmin/GUI yang gampang beda urutan kolom, tipe data, atau lupa foreign key, dan tidak ada riwayat
perubahan skema yang bisa di-review lewat Git. Terlihat jelas manfaatnya saat saya perlu menjalankan
ulang database beberapa kali untuk pengujian (migrate:fresh --seed, isi 50 kamar untuk skenario bentrok,
reset lagi) — tanpa migration + seeder, proses ini harus diulang manual setiap kali.

**Bagaimana relasi Room dan Booking diterapkan pada aplikasi?**
Karena studi kasus proyek bukan peminjaman ruang generik, relasi ini dipetakan ke dua pasang relasi nyata:
`Pelatihan` (1) — (N) `Pendaftaran` (satu pelatihan bisa punya banyak pengajuan), dan `Kamar` (1) — (N)
`Pendaftaran` (satu kamar bisa dipakai banyak pendaftaran pada rentang tanggal berbeda, selama kapasitas
dan jadwalnya tidak bentrok). Relasi kedua inilah yang paling mirip dengan "Room—Booking" pada jobsheet:
`Kamar::tersediaUntukTanggal()` mengecek tumpang-tindih rentang `check_in`/`check_out` sebelum sebuah
pendaftaran diberi `kamar_id`, persis seperti pengecekan `start_time`/`end_time` pada contoh jobsheet,
hanya granularitasnya per hari (menginap), bukan per jam (meminjam ruang). Penjelasan lengkap pemetaan
ada di `01-data-model.md`.

**Validasi apa yang paling penting untuk mencegah data tidak konsisten?**
Validasi kepemilikan (peserta hanya boleh mengubah/menghapus pengajuan miliknya sendiri) dan validasi
status (pengajuan yang sudah `diverifikasi`/`ditolak` tidak boleh diubah lagi oleh peserta) — tanpa dua
ini, peserta lain bisa saja mengubah data pengajuan orang lain, atau mengubah pengajuan yang sudah
diproses panitia sehingga catatan verifikasi jadi tidak sinkron dengan data aslinya. Validasi bentrok
jadwal pada kamar juga penting, tapi konsekuensinya sengaja dibuat tidak sekeras validasi kepemilikan:
alih-alih menolak seluruh pengajuan, sistem tetap menyimpan pendaftaran tanpa kamar dan memberi pesan
jelas — karena kegagalan dapat kamar tidak seharusnya menggagalkan pendaftaran pelatihannya.

**Masalah apa yang ditemukan saat menghubungkan UI dengan database dan bagaimana perbaikannya?**
Dua masalah nyata ditemukan lewat pengujian browser sungguhan (bukan dugaan): (1) `AsramaKamarSeeder`
yang sudah ada tidak pernah dipanggil dari `DatabaseSeeder`, sehingga data asrama/kamar selalu kosong di
database baru — diperbaiki dengan mendaftarkannya bersama `PelatihanSeeder` baru; (2) toggle JavaScript
untuk menampilkan field tanggal asrama di form pendaftaran/edit tidak pernah berjalan, karena layout
utama (`layouts/app.blade.php`) tidak punya `@stack('scripts')` sehingga blok `@push('scripts')` di form
tidak pernah dirender — diperbaiki dengan menambahkan `@stack('scripts')` sebelum `</body>`. Keduanya
baru ketahuan karena pengujian dilakukan benar-benar lewat browser dan database, bukan hanya membaca
kode. Detail ada di `06-test-checklist.md`.

**Apa kontribusi Anda pada kode dan apa bukti commit-nya?**
Membuat `PelatihanSeeder` dan memperbaiki `DatabaseSeeder`; menambahkan route, method controller
(`edit`, `updateSelf`), view (`pendaftarans/edit.blade.php`), dan tombol "Ubah" untuk CRUD pengajuan sisi
peserta yang sebelumnya belum ada; memastikan validasi bentrok jadwal berlaku konsisten di jalur edit;
memperbaiki bug `@stack('scripts')`; menulis test otomatis baru (`tests/Feature/PendaftaranEditTest.php`,
7 skenario); dan menyusun seluruh dokumentasi `Pertemuan05/`. Bukti commit ada di riwayat branch
`feature/database-crud`.

## Status

- [x] Pemetaan antarmuka ke model data
- [x] Migration & model (sudah ada, didokumentasikan ulang)
- [x] Seeder (PelatihanSeeder baru + perbaikan AsramaKamarSeeder)
- [x] CRUD Pelatihan ("Room") — sudah ada, didokumentasikan ulang
- [x] CRUD Pendaftaran ("Booking") — fitur edit/update baru ditambahkan
- [x] Validasi bentrok jadwal asrama pada jalur edit
- [x] Pengujian end-to-end desktop & mobile + perbaikan bug yang ditemukan
- [x] Bukti uji (screenshot)
- [x] Checklist uji & refleksi individu
- [x] Pull Request direview dan digabung ke `main` ([PR #3](../../pull/3))
