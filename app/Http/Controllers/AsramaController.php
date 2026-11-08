<?php

namespace App\Http\Controllers;

use App\Models\Asrama;
use App\Models\Pendaftaran;
use Illuminate\Http\Request;

class AsramaController extends Controller
{
    /**
     * Daftar asrama dengan ringkasan kamar dan penghuni.
     */
    public function index(Request $request)
    {
        $tanggal = $request->query('tanggal');
        if ($tanggal && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            $tanggal = null;
        }

        // Hitung penghuni aktif per asrama menggunakan query
        $asramas = Asrama::with('kamars')
            ->get()
            ->map(function ($asrama) use ($tanggal) {
                // Hitung penghuni aktif SEKALI per asrama
                $penghuniAktif = Pendaftaran::whereIn('kamar_id', $asrama->kamars->pluck('id'))
                    ->penghuniAktif($tanggal)
                    ->count();

                $totalKapasitas = $asrama->totalKapasitasKamar();

                return [
                    'id' => $asrama->id,
                    'nama' => $asrama->nama,
                    'jumlah_kamar' => $asrama->kamars->count(),
                    'total_kapasitas' => $totalKapasitas,
                    'jumlah_penghuni_aktif' => $penghuniAktif,
                    'sisa_kapasitas' => $totalKapasitas - $penghuniAktif,
                ];
            });

        return view('panitia.asrama.index', [
            'asramas' => $asramas,
            'tanggal' => $tanggal,
        ]);
    }

    /**
     * Detail asrama: daftar kamar dan penghuni per kamar.
     */
    public function show(Asrama $asrama)
    {
        // Eager load dengan constraint pada scope penghuniAktif
        $asrama->load([
            'kamars.pendaftarans' => function ($q) {
                $q->penghuniAktif()
                    ->with('peserta', 'pelatihan');
            },
        ]);

        $kamars = $asrama->kamars
            ->map(function ($kamar) {
                $penghuni = $kamar->pendaftarans;

                return [
                    'id' => $kamar->id,
                    'nomor_kamar' => $kamar->nomor_kamar,
                    'kapasitas' => $kamar->kapasitas,
                    'jumlah_penghuni' => $penghuni->count(),
                    'status' => $penghuni->count() >= $kamar->kapasitas ? 'Penuh' : 'Tersedia',
                    'penghuni' => $penghuni->map(fn ($p) => [
                        'id' => $p->id,
                        'nama_peserta' => $p->peserta?->name ?? '-',
                        'pelatihan' => $p->pelatihan?->nama ?? '-',
                        'check_in' => $p->check_in?->format('d/m/Y') ?? '-',
                        'check_out' => $p->check_out?->format('d/m/Y') ?? '-',
                        'status_verifikasi' => $p->status_verifikasi,
                    ])->all(),
                ];
            })
            ->all();

        return view('panitia.asrama.show', [
            'asrama' => $asrama,
            'kamars' => $kamars,
        ]);
    }
}
