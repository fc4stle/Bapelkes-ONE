<?php

namespace App\Models;

use App\Enums\StatusPelatihan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pelatihan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nama',
        'deskripsi',
        'tanggal_mulai',
        'tanggal_selesai',
        'lokasi',
        'kuota',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'kuota' => 'integer',
            'status' => StatusPelatihan::class,
        ];
    }

    /**
     * @return HasMany<Pendaftaran, $this>
     */
    public function pendaftarans(): HasMany
    {
        return $this->hasMany(Pendaftaran::class);
    }

    public function jumlahPendaftar(): int
    {
        return $this->pendaftarans()->where('status_verifikasi', 'diverifikasi')->count();
    }

    public function sisaKuota(): int
    {
        return $this->kuota - $this->jumlahPendaftar();
    }

    public function isFull(): bool
    {
        return $this->sisaKuota() <= 0;
    }
}
