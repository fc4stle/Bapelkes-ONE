<?php

namespace App\Http\Controllers;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\TiyasResponse;
use App\Services\EllipseCalculator;
use App\Services\FuzzyCMeans;
use App\Services\TiyasProfilKlaster;
use App\Services\TiyasScorer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TiyasController extends Controller
{
    use AuthorizesRequests;

    /**
     * Tampilkan form pengisian TIYAS untuk peserta.
     */
    public function form(Pendaftaran $pendaftaran): View
    {
        $this->authorize('isiTiyas', $pendaftaran);

        $config = config('tiyas');

        // Hitung total pertanyaan dari config
        $totalQuestions = 0;
        foreach ($config['konstruk'] as $konstruk) {
            foreach ($konstruk['dimensi'] as $dimensi) {
                $totalQuestions += count($dimensi);
            }
        }

        // Tentukan langkah awal berdasarkan error validasi (jika ada)
        $initialStep = 1;
        $errorsFields = [];

        if ($errors = request()->session()->get('errors')) {
            $errorsFields = array_keys($errors->getMessages());

            // Tentukan langkah berdasarkan field yang error
            if (count($errorsFields) > 0) {
                $firstError = $errorsFields[0];

                // Langkah 1: identitas
                if (in_array($firstError, ['asal_instansi', 'asal_kabupaten', 'jenis_profesi', 'masa_kerja', 'waktu_sejak_selesai'])) {
                    $initialStep = 1;
                } else {
                    // Langkah 2-5: cek konstruk
                    $stepMap = [];
                    $step = 2;
                    foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
                        foreach ($konstruk['dimensi'] as $dimensi) {
                            foreach ($dimensi as $kode => $text) {
                                $stepMap[$kode] = $step;
                            }
                        }
                        $step++;
                    }

                    if (isset($stepMap[$firstError])) {
                        $initialStep = $stepMap[$firstError];
                    }
                }
            }
        }

        return view('tiyas.form', [
            'pendaftaran' => $pendaftaran,
            'config' => $config,
            'totalQuestions' => $totalQuestions,
            'initialStep' => $initialStep,
            'errorsFields' => $errorsFields,
        ]);
    }

    /**
     * Simpan jawaban TIYAS dari peserta.
     */
    public function simpan(Request $request, Pendaftaran $pendaftaran): RedirectResponse
    {
        $this->authorize('isiTiyas', $pendaftaran);

        // Ambil config untuk validasi
        $config = config('tiyas');

        // Susun aturan validasi berdasarkan config
        $rules = [];

        // Validasi identitas (harus sesuai pilihan dari config)
        $rules['asal_instansi'] = 'required|string|in:'.implode(',', $config['identitas']['asal_instansi']);
        $rules['asal_kabupaten'] = 'required|string|in:'.implode(',', $config['identitas']['asal_kabupaten']);
        $rules['jenis_profesi'] = 'required|string|in:'.implode(',', $config['identitas']['jenis_profesi']);
        $rules['masa_kerja'] = 'required|string|in:'.implode(',', $config['identitas']['masa_kerja']);
        $rules['waktu_sejak_selesai'] = 'required|string|in:'.implode(',', $config['identitas']['waktu_sejak_selesai']);

        // Validasi 65 butir (1-4)
        foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
            foreach ($konstruk['dimensi'] as $dimensi) {
                foreach ($dimensi as $kode => $text) {
                    $rules[$kode] = 'required|integer|between:1,4';
                }
            }
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->route('tiyas.form', $pendaftaran)
                ->withErrors($validator)
                ->withInput();
        }
        $validated = $validator->validated();

        // Pisahkan identitas dan jawaban
        $identitas = [
            'asal_instansi' => $validated['asal_instansi'],
            'asal_kabupaten' => $validated['asal_kabupaten'],
            'jenis_profesi' => $validated['jenis_profesi'],
            'masa_kerja' => $validated['masa_kerja'],
            'waktu_sejak_selesai' => $validated['waktu_sejak_selesai'],
        ];

        $jawaban = [];
        foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
            foreach ($konstruk['dimensi'] as $dimensi) {
                foreach ($dimensi as $kode => $text) {
                    if (isset($validated[$kode])) {
                        $jawaban[$kode] = (int) $validated[$kode];
                    }
                }
            }
        }

        // Buat TiyasResponse
        TiyasResponse::create([
            'pendaftaran_id' => $pendaftaran->id,
            'identitas' => $identitas,
            'jawaban' => $jawaban,
            'submitted_at' => now(),
        ]);

        return redirect()->route('pendaftarans.index')
            ->with('success', 'Evaluasi TIYAS berhasil disimpan.');
    }

    /**
     * Tampilkan hasil TIYAS untuk panitia (per pelatihan).
     */
    public function hasil(Pelatihan $pelatihan): View
    {
        // Middleware 'role:panitia' sudah mengecek akses
        $config = config('tiyas');
        $scorer = new TiyasScorer;

        // Ambil semua TiyasResponse untuk pelatihan ini
        $responses = TiyasResponse::whereHas('pendaftaran', function ($q) use ($pelatihan) {
            $q->where('pelatihan_id', $pelatihan->id);
        })->with('pendaftaran.peserta')->get();

        // Hitung profil klaster (FCM)
        $klasterService = new TiyasProfilKlaster($scorer, new FuzzyCMeans);
        $klasterResult = $klasterService->analisis($responses);

        // Hitung statistik deskriptif (tampil untuk semua, termasuk kurang_data)
        $deskriptif = $klasterResult['deskriptif'] ?? [];

        // Ambil PCA dan penjelasan klaster dari hasil analisis
        $pcaData = $klasterResult['pcaData'] ?? null;
        $clusterExplanations = $klasterResult['clusterExplanations'] ?? [];

        // Hitung rata-rata skor per konstruk
        $skorTotal = ['TC' => [], 'TE' => [], 'ITT' => [], 'YB' => []];
        $dataPerPeserta = [];
        $skorDimensiSemuaPeserta = []; // Kumpulkan skor dimensi per peserta

        foreach ($responses as $response) {
            $skor = $scorer->hitungSkor($response->jawaban);
            $skorDim = $scorer->hitungSkorDimensi($response->jawaban);

            // Kumpulkan skor untuk rata-rata
            foreach ($skor as $konstruk => $data) {
                $skorTotal[$konstruk][] = $data['skor'];
            }

            // Kumpulkan skor dimensi
            $skorDimensiSemuaPeserta[] = $skorDim;

            // Data per peserta
            $dataPerPeserta[] = [
                'id' => $response->id,
                'nama' => $response->pendaftaran->peserta->name,
                'skor' => $skor,
            ];
        }

        // Hitung rata-rata skor
        $skorRataRata = [];
        foreach ($skorTotal as $konstruk => $nilai) {
            $skorRataRata[$konstruk] = count($nilai) > 0
                ? round(array_sum($nilai) / count($nilai), 2)
                : 0;
        }

        // Tentukan kategori untuk skor rata-rata
        $skorRataRataKategori = [];
        foreach ($skorRataRata as $konstruk => $nilai) {
            $skorRataRataKategori[$konstruk] = [
                'skor' => $nilai,
                'kategori' => $scorer->kategori($nilai),
            ];
        }

        // Hitung rata-rata per dimensi dari semua peserta
        $rataRataDimensi = [];
        if (! empty($skorDimensiSemuaPeserta)) {
            foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
                $rataRataDimensi[$kodeKonstruk] = [];

                foreach ($konstruk['dimensi'] as $namaDimensi => $butirs) {
                    $nilaiDimensi = [];

                    // Kumpulkan nilai dimensi dari semua peserta
                    foreach ($skorDimensiSemuaPeserta as $skorDim) {
                        if (isset($skorDim[$kodeKonstruk][$namaDimensi])) {
                            $nilaiDimensi[] = $skorDim[$kodeKonstruk][$namaDimensi];
                        }
                    }

                    // Hitung rata-rata dimensi
                    $rataRataDimensi[$kodeKonstruk][$namaDimensi] = count($nilaiDimensi) > 0
                        ? round(array_sum($nilaiDimensi) / count($nilaiDimensi), 2)
                        : 0;
                }
            }
        }

        // Hitung ellipse params per klaster menggunakan EllipseCalculator
        $pcaEllipses = [];
        if ($pcaData && isset($klasterResult['tabel_peserta'])) {
            // Hitung scale untuk SVG
            $points = $pcaData['points'] ?? [];
            if (! empty($points)) {
                $xVals = array_map(fn($p) => $p[0], $points);
                $yVals = array_map(fn($p) => $p[1], $points);
                $xMin = min($xVals);
                $xMax = max($xVals);
                $yMin = min($yVals);
                $yMax = max($yVals);

                $xRange = max(0.1, $xMax - $xMin);
                $yRange = max(0.1, $yMax - $yMin);
                $scale = 250 / max($xRange, $yRange);

                // Fungsi transform: dari koordinat data ke SVG
                $transformX = function($x) use ($xMin, $scale) {
                    return ($x - $xMin) * $scale + 40;
                };
                $transformY = function($y) use ($yMin, $yMax, $yRange, $scale) {
                    return 400 - (($y - $yMin) * $scale + 40);
                };

                // Kelompokkan titik per klaster
                $pointsByCluster = [];
                $tabelPeserta = $klasterResult['tabel_peserta'] ?? [];
                foreach ($tabelPeserta as $idx => $peserta) {
                    $k = $peserta['klaster_dominan'];
                    if (! isset($pointsByCluster[$k])) {
                        $pointsByCluster[$k] = [];
                    }
                    if (isset($points[$idx])) {
                        $pointsByCluster[$k][] = $points[$idx];
                    }
                }
                ksort($pointsByCluster);

                // Hitung ellipse untuk setiap klaster
                foreach ($pointsByCluster as $clusterK => $clusterPoints) {
                    if (count($clusterPoints) >= 1) {
                        try {
                            $ellipseParams = EllipseCalculator::calculate(
                                $clusterPoints,
                                sigmaFactor: 1.5,
                                svgScale: $scale
                            );

                            // Transform cx, cy dari data coordinates ke SVG coordinates
                            $ellipseParams['cx'] = $transformX($ellipseParams['cx']);
                            $ellipseParams['cy'] = $transformY($ellipseParams['cy']);

                            // Penjaga numerik: pastikan tidak ada NaN/INF
                            foreach (['cx', 'cy', 'rx', 'ry', 'angle'] as $key) {
                                if (! is_finite($ellipseParams[$key])) {
                                    $ellipseParams[$key] = 0.0;
                                }
                            }

                            // Minimal 6px untuk rx/ry sudah ditangani di EllipseCalculator
                            $ellipseParams['rx'] = max(6.0, $ellipseParams['rx']);
                            $ellipseParams['ry'] = max(6.0, $ellipseParams['ry']);

                            $pcaEllipses[$clusterK] = $ellipseParams;
                        } catch (\Exception $e) {
                            // Fallback jika EllipseCalculator error
                            $pcaEllipses[$clusterK] = [
                                'cx' => 250.0,
                                'cy' => 200.0,
                                'rx' => 6.0,
                                'ry' => 6.0,
                                'angle' => 0.0,
                            ];
                        }
                    }
                }
            }
        }

        return view('tiyas.hasil', [
            'pelatihan' => $pelatihan,
            'totalResponses' => count($responses),
            'totalDiverifikasi' => $pelatihan->pendaftarans()
                ->where('status_verifikasi', 'diverifikasi')
                ->whereNotNull('hadir_at')
                ->count(),
            'skorRataRata' => $skorRataRataKategori,
            'dataPerPeserta' => $dataPerPeserta,
            'rataRataDimensi' => $rataRataDimensi,
            'klasterResult' => $klasterResult,
            'deskriptif' => $deskriptif,
            'pcaData' => $pcaData,
            'pcaEllipses' => $pcaEllipses,
            'clusterExplanations' => $clusterExplanations,
            'config' => $config,
        ]);
    }

    /**
     * Tampilkan detail TIYAS untuk satu peserta (panitia).
     */
    public function detail(TiyasResponse $tiyasResponse): View
    {
        // Middleware 'role:panitia' sudah mengecek akses
        $scorer = new TiyasScorer;
        $skor = $scorer->hitungSkor($tiyasResponse->jawaban);
        $skorDimensi = $scorer->hitungSkorDimensi($tiyasResponse->jawaban);
        $interpretasi = $scorer->interpretasi($skor);

        return view('tiyas.detail', [
            'tiyasResponse' => $tiyasResponse,
            'skor' => $skor,
            'skorDimensi' => $skorDimensi,
            'interpretasi' => $interpretasi,
            'config' => config('tiyas'),
        ]);
    }

    /**
     * Simpan catatan supervisor dan rekan kerja.
     */
    public function catatan(Request $request, TiyasResponse $tiyasResponse): RedirectResponse
    {
        // Middleware 'role:panitia' sudah mengecek akses
        $validated = $request->validate([
            'catatan_supervisor' => 'nullable|string|max:2000',
            'catatan_rekan_kerja' => 'nullable|string|max:2000',
        ]);

        $tiyasResponse->update([
            'catatan_supervisor' => $validated['catatan_supervisor'] ?? null,
            'catatan_rekan_kerja' => $validated['catatan_rekan_kerja'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Catatan berhasil disimpan.');
    }

    /**
     * Export data TIYAS ke CSV.
     */
    public function export(Pelatihan $pelatihan): StreamedResponse
    {
        // Middleware 'role:panitia' sudah mengecek akses
        $config = config('tiyas');
        $scorer = new TiyasScorer;

        // Ambil semua TiyasResponse untuk pelatihan ini
        $responses = TiyasResponse::whereHas('pendaftaran', function ($q) use ($pelatihan) {
            $q->where('pelatihan_id', $pelatihan->id);
        })->with('pendaftaran.peserta')->get();

        // Buat header CSV
        $headers = ['Nama', 'Email'];

        // Tambah kolom identitas
        foreach ($config['identitas'] as $fieldName => $options) {
            $headers[] = ucfirst(str_replace('_', ' ', $fieldName));
        }

        // Tambah kolom untuk 65 butir
        foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
            foreach ($konstruk['dimensi'] as $dimensi) {
                foreach ($dimensi as $kode => $text) {
                    $headers[] = $kode;
                }
            }
        }

        // Tambah kolom rata-rata konstruk
        $headers[] = 'TC';
        $headers[] = 'TE';
        $headers[] = 'ITT';
        $headers[] = 'YB';

        return response()->streamDownload(function () use ($responses, $config, $scorer, $headers) {
            $fp = fopen('php://output', 'w');

            // Tulis header
            fputcsv($fp, $headers);

            // Tulis data
            foreach ($responses as $response) {
                $skor = $scorer->hitungSkor($response->jawaban);
                $row = [
                    $response->pendaftaran->peserta->name,
                    $response->pendaftaran->peserta->email,
                ];

                // Tambah identitas
                foreach (array_keys($config['identitas']) as $fieldName) {
                    $row[] = $response->identitas[$fieldName] ?? '';
                }

                // Tambah jawaban 65 butir
                foreach ($config['konstruk'] as $kodeKonstruk => $konstruk) {
                    foreach ($konstruk['dimensi'] as $dimensi) {
                        foreach ($dimensi as $kode => $text) {
                            $row[] = $response->jawaban[$kode] ?? '';
                        }
                    }
                }

                // Tambah rata-rata skor
                $row[] = $skor['TC']['skor'] ?? '';
                $row[] = $skor['TE']['skor'] ?? '';
                $row[] = $skor['ITT']['skor'] ?? '';
                $row[] = $skor['YB']['skor'] ?? '';

                fputcsv($fp, $row);
            }

            fclose($fp);
        }, 'tiyas_export_'.now()->format('Y-m-d_H-i-s').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
