{{--
    Uso: não instanciar diretamente — o toast é controlado pelo listener Alpine
    no layout. Disparar via Livewire: $this->dispatch('show-toast', message: '...', type: 'sucesso')
    Tipos aceitos: sucesso | erro | aviso | info
--}}
<div
    x-data="{
        toasts: [],
        addToast(message, type = 'sucesso') {
            const id = Date.now();
            this.toasts.push({ id, message, type });
            setTimeout(() => { this.removeToast(id); }, 3000);
        },
        removeToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    @show-toast.window="addToast($event.detail.message, $event.detail.type ?? 'sucesso')"
    class="fixed bottom-6 right-6 z-50 flex flex-col gap-3 items-end"
    aria-live="assertive"
    aria-atomic="true"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-x-full"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-full"
            class="bg-white neo-border shadow-neo-xl w-80 max-w-[calc(100vw-3rem)] flex overflow-hidden"
            role="alert"
        >
            {{-- Faixa lateral colorida --}}
            <div
                class="w-2 flex-shrink-0"
                :class="{
                    'bg-neo-green':   toast.type === 'sucesso',
                    'bg-neo-magenta': toast.type === 'erro',
                    'bg-neo-yellow':  toast.type === 'aviso',
                    'bg-neo-teal':    toast.type === 'info'
                }"
            ></div>

            {{-- Ícone --}}
            <div class="flex items-start pt-4 pl-3 flex-shrink-0">
                <span
                    class="w-6 h-6 flex items-center justify-center border-2 border-black font-heading font-bold text-xs"
                    :class="{
                        'bg-neo-green':   toast.type === 'sucesso',
                        'bg-neo-magenta': toast.type === 'erro',
                        'bg-neo-yellow':  toast.type === 'aviso',
                        'bg-neo-teal':    toast.type === 'info'
                    }"
                    x-text="{ sucesso: '✓', erro: '✕', aviso: '!', info: 'i' }[toast.type] ?? 'i'"
                ></span>
            </div>

            {{-- Texto --}}
            <div class="flex-1 p-4 pr-3 min-w-0">
                <p
                    class="font-heading font-bold uppercase text-xs mb-1"
                    x-text="{ sucesso: 'Sucesso', erro: 'Erro', aviso: 'Aviso', info: 'Info' }[toast.type] ?? 'Info'"
                ></p>
                <p class="font-body text-sm leading-snug" x-text="toast.message"></p>
            </div>

            {{-- Fechar --}}
            <div class="p-2 flex-shrink-0">
                <button
                    @click="removeToast(toast.id)"
                    class="w-6 h-6 flex items-center justify-center border-2 border-black bg-white font-heading font-bold text-sm hover:bg-neo-yellow transition-colors duration-100"
                    aria-label="Fechar notificação"
                >×</button>
            </div>
        </div>
    </template>
</div>
