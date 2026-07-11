<?php

namespace Tests\Feature;

use App\Enums\SkillStatus;
use App\Models\Skill;
use App\Services\Curation\SkillMarkdownRenderer;
use App\Services\Curation\SkillPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class SkillPublisherTest extends TestCase
{
    use RefreshDatabase;

    private string $repoPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoPath = storage_path('framework/testing/skills-repo');
        File::deleteDirectory($this->repoPath);
        Process::fake();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->repoPath);
        parent::tearDown();
    }

    private function makeApprovedSkill(): Skill
    {
        return Skill::create([
            'slug' => 'migrations-idempotentes',
            'name' => 'Migrations Idempotentes',
            'version' => '1.0.0',
            'status' => SkillStatus::APPROVED,
            'manifest' => [
                'schema_version' => '1.0',
                'slug' => 'migrations-idempotentes',
                'name' => 'Migrations Idempotentes',
                'purpose' => 'Evitar duplicate column em bancos com drift.',
                'activation' => ['technologies' => ['laravel'], 'triggers' => ['migration incremental']],
                'preconditions' => ['Projeto usa migrations.'],
                'workflow' => [['order' => 1, 'action' => 'Guardar colunas com hasColumn.', 'validation' => null]],
                'guardrails' => ['Nunca assumir schema limpo.'],
                'anti_patterns' => ['Migration sem guarda.'],
                'evidence' => [
                    'lesson_ids' => ['mem-1'],
                    'official_sources' => ['https://laravel.com/docs/13.x/migrations'],
                ],
                'test_cases' => [['name' => 'Re-execução com drift', 'expected' => 'sem erro']],
            ],
        ]);
    }

    private function publisher(): SkillPublisher
    {
        return new SkillPublisher(new SkillMarkdownRenderer, $this->repoPath);
    }

    public function test_publishes_skill_writing_files_and_committing(): void
    {
        $skill = $this->makeApprovedSkill();

        $published = $this->publisher()->publish($skill);

        $this->assertSame(SkillStatus::PUBLISHED, $published->status);
        $this->assertSame('1.0.0', $published->version);

        $this->assertFileExists("{$this->repoPath}/skills/migrations-idempotentes/SKILL.md");
        $this->assertFileExists("{$this->repoPath}/skills/migrations-idempotentes/manifest.json");
        $this->assertFileExists("{$this->repoPath}/README.md");

        $markdown = File::get("{$this->repoPath}/skills/migrations-idempotentes/SKILL.md");
        $this->assertStringContainsString('https://laravel.com/docs/13.x/migrations', $markdown);

        $readme = File::get("{$this->repoPath}/README.md");
        $this->assertStringContainsString('`migrations-idempotentes`', $readme);

        Process::assertRan(fn ($process) => str_contains($process->command[1] ?? '', 'init'));
        Process::assertRan(fn ($process) => in_array('commit', $process->command, true)
            && str_contains(implode(' ', $process->command), 'publica migrations-idempotentes v1.0.0'));
    }

    public function test_republish_bumps_patch_version(): void
    {
        $skill = $this->makeApprovedSkill();
        $publisher = $this->publisher();

        $publisher->publish($skill);
        $republished = $publisher->publish($skill->fresh());

        $this->assertSame('1.0.1', $republished->version);

        Process::assertRan(fn ($process) => in_array('commit', $process->command, true)
            && str_contains(implode(' ', $process->command), 'v1.0.1'));
    }
}
