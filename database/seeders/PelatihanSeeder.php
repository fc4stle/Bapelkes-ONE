<?php

namespace Database\Seeders;

use App\Models\Pelatihan;
use Illuminate\Database\Seeder;

class PelatihanSeeder extends Seeder
{
    /**
     * Seed data pelatihan dummy yang realistis untuk kebutuhan Bapelkes DIY,
     * mencakup setiap kombinasi status dan metode agar katalog/dashboard bisa
     * diuji tanpa harus mendaftar manual lewat form.
     */
    public function run(): void
    {
        Pelatihan::query()->delete();

        $data = [
            [
                'nama' => 'Pelatihan Dasar K3 Rumah Sakit',
                'deskripsi' => 'Pelatihan dasar keselamatan dan kesehatan kerja (K3) bagi tenaga kesehatan di lingkungan rumah sakit, mencakup identifikasi bahaya, APD, dan prosedur tanggap darurat.',
                'persyaratan' => 'Peserta minimal adalah tenaga kesehatan (dokter, perawat, bidan, atau tenaga medis lainnya) yang bekerja di fasilitas kesehatan. Surat rekomendasi dari institusi asal diperlukan. Peserta diharapkan membawa alat tulis dan berpakaian profesional.',
                'tanggal_mulai' => now()->addDays(7),
                'tanggal_selesai' => now()->addDays(9),
                'lokasi' => 'Aula Bapelkes DIY, Sleman',
                'kuota' => 40,
                'metode' => 'luring',
                'status' => 'dibuka',
            ],
            [
                'nama' => 'Manajemen Puskesmas Berbasis Digital',
                'deskripsi' => 'Pelatihan manajemen operasional puskesmas yang mengintegrasikan sistem informasi kesehatan digital untuk efisiensi pelayanan.',
                'persyaratan' => 'Peserta adalah pengelola atau kepala puskesmas, serta staf administrasi puskesmas. Pemahaman dasar komputer/laptop diwajibkan. Peserta harus memiliki akses internet yang stabil selama pelatihan berlangsung.',
                'tanggal_mulai' => now()->addDays(14),
                'tanggal_selesai' => now()->addDays(17),
                'lokasi' => 'Online via Zoom',
                'kuota' => 60,
                'metode' => 'daring',
                'status' => 'dibuka',
            ],
            [
                'nama' => 'Gizi Masyarakat dan Pencegahan Stunting',
                'deskripsi' => 'Pelatihan bagi tenaga gizi dan kader kesehatan mengenai deteksi dini dan intervensi gizi untuk pencegahan stunting di wilayah kerja puskesmas.',
                'persyaratan' => 'Peserta adalah gizi, petugas kesehatan, atau kader posyandu di wilayah DIY. Pengalaman minimal 1 tahun di bidang gizi/kesehatan masyarakat. Pendaftaran dilengkapi dengan surat keterangan dari puskesmas setempat.',
                'tanggal_mulai' => now()->addDays(21),
                'tanggal_selesai' => now()->addDays(23),
                'lokasi' => 'Aula Bapelkes DIY, Sleman',
                'kuota' => 35,
                'metode' => 'blended',
                'status' => 'dibuka',
            ],
            [
                'nama' => 'Pelatihan Perawatan Luka Modern',
                'deskripsi' => 'Pelatihan teknik perawatan luka modern (modern wound care) bagi perawat di fasilitas kesehatan primer dan rujukan.',
                'persyaratan' => 'Peserta adalah perawat/bidan bersertifikat dengan pengalaman minimal 2 tahun. Peserta harus membawa notebook/tablet untuk akses materi digital. Lolos pre-test untuk mengukur baseline pengetahuan tentang perawatan luka konvensional.',
                'tanggal_mulai' => now()->addDays(30),
                'tanggal_selesai' => now()->addDays(33),
                'lokasi' => 'Aula Bapelkes DIY, Sleman',
                'kuota' => 25,
                'metode' => 'luring',
                'status' => 'draft',
            ],
            [
                'nama' => 'Komunikasi Efektif Tenaga Kesehatan',
                'deskripsi' => 'Pelatihan komunikasi efektif antara tenaga kesehatan dengan pasien dan keluarga guna meningkatkan kepuasan dan keselamatan pasien.',
                'persyaratan' => 'Semua tenaga kesehatan (dokter, perawat, bidan, farmasis, ahli gizi, dan lainnya) dipersilakan mengikuti. Tidak ada prasyarat pendidikan formal khusus. Peserta diminta aktif berpartisipasi dalam simulasi dan diskusi kelompok.',
                'tanggal_mulai' => now()->subDays(10),
                'tanggal_selesai' => now()->subDays(8),
                'lokasi' => 'Online via Zoom',
                'kuota' => 50,
                'metode' => 'daring',
                'status' => 'selesai',
            ],
            [
                'nama' => 'Pelatihan Pencegahan dan Pengendalian Infeksi (PPI)',
                'deskripsi' => 'Pelatihan PPI dasar untuk tenaga kesehatan di fasilitas pelayanan kesehatan, sesuai standar akreditasi terbaru.',
                'persyaratan' => 'Peserta adalah seluruh tenaga di fasilitas kesehatan (medis, paramedis, administrasi, housekeeping). Surat penugasan dari institusi asal wajib disertakan. Peserta harus menghadiri minimal 80% sesi pelatihan untuk mendapatkan sertifikat.',
                'tanggal_mulai' => now()->subDays(5),
                'tanggal_selesai' => now()->subDays(3),
                'lokasi' => 'Aula Bapelkes DIY, Sleman',
                'kuota' => 30,
                'metode' => 'luring',
                'status' => 'ditutup',
            ],
        ];

        foreach ($data as $pelatihan) {
            Pelatihan::create($pelatihan);
        }
    }
}
