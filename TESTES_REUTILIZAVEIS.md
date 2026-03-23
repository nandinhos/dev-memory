# Documento de Testes Reutilizáveis para Projetos Laravel

> Este documento contém todos os testes realizados no projeto Dev Memory, estruturados de forma que possam ser adaptados para outros projetos Laravel.

---

## 1. Testes de Integração (Playwright/E2E)

### 1.1 Estrutura de Testes E2E

```yaml
# Estrutura de testes reusable
test_suites:
  - name: "Funcionalidades Básicas"
    tests:
      - test_dashboard_access
      - test_navigation
      - test_page_load

  - name: "CRUD Operations"
    tests:
      - test_create_record
      - test_read_record
      - test_update_record
      - test_delete_record

  - name: "Filters & Search"
    tests:
      - test_filter_by_type
      - test_filter_by_status
      - test_search_functionality

  - name: "Authentication"
    tests:
      - test_login
      - test_logout
      - test_unauthorized_access
```

### 1.2 Exemplos de Testes Playwright

```javascript
// Teste 1:(Acesso ao Dashboard
await page.goto('http://localhost:9587/');
await expect(page.locator('h1')).toContainText('DASHBOARD');

// Teste 2: Navegação
await page.click('text=Lista');
await expect(page).toHaveURL('/memories');

// Teste 3: Criação de Registro
await page.click('text=+ Nova');
await page.fill('input[title]', 'Título do Teste');
await page.fill('textarea[description]', 'Descrição do Teste');
await page.selectOption('select[name=type]', 'Erro');
await page.click('button: text(Salvar)');
await expect(page.locator('.success-message')).toBeVisible();

// Teste 4: Edição
await page.click('text=Ver');
await page.click('text=Editar');
await page.fill('input[title]', 'Novo Título');
await page.click('button: text(Salvar)');
await expect(page.locator('h1')).toContainText('Novo Título');

// Teste 5: Validação
await page.click('text=Validar');
await expect(page.locator('.badge-validated')).toBeVisible();

// Teste 6: Filtros
await page.selectOption('select[name=type]', 'Erro');
await expect(page.locator('.card-error')).toBeVisible();
```

### 1.3 Comandos Playwright

```bash
# Instalação
npm install -D @playwright/test
npx playwright install chromium

# Criar teste
npx playwright test --generate

# Executar testes
npx playwright test
npx playwright test tests/e2e/
npx playwright test --ui

# Debug
npx playwright test --debug

# Relatório
npx playwright show-report
```

---

## 2. Testes Unitários (PHPUnit)

### 2.1 Estrutura de Testes Laravel

```php
// tests/Unit/ExampleTest.php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}
```

### 2.2 Testes de Modelo

```php
// tests/Unit/ModelTest.php
<?php

namespace Tests\Unit;

use App\Models\Memory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemoryModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_memory(): void
    {
        $memory = Memory::create([
            'title' => 'Test Memory',
            'description' => 'Test Description',
            'type' => 'error',
            'scope' => 'project',
            'validation_status' => 'pending',
            'stack' => 'Laravel, PHP',
        ]);

        $this->assertDatabaseHas('memories', [
            'title' => 'Test Memory',
        ]);
    }

    public function test_memory_has_valid_uuid(): void
    {
        $memory = Memory::create([
            'title' => 'Test',
            'description' => 'Test',
            'type' => 'error',
            'scope' => 'project',
            'validation_status' => 'pending',
        ]);

        $this->assertNotNull($memory->id);
        $this->assertTrue(
            preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $memory->id) === 1
        );
    }

    public function test_memory_scopes(): void
    {
        $memory = Memory::create([
            'title' => 'Global Memory',
            'description' => 'Test',
            'type' => 'lesson',
            'scope' => 'global',
            'validation_status' => 'validated',
        ]);

        $globalMemories = Memory::global()->get();
        $this->assertTrue($globalMemories->contains($memory));
    }
}
```

### 2.3 Testes de Serviço

