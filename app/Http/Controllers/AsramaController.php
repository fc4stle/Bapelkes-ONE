<?php

namespace App\Http\Controllers;

use App\Models\Asrama;
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

        $asramas = Asrama::with('kamars.pendaftarans.peserta', 'kamars.pendaftarans.pelatihan')
            ->get()
            ->map(function ($asrama) use ($tanggal) {
                return [
                    'id' => $asrama->id,
                    'nama' => $asrama->nama,
                    'jumlah_kamar' => $asrama->kamars->count(),
                    'total_kapasitas' => $asrama->totalKapasitasKamar(),
                    'jumlah_penghuni_aktif' => $this->hitungPenghuniAktif($asrama, $tanggal),
                    'sisa_kapasitas' => $asrama->totalKapasitasKamar() - $this->hitungPenghuniAktif($asrama, $tanggal),
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
        $asrama->load('kamars.pendaftarans.peserta', 'kamars.pendaftarans.pelatihan', 'kamars.pendaftarans.kamar');

        $kamars = $asrama->kamars
            ->map(function ($kamar) {
                $penghuni = $kamar->pendaftarans()
                    ->where('status_verifikasi', '!=', 'ditolak')
                    ->with('peserta', 'pelatihan', 'kamar.asrama')
                    ->get();

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

    /**
     * Hitung penghuni aktif di asrama pada tanggal tertentu.
     * Penghuni aktif = status_verifikasi != 'ditolak' AND check_out >= hari ini (atau tanggal filter).
     */
    private function hitungPenghuniAktif(Asrama $asrama, ?string $tanggal = null): int
    {
        $cekTanggal = $tanggal ? $tanggal : now()->format('Y-m-d');

        $penghuni = $asrama->kamars
            ->flatMap(fn ($kamar) => $kamar->pendaftarans)
            ->filter(function ($p) use ($cekTanggal) {
                if ($p->status_verifikasi === 'ditolak') {
                    return false;
                }

                // Jika ada filter tanggal, hitung yang menginap pada tanggal itu
                if ($cekTanggal !== now()->format('Y-m-d')) {
                    $checkIn = $p->check_in?->format('Y-m-d');
                    $checkOut = $p->check_out?->format('Y-m-d');

                    if (! $checkIn || ! $checkOut) {
                        return false;
                    }

                    return $checkIn <= $cekTanggal && $cekTanggal <= $checkOut;
                }

                // Default: check_out >= hari ini
                return $p->check_out?->format('Y-m-d') >= now()->format('Y-m-d');
            })
            ->count();

        return $penghuni;
    }
}
