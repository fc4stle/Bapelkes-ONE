# Matriks Akses Bapelkes-ONE - Pertemuan 6

## Ringkasan Struktur

### Role & Relasi
- **Peserta**: User dengan `role='peserta'`. Bisa daftar ke pelatihan, lihat dan ubah daftar pendaftarannya sendiri.
- **Panitia**: User dengan `role='panitia'`. Bisa kelola pelatihan, verifikasi/tolak pendaftaran peserta, lihat semua pendaftaran.

### Entitas Utama
- **users** (id, name, email, password, role='peserta'|'panitia')
- **pelatihans** (id, nama, status=dibuka|ditutup, kuota, metode=daring|luring)
- **pendaftarans** (id, peserta_id, pelatihan_id, status_verifikasi=pending|diverifikasi|ditolak, data_diri, dokumen, asrama, kamar_id)
- **kamars** (id, asrama_id, nomor_kamar, kapasitas)

---

## Matriks Akses Per Role

### Tamu (Belum Login)

| Fitur | Path | Method | Akses | Keterangan |
|-------|------|--------|-------|-----------|
| Beranda | `/` | GET | ✓ BOLEH | Publik, ada link login/register |
| Registrasi | `/register` | GET, POST | ✓ BOLEH | Buat akun baru (otomatis role=peserta) |
| Login | `/login` | GET, POST | ✓ BOLEH | Masuk sistem |
| Katalog Pelatihan | `/pelatihan` | GET | ✓ BOLEH | Lihat list pelatihan terbuka |
| Detail Pelatihan | `/pelatihan/{id}/detail` | GET | ✓ BOLEH | Lihat detail pelatihan |
| Dashboard | `/dashboard` | GET | ✗ TIDAK | Redirect ke `/login` |
| Pendaftaran Saya | `/pendaftarans` | GET | ✗ TIDAK | Redirect ke `/login` |
| Profile | `/profile` | GET, PATCH, DELETE | ✗ TIDAK | Redirect ke `/login` |

---

### Peserta (Login + role='peserta')

| Fitur | Path | Method | Akses | Batasan | Keterangan |
|-------|------|--------|-------|---------|-----------|
| **Katalog & Detail** |
| Katalog Pelatihan | `/pelatihan` | GET | ✓ BOLEH | - | Lihat semua pelatihan terbuka |
| Detail Pelatihan | `/pelatihan/{id}/detail` | GET | ✓ BOLEH | - | Lihat detail pelatihan |
| **Pendaftaran** |
| Daftar Pelatihan | `/pelatihan/{id}/daftar` | GET | ✓ BOLEH | Status pelatihan=dibuka, kuota tersedia, belum daftar | Form daftar pelatihan |
| Simpan Pendaftaran | `/pelatihans/{id}/daftar` | POST | ✓ BOLEH | Validasi data, kuota/asrama | Simpan ke DB, peserta_id=auth()->user()->id |
| Lihat Pendaftaran Saya | `/pendaftarans` | GET | ✓ BOLEH | Hanya milik peserta login | Daftar pendaftaran peserta |
| Edit Pendaftaran | `/pendaftarans/{id}/edit` | GET | ✓ BOLEH | Milik peserta, status=pending | Form ubah data diri/asrama |
| Perbarui Pendaftaran | `/pendaftarans/{id}` | PUT | ✓ BOLEH | Milik peserta, status=pending | Update data_diri, dokumen, asrama |
| Hapus Pendaftaran | `/pendaftarans/{id}` | DELETE | ✓ BOLEH | Milik peserta, status=pending | Batalkan pendaftaran (soft delete) |
| Lihat Pendaftaran Orang Lain | `/pendaftarans/{id}` (orang lain) | GET | ✗ 403 | - | Policy: hanya lihat milik sendiri |
| **Pasca Verifikasi** |
| Lihat Kartu Peserta | `/pendaftarans/{id}/kartu` | GET | ✓ BOLEH | Milik peserta, status=diverifikasi | Tampilkan kartu + QR code presensi |
| Download Sertifikat | `/pendaftarans/{id}/sertifikat` | GET | ✓ BOLEH | Milik peserta, status=diverifikasi, hadir=true, pelatihan selesai | Generate & download PDF |
| **Dashboard & Profile** |
| Dashboard Peserta | `/dashboard` | GET | ✓ BOLEH | - | Ringkasan peserta |
| Profile | `/profile` | GET, PATCH, DELETE | ✓ BOLEH | - | Ubah email, password, hapus akun |
| **Akses Terlarang** |
| Kelola Pelatihan | `/pelatihans` | GET, POST, PUT, DELETE | ✗ 403 | - | Middleware `role:panitia` |
| Rekap Panitia | `/panitia/dashboard` | GET | ✗ 403 | - | Middleware `role:panitia` |
| Verifikasi Pendaftaran | `/pendaftarans/{id}/verifikasi` | PATCH | ✗ 403 | - | Middleware `role:panitia` |
| Tolak Pendaftaran | `/pendaftarans/{id}/tolak` | PATCH | ✗ 403 | - | Middleware `role:panitia` |

