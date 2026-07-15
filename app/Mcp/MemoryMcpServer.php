<?php

namespace App\Mcp;

use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Jobs\CurateCaptureJob;
use App\Models\Memory;
use App\Services\ConfirmationGuard;
use App\Services\Curation\CaptureService;
use App\Services\HubBriefingService;
use App\Services\MemoryService;

class MemoryMcpServer
{
    private array $tools = [];

    private array $resources = [];

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
                'description' => 'Busca memórias por texto livre (busca em título e descrição)',
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
                    'properties' => [],
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

    public function handle(array $request): array
    {
        $method = $request['method'] ?? null;
        $id = $request['id'] ?? null;

        return match ($method) {
            'initialize' => $this->handleInitialize($id),
            'tools/list' => $this->handleToolsList($id),
            'tools/call' => $this->handleToolsCall($request['params'] ?? [], $id),
            'resources/list' => $this->handleResourcesList($id),
            'resources/read' => $this->handleResourcesRead($request['params'] ?? [], $id),
            default => $this->errorResponse($id, 'Method not found', -32601),
        };
    }

    private function handleInitialize(?string $id): array
    {
        return $this->response($id, [
            'protocolVersion' => '2024-11-05',
            'capabilities' => [
                'tools' => true,
                'resources' => true,
            ],
            'serverInfo' => [
                'name' => 'dev-memory-mcp',
                'version' => '1.0.0',
            ],
        ]);
    }

    private function handleToolsList(?string $id): array
    {
        return $this->response($id, [
            'tools' => array_values($this->tools),
        ]);
    }

    private function handleToolsCall(array $params, ?string $id): array
    {
        $toolName = $params['name'] ?? null;
        $args = $params['arguments'] ?? [];

        $result = match ($toolName) {
            'memory_list' => $this->toolMemoryList($args),
            'memory_search' => $this->toolMemorySearch($args),
            'memory_get' => $this->toolMemoryGet($args),
            'memory_create' => $this->toolMemoryCreate($args),
            'memory_stats' => $this->toolMemoryStats(),
            'memory_update' => $this->toolMemoryUpdate($args),
            'memory_validate' => $this->toolMemoryValidate($args),
            'memory_promote' => $this->toolMemoryPromote($args),
            'memory_delete' => $this->toolMemoryDelete($args),
            'hub_briefing' => $this->toolHubBriefing($args),
            'memory_ingest' => $this->toolMemoryIngest($args),
            default => null,
        };

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

    private function handleResourcesList(?string $id): array
    {
        return $this->response($id, [
            'resources' => array_values($this->resources),
        ]);
    }

    private function handleResourcesRead(array $params, ?string $id): array
    {
        $uri = $params['uri'] ?? null;

        $memories = Memory::orderBy('created_at', 'desc')->limit(100)->get(['id', 'title', 'type', 'stack', 'scope', 'created_at']);

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
        $query = Memory::query()
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

        $results = Memory::where(fn ($q) => $q->where('title', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
        )
            ->orderBy('recurrence_count', 'desc')
            ->limit($limit)
            ->get();

        return [
            'query' => $query,
            'total' => $results->count(),
            'results' => $results->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'description' => substr($m->description, 0, 200).(strlen($m->description) > 200 ? '...' : ''),
                'type' => $m->type->value,
                'score' => $m->recurrence_count,
            ])->toArray(),
        ];
    }

    private function toolMemoryGet(array $args): array
    {
        $memory = Memory::find($args['id'] ?? '');

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

    private function toolMemoryCreate(array $args): array
    {
        $allowedTypes = array_column(MemoryType::cases(), 'value');
        $allowedScopes = array_column(MemoryScope::cases(), 'value');

        $type = $args['type'] ?? null;
        $scope = $args['scope'] ?? 'project';

        if (! in_array($type, $allowedTypes, true)) {
            return ['error' => 'Tipo inválido. Valores permitidos: '.implode(', ', $allowedTypes)];
        }

        if (! in_array($scope, $allowedScopes, true)) {
            return ['error' => 'Escopo inválido. Valores permitidos: '.implode(', ', $allowedScopes)];
        }

        $memory = Memory::create([
            'title' => $args['title'],
            'description' => $args['description'],
            'type' => $type,
            'stack' => $args['stack'] ?? null,
            'scope' => $scope,
        ]);

        return [
            'success' => true,
            'id' => $memory->id,
            'message' => 'Memória criada com sucesso',
        ];
    }

    private function toolMemoryStats(): array
    {
        return [
            'total' => Memory::count(),
            'by_type' => [
                'error' => Memory::where('type', 'error')->count(),
                'lesson' => Memory::where('type', 'lesson')->count(),
                'best_practice' => Memory::where('type', 'best_practice')->count(),
            ],
            'by_scope' => [
                'project' => Memory::where('scope', 'project')->count(),
                'global' => Memory::where('scope', 'global')->count(),
            ],
            'top_stacks' => Memory::selectRaw('stack, COUNT(*) as count')
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
        $memory = Memory::find($args['id'] ?? '');

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
        $memory = Memory::find($args['id'] ?? '');

        if (! $memory) {
            return ['error' => 'Memória não encontrada'];
        }

        app(MemoryService::class)->validate($memory);

        return ['success' => true, 'id' => $memory->id, 'validation_status' => 'validated'];
    }

    private function toolMemoryPromote(array $args): array
    {
        $memory = Memory::find($args['id'] ?? '');

        if (! $memory) {
            return ['error' => 'Memória não encontrada'];
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
        $memory = Memory::find($args['id'] ?? '');

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
        return app(HubBriefingService::class)->briefing(
            $args['stack'] ?? null,
            $args['description'] ?? null,
        );
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

    private function response(?string $id, array $result): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ];
    }

    private function errorResponse(?string $id, string $message, int $code): array
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
}
