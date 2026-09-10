<?php

namespace App\Enums;

enum MetodePelatihan: string
{
    case Luring = 'luring';
    case Daring = 'daring';
    case Blended = 'blended';

    public function label(): string
    {
        return match ($this) {
            self::Luring => 'Luring',
            self::Daring => 'Daring',
            self::Blended => 'Blended',
        };
    }
}
