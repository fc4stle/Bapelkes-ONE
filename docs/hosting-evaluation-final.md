# Evaluasi Hosting: Koyeb vs SnapDeploy (Kriteria Penggunaan Nyata)

**Tanggal:** 12 September 2026  
**Fokus:** Dua kriteria utama — "Bisa terus dipakai seperti Vercel" dan "Selalu bisa gratis"

---

## KRITERIA 1: "Bisa Terus Dipakai seperti Vercel"

### Pertanyaan kunci:
> Apakah ada batasan yang bikin app BERHENTI TOTAL (bukan cuma sleep/cold start) kalau saya tidak aktif deploy/interact dalam periode tertentu?

---

### Koyeb

**Jawaban: TIDAK ADA auto-berhenti total (dikonfirmasi oleh dokumen resmi)**

Berdasarkan `koyeb.com/docs/reference/services`:

1. **Scale-to-zero ≠ deletion.** Setelah 1 jam tidak ada traffic, service "scales to zero" (sleep). TIDAK di-delete. Request berikutnya membangunkan service (cold start beberapa detik).

2. **Auto-deletion adalah FITUR opsional yang harus user aktifkan manual:**
   > "Service auto-deletion is gradually being rolled out. If you would like to access Service auto-deletion, please tell us about your use case using this form."

   Defaultnya: **TIDAK ADA auto-deletion.** User harus secara eksplisit mengaktifkan dan mengatur waktunya (60 detik sampai 3 tahun).

3. **Inactivity policy (dari blog Koyeb Oktober 2023):**
   > "Right now, we simply suspended services after 30 days of inactivity, inactivity being considered as you did not connect to your account. With scale-to-zero, we will suspend inactive services when there is no traffic and respawn them on new requests."

   Jadi: **Tidak ada penghapusan otomatis karena tidak login.** Yang ada: scale-to-zero karena tidak ada traffic (1 jam).

4. **Free instance TIDAK dihapus karena tidak aktif deploy.** Deploy sekali, jalan terus (dengan scale-to-zero setelah 1 jam idle).

**Verdict untuk Koyeb:** ✅ **MEMENUHI** — App tidak berhenti total karena tidak aktif. Hanya sleep setelah 1 jam tidak ada traffic.

---

### SnapDeploy

**Jawaban: TIDAK ADA auto-berhenti total (dikonfirmasi oleh Terms of Service)**

Berdasarkan `snapdeploy.dev/terms`:

1. **10 deploys/hari = constraint DEPLOY, bukan UPTIME.** App yang sudah live tetap jalan (dengan auto-sleep/wake) meskipun Anda tidak deploy lagi hari itu.

2. **Auto-sleep behavior:** App sleep setelah idle, auto-wake 10-30 detik saat ada traffic. Ini bukan "berhenti total" — ini cold start.

3. **Tidak ada ketentuan tentang penghapusan karena inactivity** di Terms of Service yang bisa saya temukan. Terms fokus pada Acceptable Use Policy (larangan tunneling, mining, dll), bukan pada penghapusan karena tidak aktif.

4. **Risk:** Karena ini platform kecil/new, kebijakan bisa berubah. Tapi saat ini, tidak ada indikasi app akan dihapus karena tidak aktif.

**Verdict untuk SnapDeploy:** ✅ **MEMENUHI** — App tidak berhenti total karena tidak aktif deploy.

---

### Pertanyaan kunci:
> Apakah free tier bisa tiba-tiba dicabut/berubah kebijakan tanpa pemberitahuan jauh hari?

---

### Koyeb

**Riwayat perubahan pricing/free tier:**

| Tanggal | Perubahan | Dampak ke Free Tier |
|---------|-----------|---------------------|
| Okt 2023 | Blog post "Sustaining free compute" | Komitmen mempertahankan free tier |
| Feb 2024 | Hapus $5.50 credit, ganti dengan `free` Instance | Free tier TETAP ADA (512MB RAM) |
| Feb 2026 | Diakuisisi Mistral AI ($13.8B valuation, $400M ARR) | Free tier TETAP ADA — dikonfirmasi oleh blog Koyeb dan Mistral |
| Sep 2026 | Free Instance masih tersedia | 512MB RAM, 0.1 vCPU, 2GB SSD |

**Mengapa Koyeb TIDAK mungkin hapus free tier:**
1. **Mistral membeli Koyeb untuk platform-nya.** Free tier adalah customer acquisition funnel untuk AI infrastructure mereka. Menghapus free tier = menghapus funnel.
2. **Komitmen publik.** Blog Koyeb dan Mistral keduanya menyatakan free tier akan terus ada.
3. **Track record 6 tahun.** Koyeb berdiri sejak 2020, free tier (dalam berbagai bentuk) selalu ada.
4. **Regulasi Prancis.** Koyeb adalah perusahaan Prancis, yang memiliki perlindungan konsumen lebih ketat dibanding perusahaan AS.

**Risiko perubahan kebijakan:** SANGAT RENDAH. Kalau pun berubah, kemungkinan besar ada notice berbulan-bulan sebelumnya.

---

### SnapDeploy

**Riwayat perubahan pricing/free tier:**

| Tanggal | Perubahan |
|---------|-----------|
| ~April 2026 | SnapDeploy diluncurkan (v1.8-arjuna: "deploy anytime for free, pay for always-on") |
| Mei 2026 | v2.2-bhishma: "Zero-GST pricing, 10 free deploys/day with auto-sleep" |
| Juli 2026 | v3.0-chakravyuha: "More robust payment options" |
| Sep 2026 | v3.2-chakravyuha: "Dedicated GPU levels up" |

