<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <title>Dev Memory — Hub de conhecimento e ambiente para devs</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Hub pessoal de conhecimento técnico com MCP: captura, cura e reutiliza seus aprendizados, e reconstrói seu ambiente em qualquer máquina, 24/7.">
    @vite(['resources/css/app.css'])
    <script>document.documentElement.classList.add('js');</script>
    <style>
        html { scroll-behavior: smooth; }
        /* Reveal só esconde quando há JS — sem JS, conteúdo sempre visível */
        html.js .reveal { opacity: 0; transform: translateY(28px); transition: opacity .6s ease-out, transform .6s ease-out; }
        html.js .reveal.is-visible { opacity: 1; transform: none; }
        .marquee-track { display: inline-block; white-space: nowrap; animation: scroll-caution 22s linear infinite; }
        .tilt-hover { transition: transform .12s ease, box-shadow .12s ease; }
        .tilt-hover:hover { transform: translate(-3px,-3px) rotate(-.5deg); box-shadow: 10px 10px 0 0 #000; }
        .link-official { transition: transform .1s ease, background-color .1s ease; }
        .link-official:hover { transform: translate(-2px,-2px); box-shadow: 4px 4px 0 0 #000; }
        [id] { scroll-margin-top: 90px; }
    </style>
</head>
<body class="bg-neo-bg text-neo-black">

    {{-- ============ NAV ============ --}}
    <header class="sticky top-0 z-50 bg-black border-b-4 border-black">
        <nav class="max-w-6xl mx-auto flex items-center justify-between px-6 h-16">
            <a href="#topo" class="logo-text text-2xl no-underline leading-none"><span class="logo-dev">DEV</span><span class="logo-memory">-MEMORY</span></a>
            <div class="hidden md:flex items-center gap-6 font-heading text-sm text-white uppercase tracking-wide">
                <a href="#o-que-e" class="no-underline text-white hover:text-neo-teal transition-colors">O que é</a>
                <a href="#recursos" class="no-underline text-white hover:text-neo-teal transition-colors">Recursos</a>
                <a href="#pipeline" class="no-underline text-white hover:text-neo-teal transition-colors">Pipeline</a>
                <a href="#stack" class="no-underline text-white hover:text-neo-teal transition-colors">Stack</a>
            </div>
            @auth
                <a href="{{ route('dashboard') }}" class="btn-neo bg-neo-green neo-border-sm shadow-neo px-5 py-2 font-heading text-sm no-underline text-black hover:bg-neo-yellow transition-colors">DASHBOARD →</a>
            @else
                <a href="{{ route('login') }}" class="btn-neo bg-neo-teal neo-border-sm shadow-neo px-5 py-2 font-heading text-sm no-underline text-black hover:bg-neo-yellow transition-colors">ENTRAR →</a>
            @endauth
        </nav>
    </header>

    {{-- ============ HERO ============ --}}
    <section id="topo" class="relative overflow-hidden border-b-4 border-black">
        <div class="max-w-6xl mx-auto px-6 py-20 md:py-28 grid lg:grid-cols-2 gap-12 items-center">
            <div class="animate-glitch-in">
                <span class="inline-block bg-neo-magenta neo-border-sm shadow-neo-sm px-3 py-1 font-heading text-xs uppercase tracking-widest mb-5">Hub pessoal · MCP · 24/7</span>
                <h1 class="font-heading text-5xl md:text-6xl leading-[0.95] m-0 mb-5">
                    Seu <span class="text-neo-magenta">conhecimento</span> e seu <span class="text-neo-teal">ambiente</span>.<br>Em qualquer máquina.
                </h1>
                <p class="text-lg text-gray-700 mb-8 max-w-xl">
                    O Dev Memory captura, cura e reutiliza seus aprendizados de desenvolvimento — e reconstrói o seu setup onde você estiver. Um hub tokenizado, acessível de qualquer projeto via <strong>MCP</strong>.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('login') }}" class="btn-neo bg-neo-teal neo-border shadow-neo-lg px-8 py-3 font-heading text-base no-underline text-black hover:bg-neo-green hover:-translate-y-0.5 hover:shadow-neo-xl transition-all">ENTRAR NO HUB →</a>
                    <a href="#o-que-e" class="btn-neo bg-neo-white neo-border shadow-neo px-8 py-3 font-heading text-base no-underline text-black hover:bg-neo-yellow transition-colors">COMO FUNCIONA</a>
                </div>
            </div>

            {{-- Terminal macOS adaptado ao neo (mesmo padrão do componente code-block) --}}
            <div class="reveal">
                <div class="code-block neo-border shadow-neo-xl overflow-hidden relative">
                    {{-- Titlebar macOS --}}
                    <div class="flex items-center gap-2 px-4 py-2.5 border-b-4 border-black" style="background-color:#2a2a3e;">
                        <span class="w-3 h-3 rounded-full border-2 border-black/30 flex-shrink-0" style="background:#ff5f57;"></span>
                        <span class="w-3 h-3 rounded-full border-2 border-black/30 flex-shrink-0" style="background:#febc2e;"></span>
                        <span class="w-3 h-3 rounded-full border-2 border-black/30 flex-shrink-0" style="background:#28c840;"></span>
                        <span class="mx-auto font-mono text-[10px] uppercase font-bold tracking-widest" style="color:#7f849c;">dev-memory — mcp</span>
                        <span class="w-3 h-3 flex-shrink-0"></span>
                    </div>
                    {{-- Corpo Catppuccin Mocha + CRT --}}
                    <div class="relative overflow-hidden" style="background-color:#1e1e2e;">
                        <div class="crt-overlay opacity-5"></div>
                        <pre class="relative z-20 text-xs md:text-sm font-mono leading-relaxed overflow-x-auto m-0" style="color:#cdd6f4; padding:1.5rem 1.25rem;"><span class="text-gray-500"># antes de implementar:</span>
