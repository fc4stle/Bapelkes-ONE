<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TiyasResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'pendaftaran_id',
        'identitas',
        'jawaban',
        'catatan_supervisor',
        'catatan_rekan_kerja',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'identitas' => 'array',
            'jawaban' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Pendaftaran, $this>
     */
    public function pendaftaran(): BelongsTo
    {
        return $this->belongsTo(Pendaftaran::class);
    }
}
