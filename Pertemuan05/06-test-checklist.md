# Checklist Pengujian End-to-End — Pertemuan 5

BAPELKES ONE · Praktik Aplikasi Web INF60295 · Universitas Negeri Yogyakarta

Pengujian dilakukan nyata lewat browser (Playwright, desktop 1280px & mobile 390px) terhadap aplikasi
yang dijalankan lokal (`php artisan serve`) dengan database SQLite yang di-*fresh*-migrate dan di-seed
ulang, memakai user peserta sungguhan (bukan asumsi/simulasi). Bukti tangkapan layar ada di
`07-screenshots.pdf`.

| No. | Skenario | Bukti yang Dicek | Hasil |
|---|---|---|---|
| 1 | Katalog pelatihan tampil dari seeder (bukan array statis) | Data browser (`/pelatihan`) = data tabel `pelatihans`, judul "Pelatihan Dasar K3 Rumah Sakit" dkk. muncul tepat setelah `migrate:fresh --seed` tanpa input manual | Sudah |
| 2 | Tambah pengajuan (daftar pelatihan) | Record baru di tabel `pendaftarans` dengan `status_verifikasi = pending` | Sudah |
| 3 | Edit pengajuan (data diri) | Nilai `data_diri` berubah di database setelah submit form edit | Sudah |
| 4 | Edit pengajuan yang sudah diverifikasi ditolak | Request GET/PUT ke `pendaftarans.edit`/`updateSelf` pada pengajuan `diverifikasi` dikembalikan dengan pesan error, data tidak berubah | Sudah |
| 5 | Hapus pengajuan (Batalkan) | Record hilang dari tabel `pendaftarans`, redirect tanpa error | Sudah |
| 6 | Kirim pengajuan dengan asrama & kamar tersedia | Record `pendaftarans` terbentuk dengan `kamar_id` terisi, pesan sukses menyebut nomor kamar | Sudah |
| 7 | Jadwal bentrok (semua kamar penuh untuk tanggal yang dipilih) | Pengajuan tetap tersimpan (`kamar_id = null`), pesan "asrama sudah penuh untuk tanggal tersebut" tampil jelas di halaman status | Sudah |
| 8 | Edit tanggal asrama ke rentang yang kosong | Kamar lama dilepas, kamar baru dialokasikan, `check_in`/`check_out` berubah di database | Sudah |
| 9 | Peserta tidak bisa mengedit/menghapus pengajuan milik peserta lain | Request dari peserta B ke pengajuan peserta A dikembalikan 403 | Sudah |
| 10 | Responsif mobile (390px) | Form edit, tombol "Ubah"/"Batalkan", dan pesan sukses/error tidak meluber di layar 390px | Sudah |

## Keterbatasan yang diketahui (belum diperbaiki)

- Console browser menampilkan error CORS untuk font `fonts.bunny.net` di semua halaman (bukan hanya
  halaman baru Pertemuan 5) — ini keterbatasan jaringan lingkungan pengujian (font eksternal diblokir),
  bukan bug aplikasi; tidak memengaruhi fungsi apa pun. Dicatat sebagai pekerjaan rumah di luar cakupan
  Pertemuan 5.
- 3 test PHPUnit yang sudah gagal sejak sebelum Pertemuan 5 (`PelatihanKatalogTest`, `SertifikatTest`
  x2) tetap gagal dengan cara yang sama — dikonfirmasi bukan regresi dari pekerjaan Pertemuan 5 (hasil
  sama persis di branch `main` sebelum perubahan apa pun ditambahkan). Tidak diperbaiki karena di luar
  cakupan kerja Pertemuan 5 (fokus: database, model, migration, seeder, dan CRUD).

## Bug ditemukan & diperbaiki selama pengujian

Saat menguji form edit secara nyata di browser, toggle JavaScript untuk menampilkan/menyembunyikan
field tanggal asrama (`asrama-fields`) tidak pernah berjalan — field selalu tersembunyi walau checkbox
"Saya butuh menginap" dicentang. Diselidiki dan ditemukan akar masalahnya: layout utama
(`resources/views/layouts/app.blade.php`) tidak memiliki `@stack('scripts')`, sehingga blok
`@push('scripts')` pada `create.blade.php` (dan `edit.blade.php` yang baru dibuat) tidak pernah
dirender ke halaman — bug ini sudah ada sejak Pertemuan 4, sebelum Pertemuan 5. **Diperbaiki** dengan
menambahkan `@stack('scripts')` sebelum `</body>` di `layouts/app.blade.php`. Dikonfirmasi lewat
pengujian ulang: toggle asrama berfungsi di kedua form (create & edit) setelah perbaikan.
