<?php

namespace App\Enums;

enum UserRole: string
{
    case Head = 'head';
    case Developer = 'developer';

    public function label(): string
    {
        return match ($this) {
            self::Head => 'Head',
            self::Developer => 'Developer',
        };
    }
}
