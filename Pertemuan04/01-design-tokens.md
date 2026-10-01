# Design Token — Pertemuan 4

BAPELKES ONE · Praktik Aplikasi Web INF60295 · Universitas Negeri Yogyakarta

Token disimpan sebagai CSS variables di `resources/css/app.css`, mengacu pada keputusan desain Pertemuan 3 (palet v5 editorial: indigo-emas-krem).

## Warna

| Token | Nilai | Contoh Penggunaan |
|---|---|---|
| --color-primary | #4F46E5 | Tombol utama, tautan, elemen aktif |
| --color-accent | #D4A017 | Aksen emas, badge, highlight |
| --color-bg | #FFF8E7 | Latar halaman (krem) |
| --color-text | #1F2937 | Judul dan isi halaman |
| --color-surface | #FFFFFF | Latar kartu atau panel |
| --color-success | #16A34A | Status terverifikasi/diterima |
| --color-warning | #D97706 | Status menunggu verifikasi |
| --color-danger | #DC2626 | Pesan error, status ditolak |

## Tipografi

| Token | Nilai | Contoh Penggunaan |
|---|---|---|
| --fs-h1 | 28px | Judul halaman |
| --fs-h2 | 20px | Subjudul, nama pelatihan |
| --fs-body | 16px | Isi teks |
| --fs-label | 14px | Label form |
| --fs-caption | 12px | Teks bantuan, keterangan kecil |

## Jarak

| Token | Nilai |
|---|---|
| --sp-1 | 4px |
| --sp-2 | 8px |
| --sp-3 | 12px |
| --sp-4 | 16px |
| --sp-6 | 24px |
| --sp-8 | 32px |

## Bentuk

| Token | Nilai | Contoh Penggunaan |
|---|---|---|
| --radius-btn | 8px | Sudut tombol |
| --radius-card | 12px | Sudut kartu pelatihan |
| --shadow-card | 0 1px 3px rgba(0,0,0,.12) | Bayangan kartu |

## Catatan

Nilai hex (indigo/emas/krem) masih sementara, menunggu kode warna final dari mockup v5 editorial Pertemuan 3. Jika berbeda, cukup ubah nilainya di `resources/css/app.css`, seluruh halaman akan mengikuti otomatis.