```php
// tests/Unit/MemoryServiceTest.php
<?php

namespace Tests\Unit;

use App\Services\MemoryService;
use App\Models\Memory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MemoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MemoryService();
    }

    public function test_can_create_memory_via_service(): void
    {
        $data = [
            'title' => 'Service Test',
            'description' => 'Testing service',
            'type' => 'error',
            'scope' => 'project',
            'validation_status' => 'pending',
        ];

        $memory = $this->service->create($data);

        $this->assertNotNull($memory);
        $this->assertEquals('Service Test', $memory->title);
    }

    public function test_can_update_memory(): void
    {
        $memory = Memory::create([
            'title' => 'Original',
            'description' => 'Original',
            'type' => 'error',
            'scope' => 'project',
            'validation_status' => 'pending',
        ]);

        $updated = $this->service->update($memory->id, [
            'title' => 'Updated',
            'validation_status' => 'validated',
        ]);

        $this->assertEquals('Updated', $updated->title);
        $this->assertEquals('validated', $updated->validation_status);
    }

    public function test_can_delete_memory(): void
    {
        $memory = Memory::create([
            'title' => 'To Delete',
            'description' => 'Test',
            'type' => 'error',
            'scope' => 'project',
            'validation_status' => 'pending',
        ]);

        $result = $this->service->delete($memory->id);

        $this->assertTrue($result);
        $this->assertNull(Memory::find($memory->id));
    }

    public function test_can_search_memories(): void
    {
        Memory::create([
            'title' => 'Laravel Error',
            'description' => 'Test',
            'type' => 'error',
            'scope' => 'project',
            'validation_status' => 'pending',
        ]);

        Memory::create([
            'title' => 'PHP Lesson',
            'description' => 'Test',
            'type' => 'lesson',
            'scope' => 'global',
            'validation_status' => 'validated',
        ]);

        $results = $this->service->search('Laravel');

        $this->assertEquals(1, $results->count());
    }
}
```

### 2.4 Testes de API/Feature

```php
// tests/Feature/MemoryApiTest.php
<?php

namespace Tests\Feature;

use App\Models\Memory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_memories(): void
    {
        Memory::create([
            'title' => 'Test Memory',
            'description' => 'Test',
            'type' => 'error',
            'scope' => 'project',
            'validation_status' => 'pending',
        ]);

        $response = $this->getJson('/api/memories');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
    }

    public function test_can_create_memory_via_api(): void
    {
        $response = $this->postJson('/api/memories', [
            'title' => 'API Test',
            'description' => 'Testing API',
            'type' => 'lesson',
            'scope' => 'global',
            'validation_status' => 'pending',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('memories', ['title' => 'API Test']);
    }

    public function test_can_show_memory(): void
    {
        $memory = Memory::create([
            'title' => 'Show Test',
            'description' => 'Test',
            'type' => 'error',
            'scope' => 'project',
            'validation_status' => 'pending',
        ]);

        $response = $this->getJson("/api/memories/{$memory->id}");

        $response->assertStatus(200);
        $response->assertJson(['title' => 'Show Test']);
    }

    public function test_can_update_memory_via_api(): void
    {
        $memory = Memory::create([
            'title' => 'Original',
            'description' => 'Test',
            'type' => 'error',
            'scope' => 'project',
            'validation_status' => 'pending',
        ]);

        $response = $this->putJson("/api/memories/{$memory->id}", [
            'title' => 'Updated',
            'validation_status' => 'validated',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('memories', [
            'id' => $memory->id,
            'title' => 'Updated',
        ]);
    }

    public function test_can_delete_memory_via_api(): void
    {
        $memory = Memory::create([
            'title' => 'To Delete',
            'description' => 'Test',
            'type' => 'error',
            'scope' => 'project',
            'validation_status' => 'pending',
        ]);

        $response = $this->deleteJson("/api/memories/{$memory->id}");

        $response->assertStatus(204);
        $this->assertNull(Memory::find($memory->id));
    }
}
```

---

## 3. Testes de Integração Architect (Código)

### 3.1 Regras de Segurança

| Regra | Descrição | Severidade | Como Testar |
|-------|-----------|------------|-------------|
| SEC-001 | SQL Injection Detection | Critical | `architect run app/` |
| SEC-002 | Dangerous Function Detection | Critical | `architect run app/` |

### 3.2 Regras de Qualidade

| Regra | Descrição | Severidade | Como Testar |
|-------|-----------|------------|-------------|
| TEST-001 | Test Required Rule | High | `architect run tests/` |
| CQ-001 | Anti-Pattern Detection | High | `architect run app/` |
| LOG-001 | No Console Rule | Medium | `architect run app/` |
| DES-001 | Design Token Validator | Low | `architect run resources/` |

### 3.3 Comandos de Verificação

```bash
# Análise completa
architect run app/

# Apenas arquivos modificados (staged)
architect staged

# Listar regras
architect rules

# Versão
architect version
```

---

## 4. Testes de Infraestrutura

### 4.1 Docker

```bash
# Verificar serviços
docker compose ps

# Verificar logs
docker compose logs app
docker compose logs postgres
docker compose logs redis

# Teste de conectividade
docker compose exec -T postgres pg_isready -U dev_memory
docker compose exec -T redis redis-cli ping

# Verificar variáveis de ambiente
docker compose exec -T app env | grep -E "APP_|DB_|REDIS"
```

