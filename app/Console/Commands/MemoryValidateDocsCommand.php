<?php

namespace App\Console\Commands;

use App\Jobs\ValidateMemoryDocumentationJob;
use App\Models\Memory;
use Illuminate\Console\Command;

class MemoryValidateDocsCommand extends Command
{
    protected $signature = 'memory:validate-docs
                            {--sync : Processa na hora em vez de enfileirar}
                            {--limit=0 : Limitar número de memórias (0 = todas)}
                            {--revalidate : Inclui memórias já verificadas anteriormente}';

    protected $description = 'Despacha a validação documental (Context7 + motor) para memórias sem veredito';

    public function handle(): int
    {
        $query = Memory::query()->orderBy('created_at');

        if (! $this->option('revalidate')) {
            $query->whereNull('doc_validation_status');
        }

        $limit = (int) $this->option('limit');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $memories = $query->get();

        if ($memories->isEmpty()) {
            $this->info('Nenhuma memória pendente de validação documental.');

            return self::SUCCESS;
        }

        foreach ($memories as $memory) {
            $this->option('sync')
                ? ValidateMemoryDocumentationJob::dispatchSync($memory)
                : ValidateMemoryDocumentationJob::dispatch($memory);
        }

        $verb = $this->option('sync') ? 'processadas' : 'enfileiradas';
        $this->info("{$memories->count()} memórias {$verb}.");

        return self::SUCCESS;
    }
}
