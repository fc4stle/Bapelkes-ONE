<?php

namespace App\Services;

class TiyasProfilKlaster
{
    private $scorer;

    private $fcm;

    public function __construct(TiyasScorer $scorer, FuzzyCMeans $fcm)
    {
        $this->scorer = $scorer;
        $this->fcm = $fcm;
    }

    /**
     * Analisis FCM pada respons TIYAS.
     *
     * @param  array  $tiyasResponses
     * @return array Status, c_optimal, tabel_klaster, tabel_peserta, indeks validitas
     */
    public function analisis($tiyasResponses)
    {
        $n = count($tiyasResponses);
        if ($n < 8) {
            return [
                'status' => 'kurang_data',
                'c_optimal' => 0,
                'jumlah_sekarang' => $n,
                'jumlah_butuh' => 8,
                'tabel_klaster' => [],
                'tabel_peserta' => [],
                'validity_table' => [],
                'iteration' => 0,
                'fuzzy_partition_coefficient' => 0,
                'normalized_classification_entropy' => 0,
                'deskriptif' => $this->deskriptif($tiyasResponses),
                'clusterExplanations' => [],
            ];
        }

        // Ekstrak data 4D dengan scoring dari jawaban
        $data = [];
        $names = [];
        $scores = [];
        foreach ($tiyasResponses as $resp) {
            $names[] = $resp->pendaftaran->peserta->name;
            $skor = $this->scorer->hitungSkor($resp->jawaban ?? []);
            $scores[] = $skor;
            $data[] = [
                floatval($skor['TC']['skor'] ?? 2.0),
                floatval($skor['TE']['skor'] ?? 2.0),
                floatval($skor['ITT']['skor'] ?? 2.0),
                floatval($skor['YB']['skor'] ?? 2.0),
            ];
        }

        // Pilih jumlah klaster optimal menggunakan FuzzyCMeans::pilihJumlahKlaster()
        $cMin = 2;
        $cMax = min(4, (int) ceil($n / 2));
        $bestC = $this->fcm->pilihJumlahKlaster($data, $cMin, $cMax);

        // Fit FCM dengan c optimal
        $bestResult = $this->fcm->fit($data, $bestC, $m = 2, $epsilon = 1e-5, $maxIter = 100);
        $U = $bestResult['U'];
        $V = $bestResult['centroid'];
        $iter = $bestResult['iterations'];

        // Bangun validity table dari semua c (cMin hingga cMax)
        $validityTable = [];
        for ($c = $cMin; $c <= $cMax; $c++) {
            $indices = $this->fcm->getValidityIndices($data, $c);
            $validityTable[] = [
                'c' => $c,
                'pc' => $indices['pc'],
                'pe' => $indices['pe'],
                'xb' => $indices['xb'],
                'silhouette' => $indices['silhouette'],
                'iteration' => ($c === $bestC) ? $iter : 0,
                'is_optimal' => ($c === $bestC),
            ];
        }

        // Bangun tabel klaster dulu untuk tahu profil setiap cluster
        $tabelKlaster = [];
        $centroids_raw = [];
        $profilPerKlaster = []; // Map cluster -> profil info
        $klasterPesertaCount = [];

        for ($k = 0; $k < $bestC; $k++) {
            $centroid = $V[$k];
            $centroids_raw[] = $centroid;
            $profil = $this->determineProfil($centroid);
            $profilPerKlaster[$k + 1] = $profil;
            $klasterPesertaCount[$k + 1] = 0;

            $tabelKlaster[] = [
                'klaster' => $k + 1,
                'kode_profil' => $profil['code'],
                'profil' => $profil['label'],
                'karakteristik' => $profil['karakteristik'],
                'rekomendasi' => $profil['rekomendasi'],
                'jumlah_peserta' => 0, // Akan diisi dari tabel peserta
                'centroid' => [
                    'TC' => number_format($centroid[0], 2),
                    'TE' => number_format($centroid[1], 2),
                    'ITT' => number_format($centroid[2], 2),
                    'YB' => number_format($centroid[3], 2),
                ],
            ];
        }

        // Bangun tabel peserta dengan profil dominan (kode + label)
        $tabelPeserta = [];
        for ($i = 0; $i < $n; $i++) {
            $klasterDominan = 0;
            $maxU = 0;
            $memberships = [];
            for ($k = 0; $k < $bestC; $k++) {
                $memberships[$k + 1] = round($U[$i][$k] * 100, 1); // Convert to percentage
                if ($U[$i][$k] > $maxU) {
                    $maxU = $U[$i][$k];
                    $klasterDominan = $k + 1;
                }
            }

            $profilDominan = $profilPerKlaster[$klasterDominan] ?? [];

            $tabelPeserta[] = [
                'nama' => $names[$i],
                'skor_raw' => $data[$i], // Tambah skor raw (array [TC, TE, ITT, YB])
                'klaster_dominan' => $klasterDominan,
                'kode_profil_dominan' => $profilDominan['code'] ?? null,
                'label_profil_dominan' => $profilDominan['label'] ?? 'Belum cocok',
                'memberships' => $memberships,
            ];

            // Hitung jumlah peserta per klaster berdasarkan dominan
            $klasterPesertaCount[$klasterDominan]++;
        }

        // Update jumlah peserta di tabel klaster
        foreach ($tabelKlaster as &$klaster) {
            $klaster['jumlah_peserta'] = $klasterPesertaCount[$klaster['klaster']] ?? 0;
        }

        // Hitung PC dan PE untuk summary (dari best result)
        $result_best = $this->fcm->getValidityIndices($data, $bestC);

        return [
            'status' => 'sukses',
            'c_optimal' => $bestC,
            'tabel_klaster' => $tabelKlaster,
            'tabel_peserta' => $tabelPeserta,
            'validity_table' => $validityTable,
            'iteration' => $iter,
            'fuzzy_partition_coefficient' => $result_best['pc'],
            'normalized_classification_entropy' => $result_best['pe'],
            'centroids_raw' => $centroids_raw,
            'deskriptif' => $this->deskriptif($tiyasResponses),
            'clusterExplanations' => $this->generateClusterExplanations($tabelKlaster, $tabelPeserta, $n),
        ];
    }