---

### Panitia (Login + role='panitia')

| Fitur | Path | Method | Akses | Keterangan |
|-------|------|--------|-------|-----------|
| **Kelola Pelatihan** |
| Daftar Pelatihan | `/pelatihans` | GET | ✓ BOLEH | List semua pelatihan (panitia) |
| Buat Pelatihan | `/pelatihans/create` | GET | ✓ BOLEH | Form input pelatihan baru |
| Simpan Pelatihan | `/pelatihans` | POST | ✓ BOLEH | Validasi, simpan ke DB |
| Edit Pelatihan | `/pelatihans/{id}/edit` | GET | ✓ BOLEH | Form ubah pelatihan |
| Perbarui Pelatihan | `/pelatihans/{id}` | PUT | ✓ BOLEH | Update: nama, jadwal, kuota, metode, status |
| Hapus Pelatihan | `/pelatihans/{id}` | DELETE | ✓ BOLEH | Soft delete pelatihan |
| Lihat Detail Pelatihan | `/pelatihans/{id}` | GET | ✓ BOLEH | Detail pelatihan (panitia) |
| **Daftar Pendaftar & Verifikasi** |
| Dashboard Panitia | `/panitia/dashboard` | GET | ✓ BOLEH | Rekap semua pelatihan + peserta |
| Daftar Pendaftar (Pelatihan) | `/pelatihan/{id}/pendaftar` | GET | ✓ BOLEH | List peserta yang daftar pelatihan ini |
| Export Pendaftar | `/pelatihan/{id}/pendaftar/export` | GET | ✓ BOLEH | Download CSV pendaftar |
| Verifikasi Pendaftaran | `/pendaftarans/{id}/verifikasi` | PATCH | ✓ BOLEH | Status=diverifikasi, buat kode presensi |
| Tolak Pendaftaran | `/pendaftarans/{id}/tolak` | PATCH | ✓ BOLEH | Status=ditolak, catat alasan, lepas kamar |
| **Presensi & Kehadiran** |
| Presensi (QR Scan) | `/pelatihan/{id}/presensi` | GET | ✓ BOLEH | Form/input scan QR code peserta |
| Proses Presensi | `/pelatihan/{id}/presensi` | POST | ✓ BOLEH | Catat kehadiran peserta (hadir_at) |
| **Lihat Semua Pendaftaran** |
| Update Status Verifikasi | `/pendaftarans/{id}` | PATCH | ✓ BOLEH | Ubah status_verifikasi langsung (fallback untuk verifikasi/tolak) |
| **Akses Katalog & Detail (Like Peserta)** |
| Katalog Pelatihan | `/pelatihan` | GET | ✓ BOLEH | Lihat pelatihan terbuka (opsional) |
| Detail Pelatihan | `/pelatihan/{id}/detail` | GET | ✓ BOLEH | Lihat detail pelatihan (opsional) |
| **Dashboard & Profile** |
| Dashboard Panitia | `/dashboard` | GET | ✓ BOLEH | Alias ke `/panitia/dashboard` atau ringkasan general |
| Profile | `/profile` | GET, PATCH, DELETE | ✓ BOLEH | Ubah profil panitia |
| **Akses Terlarang** |
| Daftar Pendaftaran Peserta | `/pendaftarans` (index) | GET | ✗ (N/A) | Tidak di-route untuk panitia, panitia lihat via `/pelatihan/{id}/pendaftar` |
| Edit Pendaftaran Peserta | `/pendaftarans/{id}/edit` | GET | ✗ 403 | Policy: hanya peserta boleh edit data diri |

---

## Perubahan Struktur Data

### Users Table
```
id | name | email | password | role (enum: peserta, panitia) | created_at | updated_at
```
- Kolom `role` ditambah via migration `2026_09_10_082046_add_role_to_users_table.php`
- Default: `'peserta'`
- Saat registrasi, role auto-set ke `'peserta'`, tidak bisa diisi dari form

### Pendaftarans Table
```
id | peserta_id (FK users) | pelatihan_id | status_verifikasi | data_diri (JSON) | dokumen (JSON) | 
butuh_asrama | check_in | check_out | kamar_id | verified_by (FK users) | verified_at | 
alasan_penolakan | kode_presensi | hadir_at | kode_sertifikat | created_at | updated_at
```
- Kolom `peserta_id` adalah FK ke `users.id`, tidak boleh diisi dari form input
- Diisi otomatis dari `Auth::user()->id` di controller store
- `status_verifikasi`: `pending` (default) → `diverifikasi` / `ditolak`
- Unique constraint: `(peserta_id, pelatihan_id)`

