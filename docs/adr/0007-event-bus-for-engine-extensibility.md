# ADR-0007 — Introduce an Event Bus for Engine Extensibility

- **Status:** Accepted
- **Date:** 2026-07-23
- **Target:** v1.5 (not implemented yet)

## Context

The architecture engine needs extension points for reporting, metrics, suggestions, and future plugins. Introducing a full plugin API now would prematurely freeze interfaces that are still evolving.

Events are easier to evolve than hook interfaces. An event bus is the right intermediate step before a plugin ecosystem.

## Decision

Introduce an **internal event bus**. Emit a **small set of public lifecycle events** after meaningful milestones. Events carry immutable value objects (`AnalysisResult` and related models). Consumers register listeners; the engine remains unaware of them. Rule packs remain configuration, not event listeners.

```
                 ArchitectureEngine
                         │
                 AnalysisResult
                         │
              Public ArchitectureEvent
                         │
          ┌──────────────┼──────────────┐
          │              │              │
     Renderer      Metrics Plugin   Suggestions
```

### Domain events vs extension events

| Kind | Audience | Stability | Examples |
| --- | --- | --- | --- |
| **Domain / lifecycle** | Engine internals | May change in v1.x | `AnalysisCompleted`, `RuleEvaluationCompleted`, `MetricsCalculated` |
| **Extension (public API)** | Listeners / future plugins | Stable; keep surface small | `ArchitectureAnalyzed`, `ViolationsCollected`, `BaselineGenerated`, `ReportRendered` |

Internal events will change as the engine evolves. Extension events become part of the public API. Keeping the public surface small preserves freedom to refactor the engine in v1.x.

### Prefer milestones over chatty dispatches

Do **not** emit an event per rule evaluation (that can fire thousands of times on a large project). Prefer milestone events:

- `ArchitectureAnalyzed`
- `ViolationsCollected`
- `MetricsCalculated` (internal or public as needed)
- `ReportRendered`

If a consumer needs per-rule detail, put the **collection of evaluations** in the event payload — one dispatch, richer data, simpler mental model.

### Explicit event contract

```php
interface ArchitectureEvent
{
    public function analysis(): AnalysisResult;
}
```

Every public event exposes the same immutable analysis context plus event-specific data. Listeners always receive `AnalysisResult` whether they care about analysis, violations, metrics, or rendering.

### Compatibility

Public extension events are part of LaraArchitect’s stable API and follow semantic versioning.

- New events may be added in minor releases.
- Existing public events will not be removed or have their payload contracts changed before the next major release.
- Internal engine events are implementation details and carry no compatibility guarantees.

That answers: *“Can I build a plugin against this?”* — yes, against the public extension event set only.

### Listener execution policy

Listeners are **observational**.

- They must not mutate `AnalysisResult` or influence engine execution.
- The engine completes analysis first, then emits immutable events.

Flow:

```
Analyze
    ↓
AnalysisResult (immutable)
    ↓
Events
    ↓
Listeners
```

Not:

```
Analyze → Listener → Modify result → Next listener
```

The latter becomes hard to reason about and test. One-way data flow keeps the engine deterministic.

## Consequences

- New capabilities (SARIF, Mermaid, GitHub Actions, suggestion providers, metrics) can be added without modifying `ArchitectureEngine`.
- A later plugin API can sit on top of the event bus rather than replacing it.
- The engine stays closed for modification and open for extension (OCP).
- Domain events stay free to change; only the small public set is a compatibility commitment.
- Contributors know what they can rely on (public events + SemVer) and what they must not do (mutate results from listeners).

## Ecosystem milestones (post-v1.4)

Prefer product milestones over “feature dump” versioning:

| Milestone | Theme |
| --- | --- |
| **Engine** (v1.4) | Graph, rules, baseline, renderers — done |
| **Integration** (v1.5) | Event bus + public lifecycle events (integration points, not a plugin marketplace yet) |
| **Tooling** | VS Code, GitHub Action, SARIF |
| **Ecosystem** | Rule packs, metrics packs, suggestion providers |
| **Platform** (v2.0) | Full architecture platform |

```
Engine → Integration → Tooling → Ecosystem → Platform
```

v1.5 enables integration points; the ecosystem comes afterwards. LaraArchitect accumulates a stable core first — stable public contracts, replaceable internals, immutable outputs — then a growing ecosystem.
