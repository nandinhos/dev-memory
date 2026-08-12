<?php

namespace App\Mcp;

use App\Enums\HarnessType;
use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Jobs\CurateCaptureJob;
use App\Models\ApiToken;
use App\Models\Memory;
use App\Services\ConfirmationGuard;
use App\Services\Curation\CaptureService;
use App\Services\Curation\CurationFailedException;
use App\Services\HarnessHookGenerator;
use App\Services\HarnessInstallerGenerator;
use App\Services\HarnessProfileService;
use App\Services\HubBriefingService;
use App\Services\KnowledgeGraph\KnowledgeGraphQueryService;
use App\Services\KnowledgeGraph\RelationProposer;
use App\Services\McpAccessPolicy;
use App\Services\MemoryService;
use App\Services\Search\MemorySearchService;
use Illuminate\Auth\Access\AuthorizationException;

class MemoryMcpServer
{
    private array $tools = [];

    private array $resources = [];

    private ?ApiToken $token = null;

    public function __construct()
    {
        $this->registerTools();
        $this->registerResources();
    }

    private function registerTools(): void
    {
        $this->tools = [
            'memory_list' => [
                'name' => 'memory_list',
                'description' => 'Lista memórias técnicas com filtros opcionais',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => ['type' => 'string', 'enum' => ['error', 'lesson', 'best_practice'], 'description' => 'Filtrar por tipo'],
                        'scope' => ['type' => 'string', 'enum' => ['project', 'global'], 'description' => 'Filtrar por escopo'],
                        'stack' => ['type' => 'string', 'description' => 'Filtrar por stack (ex: Laravel)'],
                        'limit' => ['type' => 'integer', 'default' => 20, 'description' => 'Número máximo de resultados'],
                    ],
                ],
            ],
            'memory_search' => [
                'name' => 'memory_search',
                'description' => 'Busca memórias por texto livre combinando relevância lexical e semântica',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Termo de busca'],
                        'limit' => ['type' => 'integer', 'default' => 10],
                    ],
                    'required' => ['query'],
                ],
            ],
            'memory_get' => [
                'name' => 'memory_get',
                'description' => 'Retorna os detalhes de uma memória específica pelo ID',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string', 'description' => 'UUID da memória'],
                    ],
                    'required' => ['id'],
                ],
            ],
            'memory_related' => [
                'name' => 'memory_related',
                'description' => 'Retorna memórias relacionadas pelo knowledge graph, incluindo o caminho e as evidências de cada relação',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string', 'description' => 'UUID da memória de origem'],
                        'depth' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 3, 'default' => 2],
                        'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20],
                    ],
                    'required' => ['id'],
                ],
            ],
            'relation_extract_propose' => [
                'name' => 'relation_extract_propose',
                'description' => 'Propõe uma relação semântica entre duas memórias via IA. A relação nasce `proposed` e só entra no knowledge graph após aprovação humana na UI de admin. Não muta o grafo público.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'source_memory_id' => ['type' => 'string', 'description' => 'UUID da memória de origem (visível ao token)'],
                        'target_memory_id' => ['type' => 'string', 'description' => 'UUID da memória de destino (visível ao token)'],
                        'relation_hint' => [
                            'type' => 'string',
                            'enum' => ['causes', 'resolves', 'prevents', 'supports', 'contradicts', 'supersedes', 'depends_on', 'derived_from', 'duplicates'],
                            'description' => 'Opcional — dica de tipo de relação. Se omitido, a IA decide.',
                        ],
                    ],
                    'required' => ['source_memory_id', 'target_memory_id'],
                ],
            ],
            'memory_create' => [
                'name' => 'memory_create',
                'description' => 'Cria uma nova memória técnica',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => ['type' => 'string', 'description' => 'Título da memória'],
                        'description' => ['type' => 'string', 'description' => 'Descrição detalhada'],
                        'type' => ['type' => 'string', 'enum' => ['error', 'lesson', 'best_practice'], 'description' => 'Tipo'],
                        'stack' => ['type' => 'string', 'description' => 'Stack (ex: Laravel)'],
                        'scope' => ['type' => 'string', 'enum' => ['project', 'global'], 'default' => 'project'],
                    ],
                    'required' => ['title', 'description', 'type'],
                ],
            ],
            'memory_stats' => [
                'name' => 'memory_stats',
                'description' => 'Retorna estatísticas das memórias (total, por tipo, por escopo, top stacks)',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => (object) [],
                ],
            ],
            'memory_update' => [
                'name' => 'memory_update',
                'description' => 'Atualiza campos de uma memória existente (título, descrição, tipo, stack, escopo)',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string', 'description' => 'UUID da memória'],
                        'title' => ['type' => 'string'],
                        'description' => ['type' => 'string'],
                        'type' => ['type' => 'string', 'enum' => ['error', 'lesson', 'best_practice', 'workaround', 'architecture_decision', 'anti_pattern']],
                        'stack' => ['type' => 'string'],
                        'scope' => ['type' => 'string', 'enum' => ['project', 'global']],
                    ],
                    'required' => ['id'],
                ],
            ],
            'memory_validate' => [
                'name' => 'memory_validate',
                'description' => 'Marca uma memória como validada',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string', 'description' => 'UUID da memória'],
                    ],
                    'required' => ['id'],
                ],
            ],
            'memory_promote' => [
                'name' => 'memory_promote',
                'description' => 'Promove uma memória validada para escopo global (exige que já esteja validada)',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string', 'description' => 'UUID da memória'],
                    ],
                    'required' => ['id'],
                ],
            ],
            'memory_delete' => [
                'name' => 'memory_delete',
                'description' => 'Remove (soft-delete) uma memória. AÇÃO DESTRUTIVA: a primeira chamada retorna um preview + confirmation_token; chame novamente com o token para confirmar.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'string', 'description' => 'UUID da memória'],
                        'confirmation_token' => ['type' => 'string', 'description' => 'Token retornado na primeira chamada, para confirmar a exclusão'],
                    ],
                    'required' => ['id'],
                ],
            ],
            'hub_briefing' => [
                'name' => 'hub_briefing',
                'description' => 'Consulta preventiva ANTES de implementar: retorna riscos conhecidos, padrões aprovados, lições relevantes, problemas recorrentes e skills para o contexto (stack + descrição da tarefa)',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'stack' => ['type' => 'string', 'description' => 'Stack/tecnologia do contexto (ex: Laravel, Docker)'],
                        'description' => ['type' => 'string', 'description' => 'Descrição da tarefa/feature a ser planejada'],
                    ],
                ],
            ],
            'memory_ingest' => [
                'name' => 'memory_ingest',
                'description' => 'Ingere um evento bruto (bug resolvido, decisão, lição) no pipeline de curadoria. O conteúdo é sanitizado, deduplicado e curado automaticamente numa memória.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'content' => ['type' => 'string', 'description' => 'Conteúdo bruto do evento'],
                        'source' => ['type' => 'string', 'description' => 'Origem (ex: claude-code, codex)', 'default' => 'mcp'],
                        'trigger' => ['type' => 'string', 'description' => 'Gatilho (ex: bug_resolved, decision)'],
                        'project' => ['type' => 'string', 'description' => 'Projeto de origem'],
                    ],
                    'required' => ['content'],
                ],
            ],
            'harness_paths' => [
                'name' => 'harness_paths',
                'description' => 'Retorna os caminhos de configuração recomendados para capturar de um harness (ex: claude-code, codex, antigravity, hermes). Leia esses arquivos na máquina de origem e envie via harness_capture.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'harness' => ['type' => 'string', 'enum' => array_column(HarnessType::cases(), 'value'), 'description' => 'Harness alvo'],
                    ],
                    'required' => ['harness'],
                ],
            ],
            'harness_capture' => [
                'name' => 'harness_capture',
                'description' => 'Sobe (salva no hub) a configuração de um harness. Segredos são redigidos automaticamente. Envie os arquivos como lista de {path, content}.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'harness' => ['type' => 'string', 'enum' => array_column(HarnessType::cases(), 'value')],
                        'name' => ['type' => 'string', 'description' => 'Nome do perfil (ex: default, trabalho)', 'default' => 'default'],
                        'description' => ['type' => 'string'],
                        'files' => [
                            'type' => 'array',
                            'description' => 'Arquivos de config: [{path, content}]',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'path' => ['type' => 'string'],
                                    'content' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                    'required' => ['harness', 'files'],
                ],
            ],
            'harness_list' => [
                'name' => 'harness_list',
                'description' => 'Lista os perfis de harness salvos no hub',
                'inputSchema' => ['type' => 'object', 'properties' => (object) []],
            ],
            'harness_provision' => [
                'name' => 'harness_provision',
                'description' => 'Retorna o plano de instalação para replicar um perfil de harness numa máquina limpa (passos write_file + notas). Aplique os passos localmente.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'harness' => ['type' => 'string', 'enum' => array_column(HarnessType::cases(), 'value')],
                        'name' => ['type' => 'string', 'default' => 'default'],
                    ],
                    'required' => ['harness'],
                ],
            ],
            'harness_installer_script' => [
                'name' => 'harness_installer_script',
                'description' => 'Gera um script Bash de instalação idempotente para provisionar o harness em máquina limpa',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'harness' => ['type' => 'string', 'enum' => array_column(HarnessType::cases(), 'value')],
                        'name' => ['type' => 'string', 'default' => 'default'],
                    ],
                    'required' => ['harness'],
                ],
            ],
            'harness_hook_script' => [
                'name' => 'harness_hook_script',
                'description' => 'Gera um script de hook leve e não-bloqueante para captura contínua de aprendizados do harness',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'harness' => ['type' => 'string', 'enum' => array_column(HarnessType::cases(), 'value')],
                        'mcp_url' => ['type' => 'string', 'description' => 'URL do endpoint MCP (ex: https://devmemory.fssdev.com.br/api/mcp)', 'default' => 'https://devmemory.fssdev.com.br/api/mcp'],
                    ],
                    'required' => ['harness'],
                ],
            ],
        ];
    }

    private function registerResources(): void
    {
        $this->resources = [
            'memories://list' => [
                'uri' => 'memories://list',
                'name' => 'Lista de Memórias',
                'description' => 'Catálogo completo de memórias técnicas',
                'mimeType' => 'application/json',
            ],
        ];
    }

    public function handle(array $request, ?ApiToken $token = null): ?array
    {
        $this->token = $token;
        $method = $request['method'] ?? null;

        if (! array_key_exists('id', $request)) {
            return null;
        }

        $id = $request['id'] ?? null;

        if ($id !== null && ! is_int($id) && ! is_string($id)) {
            return $this->errorResponse(null, 'Invalid Request', -32600);
        }

        return match ($method) {
            'initialize' => $this->handleInitialize($id),
            'tools/list' => $this->handleToolsList($id),
            'tools/call' => $this->handleToolsCall($request['params'] ?? [], $id),
            'resources/list' => $this->handleResourcesList($id),
            'resources/read' => $this->handleResourcesRead($request['params'] ?? [], $id),
            default => $this->errorResponse($id, 'Method not found', -32601),
        };
    }

    private function handleInitialize(int|string|null $id): array
    {
        return $this->response($id, [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [
                'tools' => ['listChanged' => false],
                'resources' => ['listChanged' => false],
            ],
            'serverInfo' => [
                'name' => 'dev-memory-mcp',
                'version' => '1.0.0',
            ],
        ]);
    }

    private function handleToolsList(int|string|null $id): array
    {
        return $this->response($id, [
            'tools' => array_values($this->tools),
        ]);
    }

    private function handleToolsCall(array $params, int|string|null $id): array
    {
        $toolName = $params['name'] ?? null;
        $args = $params['arguments'] ?? [];

        try {
            $result = match ($toolName) {
                'memory_list' => $this->toolMemoryList($args),
                'memory_search' => $this->toolMemorySearch($args),
                'memory_get' => $this->toolMemoryGet($args),
                'memory_related' => $this->toolMemoryRelated($args),
                'relation_extract_propose' => $this->toolRelationExtractPropose($args),
                'memory_create' => $this->toolMemoryCreate($args),
                'memory_stats' => $this->toolMemoryStats(),
                'memory_update' => $this->toolMemoryUpdate($args),
                'memory_validate' => $this->toolMemoryValidate($args),
                'memory_promote' => $this->toolMemoryPromote($args),
                'memory_delete' => $this->toolMemoryDelete($args),
                'hub_briefing' => $this->toolHubBriefing($args),
                'memory_ingest' => $this->toolMemoryIngest($args),
                'harness_paths' => $this->toolHarnessPaths($args),
                'harness_capture' => $this->toolHarnessCapture($args),
                'harness_list' => $this->toolHarnessList(),
                'harness_provision' => $this->toolHarnessProvision($args),
                'harness_installer_script' => $this->toolHarnessInstallerScript($args),
                'harness_hook_script' => $this->toolHarnessHookScript($args),
                default => null,
            };
        } catch (AuthorizationException $exception) {
            $result = ['error' => $exception->getMessage()];
        }

        if ($result === null) {
            return $this->errorResponse($id, "Tool not found: {$toolName}", -32602);
        }

        return $this->response($id, [
            'content' => [
                [
                    'type' => 'text',
                    'text' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                ],
            ],
        ]);
    }

    private function handleResourcesList(int|string|null $id): array
    {
        return $this->response($id, [
            'resources' => array_values($this->resources),
        ]);
    }

    private function handleResourcesRead(array $params, int|string|null $id): array
    {
        $uri = $params['uri'] ?? null;

        $memories = $this->access()->memories($this->token)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get(['id', 'title', 'type', 'stack', 'scope', 'created_at']);

        return $this->response($id, [
            'contents' => [
                [
                    'uri' => $uri,
                    'mimeType' => 'application/json',
                    'text' => json_encode($memories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                ],
            ],
        ]);
    }

    private function toolMemoryList(array $args): array
    {
        $query = $this->access()->memories($this->token)
            ->when($args['type'] ?? null, fn ($q, $t) => $q->where('type', $t))
            ->when($args['scope'] ?? null, fn ($q, $s) => $q->where('scope', $s))
            ->when($args['stack'] ?? null, fn ($q, $s) => $q->where('stack', 'like', "%{$s}%"))
            ->orderBy('created_at', 'desc')
            ->limit($args['limit'] ?? 20)
            ->get();

        return [
            'total' => $query->count(),
            'memories' => $query->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'type' => $m->type->value,
                'stack' => $m->stack,
                'scope' => $m->scope->value,
                'created_at' => $m->created_at->toIso8601String(),
            ])->toArray(),
        ];
    }

    private function toolMemorySearch(array $args): array
    {
        $query = $args['query'] ?? '';
        $limit = $args['limit'] ?? 10;

        /** @var MemorySearchService $searchService */
        $searchService = app(MemorySearchService::class);
        $searchResult = $searchService->search($query, $this->visibilityFilters(), $limit);

        $results = $searchResult['results'];

        return [
            'query' => $query,
            'search_mode' => $searchResult['search_mode'],
            'total' => count($results),
            'results' => collect($results)->map(function ($item) {
                /** @var Memory $m */
                $m = $item['memory'] ?? $item;
                $similarity = $item['similarity'] ?? 0.0;
                $rankScore = $item['rank_score'] ?? 0.0;

                return [
                    'id' => $m->id,
                    'title' => $m->title,
                    'description' => substr($m->description, 0, 200).(strlen($m->description) > 200 ? '...' : ''),
                    'type' => $m->type->value,
                    'maturity' => $m->maturity?->value ?? 'provisional',
                    'similarity' => round($similarity, 4),
                    'rank_score' => round($rankScore, 6),
                    'score' => $m->recurrence_count,
                ];
            })->toArray(),
        ];
    }

    private function toolMemoryGet(array $args): array
    {
        $memory = $this->access()->memory($this->token, $args['id'] ?? '');

        if (! $memory) {
            return ['error' => 'Memória não encontrada'];
        }

        return [
            'id' => $memory->id,
            'title' => $memory->title,
            'description' => $memory->description,
            'type' => $memory->type->value,
            'stack' => $memory->stack,
            'scope' => $memory->scope->value,
            'validation_status' => $memory->validation_status->value,
            'official_reference' => $memory->official_reference,
            'recurrence_count' => $memory->recurrence_count,
            'created_at' => $memory->created_at->toIso8601String(),
            'updated_at' => $memory->updated_at->toIso8601String(),
        ];
    }

    private function toolMemoryRelated(array $args): array
    {
        $memory = $this->access()->memory($this->token, $args['id'] ?? '');

        if ($memory === null) {
            return ['error' => 'Memória não encontrada'];
        }

        $results = app(KnowledgeGraphQueryService::class)->relatedTo(
            $memory,
            (int) ($args['depth'] ?? 2),
            (int) ($args['limit'] ?? 20),
        );

        return [
            'memory_id' => $memory->id,
            'total' => count($results),
            'results' => collect($results)->map(fn (array $result) => [
                'id' => $result['memory']->id,
                'title' => $result['memory']->title,
                'type' => $result['memory']->type->value,
                'stack' => $result['memory']->stack,
                'scope' => $result['memory']->scope->value,
                'depth' => $result['depth'],
                'path' => $result['path'],
            ])->all(),
        ];
    }

    private function toolRelationExtractPropose(array $args): array
    {
        $source = $this->access()->memory($this->token, (string) ($args['source_memory_id'] ?? ''));
        $target = $this->access()->memory($this->token, (string) ($args['target_memory_id'] ?? ''));

        if ($source === null) {
            return ['error' => 'Memória de origem não encontrada (ou fora do escopo do token)'];
        }
        if ($target === null) {
            return ['error' => 'Memória de destino não encontrada (ou fora do escopo do token)'];
        }

        $hint = isset($args['relation_hint']) ? (string) $args['relation_hint'] : null;

        try {
            $result = app(RelationProposer::class)
                ->propose($source, $target, $hint);
        } catch (CurationFailedException $e) {
            return ['error' => 'Motor de curadoria indisponível: '.$e->getMessage()];
        } catch (\InvalidArgumentException $e) {
            return ['error' => $e->getMessage()];
        }

        return [
            'success' => true,
            ...$result,
            'message' => 'Relação proposta criada com status "proposed". Revisão humana required antes de entrar no knowledge graph.',
        ];
    }

    private function toolMemoryCreate(array $args): array
    {
        $allowedTypes = array_column(MemoryType::cases(), 'value');
        $allowedScopes = array_column(MemoryScope::cases(), 'value');

        $type = $args['type'] ?? null;
        $scope = $args['scope'] ?? 'project';

        if (trim((string) ($args['title'] ?? '')) === '' || trim((string) ($args['description'] ?? '')) === '') {
            return ['error' => 'title e description são obrigatórios'];
        }

        if (! in_array($type, $allowedTypes, true)) {
            return ['error' => 'Tipo inválido. Valores permitidos: '.implode(', ', $allowedTypes)];
        }

        if (! in_array($scope, $allowedScopes, true)) {
            return ['error' => 'Escopo inválido. Valores permitidos: '.implode(', ', $allowedScopes)];
        }

        if ($scope === MemoryScope::GLOBAL->value && ! $this->access()->canPublishGlobal($this->token)) {
            return ['error' => 'Apenas administradores podem criar memórias globais.'];
        }

        $memory = app(MemoryService::class)->create([
            'title' => $args['title'],
            'description' => $args['description'],
            'type' => $type,
            'stack' => $args['stack'] ?? null,
            'scope' => $scope,
            'project_id' => $scope === MemoryScope::PROJECT->value && ! $this->access()->canPublishGlobal($this->token)
                ? $this->access()->projectId($this->token)
                : null,
        ]);

        return [
            'success' => true,
            'id' => $memory->id,
            'message' => 'Memória criada com sucesso',
        ];
    }

    private function toolMemoryStats(): array
    {
        $memories = $this->access()->memories($this->token);

        return [
            'total' => (clone $memories)->count(),
            'by_type' => [
                'error' => (clone $memories)->where('type', 'error')->count(),
                'lesson' => (clone $memories)->where('type', 'lesson')->count(),
                'best_practice' => (clone $memories)->where('type', 'best_practice')->count(),
            ],
            'by_scope' => [
                'project' => (clone $memories)->where('scope', 'project')->count(),
                'global' => (clone $memories)->where('scope', 'global')->count(),
            ],
            'top_stacks' => (clone $memories)->selectRaw('stack, COUNT(*) as count')
                ->whereNotNull('stack')
                ->groupBy('stack')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
                ->toArray(),
        ];
    }

    private function toolMemoryUpdate(array $args): array
    {
        $memory = $this->access()->writableMemory($this->token, $args['id'] ?? '');

        if (! $memory) {
            return ['error' => 'Memória não encontrada'];
        }

        $data = [];

        foreach (['title', 'description', 'stack'] as $field) {
            if (array_key_exists($field, $args)) {
                $data[$field] = $args[$field];
            }
        }

        if (isset($args['type'])) {
            if (! in_array($args['type'], array_column(MemoryType::cases(), 'value'), true)) {
                return ['error' => 'Tipo inválido'];
            }
            $data['type'] = $args['type'];
        }

        if (isset($args['scope'])) {
            if (! in_array($args['scope'], array_column(MemoryScope::cases(), 'value'), true)) {
                return ['error' => 'Escopo inválido'];
            }
            if ($args['scope'] === MemoryScope::GLOBAL->value && ! $this->access()->canPublishGlobal($this->token)) {
                return ['error' => 'Apenas administradores podem promover memórias para o escopo global.'];
            }
            $data['scope'] = $args['scope'];
        }

        if ($data === []) {
            return ['error' => 'Nenhum campo para atualizar'];
        }

        app(MemoryService::class)->update($memory, $data);

        return ['success' => true, 'id' => $memory->id, 'message' => 'Memória atualizada'];
    }

    private function toolMemoryValidate(array $args): array
    {
        $memory = $this->access()->writableMemory($this->token, $args['id'] ?? '');

        if (! $memory) {
            return ['error' => 'Memória não encontrada'];
        }

        app(MemoryService::class)->validate($memory);

        return ['success' => true, 'id' => $memory->id, 'validation_status' => 'validated'];
    }

    private function toolMemoryPromote(array $args): array
    {
        $memory = $this->access()->memory($this->token, $args['id'] ?? '');

        if (! $memory) {
            return ['error' => 'Memória não encontrada'];
        }

        if (! $this->access()->canPublishGlobal($this->token)) {
            return ['error' => 'Apenas administradores podem promover memórias para o escopo global.'];
        }

        try {
            app(MemoryService::class)->promoteToGlobal($memory);
        } catch (\InvalidArgumentException $e) {
            return ['error' => 'A memória precisa estar validada antes de promover a global'];
        }

        return ['success' => true, 'id' => $memory->id, 'scope' => 'global'];
    }

    private function toolMemoryDelete(array $args): array
    {
        $memory = $this->access()->writableMemory($this->token, $args['id'] ?? '');

        if (! $memory) {
            return ['error' => 'Memória não encontrada'];
        }

        $guard = app(ConfirmationGuard::class);
        $token = $args['confirmation_token'] ?? null;

        if ($token !== null) {
            if (! $guard->consume('memory_delete', $memory->id, $token)) {
                return ['error' => 'Token de confirmação inválido ou expirado. Repita a operação sem token para obter um novo.'];
            }

            app(MemoryService::class)->delete($memory);

            return ['success' => true, 'id' => $memory->id, 'message' => 'Memória removida (soft-delete, recuperável).'];
        }

        return $guard->challenge('memory_delete', $memory->id, [
            'title' => $memory->title,
            'type' => $memory->type->value,
            'stack' => $memory->stack,
            'recurrence_count' => $memory->recurrence_count,
            'validation_status' => $memory->validation_status->value,
        ]);
    }

    private function toolHubBriefing(array $args): array
    {
        $projectId = $this->access()->isOperator($this->token)
            ? null
            : $this->access()->projectId($this->token);

        return app(HubBriefingService::class)->briefing(
            $args['stack'] ?? null,
            $args['description'] ?? null,
            $projectId,
        );
    }

    private function resolveHarness(mixed $value): ?HarnessType
    {
        return HarnessType::tryFrom(is_string($value) ? $value : '');
    }

    private function toolHarnessPaths(array $args): array
    {
        $harness = $this->resolveHarness($args['harness'] ?? null);

        if ($harness === null) {
            return ['error' => 'Harness inválido'];
        }

        return [
            'harness' => $harness->value,
            'recommended_paths' => $harness->recommendedPaths(),
            'note' => 'Leia os arquivos que existirem e envie via harness_capture como [{path, content}].',
        ];
    }

    private function toolHarnessCapture(array $args): array
    {
        $harness = $this->resolveHarness($args['harness'] ?? null);

        if ($harness === null) {
            return ['error' => 'Harness inválido'];
        }

        $files = $args['files'] ?? null;

        if (! is_array($files) || $files === []) {
            return ['error' => 'files é obrigatório (lista de {path, content})'];
        }

        try {
            $profile = app(HarnessProfileService::class)->capture(
                harness: $harness,
                files: $files,
                name: $args['name'] ?? 'default',
                description: $args['description'] ?? null,
                owner: $this->token?->user,
            );
        } catch (\InvalidArgumentException $e) {
            return ['error' => $e->getMessage()];
        }

        $redacted = collect($profile->files)
            ->filter(fn ($f) => ! empty($f['redactions']))
            ->pluck('path')
            ->values()
            ->all();

        return [
            'success' => true,
            'harness' => $profile->harness->value,
            'name' => $profile->name,
            'version' => $profile->version,
            'files_stored' => count($profile->files),
            'files_with_redactions' => $redacted,
            'message' => 'Configuração salva. Segredos foram redigidos automaticamente.',
        ];
    }

    private function toolHarnessList(): array
    {
        return [
            'profiles' => $this->access()->harnessProfiles($this->token)->orderBy('harness')->orderBy('name')
                ->get()
                ->map(fn ($p) => [
                    'harness' => $p->harness->value,
                    'name' => $p->name,
                    'version' => $p->version,
                    'files' => count($p->files),
                    'updated_at' => $p->updated_at->toIso8601String(),
                ])->all(),
        ];
    }

    private function toolHarnessProvision(array $args): array
    {
        $harness = $this->resolveHarness($args['harness'] ?? null);

        if ($harness === null) {
            return ['error' => 'Harness inválido'];
        }

        $profile = $this->access()->harnessProfiles($this->token)->where('harness', $harness->value)
            ->where('name', $args['name'] ?? 'default')
            ->first();

        if ($profile === null) {
            return ['error' => 'Perfil não encontrado. Capture a configuração primeiro com harness_capture.'];
        }

        return app(HarnessProfileService::class)->provisionPlan($profile);
    }

    private function toolHarnessInstallerScript(array $args): array
    {
        $harness = $this->resolveHarness($args['harness'] ?? null);

        if ($harness === null) {
            return ['error' => 'Harness inválido'];
        }

        $profile = $this->access()->harnessProfiles($this->token)->where('harness', $harness->value)
            ->where('name', $args['name'] ?? 'default')
            ->first();

        if ($profile === null) {
            return ['error' => 'Perfil não encontrado. Capture a configuração primeiro com harness_capture.'];
        }

        $generator = app(HarnessInstallerGenerator::class);

        return [
            'harness' => $harness->value,
            'name' => $profile->name,
            'version' => $profile->version,
            'script' => $generator->generateScript($profile),
        ];
    }

    private function toolHarnessHookScript(array $args): array
    {
        $harness = $this->resolveHarness($args['harness'] ?? null);

        if ($harness === null) {
            return ['error' => 'Harness inválido'];
        }

        $mcpUrl = $args['mcp_url'] ?? 'https://devmemory.fssdev.com.br/api/mcp';
        $generator = app(HarnessHookGenerator::class);

        return [
            'harness' => $harness->value,
            'hook_paths' => $harness->hookPaths(),
            'script' => $generator->generateHookScript($harness, $mcpUrl),
        ];
    }

    private function toolMemoryIngest(array $args): array
    {
        $content = $args['content'] ?? '';

        if (trim($content) === '') {
            return ['error' => 'content é obrigatório'];
        }

        $capture = app(CaptureService::class)->ingest(
            rawContent: $content,
            sourceSystem: $args['source'] ?? 'mcp',
            triggerEvent: $args['trigger'] ?? null,
            sourceProject: $args['project'] ?? null,
            projectId: $this->access()->isOperator($this->token) ? null : $this->access()->projectId($this->token),
        );

        if ($capture->wasRecentlyCreated) {
            CurateCaptureJob::dispatch($capture);
        }

        return [
            'capture_id' => $capture->id,
            'status' => $capture->status->value,
            'deduplicated' => ! $capture->wasRecentlyCreated,
            'message' => $capture->wasRecentlyCreated
                ? 'Captura recebida e enfileirada para curadoria.'
                : 'Captura idêntica já existente — ignorada (idempotência).',
        ];
    }

    private function response(int|string|null $id, array $result): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];
    }

    private function errorResponse(int|string|null $id, string $message, int $code): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];
    }

    private function access(): McpAccessPolicy
    {
        return app(McpAccessPolicy::class);
    }

    private function visibilityFilters(): array
    {
        if ($this->access()->isOperator($this->token)) {
            return [];
        }

        return ['visible_project_id' => $this->access()->projectId($this->token)];
    }
}
