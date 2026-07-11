<?php

namespace App\Console\Commands;

use App\Enums\SkillStatus;
use App\Models\Skill;
use App\Services\Curation\SkillPublisher;
use Illuminate\Console\Command;

class MemoryPublishSkillsCommand extends Command
{
    protected $signature = 'memory:publish-skills
                            {--skill= : Publicar apenas a skill com este slug}';

    protected $description = 'Publica skills aprovadas no repositório git versionado (fonte de verdade de distribuição)';

    public function handle(SkillPublisher $publisher): int
    {
        $query = Skill::whereIn('status', [SkillStatus::APPROVED, SkillStatus::PUBLISHED]);

        if ($slug = $this->option('skill')) {
            $query->where('slug', $slug);
        } else {
            $query->where('status', SkillStatus::APPROVED);
        }

        $skills = $query->get();

        if ($skills->isEmpty()) {
            $this->info('Nenhuma skill aprovada para publicar. (Drafts precisam de aprovação antes.)');

            return self::SUCCESS;
        }

        foreach ($skills as $skill) {
            $published = $publisher->publish($skill);
            $this->info("  ✓ {$published->slug} v{$published->version} publicada");
        }

        $this->newLine();
        $this->line('Repositório: '.config('services.skills_repo.path'));

        return self::SUCCESS;
    }
}
