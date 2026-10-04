# Inventori Komponen — Pertemuan 4

BAPELKES ONE · Praktik Aplikasi Web INF60295 · Universitas Negeri Yogyakarta

## Komponen yang Disesuaikan (sudah ada, diperbarui ke token)

| Komponen | File | State | Catatan |
|---|---|---|---|
| Primary Button | `primary-button.blade.php` | default, hover, focus, disabled | Warna diganti dari `bg-gray-800` ke `--color-primary` |
| Secondary Button | `secondary-button.blade.php` | default, hover, focus, disabled | Warna teks diganti ke `--color-text` |
| Danger Button | `danger-button.blade.php` | default, hover, active, focus | Warna diganti dari `bg-red-600` ke `--color-danger` |

## Komponen Baru

| Komponen | File | State | Kriteria Pemeriksaan |
|---|---|---|---|
| Card | `card.blade.php` | normal, selected, unavailable | Info utama dan aksi tidak membingungkan; state unavailable dibuat redup dan non-interaktif |
| Status Badge | `status-badge.blade.php` | pending, approved, rejected | Warna bukan satu-satunya pembeda — tiap status punya teks sendiri (Menunggu Verifikasi / Terverifikasi / Ditolak) |

## Komponen yang Belum Diubah (dipakai apa adanya)

| Komponen | File |
|---|---|
| Text Input | `text-input.blade.php` |
| Input Label | `input-label.blade.php` |
| Input Error | `input-error.blade.php` |
| Nav Link | `nav-link.blade.php`, `responsive-nav-link.blade.php` |
| Dropdown | `dropdown.blade.php`, `dropdown-link.blade.php` |
| Modal | `modal.blade.php` |
| Application Logo | `application-logo.blade.php` |
| Auth Session Status | `auth-session-status.blade.php` |

## Selisih dengan Prototype

Dibandingkan dengan wireframe desktop Pertemuan 3 (`Pertemuan03/05-wireframe-desktop.pdf`, D1–D4):

| Halaman | Wireframe Pertemuan 3 | Implementasi Pertemuan 4 | Alasan selisih |
|---|---|---|---|
| D1 — Katalog | Daftar sederhana: nama pelatihan, metode, kuota dalam baris memanjang | Kartu dengan nama, deskripsi singkat, rentang tanggal, lokasi, metode, kuota, dan badge "Tersedia" | Info yang dibutuhkan peserta untuk memutuskan ternyata lebih banyak dari sekadar nama+kuota; kartu juga lebih mudah dibuat responsif (1 kolom di mobile) dibanding baris tabel |
| D2 — Detail | Tanggal, lokasi, kuota ("18/25 tersedia"), deskripsi, tombol Daftar | Tanggal, lokasi, metode, kuota (format "0/40"), tombol Kembali + Ajukan Pendaftaran | Ditambah info Metode karena pelatihan ada yang luring/daring/blended (relevan sejak Pertemuan 2); format kuota mengikuti field `kuota` di database, bukan dihitung manual |
| D3 — Form | 3 field (nama, instansi, unggah surat tugas) + 1 pesan error generik | 5 field wajib (nama, NIK, kontak, profesi, instansi) + 2 unggah dokumen opsional + bagian asrama + pesan error per field | Mengikuti acceptance criteria pendaftaran kolektif/asrama yang sudah ditetapkan di Pertemuan 2–3; pesan error per field dipilih karena lebih jelas menunjukkan field mana yang bermasalah dibanding satu pesan umum |
| D4 — Status | Daftar status dengan badge "Menunggu verifikasi" / "Terverifikasi" | Tabel (desktop) / kartu (mobile) dengan badge serupa + tombol Batalkan untuk status pending | Ditambah aksi pembatalan karena acceptance criteria peserta mengizinkan membatalkan pendaftaran yang belum diverifikasi |

Kesimpulannya: struktur dan urutan layar (katalog → detail → form → status) tidak berubah dari wireframe,
tapi jumlah field dan informasi per layar bertambah mengikuti keputusan desain dan acceptance criteria
yang sudah difinalisasi setelah wireframe dibuat.