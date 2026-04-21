<?php

namespace App\Enums;

enum MemorySource: string
{
    case SERENA_MCP    = 'serena_mcp';
    case BASIC_MEMORY  = 'basic_memory';
    case DEVORQ_LESSONS = 'devorq_lessons';
    case FILE_DOCS     = 'file_docs';
    case DEVMEMORY     = 'devmemory';
    case VPS_HUB       = 'vps_hub';
    case MANUAL        = 'manual';
    case TROUBLESHOOTING = 'troubleshooting';
    case BUG_REPORT    = 'bug_report';
    case HANDOVER      = 'handover';
    case E2E_AUDIT     = 'e2e_audit';
    case SKILL_DOCS    = 'skill_docs';

    public function label(): string
    {
        return match ($this) {
            self::SERENA_MCP     => 'Serena MCP',
            self::BASIC_MEMORY    => 'Basic Memory',
            self::DEVORQ_LESSONS => 'DEVORQ Lessons',
            self::FILE_DOCS      => 'Documentação de Arquivos',
            self::DEVMEMORY      => 'Dev Memory',
            self::VPS_HUB        => 'VPS HUB',
            self::MANUAL          => 'Manual',
            self::TROUBLESHOOTING => 'Troubleshooting',
            self::BUG_REPORT     => 'Bug Report',
            self::HANDOVER       => 'Handover',
            self::E2E_AUDIT      => 'E2E Audit',
            self::SKILL_DOCS     => 'Skill Docs',
        };
    }
}