&gt; <span class="text-neo-magenta">hub_briefing</span>(stack: <span class="text-neo-teal">"Laravel"</span>)
  <span class="text-gray-500">↳ 8 riscos conhecidos, 6 padrões aprovados</span>

<span class="text-gray-500"># máquina limpa, seu setup de volta:</span>
&gt; <span class="text-neo-magenta">harness_provision</span>(<span class="text-neo-teal">"claude-code"</span>)
  <span class="text-gray-500">↳ plano de instalação pronto</span>

<span class="text-gray-500"># bug resolvido → vira conhecimento:</span>
&gt; <span class="text-neo-magenta">memory_ingest</span>(...)
  <span class="text-neo-green">↳ curado, validado, reutilizável ✓</span></pre>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ MARQUEE ============ --}}
    <div class="caution-scroll-container">
        <div class="marquee-track font-heading">
            CAPTURA /// SANITIZA /// CURA COM IA /// VALIDA CONTRA DOCS OFICIAIS /// DEDUPLICA /// AGRUPA EM SKILLS /// SERVE VIA MCP /// CAPTURA /// SANITIZA /// CURA COM IA /// VALIDA CONTRA DOCS OFICIAIS /// DEDUPLICA /// AGRUPA EM SKILLS /// SERVE VIA MCP ///
        </div>
    </div>

    {{-- ============ O QUE É ============ --}}
    <section id="o-que-e" class="border-b-4 border-black">
        <div class="max-w-6xl mx-auto px-6 py-20">
            <div class="section-tag mb-4 reveal"><span class="num">01</span> O QUE É</div>
            <div class="grid md:grid-cols-2 gap-10 items-start">
                <h2 class="font-heading text-3xl md:text-4xl leading-tight m-0 reveal">O conhecimento que se perde entre projetos, agora vira patrimônio reutilizável.</h2>
                <div class="space-y-4 text-gray-700 reveal">
                    <p class="m-0">Os mesmos erros se repetem. As boas decisões se perdem. E cada máquina nova começa do zero. O Dev Memory resolve isso: um <strong>hub único</strong> que acumula sua experiência técnica e a serve de volta — para qualquer projeto, IDE ou agente de IA que se conectar.</p>
                    <p class="m-0">O acesso é <strong>exclusivamente via MCP tokenizado</strong>. Você loga uma vez, conecta o MCP, e leva o seu jeito de codar para onde for.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ RECURSOS ============ --}}
    <section id="recursos" class="border-b-4 border-black bg-neo-white">
        <div class="max-w-6xl mx-auto px-6 py-20">
            <div class="section-tag mb-8 reveal"><span class="num">02</span> RECURSOS</div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @php
                    $features = [
                        ['bg' => 'bg-neo-teal',    't' => 'Curadoria automática', 'd' => 'Pipeline completo: captura imutável → sanitização → curadoria com IA (structured output) → validação documental → deduplicação por recorrência.'],
                        ['bg' => 'bg-neo-magenta', 't' => 'MCP remoto',            'd' => '15 tools acessíveis de qualquer projeto por HTTP + token, ou stdio local. Leitura, escrita e inteligência sobre o seu conhecimento.'],
                        ['bg' => 'bg-neo-yellow',  't' => 'Skills versionadas',    'd' => 'A IA agrupa lições recorrentes e compila skills operacionais com rastreabilidade de fonte, publicadas num repositório git.'],
                        ['bg' => 'bg-neo-green',   't' => 'Provisão de ambiente',  'd' => 'Sobe a config dos seus harnesses (Claude Code e mais) e replica o seu setup completo numa máquina limpa via MCP.'],
                        ['bg' => 'bg-neo-purple',  't' => 'Consulta preventiva',   'd' => 'A tool hub_briefing traz riscos conhecidos, padrões aprovados e lições relevantes ANTES de você implementar.'],
                        ['bg' => 'bg-neo-salmon',  't' => 'Segurança por padrão',  'd' => 'Segredos redigidos na captura, ações destrutivas com confirmação em duas fases, tokens revogáveis, zero credenciais no código.'],
                    ];
                @endphp
                @foreach ($features as $f)
                    <article class="reveal bg-neo-bg neo-border shadow-neo p-6 tilt-hover">
                        <div class="w-12 h-12 {{ $f['bg'] }} neo-border-sm shadow-neo-sm mb-4"></div>
                        <h3 class="font-heading text-xl m-0 mb-2">{{ $f['t'] }}</h3>
                        <p class="text-sm text-gray-700 m-0">{{ $f['d'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ PIPELINE ============ --}}
    <section id="pipeline" class="border-b-4 border-black">
        <div class="max-w-6xl mx-auto px-6 py-20">
            <div class="section-tag mb-3 reveal"><span class="num">03</span> COMO FUNCIONA</div>
            <h2 class="font-heading text-3xl md:text-4xl m-0 mb-10 reveal">Do evento bruto ao conhecimento validado.</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @php
                    $steps = [
                        ['n' => '01', 'bg' => 'bg-neo-teal',    't' => 'Captura & Sanitiza', 'd' => 'Um evento (bug, decisão, lição) entra imutável; segredos são redigidos determinísticamente.'],
                        ['n' => '02', 'bg' => 'bg-neo-magenta', 't' => 'Cura com IA',        'd' => 'O motor extrai um registro estruturado com reparo de schema e auditoria por execução.'],
                        ['n' => '03', 'bg' => 'bg-neo-yellow',  't' => 'Valida & Deduplica', 'd' => 'Conferência contra documentação oficial (Context7) e score composto de recorrência.'],
                        ['n' => '04', 'bg' => 'bg-neo-green',   't' => 'Agrupa & Serve',     'd' => 'Vira skill versionada e fica disponível via MCP — inclusive na consulta preventiva.'],
                    ];
                @endphp
                @foreach ($steps as $s)
                    <div class="reveal bg-neo-white neo-border shadow-neo p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="{{ $s['bg'] }} neo-border-sm px-2 py-0.5 font-heading text-xs">{{ $s['n'] }}</span>
                        </div>
                        <h3 class="font-heading text-lg m-0 mb-2">{{ $s['t'] }}</h3>
                        <p class="text-sm text-gray-700 m-0">{{ $s['d'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-12 reveal">
                <div class="sep-stripe mb-6"></div>
                <div class="grid md:grid-cols-3 gap-4 text-center">
                    <div class="p-4"><div class="font-heading text-4xl text-neo-magenta">15</div><div class="text-xs font-mono uppercase tracking-widest text-gray-600">tools MCP</div></div>
                    <div class="p-4"><div class="font-heading text-4xl text-neo-teal">6</div><div class="text-xs font-mono uppercase tracking-widest text-gray-600">tipos de conhecimento</div></div>
                    <div class="p-4"><div class="font-heading text-4xl text-neo-green">24/7</div><div class="text-xs font-mono uppercase tracking-widest text-gray-600">na sua VPS</div></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ STACK & LINKS OFICIAIS ============ --}}
    <section id="stack" class="border-b-4 border-black bg-neo-white">
        <div class="max-w-6xl mx-auto px-6 py-20">
            <div class="section-tag mb-3 reveal"><span class="num">04</span> CONSTRUÍDO COM</div>
            <p class="text-gray-700 mb-8 max-w-2xl reveal">Ferramentas open-source e padrões abertos. Links para a documentação oficial de cada um:</p>
            @php
                $stack = [
                    ['n' => 'Laravel 13', 'u' => 'https://laravel.com'],
                    ['n' => 'Livewire 4', 'u' => 'https://livewire.laravel.com'],
                    ['n' => 'Tailwind CSS 4', 'u' => 'https://tailwindcss.com'],
                    ['n' => 'Model Context Protocol', 'u' => 'https://modelcontextprotocol.io'],
                    ['n' => 'PHP', 'u' => 'https://www.php.net'],
                    ['n' => 'PostgreSQL', 'u' => 'https://www.postgresql.org'],
                    ['n' => 'Redis', 'u' => 'https://redis.io'],
                    ['n' => 'Context7', 'u' => 'https://context7.com'],
                    ['n' => 'MiniMax', 'u' => 'https://www.minimax.io'],
                ];
            @endphp
            <div class="flex flex-wrap gap-3">
                @foreach ($stack as $item)
                    <a href="{{ $item['u'] }}" target="_blank" rel="noopener noreferrer"
                       class="link-official reveal bg-neo-bg neo-border-sm shadow-neo-sm px-4 py-2 font-heading text-sm no-underline text-black inline-flex items-center gap-2">
                        {{ $item['n'] }}
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5h5v5m0-5L10 14M5 5v14h14v-5"/></svg>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ CTA FINAL ============ --}}
    <section class="border-b-4 border-black bg-black text-white">
        <div class="max-w-6xl mx-auto px-6 py-20 text-center reveal">
            <h2 class="font-heading text-4xl md:text-5xl m-0 mb-4">Comece a acumular <span class="text-neon-green">seu patrimônio técnico</span>.</h2>
            <p class="text-gray-400 mb-8 max-w-xl mx-auto">Cada projeto começa já beneficiado por tudo que você aprendeu antes.</p>
            <a href="{{ route('login') }}" class="btn-neo bg-neo-teal neo-border shadow-neo-lg px-10 py-4 font-heading text-lg no-underline text-black hover:bg-neon-green hover:-translate-y-1 hover:shadow-neo-xl transition-all">ENTRAR NO HUB →</a>
        </div>
    </section>

    {{-- ============ FOOTER ============ --}}
    <footer class="bg-neo-bg">
        <div class="max-w-6xl mx-auto px-6 py-10 flex flex-col md:flex-row items-center justify-between gap-4">
            <span class="logo-text text-lg"><span style="color:#000">DEV</span><span class="text-gray-500">-MEMORY</span></span>
            <p class="text-xs font-mono text-gray-500 m-0 uppercase tracking-widest">Sistema operacional de conhecimento para desenvolvimento · nandodev</p>
        </div>
    </footer>

    <script>
        (function () {
            var reveals = document.querySelectorAll('.reveal');
            var show = function (el) { el.classList.add('is-visible'); };
            if ('IntersectionObserver' in window) {
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) {
                        if (e.isIntersecting) { show(e.target); io.unobserve(e.target); }
                    });
                }, { threshold: 0.1 });
                reveals.forEach(function (el, i) {
                    el.style.transitionDelay = (i % 6) * 55 + 'ms';
                    io.observe(el);
                });
                // Rede de segurança: garante que tudo apareça mesmo se o observer falhar.
                window.addEventListener('load', function () {
                    setTimeout(function () { reveals.forEach(show); }, 1200);
                });
            } else {
                reveals.forEach(show);
            }
        })();
    </script>
</body>
</html>
