<?php

namespace App\Services;

use App\Models\TiyasResponse;
use Illuminate\Support\Collection;

/**
 * Analisis profil klaster peserta menggunakan Fuzzy C-Means.
 * Input: koleksi TiyasResponse
 * Output: cluster assignment dengan profil dan rekomendasi
 */
class TiyasProfilKlaster
{
    private TiyasScorer $scorer;

    private FuzzyCMeans $fcm;

    public function __construct(TiyasScorer $scorer, FuzzyCMeans $fcm)
    {
        $this->scorer = $scorer;
        $this->fcm = $fcm;
    }

    /**
     * Analisis profil klaster dari respons TIYAS.
     *
     * @param  Collection<TiyasResponse>  $responses  Koleksi TiyasResponse yang sudah submitted
     * @param  int  $cMin  Minimum clusters (default 2)
     * @param  int  $cMax  Maximum clusters (default 4)
     * @return array Status, data klaster, atau pesan kurang data
     */
    public function analisis(Collection $responses, int $cMin = 2, int $cMax = 4): array
    {
        $count = $responses->count();

        // Minimal 8 respons
        if ($count < 8) {
            return [
                'status' => 'kurang_data',
                'jumlah_sekarang' => $count,
                'jumlah_butuh' => 8,
            ];
        }

        // Hitung skor per respons: [TC, TE, ITT, YB]
        $data = [];
        $respons_ids = [];

        foreach ($responses as $respons) {
            $skor = $this->scorer->hitungSkor($respons->jawaban ?? []);

            // Ambil 4 skor utama (urutan: TC, TE, ITT, YB)
            $data[] = [
                $skor['TC']['skor'] ?? 0,
                $skor['TE']['skor'] ?? 0,
                $skor['ITT']['skor'] ?? 0,
                $skor['YB']['skor'] ?? 0,
            ];
            $respons_ids[] = $respons->id;
        }

        // Tentukan jumlah klaster optimal
        try {
            $c_optimal = $this->fcm->pilihJumlahKlaster($data, $cMin, $cMax);
        } catch (\Exception $e) {
            // Fallback: jika terlalu sedikit data untuk c_max
            $c_optimal = min($cMin, count($data) - 1);
            if ($c_optimal < 2) {
                $c_optimal = 2;
            }
        }

        // Fit FCM dengan c optimal
        $result = $this->fcm->fit($data, $c_optimal);
        $centroid = $result['centroid'];
        $U = $result['U'];

        // Hitung validity indices untuk semua c dalam range
        $validity_table = [];
        for ($c = $cMin; $c <= $cMax; $c++) {
            try {
                $indices = $this->fcm->getValidityIndices($data, $c);
                $indices['is_optimal'] = ($c === $c_optimal);
                $validity_table[] = $indices;
            } catch (\Exception $e) {
                // Skip jika c >= n
            }
        }

        // Tentukan profil klaster untuk tiap centroid
        $profil_klaster = [];
        for ($j = 0; $j < $c_optimal; $j++) {
            $profil_klaster[$j] = $this->tentukanProfil($centroid[$j]);
        }

        // Hitung jumlah peserta per klaster (hard assignment)
        $jumlah_peserta = array_fill(0, $c_optimal, 0);
        for ($i = 0; $i < count($U); $i++) {
            $max_j = 0;
            $max_uij = $U[$i][0] ?? 0;
            for ($j = 1; $j < $c_optimal; $j++) {
                if (($U[$i][$j] ?? 0) > $max_uij) {
                    $max_uij = $U[$i][$j];
                    $max_j = $j;
                }
            }
            $jumlah_peserta[$max_j]++;
        }

        // Buat tabel klaster: daftar profil per klaster dengan karakteristik dan rekomendasi
        $tabel_klaster = [];
        for ($j = 0; $j < $c_optimal; $j++) {
            $kode_profil = $profil_klaster[$j];
            $profil_config = config('tiyas.profil_klaster');

            // Tentukan label profil dan rekomendasi dari config
            $label = null;
            $rekomendasi = '';
            if (isset($profil_config[$kode_profil])) {
                $label = $profil_config[$kode_profil]['label'];
                $rekomendasi = $profil_config[$kode_profil]['rekomendasi'];
            } else {
                $label = 'Belum cocok dengan 4 profil konseptual';
                $rekomendasi = '—';
            }

            // Karakteristik: ringkas dari deskripsi
            $karakteristik = '';
            if (isset($profil_config[$kode_profil]['deskripsi'])) {
                $karakteristik = $profil_config[$kode_profil]['deskripsi'];
            }

            // Buat centroid untuk klaster ini sebagai array dengan kunci TC, TE, ITT, YB
            $centroid_klaster = [
                'TC' => $centroid[$j][0] ?? 0,
                'TE' => $centroid[$j][1] ?? 0,
                'ITT' => $centroid[$j][2] ?? 0,
                'YB' => $centroid[$j][3] ?? 0,
            ];

            $tabel_klaster[] = [
                'klaster' => $j + 1, // Nomor klaster 1..c
                'kode_profil' => $kode_profil,
                'profil' => $label,
                'karakteristik' => $karakteristik,
                'rekomendasi' => $rekomendasi,
                'jumlah_peserta' => $jumlah_peserta[$j],
                'centroid' => $centroid_klaster,
            ];
        }

        // Buat tabel peserta dengan derajat keanggotaan
        $tabel_peserta = [];
        foreach ($responses as $i => $respons) {
            $memberships = [];
            for ($j = 0; $j < $c_optimal; $j++) {
                $memberships[$j] = round(($U[$i][$j] ?? 0) * 100, 1);
            }

            // Klaster dominan (hard assignment) — nomor 1..c
            $dominant_cluster = 0;
            $max_membership = $memberships[0];
            for ($j = 1; $j < $c_optimal; $j++) {
                if ($memberships[$j] > $max_membership) {
                    $max_membership = $memberships[$j];
                    $dominant_cluster = $j;
                }
            }

            // Nama peserta dari relasi (eager-loaded di controller)
            $nama = $respons->pendaftaran->peserta->name ?? '—';

            $tabel_peserta[] = [
                'id' => $respons->id,
                'nama' => $nama,
                'memberships' => $memberships,
                'klaster_dominan' => $dominant_cluster + 1, // Nomor 1..c (bukan 0..c-1)
            ];
        }

        return [
            'status' => 'sukses',
            'c_optimal' => $c_optimal,
            'validity_table' => $validity_table,
            'tabel_klaster' => $tabel_klaster,
            'tabel_peserta' => $tabel_peserta,
        ];
    }

