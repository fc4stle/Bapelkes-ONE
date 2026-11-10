<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AktivitasLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'aktivitas_logs';

    protected $fillable = [
        'user_id',
        'aksi',
        'deskripsi',
        'subjek_tipe',
        'subjek_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Relasi ke User (pelaku aktivitas).
     */
    public function pelaku(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Catat aktivitas aksi penting.
     *
     * @param  string  $aksi  Jenis aksi (max 60 karakter)
     * @param  string  $deskripsi  Deskripsi aksi (akan di-limit ke 255 karakter)
     * @param  Model|null  $subjek  Model yang menjadi subjek aksi (opsional)
     * @return AktivitasLog|null Instance yang dicatat, atau null jika gagal
     */
    public static function catat(string $aksi, string $deskripsi, ?Model $subjek = null): ?self
    {
        try {
            // Batasi deskripsi ke 255 karakter
            $deskripsi = Str::limit($deskripsi, 255, '');

            // Ambil user_id dari auth
            $userId = Auth::id();

            // Siapkan data untuk insert
            $data = [
                'user_id' => $userId,
                'aksi' => $aksi,
                'deskripsi' => $deskripsi,
                'created_at' => now(),
            ];

            // Jika ada subjek, catat tipenya dan ID-nya
            if ($subjek) {
                $data['subjek_tipe'] = class_basename($subjek);
                $data['subjek_id'] = $subjek->getKey();
            }

            return self::create($data);
        } catch (\Exception $e) {
            // Jangan throw exception; pencatatan tidak boleh merusak aksi utama
            Log::warning('Aktivitas log catat gagal: '.class_basename($e));

            return null;
        }
    }
}
