<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengumuman extends Model
{
    protected $table = 'pengumumans';

    protected $fillable = [
        'pelatihan_id',
        'user_id',
        'judul',
        'isi',
        'jumlah_penerima',
    ];

    public function pelatihan(): BelongsTo
    {
        return $this->belongsTo(Pelatihan::class);
    }

    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
