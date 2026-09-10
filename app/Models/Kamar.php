<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kamar extends Model
{
    use HasFactory;

    protected $fillable = [
        'asrama_id',
        'nomor_kamar',
        'kapasitas',
    ];

    public function asrama(): BelongsTo
    {
        return $this->belongsTo(Asrama::class);
    }

    public function pendaftarans(): HasMany
    {
        return $this->hasMany(Pendaftaran::class);
    }

    /**
     * Cek apakah kamar tersedia untuk rentang tanggal tertentu.
     */
    public function tersediaUntukTanggal(string $checkIn, string $checkOut): bool
    {
        $jumlahPendaftar = $this->pendaftarans()
            ->where('status_verifikasi', '!=', 'ditolak')
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in', [$checkIn, $checkOut])
                    ->orWhereBetween('check_out', [$checkIn, $checkOut])
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in', '<=', $checkIn)
                            ->where('check_out', '>=', $checkOut);
                    });
            })
            ->count();

        return $jumlahPendaftar < $this->kapasitas;
    }
}
