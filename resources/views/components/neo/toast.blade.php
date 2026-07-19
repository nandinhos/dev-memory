{{--
    Uso: não instanciar diretamente — o toast é controlado pelo listener Alpine
    no layout. Disparar via Livewire: $this->dispatch('show-toast', message: '...', type: 'sucesso')
    Disparar via JS: window.dispatchEvent(new CustomEvent('show-toast', { detail: { message: '...' } }))
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
    class="fixed top-8 right-8 z-[100] flex flex-col gap-4 items-end"
    aria-live="assertive"
    aria-atomic="true"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="animate-glitch-in"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95 translate-x-10"
            class="relative text-black font-bold px-6 py-4 border-4 border-black shadow-[10px_10px_0px_0px_rgba(0,0,0,1)] overflow-hidden min-w-[300px]"
            :class="{ sucesso: 'bg-[#39FF14]', erro: 'bg-neo-magenta', aviso: 'bg-neo-yellow', info: 'bg-neo-teal' }[toast.type] ?? 'bg-[#39FF14]'"
            role="alert"
        >
            {{-- CRT Overlay integrada com opacidade 0.05 --}}
            <div class="crt-overlay opacity-5"></div>

            <div class="relative z-20 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="text-xl" x-text="{ sucesso: '✓', erro: '✕', aviso: '!', info: 'i' }[toast.type] ?? '✓'"></span>
                    <span class="uppercase tracking-tight" x-text="toast.message"></span>
                </div>
                <button
                    @click="removeToast(toast.id)"
                    class="text-black hover:opacity-50 transition-opacity"
                    aria-label="Fechar"
                >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </template>
</div>
