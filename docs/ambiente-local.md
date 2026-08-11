# Ambiente local (Docker) e fluxo de branches

## Fluxo: `dev` → `main`

Este é um projeto pessoal, sem homologação. **`main` faz deploy automático** (push → release em
~20s, rodando migrations). Portanto:

- **Nunca commitar direto na `main`.** Todo trabalho vive na `dev`.
- A promoção é **merge local** (você é o mantenedor — não há PR):

```bash
git checkout main
git merge --ff-only dev     # falha se a main andou; nesse caso rebase a dev antes
git push origin main        # ← este push deploya
git checkout dev
```

O `--ff-only` é proposital: se a `main` recebeu algo que a `dev` não tem, o merge falha em vez de
criar um merge-commit silencioso. Resolva com `git rebase main` na `dev` e repita.

**Antes de promover:** `bin/dev test` verde e `bin/dev pint --test` limpo.

## Subir o ambiente

```bash
bin/dev up          # postgres + redis + app + fila + vite
bin/dev logs app    # acompanhar
bin/dev down        # derrubar
```

| Serviço | Onde | Observação |
|---|---|---|
| App | http://localhost:25080 | `artisan serve`, código por bind-mount (sem rebuild ao editar) |
| Vite | http://localhost:25173 | HMR |
| Postgres | `localhost:25432` | mesma major de produção (16) |
| Redis | `localhost:25379` | cache/sessão/fila |

Portas na faixa 25xxx para conviverem com o `docker-compose.yml` de **produção** (15432/16379),
que este ambiente não toca.

## Laravel Boost via MCP

O Laravel Boost é uma dependência de desenvolvimento e o servidor MCP deve ser
iniciado dentro do serviço `app`. A conexão já está registrada em `.mcp.json`
como `laravel-boost`:

```json
{
  "command": "docker",
  "args": [
    "compose", "-f", "docker-compose.dev.yml", "exec", "-T",
    "app", "php", "artisan", "boost:mcp"
  ]
}
```

O `-T` desativa o pseudo-TTY e preserva o transporte stdio do MCP. Suba o
ambiente antes de conectar o cliente:

```bash
bin/dev up
docker compose -f docker-compose.dev.yml exec -T app php artisan boost:mcp
```

O segundo comando é apenas um smoke test manual; o cliente MCP deve usar a
entrada `laravel-boost` do `.mcp.json`.

### Regra de recuperação de conectividade

Quando o Laravel Boost aparecer como desconectado, indisponível ou sem tools,
seguir esta ordem. A conexão só é considerada recuperada depois que o handshake
MCP responder; `command -v docker`, `docker --version` ou `artisan list` sozinhos
não provam conectividade.

1. **Confirmar o worktree ativo.** O cliente MCP, o `.mcp.json`, o
   `docker-compose.dev.yml` e o container precisam pertencer ao mesmo worktree:

   ```bash
   pwd
   git worktree list
   docker compose -f docker-compose.dev.yml config --services
   ```

   Se houver outro worktree usando a mesma stack, não reutilizar silenciosamente
   o container dele: o bind mount pode estar servindo código de outra árvore.
   Parar a stack concorrente ou configurar uma stack com nomes/portas próprios
   antes de reconectar.

2. **Classificar a falha antes de editar.** Verificar o estado real:

   ```bash
   docker compose -f docker-compose.dev.yml ps
   docker compose -f docker-compose.dev.yml exec -T app php artisan about
   docker compose -f docker-compose.dev.yml exec -T app php artisan list --format=txt | rg 'boost:mcp'
   docker compose -f docker-compose.dev.yml exec -T app composer show laravel/boost
   ```

   Se aparecer `permission denied` no Docker socket, isso é bloqueio do
   sandbox/executor, não defeito do Boost. Repetir os mesmos comandos em um
   terminal autorizado no host; não trocar a configuração para `php artisan`
   nem remover o `-T` para contornar o sandbox.

3. **Reparar somente o que estiver quebrado.** Se o container estiver parado,
   iniciar `bin/dev up`. Se o pacote estiver ausente após recriar a imagem,
   restaurar as dependências no container:

   ```bash
   bin/dev composer install
   ```

   Se `composer.json` ainda não declarar o pacote, instalar apenas como
   dependência de desenvolvimento:

   ```bash
   bin/dev composer require laravel/boost --dev
   docker compose -f docker-compose.dev.yml exec -T app php artisan boost:install --mcp --no-interaction
   ```

   Depois de `boost:install`, revisar `.mcp.json`: neste projeto a entrada
   correta deve continuar apontando para `docker-compose.dev.yml`, serviço
   `app`, `exec -T` e `php artisan boost:mcp`. O instalador pode gerar a forma
   de host (`php artisan boost:mcp`), que não é a forma correta para este
   ambiente Dockerizado.

