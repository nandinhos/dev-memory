<div class="animate-fade-in-up">
    <x-neo.page-header titulo="{{ $memoryId ? 'EDITAR' : 'NOVA' }}" subtitulo="Memória Técnica">
        <x-slot:breadcrumb>
            <nav class="flex items-center gap-2 text-sm font-mono">
                <a href="{{ route('memories.index') }}" class="hover:text-neo-magenta transition-colors">Lista</a>
                <span class="text-gray-400">/</span>
                <span class="font-bold">{{ $memoryId ? 'Editar' : 'Nova' }}</span>
            </nav>
        </x-slot:breadcrumb>
    </x-neo.page-header>

    <div class="max-w-3xl mx-auto">
        <div class="card-neo bg-neo-white neo-border shadow-neo p-6">
            @if(session()->has('success'))
                <x-neo.alert tipo="sucesso" class="mb-6">
                    {{ session('success') }}
                </x-neo.alert>
            @endif

            <form wire:submit.prevent="save" class="space-y-6">
                <x-neo.input 
                    id="title"
                    rotulo="Título"
                    placeholder="Ex: Erro CSRF Token inválido"
                    wire:model="title"
                    :erro="$errors->first('title')"
                />

                <x-neo.textarea 
                    id="description"
                    rotulo="Descrição"
                    placeholder="Descreva o problema, solução e contexto..."
                    rows="5"
                    wire:model="description"
                    :erro="$errors->first('description')"
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-neo.select 
                        id="type"
                        rotulo="Tipo"
                        wire:model="type"
                        :erro="$errors->first('type')"
                    >
                        <option value="error">Erro</option>
                        <option value="lesson">Lição Aprendida</option>
                        <option value="best_practice">Boa Prática</option>
                    </x-neo.select>

                    <x-neo.input 
                        id="stack"
                        rotulo="Stack / Tecnologia"
                        placeholder="Laravel, PHP, Docker..."
                        wire:model="stack"
                    />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-neo.select 
                        id="scope"
                        rotulo="Escopo"
                        wire:model="scope"
                        :erro="$errors->first('scope')"
                    >
                        <option value="project">Projeto</option>
                        <option value="global">Global</option>
                    </x-neo.select>

                    <x-neo.select 
                        id="validation_status"
                        rotulo="Status de Validação"
                        wire:model="validation_status"
                        :erro="$errors->first('validation_status')"
                    >
                        <option value="pending">Pendente</option>
                        <option value="validated">Validado</option>
                        <option value="rejected">Rejeitado</option>
                    </x-neo.select>
                </div>

                <x-neo.input 
                    id="official_reference"
                    tipo="url"
                    rotulo="Referência Oficial (URL)"
                    placeholder="https://laravel.com/docs/..."
                    wire:model="official_reference"
                    :erro="$errors->first('official_reference')"
                />

                <div class="flex gap-4 pt-6 border-t-2 border-black">
                    <button type="submit" 
                            class="btn-neo bg-neo-teal neo-border-sm shadow-neo px-6 py-3 font-heading text-sm hover:bg-neo-yellow transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Salvar Memória
                    </button>
                    <a href="{{ route('memories.index') }}" 
                       class="btn-neo bg-neo-white neo-border-sm shadow-neo px-6 py-3 font-heading text-sm hover:bg-gray-100 transition-colors">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
