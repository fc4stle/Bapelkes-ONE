# TAHAP 2 — Setup Storage & Konfigurasi Vercel

**Tanggal:** 12 September 2026  
**Fokus:** Session/Cache driver + Supabase Storage S3 compatibility

---

## 1. Status SESSION_DRIVER & CACHE_STORE

### Dari `.env.example` (project default):

```env
SESSION_DRIVER=database   ✅ Sudah benar untuk Vercel
CACHE_STORE=database      ✅ Sudah benar untuk Vercel
QUEUE_CONNECTION=database  ⚠️  Perlu diubah ke 'sync' untuk Vercel
```

**Catatan:** File `.env` asli tidak bisa dibaca (secret file), tapi `.env.example` menunjukkan default yang sudah benar. Pastikan `.env` production (Vercel) punya setting yang sama.

---

## 2. Migration Status

| Tabel | Migration | Status |
|-------|-----------|--------|
| `cache` | `0001_01_01_000001_create_cache_table.php` | ✅ Sudah ada |
| `cache_locks` | (sama dengan cache) | ✅ Sudah ada |
| `jobs` | `0001_01_01_000002_create_jobs_table.php` | ✅ Sudah ada |
| `sessions` | — | ❌ **BELUM ADA** |

**Aksi:** Perlu buat migration untuk `sessions` table:

```bash
php artisan make:session-table
```

Migration ini akan membuat tabel:
```php
Schema::create('sessions', function (Blueprint $table) {
    $table->string('id')->primary();
    $table->foreignId('user_id')->nullable()->index();
    $table->string('ip_address', 45)->nullable();
    $table->text('user_agent')->nullable();
    $table->longText('payload');
    $table->integer('last_activity')->index();
});
```

---

## 3. Supabase Storage S3 — Kompatibilitas & Konfigurasi

### Bukti Kompatibilitas

Berdasarkan dokumentasi resmi Supabase (supabase.com/blog/s3-compatible-storage):

> "Supabase Storage now supports the S3 protocol. The protocol works on the cloud, local development, and self-hosting."

Dan dari GitHub discussion #30518, user berhasil connect Laravel ke Supabase Storage via S3 driver.

### Konfigurasi Laravel untuk Supabase Storage

Berdasarkan dokumentasi dan community reports, konfigurasi yang benar:

```env
# Filesystem disk untuk Supabase Storage
FILESYSTEM_DISK=supabase

# S3 Credentials (dari Supabase Dashboard > Storage > Settings > S3)
AWS_ACCESS_KEY_ID=<s3-access-key-id>
AWS_SECRET_ACCESS_KEY=<s3-secret-access-key>
AWS_DEFAULT_REGION=<supabase-project-region>  # contoh: ap-southeast-1

# Endpoint Supabase (format: https://<project-ref>.supabase.co/storage/v1/s3)
AWS_ENDPOINT=https://<project-ref>.supabase.co/storage/v1/s3

# WAJIB: Supabase menggunakan path-style URLs
AWS_USE_PATH_STYLE_ENDPOINT=true

# Bucket name
AWS_BUCKET=<bucket-name>
```

### Konfigurasi `filesystems.php`

Tambah disk `supabase`:

```php
'supabase' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', true),
    'throw' => true,
],
```

### Catatan Penting dari Community Reports

1. **`AWS_USE_PATH_STYLE_ENDPOINT=true` WAJIB** — Supabase menggunakan path-style URLs (`https://<project-ref>.supabase.co/storage/v1/s3/bucket/...`), bukan virtual-hosted-style.

2. **Generate S3 credentials dari Dashboard** — Supabase Dashboard > Storage > Settings > S3 > "Generate new credentials". Access Key dan Secret Key berbeda dari service_role key biasa.

3. **Region harus sesuai** — Region harus match dengan region Supabase project (contoh: `ap-southeast-1` untuk Singapore).

4. **Bucket harus public atau policy sesuai** — Untuk file yang perlu diakses publik (surat tugas, dokumen), bucket perlu di-set public atau attach policy yang mengizinkan read.

---

## 4. Dampak ke Kode Upload

### Kode Existing (PendaftaranController.php):

```php
// Baris 94
$suratTugasPath = $request->file('surat_tugas')->store('surat_tugas', 'public');

// Baris 99
$dokumenLainPaths[] = $request->file('dokumen_lain')->store('dokumen_pendaftar', 'public');
```

### Kode yang Diperlukan untuk Vercel:

```php
// Ganti 'public' ke 'supabase'
$suratTugasPath = $request->file('surat_tugas')->store('surat_tugas', 'supabase');
$dokumenLainPaths[] = $request->file('dokumen_lain')->store('dokumen_pendaftar', 'supabase');
```

### Aksi Tambahan:

1. **Buat bucket di Supabase** (contoh: `dokumen-peserta`)
2. **Set bucket policy** untuk allow read/write dari S3 credentials
3. **Update kode upload** untuk gunakan disk `supabase`
4. **Update kode download/tampil file** untuk gunakan `Storage::disk('supabase')->url()` atau `Storage::disk('supabase')->temporaryUrl()`

---

## 5. Ringkasan Aksi Tahap 2

| Aksi | Status | Catatan |
|------|--------|---------|
| Pastikan `SESSION_DRIVER=database` | ✅ Dari `.env.example` | Verifikasi di `.env` production |
| Pastikan `CACHE_STORE=database` | ✅ Dari `.env.example` | Verifikasi di `.env` production |
| Buat migration `sessions` table | ❌ Perlu `php artisan make:session-table` | Tambah ke Tahap 3 |
| Tambah disk `supabase` di `filesystems.php` | ❌ Perlu edit | Tambah ke Tahap 3 |
| Generate S3 credentials dari Supabase Dashboard | ❌ Perlu action user | User perlu generate credentials |
| Update kode upload di `PendaftaranController.php` | ❌ Perlu edit | Ganti `'public'` ke `'supabase'` |

---

## 6. Rekomendasi: Test Koneksi Supabase S3

Sebelum lanjut ke Tahap 3, **test koneksi Supabase Storage S3** bisa dilakukan dengan script kecil:

```php
// routes/web.php (temporary test route)
Route::get('/test-supabase-s3', function () {
    try {
        $disk = Storage::disk('supabase');
        
        // Test write
        $disk->put('test.txt', 'Hello from Laravel at ' . now());
        
        // Test read
        $content = $disk->get('test.txt');
        
        // Test URL
        $url = $disk->url('test.txt');
        
        // Cleanup
        $disk->delete('test.txt');
        
        return response()->json([
            'status' => 'success',
            'content' => $content,
            'url' => $url,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
});
```

**Catatan:** Test ini hanya bisa dijalankan di environment yang punya credentials Supabase S3 (bukan di server ini).

---

## Langkah Selanjutnya (Tahap 3)

Setelah konfirmasi, Tahap 3 akan:

1. **Generate `Dockerfile.vercel`** — multi-stage: composer + FrankenPHP
2. **Generate `Caddyfile`** — serve `public/`, route ke `index.php`
3. **Generate `vercel.json`** — container service + routing
4. **Buat migration `sessions` table** — `php artisan make:session-table`
5. **Update `filesystems.php`** — tambah disk `supabase`
6. **Update `PendaftaranController.php`** — ganti `'public'` ke `'supabase'`
7. **Commit ke branch `vercel-deploy`** — jangan sentuh `main`

---

**Status:** Menunggu konfirmasi untuk lanjut ke Tahap 3.
