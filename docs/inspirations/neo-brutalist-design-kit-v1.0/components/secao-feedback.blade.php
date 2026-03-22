{{--
    x-neo.secao-feedback
    Seção de feedback: alertas, barra de progresso (Livewire) e modal de confirmação.
--}}
<section
    id="feedback"
    aria-labelledby="titulo-feedback"
    class="neo-border bg-white p-6 shadow-hard relative scroll-mt-8 animate-fade-in-up"
>
    {{-- Label flutuante --}}
    <div class="section-label bg-neo-purple text-white neo-border px-4 py-1 shadow-hard">
        <h2 id="titulo-feedback" class="font-heading text-xl">Feedback</h2>
    </div>

    <div class="mt-6 space-y-8">

        {{-- ── Toast Stack ── --}}
        <div
            x-data="{
                toasts: [
                    { id: 1, mensagem: 'Mensagem de sucesso', cor: '#22D3EE' },
                    { id: 2, mensagem: 'Mensagem de aviso',   cor: '#fef3c7' },
                    { id: 3, mensagem: 'Mensagem de erro',    cor: '#fca5a5' },
                ],
                remover(id) { this.toasts = this.toasts.filter(t => t.id !== id) }
            }"
            class="animate-fade-in-up animation-delay-100"
        >
            <h3 class="font-bold border-b-4 border-black inline-block mb-4 uppercase font-body text-sm">
                Toast Notifications
            </h3>
            <div
                class="space-y-2"
                role="list"
                aria-label="Notificações empilhadas (toast)"
                aria-live="polite"
            >
                <template x-for="toast in toasts" :key="toast.id">
                    <div
                        :style="{ backgroundColor: toast.cor }"
                        class="neo-border flex items-center justify-between p-3 font-bold shadow-[--shadow-neo] font-body text-sm"
                        role="listitem"
                    >
                        <span x-text="toast.mensagem"></span>
                        <button
                            type="button"
                            @click.stop="remover(toast.id)"
                            class="ml-4 hover:bg-black/10 px-1 transition-colors font-heading icon-hover"
                            :aria-label="'Fechar notificação: ' + toast.mensagem"
                        >✕</button>
                    </div>
                </template>
                <p
                    x-show="toasts.length === 0"
                    class="text-sm text-gray-400 font-body italic"
                >
                    Todas as notificações foram fechadas.
                </p>
            </div>
        </div>



        {{-- ── Alertas — usa x-neo.alert com os 3 tipos --}}
        <div class="animate-fade-in-up animation-delay-200">
            <h3 class="font-bold border-b-4 border-black inline-block mb-4 uppercase font-body text-sm">
                Alertas
            </h3>
            <div class="space-y-3" role="list" aria-label="Tipos de alerta disponíveis">

                {{-- Tipo sucesso: verde + ícone check --}}
                <x-neo.alert tipo="sucesso" role="listitem" class="feedback-banner">
                    SUCESSO: Operação concluída com êxito.
                </x-neo.alert>

                {{-- Tipo aviso: amarelo + ícone triângulo --}}
                <x-neo.alert tipo="aviso" role="listitem" class="feedback-banner animation-delay-100">
                    AVISO: Verifique seus dados antes de prosseguir.
                </x-neo.alert>

                {{-- Tipo erro: magenta + ícone x, aria-live="assertive" --}}
                <x-neo.alert tipo="erro" role="listitem" class="feedback-banner animation-delay-200">
                    ERRO: Falha na operação do sistema.
                </x-neo.alert>

            </div>
        </div>

        {{-- ── Progresso Reativo (Livewire) ── --}}
        <div class="animate-fade-in-up animation-delay-300">
            <h3 class="font-bold border-b-4 border-black inline-block mb-4 uppercase font-body text-sm">
                Progresso (Livewire)
            </h3>
            <livewire:progresso-demo />
        </div>

        {{-- ── Modal — usa x-neo.modal (Alpine.js auto-gerenciado) ── --}}
        <div class="animate-fade-in-up animation-delay-400">
            <h3 class="font-bold border-b-4 border-black inline-block mb-4 uppercase font-body text-sm">
                Modal de Confirmação
            </h3>

            {{-- O componente gerencia todo o estado Alpine internamente.
                 Slot 'gatilho' = o botão que abre o modal.
                 Slot default  = o conteúdo dentro do modal. --}}
            <div class="bg-gray-100 p-4 border-4 border-dashed border-black">
                <x-neo.modal
                    titulo="Confirmar Ação"
                    id="modal-confirmacao"
                    texto-cancelar="Cancelar"
                    texto-confirmar="Confirmar"
                >
                    <x-slot:gatilho>
                        <x-neo.button variante="destrutivo">
                            Abrir Modal
                        </x-neo.button>
                    </x-slot:gatilho>

                    Tem certeza que deseja prosseguir com esta ação?
                    <strong>Esta operação não pode ser desfeita.</strong>
                </x-neo.modal>
            </div>
        </div>

        {{-- ── Estado Vazio — usa x-neo.empty-state ── --}}
        <div>
            <h3 class="font-bold border-b-4 border-black inline-block mb-4 uppercase font-body text-sm">
                Estado Vazio
            </h3>
            {{-- Demonstra o componente com todas as propriedades preenchidas --}}
            <x-neo.empty-state
                titulo="Nenhum item ainda"
                descricao="Sua coleção está vazia no momento. Comece adicionando um novo item abaixo."
                cta="Criar Seu Primeiro Item"
                href="#"
            />
        </div>

    </div>
</section>

