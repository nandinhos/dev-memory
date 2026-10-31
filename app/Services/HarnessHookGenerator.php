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

    /**
     * Gera o script de hook de pre-flight guard para consulta preventiva antes de tarefas.
     */
    public function generatePreflightHookScript(HarnessType $harness, string $mcpUrl, ?string $apiToken = null): string
    {
        $harnessLabel = $harness->label();
        $tokenHeader = $apiToken ? "Authorization: Bearer {$apiToken}" : 'Authorization: Bearer YOUR_API_TOKEN';

        $script = [];
        $script[] = '#!/usr/bin/env bash';
        $script[] = "# Dev Memory Hub - Pre-flight Guard Hook ({$harnessLabel})";
        $script[] = '# Consulta preventiva de lições, causalidade do grafo e riscos antes da execução';
        $script[] = '';
        $script[] = 'set -euo pipefail';
        $script[] = '';
        $script[] = '# Garante que jq e curl existam sem falhar a sessão do usuário';
        $script[] = 'if ! command -v jq >/dev/null 2>&1 || ! command -v curl >/dev/null 2>&1; then';
        $script[] = '    exit 0';
        $script[] = 'fi';
        $script[] = '';
        $script[] = '# Detecta a stack tecnológica dominante do projeto';
        $script[] = 'STACK=""';
        $script[] = 'if [ -f "composer.json" ]; then';
        $script[] = '    if grep -q "laravel/framework" composer.json 2>/dev/null; then';
        $script[] = '        STACK="Laravel"';
        $script[] = '    else';
        $script[] = '        STACK="PHP"';
        $script[] = '    fi';
        $script[] = 'elif [ -f "package.json" ]; then';
        $script[] = '    STACK="Node"';
        $script[] = 'elif [ -f "pyproject.toml" ] || [ -f "requirements.txt" ]; then';
        $script[] = '    STACK="Python"';
        $script[] = 'elif [ -f "Cargo.toml" ]; then';
        $script[] = '    STACK="Rust"';
        $script[] = 'elif [ -f "go.mod" ]; then';
        $script[] = '    STACK="Go"';
        $script[] = 'fi';
        $script[] = '';
        $script[] = 'PROJECT_NAME=$(basename "$(git rev-parse --show-toplevel 2>/dev/null || pwd)")';
        $script[] = 'TASK_DESC="${*:-$PROJECT_NAME}"';
        $script[] = '';
        $script[] = '# Monta o payload JSON-RPC para o endpoint MCP chamando hub_briefing';
        $script[] = 'PAYLOAD=$(jq -n \\';
        $script[] = '  --arg stack "$STACK" \\';
        $script[] = '  --arg desc "$TASK_DESC" \\';
        $script[] = '  \'{jsonrpc: "2.0", method: "tools/call", params: {name: "hub_briefing", arguments: {stack: $stack, description: $desc}}, id: 1}\')';
        $script[] = '';
        $script[] = '# Consulta o hub com timeout reduzido (3s) para não onerar o fluxo';
        $script[] = 'RESPONSE=$(curl -s --max-time 3 -X POST "'.$mcpUrl.'" \\';
        $script[] = '  -H "Content-Type: application/json" \\';
        $script[] = '  -H "'.$tokenHeader.'" \\';
        $script[] = '  -d "$PAYLOAD" 2>/dev/null || echo "")';
        $script[] = '';
        $script[] = 'if [ -z "$RESPONSE" ]; then';
        $script[] = '    exit 0';
        $script[] = 'fi';
        $script[] = '';
        $script[] = '# Extrai o conteúdo serializado em text';
        $script[] = 'RAW_TEXT=$(echo "$RESPONSE" | jq -r \'.result.content[0].text // empty\' 2>/dev/null || echo "")';
        $script[] = 'if [ -z "$RAW_TEXT" ]; then';
        $script[] = '    exit 0';
        $script[] = 'fi';
        $script[] = '';
        $script[] = 'TOTAL=$(echo "$RAW_TEXT" | jq -r \'.total_memories // 0\' 2>/dev/null || echo "0")';
        $script[] = 'if [ "$TOTAL" -eq 0 ]; then';
        $script[] = '    exit 0';
        $script[] = 'fi';
        $script[] = '';
        $script[] = 'echo -e "\\033[0;34m[DEV-MEMORY GUARD]\\033[0m Briefing preventivo: \\033[1m$TOTAL memórias\\033[0m mapeadas para stack \\033[1m${STACK:-geral}\\033[0m"';
        $script[] = '';
        $script[] = 'INSIGHTS_COUNT=$(echo "$RAW_TEXT" | jq -r \'.graph_insights | length // 0\' 2>/dev/null || echo "0")';
        $script[] = 'if [ "$INSIGHTS_COUNT" -gt 0 ]; then';
        $script[] = '    echo -e "\\033[1;36m  ↳ Causalidade & Relacionamentos no Grafo (Graph RAG):\\033[0m"';
        $script[] = '    echo "$RAW_TEXT" | jq -r \'.graph_insights[] | "    • [\(.relation)] \(.title) (confiança: \(.confidence))"\' 2>/dev/null | head -n 5 || true';
        $script[] = 'fi';
        $script[] = '';
        $script[] = 'ERRORS_COUNT=$(echo "$RAW_TEXT" | jq -r \'.errors | length // 0\' 2>/dev/null || echo "0")';
        $script[] = 'if [ "$ERRORS_COUNT" -gt 0 ]; then';
        $script[] = '    echo -e "\\033[0;31m  ↳ Erros Críticos Catalogados (Evitar):\\033[0m"';
        $script[] = '    echo "$RAW_TEXT" | jq -r \'.errors[] | "    ⚠️  \(.title)"\' 2>/dev/null | head -n 5 || true';
        $script[] = 'fi';
        $script[] = '';
        $script[] = 'BP_COUNT=$(echo "$RAW_TEXT" | jq -r \'.best_practices | length // 0\' 2>/dev/null || echo "0")';
        $script[] = 'if [ "$BP_COUNT" -gt 0 ]; then';
        $script[] = '    echo -e "\\033[0;32m  ↳ Boas Práticas Recomendadas:\\033[0m"';
        $script[] = '    echo "$RAW_TEXT" | jq -r \'.best_practices[] | "    ⭐ \(.title)"\' 2>/dev/null | head -n 5 || true';
        $script[] = 'fi';
        $script[] = '';

        return implode("\n", $script)."\n";
    }
}
