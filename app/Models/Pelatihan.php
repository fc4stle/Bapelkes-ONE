<?php

namespace App\Models;

use App\Enums\MetodePelatihan;
use App\Enums\StatusPelatihan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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
        'persyaratan',
        'tanggal_mulai',
        'tanggal_selesai',
        'lokasi',
        'kuota',
        'metode',
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
            'metode' => MetodePelatihan::class,
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

    /**
     * @return HasMany<Pengumuman, $this>
     */
    public function pengumumans(): HasMany
    {
        return $this->hasMany(Pengumuman::class);
    }

    /**
     * @return HasManyThrough<TiyasResponse, Pendaftaran>
     */
    public function tiyasResponses(): HasManyThrough
    {
        return $this->hasManyThrough(
            TiyasResponse::class,
            Pendaftaran::class,
            'pelatihan_id',
            'pendaftaran_id'
        );
    }

    public function jumlahPendaftar(): int
    {
        // Gunakan eager loaded count jika tersedia (dari withCount dalam katalog)
        if (isset($this->attributes['pendaftarans_count'])) {
            return (int) $this->pendaftarans_count;
        }

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

    /**
     * Satu sumber kebenaran: pelatihan sudah selesai
     * - Status === Selesai, ATAU
     * - Tanggal selesai lebih awal dari HARI INI (bukan termasuk hari tersebut, kecuali status sudah selesai)
     */
    public function sudahSelesai(): bool
    {
        // Jika status sudah manual 'selesai', langsung return true
        if ($this->status === StatusPelatihan::Selesai) {
            return true;
        }

        // Jika status belum 'selesai', cek apakah tanggal_selesai sudah lewat
        // Membandingkan TANGGAL saja (bukan waktu): tanggal_selesai harus < hari ini
        // Pada hari tanggal_selesai sendiri (pukul berapa pun), pelatihan BELUM selesai
        return $this->tanggal_selesai->copy()->startOfDay()->lt(today());
    }

    /**
     * Scope: Pelatihan yang sudah selesai
     * Menggunakan logika yang sama dengan sudahSelesai()
     */
    public function scopeSudahSelesai($query)
    {
        return $query
            ->where('status', StatusPelatihan::Selesai)
            ->orWhereDate('tanggal_selesai', '<', now()->toDate());
    }

    /**
     * Scope: Pelatihan yang belum selesai
     */
    public function scopeBelumSelesai($query)
    {
        return $query
            ->where('status', '!=', StatusPelatihan::Selesai)
            ->whereDate('tanggal_selesai', '>=', now()->toDate());
    }
}
