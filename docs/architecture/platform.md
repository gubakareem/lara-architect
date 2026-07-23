# Lara Architect Platform

> The Composer package is the **entry point**.  
> The **platform** is the vision.  
> Keep packages separate so the core stays small and trustworthy.

Product brand:

**Lara Architect — Architecture Memory and Improvement Platform for Laravel**

Composer discoverability stays:

```bash
composer require karim-ashraf/lara-architect
```

Do not rename the core package. Developers find Laravel tools as packages; they adopt platforms over time.

## Mental model

```text
Package  →  Toolkit  →  Platform
```

Same direction as Laravel (framework → ecosystem) and Symfony (components → platform).

```text
                 Lara Architect
                       |
        --------------------------------
        |              |               |
       Core            UI          Integrations
        |              |               |
  lara-architect   lara-architect-ui   future packages
```

## Product layers

| Layer | Role | Where |
| --- | --- | --- |
| **1. Engine** | Understand code | `lara-architect` |
| **2. Workspace** | Developer experience | `lara-architect-ui` |
| **3. Memory** | Remember decisions | inside core |
| **4. Intelligence** | Learn patterns from evidence | inside core |
| **5. Ecosystem** | VS Code · GitHub · CI · AI · Teams | separate packages |

Memory and Intelligence live in core because they are architecture knowledge — not presentation. UI and future adapters **consume** stable read models (`WorkspaceSnapshot`, `GovernanceSnapshot`, `ArchitectureIdentitySnapshot`, `ArchitectureContextEnvelope`, Learning / Collaboration reports). They do not own analysis.

## Package responsibilities

### `karim-ashraf/lara-architect` (Core)

Heart of the platform.

- Architecture analysis · dependency graph · rules
- Standards · events · memory
- Intelligence · guidance · governance
- Evolution · learning · collaboration facts

```bash
composer require karim-ashraf/lara-architect
php artisan architect:workspace
```

Stays: Laravel-compatible · framework-aware at boundaries · **engine-first** internally.

### `karim-ashraf/lara-architect-ui` (Workspace)

Separate repository and Composer package. Developer experience only.

- Workspace shell · React components
- History · Replay · Guidance · Governance · Learning · Collaboration views

```bash
composer require karim-ashraf/lara-architect-ui
# requires karim-ashraf/lara-architect ^1.5
# → /architect/workspace
```

```text
lara-architect-ui  →  lara-architect
lara-architect     ✗  lara-architect-ui   (never)
```

The UI does **not** analyze code. It visualizes knowledge from core (`ArchitectureContextEnvelope`, snapshots, reports). **Core discovers. UI explains. Developers decide.**

### Future packages (ecosystem)

| Package | Surface |
| --- | --- |
| `lara-architect-debugbar` | Laravel Debugbar |
| `lara-architect-vscode` | Editor |
| `lara-architect-github` | PR architecture comments |
| `lara-architect-ai` | AI layer (later — consumes knowledge) |
| `lara-architect-pulse` | Runtime architecture signals |
| `lara-architect-enterprise` | Team features |

## Why not one mega-package?

```text
❌ lara-architect
      ├── UI
      ├── VSCode
      ├── GitHub
      ├── AI
      └── Enterprise
```

Different users need different things:

| Need | Install |
| --- | --- |
| Core only | `lara-architect` |
| Daily DX | `lara-architect` + `lara-architect-ui` |
| Org toolchain | core + UI + GitHub + Enterprise (later) |

## Hard rules

1. **Do not rename** `karim-ashraf/lara-architect`.
2. **Do not ship Workspace UX inside core** (see [ADR-0008](../adr/0008-visualize-architecture-assistant-ux.md)).
3. **Stable contracts outward** — snapshots and public events; adapters stay thin.
4. **Core stays small** — platform growth happens in sibling packages.
5. **One-way dependencies** — consumers (`lara-architect-ui`, Debugbar, VS Code, GitHub, AI) require core; core never requires a consumer.
6. **AI consumes knowledge** created by Memory + Learning + human Collaboration — it does not invent it. Future `lara-architect-ai` consumes **ArchitectureContextEnvelope** only (explain / summarize / navigate). It must not call the analyzer directly, invent rules, findings, or architecture decisions.

7. **AI speaks from architectural memory — never replaces it.** Preserve forever across VISION, AI package README, contributor guides, and integrations.

## Further reading

- [VISION.md](../../VISION.md) — why
- [Workspace specification](workspace.md) — DX contracts
- [ADR-0008](../adr/0008-visualize-architecture-assistant-ux.md) — Workspace package boundary
- [Roadmap](../roadmap.md) — phased delivery
