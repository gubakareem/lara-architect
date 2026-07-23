# ADR-0008 — Architecture Workspace (Visualize Pillar)

- **Status:** Accepted
- **Date:** 2026-07-23
- **Depends on:** [ADR-0007](0007-event-bus-for-engine-extensibility.md) (event bus / Integration)
- **Product context:** [VISION.md](../../VISION.md) (why) · this ADR (how)
- **Target:** post–v1.5 (not implemented yet)

## Context

CLI analysis is useful once. Adding more rules has diminishing returns relative to giving developers a place to **improve** architecture while they work.

An **assistant** implies ask → answer. An **Architecture Workspace** implies: *this is where I improve my architecture.* That is a broader, longer-lived product identity than a chat panel or a dashboard.

LaraArchitect’s rare combination is **Generation + Analysis** on one engine — every issue can become an action (generate → preview → apply → verify).

## Product vision

> Lara Architect is an Architecture Workspace for Laravel. It helps teams design, generate, analyze, enforce, understand, and continuously improve the architecture of their applications through a unified engine, intelligent guidance, and safe automated refactoring.

Not “a generator.” Not “an analyzer.” A workspace that can carry the project for years: engine as foundation, workspace as daily DX, future capabilities as slots — not standalone features.

## Decision

### Branding

| Prefer | Avoid as product name |
| --- | --- |
| **Architecture Workspace** | Dashboard |
| Workspace session / Start session | Report / Issue dump |
| Insights | “Charts page” as the brand |

“Dashboard” may describe one Insights surface if needed — never the product. “Architect Assistant” may remain a UI voice inside the workspace; the **category** is Workspace.

### Product pillars

```text
Design → Generate → Analyze → Enforce → Visualize → Integrate → Platform
```

**Visualize** means making architecture actionable in a workspace. Graphs and trends live under **Insights** — one consumer of the engine, not the destination.

### Package family

Brand the **product** as a platform; keep Composer packages separate. Do not merge UI / VS Code / GitHub / AI / Enterprise into core. Full model: [platform.md](../architecture/platform.md).

```text
karim-ashraf/
│
├── lara-architect              ← Core (Composer entry · engine · memory · learning)
├── lara-architect-ui           ← Workspace shell (panels, session, preview/apply)
├── lara-architect-debugbar     ← Notification adapter (future)
├── lara-architect-vscode       ← Editor adapter (future)
├── lara-architect-github       ← PR / Code Scanning (future)
├── lara-architect-ai           ← consumes architecture knowledge (future)
├── lara-architect-pulse        ← runtime signals (future)
└── lara-architect-enterprise   ← team / org (future)
```

Today’s Composer package `karim-ashraf/lara-architect` is **core** — keep that name for discoverability. Do **not** ship the Workspace UX inside core. Downstream packages consume **stable API + ADR-0007 public events**.

### UX layers

```text
Notification
        │
Workspace          ← primary product (where improvement happens)
        │
Insights           ← metrics, trends, graphs, team reports — broader than “charts”
```

| Layer | Surfaces | Role |
| --- | --- | --- |
| **Notification** | Debugbar badge, toasts, request summary | Continuous awareness — tiny |
| **Workspace** | Session, current file, issues, quick actions, preview, apply, verify | Daily work — polish this |
| **Insights** | Metrics, trends, dependency graphs, AI explanations, team reports, history, health | Occasional / managers / deep dive |

**Workspace** replaces the earlier “Interaction” label: everything actionable happens there.

### Workspace surface (daily)

```text
──────────────────────────────────────────────
Lara Architect
Good afternoon, Kareem.

Today's summary
✓ Health increased +2%
⚠ 2 new issues
🟢 5 safe fixes available
Estimated time: 3 min
──────────────────────────────────────────────
Current File · ProductController.php
Architecture · Good · 92%
──────────────────────────────────────────────
Issues
⚠ Direct model dependency
⚠ Missing FormRequest
──────────────────────────────────────────────
Quick Actions
[Fix Safe Issues] [Preview Changes]
[Explain] [Generate Missing Layer]
──────────────────────────────────────────────
```

It must feel like a **workspace**, not a report.

### Architecture Goals

