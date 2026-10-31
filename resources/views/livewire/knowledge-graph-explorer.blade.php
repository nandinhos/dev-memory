<div class="flex-1 flex flex-col h-[calc(100vh-4rem)] lg:h-screen overflow-hidden bg-neo-bg"
     x-data="knowledgeGraphExplorer({
         nodesData: @js($graphData['nodes']),
         edgesData: @js($graphData['edges']),
         selectedId: @entangle('selectedMemoryId')
     })"
     x-init="initGraph()"
     @graph-data-updated.window="updateGraph($event.detail.data)">

    {{-- Script CDN do Vis-Network com carregamento seguro --}}
    @assets
    <script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
    @endassets

    {{-- Header de Controle e Filtros --}}
    <header class="bg-neo-white border-b-4 border-black p-4 shrink-0 flex flex-wrap items-center justify-between gap-4 z-10 shadow-neo-sm">
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <span class="text-xl">🕸️</span>
                <h1 class="font-heading font-black text-lg md:text-xl uppercase tracking-wider m-0 text-black">
                    Grafo Causal
                </h1>
            </div>

            <div class="hidden sm:flex items-center gap-2">
                <span class="bg-black text-white text-[11px] font-mono font-bold px-2.5 py-1 border border-black">
                    {{ count($graphData['nodes']) }} nós
                </span>
                <span class="bg-neo-yellow text-black text-[11px] font-mono font-bold px-2.5 py-1 border border-black">
                    {{ count($graphData['edges']) }} relações
                </span>
            </div>
        </div>

        {{-- Controles Centrais: Stack e Busca --}}
        <div class="flex items-center flex-wrap gap-2 flex-1 max-w-xl justify-end">
            @if($availableStacks->isNotEmpty())
                <div class="w-36">
                    <select wire:model.live="stack"
                            class="w-full bg-white neo-border-sm px-2.5 py-1.5 text-xs font-mono font-bold focus:outline-none focus:ring-2 focus:ring-black">
                        <option value="">Todas as Stacks</option>
                        @foreach($availableStacks as $s)
                            <option value="{{ $s }}">{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="w-44 md:w-56">
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Buscar no grafo..."
                       class="w-full bg-white neo-border-sm px-3 py-1.5 text-xs font-mono font-bold placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-black">
            </div>

            {{-- Botões de Zoom / Navegação no Canvas --}}
            <div class="flex items-center gap-1">
                <button type="button"
                        @click="zoomIn()"
                        title="Aumentar Zoom"
                        class="btn-neo bg-white neo-border-sm px-2.5 py-1 font-mono text-xs font-black hover:bg-neo-yellow">
                    +
                </button>
                <button type="button"
                        @click="zoomOut()"
                        title="Diminuir Zoom"
                        class="btn-neo bg-white neo-border-sm px-2.5 py-1 font-mono text-xs font-black hover:bg-neo-yellow">
                    -
                </button>
                <button type="button"
                        @click="fitView()"
                        title="Centralizar Grafo"
                        class="btn-neo bg-white neo-border-sm px-2.5 py-1 font-mono text-xs font-black hover:bg-neo-teal">
                    ⤢
                </button>
            </div>
        </div>
    </header>

    {{-- Banner de Foco Ativo (se aplicável) --}}
    @if($rootMemory)
        <div class="bg-neo-yellow border-b-4 border-black px-4 py-2 flex items-center justify-between gap-3 shrink-0">
            <div class="flex items-center gap-2 min-w-0">
                <span class="bg-black text-white px-2 py-0.5 text-[10px] font-mono font-bold uppercase shrink-0">
                    FOCO ATIVO (2 SALTOS)
                </span>
                <span class="font-heading font-black text-xs md:text-sm text-black truncate">
                    🎯 {{ $rootMemory->title }}
                </span>
            </div>

            <button type="button"
                    wire:click="clearFocus"
                    class="btn-neo bg-white neo-border-sm text-black px-3 py-1 text-xs font-mono font-bold hover:bg-neo-magenta hover:text-white shrink-0">
                ✕ Ver Todos os Nós
            </button>
        </div>
    @endif

    {{-- Área Principal: Canvas do Grafo + Drawer Lateral --}}
    <div class="flex-1 relative overflow-hidden flex">
        {{-- Canvas do Vis-Network --}}
        <div id="vis-network-container"
             x-ref="graphContainer"
             class="flex-1 w-full h-full cursor-grab active:cursor-grabbing bg-[#faf6ee]"
             style="background-image: radial-gradient(#d1d5db 1px, transparent 1px); background-size: 24px 24px;">
        </div>

        {{-- Legenda Neo-Brutalista Flutuante no Canto Inferior Esquerdo --}}
        <div class="absolute bottom-4 left-4 z-10 bg-white/95 neo-border-sm p-3 shadow-neo hidden sm:block backdrop-blur-sm">
            <div class="text-[10px] font-mono font-bold uppercase text-gray-500 mb-2">// LEGENDA</div>
            <div class="space-y-1.5 text-xs font-mono">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 border border-black inline-block bg-[#fee2e2]"></span>
                    <span class="text-black font-bold">Erro / Falha</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 border border-black inline-block bg-[#dcfce7]"></span>
                    <span class="text-black font-bold">Boa Prática</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 border border-black inline-block bg-[#fef9c3]"></span>
                    <span class="text-black font-bold">Lição Aprendida</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 border border-black inline-block bg-[#4f46e5]"></span>
                    <span class="text-black font-bold">Nó Central / Foco</span>
                </div>
            </div>
            <div class="mt-2 pt-2 border-t border-gray-200 text-[10px] font-mono text-gray-500">
                Arraste nós • Scroll p/ zoom • Clique p/ inspecionar
            </div>
        </div>

        {{-- Painel Lateral (Drawer) de Inspeção da Memória Selecionada --}}
        @if($selectedMemory)
            <div class="w-80 md:w-96 bg-white border-l-4 border-black p-5 flex flex-col justify-between shrink-0 shadow-[-6px_0px_0px_0px_rgba(0,0,0,1)] z-20 overflow-y-auto">
                <div class="space-y-4">
                    {{-- Topo do Drawer: Tipo e Fechar --}}
                    <div class="flex items-center justify-between gap-2 border-b-2 border-black pb-3">
                        @php
                            $badgeStyle = match($selectedMemory->type->value) {
                                'error' => 'bg-neo-magenta text-white',
                                'best_practice' => 'bg-neo-green text-black',
                                'lesson' => 'bg-neo-yellow text-black',
                                default => 'bg-gray-200 text-black',
                            };
                        @endphp
                        <span class="{{ $badgeStyle }} px-2 py-0.5 text-[11px] font-heading font-black uppercase border border-black">
                            {{ $selectedMemory->type->label() }}
                        </span>

                        <button type="button"
                                wire:click="clearSelection"
                                class="w-6 h-6 rounded-none border border-black font-mono text-xs font-bold hover:bg-black hover:text-white flex items-center justify-center">
                            ✕
                        </button>
                    </div>

                    {{-- Título da Memória --}}
                    <div>
                        <span class="text-[10px] font-mono uppercase text-gray-400 block mb-1">Título da Memória</span>
                        <h2 class="font-heading font-black text-base text-black leading-snug m-0">
                            {{ $selectedMemory->title }}
                        </h2>
                    </div>

                    {{-- Metadados: Stack e Recorrência --}}
                    <div class="grid grid-cols-2 gap-2 pt-2">
                        <div class="bg-gray-50 neo-border-sm p-2">
                            <span class="text-[9px] font-mono uppercase text-gray-500 block">Stack</span>
                            <span class="font-mono text-xs font-bold text-black truncate block mt-0.5">
                                {{ $selectedMemory->stack ?: 'Geral' }}
                            </span>
                        </div>
                        <div class="bg-gray-50 neo-border-sm p-2">
                            <span class="text-[9px] font-mono uppercase text-gray-500 block">Ocorrências</span>
                            <span class="font-heading text-lg font-black text-neo-magenta block mt-0.5 leading-none">
                                {{ $selectedMemory->recurrence_count }}x
                            </span>
                        </div>
                    </div>

                    {{-- Resumo da Memória --}}
                    @if($selectedMemory->description)
                        <div class="pt-2">
                            <span class="text-[10px] font-mono uppercase text-gray-400 block mb-1">Resumo Causal</span>
                            <p class="font-sans text-xs text-gray-700 bg-gray-50 p-2.5 neo-border-sm m-0 leading-relaxed max-h-36 overflow-y-auto">
                                {{ Str::limit($selectedMemory->description, 280) }}
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Ações Rápidas no Rodapé do Drawer --}}
                <div class="space-y-2 pt-4 border-t-2 border-black mt-4">
                    <button type="button"
                            wire:click="setFocus('{{ $selectedMemory->id }}')"
                            class="w-full btn-neo bg-neo-yellow neo-border-sm text-black py-2 font-heading font-black text-xs hover:bg-black hover:text-white transition-colors flex items-center justify-center gap-2">
                        <span>🎯 Focar Grafo Aqui (2 Saltos)</span>
                    </button>

                    <a href="{{ route('memories.show', $selectedMemory) }}"
                       wire:navigate
                       class="w-full btn-neo bg-neo-magenta neo-border-sm text-white py-2 font-heading font-black text-xs hover:bg-neo-teal hover:text-black transition-colors flex items-center justify-center gap-2 no-underline">
                        <span>Abrir Detalhe Completo ↗</span>
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- Script Alpine.js para Inicializar e Controlar o Vis-Network --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('knowledgeGraphExplorer', (config) => ({
                network: null,
                nodesDataSet: null,
                edgesDataSet: null,

                initGraph() {
                    this.$nextTick(() => {
                        this.mountNetwork(config.nodesData, config.edgesData);
                    });
                },

                mountNetwork(nodes, edges) {
                    const container = this.$refs.graphContainer;
                    if (!container) return;

                    // Cria DataSets do Vis-Network
                    this.nodesDataSet = new vis.DataSet(nodes);
                    this.edgesDataSet = new vis.DataSet(edges);

                    const data = {
                        nodes: this.nodesDataSet,
                        edges: this.edgesDataSet
                    };

                    const options = {
                        autoResize: true,
                        height: '100%',
                        width: '100%',
                        interaction: {
                            hover: true,
                            tooltipDelay: 150,
                            zoomView: true,
                            dragView: true,
                            navigationButtons: false,
                            keyboard: false
                        },
                        physics: {
                            solver: 'forceAtlas2Based',
                            forceAtlas2Based: {
                                gravitationalConstant: -60,
                                centralGravity: 0.015,
                                springLength: 120,
                                springConstant: 0.08,
                                damping: 0.4,
                                avoidOverlap: 0.8
                            },
                            stabilization: {
                                iterations: 80,
                                updateInterval: 25
                            }
                        }
                    };

                    this.network = new vis.Network(container, data, options);

                    // Evento de clique para selecionar nó
                    this.network.on('click', (params) => {
                        if (params.nodes.length > 0) {
                            const nodeId = params.nodes[0];
                            this.$wire.selectNode(nodeId);
                        } else if (params.edges.length === 0) {
                            this.$wire.clearSelection();
                        }
                    });
                },

                updateGraph(data) {
                    if (!this.network) {
                        this.mountNetwork(data.nodes, data.edges);
                        return;
                    }

                    this.nodesDataSet.clear();
                    this.edgesDataSet.clear();

                    this.nodesDataSet.add(data.nodes);
                    this.edgesDataSet.add(data.edges);

                    this.network.fit({ animation: { duration: 400, easingFunction: 'easeInOutQuad' } });
                },

                zoomIn() {
                    if (!this.network) return;
                    const scale = this.network.getScale();
                    this.network.moveTo({ scale: scale * 1.3, animation: { duration: 250 } });
                },

                zoomOut() {
                    if (!this.network) return;
                    const scale = this.network.getScale();
                    this.network.moveTo({ scale: scale * 0.7, animation: { duration: 250 } });
                },

                fitView() {
                    if (!this.network) return;
                    this.network.fit({ animation: { duration: 400, easingFunction: 'easeInOutQuad' } });
                }
            }));
        });
    </script>
</div>
