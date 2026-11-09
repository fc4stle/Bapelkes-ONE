<?php

namespace App\Http\Controllers;

use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use App\Services\TiyasScorer;
use Illuminate\View\View;

class StatistikController extends Controller
{
    public function __construct(private TiyasScorer $scorer) {}

    public function index(): View
    {
        // Query agregat untuk kartu global
        $pelatihans = Pelatihan::with([
            'pendaftarans' => fn ($q) => $q->select('pelatihan_id', 'peserta_id', 'status_verifikasi', 'hadir_at', 'butuh_asrama', 'kamar_id'),
            'tiyasResponses' => fn ($q) => $q->select('pendaftaran_id', 'jawaban'),
        ])
            ->withCount([
                'pendaftarans',
                'pendaftarans as pendaftarans_diverifikasi' => fn ($q) => $q->where('status_verifikasi', 'diverifikasi'),
                'pendaftarans as pendaftarans_hadir' => fn ($q) => $q->where('status_verifikasi', 'diverifikasi')->whereNotNull('hadir_at'),
            ])
            ->orderBy('tanggal_mulai', 'desc')
            ->get();

        // Hitung statistik global
        $totalPelatihan = $pelatihans->count();

        // Peserta unik yang terverifikasi
        $pesertaTerverifikasi = Pendaftaran::where('status_verifikasi', 'diverifikasi')
            ->distinct()
            ->count('peserta_id');

        // Total pendaftaran terverifikasi
        $totalPendaftaranTerverifikasi = $pelatihans->sum('pendaftarans_diverifikasi');

        // Total hadir
        $totalHadir = $pelatihans->sum('pendaftarans_hadir');

        // Tingkat kehadiran global
        $tingkatKehadiranGlobal = $totalPendaftaranTerverifikasi > 0
            ? round(($totalHadir / $totalPendaftaranTerverifikasi) * 100, 1)
            : null;

        // Rata-rata skor TIYAS global
        $rataRataTiyasGlobal = null;
        if ($pelatihans->sum(fn ($p) => count($p->tiyasResponses)) > 0) {
            $allScores = [];
            foreach ($pelatihans as $pelatihan) {
                foreach ($pelatihan->tiyasResponses as $tiyas) {
                    $skorKonstruk = $this->scorer->hitungSkor($tiyas->jawaban ?? []);
                    // Hitung rata-rata dari semua konstruk (TC, TE, ITT, YB)
                    $skorKonstrukValues = array_filter(
                        array_map(fn ($s) => $s['skor'] ?? null, $skorKonstruk),
                        fn ($s) => $s !== null && $s > 0
                    );
                    if (count($skorKonstrukValues) > 0) {
                        $allScores[] = array_sum($skorKonstrukValues) / count($skorKonstrukValues);
                    }
                }
            }
            if (count($allScores) > 0) {
                $rataRataTiyasGlobal = round(array_sum($allScores) / count($allScores), 2);
            }
        }

        // Proses data per pelatihan untuk tabel
        $pelatihansData = $pelatihans->map(function ($pelatihan) {
            $totalPendaftar = $pelatihan->pendaftarans_count;
            $totalTerverifikasi = $pelatihan->pendaftarans_diverifikasi;
            $totalHadir = $pelatihan->pendaftarans_hadir;

            // Persentase hadir
            $persenHadir = $totalTerverifikasi > 0
                ? round(($totalHadir / $totalTerverifikasi) * 100, 1)
                : null;

            // Rata-rata TIYAS untuk pelatihan ini
            $rataRataTiyas = null;
            if (count($pelatihan->tiyasResponses) > 0) {
                $scores = [];
                foreach ($pelatihan->tiyasResponses as $tiyas) {
                    $skorKonstruk = $this->scorer->hitungSkor($tiyas->jawaban ?? []);
                    $skorValues = array_filter(
                        array_map(fn ($s) => $s['skor'] ?? null, $skorKonstruk),
                        fn ($s) => $s !== null && $s > 0
                    );
                    if (count($skorValues) > 0) {
                        $scores[] = array_sum($skorValues) / count($skorValues);
                    }
                }
                if (count($scores) > 0) {
                    $rataRataTiyas = round(array_sum($scores) / count($scores), 2);
                }
            }

            // Keterisian asrama
            $keterisianAsrama = null;
            $pendaftarButuhAsrama = $pelatihan->pendaftarans
                ->filter(fn ($p) => $p->status_verifikasi === 'diverifikasi' && $p->butuh_asrama)
                ->count();
            if ($pendaftarButuhAsrama > 0) {
                $pendaftarPunyaKamar = $pelatihan->pendaftarans
                    ->filter(fn ($p) => $p->status_verifikasi === 'diverifikasi' && $p->butuh_asrama && $p->kamar_id !== null)
                    ->count();
                $keterisianAsrama = "$pendaftarPunyaKamar / $pendaftarButuhAsrama";
            }

            return [
                'id' => $pelatihan->id,
                'nama' => $pelatihan->nama,
                'tanggal_mulai' => $pelatihan->tanggal_mulai,
                'tanggal_selesai' => $pelatihan->tanggal_selesai,
                'status' => $pelatihan->status,
                'kuota' => $pelatihan->kuota,
                'total_pendaftar' => $totalPendaftar,
                'total_terverifikasi' => $totalTerverifikasi,
                'total_hadir' => $totalHadir,
                'persen_hadir' => $persenHadir,
                'rata_rata_tiyas' => $rataRataTiyas,
                'keterisian_asrama' => $keterisianAsrama,
            ];
        });

        return view('panitia.statistik', [
            'totalPelatihan' => $totalPelatihan,
            'pesertaTerverifikasi' => $pesertaTerverifikasi,
            'totalPendaftaranTerverifikasi' => $totalPendaftaranTerverifikasi,
            'tingkatKehadiranGlobal' => $tingkatKehadiranGlobal,
            'rataRataTiyasGlobal' => $rataRataTiyasGlobal,
            'pelatihans' => $pelatihansData,
        ]);
    }
}
