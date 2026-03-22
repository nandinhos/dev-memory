{{--
    ┌─────────────────────────────────────────────────────────────────┐
    │  x-neo.steps — Indicador de Progresso em Etapas                 │
    ├─────────────────────────────────────────────────────────────────┤
    │  DESCRIÇÃO                                                      │
    │  Linha do tempo horizontal mostrando as etapas de um processo   │
    │  (wizard/stepper). Etapas concluídas: fundo teal. Etapa atual:  │
    │  fundo preto (inversão). Etapas futuras: opacidade reduzida.    │
    │                                                                 │
    │  PROPRIEDADES                                                   │
    │  @passos  array  Lista de strings com o nome de cada etapa      │
    │  @ativo   int    Índice 1-based da etapa atual                  │
    │                                                                 │
    │  USO                                                            │
    │  <x-neo.steps                                                   │
    │      :passos="['Dados', 'Endereço', 'Pagamento', 'Revisão']"    │
    │      :ativo="2"                                                 │
    │  />                                                             │
    └─────────────────────────────────────────────────────────────────┘
--}}

@props([
    'passos' => [],
    'ativo'  => 1,   
])

<nav aria-label="Indicador de progresso em etapas" {{ $attributes }}>
    <ol class="flex items-center gap-2 font-bold text-sm font-body flex-wrap">

        @foreach($passos as $index => $passo)
            @php
                $numero     = $index + 1;
                $ehAtivo    = $numero === $ativo;    // Etapa corrente
                $ehCompleto = $numero < $ativo;      // Etapa já completada
            @endphp

            <li>
                <span
                    {{-- Estado visual: ativo=preto, completo=teal, pendente=opaco --}}
                    class="{{ $ehAtivo
                        ? 'bg-neo-black text-white border-4 border-black shadow-[--shadow-neo-sm]'
                        : ($ehCompleto
                            ? 'bg-neo-teal text-black border-4 border-black'
                            : 'bg-white text-black border-4 border-black opacity-40')
                    }} px-3 py-1 inline-block"

                    {{-- aria-current para etapa ativa --}}
                    @if($ehAtivo) aria-current="step" @endif

                    aria-label="{{ $ehCompleto ? 'Concluído' : ($ehAtivo ? 'Atual' : 'Pendente') }}: {{ $numero }}. {{ $passo }}"
                >
                    {{ $numero }}. {{ strtoupper($passo) }}
                </span>
            </li>

            {{-- Seta separadora entre etapas (oculta para screen readers) --}}
            @if(!$loop->last)
                <li aria-hidden="true">
                    <i class="fa-solid fa-chevron-right text-xs icon-hover {{ !$ehAtivo && !$ehCompleto ? 'opacity-30' : '' }}"></i>
                </li>
            @endif

        @endforeach
    </ol>
</nav>
