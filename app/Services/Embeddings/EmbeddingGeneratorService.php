<?php

namespace App\Services\Embeddings;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingGeneratorService
{
    /**
     * Gera o vetor de embedding para o texto fornecido.
     * Tenta primeiro Ollama (local/gratuito), com fallback para MiniMax.
     *
     * @return array{embedding: array<float>, model: string, hash: string}|null
     */
    public function generate(string $text): ?array
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        $hash = hash('sha256', $text);

        // 1. Tentar Ollama local
        $ollamaResult = $this->generateViaOllama($text);
        if ($ollamaResult !== null) {
            return [
                'embedding' => $ollamaResult['embedding'],
                'model' => 'ollama/'.$ollamaResult['model'],
                'hash' => $hash,
            ];
        }

        // 2. Fallback para MiniMax
        $minimaxResult = $this->generateViaMiniMax($text);
        if ($minimaxResult !== null) {
            return [
                'embedding' => $minimaxResult['embedding'],
                'model' => 'minimax/embo-01',
                'hash' => $hash,
            ];
        }

        Log::warning('EmbeddingGeneratorService: Falha ao gerar embedding em todos os provedores (Ollama e MiniMax).');

        return null;
    }

    private function generateViaOllama(string $text): ?array
    {
        $host = config('services.ollama.host', env('OLLAMA_HOST', 'http://localhost:11434'));
        $model = config('services.ollama.embedding_model', env('OLLAMA_EMBED_MODEL', 'nomic-embed-text'));

        try {
            $response = Http::timeout(5)->post("{$host}/api/embeddings", [
                'model' => $model,
                'prompt' => $text,
            ]);

            if ($response->successful() && is_array($response->json('embedding'))) {
                $vector = $response->json('embedding');

                return [
                    'embedding' => $this->normalizeVectorDimensions($vector, 1536),
                    'model' => $model,
                ];
            }
        } catch (Exception $e) {
            Log::debug('Embedding via Ollama indisponível: '.$e->getMessage());
        }

        return null;
    }

    private function generateViaMiniMax(string $text): ?array
    {
        $apiKey = config('services.minimax.api_key', env('MINIMAX_API_KEY'));

        if (! $apiKey) {
            return null;
        }

        try {
            $response = Http::timeout(8)
                ->withToken($apiKey)
                ->post('https://api.minimaxi.chat/v1/embeddings', [
                    'model' => 'embo-01',
                    'texts' => [$text],
                    'type' => 'db',
                ]);

            if ($response->successful() && is_array($response->json('vectors.0'))) {
                $vector = $response->json('vectors.0');

                return [
                    'embedding' => $this->normalizeVectorDimensions($vector, 1536),
                    'model' => 'embo-01',
                ];
            }
        } catch (Exception $e) {
            Log::error('Embedding via MiniMax falhou: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Ajusta a dimensão do vetor para exatamente $targetDim (pad com zeros ou truncamento se necessário).
     *
     * @param  array<float>  $vector
     * @return array<float>
     */
    private function normalizeVectorDimensions(array $vector, int $targetDim = 1536): array
    {
        $count = count($vector);

        if ($count === $targetDim) {
            return array_map('floatval', $vector);
        }

        if ($count > $targetDim) {
            return array_map('floatval', array_slice($vector, 0, $targetDim));
        }

        // Truncado/padded se menor
        $padded = array_pad($vector, $targetDim, 0.0);

        return array_map('floatval', $padded);
    }
}
