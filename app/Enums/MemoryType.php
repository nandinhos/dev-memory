<?php

namespace App\Enums;

enum MemoryType: string
{
    case ERROR = 'error';
    case LESSON = 'lesson';
    case BEST_PRACTICE = 'best_practice';

    public function label(): string
    {
        return match ($this) {
            self::ERROR => 'Erro',
            self::LESSON => 'Lição',
            self::BEST_PRACTICE => 'Boa Prática',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ERROR => 'bg-neo-magenta',
            self::LESSON => 'bg-neo-yellow',
            self::BEST_PRACTICE => 'bg-neo-green',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::ERROR => 'ERR',
            self::LESSON => 'LEC',
            self::BEST_PRACTICE => 'BP',
        };
    }
}
