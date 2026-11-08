<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKamarRequest;
use App\Http\Requests\UpdateKamarRequest;
use App\Models\Asrama;
use App\Models\Kamar;
use Illuminate\Http\RedirectResponse;

class KamarController extends Controller
{
    /**
     * Tambah kamar baru ke asrama.
     */
    public function store(StoreKamarRequest $request, Asrama $asrama): RedirectResponse
    {
        $validated = $request->validated();

        Kamar::create([
            'asrama_id' => $asrama->id,
            'nomor_kamar' => $validated['nomor_kamar'],
            'kapasitas' => $validated['kapasitas'],
        ]);

        return redirect()
            ->route('panitia.asrama.show', $asrama)
            ->with('success', 'Kamar berhasil ditambahkan.');
    }

    /**
     * Update kapasitas kamar.
     */
    public function update(UpdateKamarRequest $request, Kamar $kamar): RedirectResponse
    {
        $validated = $request->validated();

        $kamar->update(['kapasitas' => $validated['kapasitas']]);

        return redirect()
            ->route('panitia.asrama.show', $kamar->asrama_id)
            ->with('success', 'Kapasitas kamar berhasil diperbarui.');
    }
}
