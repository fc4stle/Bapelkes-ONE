# Keputusan Desain — Pertemuan 3

BAPELKES ONE &middot; Praktik Aplikasi Web INF60295 &middot; Universitas Negeri Yogyakarta

## Format Keputusan Desain

| Keputusan | Dasar Persona/User Story | Alternatif | Alasan Dipilih | Perlu Diuji Lagi? |
|---|---|---|---|---|
| Scope Pertemuan 3 dibatasi ke alur pendaftaran (US-01, US-09) + verifikasi (US-05) | Persona Yuni & Dimas; pendaftaran adalah inti produk BAPELKES ONE, bukan asrama | Memasukkan semua 9 user story sekaligus | Modul mewajibkan sitemap awal maksimal 3 tingkat; scope besar akan mengaburkan alur inti | Tidak |
| Hierarki sitemap dipisah 3 area: Beranda (publik), Pendaftaran (peserta login), Panitia | Perlu membedakan menu publik, pengguna terautentikasi, dan petugas/admin (prinsip arsitektur informasi modul) | Satu sitemap datar tanpa pemisahan peran | Mencegah peserta tersesat ke halaman panitia dan sebaliknya | Tidak |
| Wireframe pakai satu warna aksen (kuning/emas) untuk tombol aksi utama, sisanya abu-abu | Aturan wireframe low-fidelity modul: fokus pada struktur, bukan visual rinci | Wireframe berwarna penuh sesuai palet final v5 editorial (indigo-emas-krem) | Wireframe tahap ini untuk validasi struktur & alur, bukan validasi visual; palet penuh menyusul di tahap desain UI | Ya &mdash; disesuaikan dengan mockup v5 editorial pada tahap implementasi UI |
| State "validasi gagal" digambar eksplisit di Form Pendaftaran (D3/M3) | Acceptance criteria US-09: sistem harus menampilkan pesan error tanpa kehilangan data peserta | Hanya menggambar state sukses (happy path saja) | Modul mewajibkan minimal satu kondisi gagal digambarkan di wireframe & prototipe | Tidak |

## Ringkasan Kontribusi

*(Lengkapi bagian ini dengan nama anggota tim dan kontribusi masing-masing sebelum push ke repositori.)*

| Nama | NIM | Kontribusi |
|---|---|---|
| Alysa Salsabila Irfan Putri | 24051130049 | Scope canvas, sitemap, user flow, wireframe, dokumentasi |
| Marshall Raihan Sahirman | 24051130054 | (isi kontribusinya sesuai yang sebenarnya dikerjakan) |

## Catatan

Dokumen ini disusun berdasarkan backlog Pertemuan 2 (persona Yuni-peserta, Dimas-panitia; 9 user story
prioritas MoSCoW). Fitur di luar ruang lingkup Pertemuan 3 (reservasi asrama, kartu peserta QR, sertifikat
digital, dashboard rekap panitia) sudah dibangun lebih lanjut di pengembangan aplikasi aktual, tetapi
sengaja tidak dimasukkan ke dokumen Pertemuan 3 ini agar sesuai dengan tahapan labsheet yang diminta.
