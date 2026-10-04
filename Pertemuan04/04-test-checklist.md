# Checklist Uji Antarmuka — Pertemuan 4

BAPELKES ONE · Praktik Aplikasi Web INF60295 · Universitas Negeri Yogyakarta

Diuji pada: Chromium (Playwright), viewport desktop 1440×900 dan mobile 390×844, dengan akun contoh
(`test@example.com`) dan 3 data pelatihan contoh. Bukti visual ada di `03-ui-screenshots.pdf`.

| No. | Pemeriksaan | Hasil | Catatan |
|---|---|---|---|
| 1 | Halaman dapat dijalankan dari instruksi README. | Sudah | Diuji ulang dari nol mengikuti langkah di README.md root (composer install → npm install → .env → migrate → build → serve). |
| 2 | Tidak ada error pada console browser. | Sudah | Dicek dengan listener console/page error di seluruh halaman (beranda, katalog, detail, form, uji komponen, status) pada dua viewport — 0 error. |
| 3 | Warna, tipografi, jarak, dan radius mengikuti token. | Sudah | Komponen Button, Card, Status Badge, dan halaman memakai CSS variables dari `resources/css/app.css` (lihat `01-design-tokens.md` dan `02-component-inventory.md`); tidak ada nilai warna/jarak baru yang ditulis manual pada perbaikan responsif. |
| 4 | Navigasi dari hasil ke detail dan form berjalan. | Sudah | Alur Katalog → Detail → Form → submit → Status Pendaftaran diuji end-to-end dan berhasil tanpa halaman kosong/error. |
| 5 | State kosong, error, dan sukses dapat ditampilkan. | Sudah | Katalog kosong (pencarian tanpa hasil), form error (validasi visual per field), dan status sukses (badge setelah submit) — ketiganya ada di `03-ui-screenshots.pdf`. |
| 6 | Tampilan desktop dan mobile tidak terpotong atau meluber. | Sudah | Dua masalah ditemukan saat uji mobile 390px (tabel "Pendaftaran Saya" meluber horizontal; baris tombol di halaman uji komponen terpotong) — keduanya sudah diperbaiki dan diverifikasi ulang dengan screenshot. Detail di `05-keputusan-implementasi.md` bagian 4. |
| 7 | Tombol dan field memiliki label yang jelas. | Sudah | Semua tombol pakai kata kerja ("Cari", "Lihat Detail", "Kirim Pendaftaran", "Batalkan"); semua field form punya label eksplisit di atasnya. |
| 8 | Kontras teks dan latar dapat dibaca. | Sudah | Diperiksa visual pada seluruh screenshot: teks gelap (`--color-text`) di atas latar terang (`--color-bg`/`--color-surface`), teks putih di atas warna status yang cukup pekat (primary/success/warning/danger). Belum diuji dengan alat kontras otomatis (mis. WAVE/axe). |
| 9 | Kode komponen tidak diduplikasi tanpa alasan. | Sudah | Button, Card, dan Status Badge tetap satu sumber kebenaran. Catatan: `pendaftarans/index.blade.php` sengaja punya dua blok markup (kartu untuk mobile, tabel untuk desktop ke atas) karena transformasi tabel→kartu antar breakpoint belum punya cara lain tanpa JavaScript tambahan di Blade murni — keduanya tetap memakai komponen `<x-status-badge>` yang sama, bukan menulis ulang logikanya. |
| 10 | Perubahan sudah di-commit dan didorong ke repositori. | Sudah | Lihat riwayat commit branch `feature/ui-pertemuan-4` dan Pull Request ke `main`. |

## Keterbatasan yang diketahui

Pesan error validasi server (fallback saat JavaScript nonaktif) masih berbahasa Inggris bawaan Laravel —
lihat `05-keputusan-implementasi.md` bagian 6. Tidak menggugurkan poin 5/7 di atas karena validasi visual
(border merah + teks error) tetap tampil dan field yang bermasalah tetap jelas, hanya bahasanya belum
konsisten.
