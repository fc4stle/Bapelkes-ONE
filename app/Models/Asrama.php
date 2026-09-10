<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asrama extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'kapasitas',
    ];

    public function kamars(): HasMany
    {
        return $this->hasMany(Kamar::class);
    }

    public function totalKapasitasKamar(): int
    {
        return $this->kamars()->sum('kapasitas');
    }
}
