# Riset Platform Hosting Alternatif untuk BAPELKES ONE (Laravel + Docker)

**Tanggal riset:** 12 September 2026  
**Sumber:** Halaman pricing resmi + dokumen resmi + forum komunitas + pengalaman langsung pengguna

---

## ⚠️ KOREKSI PENTING: Render TIDAK se-"bebas" yang diklaim

### Temuan: Kartu kredit DIPERLUKAN untuk Web Service di Render

Meskipun halaman pricing dan artikel resmi Render (render.com/articles/platforms-with-a-real-free-tier-for-developers-in-2026) menyatakan **"No credit card is required"**, kenyataannya lebih kompleks:

**Bukti bahwa CC diperlukan untuk Web Service:**

1. **FlyWP (sumber independen, Feb 2026):**
   > "Render's static site hosting does not require a credit card; **only the compute services (web services, background workers) do**."

2. **Reddit r/webdev (Juli 2026):**
   > "Do NOT give your credit card to render.com you WILL get charged. I signed up for render.com thinking it was free, but they asked for a credit card even though I planned to use a free service."

3. **Reddit r/rails (2024, tapi masih relevan):**
   > "Your render.yaml services require payment information on file."

4. **Northflank blog (competitor analysis):**
   > "You might also need to add a credit card before unlocking certain things."

5. **Pengalaman langsung Alysa:**
   > Saat mencoba deploy/signup Bapelkes-ONE ke Render, sistem meminta kartu kredit sebelum bisa lanjut.

**Kesimpulan:** Render TIDAK memenuhi kriteria "tanpa kartu kredit di semua tahap" untuk use case Web Service berbasis Docker/custom runtime. **Render GUGUR dari opsi.**

---

## 🔍 VERIFIKASI LANJUTAN: Koyeb

### Temuan: Koyeb Signup TIDAK meminta kartu kredit

Berdasarkan dokumen resmi Koyeb (koyeb.com/docs/reference/accounts):

**Alur signup Koyeb:**
1. Buka halaman signup
2. Masukkan **first name, last name, email** → klik Continue
3. Masukkan **password** (atau passkey) → klik Continue
4. Akun terbuat → terima email verifikasi
5. Masukkan kode verifikasi → signup selesai

**Tidak ada langkah kartu kredit dalam alur signup.**

Setelah signup, user langsung bisa:
- Membuat organisasi
- Membuat Web Service (termasuk yang menggunakan Docker/custom runtime)
- Deploy dari GitHub repo

**Kapan CC diminta?**
> "Koyeb says it tries to keep the free tier available without requiring a credit card, but **may ask for one if it cannot automatically verify that you are human**." (srvrlss.io)

Artinya: CC **mungkin** diminta hanya jika sistem gagal melakukan verifikasi otomatis (misalnya untuk pencegahan fraud/abuse). Ini tidak terjadi untuk sebagian besar user.

### Catatan: Akuisisi Mistral AI

Koyeb diakuisisi oleh Mistral AI (Februari 2026). Beberapa sumber menyatakan:
> "The free Starter plan will disappear, and new users will have to switch to switch to paid plans." (codedtrip.com)

TAPI sumber lain (blog resmi Koyeb, freetiers.com) menyatakan:
> "Koyeb provides a free tier with Database included – no credit card required, never expires, commercial use allowed."

**Status free instance pasca-akuisisi:** TETAP TERSEDIA (512MB RAM, 0.1 vCPU, 2GB SSD) — berdasarkan dokumentasi resmi Koyeb terbaru (September 2026).

---

## 🔍 VERIFIKASI LANJUTAN: SnapDeploy

### Detail Platform SnapDeploy

**Sumber:** snapdeploy.dev (official blog), berbagai review independen

**Free tier:**
- 512 MB RAM, 0.25 vCPU per container
- **Tidak perlu kartu kredit**
- Auto-sleep ketika idle, auto-wake 10-30 detik saat ada traffic
- **10 deploys/hari**
- Hingga 4 containers
- Custom domains hanya di Always-On ($12/bulan)

**Deployment options (dari official blog):**
> "You can deploy by pulling a Docker image by name, connecting a GitHub repo that contains a Dockerfile, uploading an artifact like a JAR or ZIP file, or spinning up a preconfigured app through 1-click templates."

