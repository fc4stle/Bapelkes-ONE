<?php

namespace App\Http\Controllers;

use App\Models\Pelatihan;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Redirect panitia ke panitia.dashboard
        if ($user->isPanitia()) {
            return redirect()->route('panitia.dashboard');
        }

        // Ambil pendaftaran peserta (terbaru dulu)
        $pendaftarans = $user->pendaftarans()
            ->with(['pelatihan', 'tiyasResponse'])
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

        return view('dashboard', compact(
            'user',
            'pendaftarans',
            'totalPendaftaran',
            'menungguVerifikasi',
            'terverifikasi',
            'pelatihansNew'
        ));
    }
}
