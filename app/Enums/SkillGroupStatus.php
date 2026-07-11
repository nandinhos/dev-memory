<?php

namespace App\Enums;

enum SkillGroupStatus: string
{
    case PROPOSED = 'proposed';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case COMPILED = 'compiled';

    public function label(): string
    {
        return match ($this) {
            self::PROPOSED => 'Proposto',
            self::APPROVED => 'Aprovado',
            self::REJECTED => 'Rejeitado',
            self::COMPILED => 'Compilado',
        };
    }
}
