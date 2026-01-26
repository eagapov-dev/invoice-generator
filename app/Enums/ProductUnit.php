<?php

namespace App\Enums;

enum ProductUnit: string
{
    case Hour = 'hour';
    case Piece = 'piece';
    case Service = 'service';

    public function label(): string
    {
        return match ($this) {
            self::Hour => 'Hour',
            self::Piece => 'Piece',
            self::Service => 'Service',
        };
    }
}
