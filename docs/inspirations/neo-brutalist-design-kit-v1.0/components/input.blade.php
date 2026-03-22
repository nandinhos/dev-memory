{{--
    ┌─────────────────────────────────────────────────────────────────┐
    │  x-neo.input — Campo de Entrada Neo-Brutalista                  │
    ├─────────────────────────────────────────────────────────────────┤
    │  DESCRIÇÃO                                                      │
    │  Campo de texto com suporte a estado padrão, com erro e         │
    │  label visível/oculto. Segue o estilo Neo-Brutalista com        │
    │  borda grossa e sombra dura que "afunda" ao focar.              │
    │                                                                 │
    │  PROPRIEDADES                                                   │
    │  @id          string  Identificador único — obrigatório para    │
    │                       acessibilidade (liga label ao input)      │
    │  @tipo        string  Tipo HTML: text, email, password, etc.    │
    │  @placeholder string  Texto de placeholder interno              │
    │  @rotulo      string  Label visível acima do campo              │
    │  @erro        string  Mensagem de erro — ativa estado vermelho  │
    │  @valor       string  Valor inicial pré-preenchido              │
    │                                                                 │
    │  USO                                                            │
    │  <x-neo.input id="nome" rotulo="Nome" placeholder="..." />      │
    │  <x-neo.input id="email" tipo="email" erro="Email inválido" />  │
    └─────────────────────────────────────────────────────────────────┘
--}}

@props([
    'id'          => null,    
    'tipo'        => 'text',  
    'placeholder' => '',      
    'rotulo'      => null,    
    'erro'        => null,    
    'valor'       => null,    
])

<div class="space-y-1 w-full">

    {{-- Label: visível se 'rotulo' fornecido, senão oculto para leitores de tela --}}
    @if($rotulo)
        <label for="{{ $id }}" class="block text-xs font-bold font-body uppercase tracking-wider">
            {{ $rotulo }}
        </label>
    @elseif($id)
        <label for="{{ $id }}" class="sr-only">{{ $placeholder ?: $id }}</label>
    @endif

    <div class="relative">
        <input
            id="{{ $id }}"
            type="{{ $tipo }}"
            placeholder="{{ $placeholder }}"
            @if($valor) value="{{ $valor }}" @endif
            @if($erro)
                aria-invalid="true"
                aria-describedby="{{ $id }}-erro"
            @endif
            {{-- Classe muda conforme estado de erro --}}
            {{ $attributes->merge([
                'class' => $erro
                    ? 'w-full border-4 border-red-500 bg-red-50 text-red-600 shadow-[4px_4px_0_#ef4444] px-3 py-2 outline-none font-body'
                    : 'input-neo w-full neo-border shadow-[--shadow-neo] px-3 py-2 outline-none font-body bg-white',
            ]) }}
        />

        {{-- Badge de erro flutuante no canto superior direito --}}
        @if($erro)
            <span
                id="{{ $id }}-erro"
                class="absolute -top-3 right-0 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 border-2 border-black font-body"
                role="alert"
            >ERRO</span>
        @endif
    </div>

    {{-- Texto descritivo do erro abaixo do campo --}}
    @if($erro)
        <p class="text-red-500 text-xs font-body font-bold">{{ $erro }}</p>
    @endif

</div>
