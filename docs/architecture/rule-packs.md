# Rule packs

Rules describe allowed and denied dependencies between layers. They are **configuration**, not UI plugins or event listeners.

## Principles

- Declarative allow/deny over imperative hooks
- Baselines record accepted debt without disabling rules forever
- Packs should be installable later as ecosystem artifacts — still consumed by the engine, not by the Workspace directly

## Boundaries

- Engine evaluates packs → `AnalysisResult`
- Workspace/Insights **display** violations and drive generators
- Do not put presentation concerns into rule definitions

## Future

Installable rule packs belong to the **Ecosystem** milestone (see [roadmap](../roadmap.md)), after Integration events exist.
