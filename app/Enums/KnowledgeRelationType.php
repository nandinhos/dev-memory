<?php

namespace App\Enums;

enum KnowledgeRelationType: string
{
    case CAUSES = 'causes';
    case RESOLVES = 'resolves';
    case PREVENTS = 'prevents';
    case SUPPORTS = 'supports';
    case CONTRADICTS = 'contradicts';
    case SUPERSEDES = 'supersedes';
    case DEPENDS_ON = 'depends_on';
    case APPLIES_TO = 'applies_to';
    case DERIVED_FROM = 'derived_from';
    case DUPLICATES = 'duplicates';

    public function requiresHumanReview(): bool
    {
        return in_array($this, [self::CONTRADICTS, self::SUPERSEDES], true);
    }
}