### 4.2 Nginx

```bash
# Testar configuração
docker compose exec -T app nginx -t

# Verificar erros
docker compose exec -T app tail -20 /var/log/nginx/error.log
```

### 4.3 PHP-FPM

```bash
# Verificar status
docker compose exec -T app ps aux | grep php-fpm

# Testar PHP
docker compose exec -T app php -v
docker compose exec -T app php -m
```

---

## 5. Testes de Performance

### 5.1 Lighthouse (Web Vitals)

```bash
# Install lighthouse CLI
npm install -g lighthouse

# Executar análise
lighthouse http://localhost:9587 --output=json --output-path=report.json
```

### 5.2 Métricas Esperadas

| Métrica | Meta | Crítico |
|---------|------|---------|
| FCP (First Contentful Paint) | < 1.8s | > 3.0s |
| LCP (Largest Contentful Paint) | < 2.5s | > 4.0s |
| TTI (Time to Interactive) | < 3.8s | > 7.3s |
| CLS (Cumulative Layout Shift) | < 0.1 | > 0.25 |

---

## 6. Checklist de Testes

### 6.1 Funcional
- [ ] Login/Logout
- [ ] CRUD completo
- [ ] Filters e Search
- [ ] Navegação
- [ ] Permissões
- [ ] Upload de arquivos
- [ ] Envio de emails
- [ ] APIs REST

### 6.2 Segurança
- [ ] SQL Injection
- [ ] XSS
- [ ] CSRF
- [ ] Auth/Authorization
- [ ] Rate Limiting
- [ ] Input Validation

### 6.3 Integração
- [ ] Database (PostgreSQL/MySQL/SQLite)
- [ ] Cache (Redis)
- [ ] Filas (Queue)
- [ ] Scheduled Jobs
- [ ] External APIs

### 6.4 Performance
- [ ] Tempo de resposta < 200ms
- [ ] Memória < 128MB
- [ ] CPU < 50%
- [ ] Database queries < 10 por request

---

## 7. Scripts de Automação

### 7.1 Makefile Ready

```makefile
# Testes
test: ## Run all tests
	./vendor/bin/phpunit

test-unit: ## Unit tests only
	./vendor/bin/phpunit --testsuite=Unit

test-feature: ## Feature tests only
	./vendor/bin/phpunit --testsuite=Feature

test-coverage: ## Tests with coverage
	./vendor/bin/phpunit --coverage-html coverage

# Linting
lint: ## Run Pint linter
	./vendor/bin/pint

lint-check: ## Check without fixing
	./vendor/bin/pint --test

# Code Analysis
analyze: ## Run Architect
	/home/nandodev/.architectai/bin/architect run app/

analyze-staged: ## Analyze staged files
	/home/nandodev/.architectai/bin/architect staged

# Docker Tests
test-docker: ## Run tests in Docker
	docker compose exec app ./vendor/bin/phpunit

test-docker-e2e: ## Run E2E in Docker
	docker compose exec app npx playwright test
```

---

## 8. Configuração CI/CD

### 8.1 GitHub Actions

```yaml
name: CI

on:
  push:
    branches: [main, master]
  pull_request:
    branches: [main, master]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      
      - name: Install Composer
        uses: ramsey/composer-install@v3
      
      - name: Lint
        run: ./vendor/bin/pint --test
      
      - name: Architect
        run: /home/nandodev/.architectai/bin/architect run app/
      
      - name: Tests
        run: ./vendor/bin/phpunit
      
      - name: Build
        run: npm run build
```

---

## 9. Adaptando para Outros Projetos

### 9.1 Steps Rápidos

1. **Copiar estrutura de testes**: `tests/Unit/`, `tests/Feature/`
2. **Configurar PHPUnit**: `phpunit.xml` com databases, factories
3. **Configurar Pint**: `pint.json` com rules
4. **Configurar Architect**: `.architect/` com tokens e rules
5. **Configurar Playwright**: `playwright.config.js`
6. **Configurar Makefile**: Adicionar comandos de teste
7. **Configurar CI**: `.github/workflows/ci.yml`

### 9.2 Comandos de Setup

```bash
# PHPUnit
composer require --dev phpunit/phpunit
php artisan make:test Tests/Unit/ExampleTest

# Pint
composer require --dev laravel/pint
./vendor/bin/pint --init

# Playwright
npm init playwright@latest

# Architect
curl -L https://Architect.ai/install.sh | sh
```

---

*Este documento foi gerado automaticamente para reutilização em projetos Laravel.*
*Baseado nos testes realizados no projeto Dev Memory em 23/03/2026.*