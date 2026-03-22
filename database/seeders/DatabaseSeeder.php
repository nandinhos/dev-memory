<?php

namespace Database\Seeders;

use App\Enums\MemoryScope;
use App\Enums\MemoryType;
use App\Enums\ValidationStatus;
use App\Models\Memory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->seedLessionsLearned();
    }

    private function seedLessionsLearned(): void
    {
        $memories = [
            // === TALL STACK (Tailwind + Alpine.js + Livewire + Laravel) ===

            [
                'title' => 'Alpine.js NUNCA deve ser importado manualmente com Livewire 4',
                'description' => "PROBLEMA: Livewire 4 já injeta e inicializa o Alpine.js via @livewireScripts. O app.js gerado pelo Breeze importa Alpine separadamente, causando duas instâncias simultâneas.\n\nSINTOMAS: 'Detected multiple instances of Alpine running', darkMode is not defined, wire:click não responde.\n\nFIX:\n```js\n// CORRETO — app.js com Livewire 4\nimport './bootstrap';\n// Não importar Alpine. Livewire cuida disso via @livewireScripts.\n```\n\nSe precisar de plugins Alpine:\n```js\nimport { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';\nimport Persist from '@alpinejs/persist';\nAlpine.plugin(Persist);\nLivewire.start();\n```",
                'type' => MemoryType::ERROR,
                'stack' => 'Livewire, Alpine.js',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 5,
                'official_reference' => 'https://livewire.laravel.com/docs/alpine',
            ],

            [
                'title' => '$persist requer plugin @alpinejs/persist não instalado por padrão',
                'description' => "\$persist(false) requer @alpinejs/persist. Sem o pacote, usar localStorage manual:\n\n```html\n<html x-data=\"{ darkMode: localStorage.getItem('ds-dark') === '1' }\"\n      x-init=\"\$watch('darkMode', v => localStorage.setItem('ds-dark', v ? '1' : '0'))\">\n```",
                'type' => MemoryType::LESSON,
                'stack' => 'Alpine.js',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 2,
            ],

            [
                'title' => 'Conflito Alpine x-data e morph DOM do Livewire',
                'description' => "PROBLEMA: Quando Livewire re-renderiza via morph, o Alpine preserva o x-data antigo. Dados do servidor são atualizados, mas a UI mostra valores obsoletos.\n\nFIX: wire:key com hash dinâmico dos dados:\n```blade\n@foreach(\$brackets as \$row)\n    <tr wire:key=\"row-{{ \$row['id'] }}-{{ md5(json_encode(\$row)) }}\">\n        <div x-data=\"{ val: '{{ \$row['valor'] }}' }\">\n```\nO hash diferente força Livewire a destruir e recriar o elemento (não morphar), e o Alpine reinicializa do zero.\n\nALTERNATIVA: \$wire.propriedade direto (estado no servidor):\n```blade\n<span x-text=\"\$wire.brackets[{{ \$index }}].valor\"></span>\n```",
                'type' => MemoryType::ERROR,
                'stack' => 'Livewire, Alpine.js',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 3,
            ],

            [
                'title' => 'wire:confirm tem limitações críticas; usar modal Alpine',
                'description' => "wire:confirm usa window.confirm() nativo — sem dark mode, sem Blade expressions dinâmicas, sem transições.\n\nPADRÃO CANÔNICO com Alpine:\n```blade\n<div x-data=\"{ showConfirm: false }\">\n    <button @click=\"showConfirm = true\">Ação</button>\n    <div x-show=\"showConfirm\" class=\"fixed inset-0 z-50 ...\">\n        <div class=\"absolute inset-0 bg-black/50\" @click=\"showConfirm = false\"></div>\n        <div class=\"relative ...\">\n            <button @click=\"showConfirm = false\">Cancelar</button>\n            <button @click=\"showConfirm = false; \$wire.confirmedAction()\">Confirmar</button>\n        </div>\n    </div>\n</div>\n```\n\nATENÇÃO: Fechar o modal ANTES de chamar \$wire.method().",
                'type' => MemoryType::BEST_PRACTICE,
                'stack' => 'Livewire, Alpine.js',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 4,
            ],

            [
                'title' => 'Blade escapa atributos Alpine; usar script global',
                'description' => "Blade converte \" em &quot; dentro de atributos HTML. Regex e strings complexas em x-data ficam corrompidas.\n\nFIX: função global em <script> + @js() no x-init:\n```blade\n<script>\n    window.highlightJson = function(data) { return JSON.stringify(data, null, 2); };\n</script>\n<div x-data=\"{ rendered: '' }\"\n     x-init=\"rendered = window.highlightJson(@js(\$data))\">\n    <pre x-html=\"rendered\"></pre>\n</div>\n```",
                'type' => MemoryType::ERROR,
                'stack' => 'Blade, Alpine.js',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 3,
            ],

            [
                'title' => 'Regras obrigatórias: wire:key em loops com x-data',
                'description' => "OBRIGATÓRIO: wire:key com hash quando loop tem x-data Alpine\n```blade\n<tr wire:key=\"prefix-{{ \$item['id'] }}-{{ md5(json_encode(\$item)) }}\">\n```\n\nOBRIGATÓRIO: prefixo único quando mesmo dataset aparece em múltiplas tabelas\n```blade\n<tr wire:key=\"tabela1-{{ \$row['id'] }}-{{ \$hash }}\">\n<tr wire:key=\"tabela2-{{ \$row['id'] }}-{{ \$hash }}\">\n```",
                'type' => MemoryType::BEST_PRACTICE,
                'stack' => 'Livewire, Alpine.js',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 8,
            ],

            [
                'title' => 'Mobile-First Tables em Modais: min-w-full não ativa scroll',
                'description' => "min-w-full limita a tabela ao container — scroll nunca ativa em modais.\n\nCENÁRIOS:\n| Cenário | Solução |\n|---------|---------|\n| Modal com ≤ 3 colunas | min-w-max w-full + overflow-x-auto no wrapper |\n| Modal com 4+ colunas | Card layout (sm:hidden cards + hidden sm:block tabela) |\n| Página full-width | min-w-full (padrão, correto) |",
                'type' => MemoryType::LESSON,
                'stack' => 'Tailwind, Livewire',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 2,
            ],

            [
                'title' => 'Livewire 3: public property com getMorphClass',
                'description' => 'Expor propriedade pública de Eloquent em Livewire 3 com renderização complexa dispara BadMethodCallException: getMorphClass.

SOLUÇÃO: hidratar a coleção no render() em vez de expô-la como propriedade pública.',
                'type' => MemoryType::ERROR,
                'stack' => 'Livewire',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 2,
            ],

            // === LARAVEL BACKEND ===

            [
                'title' => 'PostgreSQL não faz cast automático UUID → bigint',
                'description' => "->change() para alterar tipo de coluna falha no PostgreSQL com 'column cannot be cast automatically to type bigint'.\n\nFIX: drop + recreate em dois Schema::table() separados:\n```php\n// Passo 1: remover índice antes da coluna\nSchema::table('tabela', function (Blueprint \$table) {\n    \$table->dropIndex(['coluna']);\n    \$table->dropColumn('coluna');\n});\n// Passo 2: recriar com tipo correto\nSchema::table('tabela', function (Blueprint \$table) {\n    \$table->unsignedBigInteger('coluna')->nullable()->after('outro');\n    \$table->index('coluna');\n});\n```\nSQLite também exige dropIndex antes de dropColumn em colunas indexadas.",
                'type' => MemoryType::ERROR,
                'stack' => 'PostgreSQL, Laravel',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 4,
                'official_reference' => 'https://laravel.com/docs/migrations',
            ],

            [
                'title' => 'UUID v7 no Laravel 12: regex correta',
                'description' => "HasUuids gera UUID v7 por padrão no Laravel 12. Regex correta:\n```\n/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\$/i\n```\n\nNÃO usar 4[0-9a-f]{3} no terceiro grupo (seria v4).",
                'type' => MemoryType::LESSON,
                'stack' => 'Laravel',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 2,
                'official_reference' => 'https://laravel.com/docs/12.x/strings#generating-uuids',
            ],

            [
                'title' => 'DomPDF + PHP 8.4: tempnam() E_WARNING fatal',
                'description' => "PROBLEMA: PHP 8.4 elevou o aviso do tempnam() que o DomPDF usa internamente. Laravel converte warnings em exceptions → HTTP 500 em PDFs.\n\nFIX no AppServiceProvider::boot():\n```php\nset_error_handler(function (\$errno, \$errstr) use (&\$previousHandler) {\n    if (\$errno === E_WARNING && str_contains(\$errstr, 'tempnam()')) {\n        return true;\n    }\n    return \$previousHandler ? (\$previousHandler)(\$errno, \$errstr) : false;\n});\n```",
                'type' => MemoryType::ERROR,
                'stack' => 'DomPDF, PHP, Laravel',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 3,
                'official_reference' => 'https://github.com/barryvdh/laravel-dompdf',
            ],

            [
                'title' => 'ConvertEmptyStringsToNull quebra parâmetros opcionais',
                'description' => "O middleware ConvertEmptyStringsToNull converte ?inicio= para null antes do controller. \$request->get('chave', 'default') retorna null porque a chave existe.\n\nFIX: usar ?? em vez de segundo argumento do get():\n```php\n\$inicio = \$request->get('inicio') ?? now()->startOfMonth()->format('Y-m-d');\n```",
                'type' => MemoryType::ERROR,
                'stack' => 'Laravel',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 6,
            ],

            [
                'title' => 'Download interceptado pelo Livewire SPA router',
                'description' => "PROBLEMA: Links de download dentro de páginas Livewire são interceptados pelo roteamento SPA.\n\nSOLUÇÃO: adicionar o atributo download ao <a>:\n```html\n<a href=\"{{ route('export.modelo-csv') }}\" download>Baixar modelo CSV</a>\n```",
                'type' => MemoryType::ERROR,
                'stack' => 'Livewire',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 4,
            ],

            [
                'title' => 'Templates de usuário não pertencem ao storage/app/',
                'description' => "storage/app/ é ignorado pelo .gitignore por padrão. Arquivos de template para download devem ficar em resources/templates/.\n\n| Tipo | Onde colocar |\n|------|-------------|\n| Templates/assets para usuário | resources/ |\n| Uploads e arquivos gerados em runtime | storage/ |",
                'type' => MemoryType::LESSON,
                'stack' => 'Laravel',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 3,
            ],

            [
                'title' => 'Unicidade composta em tabelas pivot',
                'description' => "\$table->unique('user_id') em tabela pivot impede o usuário de aparecer em mais de uma entidade pai.\n\nUSAR unicidade composta:\n```php\n\$table->dropForeign(['user_id']);\n\$table->dropUnique(['user_id']);\n\$table->unique(['user_id', 'commission_id']); // único POR comissão\n\$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');\n```",
                'type' => MemoryType::LESSON,
                'stack' => 'Laravel, PostgreSQL',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 2,
            ],

            [
                'title' => 'Validação: sempre Form Requests, nunca inline',
                'description' => "Nunca usar \$request->validate() no controller.\n\nCRIAR FormRequest dedicado via php artisan make:request. Permite reutilizar regras Store/Update e mantém o controller limpo.",
                'type' => MemoryType::BEST_PRACTICE,
                'stack' => 'Laravel',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 10,
            ],

            [
                'title' => 'Proteção explícita de papéis do sistema',
                'description' => "Sempre guardar contra modificação de papéis reservados:\n```php\nif (in_array(\$role->name, ['admin', 'super-admin'])) {\n    abort(403);\n}\n```",
                'type' => MemoryType::BEST_PRACTICE,
                'stack' => 'Laravel',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 5,
            ],

            // === DOCKER / DEVOPS ===

            [
                'title' => 'Permissões root:root após artisan make:* no Docker',
                'description' => "ARQUIVOS criados dentro do container ficam como root:root. O VSCode não consegue editá-los.\n\nFIX imediato: sudo chown -R \$USER:\$USER app/\n\nAlias recomendado no .bashrc:\nalias sail-chown='sudo chown -R \$USER:\$USER .'",
                'type' => MemoryType::LESSON,
                'stack' => 'Docker, Laravel',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 8,
            ],

            [
                'title' => 'storage:link deve rodar DENTRO do container',
                'description' => "Link criado fora do container aponta para path do host (inexistente dentro do container) → 403/404.\n\nUSAR:\nbash vendor/bin/sail artisan storage:link\nou\ndocker compose exec app php artisan storage:link",
                'type' => MemoryType::ERROR,
                'stack' => 'Docker, Laravel',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 5,
            ],

            [
                'title' => 'Docker multi-stage: rebuild obrigatório após mudanças frontend',
                'description' => "Em imagens onde assets são baked (sem volume mount), mudanças no frontend exigem rebuild:\nbash\ndocker compose up -d --build\n\nSem rebuild, novas propriedades Livewire e assets JS/CSS não são refletidos.",
                'type' => MemoryType::LESSON,
                'stack' => 'Docker, Laravel',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 6,
            ],

            [
                'title' => 'SQLite em desenvolvimento: sempre bind mount',
                'description' => "Volume nomeado isola o banco do host (inacessível via DB Browser, tinker no host usa arquivo diferente).\n\nCORRETO — mesmo arquivo compartilhado:\nyaml\nvolumes:\n  - ./storage:/var/www/html/storage\n```\n\nRegra crítica: .env usa path do CONTAINER, não do host:\nenv\nDB_DATABASE=/var/www/html/storage/app/database.sqlite\n```\n\nVolumes nomeados são para PostgreSQL/MySQL em produção, não SQLite em dev.",
                'type' => MemoryType::LESSON,
                'stack' => 'Docker, SQLite, Laravel',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 4,
            ],

            [
                'title' => 'MCP Laravel Boost: configuração via docker compose exec',
                'description' => "Para Sail (container laravel.test), configuração no .mcp.json:\n```json\n{\n  \"mcpServers\": {\n    \"laravel-boost\": {\n      \"command\": \"docker\",\n      \"args\": [\"compose\", \"exec\", \"-T\", \"laravel.test\", \"php\", \"artisan\", \"boost:mcp\"],\n      \"env\": { \"WWWUSER\": \"1000\", \"WWWGROUP\": \"1000\" }\n    }\n  }\n}\n```\n-T desabilita pseudo-TTY — obrigatório para protocolo MCP via stdio.",
                'type' => MemoryType::LESSON,
                'stack' => 'Docker, MCP, Laravel',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 3,
                'official_reference' => 'https://laravel.com/docs/12.x/boost',
            ],

            [
                'title' => 'MCP Laravel Boost no Gemini CLI / Antigravity com Docker customizado',
                'description' => "Para containers customizados (não Sail), arquivo ~/.gemini/antigravity/mcp_config.json:\n\n```json\n{\n  \"mcpServers\": {\n    \"laravel-boost\": {\n      \"command\": \"docker\",\n      \"args\": [\"exec\", \"-i\", \"<container_app>\", \"php\", \"/var/www/artisan\", \"boost:mcp\"]\n    }\n  }\n}\n```\n\nCAMPOS CRÍTICOS:\n| Campo | Correto | Erro Comum |\n|-------|---------|------------|\n| Flag docker | -i | -it (quebra MCP) |\n| Path artisan | /var/www/artisan | /var/www/html/artisan |\n| Comando | boost:mcp | mcp:start laravel-boost |\n| Container | PHP/FPM | nginx (errado) |",
                'type' => MemoryType::LESSON,
                'stack' => 'Docker, MCP, Laravel',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 2,
            ],

            // === PHP ===

            [
                'title' => 'PHP 8.4: warnings elevados a exceptions',
                'description' => "PHP 8.4 elevou o nível de vários avisos. Laravel converte E_WARNING em exceptions por padrão.\n\nPacotes não atualizados (DomPDF, etc.) podem começar a falhar silenciosamente.\n\nSEMPRE verificar o log do Laravel após upgrade de PHP.",
                'type' => MemoryType::ERROR,
                'stack' => 'PHP',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 3,
            ],

            // === SCRAPING / INTEGRAÇÃO ===

            [
                'title' => 'Sites governamentais sempre precisam de fallback hardcoded',
                'description' => "Sempre implementar fallback com dados hardcoded para scrapers de sites governamentais:\n\n```php\npublic function scrape(): array\n{\n    try {\n        return \$this->scrapeFromWeb();\n    } catch (\\Exception \$e) {\n        Log::warning('Scraper falhou, usando OFFICIAL_DATA');\n        return self::OFFICIAL_DATA;\n    }\n}\n```",
                'type' => MemoryType::BEST_PRACTICE,
                'stack' => 'PHP, Laravel',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 4,
            ],

            [
                'title' => 'Regex é 30x mais rápido que DomCrawler para HTML governamental',
                'description' => "O site do Planalto tem ~2MB de HTML em ISO-8859-1. DomCrawler causa timeout.\n\nSOLUÇÃO: Regex com pré-filtragem (strrpos + substr) resolve.\n\nENCODING obrigatório:\n```php\nmb_convert_encoding(\$body, 'UTF-8', 'ISO-8859-1')\n```",
                'type' => MemoryType::LESSON,
                'stack' => 'PHP',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 2,
            ],

            // === ARQUITETURA ===

            [
                'title' => 'Convenção Percentual vs Decimal em dados tributários',
                'description' => "Regra: a camada de serviço é o único lugar para conversão de unidades.\n\n- Banco de referência em percentual (6)\n- Cálculo em decimal (0.06)\n- View aplica * 100 quando necessário\n\nNUNCA aplicar * 100 em UPDATE em massa — fazer migrate:fresh --seed em vez de corrigir manualmente.",
                'type' => MemoryType::BEST_PRACTICE,
                'stack' => 'Laravel',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 3,
            ],

            [
                'title' => 'Separação Scraper/Comparador: Single Responsibility Principle',
                'description' => "Scraper: obtém dados externos, retorna no formato do banco (sem conversão).\n\nComparador: compara dois arrays, ignora origem dos dados.\n\nPermite testar Comparador com mocks sem HTTP.",
                'type' => MemoryType::BEST_PRACTICE,
                'stack' => 'Laravel',
                'scope' => MemoryScope::GLOBAL,
                'validation_status' => ValidationStatus::VALIDATED,
                'recurrence_count' => 2,
            ],
        ];

        foreach ($memories as $memory) {
            Memory::create($memory);
        }

        Memory::factory(10)->create();
    }
}
