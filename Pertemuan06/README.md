# Pertemuan 6: Autentikasi, Otorisasi, Role, Middleware, Policy

Implementasi praktik web Pertemuan 6 untuk Bapelkes-ONE: role-based access control (RBAC) dengan middleware, policy, dan authorization sesuai pola Laravel 13 modern.

## Tujuan Pembelajaran

Memahami dan menerapkan:
1. **Autentikasi (Authentication)**: Login/register dengan Laravel Breeze, default role peserta
2. **Otorisasi (Authorization)**: Policy untuk kontrol akses berbasis role (peserta, panitia)
3. **Middleware**: Role middleware untuk protect route tertentu
4. **Authorization methods**: `$this->authorize()` vs `Gate::authorize()`
5. **Testing**: Feature test untuk verify RBAC behavior dengan SQLite in-memory

## Struktur File & Komponen

### Authentication & Users
- **Model**: `app/Models/User.php`
  - Field: `id, name, email, password, role, created_at, updated_at`
  - Roles: `peserta` (default), `panitia`
  - Relasi: `hasMany('pendaftarans')` via foreign key `peserta_id`

- **Migration**: `database/migrations/2026_09_10_082046_add_role_to_users_table.php`
  - Kolom: `role enum('peserta', 'panitia') default='peserta'`

- **Seeder**: `database/seeders/DatabaseSeeder.php`
  - Akun uji via `updateOrCreate()`:
    - Email: `panitia@test.local` (role='panitia', password: `password`)
    - Email: `peserta@test.local` (role='peserta', password: `password`)
  - **Catatan**: Password untuk latihan lokal saja; jangan gunakan di server sungguhan.

### Authorization & Policies
- **Policy**: `app/Policies/PendaftaranPolicy.php`
  - Metode: `view()`, `update()`, `delete()`, `verify()`, `reject()`
  - Peserta: view/update/delete hanya milik sendiri, hanya saat `status_verifikasi='pending'`
  - Panitia: akses full; verify/reject panitia-only

- **Controller**: `app/Http/Controllers/PendaftaranController.php`
  - Menggunakan `$this->authorize('action', $model)` di methods: edit, updateSelf, destroy, kartu, sertifikat, verifikasi, tolak

### Middleware & Routes
- **Middleware**: `app/Http/Middleware/RoleMiddleware.php`
  - Alias: `'role' => RoleMiddleware::class` (bootstrap/app.php)
  - Usage: `middleware('role:panitia')` pada route tertentu

- **Routes** (`routes/web.php`):
  - Public: `/` (home), `/awal`
  - Auth required: `/dashboard`, `/pelatihan` (katalog), `/pendaftarans` (list)
  - Peserta only: `/pelatihan/{id}/daftar`, `/pendaftarans/{id}/edit|delete|updateSelf`, `/pendaftarans/{id}/kartu|sertifikat`
  - Panitia only: `/panitia/dashboard`, `/pelatihan/{id}/pendaftar`, `/pendaftarans/{id}/verifikasi|tolak`, Resource `/pelatihans` (CRUD)

### Models & Relations
- **Pendaftaran**: `peserta_id` FK ke users, `pelatihan_id` FK ke pelatihans
  - Relasi: `belongsTo(User, 'peserta_id')` → User::pendaftarans()
  - Status: `pending`, `diverifikasi`, `ditolak`

- **Pelatihan**: status enum, kuota, tanggal_mulai/selesai
  - Relasi: `hasMany(Pendaftaran)`, `hasMany(Kamar)` (untuk asrama)

### Testing
- **Test file**: `tests/Feature/AccessTest.php` (23 test cases)
  - Setup: `RefreshDatabase` trait (SQLite in-memory)
  - Coverage: Guest access, registration, login, peserta CRUD, panitia approval, role-based forbidden, kuota-ditolak

## Cara Menjalankan

### Setup Awal
```bash
cd ~/projects/Bapelkes-ONE

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed

# Build assets (optional, dev sudah running)
npm run dev
```

### Menjalankan Server Development
```bash
# Terminal 1: Laravel dev server
php artisan serve
# Akses: http://localhost:8000

# Terminal 2: Asset build (watcher)
npm run dev
```

### Menjalankan Test
```bash
# Semua test
vendor/bin/phpunit

# Hanya AccessTest
vendor/bin/phpunit tests/Feature/AccessTest.php

# Dengan output verbose
vendor/bin/phpunit tests/Feature/AccessTest.php --verbose

# Code style check (Pint)
./vendor/bin/pint tests/Feature/AccessTest.php --test
./vendor/bin/pint --dirty  # Fix all dirty files
```

