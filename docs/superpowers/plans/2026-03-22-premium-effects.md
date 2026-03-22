# Premium UI Effects Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Adicionar efeitos visuais premium ao Dev Memory: code block estilo macOS Catppuccin, separador animado de validação (bandeira quadriculada + shine sweep dourado), entrada stagger dos cards na lista, hover padronizado e toast de feedback.

**Architecture:** Todas as mudanças são CSS/Blade sem novas dependências de runtime. O separador aparece condicionalmente via Blade (`@if validated`). O toast é um componente Alpine standalone ativado por eventos Livewire dispatch no layout. O stagger usa uma classe wrapper `.card-stagger-item` para não afetar `.card-neo` globalmente.

**Tech Stack:** Laravel 13 · Livewire 4 · Alpine.js · Tailwind CSS 4 · highlight.js (já carregado via CDN)

---

## File Map

| Arquivo | Operação | Responsabilidade |
|---|---|---|
| `resources/css/app.css` | Modificar | `.sep-validated` + shine sweep, `.card-stagger-item`, `.card-neo` hover, `font-body` alias |
| `resources/views/components/neo/code-block.blade.php` | Reescrever | Titlebar macOS, dots reais, Catppuccin, line numbers, copy button |
| `resources/views/components/neo/toast.blade.php` | Criar | Componente Alpine de notificação com auto-dismiss |
| `resources/views/layouts/app.blade.php` | Modificar | Trocar CDN highlight.js theme + listener Alpine para toast |
| `resources/views/components/neo/memory-card.blade.php` | Modificar | Separador condicional `.sep-validated` |
| `resources/views/livewire/memory-detail.blade.php` | Modificar | Separador condicional no body do card |
| `app/Livewire/MemoryDetail.php` | Modificar | Dispatch `show-toast` em `markAsValidated` e `incrementRecurrence` |
| `resources/views/livewire/memory-list.blade.php` | Modificar | Wrapper `.card-stagger-item` com delay escalonado |
| `tests/Feature/MemoryDetailTest.php` | Criar | Testes Livewire para dispatch de toast |

---

## Task 1: CSS — Fundações Visuais

**Files:**
- Modify: `resources/css/app.css`

- [ ] **Step 1: Adicionar `font-body` alias e `.sep-validated` com shine sweep**

Abrir `resources/css/app.css`. Localizar o bloco `@layer utilities` (linha ~129) e adicionar **após** `.border-neo-green`:

```css
.font-body { font-family: var(--font-mono); }
```

Localizar o bloco `@layer components` principal (linha ~52) e adicionar **após** `.input-neo:hover`:

```css
/* Separador de validação — bandeira quadriculada com shine sweep */
.sep-validated {
    position: relative;
    height: 10px;
    border-top: 1.5px solid #000;
    border-bottom: 1.5px solid #000;
    background-image: repeating-linear-gradient(
        90deg,
        #000 0px, #000 8px,
        #fff 8px, #fff 16px
    );
    overflow: hidden;
}

.sep-validated::after {
    content: '';
    position: absolute;
    top: 0;
    left: -60%;
    width: 50%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(253, 224, 71, 0.75),
        transparent
    );
    animation: sep-shine-sweep 2.5s ease-in-out infinite;
}

@keyframes sep-shine-sweep {
    0%   { left: -60%; }
    100% { left: 120%; }
}
```

- [ ] **Step 2: Adicionar `.card-stagger-item` e melhorar hover do `.card-neo`**

No mesmo bloco `@layer components`, adicionar após `.sep-validated::after`:

```css
/* Stagger de entrada para cards na lista */
.card-stagger-item {
    opacity: 0;
    animation: fade-in-up 0.5s ease-out forwards;
}

/* Hover padronizado para card-neo */
.card-neo:hover {
    transform: translate(-2px, -2px);
    box-shadow: 8px 8px 0px 0px #000;
}
```

Verificar se já existe `.card-neo:hover` no arquivo — se existir, substituir. Confirmar que a `transition` no `.btn-neo, .card-neo, .input-neo` (linha ~61) já cobre `transform` e `box-shadow`. Também confirmar que `.card-neo:active` (linha ~66) está presente — se não estiver, adicionar `transform: translate(4px, 4px); box-shadow: 0px 0px 0px 0px #000 !important;`

- [ ] **Step 3: Verificar resultado visual**

Rodar `npm run dev` (ou `composer dev`). Abrir o app no browser e verificar que não há regressões visuais óbvias nos cards existentes.

