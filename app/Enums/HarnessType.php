<?php

namespace App\Enums;

enum HarnessType: string
{
    case CLAUDE_CODE = 'claude-code';
    case CODEX = 'codex';
    case ANTIGRAVITY = 'antigravity';
    case HERMES = 'hermes';

    public function label(): string
    {
        return match ($this) {
            self::CLAUDE_CODE => 'Claude Code',
            self::CODEX => 'OpenAI Codex',
            self::ANTIGRAVITY => 'Google Antigravity',
            self::HERMES => 'Hermes CLI',
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
            self::CODEX => [
                '~/.codex/config.toml',
                'AGENTS.md',
                'CLAUDE.md',
                '.mcp.json',
            ],
            self::ANTIGRAVITY => [
                '~/.gemini/config/AGENTS.md',
                '~/.gemini/antigravity/mcp_config.json',
                'AGENTS.md',
                '.mcp.json',
            ],
            self::HERMES => [
                '~/.hermes/config.json',
                'AGENTS.md',
                '.mcp.json',
            ],
        };
    }

    /**
     * Caminhos dos scripts de hook para captura contínua de aprendizados.
     *
     * @return list<string>
     */
    public function hookPaths(): array
    {
        return match ($this) {
            self::CLAUDE_CODE => ['~/.claude/hooks/post_tool_use.sh'],
            self::CODEX => ['~/.codex/hooks/post_execution.sh'],
            self::ANTIGRAVITY => ['.agents/hooks/post-execution.sh'],
            self::HERMES => ['~/.hermes/hooks/post_stop.sh'],
        };
    }
}
