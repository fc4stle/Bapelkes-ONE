# TAHAP 1 — Riset & Persiapan: Deploy Bapelkes-ONE ke Vercel

**Tanggal:** 12 September 2026  
**Sumber:** https://vercel.com/kb/guide/laravel-php-with-docker (Vercel KB resmi)

---

## 1. Struktur File yang Dibutuhkan (Menurut Panduan Resmi Vercel)

Berdasarkan Vercel KB "Deploy Laravel on Vercel with Docker":

| File | Fungsi | Status di Project Kami |
|------|--------|------------------------|
| `Dockerfile.vercel` | Build multi-stage: composer install + FrankenPHP runtime | ❌ Belum ada (perlu buat) |
| `Caddyfile` | Konfigurasi web server Caddy (serve `public/`, route ke `index.php`) | ❌ Belum ada (perlu buat) |
| `vercel.json` | Deklarasi container service + routing | ❌ Belum ada (perlu buat) |

### Requirement PHP Extensions untuk FrankenPHP

Berdasarkan panduan, FrankenPHP membutuhkan:
- `pdo_pgsql` + `pgsql` (untuk Supabase PostgreSQL) ✅ sudah ada di Dockerfile kita
- `mbstring`, `xml`, `bcmath`, `gd`, `zip`, `tokenizer`, `fileinfo`, `openssl`, `ctype`, `json`, `pdo` ✅ sudah ada di Dockerfile kita

### Environment Variables Wajib (Vercel)

```
PORT=80
APP_ENV=production
APP_DEBUG=false
LOG_CHANNEL=stderr
CACHE_STORE=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
```

---

## 2. Perbandingan Dockerfile Existing vs Kebutuhan Vercel

### Dockerfile Existing (node+php-alpine)

```dockerfile
# Stage 1: Build frontend assets with Node LTS
FROM node:22-alpine AS node-builder
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: PHP runtime (PHP-FPM + Nginx style)
FROM php:8.3-cli-alpine
# ... install extensions ...
# ... install composer ...
# ... copy app ...
COPY --from=node-builder /build/public/build ./public/build
# ... entrypoint ...
EXPOSE 8080
ENTRYPOINT ["docker-entrypoint"]
CMD ["docker-entrypoint"]
```

### Dockerfile.vercel (Kebutuhan Vercel)

```dockerfile
# Stage 1: Composer dependencies
FROM composer:2 AS dependencies
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader

# Stage 2: FrankenPHP runtime
FROM dunglas/frankenphp:1-php8.4-alpine
WORKDIR /app
COPY --from=dependencies --chown=www-data:www-data /app /app
COPY --chown=www-data:www-data Caddyfile /etc/frankenphp/Caddyfile
RUN setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp \
    && chown -R www-data:www-data /config/caddy /data/caddy
ENV PORT=80 \
    APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    CACHE_STORE=array \
    SESSION_DRIVER=array \
    QUEUE_CONNECTION=sync
USER www-data
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
```

### Apa yang BISA Dipakai Ulang

| Komponen | Status | Catatan |
|----------|--------|---------|
| Stage 1 (node-builder) | ✅ Bisa dipakai | `npm ci` + `npm run build` tetap diperlukan |
| Daftar PHP extensions | ✅ Bisa dipakai | FrankenPHP image sudah include banyak extensions, perlu verify |
| `composer install` command | ✅ Bisa dipakai | Flag `--no-dev --optimize-autoloader` sama |
| Built assets (`public/build`) | ✅ Bisa dipakai | Copy dari node-builder tetap diperlukan |

### Apa yang HARUS Diganti Total

| Komponen | Existing | Vercel |
|----------|----------|--------|
| Base image | `php:8.3-cli-alpine` | `dunglas/frankenphp:1-php8.4-alpine` |
| Web server | PHP-FPM + Nginx (via entrypoint) | FrankenPHP + Caddy (built-in) |
| Entrypoint | `docker-entrypoint.sh` (custom) | `frankenphp run --config /etc/frankenphp/Caddyfile` |
| Port | `8080` | `80` (atau `$PORT` dari Vercel) |
| Log channel | `stack` (default Laravel) | `stderr` (wajib untuk Vercel) |
| Cache/Session driver | `file` (default Laravel) | `array` (karena disk tidak persisten) |
| Queue connection | `database` (default) | `sync` (karena tidak ada worker) |

