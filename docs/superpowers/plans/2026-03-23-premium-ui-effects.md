# Premium UI Effects (Architect Sinistro) Implementation Plan - Nando Edition

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar efeitos visuais premium e refinar o DNA visual (v2.7) com harmonia de 80px e identidade NANDO DEV.

**Architecture:** Refinamento da camada de apresentação (Blade + CSS) e interatividade local via Alpine.js.

---

### Task 1: Harmonia de Linhas e Identidade (80px & NANDO DEV)

**Files:**
- Modify: `resources/css/app.css`
- Modify: `resources/views/layouts/app.blade.php`

- [ ] **Step 1: Ajustar alturas no CSS e Layout**
Configurar altura de 80px para o container do logo na sidebar e para o header.
```css
.sidebar-logo-block { height: 80px; display: flex; align-items: center; justify-content: center; background: #000; }
.app-header { height: 80px; display: flex; align-items: center; justify-content: space-between; border-bottom: 4px solid #000; }
```

- [ ] **Step 2: Implementar Identidade NANDO DEV**
Atualizar a área de perfil no layout com o nome, função e avatar ND.

- [ ] **Step 3: Commit**
```bash
git add resources/css/app.css resources/views/layouts/app.blade.php
git commit -m "style: implement 80px line harmony and NANDO DEV identity"
```

---

### Task 2: Componentes Premium e Efeitos CRT

**Files:**
- Create: `resources/views/components/neo/toast.blade.php`
- Modify: `resources/views/components/neo/code-block.blade.php`

- [ ] **Step 1: Criar Toast Glitch com Scanlines**
- [ ] **Step 2: Atualizar Code Block com macOS style e CRT overlay**
- [ ] **Step 3: Commit**
```bash
git add resources/views/components/neo/toast.blade.php resources/views/components/neo/code-block.blade.php
git commit -m "feat: add premium components with CRT scanlines and glitch effects"
```

---

### Task 3: Refinamento do Memory Card e Separador

**Files:**
- Modify: `resources/views/components/neo/memory-card.blade.php`

- [ ] **Step 1: Implementar Separador Caution e Header Pastel**
- [ ] **Step 2: Commit**
```bash
git add resources/views/components/neo/memory-card.blade.php
git commit -m "style: refine memory card with caution separator and pastel header"
```
