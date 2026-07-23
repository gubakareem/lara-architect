# Events

Integration is event-driven. Decision record: [ADR-0007](../adr/0007-event-bus-for-engine-extensibility.md).

## Domain vs extension

| Kind | Stability |
| --- | --- |
| Internal lifecycle events | No compatibility promise |
| Public extension events | SemVer-stable API |

## Public milestones (illustrative)

Prefer coarse events with rich payloads over thousands of per-rule dispatches:

- `ArchitectureAnalyzed`
- `ViolationsCollected`
- `BaselineGenerated`
- `ReportRendered`

## Contract

Public events implement a shared contract exposing immutable `analysis(): AnalysisResult`.

## Listener policy

Listeners are **observational**. Analyze completes first; events carry immutable data; listeners must not influence engine execution.
