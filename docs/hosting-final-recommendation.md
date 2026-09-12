# Evaluasi Akhir: Hosting Jangka Panjang untuk BAPELKES ONE

**Tanggal:** 12 September 2026  
**Horizon waktu:** 3-5+ tahun (setelah lulus kuliah, portofolio hidup)

---

## ⚠️ TEMUAN KRITIS: Koyeb Free Tier TERTUTUP untuk User Baru

Sumber-sumber dari Februari–Juli 2026 (post-acquisition) secara konsisten melaporkan:

> *"New users will no longer be able to signup for Koyeb's free Starter tier."*
> — Yahoo Finance, kuberns.com, communicationstoday.co.in (Feb 2026)

> *"Post-acquisition, new users can no longer sign up for the Starter tier. The Pro plan at $29/month plus compute is now the entry point."*
> — kuberns.com

Namun pengalaman Alysa bertentangan: signup via GitHub OAuth BERHASIL tanpa CC. Kemungkinan:

1. Kebijakan berubah kembali (buka-tutup) antara Februari dan September 2026
2. Atau "Starter plan" (bernama) berbeda dari "free Instance type" (512MB RAM) — yang tersembunyi dari landing page tapi masih bisa diakses via dashboard
3. Atau akun Alysa dianggap "existing" karena signup terjadi di window transisi

**Status free tier Koyeb saat ini: TIDAK JELAS / BERUBAH-UBAH.**

Ini sendiri adalah RED FLAG untuk kriteria "selama-lama-nya gratis".

---

## Analisis Dua Kriteria Utama

### KRITERIA 1: "Bisa Terus Dipakai seperti Vercel"

#### Koyeb

| Aspek | Status | Risiko |
|-------|--------|--------|
| Auto-stop total karena tidak aktif? | TIDAK (hanya scale-to-zero setelah 1 jam) | ✅ Rendah |
| Auto-deletion karena inactivity? | DEFAULTNYA TIDAK (harus diaktifkan manual) | ✅ Rendah |
| Free tier dicabut tiba-tiba? | **PERNAH** (post-acquisition Feb 2026) | 🔴 TINGGI |
| Dashboard stabil? | **TIDAK** (bug saat ini, Sept 2026) | 🔴 TINGGI |
| Produk diprioritaskan untuk enterprise? | **YA** (pernyataan Mistral) | 🔴 TINGGI |

**Verdict:** 🔴 **RISIKO TINGGI** — Dashboard tidak stabil + kebijakan berubah-ubah pasca-akuisisi.

#### SnapDeploy

| Aspek | Status | Risiko |
|-------|--------|--------|
| Auto-stop total karena tidak aktif? | TIDAK (auto-sleep/wake) | ✅ Rendah |
| Auto-deletion karena inactivity? | TIDAK ADA ketentuan di ToS | ✅ Rendah |
| 10 deploys/hari = constraint uptime? | **TIDAK** — hanya constraint DEPLOY. App yang sudah live tetap jalan. | ✅ Rendah |
| Free tier dicabut tiba-tiba? | **MUNGKIN** — perusahaan baru (<1 tahun), tanpa funding | 🔴 TINGGI |
| Track record | Sangat pendek (~6 bulan) | 🔴 TINGGI |

**Verdict:** 🟡 **RISIKO SEDANG** — Platform terlalu baru, tidak bisa prediksi 3-5 tahun.

---

### KRITERIA 2: "Selalu Bisa Gratis"

#### Pola Historis: Free Tier yang Hilang Setelah Akuisisi

| Platform | Diakquisisi Oleh | Tahun | Free Tier |
|----------|------------------|-------|-----------|
| Heroku | Salesforce | 2012 | Dihapus 2022 |
| Fly.io | (independent) | — | Dihapus 2024 |
| NPM | GitHub (Microsoft) | 2020 | Dihapus 2022 |
| Koyeb | Mistral AI | 2026 | Ditutup untuk user baru (Feb 2026) |

**Pola:** Free tier sering dijadikan "customer acquisition funnel". Setelah tujuannya tercapai (banyak user, atau perusahaan dibeli), free dihapus untuk fokus ke enterprise/paying customers.

**Untuk Koyeb:**
- Mistral membeli Koyeb untuk AI cloud infrastructure, bukan untuk hobby hosting
- Pernyataan Mistral: "focused on enterprise clients going forward"
- Free tier sudah pernah ditutup untuk user baru (Feb 2026), dan meskipun sepertinya masih ada cara masuk, **kapan saja bisa ditutup permanen**

#### Platform dengan Free Tier Paling Stabil (5+ Tahun)

Berdasarkan track record historis:

| Platform | Free Tier Sejak | Pernah Hilang? | Catatan |
|----------|-----------------|----------------|---------|
| **Vercel** | 2018 | TIDAK | Tapi Next.js-focused, tidak bisa Laravel+Docker |
| **Netlify** | 2016 | TIDAK (tapi berubah syarat) | Static-focused, bukan Docker PaaS |
| **Hetzner Cloud** | 2016 | TIDAK (VPS berbayar, buat free tier) | VPS mulai ~€3/bulan, sangat stabil |
| **Oracle Cloud Always Free** | 2020 | BERKURANG (4 OCPU → 2 OCPU, Juni 2026) | Tapi masih ada |

