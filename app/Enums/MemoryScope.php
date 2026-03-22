<?php

namespace App\Enums;

enum MemoryScope: string
{
    case PROJECT = 'project';
    case GLOBAL = 'global';

    public function label(): string
    {
        return match ($this) {
            self::PROJECT => 'Projeto',
            self::GLOBAL => 'Global',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PROJECT => 'bg-neo-teal',
            self::GLOBAL => 'bg-neo-purple',
        };
    }
}
