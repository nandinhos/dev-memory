<?php

namespace App\Jobs;

use App\Models\Memory;
use App\Services\Embeddings\EmbeddingGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateMemoryEmbeddingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Memory $memory,
    ) {}

    public function handle(EmbeddingGeneratorService $generator): void
    {
        $textToEmbed = trim("{$this->memory->title}\n{$this->memory->description}\nStack: {$this->memory->stack}");

        $hash = hash('sha256', $textToEmbed);

        // Não re-gerar se o conteúdo não mudou
        if ($this->memory->embedding !== null && $this->memory->embedding_hash === $hash) {
            return;
        }

        $result = $generator->generate($textToEmbed);

        if ($result !== null) {
            $this->memory->updateQuietly([
                'embedding' => $result['embedding'],
                'embedding_model' => $result['model'],
                'embedding_hash' => $result['hash'],
            ]);
        }
    }
}
