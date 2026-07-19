# WIP pendente — pacote local `nando/ai-motor`

Havia uma alteração NÃO-COMMITADA no `composer.json` adicionando o pacote local
`nando/ai-motor` (repositório `path` → `/home/nandodev/projects/teste/nando-ai-laravel`).

**Ela foi perdida** num `git reset --hard` meu ao mover o trabalho da `main` para a `dev`
(2026-07-19). Reconstruí pelo registro em `status-checkpoint`, mas a reconstrução quebrava
`composer install` (o pacote não está no `composer.lock`), então preferi devolver a árvore
funcional e deixar o WIP aqui como comando — assim você o refaz do jeito certo:

```bash
composer config repositories.ai-motor path ../teste/nando-ai-laravel
composer require nando/ai-motor:@dev
```

Isso atualiza `composer.json` E `composer.lock` de forma consistente (o edit manual só
mexia no primeiro, que era exatamente o estado inacabado).

> Lembrete do checkpoint: **não deve ir para a `main` nem para o deploy** — o `composer.json`
> commitado está limpo.

Apague este arquivo depois de refazer.
