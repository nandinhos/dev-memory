<div class="animate-fade-in-up" x-data="{ confirmingRevoke: false, tokenToRevokeId: null, tokenToRevokeName: '' }">
    <p class="text-sm text-gray-600 font-mono mb-6">Acesso remoto de outros projetos via MCP.</p>

    <div class="bg-neo-white neo-border shadow-neo p-6 mb-6">
        <h2 class="font-heading text-lg m-0 mb-3">Novo token</h2>
        <form wire:submit="create" class="flex flex-col sm:flex-row gap-3 sm:items-end">
            <div class="flex-1">
                <x-neo.input rotulo="NOME (ex.: projeto-eventos-control)" wire:model="name" placeholder="nome do projeto/máquina" :erro="$errors->first('name')" />
            </div>
            <div>
                <label class="block text-xs font-bold font-mono uppercase tracking-wider mb-1">Validade</label>
                <select wire:model="expiresInDays" class="input-neo neo-border-sm shadow-neo-sm px-3 py-2 outline-none font-mono text-sm cursor-pointer">
                    <option value="0">Sem expiração</option>
                    <option value="30">30 dias</option>
                    <option value="90">90 dias</option>
                    <option value="365">1 ano</option>
                </select>
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
                    @if ($token->expires_at)
                        · <span class="{{ $token->isExpired() ? 'text-neo-magenta font-bold' : '' }}">{{ $token->isExpired() ? 'EXPIRADO' : 'expira '.$token->expires_at->format('d/m/Y') }}</span>
                    @else
                        · sem expiração
                    @endif
                </div>
            </div>
            <x-neo.button type="button" variante="destrutivo" tamanho="sm"
                          @click="tokenToRevokeId = '{{ $token->id }}'; tokenToRevokeName = '{{ addslashes($token->name) }}'; confirmingRevoke = true">REVOGAR</x-neo.button>
        </div>
    @empty
        <x-neo.empty-state titulo="Nenhum token" mensagem="Gere um token para conectar outros projetos a este hub via MCP." />
    @endforelse

    <x-neo.modal model="confirmingRevoke" titulo="REVOGAR TOKEN MCP" variante="destrutivo">
        <div class="space-y-4">
            <div class="bg-neo-salmon/20 neo-border-sm p-4 font-mono text-sm">
                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest block mb-1.5">// CONFIRMAÇÃO DE REVOGAÇÃO</span>
                <p class="text-black text-sm leading-relaxed mb-2">
                    Deseja realmente revogar o token:
                </p>
                <div class="bg-neo-yellow neo-border-sm px-3 py-1.5 font-mono font-bold text-black break-all shadow-neo-sm text-sm inline-block">
                    <span x-text="tokenToRevokeName"></span>
                </div>
                <p class="text-xs text-gray-700 mt-3 font-mono leading-normal">
                    ⚠️ <strong>Atenção:</strong> Todas as conexões, terminais ou agentes de IA utilizando este token perderão imediatamente o acesso às tools MCP deste hub.
                </p>
            </div>
        </div>

        <x-slot:actions>
            <x-neo.button type="button" variante="contorno" tamanho="md" @click="confirmingRevoke = false">
                CANCELAR
            </x-neo.button>
            <x-neo.button type="button" variante="destrutivo" tamanho="md" 
                          @click="$wire.revoke(tokenToRevokeId); confirmingRevoke = false">
                CONFIRMAR REVOGAÇÃO
            </x-neo.button>
        </x-slot:actions>
    </x-neo.modal>
</div>
