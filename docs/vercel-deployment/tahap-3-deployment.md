# Tahap 3: Konfigurasi Deployment Vercel

Dokumen ini merangkum perubahan yang dilakukan untuk persiapan deployment ke Vercel.

## Perubahan yang Dilakukan

### 1. Dockerfile.vercel
File: `Dockerfile.vercel`

Multi-stage build:
- Stage 1: Node 22 Alpine untuk build aset frontend
- Stage 2: dunglas/frankenphp:1.8.1-php8.5 sebagai runtime PHP
- Mendukung database SQLite (dev) dan PostgreSQL (prod via Supabase)

### 2. Caddyfile
File: `Caddyfile`

Konfigurasi FrankenPHP/Caddy untuk:
- HTTP/3 support
- Static file serving
- PHP processing via FrankenPHP
- Security headers
- Compression (zstd, br, gzip)

### 3. vercel.json
File: `vercel.json`

Konfigurasi deployment Vercel:
- Runtime: `vercel-php@0.7.0`
- Build command: `npm run build`
- Output directory: `public`
- Route semua request ke `api/index.php`
- Cache immutable untuk aset static

### 4. api/index.php
File: `api/index.php`

Front controller untuk serverless PHP runtime Vercel.
Memuat Laravel dari root project dan menangani request.

### 5. Session Migration
Tabel sessions sudah ada di `database/migrations/0001_01_01_000000_create_users_table.php`.
Migration duplikat dihapus (sudah ada di default Laravel).

### 6. Config Filesystems - Disk 'supabase'
File: `config/filesystems.php`

Disk baru 'supabase' ditambahkan dengan:
- Driver: S3 (compatible dengan Supabase Storage)
- Endpoint: https://bgitojiyzvahpnebcvve.storage.supabase.co/storage/v1/s3
- Bucket: bapelkes-one-vercel
- Path style: true (required untuk Supabase)

### 7. PendaftaranController
File: `app/Http/Controllers/PendaftaranController.php`

Perubahan:
- Line 94: `->store('surat_tugas', 'public')` → `->store('surat_tugas', 'supabase')`
- Line 99: `->store('dokumen_pendaftar', 'public')` → `->store('dokumen_pendaftar', 'supabase')`

### 8. Dependencies
File: `composer.json`

Package `league/flysystem-aws-s3-v3` ditambahkan untuk support S3/Supabase Storage.

## Verifikasi

- Tests: 125 passed, 1 skipped (126 total)
- Build: Berhasil (CSS 41.86 kB, JS 54.19 kB)

## Environment Variables yang Diperlukan

Pastikan tersedia di Vercel:
- `APP_KEY` - Application key Laravel
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` - Domain Vercel
- `DB_CONNECTION` - Database driver
- `AWS_ACCESS_KEY_ID` - S3 access key
- `AWS_SECRET_ACCESS_KEY` - S3 secret key
- `AWS_DEFAULT_REGION` - Region S3
- `AWS_ENDPOINT` - Supabase Storage endpoint
- `AWS_USE_PATH_STYLE_ENDPOINT=true`
- `AWS_BUCKET=bapelkes-one-vercel`

## Catatan

- Dokumen ini untuk referensi saja, tidak dibutuhkan di production
- `docs/vercel-deployment/` berikan untuk dokumentasi internal
- `.env.save` adalah backup env lama (abaikan atau hapus)
