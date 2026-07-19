<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tela CONFIGURAÇÕES: providers do motor de curadoria e Context7.
 * Segurança: chaves de API são WRITE-ONLY — nunca são carregadas de volta
 * para o formulário nem exibidas; ficam criptografadas em DB (cast encrypted).
 * Painel sobrepõe env; limpar volta ao env. Salvar reinicia os workers
 * (queue:restart) para não rodarem com config velha.
 */
#[Title('Configurações')]
class SystemSettings extends Component
{
    public string $curationBaseUrl = '';

    public string $curationModel = '';

    public string $curationApiKey = '';

    public string $context7BaseUrl = '';

    public string $context7ApiKey = '';

    /** @var array<string, string> chave do painel => 'painel'|'env'|'nenhuma' */
    public array $sources = [];

    /** Resultado persistente do último teste de conexão, por seção. @var array{type:string,message:string} */
    public array $curationTest = [];

    /** @var array{type:string,message:string} */
    public array $context7Test = [];

    public function mount(SettingsService $settings): void
    {
        // Campos NÃO-secretos são pré-preenchidos com o valor efetivo.
        $this->curationBaseUrl = (string) config('services.minimax.base_url');
        $this->curationModel = (string) config('services.minimax.model');
        $this->context7BaseUrl = (string) config('services.context7.base_url');

        $this->refreshSources($settings);
    }

    public function saveCuration(SettingsService $settings): void
    {
        $this->validate([
            'curationBaseUrl' => ['required', 'url', 'max:500'],
            'curationModel' => ['required', 'string', 'max:120'],
            'curationApiKey' => ['nullable', 'string', 'max:2000'],
        ]);

        Setting::put('curation.base_url', $this->curationBaseUrl);
        Setting::put('curation.model', $this->curationModel);

        // Chave: campo vazio = manter como está (write-only).
        if ($this->curationApiKey !== '') {
            Setting::put('curation.api_key', $this->curationApiKey);
        }

        $settings->applyOverrides();
        $this->curationApiKey = '';
        $this->refreshSources($settings);

        Artisan::call('queue:restart');

        $this->dispatch('show-toast', message: 'Motor de curadoria salvo — workers de fila reiniciados', type: 'sucesso');
    }

    public function saveContext7(SettingsService $settings): void
    {
        $this->validate([
            'context7BaseUrl' => ['required', 'url', 'max:500'],
            'context7ApiKey' => ['nullable', 'string', 'max:2000'],
        ]);

        Setting::put('context7.base_url', $this->context7BaseUrl);

        if ($this->context7ApiKey !== '') {
            Setting::put('context7.api_key', $this->context7ApiKey);
        }

        $settings->applyOverrides();
        $this->context7ApiKey = '';
        $this->refreshSources($settings);

        Artisan::call('queue:restart');

        $this->dispatch('show-toast', message: 'Context7 salvo — workers de fila reiniciados', type: 'sucesso');
    }

    public function removeKey(string $panelKey, SettingsService $settings): void
    {
        if (! in_array($panelKey, ['curation.api_key', 'context7.api_key'], true)) {
            return;
        }

        Setting::put($panelKey, null);
        Artisan::call('queue:restart');

        $this->dispatch('show-toast', message: 'Chave removida do painel — o env volta a valer', type: 'aviso');

        // Redireciona para re-bootar a config a partir do env (o override desta
        // request já foi aplicado e não pode ser desfeito em memória).
        $this->redirect(route('admin.settings'), navigate: true);
    }

    public function restoreEnv(SettingsService $settings): void
    {
        foreach (array_keys(SettingsService::OVERRIDES) as $key) {
            Setting::put($key, null);
        }

        Artisan::call('queue:restart');

        $this->dispatch('show-toast', message: 'Painel limpo — todas as configurações voltam ao env', type: 'aviso');

        $this->redirect(route('admin.settings'), navigate: true);
    }

    public function testCuration(): void
    {
        // Testa o que está digitado AGORA (antes de salvar); chave vazia usa a efetiva.
        $apiKey = $this->curationApiKey !== '' ? $this->curationApiKey : (string) config('services.minimax.api_key');

        if ($apiKey === '') {
            $this->curationTest = ['type' => 'erro', 'message' => 'Nenhuma chave de API para testar — digite acima ou configure no .env.'];

            return;
        }

        try {
            $response = Http::baseUrl($this->curationBaseUrl)
                ->timeout(20)
                ->withHeaders(['x-api-key' => $apiKey, 'anthropic-version' => '2023-06-01'])
                ->post('/v1/messages', [
                    'model' => $this->curationModel,
                    'max_tokens' => 1,
                    'messages' => [['role' => 'user', 'content' => 'ping']],
                ]);

            if ($response->successful()) {
                $this->curationTest = ['type' => 'sucesso', 'message' => "Motor OK — {$this->curationModel} respondeu (HTTP {$response->status()})."];
            } elseif ($response->status() === 401 || $response->status() === 403) {
                $this->curationTest = ['type' => 'erro', 'message' => 'Chave inválida ou sem permissão (HTTP '.$response->status().').'];
            } elseif ($response->status() === 429) {
                $this->curationTest = ['type' => 'aviso', 'message' => 'Cota/limite de taxa atingido (HTTP 429) — a chave é válida, mas a janela de uso está cheia. Aguarde o reset.'];
            } else {
                $this->curationTest = ['type' => 'erro', 'message' => 'Motor respondeu HTTP '.$response->status().'.'];
            }
        } catch (\Throwable $e) {
            $this->curationTest = ['type' => 'erro', 'message' => 'Falha de conexão: '.mb_substr($e->getMessage(), 0, 160)];
        }
    }

    public function testContext7(): void
    {
        $apiKey = $this->context7ApiKey !== '' ? $this->context7ApiKey : (string) config('services.context7.api_key');

        try {
            $request = Http::baseUrl($this->context7BaseUrl)->timeout(20);

            if ($apiKey !== '') {
                $request = $request->withToken($apiKey);
            }

            $response = $request->get('/search', ['query' => 'laravel']);

            if ($response->successful()) {
                $modo = $apiKey !== '' ? 'com chave' : 'keyless';
                $this->context7Test = ['type' => 'sucesso', 'message' => "Context7 OK ({$modo}) — HTTP {$response->status()}."];
            } elseif ($response->status() === 429) {
                $this->context7Test = ['type' => 'aviso', 'message' => 'Limite de taxa atingido (HTTP 429) — considere adicionar uma chave para elevar a cota.'];
            } else {
                $this->context7Test = ['type' => 'erro', 'message' => 'Context7 respondeu HTTP '.$response->status().'.'];
            }
        } catch (\Throwable $e) {
            $this->context7Test = ['type' => 'erro', 'message' => 'Falha de conexão: '.mb_substr($e->getMessage(), 0, 160)];
        }
    }

    public function render()
    {
        return view('livewire.admin.system-settings');
    }

    private function refreshSources(SettingsService $settings): void
    {
        foreach (array_keys(SettingsService::OVERRIDES) as $key) {
            $this->sources[$key] = $settings->sourceOf($key);
        }
    }
}
