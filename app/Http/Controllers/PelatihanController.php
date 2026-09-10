<?php

namespace App\Http\Controllers;

use App\Enums\MetodePelatihan;
use App\Enums\StatusPelatihan;
use App\Http\Requests\StorePelatihanRequest;
use App\Http\Requests\UpdatePelatihanRequest;
use App\Models\Pelatihan;
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
}
