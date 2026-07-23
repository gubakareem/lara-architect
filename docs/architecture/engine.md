# Architecture Engine

The engine is the foundation of Lara Architect: framework-agnostic analysis and enforcement. Artisan commands and future Workspace packages are **adapters**.

## Responsibilities

- Build a dependency graph from source
- Evaluate declarative layer rules (+ baselines)
- Produce an immutable `AnalysisResult`
- Expose stable APIs for lint/analyze consumers

## Non-responsibilities

- Rendering UIs
- Mutating application code
- Owning plugin marketplaces

## Design rules

- **Stable public contracts, replaceable internals**
- Outputs are immutable; listeners must not mutate results ([ADR-0007](../adr/0007-event-bus-for-engine-extensibility.md))
- Rule packs are configuration, not event listeners

## Related

- [Events](events.md) — how integrations observe analysis
- [Rule packs](rule-packs.md) — how rules are packaged
- [VISION.md](../../VISION.md) — why the engine comes first