    /**
     * Determine profil (A/B/C/D/Belum cocok) from centroid using TiyasScorer::kategori().
     */
    private function determineProfil($centroid)
    {
        $tc = $centroid[0];
        $te = $centroid[1];
        $itt = $centroid[2];
        $yb = $centroid[3];

        // Kategorisasi setiap skor menggunakan TiyasScorer
        $kategoriTC = $this->scorer->kategori($tc);
        $kategoriTE = $this->scorer->kategori($te);
        $kategoriITT = $this->scorer->kategori($itt);
        $kategoriYB = $this->scorer->kategori($yb);

        $config = config('tiyas');
        $profilConfig = $config['profil_klaster'] ?? [];

        // Tentukan profil berdasarkan aturan kombinasi kategori
        // A: TC,TE,ITT,YB semuanya Tinggi
        if ($kategoriTC === 'Tinggi' && $kategoriTE === 'Tinggi' && $kategoriITT === 'Tinggi' && $kategoriYB === 'Tinggi') {
            $code = 'A';
        }
        // B: TC,TE,ITT Tinggi dan YB bukan Tinggi
        elseif ($kategoriTC === 'Tinggi' && $kategoriTE === 'Tinggi' && $kategoriITT === 'Tinggi' && $kategoriYB !== 'Tinggi') {
            $code = 'B';
        }
        // C: TC dan TE Rendah/Sedang, ITT Tinggi, YB Rendah/Sedang
        elseif (
            in_array($kategoriTC, ['Rendah', 'Sedang']) &&
            in_array($kategoriTE, ['Rendah', 'Sedang']) &&
            $kategoriITT === 'Tinggi' &&
            in_array($kategoriYB, ['Rendah', 'Sedang'])
        ) {
            $code = 'C';
        }
        // D: semuanya Rendah
        elseif ($kategoriTC === 'Rendah' && $kategoriTE === 'Rendah' && $kategoriITT === 'Rendah' && $kategoriYB === 'Rendah') {
            $code = 'D';
        } else {
            $code = null;
        }

        if ($code && isset($profilConfig[$code])) {
            $label = $profilConfig[$code]['label'] ?? $code;
            $karakteristik = $profilConfig[$code]['deskripsi'] ?? '';
            $rekomendasi = $profilConfig[$code]['rekomendasi'] ?? '';
        } else {
            $label = 'Belum cocok dengan 4 profil konseptual';
            $karakteristik = 'Pola data tidak sesuai dengan profil A-D yang didefinisikan.';
            $rekomendasi = 'Tinjau ulang kriteria profil atau data responsnya.';
        }

        return [
            'code' => $code,
            'label' => $label,
            'karakteristik' => $karakteristik,
            'rekomendasi' => $rekomendasi,
        ];
    }

