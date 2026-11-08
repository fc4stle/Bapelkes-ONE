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
    public function tersediaUntukTanggal(string $checkIn, string $checkOut, ?int $excludePendaftaranId = null): bool
    {
        $query = $this->pendaftarans()
            ->where('status_verifikasi', '!=', 'ditolak')
            ->where(function ($q) use ($checkIn, $checkOut) {
                $q->whereBetween('check_in', [$checkIn, $checkOut])
                    ->orWhereBetween('check_out', [$checkIn, $checkOut])
                    ->orWhere(function ($q2) use ($checkIn, $checkOut) {
                        $q2->where('check_in', '<=', $checkIn)
                            ->where('check_out', '>=', $checkOut);
                    });
            });

        if ($excludePendaftaranId) {
            $query->where('id', '!=', $excludePendaftaranId);
        }

        $jumlahPendaftar = $query->count();

        return $jumlahPendaftar < $this->kapasitas;
    }
}
