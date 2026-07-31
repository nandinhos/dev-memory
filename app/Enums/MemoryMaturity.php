<?php

namespace App\Enums;

enum MemoryMaturity: string
{
    case WORKAROUND = 'workaround';
    case PROVISIONAL = 'provisional';
    case RECOMMENDED = 'recommended';
    case CANONICAL = 'canonical';
    case CONSOLIDATED = 'consolidated';

    public function label(): string
    {
        return match ($this) {
            self::WORKAROUND => 'Solução de Contorno (Workaround)',
            self::PROVISIONAL => 'Provisório',
            self::RECOMMENDED => 'Recomendado',
            self::CANONICAL => 'Canônico',
            self::CONSOLIDATED => 'Consolidado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::WORKAROUND => 'bg-amber-100 text-amber-900 border-amber-500',
            self::PROVISIONAL => 'bg-blue-100 text-blue-900 border-blue-500',
            self::RECOMMENDED => 'bg-emerald-100 text-emerald-900 border-emerald-500',
            self::CANONICAL => 'bg-purple-100 text-purple-900 border-purple-500',
            self::CONSOLIDATED => 'bg-indigo-100 text-indigo-900 border-indigo-500',
        };
    }

    /**
     * Regra de transição governada de maturidade.
     */
    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return true;
        }

        return match ($this) {
            self::WORKAROUND => in_array($target, [self::PROVISIONAL, self::RECOMMENDED], true),
            self::PROVISIONAL => in_array($target, [self::WORKAROUND, self::RECOMMENDED], true),
            self::RECOMMENDED => in_array($target, [self::CANONICAL, self::PROVISIONAL], true),
            self::CANONICAL => in_array($target, [self::CONSOLIDATED, self::RECOMMENDED], true),
            self::CONSOLIDATED => $target === self::CANONICAL,
        };
    }
}
