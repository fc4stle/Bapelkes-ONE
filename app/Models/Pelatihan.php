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

        // Jika status belum 'selesai', cek apakah tanggal_selesai sudah lewat (bukan hari ini)
        // Tanggal selesai < hari ini (zona waktu aplikasi)
        return $this->tanggal_selesai->lessThan(now()->toDate());
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
