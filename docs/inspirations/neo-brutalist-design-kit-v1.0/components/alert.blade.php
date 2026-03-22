{{--
    ┌─────────────────────────────────────────────────────────────────┐
    │  x-neo.alert — Banner de Alerta Neo-Brutalista                  │
    ├─────────────────────────────────────────────────────────────────┤
    │  DESCRIÇÃO                                                      │
    │  Bloco de feedback visual para comunicar estados ao usuário:    │
    │  sucesso, aviso, erro ou informação. Usa cores semânticas,      │
    │  ícone Font Awesome e atributos ARIA para acessibilidade.       │
    │                                                                 │
    │  PROPRIEDADES                                                   │
    │  @tipo  string  Tipo do alerta — define cor e ícone:            │
    │         'sucesso'  → verde, ✓ check-circle, role="alert"        │
    │         'aviso'    → amarelo, ⚠ exclamation, role="status"      │
    │         'erro'     → magenta, ✕ times-circle, role="alert"      │
    │         'info'     → teal, ℹ info-circle, role="status"         │
    │                                                                 │
    │  USO                                                            │
    │  <x-neo.alert tipo="sucesso">Registro salvo!</x-neo.alert>      │
    │  <x-neo.alert tipo="erro">Falha ao processar.</x-neo.alert>     │
    │  <x-neo.alert tipo="aviso">Preencha todos os campos.</x-neo.alert>│
    └─────────────────────────────────────────────────────────────────┘
--}}

@props(['tipo' => 'info'])

@php
// Mapeamento de tipo para [cor-de-fundo, classe-ícone, aria-live, role]
[$bg, $icone, $live, $role] = match($tipo) {
    'sucesso' => ['bg-green-400',   'fa-check-circle',        'polite',    'alert' ],
    'aviso'   => ['bg-neo-yellow',  'fa-exclamation-triangle', 'polite',    'status'],
    'erro'    => ['bg-neo-magenta', 'fa-times-circle',         'assertive', 'alert' ],
    'info'    => ['bg-neo-teal',    'fa-info-circle',          'polite',    'status'],
    default   => ['bg-neo-teal',    'fa-info-circle',          'polite',    'status'],
};
@endphp

<div
    class="{{ $bg }} neo-border p-3 font-bold flex items-center gap-3 shadow-[--shadow-neo] font-body feedback-banner"
    role="{{ $role }}"
    aria-live="{{ $live }}"
    {{ $attributes }}
>
    {{-- Ícone decorativo (oculto para leitores de tela) --}}
    <i class="fas {{ $icone }} icon-hover" aria-hidden="true"></i>

    {{-- Mensagem de feedback --}}
    {{ $slot }}
</div>
