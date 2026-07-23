# UI adapters

The engine does not know how results are shown. Surfaces implement a small adapter contract.

## Surfaces (direction)

| Package | Role |
| --- | --- |
| `ui` | Architecture Workspace shell (Inertia + React preferred) |
| `debugbar` | Notification layer |
| `vscode` | Editor + command palette |
| `github` | PR checks / annotations |
| `cli` | Artisan / CI |

## Composition

```text
Workspace → Panels → Widgets → Actions
```

Actions are first-class (Explain, Generate, Preview, Apply, …) — not ad-hoc controller methods. See [workspace.md](workspace.md).

## Adapter concerns (illustrative)

- Health / notification summaries
- Issue and suggestion lists
- Preview and fix requests
- Insights views (graphs, trends, replay later)

## Rules

- **Context-first** — open on current module/file, not project-wide dumps
- Preview before Apply (except narrowly defined Safe batch)
- Verify after Apply when possible
- Never mutate `AnalysisResult` from a listener

See [workspace.md](workspace.md) and [ADR-0008](../adr/0008-visualize-architecture-assistant-ux.md).
