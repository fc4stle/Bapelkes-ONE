<?php

/*
 * Instrumen Model TIYAS (Training Implementation and Yielded Behavior
 * Evaluation System) — Kuesioner Model TIYAS, Lampiran 2 proposal disertasi
 * Yudi Andriyaningtiyas. Butir dan urutan sama persis dengan instrumen asli.
 *
 * Skala: 4 kategori tanpa pilihan tengah.
 *  - TC, TE, ITT : kesesuaian (1 = Sangat Tidak Sesuai ... 4 = Sangat Sesuai)
 *  - YB          : frekuensi  (1 = Tidak Pernah ... 4 = Selalu)
 */

return [

    'skala' => [
        'kesesuaian' => [
            1 => 'Sangat Tidak Sesuai',
            2 => 'Tidak Sesuai',
            3 => 'Sesuai',
            4 => 'Sangat Sesuai',
        ],
        'frekuensi' => [
            1 => 'Tidak Pernah',
            2 => 'Jarang',
            3 => 'Sering',
            4 => 'Selalu',
        ],
    ],

    // Batas kategori skor rata-rata (1.00-4.00). Ini batas bawaan aplikasi
    // (interval sama), bisa diganti sesuai pedoman interpretasi TIYAS.
    'kategori' => [
        ['maks' => 2.00, 'label' => 'Rendah'],
        ['maks' => 3.00, 'label' => 'Sedang'],
        ['maks' => 4.00, 'label' => 'Tinggi'],
    ],

    'konstruk' => [

        'TC' => [
            'nama' => 'Transfer Climate',
            'judul' => 'Iklim Transfer',
            'bagian' => 'A',
            'skala' => 'kesesuaian',
            'petunjuk' => 'Pilih angka yang paling sesuai dengan kondisi tempat kerja Anda saat ini.',
            'dimensi' => [
                'Dukungan Supervisor' => [
                    'TC1' => 'Supervisor saya mendorong penerapan hasil pelatihan di tempat kerja.',
                    'TC2' => 'Supervisor saya memberikan arahan ketika saya menerapkan kompetensi hasil pelatihan.',
                    'TC3' => 'Supervisor saya mendiskusikan kemajuan penerapan hasil pelatihan dengan saya.',
                    'TC4' => 'Supervisor saya memberikan apresiasi atas keberhasilan saya menerapkan hasil pelatihan.',
                    'TC5' => 'Supervisor saya membantu mengatasi hambatan dalam penerapan hasil pelatihan.',
                ],
                'Dukungan Rekan Kerja' => [
                    'TC6' => 'Rekan kerja saya mendukung penerapan kompetensi hasil pelatihan.',
                    'TC7' => 'Rekan kerja saya bersedia berdiskusi mengenai penerapan hasil pelatihan.',
                    'TC8' => 'Rekan kerja saya membantu ketika saya mengalami kesulitan menerapkan hasil pelatihan.',
                    'TC9' => 'Rekan kerja saya menerima perubahan cara kerja yang saya terapkan dari pelatihan.',
                    'TC10' => 'Rekan kerja saya mendorong agar hasil pelatihan diterapkan secara berkelanjutan.',
                ],
                'Umpan Balik dan Kesempatan Berlatih' => [
                    'TC11' => 'Saya menerima masukan tentang ketepatan penerapan hasil pelatihan saya.',
                    'TC12' => 'Saya memperoleh kesempatan menerapkan hasil pelatihan dalam pekerjaan sehari-hari.',
                    'TC13' => 'Saya memperoleh kesempatan mencoba kompetensi baru secara langsung dalam pelayanan.',
                    'TC14' => 'Penerapan hasil pelatihan saya dipantau setelah saya kembali bekerja.',
                    'TC15' => 'Saya memperoleh kesempatan memperbaiki cara penerapan setelah menerima masukan.',
                ],
            ],
        ],

        'TE' => [
            'nama' => 'Transfer Environment',
            'judul' => 'Lingkungan Transfer',
            'bagian' => 'B',
            'skala' => 'kesesuaian',
            'petunjuk' => 'Pilih angka yang paling sesuai dengan kondisi tempat kerja Anda saat ini.',
            'dimensi' => [
                'Ketersediaan Sumber Daya' => [
                    'TE1' => 'Alat kerja yang diperlukan untuk menerapkan hasil pelatihan tersedia di unit kerja saya.',
                    'TE2' => 'Bahan habis pakai yang diperlukan untuk menerapkan hasil pelatihan tersedia di unit kerja saya.',
                    'TE3' => 'Fasilitas kerja memadai untuk menerapkan kompetensi hasil pelatihan.',
                    'TE4' => 'Dukungan biaya operasional di unit kerja saya memadai untuk menerapkan hasil pelatihan.',
                    'TE5' => 'Sistem pencatatan dan pelaporan di unit kerja saya mendukung penerapan hasil pelatihan.',
                ],
                'Kebijakan Organisasi' => [
                    'TE6' => 'Kebijakan organisasi saya mendukung penerapan hasil pelatihan dalam pekerjaan.',
                    'TE7' => 'Standar Operasional Prosedur (SOP) di unit kerja saya mendukung penerapan kompetensi hasil pelatihan.',
                    'TE8' => 'Organisasi saya menetapkan target kinerja yang selaras dengan kompetensi hasil pelatihan.',
                    'TE9' => 'Uraian tugas jabatan saya memuat kompetensi yang diperoleh dari pelatihan.',
                    'TE10' => 'Hasil pelatihan diintegrasikan ke dalam pelaksanaan program atau kegiatan di unit kerja saya.',
                ],
                'Budaya Kerja yang Mendukung Transfer' => [
                    'TE11' => 'Budaya kerja di unit saya mendorong penerapan hasil pelatihan.',
                    'TE12' => 'Lingkungan kerja di unit saya terbuka terhadap cara kerja baru.',
                    'TE13' => 'Pembagian beban kerja di unit saya memungkinkan pegawai menerapkan hasil pelatihan.',
                    'TE14' => 'Unit kerja saya memberi ruang waktu bagi pegawai untuk menerapkan hasil pelatihan.',
                    'TE15' => 'Unit kerja saya menghargai cara kerja yang sesuai dengan standar terbaru.',
                ],
            ],
        ],

        'ITT' => [
            'nama' => 'Intention to Transfer',
            'judul' => 'Niat Transfer',
            'bagian' => 'C',
            'skala' => 'kesesuaian',
            'petunjuk' => 'Pilih angka yang paling sesuai dengan niat Anda untuk melanjutkan penerapan hasil pelatihan dalam tiga bulan ke depan.',
            'dimensi' => [
                'Niat Kognitif' => [
                    'ITT1' => 'Saya telah menentukan cara menerapkan hasil pelatihan pada tugas saya.',
                    'ITT2' => 'Saya memiliki rencana tindak lanjut untuk melanjutkan penerapan hasil pelatihan.',
                    'ITT3' => 'Saya telah menetapkan waktu penerapan hasil pelatihan.',
                    'ITT4' => 'Saya telah mengidentifikasi bagian pekerjaan yang akan terus saya perbaiki berdasarkan hasil pelatihan.',
                    'ITT5' => 'Saya telah menetapkan kompetensi hasil pelatihan yang menjadi prioritas untuk saya terapkan.',
                ],
                'Niat Afektif' => [
                    'ITT6' => 'Saya memiliki keinginan kuat untuk terus menerapkan hasil pelatihan.',
                    'ITT7' => 'Saya yakin penerapan hasil pelatihan bermanfaat bagi kualitas pelayanan saya.',
                    'ITT8' => 'Saya menganggap penerapan hasil pelatihan penting bagi pekerjaan saya.',
                    'ITT9' => 'Saya ingin hasil pelatihan menjadi bagian dari cara kerja saya sehari-hari.',
                    'ITT10' => 'Saya yakin hasil pelatihan layak terus saya terapkan dalam pekerjaan.',
                ],
                'Niat Volitif' => [
                    'ITT11' => 'Saya siap melanjutkan penerapan hasil pelatihan dalam pekerjaan.',
                    'ITT12' => 'Saya bertekad tetap menerapkan hasil pelatihan meskipun menghadapi hambatan.',
                    'ITT13' => 'Saya akan mencari cara lain apabila penerapan hasil pelatihan menemui kendala.',
                    'ITT14' => 'Saya tetap mencari kesempatan menerapkan hasil pelatihan meskipun tidak diminta.',
                    'ITT15' => 'Saya berniat mempertahankan penerapan hasil pelatihan dalam jangka panjang.',
                ],
            ],
        ],

        'YB' => [
            'nama' => 'Yielded Behavior',
            'judul' => 'Perilaku Pascapelatihan',
            'bagian' => 'D',
            'skala' => 'frekuensi',
            'petunjuk' => 'Pilih angka yang paling sesuai dengan perilaku kerja Anda sejak mengikuti pelatihan.',
            'dimensi' => [
                'Penerapan SOP dan Prosedur Kerja' => [
                    'YB1' => 'Saya menerapkan prosedur kerja yang dipelajari dari pelatihan.',
                    'YB2' => 'Saya menggunakan SOP hasil pelatihan dalam pekerjaan.',
                    'YB3' => 'Saya memastikan tindakan kerja sesuai standar yang diperoleh dari pelatihan.',
                    'YB4' => 'Saya mendokumentasikan pekerjaan sesuai standar yang diperoleh dari pelatihan.',
                    'YB5' => 'Saya memperbaiki cara kerja yang tidak sesuai dengan standar baru.',
                ],
                'Kemandirian Pengambilan Keputusan' => [
                    'YB6' => 'Saya membuat keputusan kerja secara mandiri berdasarkan kompetensi hasil pelatihan.',
                    'YB7' => 'Saya menggunakan pengetahuan hasil pelatihan sebagai dasar pengambilan keputusan.',
                    'YB8' => 'Saya menyelesaikan permasalahan kerja menggunakan kompetensi hasil pelatihan.',
                    'YB9' => 'Saya menentukan langkah kerja yang tepat berdasarkan penilaian situasi sesuai hasil pelatihan.',
                    'YB10' => 'Saya mengambil inisiatif dalam pelaksanaan pekerjaan sesuai kompetensi hasil pelatihan.',
                ],
                'Kolaborasi dan Interaksi Kerja' => [
                    'YB11' => 'Saya berkomunikasi dengan pasien atau masyarakat menggunakan cara yang dipelajari dari pelatihan.',
                    'YB12' => 'Saya menyampaikan informasi pekerjaan secara jelas kepada anggota tim saat koordinasi pelayanan sesuai hasil pelatihan.',
                    'YB13' => 'Saya berkoordinasi dengan rekan kerja dalam menerapkan hasil pelatihan.',
                    'YB14' => 'Saya berpartisipasi aktif dalam penerapan hasil pelatihan bersama tim kerja.',
                    'YB15' => 'Saya menjalin kerja sama dengan pihak terkait untuk mendukung penerapan hasil pelatihan.',
                ],
                'Diseminasi Keterampilan kepada Rekan Kerja' => [
                    'YB16' => 'Saya berbagi keterampilan yang diperoleh dari pelatihan kepada rekan kerja.',
                    'YB17' => 'Saya membimbing rekan kerja dalam menerapkan prosedur kerja baru.',
                    'YB18' => 'Saya memberikan penjelasan mengenai penerapan hasil pelatihan kepada rekan kerja.',
                    'YB19' => 'Saya menjawab pertanyaan rekan kerja terkait penerapan hasil pelatihan.',
                    'YB20' => 'Saya mendorong rekan kerja untuk menerapkan hasil pelatihan dalam pekerjaan.',
                ],
            ],
        ],
    ],

    // Identitas responden (Lampiran 2). Nama dan pelatihan diambil dari akun
    // dan pendaftaran, jadi tidak ditanyakan lagi.
    'identitas' => [
        'asal_instansi' => ['Dinas Kesehatan', 'Rumah Sakit', 'Puskesmas', 'Lainnya'],
        'asal_kabupaten' => ['Kota Yogyakarta', 'Sleman', 'Bantul', 'Kulon Progo', 'Gunungkidul'],
        'jenis_profesi' => ['Dokter', 'Perawat', 'Bidan', 'Tenaga Gizi', 'Tenaga Kesehatan Masyarakat', 'Tenaga Sanitasi Lingkungan', 'Lainnya'],
        'masa_kerja' => ['1–2 tahun', '3–5 tahun', '>5 tahun'],
        'waktu_sejak_selesai' => ['3–6 bulan', '7–12 bulan'],
    ],

    // Profil Klaster Peserta berdasarkan Fuzzy C-Means (Tabel 18 proposal)
    // Levelisasi: Rendah < 2.00, Sedang 2.00–3.00, Tinggi > 3.00
    'profil_klaster' => [
        'A' => [
            'label' => 'A: Optimal Transfer',
            'deskripsi' => 'Dukungan organisasi dan lingkungan kerja kondusif, peserta memiliki niat transfer yang kuat serta mampu menerapkan hasil pelatihan secara konsisten.',
            'rekomendasi' => 'Dipertahankan sebagai role model, mentor, atau fasilitator praktik baik serta diprioritaskan dalam program pengembangan kompetensi lanjutan.',
        ],
        'B' => [
            'label' => 'B: Developing Transfer',
            'deskripsi' => 'Dukungan organisasi dan niat transfer telah terbentuk, namun penerapan perilaku kerja belum optimal.',
            'rekomendasi' => 'Coaching pascapelatihan, supervisi berkala, monitoring RTL, dan pemberian umpan balik berkelanjutan.',
        ],
        'C' => [
            'label' => 'C: Constrained Transfer',
            'deskripsi' => 'Peserta memiliki niat kuat untuk mentransfer hasil pelatihan, tetapi implementasi terhambat oleh lingkungan kerja dan dukungan organisasi yang kurang memadai.',
            'rekomendasi' => 'Penguatan dukungan pimpinan, penyediaan sumber daya, pembentukan community of practice, dan perbaikan transfer climate.',
        ],
        'D' => [
            'label' => 'D: At-Risk Transfer',
            'deskripsi' => 'Dukungan organisasi, lingkungan kerja, niat transfer, dan perubahan perilaku sama-sama rendah sehingga berisiko gagal mentransfer hasil pelatihan.',
            'rekomendasi' => 'Refreshment training, coaching individual, peningkatan efikasi diri dan motivasi, serta perbaikan kebijakan organisasi dan sistem supervisi.',
        ],
    ],
];
