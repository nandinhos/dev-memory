<?php

namespace App\Enums;

enum Severity: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::LOW => 'Baixo',
            self::MEDIUM => 'Médio',
            self::HIGH => 'Alto',
            self::CRITICAL => 'Crítico',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::LOW => 'text-green-600',
            self::MEDIUM => 'text-yellow-600',
            self::HIGH => 'text-orange-600',
            self::CRITICAL => 'text-red-600',
        };
    }
}
