@props([
    'action' => '#',
    'title'  => 'Sign In',
])

<div class="card-neo bg-white neo-border shadow-neo-xl p-8 w-full max-w-sm mx-auto">
    {{-- Title --}}
    <h2 class="font-heading text-3xl font-bold uppercase mb-6 border-b-4 border-black pb-3 tracking-wide">
        {{ $title }}
    </h2>

    <form method="POST" action="{{ $action }}" class="space-y-5" novalidate>
        @csrf

        {{-- Email --}}
        <div class="space-y-1">
            <label for="email" class="block text-xs font-bold font-body uppercase tracking-wider">E-mail</label>
            <input
                id="email"
                name="email"
                type="email"
                placeholder="you@example.com"
                required
                autocomplete="email"
                class="input-neo w-full neo-border shadow-neo px-3 py-2 outline-none font-body bg-white"
            />
        </div>

        {{-- Password --}}
        <div class="space-y-1">
            <label for="password" class="block text-xs font-bold font-body uppercase tracking-wider">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                placeholder="••••••••"
                required
                autocomplete="current-password"
                class="input-neo w-full neo-border shadow-neo px-3 py-2 outline-none font-body bg-white"
            />
        </div>

        {{-- Submit --}}
        <button
            type="submit"
            class="btn-neo w-full bg-neo-teal neo-border shadow-neo px-6 py-3 font-heading font-bold uppercase tracking-wide hover:bg-neo-yellow transition-colors duration-100 text-base mt-2"
        >
            Sign In
        </button>
    </form>

    {{-- Forgot password --}}
    <div class="mt-5 text-center">
        <a
            href="#"
            class="font-body text-xs font-bold uppercase underline underline-offset-2 hover:text-neo-purple transition-colors duration-100"
        >
            Forgot password?
        </a>
    </div>
</div>
