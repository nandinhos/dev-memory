<div class="animate-fade-in-up">
    @if (session()->has('success'))
        <x-neo.alert tipo="sucesso" class="mb-4">
            {{ session('success') }}
        </x-neo.alert>
    @endif

    <div class="card-neo bg-neo-white neo-border shadow-neo p-6 max-w-5xl mx-auto w-full">
        <form wire:submit.prevent="save" class="space-y-5">
            <div class="grid md:grid-cols-2 gap-6">
                {{-- Coluna esquerda: identidade da memória --}}
                <div class="flex flex-col gap-4">
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
                        rows="8"
                        wire:model="description"
                        :erro="$errors->first('description')"
                    />
                </div>

                {{-- Coluna direita: metadados --}}
                <div class="space-y-4">
                    <x-neo.select
                        id="type"
                        rotulo="Tipo"
                        wire:model="type"
                        :erro="$errors->first('type')"
                    >
                        <option value="error">Erro</option>
                        <option value="lesson">Lição Aprendida</option>
                        <option value="best_practice">Boa Prática</option>
                        <option value="workaround">Workaround</option>
                        <option value="architecture_decision">Decisão Arquitetural</option>
                        <option value="anti_pattern">Antipadrão</option>
                    </x-neo.select>

                    <x-neo.input
                        id="stack"
                        rotulo="Stack / Tecnologia"
                        placeholder="Laravel, PHP, Docker..."
                        wire:model="stack"
                    />

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

                    <x-neo.input
                        id="official_reference"
                        tipo="url"
                        rotulo="Referência Oficial (URL)"
                        placeholder="https://laravel.com/docs/..."
                        wire:model="official_reference"
                        :erro="$errors->first('official_reference')"
                    />
                </div>
            </div>

            <div class="flex gap-4 pt-4 border-t-2 border-black">
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
