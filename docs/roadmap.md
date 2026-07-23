# Roadmap

Vision first. Version numbers are secondary. See [VISION.md](../VISION.md).

## North Star test

Ship only what makes architecture **easier to improve every day** without requiring an architecture expert.

**Feature checkpoint:** Which existing architectural memory does this feature help a developer access? If unclear, it probably does not belong here.

## Roadmap transition (foundation frozen)

```text
Architecture Foundation
██████████ COMPLETE

Discovery Discipline
██████████ COMPLETE

First Validated Moment
░░░░░░░░░░  ← now (evidence collection)

First Surface
░░░░░░░░░░

Phase 19 — Context at Code Boundary
░░░░░░░░░░ (after proven moment)

Phase 20 — Team Change Understanding
░░░░░░░░░░

Phase 21 — AI Language Layer
░░░░░░░░░░
```

Lara Architect is an **architecture continuity system** with a **product discovery discipline**.

**Hypothesis (testable):** There is a recurring developer moment where existing architectural memory can prevent uncertainty before change.

Product loop: Uncertainty → Moment → Existing Memory → Context Delivery → Measured Value → Choose Surface.

## Phase 18 — Architecture Moments ✅

Discovery filter. Artifact: [Architecture Moment Map](architecture/moments.md).

### Evidence collection (now)

**Constraints:** No sentence → no surface. No changed decision → no Moment #001. No evidence → no roadmap shift. Restraint until then.

**Discipline:** Observe → Capture → Learn → Decide. Keep **Fact / Interpretation / Decision** separate.

**Moment #001:** all of unfamiliar · uncertainty · context · intended approach changed · can explain why · natural surface signal. Less = signal only.

## Workspace guiding principle

> Every screen should help a developer improve the code they are currently touching.

Details: [architecture/workspace.md](architecture/workspace.md).

## Product roadmap

```text
Foundation                          ✓ Generate
                                    ✓ Analyze
                                    ✓ Enforce
                                    ✓ Engine

────────────────────────────────────────────────

Experience                          Workspace
                                    Sessions
                                    Goals
                                    Safe Fixes
                                    Replay
                                    VS Code
                                    GitHub

────────────────────────────────────────────────

Automation                          Suggestions
                                    Rector bridge
                                    AI (after Replay)

────────────────────────────────────────────────

Platform                            Plugins / packs
                                    Telemetry
                                    Enterprise ← future direction only
```

| Horizon | Focus | Notes |
| --- | --- | --- |
| **Foundation** | Generate · Analyze · Enforce · Engine | Shipped in core (v1.4+) |
| **Experience** | Workspace · Sessions · Goals · Safe fixes · Replay · VS Code · GitHub | [ADR-0008](adr/0008-visualize-architecture-assistant-ux.md); Integration events ([ADR-0007](adr/0007-event-bus-for-engine-extensibility.md)) |
| **Automation** | Suggestions · Rector · AI | AI **after** Architecture Replay |
| **Platform** | Packs · Telemetry · Enterprise | Enterprise = future direction only |

## Workspace build phases

Think **developer workflow**, not pages. Full notes: [architecture/workspace.md](architecture/workspace.md).

| Phase | Focus | Outcome |
| --- | --- | --- |
| **1 — Shell** (~2–3 weeks) | Layout developers love opening | Today · Explorer · Current File · Actions — no AI, charts, or auto-fixes |
| **2 — Context explorer** | Click class → contextual view | Health, deps, issues, metrics for *this* file/module |
| **3 — Explain** | WHY on every issue | Rationale · benefits · suggested fix (learn, don’t only patch) |
| **4 — Preview** | Diff before mutation | Preview · Apply · Cancel only |
| **5 — Safe fixes** | Wire generators | 🟢 Safe only; Assisted/Manual stay gated |
| **6 — Insights** | Charts · history · trends · Replay | Managers & graphs — **last**, not first |

### Suggested release mapping

| Version | Delivers |
| --- | --- |
| **v1.5** | Integration events + Workspace shell + Explorer + Context + Explain |
| **v1.6** | Preview · Apply · Safe fixes |
| **v1.7** | Replay · Sessions · Goals |
| **v1.8** | Graph · Mermaid · Dependencies (Insights depth) |
| **v2.0** | AI assistant · Suggestions · Refactoring · Team Workspace |

Versions track Experience progress; the phase table is the source of truth if tags slip.

## Near-term sequence