    /**
     * Tentukan profil klaster (A, B, C, D) berdasarkan centroid.
     *
     * @param  array  $centroid  Vektor [TC, TE, ITT, YB]
     * @return string Label profil (A, B, C, D, atau 'Belum Cocok')
     */
    private function tentukanProfil(array $centroid): string
    {
        if (count($centroid) < 4) {
            return 'Belum Cocok dengan 4 profil konseptual';
        }

        $tc = $centroid[0];
        $te = $centroid[1];
        $itt = $centroid[2];
        $yb = $centroid[3];

        $level_tc = $this->kategoriSkor($tc);
        $level_te = $this->kategoriSkor($te);
        $level_itt = $this->kategoriSkor($itt);
        $level_yb = $this->kategoriSkor($yb);

        // A Optimal: TC, TE, ITT, YB semuanya Tinggi
        if ($level_tc === 'Tinggi' && $level_te === 'Tinggi' && $level_itt === 'Tinggi' && $level_yb === 'Tinggi') {
            return 'A';
        }

        // B Developing: TC, TE, ITT Tinggi, YB bukan Tinggi
        if ($level_tc === 'Tinggi' && $level_te === 'Tinggi' && $level_itt === 'Tinggi' && $level_yb !== 'Tinggi') {
            return 'B';
        }

        // C Constrained: TC dan TE Rendah/Sedang, ITT Tinggi, YB Rendah/Sedang
        if (
            in_array($level_tc, ['Rendah', 'Sedang']) &&
            in_array($level_te, ['Rendah', 'Sedang']) &&
            $level_itt === 'Tinggi' &&
            in_array($level_yb, ['Rendah', 'Sedang'])
        ) {
            return 'C';
        }

        // D At-Risk: semuanya Rendah
        if ($level_tc === 'Rendah' && $level_te === 'Rendah' && $level_itt === 'Rendah' && $level_yb === 'Rendah') {
            return 'D';
        }

        return 'Belum Cocok dengan 4 profil konseptual';
    }

    /**
     * Kategorisasi skor berdasarkan batas kategori config.
     *
     * @param  float  $skor  Skor 1.00-4.00
     * @return string 'Rendah', 'Sedang', atau 'Tinggi'
     */
    private function kategoriSkor(float $skor): string
    {
        return $this->scorer->kategori($skor);
    }
}
