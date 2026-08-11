<?php

namespace Tests\Feature;

use App\Enums\MemoryScope;
use App\Mcp\MemoryMcpServer;
use App\Models\Memory;
use App\Services\KnowledgeGraph\KnowledgeGraphProjector;
use App\Services\KnowledgeGraph\KnowledgeGraphQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeGraphQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_related_memories_with_explainable_evidence_paths_and_scope_isolation(): void
    {
        $projectId = fake()->uuid();
        $otherProjectId = fake()->uuid();

        $source = Memory::factory()->create([
            'title' => 'Fonte do projeto',
            'stack' => 'PostgreSQL',
            'scope' => MemoryScope::PROJECT,
            'project_id' => $projectId,
        ]);
        $sameProject = Memory::factory()->create([
            'title' => 'Relacionada no mesmo projeto',
            'stack' => 'PostgreSQL',
            'scope' => MemoryScope::PROJECT,
            'project_id' => $projectId,
        ]);
        $global = Memory::factory()->create([
            'title' => 'Relacionada global',
            'stack' => 'PostgreSQL',
            'scope' => MemoryScope::GLOBAL,
            'project_id' => null,
        ]);
        $otherProject = Memory::factory()->create([
            'title' => 'Memória de outro projeto',
            'stack' => 'PostgreSQL',
            'scope' => MemoryScope::PROJECT,
            'project_id' => $otherProjectId,
        ]);

        $projector = app(KnowledgeGraphProjector::class);
        collect([$source, $sameProject, $global, $otherProject])
            ->each(fn (Memory $memory) => $projector->projectMemory($memory));

        $results = app(KnowledgeGraphQueryService::class)->relatedTo($source, depth: 2);
        $ids = collect($results)->pluck('memory.id');

        $this->assertTrue($ids->contains($sameProject->id));
        $this->assertTrue($ids->contains($global->id));
        $this->assertFalse($ids->contains($otherProject->id));

        $related = collect($results)->firstWhere('memory.id', $sameProject->id);

        $this->assertSame(2, $related['depth']);
        $this->assertCount(2, $related['path']);
        $this->assertSame('applies_to', $related['path'][0]['relation']);
        $this->assertNotEmpty($related['path'][0]['evidence']);
        $this->assertArrayHasKey('excerpt', $related['path'][0]['evidence'][0]);
    }

    public function test_global_memory_does_not_expose_project_scoped_memories(): void
    {
        $global = Memory::factory()->create([
            'stack' => 'Laravel',
            'scope' => MemoryScope::GLOBAL,
            'project_id' => null,
        ]);
        $project = Memory::factory()->create([
            'stack' => 'Laravel',
            'scope' => MemoryScope::PROJECT,
            'project_id' => fake()->uuid(),
        ]);

        $projector = app(KnowledgeGraphProjector::class);
        $projector->projectMemory($global);
        $projector->projectMemory($project);

        $results = app(KnowledgeGraphQueryService::class)->relatedTo($global, depth: 2);

        $this->assertNotContains($project->id, collect($results)->pluck('memory.id'));
    }

    public function test_it_returns_an_empty_result_when_memory_has_not_been_projected(): void
    {
        $memory = Memory::factory()->create();

        $this->assertSame([], app(KnowledgeGraphQueryService::class)->relatedTo($memory));
    }

    public function test_mcp_exposes_related_memories_with_the_explanation_path(): void
    {
        $source = Memory::factory()->global()->create(['stack' => 'PostgreSQL']);
        $related = Memory::factory()->global()->create(['stack' => 'PostgreSQL']);
        $projector = app(KnowledgeGraphProjector::class);
        $projector->projectMemory($source);
        $projector->projectMemory($related);

        $response = app(MemoryMcpServer::class)->handle([
            'jsonrpc' => '2.0',
            'method' => 'tools/call',
            'params' => [
                'name' => 'memory_related',
                'arguments' => ['id' => $source->id, 'depth' => 2],
            ],
            'id' => 'graph-test',
        ]);

        $payload = json_decode($response['result']['content'][0]['text'], true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(1, $payload['total']);
        $this->assertSame($related->id, $payload['results'][0]['id']);
        $this->assertCount(2, $payload['results'][0]['path']);
        $this->assertNotEmpty($payload['results'][0]['path'][0]['evidence']);
    }
}