    /**
     * Statistik deskriptif per konstruk.
     *
     * @param  array  $tiyasResponses
     * @return array ['TC' => [...], 'TE' => [...], ...] dengan mean, stdev, min, max, kategori
     */
    public function deskriptif($tiyasResponses)
    {
        $konstruk = ['TC' => [], 'TE' => [], 'ITT' => [], 'YB' => []];

        foreach ($tiyasResponses as $resp) {
            $skor = $this->scorer->hitungSkor($resp->jawaban ?? []);
            $konstruk['TC'][] = floatval($skor['TC']['skor'] ?? 2.0);
            $konstruk['TE'][] = floatval($skor['TE']['skor'] ?? 2.0);
            $konstruk['ITT'][] = floatval($skor['ITT']['skor'] ?? 2.0);
            $konstruk['YB'][] = floatval($skor['YB']['skor'] ?? 2.0);
        }

        $config = config('tiyas');
        $kategoriConfig = $config['kategori'] ?? [
            ['maks' => 2.00, 'label' => 'Rendah'],
            ['maks' => 3.00, 'label' => 'Sedang'],
            ['maks' => 4.00, 'label' => 'Tinggi'],
        ];

        $result = [];
        foreach ($konstruk as $name => $values) {
            if (empty($values)) {
                continue;
            }

            $n = count($values);
            $mean = array_sum($values) / $n;
            $variance = array_sum(array_map(fn ($v) => ($v - $mean) ** 2, $values)) / $n;
            $stdev = sqrt($variance);
            $min = min($values);
            $max = max($values);

            // Kategorisasi berdasarkan config
            $kategori = [];
            foreach ($kategoriConfig as $cat) {
                $label = $cat['label'];
                $kategori[$label] = ['count' => 0];
            }

            foreach ($values as $v) {
                $found = false;
                foreach ($kategoriConfig as $cat) {
                    if ($v <= $cat['maks']) {
                        $kategori[$cat['label']]['count']++;
                        $found = true;
                        break;
                    }
                }
            }

            // Hitung persen
            foreach ($kategori as &$k) {
                $k['percent'] = $n > 0 ? round($k['count'] / $n * 100) : 0;
            }

            $result[$name] = [
                'mean' => number_format($mean, 2),
                'stdev' => number_format($stdev, 2),
                'min' => number_format($min, 2),
                'max' => number_format($max, 2),
                'kategori' => $kategori,
            ];
        }

        return $result;
    }

    /**
     * Generate cluster explanations.
     *
     * @param  array  $tabelKlaster
     * @param  array  $tabelPeserta
     * @param  int  $totalPeserta
     * @return array Cluster explanations indexed by cluster number
     */
    public function generateClusterExplanations($tabelKlaster, $tabelPeserta, $totalPeserta)
    {
        $explanations = [];
        foreach ($tabelKlaster as $klaster) {
            $klasterNum = $klaster['klaster'];
            $profil = $klaster['profil'];
            $jumlah = $klaster['jumlah_peserta'];
            $persen = $totalPeserta > 0 ? round($jumlah / $totalPeserta * 100) : 0;

            $centroids = $klaster['centroid'];
            $tc = floatval(str_replace(',', '.', $centroids['TC']));
            $te = floatval(str_replace(',', '.', $centroids['TE']));
            $itt = floatval(str_replace(',', '.', $centroids['ITT']));
            $yb = floatval(str_replace(',', '.', $centroids['YB']));

            $text = "Klaster {$klasterNum} ({$jumlah} peserta, {$persen}%) berprofil {$profil}. "
                  .'Rerata TC '.number_format($tc, 2).', TE '.number_format($te, 2).', '
                  .'ITT '.number_format($itt, 2).', YB '.number_format($yb, 2).'.';

            $explanations[$klasterNum] = $text;
        }

        return $explanations;
    }
}
