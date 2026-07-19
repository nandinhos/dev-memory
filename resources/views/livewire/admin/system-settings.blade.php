<div class="animate-fade-in-up">
    <p class="text-sm text-gray-600 font-mono mb-6">Providers, chaves de API e conexões do hub. Painel sobrepõe o <code class="bg-black/5 px-1">.env</code>; limpar volta ao env.</p>

    @php
        $badge = function (string $source) {
            return match ($source) {
                'painel' => ['bg-neo-teal', 'PAINEL'],
                'env' => ['bg-neo-yellow', 'ENV'],
                default => ['bg-neo-magenta text-white', 'NÃO CONFIGURADA'],
            };
        };
    @endphp

    <form wire:submit="save">
        {{-- ===================== MOTOR DE CURADORIA ===================== --}}
        <div class="bg-neo-white neo-border shadow-neo p-6 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                <h2 class="font-heading text-lg m-0">Motor de curadoria</h2>
                <div class="flex gap-2">
                    @php [$cor, $txt] = $badge($sources['curation.api_key'] ?? 'nenhuma'); @endphp
                    <span class="{{ $cor }} border-2 border-black px-2 py-0.5 text-[10px] font-black uppercase">CHAVE: {{ $txt }}</span>
                </div>
            </div>
            <p class="text-xs font-mono text-gray-500 mb-4">Qualquer endpoint Anthropic-compatible (MiniMax, Anthropic, proxy local). É quem transforma capturas em memórias curadas.</p>

            <div class="grid md:grid-cols-2 gap-4">
                <x-neo.input rotulo="BASE URL" wire:model="curationBaseUrl" placeholder="https://api.minimax.io/anthropic" :erro="$errors->first('curationBaseUrl')" />
                <x-neo.input rotulo="MODELO" wire:model="curationModel" placeholder="MiniMax-M2.5" :erro="$errors->first('curationModel')" />
            </div>

            <div class="mt-4">
                <x-neo.input tipo="password" rotulo="CHAVE DE API (write-only — nunca é re-exibida)" wire:model="curationApiKey"
                    placeholder="{{ ($sources['curation.api_key'] ?? '') === 'nenhuma' ? 'cole a chave do provider' : '•••••••• configurada — preencha só para substituir' }}"
                    :erro="$errors->first('curationApiKey')" autocomplete="off" />
            </div>

            <div class="flex flex-wrap gap-2 mt-4">
                <x-neo.button variante="contorno" tamanho="sm" tipo="button" wire:click="testCuration" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="testCuration">TESTAR CONEXÃO</span>
                    <span wire:loading wire:target="testCuration">TESTANDO…</span>
                </x-neo.button>
                @if (($sources['curation.api_key'] ?? '') === 'painel')
                    <x-neo.button variante="destrutivo" tamanho="sm" tipo="button" wire:click="removeKey('curation.api_key')"
                        wire:confirm="Remover a chave do painel? O env volta a valer.">REMOVER CHAVE DO PAINEL</x-neo.button>
                @endif
            </div>
        </div>

        {{-- ===================== CONTEXT7 ===================== --}}
        <div class="bg-neo-white neo-border shadow-neo p-6 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                <h2 class="font-heading text-lg m-0">Context7 — validação documental</h2>
                <div class="flex gap-2">
                    @php [$cor7, $txt7] = $badge($sources['context7.api_key'] ?? 'nenhuma'); @endphp
                    <span class="{{ $cor7 }} border-2 border-black px-2 py-0.5 text-[10px] font-black uppercase">CHAVE: {{ $txt7 }}</span>
                </div>
            </div>
            <p class="text-xs font-mono text-gray-500 mb-4">Valida memórias contra a documentação oficial. <strong>Funciona sem chave</strong> (keyless) — a chave só aumenta os limites de uso. Gere a sua em <a href="https://context7.com/dashboard" target="_blank" rel="noopener noreferrer" class="text-neo-magenta underline">context7.com/dashboard</a>.</p>

            <div class="grid md:grid-cols-2 gap-4">
                <x-neo.input rotulo="BASE URL" wire:model="context7BaseUrl" placeholder="https://context7.com/api/v1" :erro="$errors->first('context7BaseUrl')" />
                <x-neo.input tipo="password" rotulo="CHAVE DE API (opcional, write-only)" wire:model="context7ApiKey"
                    placeholder="{{ ($sources['context7.api_key'] ?? '') === 'nenhuma' ? 'vazio = modo keyless' : '•••••••• configurada — preencha só para substituir' }}"
                    :erro="$errors->first('context7ApiKey')" autocomplete="off" />
            </div>

            <div class="flex flex-wrap gap-2 mt-4">
                <x-neo.button variante="contorno" tamanho="sm" tipo="button" wire:click="testContext7" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="testContext7">TESTAR CONEXÃO</span>
                    <span wire:loading wire:target="testContext7">TESTANDO…</span>
                </x-neo.button>
                @if (($sources['context7.api_key'] ?? '') === 'painel')
                    <x-neo.button variante="destrutivo" tamanho="sm" tipo="button" wire:click="removeKey('context7.api_key')"
                        wire:confirm="Remover a chave do painel? O env volta a valer.">REMOVER CHAVE DO PAINEL</x-neo.button>
                @endif
            </div>
        </div>

        {{-- ===================== AÇÕES ===================== --}}
        <div class="flex flex-wrap gap-3 mb-6">
            <x-neo.button tipo="submit" variante="sucesso" tamanho="md" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">SALVAR CONFIGURAÇÕES</span>
                <span wire:loading wire:target="save">SALVANDO…</span>
            </x-neo.button>
            <x-neo.button variante="contorno" tamanho="md" tipo="button" wire:click="restoreEnv"
                wire:confirm="Limpar TODAS as configurações do painel? Tudo volta a valer pelo .env.">RESTAURAR ENV</x-neo.button>
        </div>
    </form>

    {{-- ===================== COMO A SEGURANÇA FUNCIONA ===================== --}}
    <div class="bg-neo-white neo-border shadow-neo p-6 mb-6">
        <h2 class="font-heading text-lg m-0 mb-3">Como as chaves são protegidas</h2>
        <ul class="space-y-2 text-sm font-mono">
            <li class="flex gap-2"><span class="text-neo-green font-black">&#10003;</span><span><strong>Criptografia at-rest</strong> — toda chave salva aqui é criptografada com a <code class="bg-black/5 px-1">APP_KEY</code> antes de tocar o banco (cast <code class="bg-black/5 px-1">encrypted</code>).</span></li>
            <li class="flex gap-2"><span class="text-neo-green font-black">&#10003;</span><span><strong>Write-only</strong> — a chave nunca é re-exibida nem devolvida ao navegador; só o status (painel/env) aparece.</span></li>
            <li class="flex gap-2"><span class="text-neo-green font-black">&#10003;</span><span><strong>Painel sobrepõe env</strong> — sem valor no painel, o <code class="bg-black/5 px-1">.env</code> da VPS continua valendo; remover a chave volta ao env na hora.</span></li>
            <li class="flex gap-2"><span class="text-neo-green font-black">&#10003;</span><span><strong>Workers sincronizados</strong> — salvar dispara <code class="bg-black/5 px-1">queue:restart</code> automaticamente; a fila nunca roda com chave antiga.</span></li>
        </ul>
    </div>

    {{-- ===================== ONBOARDING MCP ===================== --}}
    <div class="bg-neo-white neo-border shadow-neo p-6">
        <h2 class="font-heading text-lg m-0 mb-3">Conectar um projeto ao hub (MCP)</h2>
        <ol class="space-y-2 text-sm font-mono list-none">
            <li class="flex gap-2"><span class="bg-black text-white px-1.5 font-black">1</span><span>Gere um token em <a href="{{ route('admin.tokens') }}" wire:navigate class="text-neo-magenta underline font-bold">TOKENS MCP</a> — ele é exibido <strong>uma única vez</strong> (só o hash fica no banco).</span></li>
            <li class="flex gap-2"><span class="bg-black text-white px-1.5 font-black">2</span><span>Na máquina cliente, guarde-o fora do repositório: <code class="bg-black/5 px-1">read -rsp "token: " T && printf '%s' "$T" > ~/.dev-memory-token && chmod 600 ~/.dev-memory-token</code></span></li>
            <li class="flex gap-2"><span class="bg-black text-white px-1.5 font-black">3</span><span>Exporte no shell: <code class="bg-black/5 px-1">export DEV_MEMORY_TOKEN="$(cat ~/.dev-memory-token)"</code> e referencie <code class="bg-black/5 px-1">${{ '{' }}DEV_MEMORY_TOKEN{{ '}' }}</code> no <code class="bg-black/5 px-1">.mcp.json</code> — o arquivo fica versionável sem segredo.</span></li>
            <li class="flex gap-2"><span class="bg-black text-white px-1.5 font-black">4</span><span>Reinicie o cliente (Claude Code carrega MCP no start) e valide com <code class="bg-black/5 px-1">tools/list</code>.</span></li>
        </ol>
        <p class="text-xs font-mono text-gray-500 mt-3">O JSON completo de conexão está na página <a href="{{ route('admin.tokens') }}" wire:navigate class="text-neo-magenta underline">TOKENS MCP</a> · catálogo de tools em <code class="bg-black/5 px-1">docs/mcp-tools.md</code>.</p>
    </div>
</div>
