# CRUD "Room" — Pertemuan 5

BAPELKES ONE · Praktik Aplikasi Web INF60295 · Universitas Negeri Yogyakarta

## Pemetaan

Jobsheet Praktikum 4 meminta CRUD penuh (create, read, update, delete) untuk resource "Room" yang
dikelola admin. Di BAPELKES ONE, resource yang setara adalah **Pelatihan**, dan CRUD-nya **sudah
lengkap** sejak sebelum Pertemuan 5, dikerjakan oleh panitia melalui `PelatihanController`:

| Operasi jobsheet | Implementasi nyata |
|---|---|
| `index()` — daftar ruang dari `Room::latest()->get()` | `PelatihanController::index()` — daftar pelatihan dari `Pelatihan::latest()->get()`, route `pelatihans.index` |
| `create()` — form tambah ruang | `PelatihanController::create()`, route `pelatihans.create` |
| `store()` — validasi + `Room::create()` | `PelatihanController::store(StorePelatihanRequest $request)`, route `pelatihans.store` — memakai Form Request khusus, bukan validasi inline |
| `edit()`/`update()` — ubah data ruang | `PelatihanController::edit()` / `update(UpdatePelatihanRequest $request, Pelatihan $pelatihan)`, route `pelatihans.edit` / `pelatihans.update` |
| `destroy()` — hapus ruang | `PelatihanController::destroy(Pelatihan $pelatihan)`, route `pelatihans.destroy` |

Route terdaftar lewat `Route::resource('pelatihans', PelatihanController::class)->middleware(['auth',
'role:panitia'])` di `routes/web.php` — persis pola `Route::resource('rooms', RoomController::class)`
pada jobsheet, hanya dengan tambahan middleware peran karena hanya panitia yang boleh mengelola
pelatihan (peserta hanya boleh melihat katalog).

## Kenapa tidak dikerjakan ulang

Fitur ini sudah diuji oleh test suite yang ada sebelum Pertemuan 5 (`tests/Feature/PelatihanTest.php`,
`tests/Feature/DashboardPanitiaTest.php`) dan sudah memakai Form Request + Policy otorisasi yang lebih
ketat daripada contoh pada jobsheet (validasi inline). Menulis ulang dengan pola jobsheet yang lebih
sederhana akan menjadi kemunduran, bukan peningkatan. Bagian yang benar-benar baru pada Pertemuan 5 ada
di `05-crud-booking/` (CRUD sisi peserta untuk Pendaftaran, yang sebelumnya memang belum lengkap).

Bukti CRUD Pelatihan berjalan end-to-end (create/read/update/delete oleh panitia) didokumentasikan pada
`06-test-checklist.md` dan `07-screenshots.pdf`.
