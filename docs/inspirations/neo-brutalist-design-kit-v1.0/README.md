# Neo-Brutalist Design System - Export

## Arquivos Gerados

| Arquivo | Descrição |
|---------|------------|
| `design-tokens.json` | Tokens em formato JSON (Design Tokens Format) |
| `IMPLEMENTATION_GUIDE.md` | Guia completo de implementação para IAs |
| `components/` | Cópia dos componentes prontos para uso |
| `app.css` | CSS base com todos os tokens e utilitários |

## Quick Start

### Para IAs (agente de implementação)

1. **Ler o arquivo `design-tokens.json`** para obter todos os valores de tokens
2. **Consultar `IMPLEMENTATION_GUIDE.md`** para spec detalhada de cada componente
3. **Copiar componentes de `components/`** para o projeto destino

### Para Uso Manual

1. Copiar `app.css` para `resources/css/app.css` do projeto
2. Criar diretório `resources/views/components/neo/`
3. Copiar componentes de `exports/components/` para o diretório criado
4. No layout base, carregar Font Awesome e Livewire scripts

## Comandos Úteis

```bash
# Gerar novamente os arquivos de exportação
php artisan make:export-neo-brutalist

# Listar componentes disponíveis
php artisan neo-brutalist:list
```

## Estrutura de Arquivos

```
exports/
├── design-tokens.json      # Tokens em JSON padrão
├── IMPLEMENTATION_GUIDE.md # Guia completo
├── components/
│   ├── button.blade.php
│   ├── input.blade.php
│   ├── alert.blade.php
│   ├── card.blade.php
│   ├── badge.blade.php
│   ├── modal.blade.php
│   ├── header.blade.php
│   ├── rodape.blade.php
│   ├── avatar.blade.php
│   ├── empty-state.blade.php
│   ├── pagination.blade.php
│   ├── breadcrumb.blade.php
│   ├── list-item.blade.php
│   ├── steps.blade.php
│   └── nav.blade.php
└── app.css                 # CSS completo
```