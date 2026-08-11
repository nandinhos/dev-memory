# ADR 0001 - Fronteira de projeto no MCP

**Status:** Aceita em 2026-08-02

## Contexto

O hub recebia um token HTTP autenticado, mas as consultas MCP não eram filtradas pelo projeto e `project_id` não era materializado no pipeline de captures. Como consequência, a origem enviada pelo cliente podia ser confundida com uma fronteira de dados e memórias privadas sem projeto compartilhavam o mesmo valor nulo no grafo.

## Decisão

1. Criar `projects` pertencentes a usuários e vincular tokens e captures a esse identificador.
2. Emitir token comum com projeto. Token global usa a marca explícita `is_global` e só pode ser emitido por administrador.
3. Usar o token, e não argumentos MCP, como fonte de autorização para leitura, escrita e ingestão.
4. Expor a um token comum apenas memórias globais e memórias privadas do próprio projeto.
5. Tratar `source_project` como proveniência. Nunca derivar autorização dele.
6. Falhar fechado para registros legados sem `project_id`; a associação exige decisão administrativa explícita.
7. Associar perfis de harness ao usuário e restringir o instalador HTTP a administrador autenticado.

## Consequências

- Tokens remotos existentes sem projeto e sem a marca explícita precisam ser substituídos ou elevados conscientemente a token global de administrador.
- Dados históricos continuam disponíveis para administrador, mas não para tokens comuns até associação explícita.
- O grafo não considera duas memórias privadas com `project_id` nulo como pertencentes ao mesmo contexto.
- O deploy deve migrar, inventariar e classificar dados legados antes de reativar ingestão remota.
