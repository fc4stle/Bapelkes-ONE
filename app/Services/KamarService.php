<?php

namespace App\Services;

use App\Models\Kamar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KamarService
{
    /**
     * Coba alokasikan 1 kamar yang tersedia untuk tanggal check-in/check-out.
     * Menggunakan database transaction + lockForUpdate untuk mencegah race condition.
     *
     * @return Kamar|null Kamar yang tersedia, atau null jika penuh
     */
    public function alokasikanKamar(string $checkIn, string $checkOut): ?Kamar
    {
        return DB::transaction(function () use ($checkIn, $checkOut) {
            // Lock semua kamar untuk mencegah race condition
            $kamars = Kamar::lockForUpdate()->get();

            foreach ($kamars as $kamar) {
                if ($kamar->tersediaUntukTanggal($checkIn, $checkOut)) {
                    return $kamar;
                }
            }

            return null;
        });
    }

    /**
     * Daftar semua kamar dengan info ketersediaan untuk tanggal tertentu.
     */
    public function daftarKamarTersedia(string $checkIn, string $checkOut): Collection
    {
        $kamars = Kamar::with('asrama')->get();

        return $kamars->filter(fn (Kamar $kamar) => $kamar->tersediaUntukTanggal($checkIn, $checkOut));
    }

    /**
     * Cek apakah masih ada kamar tersedia untuk tanggal tertentu.
     */
    public function adaKamarTersedia(string $checkIn, string $checkOut): bool
    {
        return $this->daftarKamarTersedia($checkIn, $checkOut)->isNotEmpty();
    }
}
