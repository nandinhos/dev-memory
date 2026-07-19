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

    /**
     * Rótulo curto para o badge do card da listagem, onde o label longo não cabe ao lado
     * do status de validação. Mesma informação, escrita para leitura de relance.
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Context7 pendente',
            self::CONFIRMED => 'Context7 OK',
            self::PARTIALLY_CONFIRMED => 'Context7 parcial',
            self::CONTRADICTED => 'Contradiz doc',
            self::INCONCLUSIVE => 'Inconclusivo',
        };
    }

    /**
     * O card da listagem precisa de contraste sobre o header colorido — as cores de
     * color() são pensadas para o fundo branco do detalhe.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::PENDING => 'bg-white text-black',
            self::CONFIRMED => 'bg-neo-green text-black',
            self::PARTIALLY_CONFIRMED => 'bg-neo-yellow text-black',
            self::CONTRADICTED => 'bg-neo-magenta text-white',
            self::INCONCLUSIVE => 'bg-gray-300 text-black',
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
