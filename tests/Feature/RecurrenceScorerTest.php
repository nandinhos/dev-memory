<?php

namespace Tests\Feature;

use App\Enums\MemoryType;
use App\Models\Capture;
use App\Models\Memory;
use App\Services\Curation\LessonDraft;
use App\Services\Curation\RecurrenceScorer;
use App\Services\Search\MemorySearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurrenceScorerTest extends TestCase
{
    use RefreshDatabase;

    private function draft(array $overrides = []): LessonDraft
    {
        return LessonDraft::fromArray(array_merge([
            'title' => 'Migration falha com duplicate column em banco com drift',
            'summary' => 'Migration re-adicionava colunas existentes; guardas Schema::hasColumn resolvem.',
            'problem' => 'migrate falhava com duplicate column name em bancos com drift',
            'root_cause' => 'migration re-adicionava colunas que a migration base já criava',
            'solution' => 'adicionar guarda Schema::hasColumn por coluna na migration',
            'category' => 'error',
            'technologies' => [['name' => 'Laravel', 'version' => '13']],
            'evidence' => [],
            'applicability' => [],
            'risks' => [],
            'confidence' => 0.9,
        ], $overrides));
    }

    /**
     * Mocka o MemorySearchService controlando quais memórias entram
     * no top-K de candidatos. O scorer não muda o comportamento
     * de scoring — só itera o subconjunto entregue pelo search.
     * Reproduzimos a coleção no formato real do serviço:
     *   ['results' => Collection<array{memory: Memory, ...}>]
     */
    private function fakeCandidates(array $memories): void
    {
        $this->mock(MemorySearchService::class, function ($mock) use ($memories) {
            $mock->shouldReceive('search')
                ->andReturn([
                    'results' => collect($memories)->map(fn (Memory $m) => [
                        'memory' => $m,
                        'similarity' => 0.0,
                        'lexical_score' => 0.0,
                        'rank_score' => 0.0,
                    ]),
                    'search_mode' => 'lexical',
                ]);
        });
    }

    public function test_matches_same_issue_with_different_wording(): void
    {
        $memory = Memory::create([
            'title' => 'Duplicate column ao rodar migrations com drift',
            'description' => "## Problema\nmigrate quebrava com duplicate column name porque colunas já existiam no banco.\n\n## Causa raiz\nmigration re-adicionava colunas criadas pela migration base\n\n## Solução\nguardas Schema::hasColumn por coluna",
            'type' => MemoryType::ERROR,
            'stack' => 'Laravel',
        ]);

        $this->fakeCandidates([$memory]);

        $match = app(RecurrenceScorer::class)->findMatch($this->draft());

        $this->assertNotNull($match);
        $this->assertGreaterThanOrEqual(RecurrenceScorer::TOTAL_FLOOR, $match->score->total);
        $this->assertTrue($match->independent);
    }

    public function test_does_not_match_unrelated_memory(): void
    {
        $memory = Memory::create([
            'title' => 'Vite requer Node 20 ou superior',
            'description' => 'npm run dev falha com crypto.getRandomValues em Node 18; atualizar via nvm.',
            'type' => MemoryType::ERROR,
            'stack' => 'Vite, Node.js',
        ]);

        // Top-K contém só a memória irrelevante — o scorer deve rejeitar
        // por não atingir o TEXT_FLOOR.
        $this->fakeCandidates([$memory]);

        $this->assertNull(app(RecurrenceScorer::class)->findMatch($this->draft()));
    }

    public function test_identical_text_scores_near_one(): void
    {
        $memory = Memory::create([
            'title' => 'Migration falha com duplicate column em banco com drift',
            'description' => 'Migration re-adicionava colunas existentes; guardas Schema::hasColumn resolvem. migrate falhava com duplicate column name. adicionar guarda Schema::hasColumn por coluna na migration',
            'type' => MemoryType::ERROR,
            'stack' => 'Laravel',
        ]);

        $this->fakeCandidates([$memory]);

        $score = app(RecurrenceScorer::class)->score($this->draft(), $memory);

        $this->assertGreaterThan(0.9, $score->components['text']);
    }

    /**
     * Onda 4 (2026-08-22) — o trade-off documentado: memórias fora do
     * top-K de candidatos não viram match, mesmo que o cosseno TF
     * isolado fosse alto. Aqui o mock do search entrega só a similar,
     * deliberadamente omitindo uma "memória-gatilho" quase idêntica
     * que existiria no banco. O scorer não pode achá-la porque ela
     * não foi entregue no top-K.
     */
    public function test_candidate_outside_topk_does_not_become_match(): void
    {
        // Memória similar: aparece no top-K (entregue via mock).
        $similar = Memory::create([
            'title' => 'Duplicate column ao rodar migrations com drift',
            'description' => "## Problema\nmigrate quebrava com duplicate column name porque colunas já existiam no banco.\n\n## Causa raiz\nmigration re-adicionava colunas criadas pela migration base\n\n## Solução\nguardas Schema::hasColumn por coluna",
            'type' => MemoryType::ERROR,
            'stack' => 'Laravel',
        ]);

        // Memória-gatilho: existe no banco mas é omitida do top-K
        // pelo mock do search. score() isolado provavelmente teria
        // passado dos floors; findMatch não pode considerá-la.
        $trigger = Memory::create([
            'title' => 'Migration duplicate column drift',
            'description' => 'migrate duplicate column name drift schema hasColumn guardas',
            'type' => MemoryType::ERROR,
            'stack' => 'Laravel',
        ]);

        $this->fakeCandidates([$similar]);

        $match = app(RecurrenceScorer::class)->findMatch($this->draft());

        $this->assertNotNull($match);
        $this->assertSame($similar->id, $match->memory->id);
        $this->assertNotSame(
            $trigger->id,
            $match->memory->id,
            'memória fora do top-K não pode virar match, mesmo que score() isolado fosse alto',
        );
    }

    public function test_picks_highest_score_among_candidates(): void
    {
        // Duas memórias candidatas — a mais similar (score total maior)
        // deve ser escolhida.
        $weak = Memory::create([
            'title' => 'Algumas palavras',
            'description' => 'descrição sem overlap forte com o draft.',
            'type' => MemoryType::ERROR,
            'stack' => 'Laravel',
        ]);

        $strong = Memory::create([
            'title' => 'Duplicate column ao rodar migrations com drift',
            'description' => "## Problema\nmigrate quebrava com duplicate column name porque colunas já existiam no banco.\n\n## Causa raiz\nmigration re-adicionava colunas criadas pela migration base\n\n## Solução\nguardas Schema::hasColumn por coluna",
            'type' => MemoryType::ERROR,
            'stack' => 'Laravel',
        ]);

        $this->fakeCandidates([$weak, $strong]);

        $match = app(RecurrenceScorer::class)->findMatch($this->draft());

        $this->assertNotNull($match);
        $this->assertSame($strong->id, $match->memory->id);
        $this->assertGreaterThan(0.5, $match->score->total);
    }

    /**
     * Verifica que o search é chamado com o texto consolidado do draft
     * (title+summary+problem+solution) e com visible_project_id quando
     * a capture tem projeto. Garante que o scorer está usando o search
     * como ponte de candidatura, não bypassando-o.
     */
    public function test_delegates_to_search_with_consolidated_text_and_project_filter(): void
    {
        $memory = Memory::create([
            'title' => 'Duplicate column ao rodar migrations com drift',
            'description' => "## Problema\nmigrate quebrava com duplicate column name porque colunas já existiam no banco.\n\n## Causa raiz\nmigration re-adicionava colunas criadas pela migration base\n## Solução\nguardas Schema::hasColumn por coluna",
            'type' => MemoryType::ERROR,
            'stack' => 'Laravel',
        ]);

        $capture = Capture::create([
            'source_system' => 'mcp',
            'raw_content' => 'draft content',
            'idempotency_key' => 'test-key-'.uniqid(),
            'project_id' => '11111111-1111-1111-1111-111111111111',
        ]);

        $expectedQuery = implode(' ', [
            $this->draft()->title,
            $this->draft()->summary,
            $this->draft()->problem,
            $this->draft()->solution,
        ]);

        $this->mock(MemorySearchService::class, function ($mock) use ($memory, $expectedQuery) {
            $mock->shouldReceive('search')
                ->once()
                ->withArgs(function (string $query, array $filters, int $limit) use ($expectedQuery) {
                    return trim($query) === trim($expectedQuery)
                        && ($filters['visible_project_id'] ?? null) === '11111111-1111-1111-1111-111111111111'
                        && $limit === RecurrenceScorer::CANDIDATE_LIMIT;
                })
                ->andReturn([
                    'results' => collect([[
                        'memory' => $memory,
                        'similarity' => 0.0,
                        'lexical_score' => 0.0,
                        'rank_score' => 0.0,
                    ]]),
                    'search_mode' => 'lexical',
                ]);
        });

        $match = app(RecurrenceScorer::class)->findMatch($this->draft(), $capture);

        $this->assertNotNull($match);
        $this->assertSame($memory->id, $match->memory->id);
    }
}
