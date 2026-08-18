<?php

namespace App\Enums;

enum RelationType: int
{
    case None = 0;
    case Friend = 1;
    case Blocked = 2;

    public function label(): string
    {
        return match ($this) {
            self::None => 'Brak',
            self::Friend => 'Znajomy',
            self::Blocked => 'Zablokowany',
        };
    }
}
