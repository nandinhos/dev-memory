<?php

namespace App\Mcp;

use App\Models\Memory;

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
        $memory = Memory::create([
            'title' => $args['title'],
            'description' => $args['description'],
            'type' => $args['type'],
            'stack' => $args['stack'] ?? null,
            'scope' => $args['scope'] ?? 'project',
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
