<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePelatihanRequest;
use App\Http\Requests\UpdatePelatihanRequest;
use App\Models\Pelatihan;

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
