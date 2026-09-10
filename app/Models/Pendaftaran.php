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
        'kode_presensi',
        'kode_generated_at',
        'hadir_at',
        'kode_sertifikat',
        'sertifikat_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'data_diri' => 'array',
            'butuh_asrama' => 'boolean',
            'verified_at' => 'datetime',
            'check_in' => 'date:Y-m-d',
            'check_out' => 'date:Y-m-d',
            'kode_generated_at' => 'datetime',
            'hadir_at' => 'datetime',
            'sertifikat_generated_at' => 'datetime',
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

    public function isHadir(): bool
    {
        return $this->hadir_at !== null;
    }

    public function isSudahSertifikat(): bool
    {
        return $this->kode_sertifikat !== null;
    }

    public function isPelatihanSelesai(): bool
    {
        return $this->pelatihan && $this->pelatihan->tanggal_selesai->isPast();
    }

    public function bisaDownloadSertifikat(): bool
    {
        return $this->isDiverifikasi() && $this->isHadir() && $this->isPelatihanSelesai();
    }

    public function generateKodeSertifikat(): string
    {
        $kode = 'CERT-'.strtoupper(substr(md5($this->id.$this->peserta_id.$this->pelatihan_id.uniqid()), 0, 12));
        $this->update([
            'kode_sertifikat' => $kode,
            'sertifikat_generated_at' => now(),
        ]);

        return $kode;
    }

    public function verifikasi(User $panitia): void
    {
        $this->update([
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $panitia->id,
            'verified_at' => now(),
            'alasan_penolakan' => null,
            'kode_presensi' => $this->generateKodePresensi(),
            'kode_generated_at' => now(),
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

    public function tandaiHadir(): void
    {
        $this->update([
            'hadir_at' => now(),
        ]);
    }

    private function generateKodePresensi(): string
    {
        return strtoupper(substr(md5($this->id.$this->peserta_id.$this->pelatihan_id.uniqid()), 0, 16));
    }
}
