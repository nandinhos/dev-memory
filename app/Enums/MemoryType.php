<?php

namespace App\Enums;

enum MemoryType: string
{
    case ERROR = 'error';
    case LESSON = 'lesson';
    case BEST_PRACTICE = 'best_practice';
    case WORKAROUND = 'workaround';
    case ARCHITECTURE_DECISION = 'architecture_decision';
    case ANTI_PATTERN = 'anti_pattern';

    public function label(): string
    {
        return match ($this) {
            self::ERROR => 'Erro',
            self::LESSON => 'Lição',
            self::BEST_PRACTICE => 'Boa Prática',
            self::WORKAROUND => 'Workaround',
            self::ARCHITECTURE_DECISION => 'Decisão Arquitetural',
            self::ANTI_PATTERN => 'Antipadrão',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ERROR => 'bg-neo-magenta',
            self::LESSON => 'bg-neo-yellow',
            self::BEST_PRACTICE => 'bg-neo-green',
            self::WORKAROUND => 'bg-neo-teal',
            self::ARCHITECTURE_DECISION => 'bg-neo-purple',
            self::ANTI_PATTERN => 'bg-neo-salmon',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::ERROR => 'ERR',
            self::LESSON => 'LEC',
            self::BEST_PRACTICE => 'BP',
            self::WORKAROUND => 'WA',
            self::ARCHITECTURE_DECISION => 'ADR',
            self::ANTI_PATTERN => 'AP',
        };
    }
}