- [ ] **Step 4: Commit**

```bash
git add resources/css/app.css
git commit -m "style: adicionar sep-validated, card-stagger-item e hover padronizado"
```

---

## Task 2: Code Block estilo macOS Catppuccin

**Files:**
- Rewrite: `resources/views/components/neo/code-block.blade.php`
- Modify: `resources/views/layouts/app.blade.php`

- [ ] **Step 1: Trocar tema do highlight.js no layout**

Em `resources/views/layouts/app.blade.php`, linha 9, substituir:

```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
```

Por:

```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/base16/catppuccin-mocha.min.css">
```

- [ ] **Step 2: Reescrever o componente code-block**

Substituir o conteúdo completo de `resources/views/components/neo/code-block.blade.php`:

```blade
@props([
    'language' => 'plaintext',  {{-- prop mantido como 'language' para compatibilidade com views existentes --}}
    'filename' => null,
])

@php
$langClass = match($language) {
    'php'                    => 'language-php',
    'js', 'javascript'       => 'language-javascript',
    'ts', 'typescript'       => 'language-typescript',
    'css'                    => 'language-css',
    'html'                   => 'language-html',
    'blade'                  => 'language-html',
    'json'                   => 'language-json',
    'bash', 'sh', 'shell'    => 'language-bash',
    'sql'                    => 'language-sql',
    'yaml', 'yml'            => 'language-yaml',
    'jsx', 'tsx'             => 'language-javascript',
    'md', 'markdown'         => 'language-markdown',
    default                  => 'language-plaintext',
};

// Adicionar números de linha: envolver cada linha em span
$rawCode = trim((string) $slot);
$lines = explode("\n", $rawCode);
$numbered = implode("\n", array_map(
    fn($i, $line) => '<span class="code-ln">' . ($i + 1) . '</span>' . htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
    array_keys($lines),
    $lines
));
@endphp

<div
    x-data="{
        copied: false,
        copyCode() {
            const code = this.\$el.querySelector('code').innerText;
            navigator.clipboard.writeText(code).then(() => {
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            });
        }
    }"
    class="code-block border-2 border-black shadow-neo my-4 overflow-hidden"
>
    {{-- Titlebar macOS --}}
    <div class="flex items-center justify-between px-3 py-2 border-b-2 border-black"
         style="background-color: #2a2a3e;">

        {{-- Dots --}}
        <div class="flex items-center gap-1.5">
            <span class="w-[11px] h-[11px] rounded-full border border-black/20 flex-shrink-0"
                  style="background:#ff5f57;"></span>
            <span class="w-[11px] h-[11px] rounded-full border border-black/20 flex-shrink-0"
                  style="background:#febc2e;"></span>
            <span class="w-[11px] h-[11px] rounded-full border border-black/20 flex-shrink-0"
                  style="background:#28c840;"></span>
            @if($filename)
                <span class="ml-2 font-mono text-[11px]" style="color:#888;">{{ $filename }}</span>
            @endif
        </div>

        {{-- Botão copiar --}}
        <button
            @click="copyCode()"
            class="flex items-center gap-1 border border-black/40 px-2 py-0.5 font-mono text-[10px] transition-colors duration-100"
            :class="copied
                ? 'bg-neo-teal text-black border-neo-teal'
                : 'bg-white/10 text-gray-400 hover:bg-white/20 hover:text-white'"
            style="font-family: var(--font-mono);"
            aria-label="Copiar código"
        >
            <span x-show="!copied" aria-hidden="true">
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
            </span>
            <span x-show="copied" aria-hidden="true">✓</span>
            <span x-text="copied ? 'Copiado' : 'Copiar'"></span>
        </button>
    </div>

    {{-- Corpo do código --}}
    <div class="overflow-x-auto" style="background-color: #1e1e2e;">
        <pre class="{{ $langClass }} !m-0 !rounded-none !border-0"
             style="background:transparent !important; padding: 1rem 1.25rem;"><code class="{{ $langClass }} code-with-lines">{!! $numbered !!}</code></pre>
    </div>
</div>
```

- [ ] **Step 3: Adicionar CSS para `.code-ln` no `app.css`**

No bloco `@layer components` de `app.css`, adicionar após `.code-block pre`:

```css
.code-ln {
    display: inline-block;
    width: 28px;
    color: #45475a;
    text-align: right;
    margin-right: 12px;
    user-select: none;
    font-size: 0.75em;
}
```

