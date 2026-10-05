# Pertemuan 4 — Design System, Struktur Proyek, Komponen UI, dan Implementasi Antarmuka Awal

**Mata Kuliah:** Praktik Aplikasi Web (INF60295)
**Program Studi:** Teknologi Informasi S1, Fakultas Teknik, Universitas Negeri Yogyakarta
**Dosen Pengampu:** Zaenal Mustofa, M.Kom.
**Proyek:** BAPELKES ONE — portal terpadu pendaftaran pelatihan Bapelkes DIY
**Branch:** `feature/ui-pertemuan-4`

## Anggota Kelompok

| Nama | NIM |
|---|---|
| Alysa Salsabila Irfan Putri | 24051130049 |
| Marshall Raihan Sahirman | 24051130054 |

## Daftar Berkas

| Berkas | Isi |
|---|---|
| `01-design-tokens.md` | Praktikum 1 — warna, tipografi, jarak, radius, bayangan (disimpan juga sebagai CSS variables di `resources/css/app.css`) |
| `02-component-inventory.md` | Praktikum 2–3 — struktur proyek Laravel yang dipakai, inventori komponen (Button, Card, Status Badge), dan selisih dengan wireframe Pertemuan 3 |
| `03-ui-screenshots.pdf` | Bukti uji tampilan desktop (1440×900) dan mobile (390×844) untuk tiap halaman/state |
| `04-test-checklist.md` | Checklist uji antarmuka 10 poin |
| `05-keputusan-implementasi.md` | Keputusan implementasi: pemetaan halaman, state kosong/tidak tersedia, navigasi, breakpoint mobile, keterbatasan yang diketahui |
| `README.md` | Berkas ini |

## Studi Kasus

Melanjutkan Pertemuan 3: wireframe dan prototipe klik diubah menjadi halaman Laravel Blade yang benar-benar
dapat dijalankan di browser — Beranda, Katalog Pelatihan (kartu), Detail Pelatihan, Form Pendaftaran, dan
Status Pendaftaran — memakai design token dan komponen yang konsisten.

## Pembagian Kerja

| Anggota | Bagian Pekerjaan |
|---|---|
| Marshall Raihan Sahirman | Design token & CSS variables, komponen Card dan Status Badge, penerapan token ke tombol, implementasi halaman Beranda/Katalog/Detail/Form Pendaftaran |
| Alysa Salsabila Irfan Putri | Setup & menjalankan aplikasi untuk pengujian, uji tampilan desktop & mobile (390px), perbaikan 2 masalah responsif yang ditemukan (tabel "Pendaftaran Saya" dan baris tombol di halaman uji komponen), penyusunan bukti uji screenshot, checklist uji, keputusan implementasi, pengisian selisih prototipe, dan dokumentasi README (Pertemuan04 & root) |

Tautan commit: lihat tabel kontribusi di [README.md root](../README.md#kontribusi-pertemuan-4-design-system--implementasi-antarmuka-awal).

## Refleksi Individu — Alysa Salsabila Irfan Putri

**Komponen apa yang paling banyak mengurangi pengulangan kode?**
Komponen Status Badge dan Button. Status Badge dipakai di tiga tempat berbeda (halaman uji komponen,
kartu pelatihan, dan tabel/kartu status pendaftaran) dengan satu sumber logika warna+teks per status,
jadi kalau aturan visual status berubah, cukup ubah satu file. Button (primary/secondary/danger) juga
dipakai di hampir semua halaman — tanpa komponen ini, warna dan ukuran tombol gampang jadi tidak
konsisten karena ditulis manual di tiap Blade.

**Bagian prototype mana yang berubah setelah diimplementasikan?**
Form pendaftaran berubah paling banyak: wireframe Pertemuan 3 hanya punya 3 field (nama, instansi,
unggah surat tugas), sedangkan implementasinya punya 5 field wajib ditambah dokumen dan bagian asrama,
mengikuti acceptance criteria yang baru difinalisasi setelah wireframe dibuat. Detail perbandingan ada di
`02-component-inventory.md` bagian "Selisih dengan Prototype".

**Masalah responsif apa yang ditemukan dan bagaimana perbaikannya?**
Sebelum pengujian ini, tampilan mobile belum pernah benar-benar dicoba. Setelah diuji pada lebar 390px,
ditemukan dua masalah: (1) tabel di halaman "Pendaftaran Saya" meluber horizontal sehingga kolom Status
dan Aksi terdorong keluar layar dan tidak terlihat tanpa geser; (2) baris tombol (Simpan/Batal/Hapus/
Nonaktif) di halaman uji komponen tidak membungkus baris baru, sehingga tombol terakhir terpotong di
tepi layar. Keduanya diperbaiki: tabel pendaftaran diberi tampilan alternatif berupa kartu bertumpuk
khusus untuk layar sempit (tabel asli tetap dipakai di layar ≥ 640px), dan baris tombol/badge diberi
`flex-wrap` supaya elemen yang tidak muat otomatis pindah ke baris berikutnya.

**Bagaimana design token membantu kerja kolaboratif?**
Karena warna, jarak, dan radius sudah didefinisikan sebagai CSS variables di satu tempat
(`resources/css/app.css`), saya bisa menambah tampilan kartu alternatif untuk tabel pendaftaran tanpa
perlu menebak kode warna atau ukuran yang dipakai Marshall di bagian lain — tinggal pakai token yang
sama (`--color-danger`, `--radius-card`, `--sp-*`) dan hasilnya otomatis konsisten dengan halaman yang
sudah ada.

**Apa kontribusi Anda dan bukti commit atau artefaknya?**
Menyiapkan environment aplikasi (Composer, npm, database SQLite) agar bisa benar-benar diuji di browser,
menguji seluruh halaman pada viewport desktop dan mobile, menemukan dan memperbaiki dua masalah
responsif di atas, serta menyusun `03-ui-screenshots.pdf`, `04-test-checklist.md`,
`05-keputusan-implementasi.md`, bagian "Selisih dengan Prototype" di `02-component-inventory.md`, dan
dokumentasi README (Pertemuan04 ini serta pembaruan README root). Bukti commit ada di riwayat branch
`feature/ui-pertemuan-4` dan Pull Request yang dibuka dari branch ini ke `main`.

## Status

- [x] Design token
- [x] Struktur proyek & komponen UI
- [x] Implementasi halaman awal (Beranda, Katalog)
- [x] Implementasi detail & form
- [x] Uji desktop & mobile + perbaikan masalah responsif
- [x] Bukti uji (screenshot)
- [x] Checklist uji & keputusan implementasi
- [x] Refleksi individu & kontribusi di README
- [ ] Pull Request direview Marshall/Alysa dan digabung ke `main`
