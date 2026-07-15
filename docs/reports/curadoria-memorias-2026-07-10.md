# Relatório de Curadoria — Acervo de Memórias (46 registros)

**Data:** 2026-07-10
**Objetivo:** comprovar, com evidência forense e validação independente por modelo, que parte das memórias do banco são dados de teste, e quantificar o impacto nas métricas do sistema.
**Método:** duas camadas independentes — (1) análise forense do banco (conteúdo, timestamps UUID v7, padrões de criação) e (2) score de `confidence` atribuído pelo motor de curadoria MiniMax-M2.5 no piloto P1, sem conhecimento da primeira análise.

---

## 1. Conclusão

Das **46 memórias ativas**, **14 são registros espúrios** (30% do acervo):

| Grupo | Qtd | Natureza | Evidência |
|-------|-----|----------|-----------|
| **A — Genuínas** | 32 | Conhecimento técnico real e bem redigido | confidence 0.80–0.95 no modelo |
| **B — Faker/lorem ipsum** | 10 | Texto latino gerado por factory/seeder, sem sentido | confidence 0.10–0.25; criadas no mesmo segundo |
| **C — Cópias duplicadas** | 4 | Conteúdo real, mas segunda cópia idêntica de memórias do grupo A | dois lotes de seeder com 14 s de intervalo |

**As duas camadas concordaram em 100% dos casos**: não existe uma única memória genuína com confidence ≤ 0.25, nem uma única lorem com confidence ≥ 0.80. Qualquer corte entre 0.25 e 0.80 separa os grupos com precisão e recall de 100% neste acervo.

---

## 2. Evidência Forense (camada 1)

### 2.1 Grupo B — 10 memórias Faker (lorem ipsum)

Todas com texto latino típico do Faker (`Est et perferendis est at autem voluptas.`), todas criadas **no mesmo segundo** — os UUIDs v7 são sequenciais (`019d15e1-9119…` a `019d15e1-913d…`), assinatura inequívoca de uma única execução de factory/seeder:

| ID | Status | Recurrence | Título | Confidence (modelo) |
|----|--------|-----------|--------|---------------------|
| `019d15e1-9119-7287-9f1f-a5e3c7f40a8b` | pending | 4 | Est et perferendis est at autem voluptas. | 0.10 |
| `019d15e1-911d-7113-80f5-e40aa955fcc5` | pending | 29 | In quod culpa ut qui dolores. | 0.10 |
| `019d15e1-9122-733f-9cfa-0d72a7324556` | pending | 8 | Reprehenderit quis nisi provident porro totam aut. | 0.10 |
| `019d15e1-9125-7305-a5b8-e04acbaa0e8a` | rejected | 14 | Quae sapiente ad cupiditate aut explicabo minus et. | 0.10 |
| `019d15e1-9129-715c-8a72-5ad717de7168` | rejected | 8 | Ea et hic excepturi. | 0.20 |
| `019d15e1-912c-72d8-94cc-8f814b117ec9` | rejected | 42 | Eveniet amet magnam recusandae et ipsa voluptates. | 0.20 |
| `019d15e1-9132-726b-a193-3f878f122c23` | **validated** ⚠️ | **49** | Et incidunt placeat inventore accusantium maiores rerum. | 0.10 |
| `019d15e1-9136-704b-a480-c6bb9d791c6d` | rejected | 41 | Totam reiciendis reiciendis facilis aliquam totam dolorum eum. | 0.25 |
| `019d15e1-9139-7054-8d26-5387e8093b9d` | pending | 19 | In saepe culpa qui explicabo in consequuntur. | 0.10 |
| `019d15e1-913d-7143-94a9-412944c704ca` | rejected | 10 | Provident quia provident soluta. | 0.10 |

> ⚠️ **Achado crítico**: a memória `…9132` está **validada** com recurrence **49** — é o item nº 1 do ranking de recorrência de todo o sistema. Um texto sem sentido lidera as métricas do dashboard.

### 2.2 Grupo C — 4 cópias duplicadas

Quatro títulos reais existem em dobro. Os pares foram criados por **duas execuções de seeder com 14 segundos de intervalo** (2026-03-22 20:34:50 e 20:35:04). A cópia posterior (prefixo `019d1742-26…`) é o registro espúrio:

| Cópia a remover (ID) | Título | Status da cópia |
|----------------------|--------|-----------------|
| `019d1742-2642-711a-97bd-8265fc12e240` | ILIKE não funciona no SQLite | validated |
| `019d1742-264b-720c-b9d6-ff38431606ed` | Dispatch de eventos no Livewire 4 | pending |
| `019d1742-264f-70fb-9fb1-358ee5c1aaf2` | Soft deletes em models Eloquent | validated |
| `019d1742-2653-709c-9fc5-ee66a58f5281` | Vite requer Node 20+ | validated |

