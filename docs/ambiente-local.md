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