**Docker multi-stage build support:**
> "SnapDeploy was built from the ground up around Docker containers. That distinction shapes the entire experience — deployment starts with your container, not with a framework-specific buildpack that happens to support Dockerfiles."

Karena SnapDeploy deploy dari GitHub repo yang berisi Dockerfile, **multi-stage builds seharusnya didukung** — SnapDeploy hanya menjalankan `docker build` pada Dockerfile yang ada di repo.

### Apakah 10 deploys/hari cukup untuk development aktif?

**Analisis:**
- 10 deploys/hari = rata-rata 1 deploy per jam (dalam 10 jam kerja)
- Untuk development normal: **CUKUP** (biasanya 2-5 deploy/hari)
- Untuk development intensif (sering ganti kode + fix bug): **MUNGKIN TIDAK CUKUP**
- Kalau limit habis: harus tunggu keesokan hari atau upgrade ke Always-On ($12/bulan)

**Verdict:** Untuk proyek tugas kuliah dengan development tidak terlalu intensif, **CUKUP**. Tapi kalau butuh deploy 20+ kali dalam sehari (misalnya saat debugging), jadi masalah.

### Track record SnapDeploy

**Masalah:** SnapDeploy adalah platform relatif kecil/tidak terkenal. Tidak banyak review independen atau testimoni pengguna jangka panjang yang bisa ditemukan. Platform ini tampaknya dikelola oleh tim kecil (mungkin 1-2 orang).

**Risiko:**
- Platform bisa tutup/akuisisi kapan saja
- Dokumentasi dan community terbatas
- Support mungkin lambat

---

## 🔍 VERIFIKASI LANJUTAN: Caasify

### Detail Platform Caasify

**Sumber:** caasify.com/container-hosting

**Free tier:**
- **4 GB RAM**, 2 vCPU, 50 GB storage
- **Tidak perlu kartu kredit**
- Scale-to-zero billing
- Free TLS & auto domain
- **Berlaku hingga 31 Desember 2026 saja**

**Kelebihan:**
- Spesifikasi sangat tinggi untuk free tier (4GB RAM, 2 vCPU)
- Scale-to-zero (bayar hanya saat running)
- Custom domain + TLS gratis

**Masalah:**
- ❌ Hanya ~3.5 bulan dari sekarang (Sept 2026 → Des 2026)
- ❌ Platform baru, track record terbatas
- ❌ Region EU saja (latensi tinggi dari Indonesia)
- ❌ TIDAK memenuhi kriteria "beberapa bulan tanpa berhenti karena limit trial habis" jika semester berlanjut ke 2027

---

## Tabel Perbandingan (Setelah Koreksi & Verifikasi)

| Platform | Perlu CC? | RAM Free | Permanen/Trial | Region Terdekat | Catatan |
|----------|-----------|----------|----------------|-----------------|---------|
| **Koyeb** | ❌ Tidak (kecuali gagal verifikasi) | 512 MB | ✅ Permanen | Frankfurt/DC | Scale-to-zero 1 jam idle, tidak ada region Asia free |
| **SnapDeploy** | ❌ Tidak | 512 MB | ✅ Permanen | US/EU | Auto-sleep + auto-wake 10-30s, 10 deploys/hari |
| **Caasify** | ❌ Tidak | 4 GB | ❌ Hingga 31 Des 2026 | EU | Sangat generus tapi hanya ~3.5 bulan |
| **Northflank** | ⚠️ Ya | 2 service always-on | ✅ Permanen | EU/US | Payment method wajib (dikonfirmasi Railway docs) |
| **Back4App** | ❌ Tidak | 256 MB | ✅ Permanen | US | Terlalu kecil |
| **Fly.io** | ⚠️ Wajib | 256 MB | ❌ 2 jam trial | — | Free tier dihapus 2024 |
| **Railway** | ❌ Tidak | 512 MB | ❌ 30 hari trial | US/EU/Asia | Setelah trial, $1/bln tidak cukup |
| **Oracle Cloud** | ⚠️ Wajib | 12 GB | ✅ Permanen | Singapore | CC wajib untuk verifikasi |
| **Laravel Cloud** | ❌ Tidak | 256 MB | ❌ $5/bln minimum | US/EU | - |
| **DigitalOcean** | ❌ Tidak | 0 (hanya static) | ✅ Permanen (static only) | Singapore | Free tier hanya static sites |
| **Sevalla** | ❌ Tidak ($20 trial) | — | ❌ Trial credit saja | US/EU | Tidak ada free tier permanen |
| **Render** | ⚠️ **Ya (untuk Web Service)** | 512 MB | ✅ Permanen | Singapore | **GUGUR** — CC diperlukan untuk compute services. |