4. **Reconectar e provar o protocolo.** Com a stack em execução, enviar um
   `initialize` ao processo configurado:

   ```bash
   printf '%s\n' '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"local-smoke","version":"1.0"}}}' \
     | timeout 5s docker compose -f docker-compose.dev.yml exec -T app php artisan boost:mcp \
     | jq -c 'select(.id == 1) | {id, server: .result.serverInfo.name}'
   ```

   O resultado esperado contém `"server":"Laravel Boost"`. Para confirmar as
   ferramentas, repetir o teste com uma segunda linha JSON-RPC para
   `tools/list`; a resposta deve listar as tools do Boost, incluindo
   `search-docs`, `database-schema` e `browser-logs`.

5. **Recarregar o cliente MCP.** Após corrigir `.mcp.json`, reiniciar ou
   recarregar a sessão do cliente (Codex, Claude, Cursor ou outro). O cliente
   não reaproveita necessariamente o processo stdio antigo. Manter o cliente
   apontado para `laravel-boost`; o `dev-memory-docker` é o MCP próprio da
   aplicação e não substitui o Laravel Boost.

**Resumo da decisão:** falha de processo/container → corrigir Compose ou
dependências; falha de permissão do sandbox → repetir fora do sandbox com a
mesma configuração; worktree incorreto → alinhar diretório, `.mcp.json` e
container; handshake sem resposta → só então investigar Boost/Artisan.

## Comandos

```bash
bin/dev test                 # suíte
bin/dev artisan migrate      # artisan
bin/dev composer require ... # composer (PHP 8.4)
bin/dev npm run build        # node
bin/dev shell                # bash no container
bin/dev psql                 # psql no banco
bin/dev pint --dirty         # estilo
```

## Por que Postgres também em desenvolvimento

O SQLite não impõe largura de coluna. Foi por isso que `memories.type` em `varchar(20)` aceitou
um valor de 21 caracteres localmente e **só estourou em produção**, depois de passar por 184
testes verdes. Rodar o dia a dia sobre o mesmo banco de produção fecha essa classe de divergência.

> Ressalva honesta: **a suíte ainda roda em SQLite in-memory** (fixado no `phpunit.xml`), porque
> é rápido. Ou seja, a paridade acima vale para o uso da aplicação, não para os testes. Migrar a
> suíte para Postgres é uma melhoria pendente — é o que pegaria um novo caso "varchar".

## Armadilha registrada: variáveis de ambiente vs PHPUnit

O PHPUnit 12 **não** sobrescreve uma variável de ambiente real com o `<env>` do `phpunit.xml`
(o atributo `force` saiu na v10). Como o compose exporta `APP_ENV=local` para a aplicação rodar
em modo dev, a suíte herdava esse valor e rodava **fora do modo de teste**: as macros do Livewire
não eram registradas (`assertSeeLivewire` inexistente) e o CSRF barrava POSTs com **419**. Eram
3 falhas que apareciam só dentro do container e passavam no host.

Por isso `bin/dev test` declara `APP_ENV=testing` (e cache/sessão/fila de teste) explicitamente.
**Rodar `artisan test` direto no container sem isso reproduz as 3 falhas** — não é regressão.

## Prompts em testes de comando

`tests/TestCase.php` chama `Prompt::fallbackWhen(true)`. Sem isso, comandos que usam Laravel
Prompts se comportam conforme exista terminal interativo: no host passavam, no container sem TTY
quebravam com `NonInteractiveValidationException`, e com TTY ficavam pendurados esperando
digitação. O fallback usa o QuestionHelper do Symfony, que é o que `expectsQuestion()` intercepta.

## `php artisan serve` lê o `.env` do host — o WEB caía no SQLite

Sintoma: usuário criado via `docker exec ... make-admin` (que usa a env do compose → **pgsql**)
não logava no navegador, "credenciais inválidas". Causa: o `.env` do host (bind-mount) tem
`DB_CONNECTION=sqlite`, e o **`php artisan serve` repassa o `.env` ao processo do servidor** —
então o WEB lia o SQLite do host (que tinha outros usuários), enquanto o CLI (`docker exec`, que
respeita a env do compose) ia no Postgres. **Dois bancos divergentes.** O `queue:work` não sofre
disso (não passa pelo serve → usa a env do compose).

Fix: cada serviço PHP monta um **`.env.dev`** (gitignored, copiado do `.env` do host preservando
`APP_KEY`/chaves, com DB/redis/sessão apontando para a topologia do compose — `postgres`/`redis`)
por cima do `.env`:
```yaml
volumes:
  - .:/var/www/html
  - ./.env.dev:/var/www/html/.env   # pgsql/redis; sem isto o serve cai no SQLite do host
```
Recriar após criar/alterar o `.env.dev`: `docker compose -f docker-compose.dev.yml up -d app queue`.

## Criar o admin (o DB dev começa vazio)

O Postgres dev não tem usuário. O `make-admin` usa Laravel Prompts (senha interativa), então
precisa de **TTY de verdade** — não funciona sob o `!` do agente:
```bash
docker exec -it devmem-dev-app php artisan memory:make-admin --email=voce@exemplo.com --name="Voce"
# ou, para script/sem prompt: reset via tinker
docker exec devmem-dev-app php artisan tinker --execute="\$u=App\Models\User::firstWhere('email','voce@exemplo.com'); \$u->password=Hash::make('SUA_SENHA'); \$u->save();"
```