- [ ] **Step 4: Verificar no browser**

Navegar para uma memória que contenha código (ou criar uma com bloco de código). Confirmar:
- Titlebar `#2a2a3e` com dots coloridos (vermelho/amarelo/verde)
- Fundo `#1e1e2e` (escuro azulado)
- Syntax highlighting Catppuccin Mocha (roxo/verde/amarelo suave)
- Números de linha cinza à esquerda
- Botão "Copiar" que muda para "✓ Copiado" em teal ao clicar

- [ ] **Step 5: Commit**

```bash
git add resources/views/components/neo/code-block.blade.php \
        resources/views/layouts/app.blade.php \
        resources/css/app.css
git commit -m "feat: code block estilo macOS Catppuccin com line numbers e copy button"
```

---

## Task 3: Toast Component + Listener no Layout

**Files:**
- Create: `resources/views/components/neo/toast.blade.php`
- Modify: `resources/views/layouts/app.blade.php`

- [ ] **Step 1: Criar o componente toast**

Criar `resources/views/components/neo/toast.blade.php` com o conteúdo do kit de inspiração adaptado:

```blade
{{--
    Uso: não instanciar diretamente — o toast é controlado pelo listener Alpine
    no layout. Disparar via Livewire: $this->dispatch('show-toast', message: '...', type: 'sucesso')
    Tipos aceitos: sucesso | erro | aviso | info
--}}
<div
    x-data="{
        toasts: [],
        addToast(message, type = 'sucesso') {
            const id = Date.now();
            this.toasts.push({ id, message, type });
            setTimeout(() => { this.removeToast(id); }, 3000);
        },
        removeToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    @show-toast.window="addToast(\$event.detail.message, \$event.detail.type ?? 'sucesso')"
    class="fixed bottom-6 right-6 z-50 flex flex-col gap-3 items-end"
    aria-live="assertive"
    aria-atomic="true"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-x-full"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-full"
            class="bg-white neo-border shadow-neo-xl w-80 max-w-[calc(100vw-3rem)] flex overflow-hidden"
            role="alert"
        >
            {{-- Faixa lateral colorida --}}
            <div
                class="w-2 flex-shrink-0"
                :class="{
                    'bg-neo-green':   toast.type === 'sucesso',
                    'bg-neo-magenta': toast.type === 'erro',
                    'bg-neo-yellow':  toast.type === 'aviso',
                    'bg-neo-teal':    toast.type === 'info'
                }"
            ></div>

            {{-- Ícone --}}
            <div class="flex items-start pt-4 pl-3 flex-shrink-0">
                <span
                    class="w-6 h-6 flex items-center justify-center border-2 border-black font-heading font-bold text-xs"
                    :class="{
                        'bg-neo-green':   toast.type === 'sucesso',
                        'bg-neo-magenta': toast.type === 'erro',
                        'bg-neo-yellow':  toast.type === 'aviso',
                        'bg-neo-teal':    toast.type === 'info'
                    }"
                    x-text="{ sucesso: '✓', erro: '✕', aviso: '!', info: 'i' }[toast.type] ?? 'i'"
                ></span>
            </div>

            {{-- Texto --}}
            <div class="flex-1 p-4 pr-3 min-w-0">
                <p
                    class="font-heading font-bold uppercase text-xs mb-1"
                    x-text="{ sucesso: 'Sucesso', erro: 'Erro', aviso: 'Aviso', info: 'Info' }[toast.type] ?? 'Info'"
                ></p>
                <p class="font-body text-sm leading-snug" x-text="toast.message"></p>
            </div>

            {{-- Fechar --}}
            <div class="p-2 flex-shrink-0">
                <button
                    @click="removeToast(toast.id)"
                    class="w-6 h-6 flex items-center justify-center border-2 border-black bg-white font-heading font-bold text-sm hover:bg-neo-yellow transition-colors duration-100"
                    aria-label="Fechar notificação"
                >×</button>
            </div>
        </div>
    </template>
</div>
```

- [ ] **Step 2: Incluir o toast no layout**

Em `resources/views/layouts/app.blade.php`, adicionar **antes de** `@livewireScripts` (perto do final do `<body>`):

```html
<x-neo.toast />
```

- [ ] **Step 3: Verificar no browser**

