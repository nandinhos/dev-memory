# Neo-Brutalist Design System — Manual de Implementação

## Visão Geral

Este documento fornece instruções para implementar o Design System Neo-Brutalista em qualquer aplicação Laravel TALL Stack.

### Características Distintivas
- **Bordas**: 4px solid preto (#000000)
- **Sombras**: Duras, sem desfoque (ex: 4px 4px 0 #000)
- **Tipografia**: Oswald (headings, uppercase) + Space Mono (body)
- **Cores**: Fundo off-white (#F0EAD6), accents vibrantes
- **Animações**:wiggle, bounce-in, fade-in-up, shake, pop
- **Interações**: Efeito "afundar" ao clicar (transform + box-shadow)

---

## Instalação

### 1. Dependências
```bash
npm install -D tailwindcss@^4.0 @tailwindcss/vite
composer require livewire/livewire
```

### 2. CSS Base (app.css)
```css
/* Fontes */
@import url('https://fonts.googleapis.com/css2?family=Oswald:wght@400;700&family=Space+Mono:ital,wght@0,400;0,700;1,400&display=swap');

@import 'tailwindcss';

/* Tokens */
@theme {
  --font-heading: 'Oswald', 'Impact', ui-sans-serif;
  --font-body: 'Space Mono', ui-monospace;
  
  --color-neo-bg: #F0EAD6;
  --color-neo-black: #000000;
  --color-neo-teal: #22D3EE;
  --color-neo-magenta: #E879F9;
  --color-neo-yellow: #FACC15;
  --color-neo-white: #FFFFFF;
  --color-neo-salmon: #FDA4AF;
  --color-neo-green: #00FF7F;
  --color-neo-purple: #9370DB;
  
  --shadow-neo-sm: 2px 2px 0px 0px #000000;
  --shadow-neo: 4px 4px 0px 0px #000000;
  --shadow-neo-lg: 8px 8px 0px 0px #000000;
  --shadow-neo-xl: 12px 12px 0px 0px #000000;
}

@layer base {
  body {
    background-color: var(--color-neo-bg);
    color: var(--color-neo-black);
    font-family: var(--font-body);
  }
  h1, h2, h3, h4, h5, h6, .font-heading {
    font-family: var(--font-heading);
    text-transform: uppercase;
    font-weight: 700;
  }
}

@layer components {
  .neo-border { border: 4px solid #000; }
  .shadow-hard { box-shadow: 4px 4px 0px 0px #000; }
  
  .btn-neo, .card-neo {
    transition: all 0.1s ease;
    cursor: pointer;
  }
  
  .btn-neo:active, .card-neo:active {
    transform: translate(4px, 4px);
    box-shadow: 0px 0px 0px 0px #000 !important;
  }
  
  .input-neo:hover { border-color: #E879F9 !important; }
  
  .feedback-banner { animation: bounce-in 0.5s ease-out forwards; }
  .feedback-banner:hover { animation: wiggle 0.3s ease-in-out infinite; }
  .progress-pulse { animation: pulse-loading 1.5s ease-in-out infinite; }
  .icon-hover:hover { transform: scale(1.1); }
  
  .animate-fade-in-up { animation: fade-in-up 0.6s ease-out forwards; }
  .animate-bounce-in { animation: bounce-in 0.5s ease-out forwards; }
  .animate-shake { animation: shake 0.5s ease-in-out; }
  .animate-pop { animation: pop 0.3s ease-in-out; }
  
  .animation-delay-100 { animation-delay: 100ms; }
  .animation-delay-200 { animation-delay: 200ms; }
  .animation-delay-300 { animation-delay: 300ms; }
  .animation-delay-400 { animation-delay: 400ms; }
}

/* Keyframes */
@keyframes wiggle {
  0%, 100% { transform: rotate(-1deg); }
  50% { transform: rotate(1deg); }
}
@keyframes bounce-in {
  0% { transform: scale(0.9); opacity: 0; }
  70% { transform: scale(1.05); opacity: 1; }
  100% { transform: scale(1); }
}
@keyframes fade-in-up {
  from { transform: translateY(30px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
  20%, 40%, 60%, 80% { transform: translateX(4px); }
}
@keyframes pop {
  0% { transform: scale(1); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}
@keyframes pulse-loading {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.6; }
}
```

---

## Componentes

### Button (`x-neo.button`)

**Props:**
| Prop | Tipo | Valores | Padrão | Descrição |
|------|------|---------|--------|------------|
| variante | string | `primario`, `pilula`, `contorno`, `destrutivo`, `texto` | `primario` | Estilo visual |
| tipo | string | `button`, `submit`, `reset` | `button` | Tipo HTML |
| desativado | boolean | true/false | false | Estado desabilitado |
| label | string | - | null | aria-label |

**Implementação Blade:**
```blade
@props(['variante' => 'primario', 'tipo' => 'button', 'desativado' => false, 'label' => null])

@php
$classes = match($variante) {
    'primario'   => 'btn-neo bg-neo-teal neo-border shadow-neo px-6 py-2 font-heading hover:bg-neo-yellow transition-colors duration-100',
    'pilula'     => 'btn-neo bg-neo-teal neo-border shadow-neo rounded-full px-6 py-2 font-heading hover:shadow-neo-lg transition-all duration-100',
    'contorno'   => 'btn-neo bg-white neo-border shadow-neo rounded-xl px-6 py-2 font-heading hover:bg-neo-yellow transition-colors duration-100',
    'destrutivo' => 'btn-neo bg-neo-magenta neo-border shadow-neo px-6 py-2 font-heading -rotate-2 hover:rotate-0 transition-transform duration-100',
    'texto'      => 'underline underline-offset-2 font-heading font-bold px-4 py-2 hover:text-gray-600 transition-colors duration-100',
    default      => 'btn-neo bg-neo-teal neo-border shadow-neo px-6 py-2 font-heading hover:bg-neo-yellow transition-colors duration-100',
};
@endphp

<button type="{{ $tipo }}" @if($desativado) disabled @endif @if($label) aria-label="{{ $label }}" @endif {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</button>
```

**Uso:**
```blade
<x-neo.button variante="primario">Salvar</x-neo.button>
<x-neo.button variante="pilula">Continuar</x-neo.button>
<x-neo.button variante="contorno">Cancelar</x-neo.button>
<x-neo.button variante="destrutivo">Excluir</x-neo.button>
<x-neo.button variante="texto">Ver mais</x-neo.button>
```

---

### Input (`x-neo.input`)

**Props:**
| Prop | Tipo | Padrão | Descrição |
|------|------|--------|------------|
| id | string | null | Obrigatório para acessibilidade |
| tipo | string | `text` | Tipo HTML |
| placeholder | string | `` | Placeholder |
| rotulo | string | null | Label visível |
| erro | string | null | Mensagem de erro |
| valor | string | null | Valor inicial |

**Implementação Blade:**
```blade
@props(['id' => null, 'tipo' => 'text', 'placeholder' => '', 'rotulo' => null, 'erro' => null, 'valor' => null])

<div class="space-y-1 w-full">
    @if($rotulo)
        <label for="{{ $id }}" class="block text-xs font-bold font-body uppercase tracking-wider">{{ $rotulo }}</label>
    @elseif($id)
        <label for="{{ $id }}" class="sr-only">{{ $placeholder ?: $id }}</label>
    @endif
    
    <div class="relative">
        <input
            id="{{ $id }}"
            type="{{ $tipo }}"
            placeholder="{{ $placeholder }}"
            @if($valor) value="{{ $valor }}" @endif
            @if($erro) aria-invalid="true" aria-describedby="{{ $id }}-erro" @endif
            {{ $attributes->merge([
                'class' => $erro
                    ? 'w-full border-4 border-red-500 bg-red-50 text-red-600 shadow-[4px_4px_0_#ef4444] px-3 py-2 outline-none font-body'
                    : 'input-neo w-full neo-border shadow-neo px-3 py-2 outline-none font-body bg-white'
            ]) }}
        />
        @if($erro)
            <span id="{{ $id }}-erro" class="absolute -top-3 right-0 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 border-2 border-black font-body" role="alert">ERRO</span>
        @endif
    </div>
    @if($erro)
        <p class="text-red-500 text-xs font-body font-bold">{{ $erro }}</p>
    @endif
</div>
```

**Uso:**
```blade
<x-neo.input id="nome" rotulo="Nome completo" placeholder="Seu nome" />
<x-neo.input id="email" tipo="email" erro="Email inválido" />
<x-neo.input id="senha" tipo="password" placeholder="••••••••" />
```

---

### Alert (`x-neo.alert`)

**Props:**
| Prop | Tipo | Valores | Padrão | Descrição |
|------|------|---------|--------|------------|
| tipo | string | `sucesso`, `aviso`, `erro`, `info` | `info` | Tipo semântico |

**Implementação Blade:**
```blade
@props(['tipo' => 'info'])

@php
[$bg, $icone] = match($tipo) {
    'sucesso' => ['bg-green-400', 'fa-check-circle'],
    'aviso'   => ['bg-neo-yellow', 'fa-exclamation-triangle'],
    'erro'    => ['bg-neo-magenta', 'fa-times-circle'],
    'info'    => ['bg-neo-teal', 'fa-info-circle'],
    default   => ['bg-neo-teal', 'fa-info-circle'],
};
@endphp

<div class="{{ $bg }} neo-border p-3 font-bold flex items-center gap-3 shadow-neo font-body feedback-banner" role="{{ $tipo === 'erro' || $tipo === 'sucesso' ? 'alert' : 'status' }}" aria-live="polite">
    <i class="fas {{ $icone }} icon-hover" aria-hidden="true"></i>
    {{ $slot }}
</div>
```

**Uso:**
```blade
<x-neo.alert tipo="sucesso">Operação realizada com sucesso!</x-neo.alert>
<x-neo.alert tipo="aviso">Verifique os campos obrigatórios.</x-neo.alert>
<x-neo.alert tipo="erro">Falha ao processar solicitação.</x-neo.alert>
<x-neo.alert tipo="info">Novas atualizações disponíveis.</x-neo.alert>
```

---

### Card (`x-neo.card`)

**Props:**
| Prop | Tipo | Padrão | Descrição |
|------|------|--------|------------|
| classe | string | null | Classes adicionais |

**Implementação Blade:**
```blade
@props(['classe' => ''])

<div class="card-neo bg-white neo-border shadow-neo p-4 {{ $classe }}">
    {{ $slot }}
</div>
```

**Uso:**
```blade
<x-neo.card>
    <h3 class="font-heading text-xl">Título do Card</h3>
    <p class="font-body">Conteúdo do card...</p>
</x-neo.card>
```

---

### Badge (`x-neo.badge`)

**Props:**
| Prop | Tipo | Valores | Padrão | Descrição |
|------|------|---------|--------|------------|
| variante | string | `padrao`, `sucesso`, `aviso`, `erro` | `padrao` | Cor de fundo |
| texto | string | - | null | Texto do badge |

**Implementação Blade:**
```blade
@props(['variante' => 'padrao', 'texto' => null])

@php
$classes = match($variante) {
    'sucesso' => 'bg-green-400',
    'aviso'   => 'bg-neo-yellow',
    'erro'    => 'bg-neo-magenta',
    default   => 'bg-neo-teal',
};
@endphp

<span class="inline-block {{ $classes }} border-2 border-black px-2 py-0.5 text-xs font-bold font-heading badge-hover">
    {{ $texto ?: $slot }}
</span>
```

**Uso:**
```blade
<x-neo.badge variante="sucesso">Novo</x-neo.badge>
<x-neo.badge variante="aviso">Pendente</x-neo.badge>
<x-neo.badge variante="erro">Excluído</x-neo.badge>
<x-neo.badge>Default</x-neo.badge>
```

---

### Modal (`x-neo.modal`)

**Props:**
| Prop | Tipo | Padrão | Descrição |
|------|------|--------|------------|
| titulo | string | null | Título do modal |
| abrir | boolean | false | Controlar abertura |
| tamanho | string | `md` | `sm`, `md`, `lg`, `xl` |

**Implementação Blade:**
```blade
@props(['titulo' => null, 'abrir' => false, 'tamanho' => 'md'])

@php
$width = match($tamanho) {
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    default => 'max-w-md',
};
@endphp

<div x-data="{ aberto: {{ $abrir ? 'true' : 'false' }} }">
    {{ $trigger ?? '' }}
    
    <div x-show="aberto" x-cloak class="fixed inset-0 z-50 flex items-center justify-center">
        <div x-show="aberto" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 bg-black/50" @click="aberto = false"></div>
        
        <div x-show="aberto" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="relative {{ $width }} w-full mx-4">
            <div class="bg-white neo-border shadow-neo-lg p-6">
                @if($titulo)
                    <h2 class="font-heading text-xl mb-4">{{ $titulo }}</h2>
                @endif
                {{ $slot }}
                <div class="mt-4 flex justify-end">
                    <button @click="aberto = false" class="btn-neo bg-neo-teal neo-border shadow-neo px-4 py-2 font-heading">Fechar</button>
                </div>
            </div>
        </div>
    </div>
</div>
```

**Uso:**
```blade
<x-neo.modal titulo="Confirmar ação" abrir="true">
    <p class="font-body">Tem certeza que deseja continuar?</p>
</x-neo.modal>
```

---

### Header (`x-neo.header`)

**Props:**
| Prop | Tipo | Padrão | Descrição |
|------|------|--------|------------|
| titulo | string | `Neo-Brutalist` | Título do header |
| nav | string | null | HTML de navegação |

**Implementação Blade:**
```blade
@props(['titulo' => 'Neo-Brutalist', 'nav' => null])

<header class="bg-white neo-border shadow-neo p-4 mb-8 animate-slide-in">
    <div class="flex justify-between items-center">
        <h1 class="font-heading text-2xl">{{ $titulo }}</h1>
        @if($nav)
            <nav>{{ $nav }}</nav>
        @endif
    </div>
</header>
```

---

### Footer/Rodape (`x-neo.rodape`)

**Props:**
| Prop | Tipo | Padrão | Descrição |
|------|------|--------|------------|
| texto | string | `© 2024 Neo-Brutalist Design System` | Texto do rodapé |

**Implementação Blade:**
```blade
@props(['texto' => '© 2024 Neo-Brutalist Design System'])

<footer class="bg-white neo-border shadow-neo p-4 mt-8 text-center">
    <p class="font-body text-sm">{{ $texto }}</p>
</footer>
```

---

### Avatar (`x-neo.avatar`)

**Props:**
| Prop | Tipo | Padrão | Descrição |
|------|------|--------|------------|
| src | string | null | URL da imagem |
| nome | string | null | Nome para fallback |
| tamanho | string | `md` | `sm`, `md`, `lg` |

**Implementação Blade:**
```blade
@props(['src' => null, 'nome' => null, 'tamanho' => 'md'])

@php
$size = match($tamanho) {
    'sm' => 'w-8 h-8 text-xs',
    'md' => 'w-12 h-12 text-sm',
    'lg' => 'w-16 h-16 text-base',
    default => 'w-12 h-12 text-sm',
};
$initials = $nome ? strtoupper(substr($nome, 0, 2)) : '?';
@endphp

<div class="neo-border shadow-neo rounded-full overflow-hidden flex items-center justify-center {{ $size }} bg-neo-teal font-heading">
    @if($src)
        <img src="{{ $src }}" alt="{{ $nome }}" class="w-full h-full object-cover">
    @else
        {{ $initials }}
    @endif
</div>
```

---

### Empty State (`x-neo.empty-state`)

**Props:**
| Prop | Tipo | Padrão | Descrição |
|------|------|--------|------------|
| titulo | string | `Nenhum resultado` | Título |
| mensagem | string | null | Mensagem descritiva |
| icone | string | `fa-folder-open` | Ícone Font Awesome |

**Implementação Blade:**
```blade
@props(['titulo' => 'Nenhum resultado', 'mensagem' => null, 'icone' => 'fa-folder-open'])

<div class="bg-white neo-border shadow-neo p-8 text-center">
    <i class="fas {{ $icone }} text-4xl text-neo-teal mb-4"></i>
    <h3 class="font-heading text-xl mb-2">{{ $titulo }}</h3>
    @if($mensagem)
        <p class="font-body text-gray-600">{{ $mensagem }}</p>
    @endif
</div>
```

---

### Pagination (`x-neo.pagination`)

**Props:**
| Prop | Tipo | Padrão | Descrição |
|------|------|--------|------------|
| currentPage | int | 1 | Página atual |
| totalPages | int | 1 | Total de páginas |
| baseUrl | string | `?page=` | URL base |

**Implementação Blade:**
```blade
@props(['currentPage' => 1, 'totalPages' => 1, 'baseUrl' => '?page='])

@if($totalPages > 1)
<nav class="flex justify-center gap-2">
    @for($i = 1; $i <= $totalPages; $i++)
        <a href="{{ $baseUrl . $i }}" class="btn-neo {{ $i === $currentPage ? 'bg-neo-teal' : 'bg-white' }} neo-border shadow-neo px-3 py-1 font-heading text-sm">
            {{ $i }}
        </a>
    @endfor
</nav>
@endif
```

---

### Breadcrumb (`x-neo.breadcrumb`)

**Props:**
| Prop | Tipo | Padrão | Descrição |
|------|------|--------|------------|
| itens | array | `[]` | Array de [`label`, `url`] |

**Implementação Blade:**
```blade
@props(['itens' => []])

<nav class="font-body text-sm">
    <ol class="flex gap-2">
        @foreach($itens as $index => $item)
            @if($index > 0)
                <li class="text-gray-400">/</li>
            @endif
            @if(isset($item[1]))
                <a href="{{ $item[1] }}" class="hover:text-neo-magenta transition-colors">{{ $item[0] }}</a>
            @else
                <span class="font-bold">{{ $item[0] }}</span>
            @endif
        @endforeach
    </ol>
</nav>
```

**Uso:**
```blade
<x-neo.breadcrumb :items="[['Home', '/'], ['Produtos', '/produtos'], 'Editar']" />
```

---

### List Item (`x-neo.list-item`)

**Props:**
| Prop | Tipo | Padrão | Descrição |
|------|------|--------|------------|
| href | string | null | Link |
| ativo | boolean | false | Estado ativo |

**Implementação Blade:**
```blade
@props(['href' => '#', 'ativo' => false])

<a href="{{ $href }}" class="block p-3 border-b-2 border-black hover:bg-neo-bg transition-colors list-item-hover {{ $ativo ? 'bg-neo-yellow' : '' }}">
    {{ $slot }}
</a>
```

---

### Steps (`x-neo.steps`)

**Props:**
| Prop | Tipo | Padrão | Descrição |
|------|------|--------|------------|
| etapas | array | `[]` | Array de nomes das etapas |
| atual | int | 1 | Etapa atual (1-indexed) |

**Implementação Blade:**
```blade
@props(['etapas' => [], 'atual' => 1])

<div class="flex gap-2 overflow-x-auto">
    @foreach($etapas as $index => $etapa)
        <div class="flex items-center">
            <div class="neo-border shadow-neo px-3 py-2 font-heading text-sm {{ $index + 1 <= $atual ? 'bg-neo-teal' : 'bg-white' }}">
                {{ $index + 1 }}. {{ $etapa }}
            </div>
            @if(!$loop->last)
                <div class="w-4 h-1 bg-black"></div>
            @endif
        </div>
    @endforeach
</div>
```

**Uso:**
```blade
<x-neo.steps :etapas="['Dados', 'Pagamento', 'Confirmação']" :atual="2" />
```

---

## Layout Base

```blade
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Neo-Brutalist' }}</title>
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-neo-bg min-h-screen">
    {{ $header ?? '' }}
    
    <main class="container mx-auto px-4 py-8">
        {{ $content ?? '' }}
    </main>
    
    {{ $footer ?? '' }}
    
    @livewireScripts
</body>
</html>
```

---

## Checklist de Implementação

- [ ] Instalar Tailwind CSS v4
- [ ] Configurar fontes (Oswald + Space Mono)
- [ ] Definir tokens no @theme
- [ ] Adicionar classes utilitárias (.neo-border, .shadow-neo, .btn-neo)
- [ ] Criar diretório `resources/views/components/neo/`
- [ ] Implementar componentes seguir specs acima
- [ ] Registrar componentes em ServiceProvider (opcional)
- [ ] Testar responsividade
- [ ] Validar acessibilidade (ARIA, labels)