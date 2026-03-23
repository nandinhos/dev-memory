# Spec: Premium UI Effects — Architect Sinistro (v2.7 Final - Nando Edition)

**Data:** 2026-03-23
**Status:** Aprovado e Consolidado
**Projeto:** dev-memory-laravel
**Escopo:** Implementação de efeitos visuais premium, feedbacks interativos e refinamento total do DNA visual com harmonia de linhas.

---

## 1. Identidade Visual (DNA)

A identidade visual combina a precisão técnica do Arquiteto com a urgência industrial e a identidade de elite do desenvolvedor.

### 1.1 Logotipo e Header (Harmonia de 80px)
- **Alinhamento:** O bloco do logotipo na sidebar e o Header da aplicação devem possuir exatamente a mesma altura (**80px**), criando uma linha visual contínua.
- **Logotipo:** Texto `DEV-MEMORY`.
  - `DEV`: Verde Neon (`#39FF14`) com `text-shadow`.
  - `-MEMORY`: Branco puro (`#FFFFFF`).
  - Peso: **Extra-Bold (900)**. Tamanho: **26px**.
  - Fundo: Preto Sólido (`#000000`).
- **Navegação (Header):**
  - Título da página em negrito (`font-900`), sem sublinhado.
  - Descrição técnica em *itálico* abaixo do título (`#64748B`).

### 1.2 Identidade do Usuário (NANDO DEV)
- **Nome:** `NANDO DEV` em uppercase, peso 900.
- **Função:** `SYSTEM_ROOT` em Verde Neon sobre fundo preto.
- **Avatar:** Círculo Verde Neon com borda preta e iniciais `ND`. Sombra de 4px sólida preta.

### 1.3 Sidebar (Estilo Bege)
- **Fundo:** Bege Claro (`#F5F5DC`).
- **Menu:** Tipografia Uppercase, peso 900. Botão ativo em Amarelo Vibrante (`#FDE047`) com sombra de 4px.
- **Scrollbar:** Customizada Neo-Brutalist (Thumb preto de 10px).

---

## 2. Componentes Premium

### 2.1 Code Block (macOS + Scanlines + Flash)
- **Titlebar:** Fundo Marinho Escuro (`#2a2a3e`), botões semáforo clássicos.
- **Botão Copiar:** Neon Green Ghost. Ao clicar, emite um **Flash Branco (150ms)** e muda para fundo Neon Green com texto preto ("COPIADO!") por 2s.
- **Corpo:** Fundo Catppuccin Mocha (`#1e1e2e`) com **Scanlines CRT** (opacidade 0.05).
- **Sombra:** 8px sólida preta.

### 2.2 Separador de Validação (Caution Scroll)
- **Visual:** Fita amarela (`#FDE047`) com bordas de 3px.
- **Animação:** Texto "VALIDATED ARCHITECT ///" em peso 900, rolando infinitamente.

### 2.3 Toast de Feedback (Glitch Top-Right)
- **Posição:** `fixed top-8 right-8`.
- **Visual:** Fundo Verde Neon, texto preto, sombra de 10px e scanlines CRT.

---

## 3. Detalhes do Card de Memória

- **Cabeçalho:** Fundo Azul Pastel suave (`#E0E7FF`) com badge Roxo (`#6366F1`).
- **Rodapé de Status:** Exibição funcional de Validação (Verde) e Urgência (Vermelho).
- **Botão de Ação:** Azul Vibrante (`#60A5FA`), texto preto, sombra de 4px.
