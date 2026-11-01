<?php

namespace App\Services\Curation;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

/**
 * Cadeia ordenada de modelos da curadoria (Curadoria v2):
 * gratuitos primeiro (slots de até 3), pagos baratos depois (3).
 *
 * A ordem é refinada pelo último `memory:models-check` persistido em
 * settings (`curation.models_check`): capazes primeiro, nunca-checados
 * no meio, reprovados por último (ainda tentados como último recurso).
 */
final class CurationModelChain
{
    public const CHECK_KEY = 'curation.models_check';

    /**
     * Candidatos declarados (config), com tier.
     *
     * @return list<array{id: string, tier: string}>
     */
    public static function candidates(): array
    {
        $candidates = [];

        foreach ((array) config('services.minimax.free_models', []) as $id) {
            $candidates[] = ['id' => (string) $id, 'tier' => 'free'];
        }

        foreach ((array) config('services.minimax.fallback_models', []) as $id) {
            $candidates[] = ['id' => (string) $id, 'tier' => 'paid'];
        }

        // O modelo primário configurado (painel/env) sempre participa da
        // cadeia (sem furar a fila free-first; entra no fim dos pagos).
        $primary = trim((string) config('services.minimax.model', ''));

        if ($primary !== '' && ! collect($candidates)->contains('id', $primary)) {
            $candidates[] = ['id' => $primary, 'tier' => 'paid'];
        }

        return collect($candidates)
            ->unique('id')
            ->reject(fn (array $c) => $c['id'] === '')
            ->values()
            ->all();
    }

    /**
     * IDs na ordem efetiva de tentativa do motor.
     *
     * @return list<string>
     */
    public static function orderedModelIds(): array
    {
        $check = self::lastCheck();

        return collect(self::candidates())
            ->sortBy(fn (array $c) => match ($check[$c['id']]['capable'] ?? null) {
                true => 0,
                null => 1,
                false => 2,
            })
            ->pluck('id')
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function lastCheck(): array
    {
        try {
            if (! Schema::hasTable('settings')) {
                return [];
            }

            $raw = Setting::get(self::CHECK_KEY);
            $decoded = $raw !== null ? json_decode($raw, true) : null;

            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable) {
            return [];
        }
    }
}
