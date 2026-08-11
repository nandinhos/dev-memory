<?php

namespace App\Console\Commands;

use App\Jobs\ProjectMemoryKnowledgeGraphJob;
use App\Models\Memory;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('memory:graph:backfill {--sync : Executar a projeção imediatamente em vez de enfileirar}')]
#[Description('Projeta as memórias existentes no knowledge graph de forma idempotente.')]
class KnowledgeGraphBackfillCommand extends Command
{
    public function handle(): int
    {
        $count = Memory::count();

        if ($count === 0) {
            $this->info('Nenhuma memória disponível para projetar no knowledge graph.');

            return self::SUCCESS;
        }

        $this->info("Projetando {$count} memórias no knowledge graph...");
        $bar = $this->output->createProgressBar($count);

        Memory::query()->chunkById(100, function ($memories) use ($bar): void {
            foreach ($memories as $memory) {
                if ($this->option('sync')) {
                    ProjectMemoryKnowledgeGraphJob::dispatchSync($memory->id);
                } else {
                    ProjectMemoryKnowledgeGraphJob::dispatch($memory->id);
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Projeção do knowledge graph concluída.');

        return self::SUCCESS;
    }
}
