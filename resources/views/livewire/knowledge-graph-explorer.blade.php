<div class="flex-1 w-full h-full flex flex-col overflow-hidden bg-[#faf6ee] relative"
     x-data="knowledgeGraphExplorer({
         nodesData: @js($graphData['nodes']),
         edgesData: @js($graphData['edges']),
         selectedId: @entangle('selectedNodeId')
     })"
     x-init="initGraph()">

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
        {{-- Canvas do Vis-Network protegido com wire:ignore contra DOM morphing do Livewire --}}
        <div id="vis-network-container"
             wire:ignore
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

        {{-- Painel Lateral Compacto (Drawer) de Inspeção --}}
        @if($selectedNode)
            <div class="w-80 md:w-96 bg-white border-l-4 border-black p-5 flex flex-col justify-between shrink-0 shadow-[-6px_0px_0px_0px_rgba(0,0,0,1)] z-30 h-full overflow-hidden">
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

                    {{-- Topo da Memória --}}
                    <div class="shrink-0 space-y-3 pb-3 border-b-2 border-black">
                        <div class="flex items-center justify-between gap-2">
                            <span class="px-2.5 py-0.5 text-[11px] font-heading font-black uppercase border-2 shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]"
                                  style="{{ $badgeStyle }}">
                                {{ $mem->type->label() }}
                            </span>

                            <button type="button"
                                    wire:click="clearSelection"
                                    class="w-7 h-7 rounded-none border-2 border-black font-mono text-xs font-black hover:bg-black hover:text-white flex items-center justify-center transition-colors">
                                ✕
                            </button>
                        </div>

                        <div>
                            <span class="text-[10px] font-mono uppercase text-gray-400 block mb-0.5">Título da Memória</span>
                            <h2 class="font-heading font-black text-base text-black leading-snug m-0">
                                {{ $mem->title }}
                            </h2>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
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
                    </div>

                    {{-- Resumo Causal (Preenche todo o espaço vertical útil) --}}
                    <div class="flex-1 min-h-0 flex flex-col my-3 overflow-hidden">
                        <span class="text-[10px] font-mono uppercase text-gray-500 block mb-1 shrink-0">// RESUMO CAUSAL</span>
                        <div class="flex-1 overflow-y-auto bg-gray-50 p-3 neo-border-sm text-xs text-gray-800 leading-relaxed font-sans">
                            {{ $mem->description ?: 'Sem descrição detalhada registrada para esta memória.' }}
                        </div>
                    </div>

                    {{-- Ações no Rodapé da Memória --}}
                    <div class="shrink-0 space-y-2 pt-3 border-t-2 border-black bg-white">
                        <button type="button"
                                wire:click="setFocus('{{ $selectedNode->memory->id }}')"
                                class="w-full btn-neo bg-neo-yellow neo-border-sm text-black py-2.5 font-heading font-black text-xs hover:bg-black hover:text-white transition-colors flex items-center justify-center gap-2">
                            <span>🎯 Focar Grafo Aqui (2 Saltos)</span>
                        </button>

                        <a href="{{ route('memories.show', $selectedNode->memory) }}"
                           wire:navigate
                           class="w-full btn-neo bg-neo-magenta neo-border-sm text-white py-2.5 font-heading font-black text-xs hover:bg-neo-teal hover:text-black transition-colors flex items-center justify-center gap-2 no-underline">
                            <span>Abrir Detalhe Completo ↗</span>
                        </a>
                    </div>

                {{-- Nó de Tecnologia / Hub --}}
                @else
                    {{-- Topo do Hub Tecnológico --}}
                    <div class="shrink-0 space-y-2 pb-3 border-b-2 border-black">
                        <div class="flex items-center justify-between gap-2">
                            <span class="px-2.5 py-0.5 text-[11px] font-heading font-black uppercase border-2 border-black shadow-[2px_2px_0px_0px_rgba(0,0,0,1)]"
                                  style="background-color: #ccfbf1; color: #134e4a;">
                                HUB TECNOLÓGICO
                            </span>

                            <button type="button"
                                    wire:click="clearSelection"
                                    class="w-7 h-7 rounded-none border-2 border-black font-mono text-xs font-black hover:bg-black hover:text-white flex items-center justify-center transition-colors">
                                ✕
                            </button>
                        </div>

                        <div>
                            <span class="text-[10px] font-mono uppercase text-gray-400 block mb-0.5">Tecnologia / Hub Causal</span>
                            <h2 class="font-heading font-black text-xl text-black leading-snug m-0 flex items-center gap-1.5">
                                <span class="text-neo-magenta">⚡</span>
                                <span>{{ $selectedNode->label }}</span>
                            </h2>
                        </div>
                    </div>

                    {{-- Lista Verticalmente Expandida de Memórias (Preenche 100% da área demarcada em vermelho) --}}
                    <div class="flex-1 min-h-0 flex flex-col my-3 overflow-hidden">
                        <div class="flex items-center justify-between gap-2 mb-2 pb-1 border-b border-black/20 shrink-0">
                            <span class="text-[10px] font-mono font-black uppercase text-gray-600 tracking-wider">
                                // MEMÓRIAS CONECTADAS ({{ $selectedNode->incomingEdges->count() }})
                            </span>
                            <span class="text-[10px] font-mono text-gray-400">
                                scroll ativo
                            </span>
                        </div>

                        <div class="flex-1 overflow-y-auto pr-1 space-y-2">
                            @forelse($selectedNode->incomingEdges as $inEdge)
                                @if($inEdge->source?->memory)
                                    @php
                                        $relMem = $inEdge->source->memory;
                                        $typeColor = match($relMem->type->value) {
                                            'error' => 'bg-neo-magenta text-white',
                                            'best_practice' => 'bg-neo-green text-black',
                                            'lesson' => 'bg-neo-yellow text-black',
                                            default => 'bg-gray-200 text-black',
                                        };
                                    @endphp
                                    <a href="{{ route('memories.show', $relMem) }}" wire:navigate
                                       class="block bg-white hover:bg-neo-yellow/20 p-2.5 neo-border-sm no-underline text-black group transition-all hover:translate-x-1 shadow-sm">
                                        <div class="flex items-center justify-between gap-2 mb-1">
                                            <span class="bg-black text-white px-1.5 py-0.5 text-[9px] font-mono uppercase font-bold">
                                                {{ $inEdge->relation_type->value }}
                                            </span>
                                            <span class="{{ $typeColor }} px-1.5 py-0.5 text-[9px] font-heading font-black uppercase border border-black">
                                                {{ $relMem->type->label() }}
                                            </span>
                                        </div>
                                        <p class="font-heading text-xs font-bold text-black group-hover:text-neo-magenta line-clamp-2 m-0 leading-tight">
                                            {{ $relMem->title }}
                                        </p>
                                    </a>
                                @endif
                            @empty
                                <div class="text-center py-8 text-gray-400 font-mono text-xs border border-dashed border-gray-300">
                                    Nenhuma memória vinculada diretamente a este hub.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Ações no Rodapé do Hub Tecnológico --}}
                    <div class="shrink-0 pt-3 border-t-2 border-black bg-white">
                        <button type="button"
                                wire:click="$set('stack', '{{ $selectedNode->label }}')"
                                class="w-full btn-neo bg-neo-teal neo-border-sm text-black py-2.5 font-heading font-black text-xs hover:bg-black hover:text-white transition-colors flex items-center justify-center gap-2">
                            <span>Filtrar Grafo por esta Stack</span>
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Payload de Dados Reativo: o Livewire atualiza este elemento a cada request --}}
    <div id="graph-data-payload"
         x-ref="graphDataPayload"
         class="hidden"
         data-nodes="{{ json_encode($graphData['nodes']) }}"
         data-edges="{{ json_encode($graphData['edges']) }}">
    </div>

    {{-- Script Alpine.js com Instância de Vis-Network Fora do Proxy Reativo --}}
    <script>
        (function() {
            // Instâncias brutas isoladas do Proxy do Alpine para compatibilidade com WeakMap/Private Fields do Vis-Network
            let rawNetwork = null;
            let rawNodesDataSet = null;
            let rawEdgesDataSet = null;

            function applyGraphUpdate(nodes, edges) {
                if (!Array.isArray(nodes) || !Array.isArray(edges)) {
                    return;
                }

                if (!rawNetwork) {
                    return;
                }

                rawNodesDataSet = new vis.DataSet(nodes);
                rawEdgesDataSet = new vis.DataSet(edges);

                // Chama setData na instância nativa (não envolvida por Proxy)
                rawNetwork.setData({
                    nodes: rawNodesDataSet,
                    edges: rawEdgesDataSet
                });

                rawNetwork.fit({ animation: { duration: 400, easingFunction: 'easeInOutQuad' } });
            }

            function syncFromPayloadElement() {
                const el = document.getElementById('graph-data-payload');
                if (!el) return;

                try {
                    const rawNodes = el.getAttribute('data-nodes');
                    const rawEdges = el.getAttribute('data-edges');
                    if (rawNodes && rawEdges) {
                        const nodes = JSON.parse(rawNodes);
                        const edges = JSON.parse(rawEdges);
                        applyGraphUpdate(nodes, edges);
                    }
                } catch (e) {
                    console.error('Erro ao sincronizar dados do grafo:', e);
                }
            }

            function registerExplorer() {
                if (window.Alpine && !window.knowledgeGraphRegistered) {
                    window.knowledgeGraphRegistered = true;

                    Alpine.data('knowledgeGraphExplorer', (config) => ({
                        observer: null,

                        initGraph() {
                            this.$nextTick(() => {
                                this.mountNetwork(config.nodesData, config.edgesData);
                                this.setupObserver();
                            });

                            window.addEventListener('resize', () => {
                                if (rawNetwork) {
                                    rawNetwork.redraw();
                                    rawNetwork.fit();
                                }
                            });
                        },

                        setupObserver() {
                            const payloadEl = this.$refs.graphDataPayload || document.getElementById('graph-data-payload');
                            if (!payloadEl) return;

                            this.observer = new MutationObserver(() => {
                                syncFromPayloadElement();
                            });

                            this.observer.observe(payloadEl, {
                                attributes: true,
                                attributeFilter: ['data-nodes', 'data-edges']
                            });
                        },

                        mountNetwork(nodes, edges) {
                            const container = this.$refs.graphContainer || document.getElementById('vis-network-container');
                            if (!container) return;

                            rawNodesDataSet = new vis.DataSet(nodes);
                            rawEdgesDataSet = new vis.DataSet(edges);

                            const data = {
                                nodes: rawNodesDataSet,
                                edges: rawEdgesDataSet
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
                                        gravitationalConstant: -140,
                                        centralGravity: 0.008,
                                        springLength: 150,
                                        springConstant: 0.05,
                                        damping: 0.45,
                                        avoidOverlap: 1.0
                                    },
                                    stabilization: {
                                        iterations: 120,
                                        updateInterval: 25,
                                        fit: true
                                    }
                                }
                            };

                            rawNetwork = new vis.Network(container, data, options);

                            rawNetwork.once('stabilizationIterationsDone', () => {
                                rawNetwork.fit({ animation: { duration: 500, easingFunction: 'easeInOutQuad' } });
                            });

                            rawNetwork.on('click', (params) => {
                                if (params.nodes.length > 0) {
                                    const nodeId = params.nodes[0];
                                    this.$wire.selectNode(nodeId);
                                } else if (params.edges.length === 0) {
                                    this.$wire.clearSelection();
                                }
                            });
                        },

                        zoomIn() {
                            if (!rawNetwork) return;
                            const scale = rawNetwork.getScale();
                            rawNetwork.moveTo({ scale: scale * 1.35, animation: { duration: 250 } });
                        },

                        zoomOut() {
                            if (!rawNetwork) return;
                            const scale = rawNetwork.getScale();
                            rawNetwork.moveTo({ scale: scale * 0.7, animation: { duration: 250 } });
                        },

                        fitView() {
                            if (!rawNetwork) return;
                            rawNetwork.fit({ animation: { duration: 400, easingFunction: 'easeInOutQuad' } });
                        }
                    }));
                }
            }

            if (window.Alpine) {
                registerExplorer();
            } else {
                document.addEventListener('alpine:init', registerExplorer);
            }

            // Hook do Livewire 3 para sincronização garantida pós-render
            document.addEventListener('livewire:initialized', () => {
                if (window.Livewire) {
                    Livewire.hook('commit', ({ succeed }) => {
                        succeed(() => {
                            setTimeout(() => {
                                syncFromPayloadElement();
                            }, 30);
                        });
                    });
                }
            });
        })();
    </script>
</div>
