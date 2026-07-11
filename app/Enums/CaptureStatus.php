<?php

namespace App\Enums;

enum CaptureStatus: string
{
    case PENDING = 'pending';
    case SANITIZED = 'sanitized';
    case CURATED = 'curated';
    case DISCARDED = 'discarded';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::SANITIZED => 'Sanitizada',
            self::CURATED => 'Curada',
            self::DISCARDED => 'Descartada pela política',
            self::FAILED => 'Falhou',
        };
    }
}
