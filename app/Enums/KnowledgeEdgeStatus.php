<?php

namespace App\Enums;

enum KnowledgeEdgeStatus: string
{
    case PROPOSED = 'proposed';
    case VALIDATED = 'validated';
    case REJECTED = 'rejected';
    case SUPERSEDED = 'superseded';
}
