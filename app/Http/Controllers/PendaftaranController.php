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
     * Show the registration form for a pelatihan.
     */
    public function create(Pelatihan $pelatihan)
    {
        if ($pelatihan->status !== StatusPelatihan::Dibuka) {
            return back()->with('error', 'Pendaftaran untuk pelatihan ini belum dibuka.');
        }

        if ($pelatihan->isFull()) {
            return back()->with('error', 'Kuota pelatihan ini sudah penuh.');
        }

        if (Auth::user()->pendaftaranPelatihan($pelatihan)) {
            return back()->with('error', 'Anda sudah terdaftar pada pelatihan ini.');
        }

        return view('pendaftarans.create', compact('pelatihan'));
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

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'string', 'max:16'],
            'kontak' => ['required', 'string', 'max:255'],
            'profesi' => ['required', 'string', 'max:255'],
            'instansi' => ['required', 'string', 'max:255'],
            'surat_tugas' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'dokumen_lain' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'butuh_asrama' => ['nullable', 'boolean'],
            'check_in' => ['required_if:butuh_asrama,true', 'nullable', 'date'],
            'check_out' => ['required_if:butuh_asrama,true', 'nullable', 'date', 'after_or_equal:check_in'],
        ]);

        $suratTugasPath = null;
        if ($request->hasFile('surat_tugas')) {
            $suratTugasPath = $request->file('surat_tugas')->store('surat_tugas', 'public');
        }

        $dokumenLainPaths = [];
        if ($request->hasFile('dokumen_lain')) {
            $dokumenLainPaths[] = $request->file('dokumen_lain')->store('dokumen_pendaftar', 'public');
        }

        Pendaftaran::create([
            'peserta_id' => $user->id,
            'pelatihan_id' => $pelatihan->id,
            'status_verifikasi' => 'pending',
            'data_diri' => [
                'nama' => $validated['nama'],
                'nik' => $validated['nik'],
                'kontak' => $validated['kontak'],
                'profesi' => $validated['profesi'],
                'instansi' => $validated['instansi'],
            ],
            'dokumen' => json_encode(array_filter([
                'surat_tugas' => $suratTugasPath,
                'dokumen_lain' => $dokumenLainPaths,
                'check_in' => $validated['check_in'] ?? null,
                'check_out' => $validated['check_out'] ?? null,
            ])),
            'butuh_asrama' => (bool) ($validated['butuh_asrama'] ?? false),
        ]);

        return redirect()->route('pendaftarans.index')->with('success', 'Pendaftaran berhasil dikirim, menunggu konfirmasi panitia.');
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
     * Verify a registration (panitia only).
     */
    public function verifikasi(Pendaftaran $pendaftaran)
    {
        $pendaftaran->verifikasi(Auth::user());

        return back()->with('success', 'Pendaftaran berhasil diverifikasi.');
    }

    /**
     * Reject a registration with a reason (panitia only).
     */
    public function tolak(Request $request, Pendaftaran $pendaftaran)
    {
        $validated = $request->validate([
            'alasan_penolakan' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $pendaftaran->tolak(Auth::user(), $validated['alasan_penolakan']);

        return back()->with('success', 'Pendaftaran berhasil ditolak.');
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