Abrir o console do browser e testar o dispatch manualmente:
```js
window.dispatchEvent(new CustomEvent('show-toast', {
    detail: { message: 'Teste de toast!', type: 'sucesso' }
}));
```
Confirmar que o toast aparece no canto inferior direito, desliza da direita, e desaparece em 3s.

- [ ] **Step 4: Commit**

```bash
git add resources/views/components/neo/toast.blade.php \
        resources/views/layouts/app.blade.php
git commit -m "feat: componente toast com slide-in e auto-dismiss 3s"
```

---

## Task 4: Dispatch de Toast no MemoryDetail + Testes

**Files:**
- Modify: `app/Livewire/MemoryDetail.php`
- Create: `tests/Feature/MemoryDetailTest.php`

- [ ] **Step 1: Escrever os testes primeiro (TDD)**

Criar `tests/Feature/MemoryDetailTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Enums\MemoryType;
use App\Enums\MemoryScope;
use App\Enums\ValidationStatus;
use App\Livewire\MemoryDetail;
use App\Models\Memory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemoryDetailTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeMemory(array $attrs = []): Memory
    {
        return Memory::factory()->create(array_merge([
            'type'              => MemoryType::ERROR,
            'scope'             => MemoryScope::PROJECT,
            'validation_status' => ValidationStatus::PENDING,
        ], $attrs));
    }

    public function test_increment_recurrence_dispatches_toast(): void
    {
        $memory = $this->makeMemory();

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->call('incrementRecurrence')
            ->assertDispatched('show-toast');
    }

    public function test_mark_as_validated_dispatches_toast(): void
    {
        $memory = $this->makeMemory(['validation_status' => ValidationStatus::PENDING]);

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->call('markAsValidated')
            ->assertDispatched('show-toast');
    }

    public function test_increment_recurrence_increases_count(): void
    {
        $memory = $this->makeMemory(['recurrence_count' => 1]);

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->call('incrementRecurrence');

        $this->assertEquals(2, $memory->fresh()->recurrence_count);
    }

    public function test_mark_as_validated_updates_status(): void
    {
        $memory = $this->makeMemory(['validation_status' => ValidationStatus::PENDING]);

        Livewire::test(MemoryDetail::class, ['memory' => $memory])
            ->call('markAsValidated');

        $this->assertEquals(
            ValidationStatus::VALIDATED,
            $memory->fresh()->validation_status
        );
    }
}
```

- [ ] **Step 2: Rodar os testes — confirmar que os 2 primeiros falham**

```bash
php artisan test tests/Feature/MemoryDetailTest.php --filter "dispatches_toast"
```

Esperado: FAIL com "Event [show-toast] was not dispatched".

- [ ] **Step 3: Adicionar dispatch no MemoryDetail.php**

Em `app/Livewire/MemoryDetail.php`, atualizar os dois métodos:

```php
public function incrementRecurrence(): void
{
    $this->memory->increment('recurrence_count');
    $this->memory->refresh();
    $this->dispatch('show-toast',
        message: '+1 ocorrência registrada',
        type: 'sucesso'
    );
}

public function markAsValidated(): void
{
    $this->memory->update(['validation_status' => ValidationStatus::VALIDATED]);
    $this->memory->refresh();
    $this->dispatch('show-toast',
        message: 'Memória validada com sucesso!',
        type: 'sucesso'
    );
}
```

- [ ] **Step 4: Rodar todos os testes — confirmar que passam**

```bash
php artisan test tests/Feature/MemoryDetailTest.php
```

Esperado: 4 testes passando (PASS).

- [ ] **Step 5: Rodar a suite completa para checar regressões**

```bash
composer test
```

Esperado: todos os testes passando.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/MemoryDetail.php \
        tests/Feature/MemoryDetailTest.php
git commit -m "feat: dispatch show-toast em markAsValidated e incrementRecurrence"
```

---

## Task 5: Separador de Validação nos Cards

**Files:**
- Modify: `resources/views/components/neo/memory-card.blade.php`
- Modify: `resources/views/livewire/memory-detail.blade.php`

- [ ] **Step 1: Adicionar separador no memory-card**

Em `resources/views/components/neo/memory-card.blade.php`, localizar o bloco do rodapé (linha ~69):

```html
<div class="mt-3 pt-3 border-t-2 border-black/20 flex items-center justify-between">
```

Substituir **só** a `div` de `border-t-2` pelo seguinte bloco condicional:

```blade
@if($memoria->validation_status->value === 'validated')
    <div class="sep-validated mt-3"></div>
@else
    <div class="mt-3 border-t-2 border-black/20"></div>
