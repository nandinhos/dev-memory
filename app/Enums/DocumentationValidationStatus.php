<?php

namespace App\Enums;

enum DocumentationValidationStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PARTIALLY_CONFIRMED = 'partially_confirmed';
    case CONTRADICTED = 'contradicted';
    case INCONCLUSIVE = 'inconclusive';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Aguardando verificação',
            self::CONFIRMED => 'Confirmado pela documentação',
            self::PARTIALLY_CONFIRMED => 'Parcialmente confirmado',
            self::CONTRADICTED => 'Contradiz a documentação',
            self::INCONCLUSIVE => 'Inconclusivo',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'bg-neo-salmon',
            self::CONFIRMED => 'bg-neo-green',
            self::PARTIALLY_CONFIRMED => 'bg-neo-yellow',
            self::CONTRADICTED => 'bg-neo-magenta',
            self::INCONCLUSIVE => 'bg-gray-400',
        };
    }
}
