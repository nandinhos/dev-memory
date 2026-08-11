<?php

namespace App\Services\Embeddings;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class EmbeddingGeneratorService
{
    /**
     * Gera o vetor no espaço canônico configurado para este ambiente.
     *
     * @return array{embedding: array<float>, model: string, hash: string}|null
     */
    public function generate(string $text): ?array
    {
        $text = trim($text);

        if ($text === '') {
            return null;
        }

        $provider = $this->provider();
        $result = match ($provider) {
            'ollama' => $this->generateViaOllama($text),
            'minimax' => $this->generateViaMiniMax($text),
            default => throw new InvalidArgumentException("Provedor de embedding não suportado: {$provider}"),
        };

        if ($result !== null) {
            $embedding = $this->validateDimensions($result['embedding']);

            if ($embedding !== null) {
                return [
                    'embedding' => $embedding,
                    'model' => $this->space(),
                    'hash' => $this->contentHash($text),
                ];
            }
        }

        Log::warning("EmbeddingGeneratorService: Falha ao gerar embedding no espaço canônico [{$this->space()}].");

        return null;
    }

    public function space(): string
    {
        $provider = $this->provider();
        $model = match ($provider) {
            'ollama' => (string) config('services.embeddings.ollama.model'),
            'minimax' => (string) config('services.embeddings.minimax.model'),
            default => throw new InvalidArgumentException("Provedor de embedding não suportado: {$provider}"),
        };

        if ($model === '') {
            throw new InvalidArgumentException("Modelo de embedding não configurado para [{$provider}].");
        }

        return "{$provider}/{$model}";
    }

    public function contentHash(string $text): string
    {
        return hash('sha256', $this->space().'|'.trim($text));
    }

    private function provider(): string
    {
        return mb_strtolower(trim((string) config('services.embeddings.provider', 'minimax')));
    }

    private function generateViaOllama(string $text): ?array
    {
        $host = rtrim((string) config('services.embeddings.ollama.host'), '/');
        $model = (string) config('services.embeddings.ollama.model');

        try {
            // CPU Ollama pode levar 10s+ em textos longos; timeout generoso
            // (60s) cobre o caso real. Cloud Ollama responde em <1s.
            $response = Http::timeout(60)->post("{$host}/api/embeddings", [
                'model' => $model,
                'prompt' => $text,
            ]);

            if ($response->successful() && is_array($response->json('embedding'))) {
                $vector = $response->json('embedding');

                return [
                    'embedding' => $vector,
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
        $apiKey = config('services.minimax.api_key');

        if (! $apiKey) {
            return null;
        }

        try {
            $baseUrl = rtrim((string) config('services.embeddings.minimax.base_url'), '/');
            $model = (string) config('services.embeddings.minimax.model');

            $response = Http::timeout(8)
                ->withToken($apiKey)
                ->post("{$baseUrl}/embeddings", [
                    'model' => $model,
                    'texts' => [$text],
                    'type' => 'db',
                ]);

            $providerStatus = (int) $response->json('base_resp.status_code', 0);

            if ($response->successful() && $providerStatus === 0 && is_array($response->json('vectors.0'))) {
                $vector = $response->json('vectors.0');

                return [
                    'embedding' => $vector,
                    'model' => $model,
                ];
            }

            Log::warning('Embedding via MiniMax rejeitado pelo provedor.', [
                'http_status' => $response->status(),
                'provider_status' => $providerStatus,
                'provider_message' => (string) $response->json('base_resp.status_msg', 'resposta sem vetor'),
            ]);
        } catch (Exception $e) {
            Log::error('Embedding via MiniMax falhou: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Rejeita vetores fora do contrato. Padding/truncamento altera o espaço
     * semântico e não torna modelos diferentes compatíveis.
     *
     * @param  array<float>  $vector
     * @return array<float>
     */
    private function validateDimensions(array $vector): ?array
    {
        $expected = (int) config('services.embeddings.dimensions', 1536);
        $actual = count($vector);

        if ($expected <= 0) {
            throw new InvalidArgumentException('EMBEDDING_DIMENSIONS deve ser maior que zero.');
        }

        if ($actual !== $expected) {
            Log::error("Embedding incompatível com [{$this->space()}]: esperado {$expected}, recebido {$actual}.");

            return null;
        }

        return array_map('floatval', $vector);
    }
}
