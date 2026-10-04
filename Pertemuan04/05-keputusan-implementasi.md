# Keputusan Implementasi — Pertemuan 4

BAPELKES ONE · Praktik Aplikasi Web INF60295 · Universitas Negeri Yogyakarta

## 1. Dua halaman prioritas yang dipilih

Jobsheet meminta minimal dua halaman (Beranda/Cari Ruang dan Hasil Pencarian) ditambah detail & form.
Karena proyek kelompok ini sudah berjalan sebagai aplikasi Laravel (bukan HTML statis), halaman yang
diimplementasikan dipetakan ke alur nyata aplikasi:

| Istilah jobsheet | Halaman di aplikasi | Alasan |
|---|---|---|
| Beranda / Cari Ruang | `beranda.blade.php` (`/`) | Pintu masuk dengan field pencarian pelatihan |
| Hasil Pencarian | `pelatihans/katalog.blade.php` (`/pelatihan`) | Sudah ada sejak Pertemuan sebelumnya, diubah dari tabel ke tampilan kartu sesuai wireframe |
| Detail Ruang | `pelatihans/detail.blade.php` (`/pelatihan/{id}/detail`) | Halaman baru, menyambungkan katalog ke form |
| Form Pengajuan | `pendaftarans/create.blade.php` (`/pelatihan/{id}/daftar`) | Dirapikan dengan token & validasi visual |
| Konfirmasi | `pendaftarans/index.blade.php` (`/pendaftarans`) | Menampilkan status badge setelah formulir dikirim |

## 2. Pendekatan state kosong & tidak tersedia

- **Katalog kosong**: saat pencarian tidak menemukan hasil, ditampilkan ikon + pesan "Tidak ada pelatihan
  ditemukan" beserta saran mengubah kata kunci/filter, bukan halaman kosong tanpa penjelasan.
- **Kuota penuh / tidak tersedia**: direpresentasikan lewat komponen Card varian `unavailable` (redup,
  non-interaktif) dan Status Badge — dicontohkan di halaman `/komponen-uji`.
- **Form kosong/wajib**: validasi visual browser bawaan diganti dengan border merah (`--color-danger`)
  dan pesan error per field, dipicu oleh validasi JavaScript sisi klien saat submit, lalu dikuatkan oleh
  validasi server Laravel (`required`) untuk kasus JavaScript dinonaktifkan.

## 3. Navigasi & routing

Alur yang diuji: Katalog → Detail → Form Pendaftaran → (submit) → Status Pendaftaran. Tombol "Kembali"
pada form mengarah balik ke Detail, dan tombol di Detail mengarah balik ke Katalog — tidak ada tautan
yang menuju halaman kosong atau route yang belum didefinisikan (diuji manual di langkah F).

## 4. Breakpoint mobile

Mengikuti troubleshooting jobsheet, breakpoint diuji pada lebar 390px (ukuran ponsel umum):

- Grid kartu katalog (`grid-cols-1 md:grid-cols-3`) otomatis menjadi satu kolom di bawah breakpoint `md`.
- Baris tombol dan status badge pada halaman uji komponen (`/komponen-uji`) awalnya memakai `flex` tanpa
  `flex-wrap` sehingga tombol "Nonaktif" terpotong di luar layar 390px — **diperbaiki** dengan menambah
  `flex-wrap` pada kedua baris.
- Tabel "Pendaftaran Saya" (`/pendaftarans`) memakai tabel lebar tetap yang meluber horizontal di mobile
  (kolom Status dan Aksi terdorong keluar layar, hanya bisa dilihat dengan geser). **Diperbaiki** dengan
  membuat dua tampilan: kartu bertumpuk untuk layar `< sm`, dan tabel seperti semula untuk `sm` ke atas.

## 5. Komponen yang dipakai ulang

Button (primary/secondary/danger), Card (normal/selected/unavailable), dan Status Badge
(pending/approved/rejected) dipakai berulang di katalog, detail, status pendaftaran, dan halaman uji
komponen — bukan ditulis ulang dengan nilai warna/jarak berbeda di tiap halaman. Rinciannya ada di
`02-component-inventory.md`.

## 6. Keterbatasan yang diketahui (belum diperbaiki)

- Pesan error validasi server (fallback saat JavaScript nonaktif) masih dalam bahasa Inggris bawaan
  Laravel ("The nama field is required"), karena `APP_LOCALE` proyek masih `en` dan belum ada file bahasa
  `lang/id`. Validasi visual (border merah + teks error) tetap berfungsi; hanya bahasanya yang belum
  konsisten dengan sisa antarmuka. Dicatat sebagai pekerjaan rumah, bukan diperbaiki sekarang karena di
  luar cakupan Praktikum 4 (fokus: design token, komponen, dan implementasi halaman).
