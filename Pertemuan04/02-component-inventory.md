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

*(Isi setelah membandingkan tampilan browser dengan prototype Figma/Penpot Pertemuan 3.)*