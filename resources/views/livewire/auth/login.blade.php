<div class="animate-glitch-in relative" x-data="{ shake: false }" x-on:auth-failed.window="shake = true; setTimeout(() => shake = false, 500)">
    <div class="bg-neo-white neo-border shadow-neo-xl relative overflow-hidden scanlines-crt"
         :class="shake && 'animate-shake'">

        {{-- Barra de título estilo terminal --}}
        <div class="h-11 bg-black flex items-center px-4 gap-2 border-b-4 border-black relative z-20">
            <div class="w-3 h-3 rounded-full bg-red-500 border border-black/40"></div>
            <div class="w-3 h-3 rounded-full bg-yellow-500 border border-black/40"></div>
            <div class="w-3 h-3 rounded-full bg-green-500 border border-black/40"></div>
            <span class="ml-2 text-[10px] font-mono text-gray-400 uppercase tracking-[0.2em]">secure-shell — auth</span>
            <span class="logo-text ml-auto text-lg leading-none"><span class="logo-dev">DEV</span><span class="logo-memory">-MEM</span></span>
        </div>

        <div class="p-8 space-y-6 relative z-20">
            <div class="text-center space-y-3">
                <span class="logo-text text-3xl"><span class="logo-dev">DEV</span><span class="text-black">-MEMORY</span></span>
                <div class="sep-validated"></div>
                <p class="text-xs font-mono text-gray-500 uppercase tracking-[0.25em]">
                    <span class="text-neon-green animate-pulse">●</span> acesso restrito // system_root
                </p>
            </div>

            <form wire:submit="login" class="space-y-4">
                <x-neo.input
                    rotulo="EMAIL"
                    tipo="email"
                    wire:model="email"
                    placeholder="voce@exemplo.com"
                    :erro="$errors->first('email')"
                    autofocus
                />

                <x-neo.input
                    rotulo="SENHA"
                    tipo="password"
                    wire:model="password"
                    placeholder="••••••••"
                    :erro="$errors->first('password')"
                />

                <label class="flex items-center gap-2 text-xs font-mono font-bold uppercase cursor-pointer select-none">
                    <input type="checkbox" wire:model="remember" class="w-4 h-4 neo-border-sm accent-neo-teal">
                    Manter conectado
                </label>

                <button type="submit" wire:loading.attr="disabled" wire:target="login"
                        class="btn-neo w-full bg-neo-teal neo-border shadow-neo-lg font-heading text-base px-8 py-3 uppercase tracking-wide
                               hover:bg-neon-green hover:-translate-y-0.5 hover:shadow-neo-xl transition-all duration-100 relative overflow-hidden group">
                    <span class="relative z-10" wire:loading.remove wire:target="login">ENTRAR &rarr;</span>
                    <span class="relative z-10" wire:loading wire:target="login">AUTENTICANDO<span class="animate-pulse">_</span></span>
                    <span class="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-500 bg-gradient-to-r from-transparent via-white/40 to-transparent"></span>
                </button>
            </form>

            <p class="text-center text-[10px] font-mono text-gray-400 uppercase tracking-widest">
                aguardando credenciais <span class="animate-pulse text-neon-green">▊</span>
            </p>
        </div>
    </div>
</div>
