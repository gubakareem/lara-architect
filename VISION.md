# Lara Architect Vision

> Read this before opening the codebase. ADRs explain *how* we build. This document explains *why* Lara Architect exists.

**Product brand:** Lara Architect — Architecture Memory and Improvement Platform for Laravel

**Composer entry point:** `karim-ashraf/lara-architect` (do not rename)

**Identity:** Lara Architect is an **architecture continuity system** — it preserves architectural intent and helps teams safely evolve Laravel applications over time.

**Differentiator:** Lara Architect remembers *why* the architecture exists and helps developers continue it safely. Interfaces (linter, refactoring, AI assistant, dashboard) are not the product.

**Frozen invariant:**

> Access existing understanding — never invent it.

**Permanent principle:**

> AI speaks from architectural memory — never replaces it.

The package is how developers discover and install it. The platform is the vision. Same path Laravel and Symfony took: package → toolkit → platform. Core stays small and trustworthy; the platform grows around it.

## Mission

Help Laravel teams continuously **build, understand, improve, and remember** software architecture — not just generate or analyze it.

## North Star

> A developer should be able to improve the architecture of a Laravel application every day without becoming an architecture expert.

Whenever you add a feature, ask: **Does this make architecture easier to improve?** If not, it probably belongs elsewhere.

## Constitution (frozen foundation)

| Owns | Responsibility |
| --- | --- |
| **Analyzer** | Code → Facts |
| **Engine** | Facts |
| **Memory** | History |
| **Humans** | Intent · Decisions |
| **AI** | Explanation · Communication |

```text
Code → Analyzer → Facts → Memory → Intent (humans)
                                      ↓
                    ArchitectureContextEnvelope v1.0
                                      ↓
                    AI · IDE · PR · Workspace · CLI
```

No layer may silently become the authority.  
Everything **above** the envelope is replaceable.  
Everything **below** the envelope is the source of truth.

### Three permanent principles

1. **Evidence before intelligence** — No insight without a traceable source.  
   `Observation → Evidence → Understanding → Explanation`
2. **Intent before automation** — Automation assists a decision; it does not replace one.  
   `Developer intention → Decision → Controlled change → Verification`
3. **Memory before AI** — AI is a translator of understanding, not the creator of truth.  
   `Architecture memory → Context envelope → AI explanation`

### Feature gate (long-term filter)

Every future proposal must answer:

> Which existing architectural memory does this feature help a developer access?

| Fit | Example | Why |
| --- | --- | --- |
| ✅ | VS Code: “Why should I change this file this way?” | Accesses Context · Decisions · History · Standards |
| ✅ | PR: “Does this change follow our direction?” | Accesses Identity · Governance · Evolution |
| ✅ | AI: “Explain this module.” | Accesses Brief · Context · Rationales |
| ❌ | “Automatically redesign my architecture.” | Creates new truth — outside the constitution |

If the answer is unclear, it probably does not belong in Lara Architect.

### Gate for future intelligence

Before adding any AI feature:

> Does this help developers understand existing architectural intent?

- **Yes** → fits Lara Architect  
- **Creates new architectural truth** → outside the AI layer

When AI arrives, it is not the product. It is the voice of the product's accumulated architectural understanding.

### What comes next — Architecture Moments

The engine has earned the right to stop growing. Next work is driven by **developer moments of uncertainty**, not by picking a surface first.

Core question:

> When does a developer need to know *why* before they change *what*?

