<div class="flex-1 w-full h-full flex flex-col overflow-hidden bg-[#faf6ee] relative"
     x-data="knowledgeGraphExplorer({
         nodesData: @js($graphData['nodes']),
         edgesData: @js($graphData['edges']),
         selectedId: @entangle('selectedNodeId')
     })"
     x-init="initGraph()"
     @graph-data-updated.window="updateGraph($event.detail.data)">

    {{-- Script CDN do Vis-Network com carregamento seguro --}}
    @assets
    <script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
    @endassets

    {{-- Header de Controle e Filtros Neo-Brutalista --}}
    <header class="bg-neo-white border-b-4 border-black px-4 py-3 shrink-0 flex flex-wrap items-center justify-between gap-4 z-10 shadow-neo-sm">
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <span class="text-xl">🕸️</span>
                <h1 class="font-heading font-black text-lg md:text-xl uppercase tracking-wider m-0 text-black">
                    Grafo Causal
                </h1>
            </div>

            <div class="flex items-center gap-2">
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
                        title="Centralizar / Enquadrar Todos os Nós"
                        class="btn-neo bg-white neo-border-sm px-2.5 py-1 font-mono text-xs font-black hover:bg-neo-teal">
                    ⤢
                </button>
            </div>
        </div>
    </header>

    {{-- Banner de Foco Ativo (se aplicável) --}}
    @if($rootMemory)
        <div class="bg-neo-yellow border-b-4 border-black px-4 py-2 flex items-center justify-between gap-3 shrink-0 z-10">
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

    {{-- Área Principal: Canvas Full Screen do Grafo + Drawer Lateral --}}
    <div class="flex-1 w-full h-full relative overflow-hidden flex">
        {{-- Canvas do Vis-Network com altura 100% garantida --}}
        <div id="vis-network-container"
             x-ref="graphContainer"
             class="flex-1 w-full h-full cursor-grab active:cursor-grabbing bg-[#faf6ee] relative"
             style="height: 100%; width: 100%; min-height: 400px; background-image: radial-gradient(#d1d5db 1px, transparent 1px); background-size: 24px 24px;">
        </div>

        {{-- Legenda Neo-Brutalista Flutuante no Canto Inferior Esquerdo com Cores Explícitas --}}
        <div class="absolute bottom-5 left-5 z-20 bg-white neo-border p-3.5 shadow-neo hidden sm:block backdrop-blur-sm pointer-events-auto"
             x-data="{ collapsed: false }">
            <div class="flex items-center justify-between gap-4 mb-2 pb-1 border-b-2 border-black">
                <span class="text-[10px] font-mono font-black uppercase text-black tracking-wider">// LEGENDA DO GRAFO</span>
                <button type="button" @click="collapsed = !collapsed" class="text-xs font-mono font-black hover:text-neo-magenta px-1">
                    <span x-text="collapsed ? '[+]' : '[−]'"></span>
                </button>
            </div>
            
            <div x-show="!collapsed" class="space-y-2 text-xs font-mono transition-all">
                <div class="flex items-center gap-2.5">
                    <span class="w-4 h-4 border-2 border-black inline-block shrink-0 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]"
                          style="background-color: #fee2e2;"></span>
                    <span class="text-black font-bold text-[11px]">Erro / Falha</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <span class="w-4 h-4 border-2 border-black inline-block shrink-0 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]"
                          style="background-color: #dcfce7;"></span>
                    <span class="text-black font-bold text-[11px]">Boa Prática</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <span class="w-4 h-4 border-2 border-black inline-block shrink-0 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]"
                          style="background-color: #fef08a;"></span>
                    <span class="text-black font-bold text-[11px]">Lição Aprendida</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <span class="w-4 h-4 border-2 border-black inline-block shrink-0 rounded-full shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]"
                          style="background-color: #ccfbf1;"></span>
                    <span class="text-black font-bold text-[11px]">Tecnologia (Hub)</span>
                </div>
                <div class="flex items-center gap-2.5">
                    <span class="w-4 h-4 border-2 border-black inline-block shrink-0 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]"
                          style="background-color: #4f46e5;"></span>
                    <span class="text-black font-bold text-[11px]">Foco Central</span>
                </div>
                <div class="pt-1.5 mt-1 border-t border-gray-200 text-[10px] font-mono text-gray-500">
                    Arraste nós • Scroll p/ zoom • Clique p/ inspecionar
                </div>
            </div>
        </div>

        {{-- Painel Lateral (Drawer) de Inspeção do Nó Selecionado --}}
        @if($selectedNode)
            <div class="w-80 md:w-96 bg-white border-l-4 border-black p-5 flex flex-col justify-between shrink-0 shadow-[-6px_0px_0px_0px_rgba(0,0,0,1)] z-30 overflow-y-auto">
                <div class="space-y-4">
                    {{-- Nó de Memória --}}
                    @if($selectedNode->memory)
                        @php
                            $mem = $selectedNode->memory;
                            $badgeStyle = match($mem->type->value) {
                                'error' => 'background-color: #fee2e2; color: #7f1d1d; border-color: #ef4444;',
                                'best_practice' => 'background-color: #dcfce7; color: #14532d; border-color: #22c55e;',
                                'lesson' => 'background-color: #fef9c3; color: #713f12; border-color: #ca8a04;',
                                default => 'background-color: #f3f4f6; color: #000000; border-color: #000000;',
                            };
                        @endphp
                        <div class="flex items-center justify-between gap-2 border-b-2 border-black pb-3">
                            <span class="px-2.5 py-0.5 text-[11px] font-heading font-black uppercase border-2 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]"
                                  style="{{ $badgeStyle }}">
                                {{ $mem->type->label() }}
                            </span>

                            <button type="button"
                                    wire:click="clearSelection"
                                    class="w-6 h-6 rounded-none border border-black font-mono text-xs font-bold hover:bg-black hover:text-white flex items-center justify-center">
                                ✕
                            </button>
                        </div>

                        <div>
                            <span class="text-[10px] font-mono uppercase text-gray-400 block mb-1">Título da Memória</span>
                            <h2 class="font-heading font-black text-base text-black leading-snug m-0">
                                {{ $mem->title }}
                            </h2>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-2">
                            <div class="bg-gray-50 neo-border-sm p-2">
                                <span class="text-[9px] font-mono uppercase text-gray-500 block">Stack</span>
                                <span class="font-mono text-xs font-bold text-black truncate block mt-0.5">
                                    {{ $mem->stack ?: 'Geral' }}
                                </span>
                            </div>
                            <div class="bg-gray-50 neo-border-sm p-2">
                                <span class="text-[9px] font-mono uppercase text-gray-500 block">Ocorrências</span>
                                <span class="font-heading text-lg font-black text-neo-magenta block mt-0.5 leading-none">
                                    {{ $mem->recurrence_count }}x
                                </span>
                            </div>
                        </div>

                        @if($mem->description)
                            <div class="pt-2">
                                <span class="text-[10px] font-mono uppercase text-gray-400 block mb-1">Resumo Causal</span>
                                <p class="font-sans text-xs text-gray-700 bg-gray-50 p-2.5 neo-border-sm m-0 leading-relaxed max-h-36 overflow-y-auto">
                                    {{ Str::limit($mem->description, 280) }}
                                </p>
                            </div>
                        @endif

                    {{-- Nó de Tecnologia / Hub --}}
                    @else
                        <div class="flex items-center justify-between gap-2 border-b-2 border-black pb-3">
                            <span class="px-2.5 py-0.5 text-[11px] font-heading font-black uppercase border-2 border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]"
                                  style="background-color: #ccfbf1; color: #134e4a;">
                                HUB TECNOLÓGICO
                            </span>

                            <button type="button"
                                    wire:click="clearSelection"
                                    class="w-6 h-6 rounded-none border border-black font-mono text-xs font-bold hover:bg-black hover:text-white flex items-center justify-center">
                                ✕
                            </button>
                        </div>

                        <div>
                            <span class="text-[10px] font-mono uppercase text-gray-400 block mb-1">Tecnologia / Conceito</span>
                            <h2 class="font-heading font-black text-xl text-black leading-snug m-0">
                                ⚡ {{ $selectedNode->label }}
                            </h2>
                        </div>

                        <div class="pt-2">
                            <span class="text-[10px] font-mono uppercase text-gray-500 block mb-2">Memórias Conectadas ({{ $selectedNode->incomingEdges->count() }})</span>
                            <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                                @forelse($selectedNode->incomingEdges as $inEdge)
                                    @if($inEdge->source?->memory)
                                        <a href="{{ route('memories.show', $inEdge->source->memory) }}" wire:navigate
                                           class="block bg-gray-50 hover:bg-neo-yellow/20 p-2 neo-border-sm no-underline text-black group transition-colors">
                                            <span class="text-[9px] font-mono uppercase text-gray-500 block">
                                                {{ $inEdge->relation_type->value }}
                                            </span>
                                            <p class="font-heading text-xs font-bold group-hover:text-neo-magenta line-clamp-2 m-0 mt-0.5">
                                                {{ $inEdge->source->memory->title }}
                                            </p>
                                        </a>
                                    @endif
                                @empty
                                    <p class="text-xs font-mono text-gray-400">Nenhuma memória vinculada diretamente.</p>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Ações no Rodapé do Drawer --}}
                <div class="space-y-2 pt-4 border-t-2 border-black mt-4">
                    @if($selectedNode->memory)
                        <button type="button"
                                wire:click="setFocus('{{ $selectedNode->memory->id }}')"
                                class="w-full btn-neo bg-neo-yellow neo-border-sm text-black py-2 font-heading font-black text-xs hover:bg-black hover:text-white transition-colors flex items-center justify-center gap-2">
                            <span>🎯 Focar Grafo Aqui (2 Saltos)</span>
                        </button>

                        <a href="{{ route('memories.show', $selectedNode->memory) }}"
                           wire:navigate
                           class="w-full btn-neo bg-neo-magenta neo-border-sm text-white py-2 font-heading font-black text-xs hover:bg-neo-teal hover:text-black transition-colors flex items-center justify-center gap-2 no-underline">
                            <span>Abrir Detalhe Completo ↗</span>
                        </a>
                    @else
                        <button type="button"
                                wire:click="$set('stack', '{{ $selectedNode->label }}')"
                                class="w-full btn-neo bg-neo-teal neo-border-sm text-black py-2 font-heading font-black text-xs hover:bg-black hover:text-white transition-colors flex items-center justify-center gap-2">
                            <span>Filtrar por esta Stack</span>
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Script Alpine.js para Inicializar, Controlar e Redimensionar o Vis-Network --}}
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

                    window.addEventListener('resize', () => {
                        if (this.network) {
                            this.network.redraw();
                            this.network.fit();
                        }
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
                                gravitationalConstant: -140, // Repulsão maior para espalhar bem os nós
                                centralGravity: 0.008,       // Gravidade suave mantendo coeso
                                springLength: 150,           // Distância confortável entre nós conectados
                                springConstant: 0.05,
                                damping: 0.45,
                                avoidOverlap: 1.0            // Evita colisão entre caixas e labels
                            },
                            stabilization: {
                                iterations: 120,
                                updateInterval: 25,
                                fit: true
                            }
                        }
                    };

                    this.network = new vis.Network(container, data, options);

                    // Ajusta o enquadramento ao concluir a estabilização da física
                    this.network.once('stabilizationIterationsDone', () => {
                        this.network.fit({ animation: { duration: 500, easingFunction: 'easeInOutQuad' } });
                    });

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

                    this.network.fit({ animation: { duration: 500, easingFunction: 'easeInOutQuad' } });
                },

                zoomIn() {
                    if (!this.network) return;
                    const scale = this.network.getScale();
                    this.network.moveTo({ scale: scale * 1.35, animation: { duration: 250 } });
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
