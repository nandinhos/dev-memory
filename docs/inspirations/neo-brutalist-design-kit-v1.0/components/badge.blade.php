{{--
    ┌─────────────────────────────────────────────────────────────────┐
    │  x-neo.badge — Etiqueta / Badge Neo-Brutalista                  │
    ├─────────────────────────────────────────────────────────────────┤
    │  DESCRIÇÃO                                                      │
    │  Elemento inline para classificação e status. Usa bordas        │
    │  arredondadas (permitidas para badges), borda preta grossa      │
    │  e sombra dura pequena. Sempre em caixa alta.                   │
    │                                                                 │
    │  PROPRIEDADES                                                   │
    │  @cor  string  Paleta de cor do badge:                          │
    │         'teal'    → ciano (#22D3EE)                             │
    │         'verde'   → verde claro                                 │
    │         'roxo'    → roxo escuro (texto branco)                  │
    │         'amarelo' → amarelo (#FACC15)                           │
    │         'magenta' → magenta (#E879F9)                           │
    │         'salmon'  → salmão rosa (#FDA4AF)                       │
    │                                                                 │
    │  USO                                                            │
    │  <x-neo.badge cor="teal">Em andamento</x-neo.badge>             │
    │  <x-neo.badge cor="verde">Aprovado</x-neo.badge>                │
    │  <x-neo.badge cor="roxo">Admin</x-neo.badge>                    │
    └─────────────────────────────────────────────────────────────────┘
--}}

@props(['cor' => 'teal'])

@php
// Cada cor mapeia para [fundo, texto] — Roxo usa texto branco por contraste
[$bg, $texto] = match($cor) {
    'teal'    => ['bg-neo-teal',    'text-black'],
    'verde'   => ['bg-green-400',   'text-black'],
    'roxo'    => ['bg-neo-purple',  'text-white'],
    'amarelo' => ['bg-neo-yellow',  'text-black'],
    'magenta' => ['bg-neo-magenta', 'text-black'],
    'salmon'  => ['bg-neo-salmon',  'text-black'],
    default   => ['bg-neo-teal',    'text-black'],
};
@endphp

<span
    {{ $attributes->merge([
        'class' => "$bg $texto border-2 border-black rounded-full px-3 py-0.5 font-bold text-xs shadow-[--shadow-neo-sm] font-body uppercase inline-block badge-hover"
    ]) }}
>
    {{ $slot }}
</span>
