{{--
    x-neo.secao-fundacoes
    Seção de fundações do sistema de design:
    tokens de cores, escala tipográfica, elevação e ícones.
--}}
<section
    id="fundacoes"
    aria-labelledby="titulo-fundacoes"
    class="neo-border bg-white p-6 shadow-hard relative card-neo scroll-mt-8 animate-fade-in-up"
>
    {{-- Label flutuante --}}
    <div class="section-label bg-neo-teal neo-border px-4 py-1 shadow-hard">
        <h2 id="titulo-fundacoes" class="font-heading text-xl">Fundações</h2>
    </div>

    <div class="mt-6 space-y-10">

        {{-- ── Tokens de Cores ── --}}
        <div class="animate-fade-in-up animation-delay-100">
            <h3 class="font-bold border-b-4 border-black inline-block mb-4 uppercase font-body text-sm">
                Tokens de Cores
            </h3>
            <div class="grid grid-cols-3 sm:grid-cols-6 gap-4" role="list" aria-label="Paleta de cores Neo-Brutalista">
                @foreach ([
                    ['bg-neo-teal',    'Teal',    '#22D3EE'],
                    ['bg-neo-magenta', 'Magenta', '#E879F9'],
                    ['bg-neo-yellow',  'Amarelo', '#FACC15'],
                    ['bg-neo-black',   'Preto',   '#000000'],
                    ['bg-white',       'Branco',  '#FFFFFF'],
                    ['bg-neo-green',   'Verde',   '#00FF7F'],
                ] as [$bg, $nome, $hex])
                    <div class="text-center" role="listitem">
                        <div
                            class="w-full aspect-square {{ $bg }} neo-border shadow-[--shadow-neo-sm] mb-2"
                            title="{{ $nome }} — {{ $hex }}"
                        ></div>
                        <span class="text-xs font-bold uppercase font-body block">{{ $nome }}</span>
                        <span class="text-[10px] text-gray-500 font-body">{{ $hex }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ── Escala Tipográfica ── --}}
        <div class="animate-fade-in-up animation-delay-200">
            <h3 class="font-bold border-b-4 border-black inline-block mb-4 uppercase font-body text-sm">
                Escala Tipográfica
            </h3>
            <div class="space-y-3 border-l-4 border-black pl-4">
                <p class="font-heading text-6xl md:text-7xl leading-tight">H1. Título Gigante</p>
                <p class="font-heading text-5xl md:text-6xl leading-tight">H2. Título Grande</p>
                <p class="font-heading text-4xl md:text-5xl leading-tight">H3. Título Médio</p>
                <p class="font-heading text-2xl md:text-3xl leading-tight">H4. Título Pequeno</p>
                <p class="text-base font-body leading-relaxed">
                    Texto de Corpo. Usado para parágrafos e leitura geral. Fonte monoespaçada altamente legível.
                </p>
                <p class="text-xs uppercase tracking-wider font-bold text-gray-500 font-body">
                    Texto de Legenda / Rótulo
                </p>
            </div>
        </div>

        {{-- ── Tokens de Elevação ── --}}
        <div class="animate-fade-in-up animation-delay-300">
            <h3 class="font-bold border-b-4 border-black inline-block mb-6 uppercase font-body text-sm">
                Tokens de Elevação
            </h3>
            <div class="flex flex-wrap gap-8 items-end" role="list" aria-label="Níveis de sombra disponíveis">
                @foreach ([
                    ['shadow-[--shadow-neo-sm]', 'SM'],
                    ['shadow-[--shadow-neo]',    'Padrão'],
                    ['shadow-[--shadow-neo-lg]', 'LG'],
                    ['shadow-[--shadow-neo-xl]', 'XL'],
                ] as [$shadow, $label])
                    <div
                        class="w-20 h-20 bg-white neo-border {{ $shadow }} flex items-center justify-center font-bold text-xs card-neo font-body"
                        role="listitem"
                        aria-label="Sombra {{ $label }}"
                    >
                        {{ $label }}
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ── Escala de Espaçamento ── --}}
        <div>
            <h3 class="font-bold border-b-4 border-black inline-block mb-4 uppercase font-body text-sm">
                Escala de Espaçamento
            </h3>
            <div class="flex flex-wrap items-end gap-2" role="list" aria-label="Escala de espaçamento visual">
                @foreach ([
                    ['w-1',  'h-8',  ''],
                    ['w-2',  'h-12', ''],
                    ['w-3',  'h-16', ''],
                    ['w-4',  'h-20', '16'],
                    ['w-6',  'h-24', ''],
                    ['w-8',  'h-28', '32'],
                    ['w-12', 'h-32', '48'],
                ] as [$w, $h, $label])
                    <div
                        class="bg-neo-magenta {{ $w }} {{ $h }} border-l-2 border-black relative"
                        role="listitem"
                        aria-label="{{ $label ? 'Espaçamento '.$label.'px' : '' }}"
                    >
                        @if($label)
                            <span class="absolute -top-5 left-0 text-[10px] font-bold font-body">{{ $label }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ── Ícones ── --}}

        <div class="animate-fade-in-up animation-delay-400">
            <h3 class="font-bold border-b-4 border-black inline-block mb-4 uppercase font-body text-sm">
                Ícones
            </h3>
            <div class="flex flex-wrap gap-4 text-2xl" role="list" aria-label="Exemplos de ícones com estilo neo-brutalista">
                @foreach ([
                    ['fa-solid fa-xmark',            'bg-neo-yellow',  'text-black', 'Fechar'],
                    ['fa-solid fa-check',             'bg-neo-green',   'text-black', 'Confirmar'],
                    ['fa-solid fa-gear',              'bg-white',       'text-black', 'Configurações'],
                    ['fa-solid fa-arrow-right',       'bg-white',       'text-black', 'Avançar'],
                    ['fa-regular fa-user',            'bg-white',       'text-black', 'Usuário'],
                    ['fa-solid fa-trash',             'bg-neo-magenta', 'text-white', 'Excluir'],
                    ['fa-solid fa-magnifying-glass',  'bg-white',       'text-black', 'Pesquisar'],
                    ['fa-solid fa-bars',              'bg-white',       'text-black', 'Menu'],
                ] as [$icon, $bg, $cor, $label])
                    <i
                        class="{{ $icon }} {{ $bg }} {{ $cor }} p-2 neo-border shadow-hard badge-hover icon-item"
                        role="listitem"
                        title="{{ $label }}"
                        aria-label="{{ $label }}"
                    ></i>
                @endforeach
            </div>
        </div>

    </div>
</section>
