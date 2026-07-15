<div class="animate-fade-in-up">
    <div class="bg-neo-white neo-border shadow-neo-lg p-8 space-y-6">
        <div class="text-center space-y-2">
            <span class="logo-text text-2xl"><span class="logo-dev">DEV</span><span class="logo-memory">-MEMORY</span></span>
            <p class="text-xs font-mono text-gray-500 uppercase tracking-[0.2em]">acesso restrito // system_root</p>
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
                <input type="checkbox" wire:model="remember" class="w-4 h-4 neo-border-sm">
                Manter conectado
            </label>

            <x-neo.button tipo="submit" variante="primario" tamanho="lg" class="w-full justify-center" wire:loading.attr="disabled" wire:target="login">
                <span wire:loading.remove wire:target="login">ENTRAR &rarr;</span>
                <span wire:loading wire:target="login">AUTENTICANDO...</span>
            </x-neo.button>
        </form>
    </div>
</div>
