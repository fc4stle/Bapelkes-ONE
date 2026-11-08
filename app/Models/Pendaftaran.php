<?php

namespace App\Models;

use App\Notifications\KehadiranTercatat;
use App\Notifications\PendaftaranDitolak;
use App\Notifications\PendaftaranDiverifikasi;
use Database\Factories\PendaftaranFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    /**
     * @return HasOne<TiyasResponse, $this>
     */
    public function tiyasResponse(): HasOne
    {
        return $this->hasOne(TiyasResponse::class);
    }

    /**
     * Scope: Penghuni aktif = status_verifikasi != 'ditolak' AND sesuai kriteria tanggal
     * - Tanpa $tanggal: check_out >= hari ini
     * - Dengan $tanggal: check_in <= $tanggal AND check_out >= $tanggal
     */
    public function scopePenghuniAktif($query, ?string $tanggal = null)
    {
        return $query
            ->where('status_verifikasi', '!=', 'ditolak')
            ->when(
                $tanggal !== null,
                function ($q) use ($tanggal) {
                    return $q
                        ->where('check_in', '<=', $tanggal)
                        ->where('check_out', '>=', $tanggal);
                },
                function ($q) {
                    return $q->where('check_out', '>=', now()->toDateString());
                }
            );
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
        $oldStatus = $this->status_verifikasi;

        $this->update([
            'status_verifikasi' => 'diverifikasi',
            'verified_by' => $panitia->id,
            'verified_at' => now(),
            'alasan_penolakan' => null,
            'kode_presensi' => $this->generateKodePresensi(),
            'kode_generated_at' => now(),
        ]);

        // Kirim notifikasi hanya jika status berubah
        if ($oldStatus !== 'diverifikasi') {
            $this->peserta->notify(new PendaftaranDiverifikasi($this));
        }
    }

    public function tolak(User $panitia, string $alasan): void
    {
        $oldStatus = $this->status_verifikasi;

        $this->update([
            'status_verifikasi' => 'ditolak',
            'verified_by' => $panitia->id,
            'verified_at' => now(),
            'alasan_penolakan' => $alasan,
        ]);

        // Kirim notifikasi hanya jika status berubah
        if ($oldStatus !== 'ditolak') {
            $this->peserta->notify(new PendaftaranDitolak($this));
        }
    }

    public function tandaiHadir(): void
    {
        $hadirSebelumnya = $this->hadir_at;
        $this->update([
            'hadir_at' => now(),
        ]);

        // Kirim notifikasi hanya jika hadir_at sebelumnya null (baru pertama kali hadir)
        if ($hadirSebelumnya === null) {
            $this->peserta->notify(new KehadiranTercatat($this));
        }
    }

    private function generateKodePresensi(): string
    {
        return strtoupper(substr(md5($this->id.$this->peserta_id.$this->pelatihan_id.uniqid()), 0, 16));
    }

    /**
     * Pengingat pelatihan: mengembalikan null jika tidak ada pengingat.
     * Kondisi: pendaftaran diverifikasi, pelatihan belum selesai, mulai paling lama 7 hari lagi.
     *
     * @return array{label: string, urgensi: string, selisih_hari: int}|null
     */
    public function pengingat(): ?array
    {
        // Hanya untuk pendaftaran yang diverifikasi
        if (! $this->isDiverifikasi()) {
            return null;
        }

        // Hitung selisih hari antara hari ini (tanpa jam) dan tanggal mulai
        // Positif = tanggal mulai di masa depan, Negatif = sudah berlangsung
        $today = now()->startOfDay();
        $start_date = $this->pelatihan->tanggal_mulai->startOfDay();
        $end_date = $this->pelatihan->tanggal_selesai->startOfDay();

        // diffInDays(other) returns (this - other), jadi start_date->diffInDays(today) = negatif jika start belum tiba
        // Kita ingin positif jika masa depan, jadi gunakan today->diffInDays(start_date)
        // diffInDays mengembalikan float, convert ke int
        $diff_days = (int) $today->diffInDays($start_date);

        // Hanya tampilkan pengingat jika mulai paling lama 7 hari lagi atau sedang berlangsung
        if ($diff_days > 7) {
            return null;
        }

        // Jika sudah melewati tanggal selesai, tidak ada pengingat
        if ($today->isAfter($end_date)) {
            return null;
        }

        // Tentukan label berdasarkan selisih hari
        $label = match (true) {
            $diff_days > 1 => "Dimulai $diff_days hari lagi",
            $diff_days === 1 => 'Besok',
            $diff_days === 0 => 'Hari ini',
            $diff_days < 0 => 'Sedang berlangsung',
            default => null,
        };

        if ($label === null) {
            return null;
        }

        // Tentukan urgensi
        $urgensi = match (true) {
            $diff_days <= 1 => 'tinggi',
            $diff_days <= 3 => 'sedang',
            default => 'rendah',
        };

        return [
            'label' => $label,
            'urgensi' => $urgensi,
            'selisih_hari' => $diff_days,
        ];
    }

    /**
     * @return array{judul: string, deskripsi: string, tone: string, aksi: array<int, array{label: string, url: string}>}
     */
    public function langkahBerikutnya(): array
    {
        // a) ditolak
        if ($this->isDitolak()) {
            return [
                'judul' => 'Pendaftaran ditolak',
                'deskripsi' => $this->alasan_penolakan ?? 'Pendaftaran Anda tidak memenuhi syarat.',
                'tone' => 'bahaya',
                'aksi' => [
                    ['label' => 'Lihat katalog', 'url' => route('pelatihan.katalog')],
                ],
            ];
        }

        // b) pending
        if ($this->isPending()) {
            return [
                'judul' => 'Menunggu verifikasi panitia',
                'deskripsi' => 'Panitia akan memeriksa data Anda.',
                'tone' => 'info',
                'aksi' => [
                    ['label' => 'Ubah data', 'url' => route('pendaftarans.edit', $this)],
                ],
            ];
        }

        // c) diverifikasi, pelatihan selesai (tanpa syarat hadir)
        if ($this->isDiverifikasi() && $this->pelatihan->status->value === 'selesai') {
            $aksi = [];

            if ($this->bisaDownloadSertifikat()) {
                $aksi[] = ['label' => 'Unduh sertifikat', 'url' => route('pendaftarans.sertifikat', $this)];
            }

            if (! $this->tiyasResponse()->exists()) {
                $aksi[] = ['label' => 'Isi Evaluasi TIYAS', 'url' => route('tiyas.form', $this)];
            }

            if (empty($aksi)) {
                return [
                    'judul' => 'Pelatihan selesai',
                    'deskripsi' => 'Terima kasih telah mengikuti pelatihan.',
                    'tone' => 'sukses',
                    'aksi' => [],
                ];
            }

            return [
                'judul' => 'Pelatihan selesai',
                'deskripsi' => 'Pelatihan Anda telah selesai. Silakan unduh sertifikat atau isi evaluasi TIYAS jika belum.',
                'tone' => 'sukses',
                'aksi' => $aksi,
            ];
        }

        // d) diverifikasi, belum hadir, pelatihan BELUM selesai
        if ($this->isDiverifikasi() && ! $this->isHadir() && $this->pelatihan->status->value !== 'selesai') {
            return [
                'judul' => 'Pendaftaran terverifikasi',
                'deskripsi' => 'Tunjukkan kartu peserta saat hari pertama ('.$this->pelatihan->tanggal_mulai->translatedFormat('d M Y').').',
                'tone' => 'info',
                'aksi' => [
                    ['label' => 'Kartu peserta', 'url' => route('pendaftarans.kartu', $this)],
                ],
            ];
        }

        // e) diverifikasi, sudah hadir, pelatihan BELUM selesai
        if ($this->isDiverifikasi() && $this->isHadir() && $this->pelatihan->status->value !== 'selesai') {
            return [
                'judul' => 'Anda sudah hadir',
                'deskripsi' => 'Pelatihan berlangsung sampai '.$this->pelatihan->tanggal_selesai->translatedFormat('d M Y').'.',
                'tone' => 'info',
                'aksi' => [],
            ];
        }

        // Fallback (seharusnya tidak ada)
        return [
            'judul' => 'Status tidak diketahui',
            'deskripsi' => 'Hubungi panitia untuk informasi lebih lanjut.',
            'tone' => 'perhatian',
            'aksi' => [],
        ];
    }
}
