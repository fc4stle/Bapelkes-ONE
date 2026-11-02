# Test Checklist — Pertemuan 6: Autentikasi, Otorisasi, Role, Middleware, Policy

Skenario pengujian untuk Bapelkes-ONE role-based access control (RBAC).

## Tabel Hasil Tes

| # | Skenario | Akun Uji | Hasil Diharapkan | Status | Bukti |
|---|----------|----------|------------------|--------|-------|
| 1 | Guest browse katalog pelatihan | – | HTTP 200, lihat nama pelatihan | ✅ Lulus | test_guest_can_browse_catalog |
| 2 | Guest akses /dashboard | – | Redirect ke /login | ✅ Lulus | test_guest_redirect_to_login_for_dashboard |
| 3 | Guest akses /pendaftarans | – | Redirect ke /login | ✅ Lulus | test_guest_redirect_to_login_for_pendaftarans |
| 4 | Registrasi akun baru | – | Akun terbuat, role='peserta' otomatis | ✅ Lulus | test_new_user_registration_creates_peserta_role |
| 5 | Login password salah | user | Redirect + error message, tidak login | ✅ Lulus | test_login_with_wrong_password_fails |
| 6 | Peserta buka create pelatihan | peserta | HTTP 403 Forbidden | ✅ Lulus | test_peserta_cannot_access_create_pelatihan |
| 7 | Peserta daftar pelatihan | peserta | Pendaftaran tersimpan, user_id terisi, status='pending' | ✅ Lulus | test_peserta_can_submit_pendaftaran |
| 8 | Peserta lihat daftar | peserta | Lihat hanya miliknya | ✅ Lulus | test_peserta_sees_only_own_pendaftarans |
| 9 | Peserta edit pendaftaran orang lain | peserta | HTTP 403 Forbidden | ✅ Lulus | test_peserta_cannot_edit_others_pendaftaran |
| 10 | Peserta hapus pendaftaran orang lain | peserta | HTTP 403 Forbidden | ✅ Lulus | test_peserta_cannot_delete_others_pendaftaran |
| 11 | Peserta ubah pendaftaran saat pending | peserta | Berhasil disimpan | ✅ Lulus | test_peserta_can_update_own_pending_pendaftaran |
| 12 | Peserta ubah pendaftaran setelah verify | peserta | HTTP 403 Forbidden | ✅ Lulus | test_peserta_cannot_update_own_verified_pendaftaran |
| 13 | Panitia verifikasi pendaftaran | panitia | Status → 'diverifikasi', kode_presensi ≠ null | ✅ Lulus | test_panitia_can_verify_pendaftaran |
| 14 | Panitia tolak pendaftaran | panitia | Status → 'ditolak', alasan tersimpan | ✅ Lulus | test_panitia_can_reject_pendaftaran |
| 15 | Panitia buat pelatihan | panitia | Pelatihan tersimpan, redirect ke index | ✅ Lulus | test_panitia_can_create_pelatihan |
| 16 | Panitia ubah pelatihan | panitia | Data terupdate, redirect ke index | ✅ Lulus | test_panitia_can_update_pelatihan |
| 17 | Panitia hapus pelatihan | panitia | Pelatihan dihapus dari DB, redirect ke index | ✅ Lulus | test_panitia_can_delete_pelatihan |
| 18 | Panitia lihat daftar pendaftar | panitia | HTTP 200, daftar lengkap | ✅ Lulus | test_panitia_can_see_all_pendaftaran_for_pelatihan |
| 19 | User login logout | peserta | Redirect home, session clear | ✅ Lulus | test_logged_in_user_can_logout |
| 20 | Akses /dashboard setelah logout | – | Redirect ke /login | ✅ Lulus | test_access_dashboard_after_logout_redirects_to_login |
| 21 | Peserta akses panitia dashboard | peserta | HTTP 403 Forbidden | ✅ Lulus | test_peserta_cannot_access_panitia_dashboard |
| 22 | Panitia akses panitia dashboard | panitia | HTTP 200 | ✅ Lulus | test_panitia_can_access_panitia_dashboard |
| 23 | Pendaftaran ditolak tidak dihitung kuota kamar | peserta | Kamar tidak penuh meski ada ditolak | ✅ Lulus | test_rejected_pendaftaran_does_not_count_toward_quota |

## Ringkasan Hasil

**Total: 23 test, 23 lulus, 0 gagal**

```
PHPUnit 12.5.35 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.5.4
Configuration: /home/lenovo/projects/Bapelkes-ONE/phpunit.xml

...............................                                       23 / 23 (100%)

Time: 00:00.960, Memory: 58.50 MB

OK (23 tests, 61 assertions)
```

## Catatan Implementasi Test

1. **SQLite in-memory**: PHPUnit set `DB_CONNECTION=sqlite` dan `DB_DATABASE=:memory:` di phpunit.xml, jadi test tidak menyentuh dev/production database.

2. **Seeder data**: Test menggunakan factory untuk user dan pendaftaran; DatabaseSeeder dipanggil otomatis via `RefreshDatabase`.

3. **Akun uji seeder**:
   - Email: `panitia@test.local` (role='panitia', password: `password`)
   - Email: `peserta@test.local` (role='peserta', password: `password`)
   - (Dibuat via `updateOrCreate()` di DatabaseSeeder.php)
   - Catatan: Password untuk latihan lokal saja; jangan gunakan di server sungguhan.

4. **Authorization checks**:
   - Policy (`app/Policies/PendaftaranPolicy.php`) handle view, update, delete, verify, reject
   - Middleware `role:panitia` pada route panitia
   - Test langsung assert HTTP status (403, 200) dan kondisi data setelah action

5. **Routes tested**:
   - `/pelatihan` → `pelatihan.katalog` (authenticated)
   - `/dashboard`, `/pendaftarans` → middleware auth
   - `/pelatihan/{id}/detail`, `/pelatihan/{id}/daftar` → authenticated
   - `/pelatihan/{id}/pendaftar` → panitia only
   - `/pendaftarans/{id}/verifikasi`, `/pendaftarans/{id}/tolak` → panitia only
   - Resource `/pelatihans/*` (CRUD) → panitia only

6. **Test kuota-ditolak** (row 23): 
   - Menguji kapasitas kamar asrama (Kamar.php `tersediaUntukTanggal()` method)
   - Logika: Pendaftaran dengan status='ditolak' **tidak dihitung** dalam kuota; hanya status='pending' dan 'diverifikasi' yang dihitung
   - Bukti kode: `app/Models/Kamar.php` line 36: `->where('status_verifikasi', '!=', 'ditolak')`
   - Test membuat 2 pendaftaran untuk kamar dengan kuota=1, verifikasi 1 dan tolak 1; test bahwa kamar masih tersedia untuk pendaftaran 3

## File Test

- **Path**: `tests/Feature/AccessTest.php`
- **Total lines**: ~454
- **Namespace**: `Tests\Feature\AccessTest`
- **Base class**: `Tests\TestCase` (with RefreshDatabase)
