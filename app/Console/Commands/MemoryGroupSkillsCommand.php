<?php

namespace App\Console\Commands;

use App\Models\Memory;
use App\Services\Curation\CurationFailedException;
use App\Services\Curation\SkillGroupProposer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MemoryGroupSkillsCommand extends Command
{
    protected $signature = 'memory:group-skills
                            {--min-recurrence=3 : Recorrência mínima para candidatura}';

    protected $description = 'Propõe agrupamentos de candidatas que compõem a mesma skill (motor propõe, humano aprova)';

    public function handle(SkillGroupProposer $proposer): int
    {
        $candidates = Memory::query()
            ->skillCandidates((int) $this->option('min-recurrence'))
            ->orderByDesc('recurrence_count')
            ->get();

        if ($candidates->count() < 2) {
            $this->info('Menos de 2 candidatas — nada a agrupar.');

            return self::SUCCESS;
        }

        $this->info("=== Agrupamento de Skills — {$candidates->count()} candidatas ===");

        try {
            $proposal = $proposer->propose($candidates);
        } catch (CurationFailedException $e) {
            $this->error('Motor não produziu proposta válida: '.$e->getMessage());

            return self::FAILURE;
        }

        $groups = $proposer->store($proposal);
        $titles = $candidates->pluck('title', 'id');

        foreach ($proposal->groups as $index => $group) {
            $number = $index + 1;
            $this->newLine();
            $this->line("<fg=cyan>GRUPO {$number}: {$group['name']}</> (coesão {$group['cohesion']})");
            $this->line("  Propósito: {$group['purpose']}");

            foreach ($group['memory_ids'] as $memoryId) {
                $this->line('    • '.$titles[$memoryId]);
            }
        }

        if ($proposal->standalone !== []) {
            $this->newLine();
            $this->line('<fg=yellow>STANDALONE (skill própria):</>');

            foreach ($proposal->standalone as $entry) {
                $this->line('    • '.$titles[$entry['memory_id']].' — '.$entry['reason']);
            }
        }

        if ($proposal->excluded !== []) {
            $this->newLine();
            $this->line('<fg=gray>EXCLUÍDAS:</>');

            foreach ($proposal->excluded as $entry) {
                $this->line('    • '.$titles[$entry['memory_id']].' — '.$entry['reason']);
            }
        }

        $path = 'curation/skill-groups-'.now()->format('Ymd-His').'.json';
        Storage::disk('local')->put($path, json_encode([
            'generated_at' => now()->toIso8601String(),
            'candidates' => $candidates->count(),
            'proposal' => $proposal->toArray(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->newLine();
        $this->info("{$groups->count()} grupos salvos como 'proposed' — aprove na UI ou via tinker antes da compilação.");
        $this->line('Proposta completa: storage/app/private/'.$path);

        return self::SUCCESS;
    }
}