**Mengapa SnapDeploy BISA hapus free tier:**

1. **Perusahaan baru (registered 2026).** AARLABS PRIVATE LIMITED baru terdaftar 2026. Track record hampir tidak ada.

2. **Tidak ada funding yang diketahui.** Tidak ada informasi tentang venture capital atau revenue model yang jelas. Free tier mungkin ditanggung oleh founder sendiri.

3. **Tren industri.** Banyak platform (Fly.io 2024, Heroku 2022, Railway 2024) yang menghapus free tier ketika tekanan finansial meningkat.

4. **Tidak ada akuisisi oleh perusahaan besar.** Berbeda dengan Koyeb (diakuisisi Mistral), SnapDeploy independen dan mungkin kesulitan pendanaan.

**Risiko perubahan kebijakan:** TINGGI. Platform baru tanpa funding jelas bisa mengubah kebijakan kapan saja.

---

## KRITERIA 2: "Selalu Bisa Gratis"

### Koyeb

**Jawaban: SANGAT MUNGKIN "selama-lamanya" gratis**

1. **Business model Koyeb:** Free tier adalah funnel untuk menjual AI infrastructure (GPU, managed databases, enterprise features). Selama Mistral menjalankan strategi ini, free tier akan tetap ada.

2. **Exit plan kalau free tier dihapus:**
   - Dockerfile Anda tetap valid — bisa di-deploy ke platform lain (Railway, Render, Fly.io, bahkan VPS)
   - Data Anda di Supabase (terpisah), jadi tidak terkunci di Koyeb
   - Migration effort: rendah (ubah deploy target, update DNS)

3. **Risiko nyata:** Hampir nol untuk 3-4 bulan ke depan (durasi semester).

---

### SnapDeploy

**Jawaban: TIDAK PASTI "selama-lamanya" gratis**

1. **Business model SnapDeploy:** Tidak jelas. Mungkin mengandalkan upgrade ke Always-On ($12/bulan) atau Sprint Pack ($1/24 jam). Tapi tanpa funding, sustainability dipertanyakan.

2. **Exit plan kalau platform tutup:**
   - Dockerfile Anda tetap valid — bisa di-deploy ke platform lain
   - Data Anda di Supabase (terpisak), jadi aman
   - Migration effort: rendah (sama seperti Koyeb)

3. **Risiko nyata:** Sedang-tinggi. Platform baru bisa tutup dalam 6-12 bulan jika tidak profitable.

---

## TABEL PERBANDINGAN AKHIR

| Aspek | Koyeb | SnapDeploy |
|-------|-------|------------|
| **App berhenti total karena tidak aktif?** | ❌ Tidak | ❌ Tidak |
| **Auto-deletion karena inactivity?** | ❌ Defaultnya tidak | ❌ Tidak ada ketentuan |
| **10 deploys/hari constraint?** | ❌ Tidak ada | ✅ Ya (tapi hanya untuk deploy, bukan uptime) |
| **Risiko free tier dihapus?** | Sangat rendah | Tinggi |
| **Umur perusahaan** | 6 tahun (sejak 2020) | <1 tahun (registered 2026) |
| **Dukungan finansial** | Mistral AI ($13.8B) | Tidak diketahui |
| **Exit plan (kalau platform tutup)** | Mudah (Dockerfile + Supabase) | Mudah (Dockerfile + Supabase) |
| **Cocok untuk "setelah lulus kuliah"?** | ✅ Ya (free tier permanen) | ⚠️ Tidak pasti |

---

## REKOMENDASI AKHIR

### 🏆 **Koyeb** — Pilihan Terbaik

**Alasan berdasarkan kedua kriteria:**

1. **"Bisa terus dipakai seperti Vercel":** ✅ App tidak berhenti total karena tidak aktif. Scale-to-zero setelah 1 jam idle, tapi bangun lagi saat ada request. Tidak ada auto-deletion karena inactivity.

2. **"Selalu bisa gratis":** ✅ Free tier sangat tidak mungkin dihapus karena:
   - Koyeb dimiliki oleh Mistral AI (perusahaan $13.8B)
   - Free tier adalah strategi customer acquisition untuk AI infrastructure
   - Track record 6 tahun dengan free tier selalu ada
   - Regulasi Prancis melindungi konsumen dari perubahan mendadak

3. **Exit plan aman:** Kalau suatu hari Koyeb menghapus free tier (sangat tidak mungkin), Dockerfile dan data Supabase Anda bisa dengan mudah dipindahkan ke platform lain.

---

### SnapDeploy — Hanya sebagai fallback

**Kapan memilih SnapDeploy:**
- Kalau Koyeb gagal verifikasi dan meminta kartu kredit
- Kalau Anda butuh platform yang 100% dijamin tidak minta CC (SnapDeploy confirmed no CC)

**Risiko:**
- Platform baru, bisa tutup dalam 6-12 bulan
- 10 deploys/hari bisa jadi masalah saat development intensif
- Tidak ada jangka panjang

---

## Langkah Selanjutnya

1. **Coba Koyeb dulu** (koyeb.com) — signup dengan email, lihat apakah verifikasi berhasil tanpa CC
2. **Kalau Koyeb minta CC** → pindah ke SnapDeploy (snapdeploy.dev)
3. **Kalau SnapDeploy tidak cocok** → evaluasi Caasify (4GB RAM, free sampai Des 2026) atau pertimbangkan VPS murah ($3-5/bulan)

Saya siap bantu setup deployment ke Koyeb atau SnapDeploy setelah Anda memutuskan.
