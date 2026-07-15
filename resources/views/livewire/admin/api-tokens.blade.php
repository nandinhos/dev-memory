<div class="animate-fade-in-up">
    <x-neo.page-header titulo="TOKENS_MCP" subtitulo="acesso remoto de outros projetos via MCP" />

    <div class="bg-neo-white neo-border shadow-neo p-6 mb-6">
        <h2 class="font-heading text-lg m-0 mb-3">Novo token</h2>
        <form wire:submit="create" class="flex flex-col sm:flex-row gap-3 sm:items-end">
            <div class="flex-1">
                <x-neo.input rotulo="NOME (ex.: projeto-eventos-control)" wire:model="name" placeholder="nome do projeto/máquina" :erro="$errors->first('name')" />
            </div>
            <x-neo.button tipo="submit" variante="sucesso" tamanho="md">GERAR TOKEN</x-neo.button>
        </form>

        @if ($plaintext)
            <div class="mt-4 bg-neo-yellow neo-border-sm shadow-neo p-4">
                <p class="text-xs font-mono font-bold uppercase mb-2">Copie agora — não será exibido novamente</p>
                <code class="block bg-black text-neo-green font-mono text-sm p-3 break-all select-all">{{ $plaintext }}</code>
            </div>
        @endif
    </div>

    <div class="bg-neo-white neo-border shadow-neo p-6 mb-6">
        <h2 class="font-heading text-lg m-0 mb-2">Como conectar</h2>
        <p class="text-sm text-gray-700 mb-2">Configure o cliente MCP do outro projeto apontando para este hub:</p>
        <pre class="text-xs font-mono bg-neo-bg neo-border-sm p-3 overflow-x-auto">{
  "mcpServers": {
    "dev-memory": {
      "type": "http",
      "url": "{{ url('/api/mcp') }}",
      "headers": { "Authorization": "Bearer &lt;SEU_TOKEN&gt;" }
    }
  }
}</pre>
    </div>

    @forelse($tokens as $token)
        <div class="bg-neo-white neo-border shadow-neo p-4 mb-3 flex justify-between items-center gap-4" wire:key="token-{{ $token->id }}">
            <div class="min-w-0">
                <span class="font-heading font-bold">{{ $token->name }}</span>
                <div class="text-xs font-mono text-gray-500">
                    criado {{ $token->created_at->format('d/m/Y') }}
                    · {{ $token->last_used_at ? 'usado '.$token->last_used_at->diffForHumans() : 'nunca usado' }}
                </div>
            </div>
            <x-neo.button variante="destrutivo" tamanho="sm" wire:click="revoke('{{ $token->id }}')"
                          wire:confirm="Revogar este token? Projetos que o usam perderão acesso.">REVOGAR</x-neo.button>
        </div>
    @empty
        <x-neo.empty-state titulo="Nenhum token" mensagem="Gere um token para conectar outros projetos a este hub via MCP." />
    @endforelse
</div>
