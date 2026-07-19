<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configuração administrada pela UI (tela CONFIGURAÇÕES). Valores são
 * criptografados at-rest com a APP_KEY (cast encrypted) — chaves de API
 * nunca ficam em texto plano no banco. A resolução em runtime é
 * "painel sobrepõe env": ver SettingsService::applyOverrides().
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected $casts = [
        'value' => 'encrypted',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $setting = static::find($key);

        return ($setting !== null && $setting->value !== null && $setting->value !== '')
            ? $setting->value
            : $default;
    }

    public static function put(string $key, ?string $value): void
    {
        if ($value === null || $value === '') {
            static::where('key', $key)->delete();

            return;
        }

        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
