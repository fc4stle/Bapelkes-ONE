<?php

namespace App\Http\Controllers;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Models\TiyasResponse;
use App\Services\TiyasScorer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('tiyas.form', [
            'pendaftaran' => $pendaftaran,
            'config' => config('tiyas'),
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

        $validated = $request->validate($rules);

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

        return view('tiyas.hasil', [
            'pelatihan' => $pelatihan,
            'totalResponses' => count($responses),
            'totalDiverifikasi' => $pelatihan->pendaftarans()
                ->where('status_verifikasi', 'diverifikasi')
                ->count(),
            'skorRataRata' => $skorRataRataKategori,
            'dataPerPeserta' => $dataPerPeserta,
            'rataRataDimensi' => $rataRataDimensi,
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
