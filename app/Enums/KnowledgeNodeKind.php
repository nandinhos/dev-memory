<?php

namespace App\Enums;

enum KnowledgeNodeKind: string
{
    case MEMORY = 'memory';
    case TECHNOLOGY = 'technology';
    case CONCEPT = 'concept';
    case ERROR_SIGNATURE = 'error_signature';
    case SOURCE = 'source';
    case SKILL = 'skill';
}
