<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

/**
 * Resolve a configuração efetiva dos providers: valor salvo no painel
 * (criptografado em DB) sobrepõe o env; sem valor no painel, o env vale.
 * O override é aplicado no boot (AppServiceProvider), portanto DEPOIS do
 * config:cache carregar — funciona com config cacheada. Workers de fila
 * carregam config no start: a tela chama queue:restart ao salvar.
 */
class SettingsService
{
    /** chave do painel => chave de config sobreposta */
    public const OVERRIDES = [
        'curation.base_url' => 'services.minimax.base_url',
        'curation.api_key' => 'services.minimax.api_key',
        'curation.model' => 'services.minimax.model',
        'context7.base_url' => 'services.context7.base_url',
        'context7.api_key' => 'services.context7.api_key',
    ];

    public function applyOverrides(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            foreach (Setting::all() as $setting) {
                $target = self::OVERRIDES[$setting->key] ?? null;

                if ($target !== null && filled($setting->value)) {
                    config([$target => $setting->value]);
                }
            }
        } catch (\Throwable) {
            // DB indisponível (instalação, migração, APP_KEY trocada) — env prevalece.
        }
    }

    /**
     * De onde vem o valor efetivo: 'painel' | 'env' | 'nenhuma'.
     * Sem valor no painel, o que está em config() é necessariamente o env.
     */
    public function sourceOf(string $panelKey): string
    {
        try {
            if (Schema::hasTable('settings') && filled(Setting::get($panelKey))) {
                return 'painel';
            }
        } catch (\Throwable) {
            // sem DB — só o env conta
        }

        $target = self::OVERRIDES[$panelKey] ?? null;

        return ($target !== null && filled(config($target))) ? 'env' : 'nenhuma';
    }
}