@endif
<div class="pt-3 flex items-center justify-between">
```

Atenção: o `mt-3 pt-3` original estava na mesma `div` — o separador absorve o `mt-3`, e a div do conteúdo mantém apenas `pt-3`.

- [ ] **Step 2: Adicionar separador no memory-detail**

Em `resources/views/livewire/memory-detail.blade.php`, localizar a `div` com os badges (linha ~43):

```html
<div class="flex flex-wrap gap-2 mb-4 pb-4 border-b-2 border-black">
```

Substituir essa `div` e seu conteúdo para usar o separador condicional após as badges:

```blade
<div class="flex flex-wrap gap-2 mb-4 pb-4">
    <span class="inline-block {{ $memory->type->color() }} border-2 border-black px-3 py-1 text-xs font-bold font-heading">
        {{ $memory->type->label() }}
    </span>
    <span class="inline-block {{ $memory->scope->badgeColor() }} border-2 border-black px-3 py-1 text-xs font-bold font-heading">
        {{ $memory->scope->label() }}
    </span>
    <span class="inline-block {{ $memory->validation_status->color() }} border-2 border-black px-3 py-1 text-xs font-bold font-heading">
        {{ $memory->validation_status->label() }}
    </span>
</div>

@if($memory->validation_status->value === 'validated')
    <div class="sep-validated mb-4"></div>
@else
    <div class="border-b-2 border-black mb-4"></div>
@endif
```

- [ ] **Step 3: Verificar no browser**

Criar ou encontrar uma memória com status `validated`. Confirmar:
- Bandeira quadriculada P&B aparece dividindo o card
- Luz dourada varre periodicamente (a cada ~2.5s)
- Cards com outros status continuam com o divisor preto normal

- [ ] **Step 4: Commit**

```bash
git add resources/views/components/neo/memory-card.blade.php \
        resources/views/livewire/memory-detail.blade.php
git commit -m "feat: separador quadriculado animado em cards validados"
```

---

## Task 6: Stagger de Entrada na Memory List

**Files:**
- Modify: `resources/views/livewire/memory-list.blade.php`

- [ ] **Step 1: Envolver cards com wrapper stagger**

Em `resources/views/livewire/memory-list.blade.php`, localizar o bloco do `@foreach` (linha ~110):

```blade
<div class="space-y-4">
    @foreach($memories as $memory)
        <x-neo.memory-card :memoria="$memory" />
    @endforeach
</div>
```

Substituir por:

```blade
<div class="space-y-4">
    @foreach($memories as $memory)
        <div class="card-stagger-item"
             style="animation-delay: {{ min($loop->index, 6) * 80 }}ms">
            <x-neo.memory-card :memoria="$memory" />
        </div>
    @endforeach
</div>
```

- [ ] **Step 2: Verificar no browser**

Navegar para `/memories`. Confirmar que os cards entram em sequência suave (fade-in-up escalonado), com cada card levemente atrasado em relação ao anterior. O delay máximo é 480ms (6° card em diante entra junto).

- [ ] **Step 3: Verificar que não há regressão de layout**

Confirmar que o `space-y-4` ainda mantém o espaçamento correto entre os wrappers. Confirmar que hover, click e paginação funcionam normalmente.

- [ ] **Step 4: Commit**

```bash
git add resources/views/livewire/memory-list.blade.php
git commit -m "feat: stagger de entrada nos cards da memory list"
```

---

## Task 7: Verificação Final e Smoke Test

- [ ] **Step 1: Rodar suite completa de testes**

```bash
composer test
```

Esperado: todos os testes passando (incluindo os novos 4 em `MemoryDetailTest`).

- [ ] **Step 2: Smoke test manual — percurso completo**

1. `/` — Dashboard: verificar animação fade-in-up da página, hover nos cards de stat
2. `/memories` — Lista: verificar stagger de entrada dos cards, hover translate nos cards
3. Clicar num card de memória com código — verificar code block macOS, dots, copy button
4. Validar uma memória (`markAsValidated`) — verificar toast "Memória validada" deslizando da direita
5. Clicar "+1 Ocorrência" — verificar toast correspondente
6. Verificar card validado na lista — separador quadriculado com shine sweep dourado

- [ ] **Step 3: Commit final se houver ajustes**

```bash
git add -p  # checar apenas os arquivos com ajustes pontuais
git commit -m "fix: ajustes visuais após smoke test dos efeitos premium"
```