Five moments (see [Architecture Moment Map](docs/architecture/moments.md) · [roadmap — Phase 18](docs/roadmap.md#phase-18--architecture-moments-product-discovery)):

1. Before touching unfamiliar code *(highest value)*
2. Creating a new feature
3. Reviewing a change
4. Joining a project
5. Making a design decision

Prioritize: **Context at code boundary → PR understanding → Onboarding → AI** (last).

**Next milestone:** Architecture Moment #001 — only when a developer changes approach because of context (*“I was about to do X, but after seeing why this exists, I did Y instead.”*). See [moments.md](docs/architecture/moments.md). Observe → Capture → Learn → Decide.

## Identity progression

Lara Architect has evolved through coherent identities — not a pile of features:

| Stage | What it means |
| --- | --- |
| **1. Code Generator** | Scaffold Laravel architecture |
| **2. Architecture Engine** | Understand and enforce rules |
| **3. Architecture Workspace** | Daily environment to improve the codebase |
| **4. Architecture Memory & Learning** | Remember what happened — and what worked |
| **5. Architecture Platform** | Ecosystem of packages: UI, editors, CI, AI, teams |

We are past “a Composer package that runs commands.” Core remains a Laravel-compatible package; the product is a **platform** with multiple packages. See [docs/architecture/platform.md](docs/architecture/platform.md).

## Principles

### Architecture is continuous

It is designed, monitored, and evolved throughout the life of a project — not a one-time setup step.

### Guidance before automation

- Explain every issue.
- Preview every change.
- Never surprise developers.

### Trust through verification

- Every automated improvement should be verifiable.
- Safe fixes should preserve behavior.
- Verification (Pint, PHPStan, tests, re-analyze) is part of the workflow — not an afterthought.

### Engine first

- Every capability should be powered by the Architecture Engine.
- The UI is a **consumer**, not the owner.
- Listeners are observational; `AnalysisResult` stays immutable.

### One architecture, many surfaces

CLI · Architect Workspace · VS Code · GitHub · CI · future integrations — same engine, same contracts, different adapters.

### AI speaks from architectural memory — never replaces it

Preserve forever. AI may explain, summarize, translate, onboard, and navigate existing knowledge. It must not create rules, findings, or decisions, bypass evidence, scan independently, or mutate code autonomously. See [ArchitectureContextEnvelope](docs/architecture/workspace.md#architecturecontextenvelope--pre-ai-boundary-).

## What we are building toward

### Workspace guiding principle

> Every screen should help a developer improve the code they are currently touching.

Open on **current context** (module + related files + issues + safe fixes for the next few minutes) — never a homepage of hundreds of project-wide issues. Details: [docs/architecture/workspace.md](docs/architecture/workspace.md).

### Architecture Workspace (daily DX)

Not a report. Not a dashboard brand. A place where developers improve architecture:

- Today’s summary (health delta, new issues, safe fixes, estimated time)
- Current-file context
- Quick actions: Fix Safe · Preview · Explain · Generate Missing Layer
- **Architecture Sessions** — goal-oriented loops (Start → Preview → Apply → Verify → next)

### Architecture Goals

Health alone is report-oriented. Goals make the Workspace purposeful:

```text
Sprint Goal · Reach 95% (Excellent)
Progress · 91 → 92 → 93
Remaining · 3 safe fixes · 1 assisted fix
Estimated · 5 minutes
```

### Safe fix tiers

| Tier | Meaning |
| --- | --- |
| 🟢 Safe | Zero behavior change — may auto-apply |
| 🟡 Assisted | Needs review — preview required |
| 🔴 Manual | Requires a design decision — explain only |

### Team Mode (shared ownership)

Not management theater — shared awareness:

```text
Today's Architecture · Health 94%
New Violations · 2 · Fixed Today · 18
Most Improved · Orders · Needs Attention · Billing
```

### Architecture Replay

Fact-based history before language generation:

```text
Yesterday · 89% → 91% → 94%
At 91%: Repository introduced · Circular dependency removed · …
```

Replay keeps Insights and future AI grounded in what actually changed.

### Foundation frozen before AI

The foundation is complete through **ArchitectureContextEnvelope v1.0**. Prefer memory, context, and factual Insights before a generic AI assistant. Next decisions are *where* intelligence appears (IDE · PR · Workspace · onboarding · conversations) — not whether understanding exists.

## Package family (direction)

Do **not** merge UI, VS Code, GitHub, AI, and Enterprise into one package. Different users need different surfaces.

```text
karim-ashraf/
│
├── lara-architect              ← Core (entry point · Composer discoverability)
├── lara-architect-ui           ← Workspace DX (React · History · Guidance)
├── lara-architect-debugbar     ← future
├── lara-architect-vscode       ← future
├── lara-architect-github       ← future
├── lara-architect-ai           ← future (voice of memory · never replaces it)
├── lara-architect-pulse        ← future
└── lara-architect-enterprise   ← future
```

| Who | Installs |
| --- | --- |
| Backend developer | core only |
| Team | core + UI |
| Company | core + UI + GitHub + Enterprise (later) |

Core stays lean and trustworthy. Surfaces evolve independently. “Dashboard” is never the product name.

## Discipline going forward

**Every new feature should strengthen the engine or improve the developer’s daily workflow inside the Workspace.**

First ask: **Which existing architectural memory does this feature help a developer access?** If unclear, it probably does not belong here.

That single rule keeps Lara Architect a cohesive platform instead of a collection of unrelated capabilities.

## Further reading

- [Philosophy](docs/philosophy.md) — how we make trade-offs
- [Roadmap](docs/roadmap.md) — milestones, not feature dumps
- [Architecture notes](docs/architecture/) — engine, workspace, events, rule packs, UI
- [ADRs](docs/adr/) — accepted decisions (how we build)
- [Contributing](CONTRIBUTING.md) — how to ship changes
