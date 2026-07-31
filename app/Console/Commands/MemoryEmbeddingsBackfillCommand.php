<?php

namespace App\Console\Commands;

use App\Jobs\GenerateMemoryEmbeddingJob;
use App\Models\Memory;
use Illuminate\Console\Command;

class MemoryEmbeddingsBackfillCommand extends Command
{
    protected $signature = 'memory:embeddings:backfill {--sync : Executar sincronamente ao invés de enfileirar} {--force : Re-gerar mesmo para memórias com embedding já existente}';

    protected $description = 'Gera embeddings vetoriais para todas as memórias existentes no banco de dados.';

    public function handle(): int
    {
        $query = Memory::query();

        if (! $this->option('force')) {
            $query->whereNull('embedding');
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info('Nenhuma memória pendente para geração de embedding.');

            return self::SUCCESS;
        }

        $this->info("Iniciando geração de embeddings para {$count} memórias...");

        $bar = $this->output->createProgressBar($count);

        $query->chunk(50, function ($memories) use ($bar) {
            foreach ($memories as $memory) {
                if ($this->option('sync')) {
                    GenerateMemoryEmbeddingJob::dispatchSync($memory);
                } else {
                    GenerateMemoryEmbeddingJob::dispatch($memory);
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Processamento de embeddings concluído com sucesso!');

        return self::SUCCESS;
    }
}
