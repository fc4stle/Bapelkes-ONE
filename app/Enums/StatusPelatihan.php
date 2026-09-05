<?php

namespace App\Enums;

enum StatusPelatihan: string
{
    case Draft = 'draft';
    case Dibuka = 'dibuka';
    case Ditutup = 'ditutup';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Dibuka => 'Dibuka',
            self::Ditutup => 'Ditutup',
            self::Selesai => 'Selesai',
        };
    }
}
