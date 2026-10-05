# Bukti Tabel Database Sebelum dan Sesudah Operasi CRUD

BAPELKES ONE · Praktik Aplikasi Web INF60295 · Universitas Negeri Yogyakarta

Jobsheet poin "I. Hasil Akhir yang Wajib Ditunjukkan" meminta bukti tabel database sebelum dan sesudah
operasi CRUD. Bukti utama sudah ada dalam bentuk tangkapan layar UI yang menampilkan data hasil query
nyata (`07-screenshots.pdf`), tetapi berkas ini menambahkan bukti langsung dari database lewat
`php artisan tinker`, dijalankan nyata terhadap database hasil `migrate:fresh --seed` (bukan hasil
tempel/dugaan).

## CRUD Pelatihan ("Room")

```
$ php artisan tinker --execute '...'

=== SEBELUM ===
Jumlah Pelatihan: 6
Jumlah Pendaftaran: 0
=== SETELAH CREATE Pelatihan id=7 ===
Jumlah Pelatihan: 7
=== SETELAH UPDATE Pelatihan id=7, kuota baru: 99 ===
=== SETELAH DELETE Pelatihan ===
Jumlah Pelatihan: 6
```

- **CREATE**: jumlah baris bertambah dari 6 menjadi 7 setelah `Pelatihan::create()`.
- **UPDATE**: kolom `kuota` berubah dari nilai awal menjadi `99`, dikonfirmasi lewat `$p->fresh()->kuota`
  (membaca ulang dari database, bukan dari objek di memori).
- **DELETE**: jumlah baris kembali ke 6 setelah `$p->delete()`.

## CRUD Pendaftaran ("Booking") — termasuk fitur edit baru Pertemuan 5

```
$ php artisan tinker --execute '...'

=== SEBELUM ===
Jumlah Pendaftaran: 0
=== SETELAH CREATE Pendaftaran id=1, nama: Peserta Uji DB ===
Jumlah Pendaftaran: 1
=== SETELAH UPDATE (via fitur edit baru), nama baru: Peserta Uji DB (diubah) ===
=== SETELAH DELETE (Batalkan) ===
Jumlah Pendaftaran: 0
```

- **CREATE**: jumlah baris bertambah dari 0 menjadi 1.
- **UPDATE**: field `data_diri.nama` (JSON) berubah dari "Peserta Uji DB" menjadi "Peserta Uji DB
  (diubah)", dikonfirmasi lewat `$pendaftaran->fresh()->data_diri['nama']` — ini adalah operasi yang sama
  persis dengan yang dijalankan `PendaftaranController::updateSelf()` saat peserta mengklik "Simpan
  Perubahan" di form edit.
- **DELETE**: jumlah baris kembali ke 0 setelah `$pendaftaran->delete()` — operasi yang sama dengan
  tombol "Batalkan".

Bukti yang sama, lewat jalur HTTP sungguhan (bukan tinker) dan lewat browser, ada di
`06-test-checklist.md` (skenario 2, 3, 5) dan `07-screenshots.pdf`.
