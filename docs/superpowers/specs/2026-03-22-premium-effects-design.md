# Spec: Premium UI Effects — Dev Memory

**Data:** 2026-03-22
**Status:** Aprovado
**Projeto:** dev-memory-laravel
**Escopo:** Efeitos visuais premium, code block estilo macOS, separador animado de validação, animações de entrada com stagger

---

## Contexto

O projeto já utiliza o design system neo-brutalist com as cores, fontes e tokens corretos. Esta spec cobre a camada de **efeitos e animações premium** que elevam a experiência visual sem alterar a arquitetura existente. Todas as mudanças são CSS/Blade — sem novas dependências de runtime.

---

## Requisitos

### R1 — Code Block estilo macOS
- Reescrever `resources/views/components/neo/code-block.blade.php`
- Titlebar com 3 dots reais: vermelho `#ff5f57`, amarelo `#febc2e`, verde `#28c840`, cada um `border-radius: 50%`, diâmetro 11px, borda sutil `rgba(0,0,0,0.3)`
- Nome do arquivo no titlebar em `font-mono` cinza (`#888`), fundo titlebar `#2a2a3e`
- Fundo do corpo `#1e1e2e` (Catppuccin Mocha base)
- Syntax highlighting via `highlight.js` já carregado — trocar tema para **Catppuccin Mocha** via CDN (`highlight.js/styles/base16/catppuccin-mocha.min.css`)
- Números de linha como `<span>` antes de cada linha, cor `#45475a`, largura fixa `28px`, alinhados à direita, separados do código por `12px`
- Botão "Copiar" no canto superior direito do titlebar: ícone clipboard, ao clicar muda para "✓ Copiado" em `neo-teal` por 2s via Alpine.js `x-data` (feedback local no botão apenas — sem toast global para esta ação)
- Container com `border: 2px solid #000` + `box-shadow: 4px 4px 0 #000` — mantém DNA neo-brutalist
- Suporte ao prop `filename` (opcional) — exibe no titlebar quando presente
- Suporte ao prop `lang` para `highlight.js` language class (default: `plaintext`)

### R2 — Separador de Validação (Bandeira Quadriculada)
- Novo elemento CSS `.sep-validated` adicionado ao `app.css`
- Aparece **somente** quando `validation_status === 'validated'`
- Altura `10px`, bordas `1.5px solid #000` acima e abaixo
- Padrão: `repeating-linear-gradient(90deg, #000 0px, #000 8px, #fff 8px, #fff 16px)`
- **Animação Shine Sweep:** pseudo-elemento `::after` com gradiente `rgba(253,224,71,0.75)` varrendo da esquerda para a direita
  - Duração: `2.5s ease-in-out infinite`
  - A cor do sweep é `neo-yellow` (`#FDE047`) — mantém paleta do kit
  - `overflow: hidden` no container do separador
- Usado em: `memory-card.blade.php` (entre corpo e rodapé) e `memory-detail.blade.php` (entre badges e título)

### R3 — Entrada Stagger dos Cards (Memory List)
- Em `memory-list.blade.php`, envolver cada `<x-neo.memory-card>` em `<div class="card-stagger-item" style="animation-delay: {{ min($loop->index, 6) * 80 }}ms">`
- Adicionar `.card-stagger-item` ao `app.css` com `opacity: 0; animation: fade-in-up 0.5s ease-out forwards` — **não modificar `.card-neo` globalmente** para evitar quebrar cards fora do contexto de lista (sidebar, dashboard, etc.)
- Cap de delay: `min($loop->index, 6) * 80` — máximo `480ms`, após o 6º card o delay não aumenta

### R4 — Efeitos de Hover e Active padronizados
- Padronizar `.card-neo` hover: `transform: translate(-2px, -2px)` + `box-shadow: 8px 8px 0 #000`
- Transição: `transition: transform 0.1s ease, box-shadow 0.1s ease`
- O active já existe (`.btn-neo:active`, `.card-neo:active`) — verificar e consolidar no CSS

### R5 — Toast de Feedback
- **Criar** `resources/views/components/neo/toast.blade.php` (não existe ainda — baseado no kit de inspiração em `docs/inspirations/neo-brutalist/components/toast.blade.php`)
- O toast é um componente Alpine standalone posicionado `fixed bottom-4 right-4 z-50`
- Props: `message` (string), `type` (sucesso/erro/aviso, default: sucesso) — mapeia para cores neo do kit (green/magenta/yellow)
- Visual: `neo-border shadow-neo` com fundo colorido pelo tipo, fonte `font-heading uppercase`, ícone de check/x/warning
- Animação de entrada: `translate-x-full → translate-x-0` (desliza da direita, 200ms ease-out)
- Auto-dismiss: após 3s faz `translate-x-0 → translate-x-full opacity-0` e remove do DOM
- Acionado quando:
  - Validar memória (`markAsValidated` Livewire → `$this->dispatch('show-toast', [...])`)
  - Incrementar ocorrência (`incrementRecurrence` Livewire → `$this->dispatch('show-toast', [...])`)
- Listener Alpine no `layouts/app.blade.php` via `@on('show-toast')` no `x-data` do body

---

## Arquivos Afetados

| Arquivo | Mudança |
|---|---|
| `resources/css/app.css` | Adicionar `.sep-validated`, `.sep-validated::after`, keyframe `sep-shine-sweep`, `.card-stagger-item`, ajustar `.card-neo` hover |
| `resources/views/components/neo/code-block.blade.php` | Reescrever completo com macOS titlebar + copiar + line numbers |
| `resources/views/components/neo/memory-card.blade.php` | Adicionar separador condicional para `validated` |
| `resources/views/livewire/memory-list.blade.php` | Adicionar stagger delay por `$loop->index` |
| `resources/views/livewire/memory-detail.blade.php` | Adicionar separador condicional no body do card |
| `app/Livewire/MemoryDetail.php` | Adicionar `$this->dispatch('show-toast', [...])` em `markAsValidated` e `incrementRecurrence` |
| `resources/views/layouts/app.blade.php` | Trocar CDN do highlight.js theme + adicionar listener de toast |

---

## Não Está no Escopo

- Modificações no backend / Livewire components (exceto dispatch de toast)
- Mudança de paleta de cores
- Novos componentes além dos listados (exceto `toast.blade.php` que é parte do R5)
- Testes automatizados (mudanças puramente visuais)

---

## Decisões de Design

| Decisão | Razão |
|---|---|
| Catppuccin Mocha para syntax | Fundo escuro consistente (#1e1e2e) + paleta suave de alto contraste — melhor legibilidade que github-dark |
| Shine em neo-yellow (#FDE047) | Mantém coerência com a paleta do kit; dourado sobre P&B cria contraste elegante sem sair do sistema |
| Delay máximo de 480ms no stagger | Evitar frustração em listas longas; os primeiros 6 cards criam o efeito cascata sem penalizar o restante |
| Toast via Alpine listener no layout | Desacopla o toast do componente Livewire — qualquer componente pode disparar sem lógica duplicada |
