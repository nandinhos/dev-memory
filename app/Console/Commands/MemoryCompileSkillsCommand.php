<?php

namespace App\Console\Commands;

use App\Enums\SkillGroupStatus;
use App\Models\SkillGroup;
use App\Services\Curation\CurationFailedException;
use App\Services\Curation\SkillCompiler;
use Illuminate\Console\Command;

class MemoryCompileSkillsCommand extends Command
{
    protected $signature = 'memory:compile-skills
                            {--group= : Compilar apenas o grupo com este slug}';

    protected $description = 'Compila grupos aprovados em skills (manifesto + markdown, status draft)';

    public function handle(SkillCompiler $compiler): int
    {
        $query = SkillGroup::where('status', SkillGroupStatus::APPROVED);

        if ($slug = $this->option('group')) {
            $query->where('slug', $slug);
        }

        $groups = $query->get();

        if ($groups->isEmpty()) {
            $this->info('Nenhum grupo aprovado para compilar.');

            return self::SUCCESS;
        }

        $failures = 0;

        foreach ($groups as $group) {
            $this->line("Compilando: {$group->name} ...");

            try {
                $skill = $compiler->compile($group);
                $sources = count($skill->manifest['evidence']['official_sources']);
                $this->info("  ✓ {$skill->slug} (draft) — {$sources} fonte(s) oficial(is) · storage/app/private/skills/{$skill->slug}.md");
            } catch (CurationFailedException $e) {
                $failures++;
                $this->error('  ✗ falhou: '.$e->getMessage());
            }
        }

        $this->newLine();
        $this->info(($groups->count() - $failures).' skill(s) compiladas como draft — revisar e aprovar antes de publicar.');

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