Sessions become goal-oriented (not report-oriented): sprint target (e.g. reach Excellent / 95%), progress trail, remaining Safe/Assisted work, estimated time. See [VISION.md](../../VISION.md).

### Architecture Sessions (differentiator)

Nobody in the Laravel ecosystem owns this well. Gamify progress without cheapening trust:

```text
Today's Session
New Issues · 2
Safe Fixes · 5
Architecture Goal · Reach Excellent (95%)
Estimated · 4 minutes
[Start]
```

Flow per issue:

```text
Issue → Preview → Apply → Pint → PHPStan → (tests) → Done → next issue
```

### Killer workflow

```text
Analyze → Explain → Preview → Apply → Verify → Celebrate
```

**Verify** after apply:

```text
✓ Pint passed · ✓ PHPStan passed · ✓ Tests passed · ✓ Architecture improved
Health: Good 89% → Excellent 93%
```

Celebration closes the loop; without Verify, automation feels reckless.

### Health: human first, number second

Managers like numbers; developers like understanding.

| Primary | Secondary |
| --- | --- |
| Excellent / Good / Needs Attention / Critical | e.g. 91% underneath |

Stars or similar labels are fine if they stay secondary to the plain-language band.

### Safe fix tiers (trust)

| Tier | Meaning | Auto-apply? |
| --- | --- | --- |
| 🟢 **Safe** | Zero behavior change | Yes — “Fix All Safe Issues” |
| 🟡 **Assisted** | Needs review | Preview required |
| 🔴 **Manual** | Requires a design decision | Explain only — cannot auto-apply |

Narrow green tier = trustworthy automation.

### Engagement, not issue dumps

Avoid Sonar-style endless lists. Prefer session framing:

```text
Today · You introduced 2 new issues.
One-click fixes available · Estimated fix time: 4 minutes.
```

### Achievements (progress, not gimmick)

Same psychology as contribution graphs — progress matters:

- No new violations for 14 days
- Controllers under 200 LOC
- Zero circular dependencies
- Service layer complete
- Repository coverage 100%

Achievements live under Workspace / Insights; they never replace Enforce.

### Command palette

- Architect: Start Session
- Architect: Analyze Current File
- Architect: Fix Safe Issues
- Architect: Generate Missing Layers
- Architect: Preview Refactor
- Architect: Explain Violation
- Architect: Open Dependency Graph
- Architect: Open Insights

### Generation + Analysis

```text
Missing Repository → Generate Repository → Update Service → Preview → Apply → Verify
```

**Never modify without Preview → Apply** (except narrowly auditable 🟢 Safe batch). Listeners remain observational (ADR-0007).

### UI adapter contract

Notification · Workspace · Insights adapters. Implementations: Debugbar, UI, VS Code, GitHub, CLI. Core stays presentation-agnostic.

## Consequences

- **Architecture Workspace** is a category that can absorb AI, graphs, Sessions, achievements, and enterprise Insights without renaming the product every year.
- Package family matches adapters; core stays SemVer-stable.
- Sessions + Verify + Celebrate make improvement addictive and safe.
- Human health bands + Safe tiers build trust.
- Insights (not Visualization) names the analytics layer accurately.

## Non-goals

- Shipping Workspace UX inside core
- Branding as “dashboard”
- Unsupervised AI / auto-apply of 🟡 or 🔴 fixes
- Achievements that weaken Enforce seriousness
- Competing primarily as Pulse/Telescope replacement
- Building **Enterprise** now — future direction only, after open-source proves the architecture

## Ecosystem milestones

```text
Foundation → Experience → Automation → Platform
```

(See [roadmap](../roadmap.md). Version tags are secondary.)

| Milestone | Theme |
| --- | --- |
| **Engine** (Foundation) | Graph, rules, baseline, CLI — done |
| **Integration** | Event bus + public lifecycle events (v1.5) |
| **Visualize / Experience** | Architecture Workspace + package family; Sessions; Goals; Safe fixes |
| **Tooling** | VS Code, GitHub Action, SARIF |
| **Automation** | Suggestions · Rector · AI (after Replay) |
| **Platform** | Packs · Telemetry · Enterprise (future direction) |

Preserve v1.4 discipline: **stable public contracts, replaceable internals, immutable outputs.** Workspace packages consume those — they never mutate `AnalysisResult` from listeners.
