<?php

namespace App\Enums;

enum ValidationStatus: string
{
    case PENDING = 'pending';
    case VALIDATED = 'validated';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendente',
            self::VALIDATED => 'Validado',
            self::REJECTED => 'Rejeitado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'bg-neo-salmon',
            self::VALIDATED => 'bg-neo-green',
            self::REJECTED => 'bg-gray-400',
        };
    }
}