### Kamar & Asrama (Existing)
- `kamar.tersediaUntukTanggal()` mengecualikan pendaftaran dengan `status_verifikasi='ditolak'` dari perhitungan kapasitas
- Saat pendaftaran ditolak, `kamar_id` tidak dihapus tapi `status_verifikasi='ditolak'` membuat kamar itu dihitung tersedia lagi

---

## Relasi Eloquent

### User Model
```php
public function pendaftarans(): HasMany
{
    return $this->hasMany(Pendaftaran::class, 'peserta_id');
}

public function isPanitia(): bool
{
    return $this->role === 'panitia';
}
```

### Pendaftaran Model
```php
public function peserta(): BelongsTo
{
    return $this->belongsTo(User::class, 'peserta_id');
}

public function pelatihan(): BelongsTo
{
    return $this->belongsTo(Pelatihan::class);
}

public function verifier(): BelongsTo
{
    return $this->belongsTo(User::class, 'verified_by');
}

public function kamar(): BelongsTo
{
    return $this->belongsTo(Kamar::class);
}
```

### Pelatihan Model
```php
public function pendaftarans(): HasMany
{
    return $this->hasMany(Pendaftaran::class);
}
```

### Kamar Model
```php
public function pendaftarans(): HasMany
{
    return $this->hasMany(Pendaftaran::class);
}

public function tersediaUntukTanggal(string $checkIn, string $checkOut): bool
{
    // Exclude status_verifikasi != 'ditolak'
}
```

---

## Middleware & Routing

### Middleware: RoleMiddleware
- Alias: `role`
- Gunakan: `Route::middleware('role:panitia')` atau `Route::middleware('role:peserta')`
- Check: `$request->user()->role === $role`, abort(403) jika tidak match

### Route Groups
```php
// Publik (tamu + auth)
GET  /                           (Beranda)
POST /register                   (Register)
POST /login                      (Login)
GET  /pelatihan                  (Katalog)
GET  /pelatihan/{id}/detail      (Detail)

// Auth + Peserta
GET  /pelatihan/{id}/daftar      (Form daftar)
POST /pelatihans/{id}/daftar     (Simpan daftar)
GET  /pendaftarans               (Lihat daftar milik)
GET  /pendaftarans/{id}/edit     (Edit form)
PUT  /pendaftarans/{id}          (Update milik)
DELETE /pendaftarans/{id}        (Hapus milik)
GET  /pendaftarans/{id}/kartu    (Lihat kartu)
GET  /pendaftarans/{id}/sertifikat (Download sertifikat)

// Auth + Panitia
GET  /panitia/dashboard          (Rekap panitia)
GET  /pelatihans                 (Index pelatihan)
POST /pelatihans                 (Buat pelatihan)
GET  /pelatihans/{id}/edit       (Edit form pelatihan)
PUT  /pelatihans/{id}            (Update pelatihan)
DELETE /pelatihans/{id}          (Hapus pelatihan)
GET  /pelatihan/{id}/pendaftar   (Daftar pendaftar)
GET  /pelatihan/{id}/pendaftar/export (Export pendaftar)
GET  /pelatihan/{id}/presensi    (Form presensi)
POST /pelatihan/{id}/presensi    (Proses presensi)
PATCH /pendaftarans/{id}/verifikasi (Verifikasi)
PATCH /pendaftarans/{id}/tolak   (Tolak)

// Auth (Peserta + Panitia)
PATCH /pendaftarans/{id}         (Update status verifikasi — fallback)
```

---

## Policy: PendaftaranPolicy

### Methods
```php
public function view(User $user, Pendaftaran $pendaftaran): bool
    → Peserta: peserta_id == user.id
    → Panitia: true (lihat semua)

public function update(User $user, Pendaftaran $pendaftaran): bool
    → Peserta: peserta_id == user.id AND status_pending
    → Panitia: false (gunakan verifikasi/tolak, bukan updateSelf)

public function delete(User $user, Pendaftaran $pendaftaran): bool
    → Peserta: peserta_id == user.id AND status_pending
    → Panitia: false

public function verify(User $user, Pendaftaran $pendaftaran): bool
    → Panitia: true
    → Peserta: false

public function reject(User $user, Pendaftaran $pendaftaran): bool
    → Panitia: true
    → Peserta: false
```

### Controller Usage
```php
// Di PendaftaranController
$this->authorize('view', $pendaftaran);     // Line 144
$this->authorize('update', $pendaftaran);   // Line 142, 160
$this->authorize('delete', $pendaftaran);   // Line 292
$this->authorize('verify', $pendaftaran);   // Line 270
$this->authorize('reject', $pendaftaran);   // Line 281
```

---

## Seeder & Akun Uji

