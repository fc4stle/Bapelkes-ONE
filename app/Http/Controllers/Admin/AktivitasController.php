<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AktivitasLog;
use Illuminate\Http\Request;

class AktivitasController extends Controller
{
    public function index(Request $request)
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        $query = AktivitasLog::query()->with('pelaku');

        // Filter aksi (whitelist)
        $aksiOptions = [
            'ubah_role_user',
            'nonaktifkan_user',
            'aktifkan_user',
            'verifikasi_pendaftaran',
            'tolak_pendaftaran',
            'tambah_pelatihan',
            'ubah_pelatihan',
            'hapus_pelatihan',
            'pindah_kamar',
        ];

        if ($request->has('aksi') && $request->aksi !== '') {
            if (in_array($request->aksi, $aksiOptions, true)) {
                $query->where('aksi', $request->aksi);
            }
        }

        // Pencarian deskripsi dengan escape
        if ($request->has('q') && $request->q !== '') {
            $searchTerm = $request->q;
            // Escape LIKE special characters: % _ \
            $searchTerm = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $searchTerm);
            $query->where('deskripsi', 'like', '%'.$searchTerm.'%');
        }

        $aktivitas = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.aktivitas.index', [
            'aktivitas' => $aktivitas,
            'aksiOptions' => $aksiOptions,
        ]);
    }
}
