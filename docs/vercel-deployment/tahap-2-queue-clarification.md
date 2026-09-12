# TAHAP 2 (Lanjutan) — Klarifikasi Queue & Rencana Test Supabase S3

**Tanggal:** 12 September 2026

---

## 1. Klarifikasi QUEUE_CONNECTION

### Temuan: Project TIDAK Pernah Pakai Queue

Setelah pencarian ulang exhaustive di seluruh kode project:

| Dicari | Ditemukan |
|--------|-----------|
| `Job::dispatch()` atau `dispatch(new ...)` | ❌ **Tidak ada** |
| `implements ShouldQueue` | ❌ **Tidak ada** |
| `Mail::to(...)->queue()` atau `Mail::queue()` | ❌ **Tidak ada** |
| `Notification::send()` atau `notify()` | ❌ **Tidak ada** |
| `Bus::batch()` atau `Bus::chain()` | ❌ **Tidak ada** |
| `queue` atau `queued` method calls | ❌ **Tidak ada** |

### Fitur Sertifikat (US-04) — Analisis Detail

```php
// PendaftaranController.php baris 266-271
$pdf = \PDF::loadView('pendaftarans.sertifikat_pdf', compact('pendaftaran'));
$filename = 'Sertifikat_'.Str::slug($pendaftaran->data_diri['nama'] ?? 'peserta').'_'.$pendaftaran->kode_sertifikat.'.pdf';
return $pdf->download($filename);
```

**Proses:** Generate PDF langsung (synchronous) → download response. **Tidak ada queue.**

### Fitur Notifikasi/Email — Analisis Detail

Satu-satunya "notifikasi" yang ada adalah:

1. **Email Verification** (Laravel Breeze built-in):
   ```php
   $request->user()->sendEmailVerificationNotification();
   ```
   - Default Laravel Breeze = **synchronous** (tidak pakai queue)

2. **Password Reset** (Laravel Breeze built-in):
   ```php
   event(new PasswordReset($user));
   ```
   - Laravel built-in = **synchronous** (tidak pakai queue)

3. **Registered event** (Laravel Breeze built-in):
   ```php
   event(new Registered($user));
   ```
   - Laravel built-in = **synchronous** (tidak pakai queue)

### Kesimpulan

**`QUEUE_CONNECTION=sync` di Vercel adalah langkah pencegahan (defensive), buhan karena ada kode yang pakai queue.**

Alasan pencegahan:
- Laravel mungkin dispatch job secara internal di beberapa fitur (misalnya cache pruning, dll)
- Package pihak ketiga mungkin dispatch job tanpa kita sadari
- Jika ada job yang di-dispatch tapi tidak ada worker, job akan "hilang" dan bisa menyebabkan bug diam-diam
- Di Vercel (serverless), tidak ada worker proses yang berjalan terus-menerus

---

## 2. Test Koneksi Supabase Storage S3

### Masalah

Untuk test koneksu nyata, saya membutuhkan:

1. **S3 Credentials** dari Supabase Dashboard:
   - Access Key ID
   - Secret Access Key

2. **Bucket name** yang sudah dibuat

3. **Project region** (untuk validasi)

4. **Project reference** (untuk construct endpoint URL)

### Alasan Saya Belum Bisa Test Langsung

Saya tidak punya akses ke Supabase Dashboard project Bapelkes-ONE, dan credentials tidak boleh dibagikan di luar channel yang aman.

### Rencana Test (Setediakan Credentials)

Setelah Anda generate S3 credentials dan buat bucket, buka file ini dan isi:

```env
# Temporary test credentials (jangan commit ke git)
SUPABASE_S3_KEY=your-access-key
SUPABASE_S3_SECRET=your-secret-key
SUPABASE_S3_BUCKET=your-bucket-name
SUPABASE_S3_REGION=ap-southeast-1
SUPABASE_PROJECT_REF=your-project-ref
```

Saya akan buat script test yang:

1. Menggunakan `Storage::disk('supabase')` untuk upload file test
2. Memverifikasi file muncul di Supabase Dashboard
3. Test read file
4. Test delete file
5. Return JSON result (success/error)

### Alternatif: Test Sendiri

Jika Anda lebih suka test sendiri, buka `routes/web.php` dan tambahkan temporary route:

```php
Route::get('/test-supabase-s3', function () {
    try {
        config(['filesystems.disks.supabase' => [
            'driver' => 's3',
            'key' => env('SUPABASE_S3_KEY'),
            'secret' => env('SUPABASE_S3_SECRET'),
            'region' => env('SUPABASE_S3_REGION', 'ap-southeast-1'),
            'bucket' => env('SUPABASE_S3_BUCKET'),
            'endpoint' => 'https://'.env('SUPABASE_PROJECT_REF').'.supabase.co/storage/v1/s3',
            'use_path_style_endpoint' => true,
        ]]);
        
        $disk = Storage::disk('supabase');
        $disk->put('test.txt', 'Hello from Laravel at ' . now());
        $content = $disk->get('test.txt');
        $url = $disk->url('test.txt');
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
        ], 500);
    }
});
```

Jalankan `php artisan serve` dan buka `/test-supabase-s3`.

---

## 3. Ringkasan sebelum Tahap 3

| Item | Status |
|------|--------|
| Queue usage di project | ✅ TIDAK ADA — aman untuk `QUEUE_CONNECTION=sync` |
| Session driver (`database`) | ✅ Siap |
| Cache store (`database`) | ✅ Siap |
| Migration `sessions` table | ❌ Perlu dibuat |
| Supabase S3 test | ⏳ Menunggu credentials |
| Filesystem config `supabase` | ❌ Perlu ditambahkan |
| Kode upload (`'public'`→`'supabase'`) | ❌ Perlu diubah |

---

## Menunggu

1. **Konfirmasi queue** — apakah penjelasan di atas cukup?
2. **S3 Credentials** — untuk test koneksi nyata (atau konfirmasi Anda mau test sendiri)

Setelah dua hal ini clear, saya lanjut ke Tahap 3.
