# Architecture Decision Index

ADRs record **how** we build. Read [VISION.md](../../VISION.md) for **why**.

| ADR | Title | Status |
| --- | --- | --- |
| [ADR-0007](0007-event-bus-for-engine-extensibility.md) | Event bus for engine extensibility (Integration) | Accepted |
| [ADR-0008](0008-visualize-architecture-assistant-ux.md) | Architecture Workspace (Visualize) | Accepted |

## Notes

- Formal ADR numbering starts at **0007**. Earlier choices (presets, generator pipeline, engine public vs internal boundaries) live in code, changelog, and [MAINTAINERS.md](../../MAINTAINERS.md); backfill older ADRs only if history must be explicit.
- Prefer amending or superseding an ADR over silent contradiction.
- Do not add ADRs for routine features — use them for decisions that constrain the platform.

## Adding an ADR

1. Copy the style of an existing ADR (Context → Decision → Consequences).
2. Add a row to this index.
3. Link from [docs/index.md](../index.md) when it is a milestone decision.
4. Mention under `[Unreleased]` in `CHANGELOG.md` if contributors need to know.
