<div class="animate-fade-in-up">
    <p class="text-sm text-gray-600 font-mono mb-6">Configurações do seu ambiente, replicáveis em qualquer máquina.</p>

    <div class="bg-neo-white neo-border shadow-neo p-6 mb-6">
        <h2 class="font-heading text-lg m-0 mb-2">Como funciona</h2>
        <p class="text-sm text-gray-700 mb-2">Suba a config de um harness (segredos são redigidos) e replique numa máquina limpa via MCP:</p>
        <pre class="text-xs font-mono bg-neo-bg neo-border-sm p-3 overflow-x-auto"># Nesta máquina — sobe a config local:
php artisan harness:capture-local claude-code

# Em outra máquina (via MCP): harness_provision → o agente instala o plano</pre>
    </div>

    @forelse($profiles as $profile)
        <div class="bg-neo-white neo-border shadow-neo p-5 mb-4" wire:key="hp-{{ $profile->id }}">
            <div class="flex justify-between items-start gap-4">
                <div>
                    <h2 class="font-heading text-xl m-0">{{ $profile->harness->label() }}</h2>
                    <p class="text-xs font-mono text-gray-500 mt-1 mb-0">
                        {{ $profile->name }} · v{{ $profile->version }} · {{ count($profile->files) }} arquivo(s) · atualizado {{ $profile->updated_at->diffForHumans() }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <x-neo.button variante="texto" tamanho="sm" wire:click="toggle('{{ $profile->id }}')">
                        {{ $expandedId === $profile->id ? 'ocultar' : 'ver arquivos' }}
                    </x-neo.button>
                    <x-neo.button variante="destrutivo" tamanho="sm" wire:click="delete('{{ $profile->id }}')"
                                  wire:confirm="Remover este perfil de harness?">REMOVER</x-neo.button>
                </div>
            </div>

            @if ($expandedId === $profile->id)
                <div class="mt-4 border-t-2 border-black/10 pt-3 space-y-3">
                    @foreach ($profile->files as $file)
                        <div>
                            <div class="flex items-center gap-2">
                                <code class="text-xs font-mono font-bold">{{ $file['path'] }}</code>
                                @if (! empty($file['redactions']))
                                    <x-neo.badge variante="magenta">SEGREDOS REDIGIDOS</x-neo.badge>
                                @endif
                            </div>
                            <pre class="text-xs font-mono bg-neo-bg neo-border-sm p-2 overflow-x-auto mt-1 max-h-48">{{ \Illuminate\Support\Str::limit($file['content'], 1200) }}</pre>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <x-neo.empty-state titulo="Nenhum perfil" mensagem="Rode php artisan harness:capture-local para subir sua configuração." />
    @endforelse
</div>