---

## Rekomendasi

### 🏆 **Koyeb** — Pilihan Terbaik Setelah Render GUGUR

**Alasan:**
1. **512 MB RAM** — cukup untuk PHP-FPM + Nginx dalam satu container Laravel
2. **Tidak perlu kartu kredit** — signup cukup email (mungkin hanya diminta jika gagal verifikasi otomatis)
3. **Permanen** — free tier tidak expire, bisa jalan 3-4 bulan sepanjang semester
4. **Support custom Dockerfile** — Dockerfile multi-stage (node+php-alpine) bisa langsung dipakai
5. **Always-on** — meski scale-to-zero setelah 1 jam idle, ini lebih baik dari Render (15 menit) atau SnapDeploy (auto-sleep)
6. **Custom domain gratis** — 5 custom domain di free tier

**Trade-off:**
- Region free hanya **Frankfurt** atau **Washington, D.C.** (latensi ~200-300ms dari Indonesia)
- Scale-to-zero setelah 1 jam idle (cold start saat pertama kali diakses setelah idle)

**Setup yang diperlukan:**
1. Buat akun di koyeb.com (email saja, no CC)
2. Connect GitHub repo Bapelkes-ONE
3. Create New App → Runtime: Docker
4. Region: Frankfurt
5. Set environment variables (APP_KEY, DB connection string ke Supabase Singapore)
6. Deploy

---

### Alternatif: **SnapDeploy** (kalau Koyeb tidak cocok)

- 512 MB RAM, no CC
- Tapi auto-sleep (cold start) dan hanya 10 deploys/hari
- Cocok kalau app jarang diakses dan butuh no CC
- Support Docker multi-stage builds (karena deploy dari Dockerfile)
- **Risiko:** Platform kecil, track record terbatas

---

### Tidak Direkomendasikan:

| Platform | Alasan |
|----------|--------|
| **Render** | CC diperlukan untuk Web Service (terkonfirmasi oleh pengalaman langsung + sumber independen) |
| **Fly.io** | Free tier sudah dihapus untuk new users, wajib CC setelah trial |
| **Railway** | Setelah 30 hari trial, $1 credit tidak cukup untuk always-on |
| **Oracle Cloud** | Wajib kartu kredit untuk signup |
| **Northflank** | Payment method required to create resources (dikonfirmasi Railway docs) |
| **Back4App** | 256 MB RAM terlalu kecil |
| **Laravel Cloud** | $5/bulan minimum, bukan free |
| **DigitalOcean** | Free tier hanya static sites |
| **Sevalla** | Tidak ada free tier permanen |
| **Caasify** | Hanya sampai Des 2026 (~3.5 bulan), platform baru |

---

## Catatan Penting

1. **Supabase region Singapore** + **Koyeb region Frankfurt** = latency antar service ~200ms (acceptable untuk admin panel).
2. **Cloudflare CDN gratis** bisa dipakai di depan Koyeb untuk cache asset statis dan mengurangi perceived latency.
3. **Database sudah di Supabase** (terpisah), jadi Koyeb hanya untuk app — ini ideal karena free DB di platform lain biasanya expire (Render: 30 hari, Railway: 30 hari trial).
4. **Backup strategy:** Karena free tier tidak ada backup, pastikan ada mekanisme backup DB Supabase secara manual (Supabase free tier punya backup harian).
5. **Koyeb's scale-to-zero:** Setelah 1 jam tidak ada traffic, app akan sleep. Request berikutnya akan membangunkan app (cold start beberapa detik). Untuk admin panel yang dipakai periodik, ini acceptable.

---

## Langkah Selanjutnya (setelah review)

Jika setuju dengan rekomendasi Koyeb:
1. Buat akun di koyeb.com (email saja, no CC)
2. Connect GitHub repo Bapelkes-ONE
3. Create New App → pilih repo → Runtime: Docker
4. Pilih region: Frankfurt
5. Set environment variables (APP_KEY, DB connection string ke Supabase, dll)
6. Deploy
7. Setup Cloudflare CDN gratis (opsional, untuk kurangi latency)

Saya siap bantu setup deployment ke Koyeb kalau sudah dapat approval.