---

## 3. Fitur Project yang Berpotensi Kena Dampak

### 🔴 FILE UPLOAD (Perlu Penanganan)

**Lokasi kode:**
- `app/Http/Controllers/PendaftaranController.php` baris 85-100

**Kode existing:**
```php
'surat_tugas' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
'dokumen_lain' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],

$suratTugasPath = $request->file('surat_tugas')->store('surat_tugas', 'public');
$dokumenLainPaths[] = $request->file('dokumen_lain')->store('dokumen_pendaftar', 'public');
```

**Masalah:**
- `store('surat_tugas', 'public')` menyimpan ke `storage/app/public/surat_tugas/`
- Di Vercel, disk tidak persisten (setiap deploy = fresh container)
- File akan hilang setelah deploy berikutnya atau saat scale

**Solusi:**
- Pindah ke Supabase Storage (S3-compatible API)
- Tambah disk `supabase` di `config/filesystems.php`
- Ubah kode upload ke `store('surat_tugas', 'supabase')`

---

### 🟢 DATABASE LOCK (Aman)

**Lokasi kode:**
- `app/Services/KamarService.php` baris 13, 21

**Kode existing:**
```php
$kamars = Kamar::lockForUpdate()->get();
```

**Analisis:**
- `lockForUpdate()` = `SELECT ... FOR UPDATE` (row-level lock di PostgreSQL)
- Ini adalah **database-level lock**, bukan application-level lock
- Lock hanya berlaku selama transaksi `DB::transaction()` berjalan
- Di lingkungan serverless (banyak instance paralel), ini tetap aman karena:
  - Setiap request = koneksi database terpishe
  - Lock di-handle oleh PostgreSQL, bukan oleh PHP
  - Selama transaksi, row terkunci untuk instance lain

**Verdict:** ✅ **AMAN** — Tidak perlu penyesuaian. Mekanisme ini tetap berfungsi di Vercel.

---

### 🟢 QUEUE WORKER / SCHEDULER (Tidak Ada)

**Pencarian:**
- `app/Jobs/` — tidak ada file
- `app/Console/Kernel.php` — tidak ada file
- `routes/console.php` — hanya default Laravel
- `QUEUE_CONNECTION` default = `database` (di `config/queue.php`)

**Analisis:**
- Tidak ada job/queue yang di-dispatch
- Tidak ada scheduler/cron yang terdaftar
- `QUEUE_CONNECTION=sync` (di Vercel) berarti job dijalankan langsung di request

**Verdict:** ✅ **AMAN** — Tidak ada queue/scheduler yang perlu diubah.

---

## Ringkasan Dampak per Fitur

| Fitur | Dampak | Aksi Required |
|-------|--------|---------------|
| File upload (surat_tugas, dokumen) | 🔴 TINGGI | Pindah ke Supabase Storage |
| Database lock (lockForUpdate) | 🟢 aman | Tidak perlu perubahan |
| Queue worker / scheduler | 🟢 aman | Tidak ada queue/scheduler |
| Session | 🟡 Sedang | Ubah ke `array` atau `database` |
| Cache | 🟡 Sedang | Ubah ke `array` atau `database` |
| Log | 🟡 Sedang | Ubah ke `stderr` |

---

## Langkah Selanjutnya (Setelah Konfirmasi)

**Tahap 2 — Setup Storage:**
1. Cek kompatibilitas Supabase Storage dengan S3 driver Laravel
2. Siapkan konfigurasi `filesystems.php` untuk Supabase Storage

**Tahap 3 — Buat File Vercel:**
1. Generate `Dockerfile.vercel` (multi-stage: composer + FrankenPHP)
2. Generate `Caddyfile` (serve `public/`, route ke `index.php`)
3. Generate `vercel.json` (container service + routing)
4. Commit ke branch `vercel-deploy` (jangan sentuh `main`)

---

**Status:** Menunggu konfirmasi untuk lanjut ke Tahap 2.
