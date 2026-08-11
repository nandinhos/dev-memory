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

    public int $tries = 3;

    public function __construct(
        public Memory $memory,
    ) {}

    public function handle(EmbeddingGeneratorService $generator): void
    {
        $textToEmbed = trim("{$this->memory->title}\n{$this->memory->description}\nStack: {$this->memory->stack}");

        $embeddingModel = $generator->space();
        $hash = $generator->contentHash($textToEmbed);

        // Não re-gerar se conteúdo e espaço vetorial não mudaram.
        if (
            $this->memory->embedding !== null
            && $this->memory->embedding_model === $embeddingModel
            && $this->memory->embedding_hash === $hash
        ) {
            return;
        }

        $result = $generator->generate($textToEmbed);

        if ($result === null) {
            logger()->warning('Embedding não gerado; a busca lexical continuará disponível.', [
                'memory_id' => $this->memory->id,
                'embedding_model' => $embeddingModel,
            ]);

            return;
        }

        $this->memory->updateQuietly([
            'embedding' => $result['embedding'],
            'embedding_model' => $result['model'],
            'embedding_hash' => $result['hash'],
        ]);
    }
}
