<?php

namespace App\Enums;

enum SkillStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case PUBLISHED = 'published';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Rascunho',
            self::APPROVED => 'Aprovada',
            self::PUBLISHED => 'Publicada',
        };
    }
}
