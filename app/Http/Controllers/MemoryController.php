<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMemoryRequest;
use App\Http\Requests\UpdateMemoryRequest;
use App\Services\MemoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class MemoryController extends Controller
{
    public function __construct(
        private MemoryService $service
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['type', 'stack', 'scope', 'search']);
        $memories = $this->service->list($filters);

        return response()->json([
            'data' => $memories->items(),
            'meta' => [
                'current_page' => $memories->currentPage(),
                'last_page' => $memories->lastPage(),
                'per_page' => $memories->perPage(),
                'total' => $memories->total(),
            ],
        ]);
    }

    public function store(StoreMemoryRequest $request): JsonResponse
    {
        $memory = $this->service->create($request->validated());

        return response()->json([
            'message' => 'Memória criada com sucesso',
            'data' => $memory,
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $memory = $this->service->findById($id);

        return response()->json([
            'data' => $memory,
        ]);
    }

    public function update(UpdateMemoryRequest $request, string $id): JsonResponse
    {
        $memory = $this->service->findById($id);
        $updated = $this->service->update($memory, $request->validated());

        return response()->json([
            'message' => 'Memória atualizada com sucesso',
            'data' => $updated,
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $memory = $this->service->findById($id);
        $this->service->delete($memory);

        return response()->json([
            'message' => 'Memória removida com sucesso',
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:1']);

        $results = $this->service->search($request->input('q'));

        return response()->json([
            'data' => $results,
        ]);
    }

    public function validate(string $id): JsonResponse
    {
        $memory = $this->service->findById($id);
        $validated = $this->service->validate($memory);

        return response()->json([
            'message' => 'Memória validada com sucesso',
            'data' => $validated,
        ]);
    }

    public function promoteToGlobal(string $id): JsonResponse
    {
        $memory = $this->service->findById($id);

        try {
            $promoted = $this->service->promoteToGlobal($memory);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => 'Memória promotionsada para escopo global',
            'data' => $promoted,
        ]);
    }

    public function stats(): JsonResponse
    {
        return response()->json($this->service->getStats());
    }
}
