# CRUD "Booking" — Pertemuan 5

BAPELKES ONE · Praktik Aplikasi Web INF60295 · Universitas Negeri Yogyakarta

## Pemetaan

"Booking" pada jobsheet dipetakan ke **Pendaftaran** (pengajuan ikut pelatihan, termasuk kebutuhan
asrama). Sebelum Pertemuan 5, CRUD-nya **belum lengkap**: create/read/delete sudah ada, tetapi
**edit/update sisi peserta belum ada sama sekali** (jobsheet Praktikum 5 poin 33). Panitia sudah bisa
`update()` status verifikasi, tetapi itu operasi berbeda (verifikasi, bukan mengubah data pengajuan).

| Operasi jobsheet | Status sebelum Pertemuan 5 | Pekerjaan Pertemuan 5 |
|---|---|---|
| `create()`/`store()` — form pengajuan, validasi, simpan status `pending` | Sudah ada (`PendaftaranController::create`/`store`) | Tidak diubah |
| Tampilkan daftar pengajuan + nama ruang via relasi Eloquent | Sudah ada (`index()`, `$pendaftaran->pelatihan->nama`) | Tidak diubah |
| `edit()`/`update()` — ubah data pengajuan milik sendiri | **Belum ada** | **Baru**: `PendaftaranController::edit()` + `updateSelf()`, route `pendaftarans.edit` (GET) dan `pendaftarans.updateSelf` (PUT) |
| `destroy()` — hapus pengajuan | Sudah ada (`destroy()`, hanya pemilik) | Tidak diubah |
| Pesan sukses/error setiap operasi | Sudah ada untuk create/delete | **Baru** untuk update: pesan berbeda untuk berhasil-dapat-kamar, berhasil-tanpa-kamar, dan berhasil-tanpa-asrama |

## Detail implementasi baru

### Route (`routes/web.php`, grup `role:peserta`)

```php
Route::get('/pendaftarans/{pendaftaran}/edit', [PendaftaranController::class, 'edit'])->name('pendaftarans.edit');
Route::put('/pendaftarans/{pendaftaran}', [PendaftaranController::class, 'updateSelf'])->name('pendaftarans.updateSelf');
```

Route `PUT /pendaftarans/{pendaftaran}` dipakai (bukan `PATCH` yang sudah dipakai `pendaftarans.update`
milik panitia) karena nama route dan tujuan operasi berbeda: `pendaftarans.update` (PATCH, khusus
`role:panitia`) mengubah `status_verifikasi`, sedangkan `pendaftarans.updateSelf` (PUT, khusus
`role:peserta`) mengubah data pengajuan milik sendiri. Dua middleware peran yang berbeda pada method
HTTP yang berbeda mencegah peserta memanggil endpoint verifikasi panitia atau sebaliknya.

### Controller (`app/Http/Controllers/PendaftaranController.php`)

- `edit(Pendaftaran $pendaftaran)` — menolak (403) jika bukan pemilik; menolak dengan pesan error jika
  status pengajuan sudah `diverifikasi`/`ditolak` (pengajuan yang sudah diproses panitia tidak boleh
  diubah peserta); jika lolos, tampilkan form terisi data lama (`resources/views/pendaftarans/edit.blade.php`).
- `updateSelf(Request $request, Pendaftaran $pendaftaran)` — validasi sama seperti `store()` (nama, NIK,
  kontak, profesi, instansi, dokumen opsional, kebutuhan asrama); jika peserta tetap butuh asrama, kamar
  lama dilepas dulu lalu dialokasikan ulang via `KamarService` (lihat bagian validasi bentrok di bawah).
- Logika alokasi kamar diekstrak ke method privat `alokasikanKamarJikaPerlu()` supaya `store()` dan
  `updateSelf()` memakai aturan dan pesan yang konsisten (tidak ada duplikasi logika).

### View baru

`resources/views/pendaftarans/edit.blade.php` — form yang sama strukturnya dengan `create.blade.php`
(4 fieldset: data diri, dokumen, asrama, konfirmasi), tapi field terisi data pengajuan yang sedang
diubah, dan dokumen lama tetap dipakai jika peserta tidak mengunggah berkas baru.

Tombol **"Ubah"** ditambahkan di `pendaftarans/index.blade.php` (versi kartu mobile maupun tabel
desktop), muncul berdampingan dengan tombol "Batalkan" untuk pengajuan berstatus `pending`.

## Validasi bentrok jadwal (Praktikum 6)

Jobsheet Praktikum 6 meminta: sebelum `Booking::create()`, cek apakah `room_id` + `date` + rentang waktu
baru tumpang tindih dengan booking lain yang `pending`/`approved`; jika bentrok, tolak dengan pesan error
dekat field.

BAPELKES ONE sudah punya mekanisme setara sejak sebelum Pertemuan 5, lewat `KamarService::alokasikanKamar()`
dan `Kamar::tersediaUntukTanggal()` — bedanya, granularitasnya rentang tanggal (check-in/check-out),
bukan jam (karena menginap di asrama, bukan meminjam ruang per jam), dan skema reaksinya **berbeda
secara sengaja**: alih-alih menolak seluruh pengajuan pelatihan hanya karena asrama penuh, sistem tetap
menyimpan pengajuan (`butuh_asrama = true`, `kamar_id = null`) dan menampilkan pesan yang jelas bahwa
asrama penuh untuk tanggal tersebut. Keputusan produk ini diambil karena pendaftaran pelatihan adalah
transaksi utama peserta — kebutuhan menginap adalah fasilitas tambahan yang semestinya tidak
menggagalkan pendaftaran pelatihannya.

Pekerjaan baru Pertemuan 5 memastikan validasi ini juga berlaku konsisten pada **jalur edit**, bukan
cuma saat pendaftaran pertama kali dibuat: saat peserta mengubah tanggal check-in/check-out lewat form
edit, kamar lama dilepas lebih dulu (supaya tidak dihitung bentrok dengan dirinya sendiri), lalu
dicari ulang kamar yang tersedia untuk rentang tanggal baru — persis logika `store()`. Jika semua kamar
penuh untuk rentang baru, peserta mendapat pesan "asrama sudah penuh untuk tanggal tersebut" (lihat
skenario C pada `06-test-checklist.md`, dan bukti tangkapan layar `08-pesan-bentrok` pada
`07-screenshots.pdf`).

Skenario pengujian otomatis ada di `tests/Feature/PendaftaranEditTest.php`:

| Kasus | Yang diuji | Hasil |
|---|---|---|
| Edit data diri biasa | Nama/NIK/dll. berubah tersimpan | Diterima |
| Pengajuan sudah diverifikasi | Peserta coba edit/lihat form edit | Ditolak (pesan error, data tidak berubah) |
| Edit milik peserta lain | Peserta A coba edit pengajuan peserta B | Ditolak (403) |
| Ubah tanggal ke rentang yang masih ada kamar kosong | Kamar baru dialokasikan, kamar lama dilepas | Diterima + dapat kamar |
| Ubah tanggal ke rentang yang semua kamar sudah penuh | Pengajuan tetap tersimpan tanpa kamar + pesan jelas | Diterima tanpa kamar (bukan ditolak total) |
