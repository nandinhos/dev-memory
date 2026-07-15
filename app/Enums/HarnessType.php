<?php

namespace App\Enums;

enum HarnessType: string
{
    case CLAUDE_CODE = 'claude-code';
    // Futuros: codex, hermes, antigravity — adicionar caso + recommendedPaths.

    public function label(): string
    {
        return match ($this) {
            self::CLAUDE_CODE => 'Claude Code',
        };
    }

    /**
     * Caminhos de configuração recomendados para captura neste harness.
     * O agente na máquina de origem lê os que existirem e envia ao hub.
     *
     * @return list<string>
     */
    public function recommendedPaths(): array
    {
        return match ($this) {
            self::CLAUDE_CODE => [
                '~/.claude/CLAUDE.md',
                '~/.claude/settings.json',
                '~/.claude/keybindings.json',
                '.mcp.json',
            ],
        };
    }
}
