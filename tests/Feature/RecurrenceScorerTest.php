<?php

namespace Tests\Feature;

use App\Enums\MemoryType;
use App\Models\Memory;
use App\Services\Curation\LessonDraft;
use App\Services\Curation\RecurrenceScorer;
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

    public function test_matches_same_issue_with_different_wording(): void
    {
        Memory::create([
            'title' => 'Duplicate column ao rodar migrations com drift',
            'description' => "## Problema\nmigrate quebrava com duplicate column name porque colunas já existiam no banco.\n\n## Causa raiz\nmigration re-adicionava colunas criadas pela migration base\n\n## Solução\nguardas Schema::hasColumn por coluna",
            'type' => MemoryType::ERROR,
            'stack' => 'Laravel',
        ]);

        $match = (new RecurrenceScorer)->findMatch($this->draft());

        $this->assertNotNull($match);
        $this->assertGreaterThanOrEqual(RecurrenceScorer::TOTAL_FLOOR, $match->score->total);
        $this->assertTrue($match->independent);
    }

    public function test_does_not_match_unrelated_memory(): void
    {
        Memory::create([
            'title' => 'Vite requer Node 20 ou superior',
            'description' => 'npm run dev falha com crypto.getRandomValues em Node 18; atualizar via nvm.',
            'type' => MemoryType::ERROR,
            'stack' => 'Vite, Node.js',
        ]);

        $this->assertNull((new RecurrenceScorer)->findMatch($this->draft()));
    }

    public function test_identical_text_scores_near_one(): void
    {
        $memory = Memory::create([
            'title' => 'Migration falha com duplicate column em banco com drift',
            'description' => 'Migration re-adicionava colunas existentes; guardas Schema::hasColumn resolvem. migrate falhava com duplicate column name. adicionar guarda Schema::hasColumn por coluna na migration',
            'type' => MemoryType::ERROR,
            'stack' => 'Laravel',
        ]);

        $score = (new RecurrenceScorer)->score($this->draft(), $memory);

        $this->assertGreaterThan(0.9, $score->components['text']);
    }
}
