{{--
    x-neo.secao-componentes
    Seção de componentes UI: botões, entradas (inputs, checkboxes),
    indicador de passos e abas — com Alpine.js para interatividade.
--}}
<section
    id="componentes"
    aria-labelledby="titulo-componentes"
    class="neo-border bg-white p-6 shadow-hard relative card-neo scroll-mt-8 animate-fade-in-up"
>
    {{-- Label flutuante --}}
    <div class="section-label bg-neo-salmon neo-border px-4 py-1 shadow-hard">
        <h2 id="titulo-componentes" class="font-heading text-xl">Componentes</h2>
    </div>

    <div class="mt-6 space-y-10">

        {{-- ── Botões — usa x-neo.button com variantes ── --}}
        <div>
            <h3 class="font-bold border-b-4 border-black inline-block mb-4 uppercase font-body text-sm">
                Botões
            </h3>
            <div class="flex flex-wrap gap-4" role="group" aria-label="Exemplos de botões">
                {{-- Variânte pílula: bordas completamente arredondadas --}}
                <x-neo.button variante="pilula" label="Botão primário estilo pílula">
                    Pílula (Primário)
                </x-neo.button>

                {{-- Variânte contorno: fundo branco, bordas arredondadas --}}
                <x-neo.button variante="contorno" label="Botão de contorno primário">
                    Contorno (Primário)
                </x-neo.button>

                {{-- Variânte destrutivo: magenta, leve rotação --}}
                <x-neo.button variante="destrutivo" label="Botão destrutivo">
                    Destrutivo
                </x-neo.button>

                {{-- Variânte texto: apenas sublinhado, sem borda/sombra --}}
                <x-neo.button variante="texto" label="Botão terciário — apenas texto">
                    Texto (Terciário)
                </x-neo.button>
            </div>
        </div>

        {{-- ── Entradas — usa x-neo.input ── --}}
        <div>
            <h3 class="font-bold border-b-4 border-black inline-block mb-4 uppercase font-body text-sm">
                Entradas
            </h3>
            <div class="space-y-4 max-w-md">

                {{-- Input padrão sem erro --}}
                <x-neo.input
                    id="input-padrao"
                    placeholder="Digite aqui..."
                    rotulo="Campo de Texto"
                />

                {{-- Input com estado de erro ativo --}}
                <x-neo.input
                    id="input-erro"
                    tipo="text"
                    valor="Dado inválido!"
                    erro="Verifique o valor informado"
                />

                {{-- Checkboxes --}}
                <fieldset>
                    <legend class="sr-only">Exemplo de checkboxes</legend>
                    <div class="flex gap-6">
                        <label class="flex items-center gap-2 cursor-pointer font-body">
                            <input
                                type="checkbox"
                                checked
                                class="input-neo border-4 border-black w-5 h-5 shadow-[--shadow-neo-sm] accent-black"
                                aria-label="Opção marcada"
                            />
                            <span class="font-bold">Marcado</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer font-body">
                            <input
                                type="checkbox"
                                class="input-neo border-4 border-black w-5 h-5 shadow-[--shadow-neo-sm]"
                                aria-label="Opção desmarcada"
                            />
                            <span class="font-bold">Desmarcado</span>
                        </label>
                    </div>
                </fieldset>

                {{-- Radio Buttons --}}
                <fieldset>
                    <legend class="sr-only">Exemplo de radio buttons</legend>
                    <div class="flex gap-6">
                        <label class="flex items-center gap-2 cursor-pointer font-body">
                            <input
                                type="radio"
                                name="radio-demo"
                                checked
                                class="border-4 border-black w-5 h-5 shadow-[--shadow-neo-sm] accent-black"
                                aria-label="Opção A"
                            />
                            <span class="font-bold">Opção A</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer font-body">
                            <input
                                type="radio"
                                name="radio-demo"
                                class="border-4 border-black w-5 h-5 shadow-[--shadow-neo-sm]"
                                aria-label="Opção B"
                            />
                            <span class="font-bold">Opção B</span>
                        </label>
                    </div>
                </fieldset>

            </div>
        </div>

        {{-- ── Passos — usa x-neo.steps ── --}}
        <div>
            <h3 class="font-bold border-b-4 border-black inline-block mb-4 uppercase font-body text-sm">
                Passos
            </h3>
            {{-- O componente gerencia visualmente: ativo=preto, completo=teal, pendente=opaco --}}
            <x-neo.steps
                :passos="['Passo Um', 'Passo Dois', 'Passo Três']"
                :ativo="1"
            />
        </div>

        {{-- ── Abas (Alpine.js) ── --}}
        <div
            x-data="{ abaAtiva: 'aba1' }"
            role="tablist"
            aria-label="Exemplo de abas"
        >
            <h3 class="font-bold border-b-4 border-black inline-block mb-4 uppercase font-body text-sm">
                Abas
            </h3>

            {{-- Lista de abas --}}
            <div class="flex" role="tablist">
                @foreach ([
                    ['aba1', 'Aba 1'],
                    ['aba2', 'Aba 2'],
                    ['aba3', 'Aba 3'],
                ] as [$id, $label])
                    <button
                        type="button"
                        role="tab"
                        :id="'tab-{{ $id }}'"
                        aria-controls="painel-{{ $id }}"
                        :aria-selected="abaAtiva === '{{ $id }}'"
                        @click="abaAtiva = '{{ $id }}'"
                        :class="abaAtiva === '{{ $id }}'
                            ? 'bg-neo-black text-white border-4 border-black px-4 py-2 font-bold font-body cursor-pointer'
                            : 'bg-white text-black border-4 border-l-0 border-black px-4 py-2 font-bold font-body cursor-pointer hover:bg-neo-yellow transition-colors'"
                        class="{{ $loop->first ? '' : '-ml-1' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Conteúdo das abas --}}
            <div class="p-5 border-4 border-t-0 border-black bg-white font-body text-sm">
                <div
                    id="painel-aba1"
                    role="tabpanel"
                    aria-labelledby="tab-aba1"
                    x-show="abaAtiva === 'aba1'"
                >
                    <strong class="font-heading">Conteúdo da Aba 1</strong>
                    <p class="mt-2 text-gray-600">Área de conteúdo da primeira aba. Clique nas outras abas para trocar.</p>
                </div>
                <div
                    id="painel-aba2"
                    role="tabpanel"
                    aria-labelledby="tab-aba2"
                    x-show="abaAtiva === 'aba2'"
                >
                    <strong class="font-heading">Conteúdo da Aba 2</strong>
                    <p class="mt-2 text-gray-600">Cada aba pode conter qualquer componente do sistema de design.</p>
                </div>
                <div
                    id="painel-aba3"
                    role="tabpanel"
                    aria-labelledby="tab-aba3"
                    x-show="abaAtiva === 'aba3'"
                >
                    <strong class="font-heading">Conteúdo da Aba 3</strong>
                    <p class="mt-2 text-gray-600">Alpine.js gerencia o estado das abas sem nenhum JavaScript adicional.</p>
                </div>
            </div>
        </div>

        {{-- ── Paginação inline (sem componente) ── --}}
        <div>
            <h3 class="font-bold border-b-4 border-black inline-block mb-4 uppercase font-body text-sm">
                Paginação
            </h3>
            <nav aria-label="Paginação de demonstração">
                <ol class="flex flex-wrap gap-1 font-bold font-body text-sm items-center">
                    <li>
                        <button type="button" class="border-4 border-black px-2 py-0.5 bg-white shadow-[4px_4px_0_#000] opacity-40 cursor-not-allowed" aria-label="Página anterior" disabled>&lt; PREV</button>
                    </li>
                    <li>
                        <button type="button" class="bg-neo-black text-white border-4 border-black px-2 py-0.5 shadow-[4px_4px_0_#000]" aria-label="Página 1" aria-current="page">1</button>
                    </li>
                    <li>
                        <button type="button" class="bg-white border-4 border-black px-2 py-0.5 shadow-[4px_4px_0_#000] hover:bg-neo-teal transition-colors" aria-label="Página 2">2</button>
                    </li>
                    <li>
                        <button type="button" class="bg-white border-4 border-black px-2 py-0.5 shadow-[4px_4px_0_#000] hover:bg-neo-teal transition-colors" aria-label="Página 3">3</button>
                    </li>
                    <li aria-hidden="true">
                        <span class="px-1 font-body text-gray-400">...</span>
                    </li>
                    <li>
                        <button type="button" class="bg-white border-4 border-black px-2 py-0.5 shadow-[4px_4px_0_#000] hover:bg-neo-teal transition-colors" aria-label="Página 10">10</button>
                    </li>
                    <li>
                        <button type="button" class="border-4 border-black px-2 py-0.5 bg-white shadow-[4px_4px_0_#000] hover:bg-neo-teal transition-colors" aria-label="Próxima página">NEXT &gt;</button>
                    </li>
                </ol>
            </nav>
        </div>

        {{-- ── Breadcrumbs — usa x-neo.breadcrumb ── --}}
        <div>
            <h3 class="font-bold border-b-4 border-black inline-block mb-4 uppercase font-body text-sm">
                Navegação Secundária (Breadcrumbs)
            </h3>
            {{-- Cada item: 'label' (obrigatório) + 'href' (opcional no último) --}}
            <x-neo.breadcrumb :itens="[
                ['label' => 'Início',  'href' => '#'],
                ['label' => 'Seção',  'href' => '#'],
                ['label' => 'Página Atual'],
            ]" />
        </div>

    </div>
</section>


