<?php

namespace App\Http\Controllers;

use App\Models\Pendaftaran;
use App\Models\Pelatihan;
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
        $rules['identitas'] = 'required|string|in:'.implode(',', array_column($config['identitas'], 'kode'));

        // Validasi 65 butir (1-4)
        foreach ($config['bagian'] as $bagian) {
            foreach ($bagian['dimensi'] as $dimensi) {
                foreach ($dimensi['butir'] as $butir) {
                    $rules[$butir['kode']] = 'required|integer|between:1,4';
                }
            }
        }

        $validated = $request->validate($rules);

        // Pisahkan identitas dan jawaban
        $identitas = ['pilihan' => $validated['identitas']];
        $jawaban = [];

        foreach ($config['bagian'] as $bagian) {
            foreach ($bagian['dimensi'] as $dimensi) {
                foreach ($dimensi['butir'] as $butir) {
                    $jawaban[$butir['kode']] = (int) $validated[$butir['kode']];
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
        $this->authorize('viewTiyasHasil');

        // Ambil semua peserta diverifikasi dengan pelatihan ini
        $pendaftarans = Pendaftaran::where('pelatihan_id', $pelatihan->id)
            ->where('status_verifikasi', 'diverifikasi')
            ->get();

        // Hitung statistik
        $jumlahTerverifikasi = $pendaftarans->count();
        $jumlahSudahMengisi = $pendaftarans->filter(fn ($p) => $p->tiyasResponse()->exists())->count();

        // Ambil semua TiyasResponse untuk pelatihan ini
        $tiyasResponses = TiyasResponse::whereIn('pendaftaran_id', $pendaftarans->pluck('id'))->get();

        // Hitung skor rata-rata per konstruk
        $scorer = new TiyasScorer();
        $skorOverall = ['TC' => [], 'TE' => [], 'ITT' => [], 'YB' => []];

        foreach ($tiyasResponses as $response) {
            $skor = $scorer->hitungSkor($response->jawaban);
            foreach ($skorOverall as $konstruk => &$daftarSkor) {
                $daftarSkor[] = $skor[$konstruk]['skor'];
            }
        }

        // Rata-rata dari rata-rata
        $skorAkhir = [];
        foreach ($skorOverall as $konstruk => $daftarSkor) {
            if (empty($daftarSkor)) {
                $skorAkhir[$konstruk] = ['skor' => 0.0, 'kategori' => 'Tidak Ada Data'];
            } else {
                $rataRata = round(array_sum($daftarSkor) / count($daftarSkor), 2);
                $config = config('tiyas');
                $kategori = $this->tentukanKategori($rataRata, $config);
                $skorAkhir[$konstruk] = ['skor' => $rataRata, 'kategori' => $kategori];
            }
        }

        // Tabel per dimensi rata-rata
        $tabelDimensi = $this->hitungRataRataDimensi($tiyasResponses, $scorer);

        // Data untuk tabel per peserta
        $tabelPeserta = [];
        foreach ($tiyasResponses as $response) {
            $skor = $scorer->hitungSkor($response->jawaban);
            $tabelPeserta[] = [
                'peserta' => $response->pendaftaran->peserta,
                'tiyas_response' => $response,
                'skor' => $skor,
            ];
        }

        return view('tiyas.hasil', [
            'pelatihan' => $pelatihan,
            'jumlahTerverifikasi' => $jumlahTerverifikasi,
            'jumlahSudahMengisi' => $jumlahSudahMengisi,
            'skorAkhir' => $skorAkhir,
            'tabelDimensi' => $tabelDimensi,
            'tabelPeserta' => $tabelPeserta,
            'config' => config('tiyas'),
        ]);
    }

    /**
     * Tampilkan detail TIYAS response untuk panitia.
     */
    public function detail(TiyasResponse $tiyasResponse): View
    {
        $this->authorize('viewTiyasDetail');

        $scorer = new TiyasScorer();
        $skor = $scorer->hitungSkor($tiyasResponse->jawaban);
        $interpretasi = $scorer->interpretasi($skor);

        return view('tiyas.detail', [
            'tiyasResponse' => $tiyasResponse,
            'skor' => $skor,
            'interpretasi' => $interpretasi,
            'config' => config('tiyas'),
        ]);
    }

    /**
     * Simpan catatan supervisor dan rekan kerja (panitia only).
     */
    public function simpanCatatan(Request $request, TiyasResponse $tiyasResponse): RedirectResponse
    {
        $this->authorize('viewTiyasDetail');

        $validated = $request->validate([
            'catatan_supervisor' => 'nullable|string|max:2000',
            'catatan_rekan_kerja' => 'nullable|string|max:2000',
        ]);

        $tiyasResponse->update($validated);

        return back()->with('success', 'Catatan berhasil disimpan.');
    }

    /**
     * Export TIYAS ke CSV untuk panitia.
     */
    public function export(Pelatihan $pelatihan): StreamedResponse
    {
        $this->authorize('viewTiyasHasil');

        // Ambil semua TiyasResponse untuk pelatihan ini
        $pendaftarans = Pendaftaran::where('pelatihan_id', $pelatihan->id)
            ->where('status_verifikasi', 'diverifikasi')
            ->get();

        $tiyasResponses = TiyasResponse::whereIn('pendaftaran_id', $pendaftarans->pluck('id'))
            ->with('pendaftaran.peserta')
            ->get();

        $scorer = new TiyasScorer();
        $config = config('tiyas');

        return response()->streamDownload(function () use ($tiyasResponses, $scorer, $config) {
            $file = fopen('php://output', 'w');

            // BOM untuk UTF-8 Excel
            fwrite($file, "\xEF\xBB\xBF");

            // Header
            $headers = ['Nama', 'Email'];

            // Identitas columns
            foreach ($config['identitas'] as $ident) {
                $headers[] = $ident['label'];
            }

            // 65 kolom butir
            foreach ($config['bagian'] as $bagian) {
                foreach ($bagian['dimensi'] as $dimensi) {
                    foreach ($dimensi['butir'] as $butir) {
                        $headers[] = $butir['kode'];
                    }
                }
            }

            // Rata-rata per konstruk
            $headers[] = 'Rata-Rata TC';
            $headers[] = 'Rata-Rata TE';
            $headers[] = 'Rata-Rata ITT';
            $headers[] = 'Rata-Rata YB';

            fputcsv($file, $headers);

            // Data rows
            foreach ($tiyasResponses as $response) {
                $row = [
                    $response->pendaftaran->peserta->name,
                    $response->pendaftaran->peserta->email,
                ];

                // Identitas values (sesuai urutan header)
                foreach ($config['identitas'] as $ident) {
                    $row[] = $response->identitas['pilihan'] ?? '';
                }

                // 65 jawaban
                foreach ($config['bagian'] as $bagian) {
                    foreach ($bagian['dimensi'] as $dimensi) {
                        foreach ($dimensi['butir'] as $butir) {
                            $row[] = $response->jawaban[$butir['kode']] ?? '';
                        }
                    }
                }

                // Rata-rata per konstruk
                $skor = $scorer->hitungSkor($response->jawaban);
                foreach (['TC', 'TE', 'ITT', 'YB'] as $konstruk) {
                    $row[] = $skor[$konstruk]['skor'] ?? '';
                }

                fputcsv($file, $row);
            }

            fclose($file);
        }, "tiyas-{$pelatihan->id}-".date('Y-m-d').".csv");
    }

    /**
     * Helper: tentukan kategori dari skor.
     */
    private function tentukanKategori(float $skor, array $config): string
    {
        foreach ($config['kategori'] as $cat) {
            if ($skor >= $cat['min'] && $skor <= $cat['max']) {
                return $cat['label'];
            }
        }

        return 'Tidak Terdefinisi';
    }

    /**
     * Helper: hitung rata-rata per dimensi dari semua responden.
     */
    private function hitungRataRataDimensi($tiyasResponses, $scorer): array
    {
        $config = config('tiyas');
        $dimensiData = [];

        foreach ($config['bagian'] as $bagian) {
            foreach ($bagian['dimensi'] as $dimensi) {
                $kode_dimensi = $dimensi['kode'];
                $butirsPerDimensi = [];

                foreach ($dimensi['butir'] as $butir) {
                    $butirsPerDimensi[] = $butir['kode'];
                }

                // Kumpulkan semua nilai butir ini
                $semuaNilai = [];
                foreach ($tiyasResponses as $response) {
                    foreach ($butirsPerDimensi as $kode) {
                        if (isset($response->jawaban[$kode])) {
                            $semuaNilai[] = (int) $response->jawaban[$kode];
                        }
                    }
                }

                if (!empty($semuaNilai)) {
                    $rataRata = round(array_sum($semuaNilai) / count($semuaNilai), 2);
                } else {
                    $rataRata = 0.0;
                }

                $dimensiData[] = [
                    'kode' => $kode_dimensi,
                    'label' => $dimensi['label'],
                    'rata_rata' => $rataRata,
                ];
            }
        }

        return $dimensiData;
    }
}
