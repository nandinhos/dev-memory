<?php

namespace Tests\Feature;

use App\Livewire\Admin\SystemSettings;
use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_settings_page_requires_auth(): void
    {
        auth()->logout();

        $this->get('/admin/settings')->assertRedirect('/login');
    }

    public function test_settings_page_renders(): void
    {
        $this->get('/admin/settings')
            ->assertOk()
            ->assertSee('Motor de curadoria')
            ->assertSee('Context7');
    }

    public function test_value_is_encrypted_at_rest(): void
    {
        Setting::put('curation.api_key', 'chave-super-secreta');

        $raw = DB::table('settings')->where('key', 'curation.api_key')->value('value');

        $this->assertNotSame('chave-super-secreta', $raw);
        $this->assertStringNotContainsString('chave-super-secreta', $raw);
        $this->assertSame('chave-super-secreta', Setting::get('curation.api_key'));
    }

    public function test_panel_overrides_env_and_clears_back(): void
    {
        $service = new SettingsService;

        $envModel = config('services.minimax.model');

        Setting::put('curation.model', 'Modelo-Painel');
        $service->applyOverrides();

        $this->assertSame('Modelo-Painel', config('services.minimax.model'));
        $this->assertSame('painel', $service->sourceOf('curation.model'));

        Setting::put('curation.model', null);
        $this->assertNull(Setting::find('curation.model'));
        $this->assertSame($envModel !== null && $envModel !== '' ? 'env' : 'nenhuma', $service->sourceOf('curation.model'));
    }

    public function test_save_persists_and_keeps_secret_write_only(): void
    {
        Livewire::test(SystemSettings::class)
            ->set('curationBaseUrl', 'https://api.exemplo.dev/anthropic')
            ->set('curationModel', 'Modelo-X')
            ->set('curationApiKey', 'nova-chave-secreta')
            ->set('context7BaseUrl', 'https://context7.com/api/v1')
            ->call('save')
            ->assertSet('curationApiKey', '') // write-only: campo é limpo após salvar
            ->assertDispatched('show-toast');

        $this->assertSame('nova-chave-secreta', Setting::get('curation.api_key'));
        $this->assertSame('Modelo-X', Setting::get('curation.model'));
    }

    public function test_empty_secret_field_keeps_existing_key(): void
    {
        Setting::put('curation.api_key', 'chave-existente');

        Livewire::test(SystemSettings::class)
            ->set('curationBaseUrl', 'https://api.exemplo.dev/anthropic')
            ->set('curationModel', 'Modelo-X')
            ->set('context7BaseUrl', 'https://context7.com/api/v1')
            ->call('save');

        $this->assertSame('chave-existente', Setting::get('curation.api_key'));
    }

    public function test_secret_is_never_rendered_in_page(): void
    {
        Setting::put('curation.api_key', 'chave-que-nao-pode-vazar');

        $this->get('/admin/settings')
            ->assertOk()
            ->assertDontSee('chave-que-nao-pode-vazar');
    }

    public function test_invalid_url_is_rejected(): void
    {
        Livewire::test(SystemSettings::class)
            ->set('curationBaseUrl', 'nao-e-url')
            ->call('save')
            ->assertHasErrors(['curationBaseUrl' => 'url']);
    }

    public function test_connection_test_reports_success_and_failure(): void
    {
        Http::fake([
            'api.ok.dev/*' => Http::response(['id' => 'msg'], 200),
            'api.ruim.dev/*' => Http::response(['error' => 'unauthorized'], 401),
        ]);

        Livewire::test(SystemSettings::class)
            ->set('curationBaseUrl', 'https://api.ok.dev/anthropic')
            ->set('curationApiKey', 'chave-teste')
            ->call('testCuration')
            ->assertDispatched('show-toast', type: 'sucesso');

        Livewire::test(SystemSettings::class)
            ->set('curationBaseUrl', 'https://api.ruim.dev/anthropic')
            ->set('curationApiKey', 'chave-invalida')
            ->call('testCuration')
            ->assertDispatched('show-toast', type: 'erro');
    }

    public function test_context7_test_works_keyless(): void
    {
        Http::fake([
            'context7.com/*' => Http::response(['results' => []], 200),
        ]);

        Livewire::test(SystemSettings::class)
            ->set('context7BaseUrl', 'https://context7.com/api/v1')
            ->call('testContext7')
            ->assertDispatched('show-toast', type: 'sucesso');
    }
}
