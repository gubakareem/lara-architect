# Maintainer notes

> For future you. Things nobody else should have to rediscover.  
> Contributors start at [VISION.md](VISION.md). Users start at the [README](README.md).

## Release checklist

- [ ] `composer format` (Pint)
- [ ] `composer analyse` (PHPStan / Larastan)
- [ ] `composer test` (PHPUnit — full suite)
- [ ] Update `[Unreleased]` → version section in `CHANGELOG.md`
- [ ] Verify **public API** surface (below) — no accidental breaks
- [ ] Review **ADR impact** — does this change need a new ADR or an amendment?
- [ ] Check **backward compatibility** (SemVer; BC aliases; config merge)
- [ ] Docs still match behavior (`README`, `docs/`, `VISION.md` if product intent shifted)
- [ ] Tag release (`vX.Y.Z`) and push tag
- [ ] Packagist / GitHub release notes as needed

## Public API (stable — SemVer)

Treat breaking changes here as **major** (unless clearly experimental and documented).

### Architecture engine

| Symbol | Role |
| --- | --- |
| `ArchitectureEngine` | Entry point for analyze / lint |
| `AnalysisResult` | Immutable analysis output |
| `LayerRegistry` | Layer definitions |
| `ArchitectureRule` | Rule contract |
| `RulePack` | Pack of rules |
| `DependencyExtractor` | Extraction contract |
| `Renderer` | Report rendering contract |
| `MetricCalculator` | Metrics contract (plugins later) |
| `SuggestionProvider` | Suggestions contract (plugins later) |
| Value objects / IDs used in public results | e.g. `Violation`, `Hotspot`, `Baseline`, `RuleId`, `LayerId`, `NodeId`, … |

### Workspace read model (Experience — evolving)

| Symbol | Role |
| --- | --- |
| `WorkspaceService` | Builds `WorkspaceSnapshot` from analysis + context |
| `FixProposalService` | Builds `FixProposal` for Preview (no Apply yet) |
| `WorkspaceSnapshot` | Immutable payload (`schema_version` 1.1) for UI/CLI/adapters |
| `FixProposal` · `FileChange` · `VerificationPlan` · `FixConfidence` · `FixRisk` | Preview / verification contract |
| `WorkspaceContext` / `Finding` / `WorkspaceIssue` / `IssueExplanation` / `WorkspaceAction` / `WorkspaceHealth` | Domain concepts |
| `WorkspaceId` · `ContextId` · `FindingId` · `IssueId` · `ActionId` · `SessionId` · `FixProposalId` | Typed identities |

Treat additive snapshot fields as minor; removing/renaming fields as major once declared stable in a release.

### Generation (application-facing)

| Symbol | Role |
| --- | --- |
| Generator contract + registered patterns | `make:module` / feature / wizard surface |
| Published config keys under `lara-architect.generation` | Presets, namespaces, generators |
| Runtime bases | e.g. `ArchitectRepository`, `ArchitectService`, … (+ BC aliases) |

### Extension events (when ADR-0007 ships)

Public lifecycle events + `ArchitectureEvent` contract — SemVer-stable per ADR-0007.

## Internal (no compatibility promise)

May change in any minor/patch without notice. Do not depend on these from app code or sibling packages.

| Symbol | Role |
| --- | --- |
| `DependencyGraph` | Graph structure |
| `RegexExtractor` | Default extractor implementation |
| `FileScanner` | Filesystem scanning |
| `EngineFactory` wiring details | Construction internals |
| Scanners / use-case helpers not exported as API | — |
| Stub file paths / private generator helpers | — |

When unsure: if it is not documented as public here or in the README/engine docs, treat it as **internal**.

## Product discipline

1. Read [VISION.md](VISION.md) and [docs/philosophy.md](docs/philosophy.md) before large changes.
2. Prefer strengthening the **engine** or the **Workspace experience** over unrelated features.
3. **Enterprise** stays a future direction until the open-source edition proves the architecture — do not build it early.
4. Update the [ADR index](docs/adr/README.md) when adding a decision record.

## Useful links

- [Roadmap](docs/roadmap.md)
- [Architecture notes](docs/architecture/)
- [ADR index](docs/adr/README.md)
- [Contributing](CONTRIBUTING.md)
