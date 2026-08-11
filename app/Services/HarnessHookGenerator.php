<?php

namespace App\Services;

use App\Enums\HarnessType;

class HarnessHookGenerator
{
    /**
     * Gera o script de hook leve e não-bloqueante para envio contínuo de aprendizados.
     */
    public function generateHookScript(HarnessType $harness, string $mcpUrl, ?string $apiToken = null): string
    {
        $harnessLabel = $harness->label();
        $tokenHeader = $apiToken ? "Authorization: Bearer {$apiToken}" : 'Authorization: Bearer YOUR_API_TOKEN';
        $harnessSlug = $harness->value;

        $script = [];
        $script[] = '#!/usr/bin/env bash';
        $script[] = "# Dev Memory Hub - Hook de Captura Contínua ({$harnessLabel})";
        $script[] = '# Executado em background após conclusão de tarefa/ferramenta';
        $script[] = '';
        $script[] = 'set -euo pipefail';
        $script[] = '';
        $script[] = '# Coleta metadados do repositório local';
        $script[] = 'PROJECT_NAME=$(basename "$(git rev-parse --show-toplevel 2>/dev/null || pwd)")';
        $script[] = 'COMMIT_HASH=$(git rev-parse --short HEAD 2>/dev/null || echo "none")';
        $script[] = 'BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "unknown")';
        $script[] = '';
        $script[] = '# Captura os últimos argumentos/logs fornecidos via stdio ou arquivo de sessão';
        $script[] = 'CONTENT=$(cat - 2>/dev/null || echo "")';
        $script[] = '';
        $script[] = 'if [ -z "$CONTENT" ]; then';
        $script[] = '    exit 0';
        $script[] = 'fi';
        $script[] = '';
        $script[] = '# Monta o payload JSON-RPC para o endpoint MCP /api/mcp (memory_ingest).';
        $script[] = '# Usa jq --arg para que newlines/aspas/barras em $CONTENT nao quebrem o JSON';
        $script[] = '# (interpolacao raw em heredoc silenciosamente descartava o payload).';
        $script[] = 'PAYLOAD=$(jq -n \\';
        $script[] = '  --arg content "$CONTENT" \\';
        $script[] = '  --arg source "hook_'.$harnessSlug.'" \\';
        $script[] = '  --arg trigger "post_tool_use" \\';
        $script[] = '  --arg project "$PROJECT_NAME" \\';
        $script[] = '  \'{jsonrpc: "2.0", method: "tools/call", params: {name: "memory_ingest", arguments: {content: $content, source: $source, trigger: $trigger, project: $project}}, id: 1}\')';
        $script[] = '';
        $script[] = '# Dispara o envio via HTTP em background (não-bloqueante)';
        $script[] = 'curl -s -X POST "'.$mcpUrl.'" \\';
        $script[] = '  -H "Content-Type: application/json" \\';
        $script[] = '  -H "'.$tokenHeader.'" \\';
        $script[] = '  -d "$PAYLOAD" >/dev/null 2>&1 &';
        $script[] = '';

        return implode("\n", $script)."\n";
    }
}