**Pola:** Free tier yang PALING STABLE biasanya:
- Milik perusahaan publik yang menjadikan free tier sebagai marketing strategy (Vercel, Netlify)
- ATAU VPS murah (bukan free tier, tapi biaya kecil dan predictable)

---

## Opsi Baru: Self-Host di VPS

Karena tidak ada opsi gratis yang benar-benar stabil untuk 3-5 tahun, perlu mempertimbangkan:

### VPS + Coolify/Dokploy

| Aspek | Detail |
|-------|--------|
| **Biaya** | $3-5/bulan (Hetzner CX22: 2 vCPU, 4GB RAM, €3.79/bulan) |
| **Stabilitas** | SANGAT TINGGI — VPS tidak pernah "hilang" |
| **Kontrol** | Penuh — bisa migrasi kapan saja |
| **Fitur** | Coolify/Dokploy memberikan UI seperti Vercel/Heroku |
| **Database** | Supabase (gratis) tetap bisa dipakai |
| **Risk** | VPS bisa tutup (bangkrut provider), tapi bisa migrasi ke VPS lain |

### Perbandingan VPS Providers untuk Jangka Panjang

| Provider | Harga | Stabilitas | Track Record |
|----------|-------|------------|--------------|
| **Hetzner** | €3-5/bulan | ⭐⭐⭐⭐⭐ | 10+ tahun, Jerman, sangat stabil |
| **DigitalOcean** | $4-6/bulan | ⭐⭐⭐⭐⭐ | Public company (DOCH), 10+ tahun |
| **Vultr** | $3-5/bulan | ⭐⭐⭐⭐ | 10+ tahun, banyak region |

---

## TABEL PERBANDINGAN AKHIR (Jangka Panjang)

| Platform | Gratis? | Stabilitas 3-5 Tahun | Risiko Hilang | Catatan |
|----------|---------|----------------------|---------------|---------|
| **Koyeb** | Ya (512MB) | 🟡 Sedang | TINGGI | Dashboard bug + kebijakan berubah pasca-akuisisi |
| **SnapDeploy** | Ya (512MB) | 🔴 Rendah | TINGGI | Platform baru, track record ~6 bulan |
| **Hetzner + Coolify** | Tidak (~€3.79/bln) | ⭐⭐⭐⭐⭐ | SANGAT RENDAK | Kontrol penuh, bisa migrasi |
| **DigitalOcean + Coolify** | Tidak (~$4/bln) | ⭐⭐⭐⭐⭐ | SANGAT RENDAK | Public company, sangat stabil |

---

## 🏆 REKOMENDASI AKHIR

### Pilihan 1 (PRIORITAS): **Self-Host di Hetzner + Coolify**

**Alasan jangka panjang:**

1. **Tidak ada yang bisa menghapus akses Anda.** VPS Anda milik Anda, bukan "free tier" yang bisa dicut kapan saja.

2. **Biaya sangat kecil.** €3.79/bulan = ~Rp 65.000/bulan — lebih murah dari paket data bulanan.

3. **Coolify memberikan UX seperti Vercel.** Git-push deploy, auto-SSL, Docker support, database management — semua ada di UI.

4. **Migrasi mudah.** Kalau Hetzner tutup (sangat tidak mungkin), VPS bisa di-export dan di-import ke provider lain.

5. **Track record Hetzner.** Perusahaan Jerman, berdiri sejak 1997, sangat stabil.

**Setup:**
1. Buat akun Hetzner (hetzner.cloud)
2. Buat VPS CX22 (2 vCPU, 4GB RAM, 40GB SSD) — €3.79/bulan
3. Install Coolify via script: `curl -fsSL https://cdn.coollabs.io/coolify/install.sh | bash`
4. Connect GitHub repo → deploy Dockerfile
5. Database tetap di Supabase (gratis)

---

### Pilihan 2 (Fallback Gratis): **SnapDeploy**

**Kalau benar-benar tidak bisa bayar €3/bulan.**

**Risiko:**
- Platform bisa tutup dalam 6-12 bulan
- 10 deploys/hari bisa jadi masalah

**Mitigasi:**
- Source code ada di GitHub → kalau SnapDeploy tutup, bisa self-host di VPS
- Data di Supabase → bisa dipindahkan kapan saja

---

### Pilihan 3: **Koyeb** — TIDAK DIREKOMENDASIKAN untuk jangka panjang

**Alasan:**
- Dashboard saat ini TIDAK STABLE (bug September 2026)
- Kebijakan berubah-ubah pasca-akuisisi
- Mistral fokus ke enterprise, bukan hobby developer
- Free tier sudah pernah ditutup untuk user baru (Feb 2026)

---

## Langkah Selanjutnya

1. **Kalau setuju dengan Hetzner + Coolify** — saya siap bantu:
   - Setup akun Hetzner
   - Install Coolify di VPS
   - Migrasi Dockerfile ke deployment baru
   - Connect ke Supabase

2. **Kalau mau tetap gratis** — kita bisa coba SnapDeploy dulu, tapi siap-siap dengan exit plan kalau platform tutup.

3. **Kalau mau coba perbaiki Koyeb** — bisa kita coba create app via CLI (karena dashboard bug), tapi risiko ke depan tetap tinggi.

Saya rekomendasikan **Pilihan 1 (Hetzner + Coolify)** untuk portofolio hidup yang harus stabil 3-5 tahun ke depan.