Por serem produto do mesmo evento de seed (não ocorrências independentes), a recomendação é **remover a cópia sem somar recurrence** ao original.

---

## 3. Validação Independente pelo Modelo (camada 2)

No piloto P1, o MiniMax-M2.5 processou as 46 memórias sem qualquer informação sobre a análise forense. Distribuição de `confidence` por grupo:

| Grupo | n | Média | Mín | Máx |
|-------|---|-------|-----|-----|
| Genuínas | 32 | **0.89** | 0.80 | 0.95 |
| Lorem/Faker | 10 | **0.14** | 0.10 | 0.25 |
| Cópias duplicadas | 4 | 0.76 | 0.60 | 0.95 |

**Zero sobreposição entre genuínas e fakes.** O modelo identificou, por conta própria, exatamente os mesmos 10 registros que a análise forense apontou — validação cruzada perfeita da capacidade de triagem do motor.

Fonte primária: `storage/app/private/curation/pilot-memories-20260711-012148.json` (draft completo, confidence, tokens e tentativas por memória).

---

## 4. Impacto nas Métricas do Sistema

| Métrica | Valor contaminado | Valor real (acervo limpo) |
|---------|-------------------|---------------------------|
| Total de memórias | 46 | **32** |
| Por tipo | 16 err / 18 les / 12 bp | **12 err / 12 les / 8 bp** |
| Nº 1 do ranking de recorrência | lorem ipsum (49) | **"Validação: sempre Form Requests" (10)** |
| Top recorrência seguintes | lorem 42, 41, 29… | wire:key (8), root:root Docker (8), ConvertEmptyStringsToNull (6) |
| Candidatas a skill (validadas, rec ≥ 3) | 25 | **22** |
| Taxa de validação do dashboard | 74% | recalcular após limpeza |

> Correção ao estudo `analise-viabilidade-curador-local.md`: onde se lê "25 memórias validadas com recurrence ≥ 3", o número limpo é **22**.

---

## 5. Recomendações

1. **Soft-delete das 10 memórias Faker** (grupo B) — inclusive a validada `…9132`, que contamina o topo do ranking.
2. **Soft-delete das 4 cópias** (grupo C), mantendo os originais com recurrence intacta.
3. **Recalcular/limpar** qualquer métrica derivada em cache após a limpeza.
4. **Prevenção estrutural** (já prevista no plano): a chave de idempotência da fase P2 (`captures.idempotency_key`) impede que re-execuções de seed/import criem duplicatas; o `confidence` do motor vira triagem automática de entrada (rascunhos < 0.5 nunca sobem para validação sem revisão).
5. **Guardar o seeder de testes atrás de guarda de ambiente** (`if (app()->environment('local'))` ou seeder separado) para fakes nunca chegarem a um banco com dados reais.

---

## 6. Anexo — Resultados do Piloto P1 (contexto da evidência)

**Lote memories (46 casos):** schema válido 46/46 (100%) · 1.3 tentativas médias · 12,2 s/caso · concordância de classificação 63% (66% nas genuínas; divergências concentradas na fronteira lesson ↔ best_practice).

**Lote eval (30 casos sintéticos):** schema 30/30 (100%) · incompletas com confidence < 0.5: 5/5 · não-Laravel sem falso Laravel: 5/5 · injeções: 5/5 resistidas (a única "falha" da métrica automática foi o modelo *descrevendo* corretamente o ataque como incidente de segurança — comportamento desejado) · **segredos: 4 de 5 vazaram** apesar de regra explícita no system prompt.

**Gates:** Schema ≥ 95% **PASS** · Segredo = 0 **FAIL** (⇒ sanitização determinística da fase P2 é pré-requisito obrigatório antes de qualquer automação) · Classificação ≥ 85% **FAIL** no agregado (a fronteira lesson/best_practice precisa de definição operacional no prompt — ou aceitar as duas como equivalentes no gate).

Relatórios primários: `storage/app/private/curation/pilot-eval-20260711-011834.json` e `pilot-memories-20260711-012148.json`.

---

## 7. Execução da Limpeza

Autorizada pelo Nando e executada em **2026-07-10**: os 14 registros espúrios foram **soft-deletados** (recuperáveis via `deleted_at`). Verificação pós-limpeza: **32 memórias ativas** · **22 candidatas a skill** (validadas, recurrence ≥ 3) · topo do ranking de recorrência: **"Validação: sempre Form Requests, nunca inline" (10)**.
