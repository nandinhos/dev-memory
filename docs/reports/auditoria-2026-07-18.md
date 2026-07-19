# Auditoria multi-agente — 2026-07-18

Auditoria ultracode (4 dimensões: segurança, robustez, UI técnica, config de providers) com **verificação adversarial** por achado: 37 agentes, 33 achados verificados → **26 confirmados**, 7 refutados, 12 lows não-verificados.

Legenda: ✅ corrigido no lote 2026-07-18 · ⏳ backlog priorizado

## Confirmados

| # | Sev | Dim | Achado | Arquivo | Status |
|---|-----|-----|--------|---------|--------|
| 1 | high | robustness | retry_after da fila database (90s) é menor que o timeout dos jobs (300s) — jobs longos são marcados como falho | `config/queue.php` | ✅ |
| 2 | high | robustness | MiniMax fora do ar (5xx/429) marca capturas como FAILED em estado terminal — nenhum comando ou código reproces | `app/Console/Commands/MemoryProcessCapturesCommand.php` | ✅ |
| 3 | high | ui-technical | Formulário oferece 6 tipos de memória mas a validação só aceita 3 — impossível criar/editar workaround, archit | `app/Livewire/MemoryForm.php` | ✅ |
| 4 | high | ui-technical | Paginação da lista de memórias perde todos os filtros ativos ao trocar de página | `resources/views/livewire/memory-list.blade.php` | ✅ |
| 5 | medium | security | Segredos persistem em claro no banco (raw_content) e são exibidos na UI admin | `app/Services/Curation/CaptureService.php` | ⏳ |
| 6 | medium | security | POST /api/mcp sem nenhum rate limiting nem limite de payload | `routes/api.php` | ✅ |
| 7 | medium | security | Autorização admin é nominal: qualquer usuário autenticado administra tudo | `routes/web.php` | ⏳ |
| 8 | medium | security | CaptureSanitizer não cobre chaves privadas PEM nem Google API keys | `app/Services/Curation/CaptureSanitizer.php` | ✅ |
| 9 | medium | security | Runbook de produção não fixa APP_DEBUG=false e os templates .env vêm com true | `docs/deploy.md` | ⏳ |
| 10 | medium | security | Tokens MCP sem expiração e sem escopo — um token vazado tem poder total e vitalício | `app/Models/ApiToken.php` | ⏳ |
| 11 | medium | security | harness_capture aceita path arbitrário que vira passo write_file no plano de provisionamento | `app/Services/HarnessProfileService.php` | ⏳ |
| 12 | medium | robustness | Jobs sem failed() handler e sem scheduler: exceção inesperada ou kill por timeout deixa captura presa em 'sani | `app/Jobs/CurateCaptureJob.php` | ✅ |
| 13 | medium | robustness | Race condition na idempotência do CaptureService: check-then-create sem tratar violação do unique — ingest con | `app/Services/Curation/CaptureService.php` | ✅ |
| 14 | medium | robustness | RecurrenceScorer carrega TODAS as memórias (com descrição completa) em RAM e roda 5 cossenos por memória a cad | `app/Services/Curation/RecurrenceScorer.php` | ⏳ |
| 15 | medium | ui-technical | validation_status é gravado no banco sem nenhuma regra de validação | `app/Livewire/MemoryForm.php` | ✅ |
| 16 | medium | ui-technical | Excluir memória não dá feedback nenhum — flash 'success' é gravado mas nunca renderizado no destino | `app/Livewire/MemoryDetail.php` | ✅ |
| 17 | medium | ui-technical | Publicar skill roda git síncrono sem wire:loading, sem proteção a duplo clique e sem tratamento de erro | `app/Livewire/Admin/SkillsAdmin.php` | ⏳ |
| 18 | medium | ui-technical | Botão 'Promover p/ Global' no card de memória não promove — só navega para o detalhe | `resources/views/components/neo/memory-card.blade.php` | ✅ |
| 19 | medium | ui-technical | Drawer mobile: Escape não fecha, sem focus trap e botão hambúrguer sem aria-expanded | `resources/views/layouts/app.blade.php` | ✅ |
| 20 | medium | ui-technical | highlight.js carregado do CDN cloudflare em produção, sem SRI — dependência externa e vetor supply-chain | `resources/views/layouts/app.blade.php` | ⏳ |
| 21 | medium | ui-technical | Filtros da lista incompletos: 3 de 6 tipos, sem status 'superseded', e filtro de stack existe no backend mas n | `resources/views/livewire/memory-list.blade.php` | ⏳ |
| 22 | medium | provider-con | TypeError latente quando minimax.api_key resolve para null — quebra o estado 'ainda nao configurado' da tela | `app/Services/Curation/AnthropicCurationEngine.php` | ✅ |
| 23 | medium | provider-con | lastMeta() hardcoda provider='minimax' — trocar de provider pela tela gravaria proveniencia errada em curation | `app/Services/Curation/AnthropicCurationEngine.php` | ✅ |
| 24 | medium | provider-con | Encaixe da UI: replicar o padrao rota auth + componente em app/Livewire/Admin + link no bloco admin do sidebar | `resources/views/layouts/app.blade.php` | ⏳ |
| 25 | low | ui-technical | Loop principal de memórias sem wire:key numa lista que re-renderiza live | `resources/views/livewire/memory-list.blade.php` | ✅ |
| 26 | low | provider-con | Troca de motor de curadoria ja e pura config (engine anthropic-compatible generico), mas memory:curate instanc | `app/Console/Commands/MemoryCurateCommand.php` | ⏳ |

