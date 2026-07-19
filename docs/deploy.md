# Deploy — Dev Memory Hub

Runbook do deploy em produção. Estado atual em [`STATUS.md`](STATUS.md).

## Ambiente de produção

- **URL:** `https://devmemory.fssdev.com.br`
- **VPS:** `srv084270.cloudprime.cloud` · painel **Jarvis Forge** (CloudPrime)
- **PHP:** 8.4 (8.5/8.3 também instalados) · **Banco:** PostgreSQL · **SSL:** Let's Encrypt
- **Releases:** zero-downtime (`/var/www/devmemory.fssdev.com.br/{current → releases/…, shared/, .repo}`).
  O `.env` e o `storage/` vivem em `shared/` (compartilhados entre releases).
- **Fila:** 2 workers daemon `queue:work` gerenciados pelo Forge (seção Daemons).

## Deploy hook (roda a cada deploy, no diretório do release)

```bash
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
npm ci && npm run build          # CRÍTICO — ver gotcha #1
php artisan migrate --force
php artisan optimize             # config + route + view + event cache
php artisan storage:link || true
php artisan queue:restart        # ver gotcha #4
```

Auto-deploy: webhook na `main` (Jarvis Forge). Como o painel usa releases zero-downtime, os
workers **precisam** do `queue:restart` no hook para não rodarem código/config antigos.

## `.env` de produção (chaves que importam)

- `APP_ENV=production`, **`APP_DEBUG=false`** (nunca true em prod — vaza stack/segredos em erro), `APP_KEY` (gerar 1×), `APP_URL=https://devmemory.fssdev.com.br`
- **`DB_CONNECTION=pgsql`, `DB_PORT=5432`** (ver gotcha #2)
- **`MINIMAX_API_KEY`** — obrigatório p/ a curadoria (lido via `config('services.minimax.api_key')`); pode também ser administrado pela tela **CONFIGURAÇÕES** (criptografado em DB, sobrepõe o env)
- `CONTEXT7_API_KEY` — **opcional** (validação documental funciona keyless; ver gotcha #6)
- `SESSION_DRIVER`/`CACHE_STORE`/`QUEUE_CONNECTION` = `database`/`file`/`database` (ver gotcha #3)
- **`DB_QUEUE_RETRY_AFTER=330`** — precisa ser MAIOR que o timeout dos jobs (300s); com o default (90s) um job lento de curadoria é re-reservado por outro worker e marcado como falho enquanto ainda roda
- `SESSION_SECURE_COOKIE=true` — cookie de sessão só via HTTPS

## Gotchas aprendidos (2026-07-17) — leia antes de deployar

1. **`public/build` é gitignored** → o repo não traz os assets. Sem `npm run build` no deploy,
   toda página com `@vite` dá **HTTP 500** (`ViteManifestNotFoundException`). Foi o que quebrou o CI também.
2. **O banco é PostgreSQL, não MySQL.** Um `.env` com `DB_CONNECTION=mysql`/`3306` dá
   `SQLSTATE[HY000] [1045] Access denied` em toda request (a sessão bate no banco). Usar `pgsql`/`5432`.
3. **Redis exige a extensão `phpredis`.** Sem ela (default `REDIS_CLIENT=phpredis`, sem `predis` no
   composer), tudo que é redis quebra no boot. No 1º deploy usamos `database`/`file` para não depender de redis.
4. **Workers ficam stale sem `queue:restart`.** `queue:work` carrega config uma vez ao subir; após
   mudar `.env`/deploy, sem `queue:restart` (+ respawn pelo Forge) eles rodam config velha e não
   processam a fila. Reiniciar pelo painel (Daemons) resolve.
5. **`env()` retorna `null` após `config:cache`.** Verifique variáveis via `config('services.…')`,
   não `env(…)` — depois do cache, `env()` fora de arquivos de config sempre é null (não é bug).
6. **SSL:** o Certbot pode gerar configs nginx malformadas (diretiva `ssl_ciphers` duplicada por
   incluir `options-ssl-nginx.conf` sobre config que já tinha ciphers inline → `nginx -t` falha e o
   painel recusa salvar). O **Context7 funciona sem chave** (`Context7Client` só manda token se existir).

## Bootstrap (1ª vez)

```bash
php artisan key:generate
php artisan make:… # (migrations rodam pelo hook)
php artisan memory:make-admin --email=… --name="…"   # senha é interativa, NUNCA no .env
```

## Pós-deploy — ingestão de produção (MCP)

1. Emitir um **API token** em `/admin/tokens` (mostrado 1× só).
2. Ingerir via **MCP HTTP**: `POST https://devmemory.fssdev.com.br/api/mcp` com
   `Authorization: Bearer <token>`, JSON-RPC `tools/call` → `memory_ingest`.
3. Os workers processam a fila e a curadoria roda automática (MiniMax). Validação documental:
   `php artisan memory:validate-docs`.
