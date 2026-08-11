<?php

namespace App\Enums;

enum KnowledgeEdgeOrigin: string
{
    case DETERMINISTIC = 'deterministic';
    case AI_EXTRACTED = 'ai_extracted';
    case HUMAN = 'human';
    case IMPORTED = 'imported';
}