1. **Integration** — event bus + public extension events (feeds Workspace)
2. **Vertical slice (core)** — `WorkspaceService` + `architect:workspace` (Analyze → Snapshot → Show → Explain)
3. **React Phase 1 + 1.5 (ui package)** — Context Workspace shell + intelligence (breadcrumb, priority, related neighborhood)
4. **Phase 2 Preview (contract)** — `FixProposal` + summary + reasoning + viewed/reviewed lifecycle
5. **Phase 2 UI** — React decision screen consuming FixProposal only
6. **Phase 2.1 — Change Understanding** — ChangeSet · Navigator · Architecture Impact · comprehension diff ✅
7. **Phase 3 — Controlled Change** — ChangeExecution · Start Improvement · Verify gate · Session ✅
8. **Phase 3.1 — Improvement Confidence** — post-session signal · Success Rate · Timeline model ✅
9. **Phase 4 — Architecture Memory** — Event Stream · Baseline · Replay v1 · History panel ✅
10. **Phase 4.1 — Architecture Story** — correlation chains · ImprovementStory · ArchitectureTrend ✅
11. **Architecture Decision Memory** — searchable Story decisions by file/context ✅
12. **Phase 5 — Architecture Intelligence** — read-only projections (improved areas · repeated problems · drift · patterns) ✅
13. **Phase 5.1 — Intelligence Explainability** — observed · why · evidence · over time · insight confidence ✅
14. **Architecture Vocabulary** — internal concept aliases (Service Extraction, …) ✅
15. **Phase 6 — Architecture Guidance** — Evidence → next improvement (not AI) ✅
16. **Phase 6.1 — Guided Improvement Journey** — Why now · Explore · Guidance → Proposal bridge ✅
17. **Guidance Decision Memory** — viewed / accepted / dismissed events ✅
18. **Phase 7 — Architecture Standards** — concept → principle + evidence (semantic layer) ✅
19. **StandardEvidence + versions** — trust through proof · standard v1.0 ✅
20. **Phase 8 — Architecture Governance** — alignment with valued standards (developer feedback) ✅
21. **GovernanceSnapshot** — stable read contract (Workspace · CLI · future surfaces) ✅
22. **Phase 9 — Architecture Evolution** — Direction · Trajectory · Momentum · Regressions ✅
23. **ArchitectureChangeIntent** — intentional evolution vs accidental drift ✅
24. **Phase 10 — Architecture Learning** — successful patterns · risks · preferred paths ✅
25. **LearningEvidence** — why we learned this (attempts · success · contexts · health delta) ✅
26. **Phase 11 — Architecture Collaboration** — Notes · Rationales · transferable human knowledge ✅
27. **Architecture Ownership + KnowledgeMap** — knowledge home · standards ↔ human docs ✅
28. **Phase 12 — Architecture Knowledge Transfer** — Onboarding · Context Brief · living history ✅
29. **Phase 13 — Architecture Questions** — deterministic ask → rationale/replay/ownership/standards/learning ✅
30. **Question Intent + Answer Sources** — typed intent · traced SourceType · ask is read-only ✅
31. **Phase 14 — Architecture Conversations** — discuss → decide → rationale · DecisionLifecycle ✅
32. **Decision Alternatives + DecisionHistory** — rejected options · decision-only trail ✅
33. **Phase 15 — Architecture Identity** — living system personality (not a grade) ✅
34. **Identity Snapshot + inertia + history** — stable contract · slow evolution ✅
35. **Phase 16 — Architecture Communication** — Architecture Brief · Audience · living transfer ✅
36. **Phase 17 — Architecture Context** — before you touch this exact thing ✅
37. **ArchitectureContextEnvelope** — stable pre-AI boundary for adapters ✅
38. **Foundation freeze** — constitution locked · AI gate defined ✅
39. **Phase 18 — Architecture Moments** — discovery discipline · Moment Map ✅
40. **First Validated Moment** — evidence: “I would have changed this differently” ← **now**
41. **Phase 19 — Context at Code Boundary** — experiments after proven moment (CLI → page → overlay)
42. **Phase 20 — Team Change Understanding** — PR / collaboration architecture fit
43. **Phase 21 — AI Language Layer** — explain / summarize / onboard from Envelope
44. **Onboarding Experience** — Brief as first impression (may ship with 19–20)

Explicitly postponed until a moment is proven: full IDE extension · GitHub bot · Autonomous Improvement · Chatbot UX · Architecture scoreboards · Enterprise reports.

## Package / platform discipline

See [architecture/platform.md](architecture/platform.md).

- Keep **`karim-ashraf/lara-architect`** as the Composer entry (do not rename).
- Brand the product as an **Architecture Memory and Improvement Platform**.
- Grow via **sibling packages** (`lara-architect-ui`, later debugbar / vscode / github / ai / enterprise) — never a mega-package.
- Core stays small; UI and integrations consume snapshots.

## Explicit non-goals (for now)

- Starting with Insights/charts dashboards
- Building **Enterprise** product surface
- Growing unbounded rule packs instead of Workspace DX
- Shipping Workspace UI inside core
- Merging VS Code / GitHub / AI / Enterprise into the core package
- Renaming the Composer package away from `karim-ashraf/lara-architect`
- Unsupervised auto-apply of Assisted/Manual fixes
- Competing as “another Pulse/Telescope”
- Calling this “Insights” (Phase 5 is Architecture Intelligence — quieter, no charts)
- Team Architecture Language (“Our architecture prefers…”) — direction visible, not yet
- AI grounding before explainable evidence + confidence
