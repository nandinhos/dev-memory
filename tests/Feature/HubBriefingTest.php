<?php

namespace Tests\Feature;

use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Enums\ValidationStatus;
use App\Jobs\CurateCaptureJob;
use App\Models\ApiToken;
use App\Models\Capture;
use App\Models\Memory;
use App\Models\User;
use App\Services\HubBriefingService;
use App\Services\KnowledgeGraph\KnowledgeGraphProjector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HubBriefingTest extends TestCase
{
    use RefreshDatabase;

    private function seedKnowledge(): void
    {
        Memory::create([
            'title' => 'ILIKE não funciona no SQLite',
            'description' => 'Usar LOWER + LIKE para portabilidade entre bancos.',
            'type' => MemoryType::ERROR,
            'stack' => 'Laravel, SQLite',
            'validation_status' => ValidationStatus::VALIDATED,
            'recurrence_count' => 5,
            'official_reference' => 'https://laravel.com/docs',
        ]);

        Memory::create([
            'title' => 'Sempre usar Form Requests',
            'description' => 'Validação em FormRequest dedicado.',
            'type' => MemoryType::BEST_PRACTICE,
            'stack' => 'Laravel',
            'validation_status' => ValidationStatus::VALIDATED,
            'recurrence_count' => 10,
        ]);

        Memory::create([
            'title' => 'Ruído não relacionado',
            'description' => 'Coisa de Python.',
            'type' => MemoryType::ERROR,
            'stack' => 'Python',
            'validation_status' => ValidationStatus::VALIDATED,
            'recurrence_count' => 3,
        ]);

        Memory::create([
            'title' => 'Erro pendente (não validado)',
            'description' => 'Não deve aparecer no briefing.',
            'type' => MemoryType::ERROR,
            'stack' => 'Laravel',
            'validation_status' => ValidationStatus::PENDING,
            'recurrence_count' => 99,
        ]);
    }

    public function test_briefing_returns_validated_knowledge_filtered_by_stack(): void
    {
        $this->seedKnowledge();

        $briefing = app(HubBriefingService::class)->briefing('Laravel');

        $riskTitles = collect($briefing['known_risks'])->pluck('title');
        $this->assertContains('ILIKE não funciona no SQLite', $riskTitles);
        $this->assertNotContains('Ruído não relacionado', $riskTitles);
        $this->assertNotContains('Erro pendente (não validado)', $riskTitles);

        $patternTitles = collect($briefing['approved_patterns'])->pluck('title');
        $this->assertContains('Sempre usar Form Requests', $patternTitles);
    }

    public function test_briefing_matches_description_keywords(): void
    {
        $this->seedKnowledge();

        $briefing = app(HubBriefingService::class)->briefing(null, 'problema com ILIKE portabilidade');

        $related = collect($briefing['related_to_description'])->pluck('title');
        $this->assertContains('ILIKE não funciona no SQLite', $related);
    }

    public function test_hub_briefing_tool_over_http(): void
    {
        $this->seedKnowledge();
        [, $token] = ApiToken::issue(User::factory()->create(), 'x');

        $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/mcp', [
                'jsonrpc' => '2.0',
                'method' => 'tools/call',
                'params' => ['name' => 'hub_briefing', 'arguments' => ['stack' => 'Laravel']],
                'id' => 1,
            ])
            ->assertOk()
            ->assertSee('known_risks');
    }

    public function test_memory_ingest_tool_creates_capture_and_dispatches_curation(): void
    {
        Queue::fake();
        [, $token] = ApiToken::issue(User::factory()->create(), 'x');

        $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/mcp', [
                'jsonrpc' => '2.0',
                'method' => 'tools/call',
                'params' => [
                    'name' => 'memory_ingest',
                    'arguments' => [
                        'content' => 'Bug corrigido: migrate falhava com duplicate column, resolvido com hasColumn.',
                        'source' => 'claude-code',
                        'trigger' => 'bug_resolved',
                        'project' => 'dev-memory',
                    ],
                ],
                'id' => 2,
            ])
            ->assertOk();

        $this->assertSame(1, Capture::count());
        Queue::assertPushed(CurateCaptureJob::class);
    }

    public function test_memory_ingest_is_idempotent(): void
    {
        Queue::fake();
        [, $token] = ApiToken::issue(User::factory()->create(), 'x');

        $call = fn () => $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/mcp', [
                'jsonrpc' => '2.0',
                'method' => 'tools/call',
                'params' => [
                    'name' => 'memory_ingest',
                    'arguments' => ['content' => 'Mesmo evento repetido', 'source' => 'claude-code'],
                ],
                'id' => 3,
            ]);

        $call()->assertOk();
        $call()->assertOk();

        $this->assertSame(1, Capture::count());
        Queue::assertPushed(CurateCaptureJob::class, 1);
    }

    public function test_briefing_enriches_results_with_knowledge_graph_insights(): void
    {
        $source = Memory::factory()->create([
            'title' => 'Deadlock de transação no Postgres',
            'type' => MemoryType::ERROR,
            'stack' => 'PostgreSQL',
            'scope' => MemoryScope::GLOBAL,
            'validation_status' => ValidationStatus::VALIDATED,
        ]);

        $solution = Memory::factory()->create([
            'title' => 'Usar select for update com ordenação consistente',
            'type' => MemoryType::BEST_PRACTICE,
            'stack' => 'PostgreSQL',
            'scope' => MemoryScope::GLOBAL,
            'validation_status' => ValidationStatus::VALIDATED,
        ]);

        $projector = app(KnowledgeGraphProjector::class);
        $projector->projectMemory($source);
        $projector->projectMemory($solution);

        $briefing = app(HubBriefingService::class)->briefing('PostgreSQL');

        $this->assertArrayHasKey('graph_insights', $briefing);
        $this->assertNotEmpty($briefing['graph_insights']);

        $insight = $briefing['graph_insights'][0];
        $this->assertArrayHasKey('source_id', $insight);
        $this->assertArrayHasKey('relation', $insight);
        $this->assertArrayHasKey('target_title', $insight);
        $this->assertStringContainsString('conexão(ões) no grafo', $briefing['summary']);
    }
}