### DatabaseSeeder.php
```php
User::updateOrCreate(
    ['email' => 'peserta@test.local'],
    [
        'name' => 'Peserta Test',
        'password' => Hash::make('password'),
        'role' => 'peserta',
    ]
);

User::updateOrCreate(
    ['email' => 'panitia@test.local'],
    [
        'name' => 'Panitia Test',
        'password' => Hash::make('password'),
        'role' => 'panitia',
    ]
);
```

**Akun Uji untuk Latihan:**
- Email: `peserta@test.local`, Password: `password`, Role: peserta
- Email: `panitia@test.local`, Password: `password`, Role: panitia

---

## UI & Navigasi

### Navigation.blade.php
- `@auth / @guest`: Tampilkan menu berbeda untuk login/logout
- `@if (auth()->user()->isPanitia())`: Menu panitia (Rekap Panitia, Kelola Pelatihan)
- `@else`: Menu peserta (Pendaftaran Saya)
- Guest: Link ke Login & Register

### Beranda
- Peserta login: Form cari pelatihan
- Panitia login: Link Rekap Panitia, Kelola Pelatihan
- Tamu: Form cari pelatihan (publik)

### Pendaftaran Views
- Tombol Edit/Hapus: Hanya tampil jika `status_pending` (Blade condition)
- Tombol Verifikasi/Tolak: Hanya peserta dalam list verifikasi panitia

---

## Perilaku Sistem & Bisnis Logic

### Alur Registrasi Pendaftaran
1. Peserta GET `/pelatihan/{id}/daftar` → Cek status pelatihan (dibuka), cek kuota, cek belum daftar
2. Peserta POST `/pelatihans/{id}/daftar` → Validasi data_diri, asrama (jika ada)
3. Create Pendaftaran dengan `peserta_id=auth()->id`, `status_verifikasi='pending'`
4. Alokasi kamar jika `butuh_asrama=true` via KamarService
5. Redirect ke `/pendaftarans` dengan pesan sukses

### Alur Verifikasi (Panitia)
1. Panitia GET `/pelatihan/{id}/pendaftar` → List peserta yang daftar pelatihan ini
2. Panitia PATCH `/pendaftarans/{id}/verifikasi` → Status='diverifikasi', generate kode_presensi
3. Peserta bisa download kartu peserta + presensi via QR code

### Alur Penolakan (Panitia)
1. Panitia PATCH `/pendaftarans/{id}/tolak` + alasan_penolakan → Status='ditolak'
2. Kamar otomatis tersedia lagi (tidak perlu explicit delete peserta_id dari kamar)
   - Logika: `Kamar::tersediaUntukTanggal()` mengecualikan status='ditolak' dari perhitungan
3. Peserta masih bisa lihat pendaftaran yang ditolak (untuk lihat alasan)

### Alur Edit Pendaftaran (Peserta)
1. Peserta GET `/pendaftarans/{id}/edit` → Cek ownership & status='pending' via Policy
2. Peserta PUT `/pendaftarans/{id}` → Update data_diri, dokumen, asrama
3. Jika asrama berubah, realokasi kamar via KamarService
4. Redirect ke `/pendaftarans` dengan pesan sukses

### Pembatasan Waktu Edit
- Peserta hanya boleh edit saat `status_verifikasi='pending'`
- Setelah panitia verifikasi/tolak, peserta tidak bisa ubah (policy mengembalikan false)
- Jika peserta edit data teritori (NIK, nama), harus request verifikasi ulang? → Belum di-implement

---

## Error Handling

### 403 Unauthorized
- Peserta akses pendaftaran orang lain → Policy::view() → 403
- Peserta ubah pendaftaran setelah diverifikasi → Policy::update() → 403
- Peserta coba akses fitur panitia → Middleware role → 403

### Redirect
- Tamu akses `/dashboard`, `/pendaftarans`, dll → Middleware auth → Redirect `/login`
- Setelah login, redirect ke intended URL atau default dashboard

### Validasi Form
- Kuota pelatihan penuh → Back dengan pesan error
- Asrama penuh → Simpan pendaftaran tapi tanpa kamar + warning pesan
- Data diri tidak valid → Form validation error

---

## Catatan Implementasi

1. **Migration**: Tidak ada perubahan struktur tabel existing. Role ditambah via migration baru `add_role_to_users_table`.
2. **Policy**: Menggunakan Laravel Policy standard dengan `$this->authorize()` di controller.
3. **Middleware**: RoleMiddleware + Auth Middleware di route group.
4. **Seeder**: Menggunakan `updateOrCreate()` agar idem-potent (aman re-run).
5. **Kamar Management**: Status ditolak otomatis exclude dari perhitungan kapasitas, tidak perlu explicit release.
6. **Soft Delete**: Pendaftaran dihapus peserta via destroy → soft delete (jika model punya SoftDeletes trait) atau hard delete (current implementation).

