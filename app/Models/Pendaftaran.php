<?php

namespace App\Models;

use Database\Factories\PendaftaranFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pendaftaran extends Model
{
    /** @use HasFactory<PendaftaranFactory> */
    use HasFactory;

    protected $fillable = [
        'peserta_id',
        'pelatihan_id',
        'status_verifikasi',
        'data_diri',
        'dokumen',
        'butuh_asrama',
        'catatan',
        'verified_by',
        'verified_at',
        'alasan_penolakan',
        'check_in',
        'check_out',
        'kamar_id',
    ];

    protected function casts(): array
    {
        return [
            'data_diri' => 'array',
            'butuh_asrama' => 'boolean',
            'verified_at' => 'datetime',
            'check_in' => 'date:Y-m-d',
            'check_out' => 'date:Y-m-d',
        ];
    }

    public function peserta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'peserta_id');
    }

    public function pelatihan(): BelongsTo
    {
        return $this->belongsTo(Pelatihan::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function kamar(): BelongsTo
    {
        return $this->belongsTo(Kamar::class);
    }

    public function isPending(): bool
    {
        return $this->status_verifikasi === 'pending';
    }

    public function isDiverifikasi(): bool
    {
        return $this->status_verifikasi === 'diverifikasi';
    }

    public function isDitolak(): bool
    {
        return $this->status_verifikasi === 'ditolak';
    }

    public function verifikasi(User $panitia): void
    {
        $this->update([
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $panitia->id,
            'verified_at' => now(),
            'alasan_penolakan' => null,
        ]);
    }

    public function tolak(User $panitia, string $alasan): void
    {
        $this->update([
            'status_verifikasi' => 'ditolak',
            'verified_by' => $panitia->id,
            'verified_at' => now(),
            'alasan_penolakan' => $alasan,
        ]);
    }
}