## Refutados pela verificação adversarial (não são problemas)

- (ui-technical) Estatísticas da sidebar da lista sempre exibem 0 — Collection::where compara enum com string
- (provider-config) Nao existe model Setting nem tabela settings — a tela precisa criar a base de persistencia do zero
- (provider-config) Ponto de integracao do override e AppServiceProvider::boot() mutando o config repository — engine e 
- (provider-config) Override de boot precisa falhar silencioso quando o DB esta indisponivel (migrate, package:discover,
- (provider-config) Salvar settings na UI exige queue:restart — os 2 workers de producao rodam com config carregada no b
- (provider-config) Segredo nunca re-exibido: seguir o precedente de ApiTokens, mas com cast encrypted (reversivel) em v
- (provider-config) Teste de conexao: MiniMax = POST /v1/messages com max_tokens 1; Context7 = GET /search?query=... (me

## Lows (não verificados; tratar oportunisticamente)

- (security) memory_create estoura com HTTP 500 quando title/description faltam (required só no schema)
- (security) Cookie de sessão sem flag Secure em produção HTTPS
- (robustness) Coluna validation_status sem índice, mas usada como filtro no MemoryList e em 2 counts do Dashboard
- (robustness) Zero visibilidade de failed_jobs e nenhuma ação de retry na UI admin — falhas de fila são invisíveis
- (robustness) SkillGroupsReview e SkillsAdmin renderizam com get() sem paginação (listas ilimitadas)
- (ui-technical) Toast de erro/aviso renderiza com estilo de sucesso — classe verde-neon fixa independente do type
- (ui-technical) Labels não associados aos inputs: componentes neo geram for=""/id="" quando id não é passado (login,
- (ui-technical) Telas admin de Grupos de Skills e Skills carregam tudo sem paginação
- (ui-technical) Realce de sintaxe some após qualquer ação Livewire na página de detalhe — hljs só roda em DOMContent
- (ui-technical) Loops aninhados sem wire:key nas telas de Grupos de Skills e Harness
- (provider-config) Tres consumidores type-hintam a classe concreta AnthropicCurationEngine, nao a interface — limita a 
- (provider-config) Chave do Context7 e opcional (keyless) — a tela nao pode exigir nem bloquear save vazio

## Detalhe completo
JSON bruto da auditoria (com detail/fix/verdict por achado) preservado no transcript do workflow `wf_51cb2adf-729`.
