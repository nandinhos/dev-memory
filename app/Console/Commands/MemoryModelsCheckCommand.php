<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\Curation\AnthropicCurationEngine;
use App\Services\Curation\CurationFailedException;
use App\Services\Curation\CurationModelChain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Probe de capacidade dos modelos de curadoria (Curadoria v2):
 * descobre modelos gratuitos vivos (preço 0, até 3) + pagos baratos em
 * /messages (3), executa as 3 tarefas reais do motor por candidato e
 * persiste o veredito em settings. Roda semanal via scheduler; o motor
 * ordena a cadeia por esse resultado (capaz → não-checado → reprovado).
 */
class MemoryModelsCheckCommand extends Command
{
    protected $signature = 'memory:models-check';

    protected $description = 'Verifica quais modelos (free primeiro, pagos baratos depois) sustentam as tarefas de curadoria';

    public function handle(): int
    {
        $candidates = $this->discoverCandidates();

        if ($candidates === []) {
            $this->warn('Nenhum modelo candidato (config vazia e listagem indisponível).');

            return self::FAILURE;
        }

        $results = [];

        foreach ($candidates as $candidate) {
            $started = microtime(true);
            $error = null;

            try {
                $this->probeModel($candidate['id']);
            } catch (\Throwable $e) {
                $error = mb_substr($e->getMessage(), 0, 200);
            }

            $results[$candidate['id']] = [
                'capable' => $error === null,
                'tier' => $candidate['tier'],
                'checked_at' => now()->toIso8601String(),
                'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                'error' => $error,
            ];

            $this->line(sprintf(
                '[%s] %s (%s) %dms%s',
                $error === null ? 'CAPAZ' : 'FALHA',
                $candidate['id'],
                $candidate['tier'],
                $results[$candidate['id']]['latency_ms'],
                $error !== null ? ' — '.$error : '',
            ));
        }

        Setting::put(CurationModelChain::CHECK_KEY, json_encode($results));

        $capable = collect($results)->where('capable', true)->count();
        $this->info("{$capable}/".count($results).' modelo(s) capazes. Cadeia atualizada.');

        return $capable > 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Config declarada + descoberta viva: gratuitos (preço 0, até 3) e,
     * para completar os pagos, os mais baratos com superfície /messages.
     *
     * @return list<array{id: string, tier: string}>
     */
    private function discoverCandidates(): array
    {
        $candidates = CurationModelChain::candidates();
        $known = collect($candidates)->pluck('id')->all();

        try {
            $response = Http::timeout(25)
                ->withHeaders([
                    'Authorization' => 'Bearer '.(string) config('services.minimax.api_key'),
                    'x-opencode-session' => 'devmemory-hub',
                    'User-Agent' => 'devmemory-hub/1.0',
                ])
                ->get((string) config('services.minimax.models_url'));

            $live = collect($response->json('data', []));

            $free = $live
                ->filter(fn (array $m) => ($m['cost']['text']['input']['price'] ?? null) === 0
                    && ($m['cost']['text']['output']['price'] ?? null) === 0)
                ->pluck('id')
                ->take(3);

            foreach ($free as $id) {
                if (! in_array($id, $known, true)) {
                    array_unshift($candidates, ['id' => (string) $id, 'tier' => 'free']);
                    $known[] = (string) $id;
                }
            }
        } catch (\Throwable) {
            // Descoberta é bônus: sem listagem, a cadeia declarada vale.
        }

        return collect($candidates)->unique('id')->values()->all();
    }

    /**
     * As 3 tarefas reais do motor; qualquer falha reprova o modelo.
     *
     * @throws CurationFailedException
     */
    private function probeModel(string $modelId): void
    {
        $engine = new AnthropicCurationEngine(model: $modelId, models: [$modelId]);

        // 1. Ingestão: rascunho estruturado completo.
        $engine->prepare('MODELS_CHECK: erro de exemplo ao rodar migration em produção; causa e solução conhecidas.');

        // 2. Relação: tipo dentro do enum + confiança numérica.
        $engine->completeJson(
            'Responda apenas JSON: {"relation": "supports|causes|supersedes", "confidence": 0.0-1.0, "evidence_excerpt": "trecho"}',
            'MODELS_CHECK_RELATION: memória A documenta a causa; memória B documenta a correção do mesmo erro.',
            function (array $json) {
                if (! in_array($json['relation'] ?? null, ['supports', 'causes', 'supersedes'], true)
                    || ! is_numeric($json['confidence'] ?? null)) {
                    throw new \InvalidArgumentException('relation/confidence fora do contrato');
                }

                return $json;
            },
        );

        // 3. Veredito documental: veredito dentro do enum + confiança.
        $engine->completeJson(
            'Responda apenas JSON: {"verdict": "confirmed|contradicted|inconclusive", "confidence": 0.0-1.0}',
            'MODELS_CHECK_VERDICT: a afirmação confere com a documentação citada como exemplo?',
            function (array $json) {
                if (! in_array($json['verdict'] ?? null, ['confirmed', 'contradicted', 'inconclusive'], true)
                    || ! is_numeric($json['confidence'] ?? null)) {
                    throw new \InvalidArgumentException('verdict/confidence fora do contrato');
                }

                return $json;
            },
        );
    }
}
