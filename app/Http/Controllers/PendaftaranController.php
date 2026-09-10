<?php

namespace App\Http\Controllers;

use App\Enums\StatusPelatihan;
use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PendaftaranController extends Controller
{
    /**
     * Display a listing of the authenticated user's registrations.
     */
    public function index()
    {
        $pendaftarans = Auth::user()
            ->pendaftarans()
            ->with('pelatihan')
            ->latest()
            ->get();

        return view('pendaftarans.index', compact('pendaftarans'));
    }

    /**
     * Register the authenticated user to the given pelatihan.
     */
    public function store(Request $request, Pelatihan $pelatihan)
    {
        $user = $request->user();

        if ($pelatihan->status !== StatusPelatihan::Dibuka) {
            return back()->with('error', 'Pendaftaran untuk pelatihan ini belum dibuka.');
        }

        if ($pelatihan->isFull()) {
            return back()->with('error', 'Kuota pelatihan ini sudah penuh.');
        }

        if ($user->pendaftaranPelatihan($pelatihan)) {
            return back()->with('error', 'Anda sudah terdaftar pada pelatihan ini.');
        }

        Pendaftaran::create([
            'peserta_id' => $user->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'pending',
        ]);

        return back()->with('success', 'Pendaftaran berhasil dikirim, menunggu konfirmasi panitia.');
    }

    /**
     * Update the status of a registration.
     */
    public function update(Request $request, Pendaftaran $pendaftaran)
    {
        $validated = $request->validate([
            'status_verifikasi' => ['required', 'in:diverifikasi,ditolak'],
            'catatan' => ['nullable', 'string'],
        ]);

        $pendaftaran->update($validated);

        return back()->with('success', 'Status pendaftaran berhasil diperbarui.');
    }

    /**
     * Cancel the authenticated user's own registration.
     */
    public function destroy(Pendaftaran $pendaftaran)
    {
        if ($pendaftaran->peserta_id !== Auth::id()) {
            abort(403);
        }

        $pendaftaran->delete();

        return back()->with('success', 'Pendaftaran berhasil dibatalkan.');
    }
}
