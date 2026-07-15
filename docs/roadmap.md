# Roadmap — Dev Memory Hub

Visão de evolução. Estado atual em [`STATUS.md`](STATUS.md).

---

## Curto prazo (essência+)

- Aprovar/publicar as 5 skills draft.
- **Deploy na VPS** (F1 físico) — o hub roda 24/7 na VPS; qualquer máquina conecta via MCP.
- **Embeddings/pgvector** — recorrência e busca semântica reais (hoje TF-cosseno).
- **Campo `maturity`** — workaround → provisório → recomendado → canônico → consolidado.

## Médio prazo

- **Landing page espetacular (neo brutalist)** — *parado para depois.* Vitrine do hub usando o melhor do tema neo; também porta de entrada/onboarding.
- **Captura contínua** — hooks dos harnesses (PostToolUse/Stop) alimentando `memory_ingest` automaticamente.

---

## Provisionamento agnóstico de harness (visão)

> O salto de "hub de **conhecimento**" para "hub de **ambiente**": o dev-memory carrega não só o que você sabe, mas *como você trabalha* — e reconstrói seu setup em qualquer máquina limpa.

### Ideia

O hub guarda, além de memórias/skills, **perfis de configuração por harness**. Numa máquina nova, você loga o MCP e pede a instalação completa do seu jeito de codar para cada CLI que usa:

- **Claude Code** · **Codex** · **Hermes** · **Antigravity**

Resultado: seu conhecimento **e** seu ambiente 24/7, independente de onde estiver.

### O que um perfil de harness guarda

- Instruções globais (ex.: `~/.claude/CLAUDE.md`), personas, convenções.
- Registro de MCP servers a instalar — **incluindo a própria conexão ao dev-memory** (bootstrap recursivo: o primeiro passo da instalação é plugar o hub).
- Skills/comandos, settings, keybindings, aliases.
- Referências a segredos — **nunca os segredos em si** (ver Riscos).

### Fluxo de instalação

Como o MCP é passivo (o servidor responde; quem age é o cliente), há dois modos:

1. **Dirigido por agente** — na máquina limpa, o agente conecta ao hub e chama uma tool `setup_provision(harness)` que retorna o **plano de instalação** (arquivos + destinos + passos). O próprio agente executa localmente (escreve os arquivos, registra os MCP, aplica settings), com confirmação para passos destrutivos.
2. **Dirigido por script** — o hub gera um instalador self-contained; `curl https://SEU-HUB/install/<harness> | bash` provisiona o CLI. Bom para quando não há agente ainda.

```
máquina limpa ──MCP──> hub: setup_provision("claude-code")
                          │
                          ▼
        plano: [ ~/.claude/CLAUDE.md, .mcp.json (+ dev-memory),
                 skills/, settings.json, keybindings.json ]
                          │
        agente instala localmente (ou curl|bash)  ──✋ confirma destrutivos──> pronto
```

### Componentes a construir (esboço)

- **Domínio `Setup`/`Profile`** — perfis versionados por harness (como as skills, git-backed).
- **Adapters por harness** — cada CLI tem formato/local de config diferente; um template/adapter por harness (claude-code, codex, hermes, antigravity).
- **Tools MCP** — `setup_list`, `setup_get(harness)`, `setup_provision(harness)` (retorna plano acionável).
- **Gerador de instalador** — endpoint que emite o bash idempotente por harness.
- **Versionamento** — perfis evoluem; manter histórico (reusa o padrão git das skills).

### Riscos e princípios

- **Segredos nunca embarcados** — o instalador referencia/pede segredos (API keys, tokens), nunca os embute. Reusa a disciplina de sanitização já no hub.
- **Idempotência + confirmação** — reinstalar não pode clobberar mudanças locais sem confirmar. Reusa o padrão do `ConfirmationGuard`.
- **Heterogeneidade** — formatos/locais variam por harness; isolar em adapters, começar por 1 (Claude Code) e expandir.
- **Agnóstico de verdade** — o núcleo (perfis, tools) é harness-neutro; só os adapters conhecem cada CLI.

### Fases sugeridas

1. **Perfil Claude Code** — modelar, guardar e provisionar 1 harness ponta a ponta (prova de conceito).
2. **Tools MCP + confirmação** — `setup_provision` com plano acionável e confirmação de destrutivos.
3. **Instalador script** — `curl|bash` idempotente por harness.
4. **Expandir adapters** — Codex, Hermes, Antigravity.
5. **Captura de config** — comando/tool para *salvar* o setup atual da máquina no hub (o inverso do provision).

---

## Longo prazo

Sistema operacional de conhecimento pessoal: captura experiência, valida boas práticas, promove padrões e **reconstrói você** (conhecimento + ambiente) em qualquer lugar, 24/7, agnóstico de máquina e de harness.
