# Pemetaan Antarmuka ke Model Data — Pertemuan 5

BAPELKES ONE · Praktik Aplikasi Web INF60295 · Universitas Negeri Yogyakarta

## Catatan penting: studi kasus jobsheet vs. proyek nyata

Jobsheet Pertemuan 5 menggunakan studi kasus generik "peminjaman ruang kampus" (`Room` dan `Booking`)
sebagai contoh. Proyek kelompok ini **bukan** aplikasi greenfield — sejak Pertemuan 2–3, BAPELKES ONE
sudah punya domain nyata (pendaftaran pelatihan Bapelkes DIY), dan sejak Pertemuan 4 modelnya sudah
sebagian terbentuk (dari pekerjaan pertemuan-pertemuan sebelumnya, di luar kerja Pertemuan 4 kami).
Supaya tugas tetap relevan dan tidak duplikat, field/konsep pada jobsheet dipetakan ke entitas yang
sudah ada di proyek, bukan dibuat ulang dari nol:

| Istilah jobsheet | Entitas nyata di BAPELKES ONE | Alasan pemetaan |
|---|---|---|
| `Room` (ruang) | `Pelatihan` (untuk CRUD "ruang/resource" oleh panitia) **dan** `Kamar`/`Asrama` (untuk konsep "ruangan yang bisa penuh/bentrok jadwal") | Jobsheet menguji dua hal sekaligus lewat `Room`: (1) CRUD resource oleh admin, dan (2) resource yang bisa habis/bentrok jadwal. Di BAPELKES ONE, dua kebutuhan ini sudah dipisah secara alami ke dua entitas berbeda sejak Pertemuan 2–3. |
| `Booking` (pengajuan peminjaman) | `Pendaftaran` (pengajuan ikut pelatihan) **dan** alokasi `Kamar` di dalamnya (untuk kebutuhan menginap) | `Pendaftaran` adalah transaksi utama peserta; kebutuhan asrama (check-in/check-out) adalah bagian dari satu pengajuan yang sama, persis seperti `Booking` pada jobsheet. |

## Pemetaan field antarmuka → kolom database

### Entitas "Room" #1 — Pelatihan (CRUD oleh panitia)

| Antarmuka | Field | Kolom Database | Tipe |
|---|---|---|---|
| Detail Pelatihan | Nama pelatihan | `pelatihans.nama` | varchar |
| Detail Pelatihan | Deskripsi | `pelatihans.deskripsi` | text |
| Detail Pelatihan | Tanggal mulai/selesai | `pelatihans.tanggal_mulai` / `tanggal_selesai` | date |
| Detail Pelatihan | Lokasi | `pelatihans.lokasi` | varchar |
| Detail Pelatihan | Kuota | `pelatihans.kuota` | integer |
| Detail Pelatihan | Metode | `pelatihans.metode` | enum (luring/daring/blended) |
| Detail Pelatihan | Status | `pelatihans.status` | enum (draft/dibuka/ditutup/selesai) |

### Entitas "Room" #2 — Kamar & Asrama (resource yang bisa bentrok jadwal)

| Antarmuka | Field | Kolom Database | Tipe |
|---|---|---|---|
| Form Pendaftaran (bagian asrama) | Nama asrama | `asramas.nama` | varchar |
| Form Pendaftaran (bagian asrama) | Kapasitas asrama | `asramas.kapasitas` | integer |
| Kartu peserta / status pendaftaran | Nomor kamar | `kamars.nomor_kamar` | varchar |
| Kartu peserta / status pendaftaran | Kapasitas kamar | `kamars.kapasitas` | integer |

### Entitas "Booking" — Pendaftaran

| Antarmuka | Field | Kolom Database | Tipe |
|---|---|---|---|
| Form Pendaftaran | Nama lengkap, NIK, kontak, profesi, instansi | `pendaftarans.data_diri` (JSON) | json |
| Form Pendaftaran | Dokumen (surat tugas, dll.) | `pendaftarans.dokumen` (JSON path file) | json |
| Form Pendaftaran (asrama) | Butuh menginap | `pendaftarans.butuh_asrama` | boolean |
| Form Pendaftaran (asrama) | Tanggal check-in/check-out | `pendaftarans.check_in` / `check_out` | date |
| Form Pendaftaran (asrama) | Kamar yang dialokasikan | `pendaftarans.kamar_id` (FK → `kamars.id`) | foreign key, nullable |
| Status Pendaftaran Saya | Status verifikasi | `pendaftarans.status_verifikasi` | enum (pending/diverifikasi/ditolak) |
| Halaman Verifikasi (panitia) | Catatan/alasan penolakan | `pendaftarans.catatan` / `alasan_penolakan` | text, nullable |

## Relasi data

```
pelatihans
- id (PK)
- nama, deskripsi, tanggal_mulai, tanggal_selesai, lokasi, kuota, metode, status
- timestamps

asramas
- id (PK)
- nama, kapasitas
- timestamps

kamars
- id (PK)
- asrama_id (FK → asramas.id)
- nomor_kamar, kapasitas
- timestamps

pendaftarans
- id (PK)
- peserta_id (FK → users.id)
- pelatihan_id (FK → pelatihans.id)
- kamar_id (FK → kamars.id, nullable)
- status_verifikasi, data_diri (json), dokumen (json)
- butuh_asrama, check_in, check_out
- verified_by, verified_at, alasan_penolakan, catatan
- kode_presensi, kode_generated_at, hadir_at
- kode_sertifikat, sertifikat_generated_at
- timestamps

Relasi:
- 1 Pelatihan dapat memiliki banyak Pendaftaran (hasMany / belongsTo)
- 1 Asrama dapat memiliki banyak Kamar (hasMany / belongsTo)
- 1 Kamar dapat memiliki banyak Pendaftaran yang menginap di sana pada rentang tanggal berbeda (hasMany / belongsTo)
- 1 User (peserta) dapat memiliki banyak Pendaftaran (hasMany / belongsTo)
```

## Hasil pekerjaan

- Seluruh field pada form Pendaftaran dan halaman Detail Pelatihan Pertemuan 4 sudah punya tempat
  penyimpanan di database — tidak ada field yang belum dipetakan.
- Relasi `Pelatihan` (1) — (N) `Pendaftaran`, `Asrama` (1) — (N) `Kamar`, dan `Kamar` (1) — (N)
  `Pendaftaran` sudah jelas dan konsisten dengan model Eloquent di `app/Models/`.
