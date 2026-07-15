<?php

namespace Tests\Feature;

use App\Enums\MemoryType;
use App\Enums\SkillGroupStatus;
use App\Enums\SkillStatus;
use App\Livewire\Admin\CapturesInbox;
use App\Livewire\Admin\SkillGroupsReview;
use App\Livewire\Admin\SkillsAdmin;
use App\Models\Memory;
use App\Models\Skill;
use App\Models\SkillGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function makeGroup(SkillGroupStatus $status = SkillGroupStatus::PROPOSED): SkillGroup
    {
        $memory = Memory::create([
            'title' => 'Memória do grupo',
            'description' => 'x',
            'type' => MemoryType::LESSON,
        ]);

        $group = SkillGroup::create([
            'name' => 'Grupo de teste',
            'slug' => 'grupo-de-teste',
            'purpose' => 'p',
            'rationale' => 'r',
            'cohesion' => 0.9,
            'status' => $status,
        ]);
        $group->memories()->attach($memory->id);

        return $group;
    }

    private function makeSkill(SkillStatus $status = SkillStatus::DRAFT): Skill
    {
        return Skill::create([
            'slug' => 'skill-de-teste',
            'name' => 'Skill de Teste',
            'version' => '1.0.0',
            'status' => $status,
            'manifest' => [
                'slug' => 'skill-de-teste',
                'name' => 'Skill de Teste',
                'purpose' => 'p',
                'activation' => ['technologies' => ['laravel'], 'triggers' => ['x']],
                'preconditions' => [],
                'workflow' => [['order' => 1, 'action' => 'a', 'validation' => null]],
                'guardrails' => [],
                'anti_patterns' => [],
                'evidence' => ['lesson_ids' => ['m1'], 'official_sources' => ['https://laravel.com/docs']],
                'test_cases' => [],
            ],
        ]);
    }

    public function test_skill_groups_review_approves_and_rejects(): void
    {
        $group = $this->makeGroup();

        Livewire::test(SkillGroupsReview::class)
            ->call('approve', $group->id);
        $this->assertSame(SkillGroupStatus::APPROVED, $group->fresh()->status);

        Livewire::test(SkillGroupsReview::class)
            ->call('reject', $group->id);
        $this->assertSame(SkillGroupStatus::REJECTED, $group->fresh()->status);
    }

    public function test_skills_admin_approves_draft(): void
    {
        $skill = $this->makeSkill();

        Livewire::test(SkillsAdmin::class)
            ->call('approve', $skill->id);

        $this->assertSame(SkillStatus::APPROVED, $skill->fresh()->status);
    }

    public function test_skills_admin_blocks_publishing_a_draft(): void
    {
        $skill = $this->makeSkill(SkillStatus::DRAFT);

        Livewire::test(SkillsAdmin::class)
            ->call('publish', $skill->id);

        $this->assertSame(SkillStatus::DRAFT, $skill->fresh()->status);
    }

    public function test_skills_admin_publishes_approved_skill(): void
    {
        Process::fake();
        $repo = storage_path('framework/testing/admin-skills-repo');
        File::deleteDirectory($repo);
        config(['services.skills_repo.path' => $repo]);

        $skill = $this->makeSkill(SkillStatus::APPROVED);

        Livewire::test(SkillsAdmin::class)
            ->call('publish', $skill->id);

        $this->assertSame(SkillStatus::PUBLISHED, $skill->fresh()->status);
        $this->assertFileExists("{$repo}/skills/skill-de-teste/SKILL.md");

        File::deleteDirectory($repo);
    }

    public function test_captures_inbox_renders(): void
    {
        Livewire::test(CapturesInbox::class)->assertOk();
    }

    public function test_admin_routes_require_auth(): void
    {
        auth()->logout();

        $this->get(route('admin.skills'))->assertRedirect(route('login'));
        $this->get(route('admin.skill-groups'))->assertRedirect(route('login'));
        $this->get(route('admin.captures'))->assertRedirect(route('login'));
    }
}
