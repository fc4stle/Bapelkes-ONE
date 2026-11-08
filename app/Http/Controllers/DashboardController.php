<?php

namespace App\Http\Controllers;

use App\Models\Pelatihan;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Redirect staf (panitia/admin) ke panitia.dashboard
        if ($user->isStaf()) {
            return redirect()->route('panitia.dashboard');
        }

        // Ambil pendaftaran peserta (terbaru dulu)
        $pendaftarans = $user->pendaftarans()
            ->with(['pelatihan', 'tiyasResponse', 'kamar'])
            ->orderByDesc('id')
            ->get();

        // Hitung statistik
        $totalPendaftaran = $pendaftarans->count();
        $menungguVerifikasi = $pendaftarans->where('status_verifikasi', 'pending')->count();
        $terverifikasi = $pendaftarans->where('status_verifikasi', 'diverifikasi')->count();

        // Ambil pelatihan yang dibuka dan belum didaftari peserta ini
        $pelatihansIdDitonton = $pendaftarans->pluck('pelatihan_id')->toArray();
        $pelatihansNew = Pelatihan::query()
            ->where('status', 'dibuka')
            ->whereNotIn('id', $pelatihansIdDitonton)
            ->orderByDesc('id')
            ->take(3)
            ->get();

        // Ambil pengingat dan urutkan dari paling dekat
        $pengingats = $pendaftarans
            ->map(function ($p) {
                $pengingat = $p->pengingat();
                return $pengingat ? array_merge(['pendaftaran' => $p], $pengingat) : null;
            })
            ->filter()
            ->sortBy('selisih_hari')
            ->values();

        // Ambil pengingat dengan sisa 0-1 hari untuk banner
        $bannerPengingat = $pengingats
            ->filter(fn ($p) => $p['selisih_hari'] <= 1 && $p['selisih_hari'] >= 0)
            ->first();

        return view('dashboard', compact(
            'user',
            'pendaftarans',
            'totalPendaftaran',
            'menungguVerifikasi',
            'terverifikasi',
            'pelatihansNew',
            'pengingats',
            'bannerPengingat'
        ));
    }
}