### Akun Uji Manual (dari seeder)
Gunakan akun ini untuk login manual di http://localhost:8000/login:

| Role | Email | Password |
|------|-------|----------|
| Panitia | `panitia@test.local` | `password` |
| Peserta | `peserta@test.local` | `password` |

**Catatan**: Password untuk testing lokal saja. Jangan gunakan di environment production.

## File Implementasi & Changes

| File | Tipe | Perubahan |
|------|------|-----------:|
| `app/Http/Controllers/Controller.php` | Edit | + `use AuthorizesRequests` trait |
| `app/Http/Controllers/PendaftaranController.php` | Edit | + `$this->authorize()` calls di 7 methods |
| `app/Policies/PendaftaranPolicy.php` | New | Policy dengan 5 authorization methods |
| `database/seeders/DatabaseSeeder.php` | Edit | Replace `factory()` dengan `updateOrCreate()` untuk panitia + peserta |
| `tests/Feature/AccessTest.php` | New | 23 feature test scenarios (6 panitia, 8 peserta, 5 guest, 3 logout, 1 kuota) |
| `Pertemuan06/01-matriks-akses.md` | New | Access matrix dokumentasi |
| `Pertemuan06/06-test-checklist.md` | New | Test results checklist |
| `Pertemuan06/README.md` | New | File ini — dokumentasi lengkap |

## Git Commit Plan

Sesuai conventional commit (English):

```
1. feat: add PendaftaranPolicy with authorization rules
   - Peserta view/update/delete own registration when pending
   - Panitia verify/reject all registrations
   
2. refactor: add AuthorizesRequests trait to Controller base class
   - Enable $this->authorize() in all child controllers

3. refactor: implement authorization checks in PendaftaranController
   - Add $this->authorize() calls to edit, updateSelf, destroy, kartu, sertifikat, verifikasi, tolak methods

4. refactor: update DatabaseSeeder with updateOrCreate for test accounts
   - Panitia account: panitia@test.local
   - Peserta account: peserta@test.local
   - Prevent duplicates on re-run

5. test: add AccessTest with 23 RBAC scenarios
   - Guest redirects, registration, login, peserta CRUD, panitia approval, kuota-ditolak
   - All tests pass with SQLite in-memory

6. docs: add Pertemuan06 documentation
   - 01-matriks-akses.md: access matrix & data model
   - 06-test-checklist.md: test results table
   - README.md: setup, routes, test execution guide
```

## Hasil Verifikasi

### PHPUnit (23/23 passed)
```
PHPUnit 12.5.35 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.5.4
Configuration: /home/lenovo/projects/Bapelkes-ONE/phpunit.xml

............................                                        23 / 23 (100%)

Time: 00:00.960, Memory: 58.50 MB

OK (23 tests, 61 assertions)
```

### Pint (code style)
```json
{"tool":"pint","result":"passed"}
```

## Referensi & Resources

- **Laravel Docs**:
  - [Authorization (Policies)](https://laravel.com/docs/11.x/authorization#policies)
  - [Middleware](https://laravel.com/docs/11.x/middleware)
  - [Route Middleware Groups](https://laravel.com/docs/11.x/routing#route-groups)
  - [HTTP Tests](https://laravel.com/docs/11.x/http-tests)

- **Bapelkes-ONE Docs**:
  - Migrasi role: `database/migrations/2026_09_10_082046_add_role_to_users_table.php`
  - Middleware: `app/Http/Middleware/RoleMiddleware.php`
  - Routes: `routes/web.php` (lines 20–56)

## Checklist Kompetensi

Setelah menyelesaikan Pertemuan 6, Anda seharusnya dapat:

- [ ] Menjelaskan perbedaan autentikasi vs otorisasi
- [ ] Membuat policy untuk model dengan multiple authorization methods
- [ ] Menggunakan `$this->authorize()` di controller untuk enforce policy
- [ ] Melindungi route dengan role middleware
- [ ] Menulis feature test untuk verify RBAC behavior
- [ ] Membaca & interpret policy logic dari kode
- [ ] Trace user flow dari route → middleware → policy → response

---

**Last Updated**: 2 November 2026  
**Branch**: `feature/pertemuan-6-auth-role`  
**Maintainer**: Alysa (IT, UNY)
