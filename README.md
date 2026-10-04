# BAPELKES ONE

Portal terpadu pendaftaran pelatihan untuk Bapelkes DIY — proyek kelompok mata kuliah **Praktik Aplikasi
Web (INF60295)**, Program Studi Teknologi Informasi S1, Fakultas Teknik, Universitas Negeri Yogyakarta.

## Anggota Kelompok

| Nama | NIM | GitHub |
|---|---|---|
| Alysa Salsabila Irfan Putri | 24051130049 | [@fc4stle](https://github.com/fc4stle) |
| Marshall Raihan Sahirman | 24051130054 | [@marshallraihan](https://github.com/marshallraihan) |

## Cara Menjalankan Proyek

Dibutuhkan PHP ≥ 8.2, Composer, Node.js ≥ 18, dan npm.

```bash
# 1. Install dependency PHP & JS
composer install
npm install

# 2. Siapkan environment
cp .env.example .env
php artisan key:generate

# 3. Siapkan database (default: SQLite)
touch database/database.sqlite
php artisan migrate
php artisan db:seed   # opsional, membuat 1 akun contoh (test@example.com / password)

# 4. Build asset frontend
npm run build          # build sekali untuk produksi
# atau
npm run dev             # mode watch untuk pengembangan

# 5. Jalankan server lokal
php artisan serve
```

Aplikasi dapat diakses di `http://127.0.0.1:8000`.

## Dokumentasi per Pertemuan

| Folder | Isi |
|---|---|
| [`Pertemuan02/`](Pertemuan02) | Problem vision, persona, user story, acceptance criteria, backlog |
| [`Pertemuan03/`](Pertemuan03) | Scope canvas, sitemap, user flow, wireframe, prototipe klik |
| [`Pertemuan04/`](Pertemuan04) | Design token, inventori komponen, bukti uji, keputusan implementasi |

## Kontribusi Pertemuan 4 (Design System & Implementasi Antarmuka Awal)

| Anggota | Bagian Pekerjaan | Tautan Commit |
|---|---|---|
| Marshall Raihan Sahirman | Design token (`01-design-tokens.md`, CSS variables), komponen Card & Status Badge, halaman Beranda/Katalog/Detail/Form Pendaftaran | [edc4806](../../commit/edc4806), [a6979a3](../../commit/a6979a3), [a1a286a](../../commit/a1a286a), [7b790c6](../../commit/7b790c6), [3047b04](../../commit/3047b04), [2e92d9d](../../commit/2e92d9d) |
| Alysa Salsabila Irfan Putri | Uji responsif desktop/mobile & perbaikan (tabel "Pendaftaran Saya" dan tombol halaman uji komponen yang meluber di 390px), bukti uji (`03-ui-screenshots.pdf`), checklist uji (`04-test-checklist.md`), keputusan implementasi (`05-keputusan-implementasi.md`), `Pertemuan04/README.md`, dan dokumentasi README ini | lihat riwayat commit branch `feature/ui-pertemuan-4` setelah 2e92d9d |

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

Dikembangkan dengan bantuan Hermes Agent.
