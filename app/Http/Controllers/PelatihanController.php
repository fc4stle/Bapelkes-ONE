<?php

namespace App\Http\Controllers;

use App\Enums\MetodePelatihan;
use App\Enums\StatusPelatihan;
use App\Http\Requests\StorePelatihanRequest;
use App\Http\Requests\UpdatePelatihanRequest;
use App\Models\Pelatihan;
use App\Models\Pendaftaran;
use Illuminate\Http\Request;

class PelatihanController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pelatihans = Pelatihan::latest()->paginate(10);

        return view('pelatihans.index', compact('pelatihans'));
    }

    /**
     * Display the catalog of currently open pelatihan for peserta.
     */
    public function katalog(Request $request)
    {
        $search = $request->query('q');
        $metode = MetodePelatihan::tryFrom((string) $request->query('metode'));

        $pelatihans = Pelatihan::query()
            ->where('status', StatusPelatihan::Dibuka)
            ->when($search, fn ($query) => $query->where('nama', 'like', '%'.$search.'%'))
            ->when($metode, fn ($query) => $query->where('metode', $metode))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pelatihans.katalog', [
            'pelatihans' => $pelatihans,
            'search' => $search,
            'metode' => $metode?->value,
        ]);
    }

    /**
     * Show the list of pendaftar for a pelatihan (panitia only).
     */
    public function pendaftar(Pelatihan $pelatihan)
    {
        $pendaftarans = $pelatihan->pendaftarans()
            ->with('peserta')
            ->latest()
            ->paginate(15);

        return view('pelatihans.pendaftar', compact('pelatihan', 'pendaftarans'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pelatihans.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePelatihanRequest $request)
    {
        $validated = $request->validated();

        Pelatihan::create($validated);

        return redirect()
            ->route('pelatihans.index')
            ->with('success', 'Pelatihan berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Pelatihan $pelatihan)
    {
        return view('pelatihans.show', compact('pelatihan'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pelatihan $pelatihan)
    {
        return view('pelatihans.edit', compact('pelatihan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePelatihanRequest $request, Pelatihan $pelatihan)
    {
        $validated = $request->validated();

        $pelatihan->update($validated);

        return redirect()
            ->route('pelatihans.index')
            ->with('success', 'Pelatihan berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pelatihan $pelatihan)
    {
        $pelatihan->delete();

        return redirect()
            ->route('pelatihans.index')
            ->with('success', 'Pelatihan berhasil dihapus.');
    }

    /**
     * Show the presensi form for a pelatihan (panitia only).
     */
    public function presensi(Pelatihan $pelatihan)
    {
        return view('pelatihans.presensi', compact('pelatihan'));
    }

    /**
     * Show the dashboard rekap for panitia.
     */
    public function dashboard()
    {
        $pelatihans = Pelatihan::withCount([
            'pendaftarans as total_pendaftar',
            'pendaftarans as terverifikasi' => fn ($q) => $q->where('status_verifikasi', 'diverifikasi'),
            'pendaftarans as menunggu_verifikasi' => fn ($q) => $q->where('status_verifikasi', 'pending'),
            'pendaftarans as ditolak' => fn ($q) => $q->where('status_verifikasi', 'ditolak'),
            'pendaftarans as hadir' => fn ($q) => $q->whereNotNull('hadir_at'),
            'pendaftarans as butuh_asrama' => fn ($q) => $q->where('butuh_asrama', true),
            'pendaftarans as sertifikat_terunduh' => fn ($q) => $q->whereNotNull('kode_sertifikat'),
        ])->latest()->paginate(10);

        return view('pelatihans.dashboard', compact('pelatihans'));
    }

    /**
     * Process the presensi submission (panitia only).
     */
    public function prosesPresensi(Request $request, Pelatihan $pelatihan)
    {
        $validated = $request->validate([
            'kode_presensi' => ['required', 'string', 'max:32'],
        ]);

        $kode = strtoupper(trim($validated['kode_presensi']));

        // Cari pendaftaran dengan kode ini untuk pelatihan ini
        $pendaftaran = Pendaftaran::where('kode_presensi', $kode)
            ->where('pelatihan_id', $pelatihan->id)
            ->first();

        // Kalau tidak ketemu di pelatihan ini, cek apakah ada di pelatihan lain
        if (! $pendaftaran) {
            $pendaftaranLain = Pendaftaran::where('kode_presensi', $kode)->first();
            if ($pendaftaranLain) {
                return back()->with('error', 'Kode presensi tidak berlaku untuk pelatihan ini. Kode ini terdaftar untuk pelatihan lain.');
            }

            return back()->with('error', 'Kode presensi tidak ditemukan.');
        }

        // Cek status verifikasi
        if (! $pendaftaran->isDiverifikasi()) {
            return back()->with('error', 'Pendaftaran belum diverifikasi. Tidak dapat melakukan presensi.');
        }

        // Cek apakah sudah pernah presensi
        if ($pendaftaran->isHadir()) {
            return back()->with('warning', "Peserta {$pendaftaran->data_diri['nama']} sudah pernah presensi pada {$pendaftaran->hadir_at->format('d M Y H:i')}.");
        }

        // Tandai hadir
        $pendaftaran->tandaiHadir();

        return back()->with('success', "Presensi berhasil! Peserta: {$pendaftaran->data_diri['nama']}.");
    }
}
