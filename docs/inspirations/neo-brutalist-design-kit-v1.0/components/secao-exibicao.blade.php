{{--
    x-neo.secao-exibicao
    Seção de exibicao de dados
--}}
<section
    id="exibicao"
    aria-labelledby="titulo-exibicao"
    class="neo-border bg-white p-6 shadow-hard relative card-neo scroll-mt-8 animate-fade-in-up"
>
    {{-- Label flutuante da seção --}}
    <div class="section-label bg-neo-yellow neo-border px-4 py-1 shadow-hard">
        <h2 id="titulo-exibicao" class="font-heading text-xl">Exibição de Dados</h2>
    </div>

    <div class="mt-6 space-y-8">

        {{-- ── Card de Produto — usa x-neo.card + x-neo.button ── --}}
        <x-neo.card
            titulo="Título em Negrito"
            descricao="Esta é uma descrição dentro de um componente de card padrão. Ele usa os estilos fundamentais de borda e sombra do sistema Neo-Brutalista."
        >
            <x-slot:rodape>
                <x-neo.button
                    variante="primario"
                    class="w-full text-xl py-2"
                    label="Ver mais sobre este item"
                >
                    Ver Mais →
                </x-neo.button>
            </x-slot:rodape>
        </x-neo.card>

        {{-- ── Lista de Itens — usa x-neo.list-item + x-neo.avatar internamente ── --}}
        <div>
            <h3 class="font-bold border-b-4 border-black inline-block mb-4 uppercase font-body text-sm">
                Lista de Itens
            </h3>
            <ul class="space-y-3" role="list" aria-label="Lista de pessoas">
                {{-- x-neo.list-item encapsula avatar + nome + cargo + seta --}}
                <x-neo.list-item
                    iniciais="JD"
                    cor="bg-neo-purple"
                    nome="João Silva"
                    papel="Engenheiro de Software"
                />
                <x-neo.list-item
                    iniciais="AS"
                    cor="bg-neo-salmon"
                    nome="Alice Souza"
                    papel="Designer de Produto"
                />
            </ul>
        </div>

        {{-- ── Badges & Avatars — usa x-neo.badge + x-neo.avatar ── --}}
        <div>
            <h3 class="font-bold border-b-4 border-black inline-block mb-4 uppercase font-body text-sm">
                Badges &amp; Avatars
            </h3>

            {{-- Badges coloridos com x-neo.badge --}}
            <div class="flex flex-wrap gap-2 mb-4" role="list" aria-label="Exemplos de badges">
                <x-neo.badge cor="teal"    role="listitem">Badge Teal</x-neo.badge>
                <x-neo.badge cor="verde"   role="listitem">Badge Verde</x-neo.badge>
                <x-neo.badge cor="roxo"    role="listitem">Badge Roxo</x-neo.badge>
            </div>

            {{-- Avatars com x-neo.avatar --}}
            <div class="flex gap-2" role="list" aria-label="Exemplos de avatares">
                <x-neo.avatar iniciais="HM" cor="bg-neo-yellow" tamanho="lg" nome="Hector M." role="listitem" />
                <x-neo.avatar iniciais="IM" cor="bg-neo-black"  tamanho="lg" nome="Iara M."   role="listitem" />
            </div>
        </div>

        {{-- ── Tabela de Dados (inline — sem componente atômico específico) ── --}}
        <div>
            <h3 class="font-bold border-b-4 border-black inline-block mb-4 uppercase font-body text-sm">
                Tabela de Registros
            </h3>
            <div class="overflow-x-auto" role="region" aria-label="Tabela de exemplo de dados">
                <table class="w-full neo-border shadow-[--shadow-neo] bg-white text-sm font-body">
                    <thead>
                        <tr class="bg-neo-black text-white">
                            <th scope="col" class="p-3 text-left font-heading uppercase border-b-4 border-black">Coluna 1</th>
                            <th scope="col" class="p-3 text-left font-heading uppercase border-b-4 border-black">Coluna 2</th>
                            <th scope="col" class="p-3 text-right font-heading uppercase border-b-4 border-black">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ([
                            ['Dado A1', 'Dado A2'],
                            ['Dado B1', 'Dado B2'],
                            ['Dado C1', 'Dado C2'],
                        ] as $i => [$col1, $col2])
                            <tr class="{{ $i < 2 ? 'border-b-2 border-black' : '' }} hover:bg-neo-teal/20 transition-colors">
                                <td class="p-3">{{ $col1 }}</td>
                                <td class="p-3">{{ $col2 }}</td>
                                <td class="p-3 text-right">
                                    <a href="#" class="font-bold underline hover:text-neo-black icon-hover" aria-label="Ver detalhes de {{ $col1 }}">
                                        VER &gt;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>
