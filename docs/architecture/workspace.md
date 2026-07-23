# Workspace Specification

> **The Workspace is not a project report.**  
> It is a developer improvement environment.  
> Every piece of information should answer: *“What can I improve here, and how?”*

This document defines **concepts** and how they communicate — not pixel layouts.  
Product decision: [ADR-0008](../adr/0008-visualize-architecture-assistant-ux.md) · Vision: [VISION.md](../../VISION.md) · Phases: [roadmap](../roadmap.md#workspace-build-phases)

## Stack alignment

```text
User Experience
        │
Architecture Workspace          ← this specification (lara-architect-ui)
        │
Engine API / Snapshots          ← WorkspaceSnapshot · GovernanceSnapshot · Learning…
        │
Analysis / Graph / Rules / Memory ← ArchitectureEngine (lara-architect core)
```

The UI is a **consumer** of the engine — the same contracts feed CLI, Debugbar, VS Code, and GitHub later. Platform packaging: [platform.md](platform.md).

## Guiding principle

Every screen should help a developer improve the **code they are currently touching**.

Open on current **Context**, never “247 project issues.”

## Domain model

### Language of improvement

```text
Finding → Issue → Explanation → Action → (Verification) → Session
```

More powerful than “lint rule → error message.” Future AI / VS Code / GitHub / refactoring are consumers of this workflow.

### Identifiers

Same philosophy as the engine (`NodeId`, `RuleId`, `LayerId`):

`WorkspaceId` · `ContextId` · `FindingId` · `IssueId` · `ActionId` · `SessionId`

### Schema version

`WorkspaceSnapshot` payloads include `schema_version` (currently `1.0`) so React, Debugbar, VS Code, and GitHub can evolve safely.

### 1. Workspace

The container for one improvement environment.

```text
Workspace
{
    id
    project
    current_context
    health
    goals
    sessions
}
```

Serialized form: **`WorkspaceSnapshot`** (immutable read model).

### 2. Context (most important)

Everything revolves around context. The UI always asks: *What is the developer currently improving?*

| Type | Example |
| --- | --- |
| `project` | Whole app |
| `module` | Product |
| `file` | ProductController.php |
| `violation` | One rule hit |
| `fix` | A pending Safe/Assisted fix |

### 3. Finding → Issue

| Concept | Meaning |
| --- | --- |
| **Finding** | Technical fact from the engine (e.g. Controller imports Product model) |
| **Issue** | Developer improvement opportunity (e.g. Move persistence behind a service) |
| **Action** | What the developer can do next |

One Finding can later produce multiple Issues; mapping starts 1:1.

### 4. IssueExplanation (IssueCatalog)

Curated architecture education — **not AI**. AI may enhance copy later; it does not own the knowledge.

```text
IssueExplanation { why, impact, benefits, recommended_fix, recommended_actions }
```

### 5. Action + states

| State | UI hint |
| --- | --- |
| `available` | Shown / optionally enabled |
| `previewable` | Show Preview |
| `executable` | Show Apply / run now |
| `completed` | Done |
| `failed` | Error |

### 6. Session

Unique Workspace concept; foundation for Replay. `SessionId` reserved.

### Copy Architecture Context

Portable text for AI assistants, PR comments, and chat — without putting AI in core (`WorkspaceService::copyArchitectureContext` / `architect:workspace --copy-context`).

## Fix Lifecycle

Central Phase 2+ concept. **Do not skip Trust → Preview.**

```text
Issue
 ↓
Action
 ↓
FixProposal          ← what will happen (core, never UI)
 ↓
Preview              ← FileChange before/after
 ↓
VerificationPlan     ← Pint · PHPStan · Tests (pending → passed)
 ↓
Applied Change       ← only after verification (later)
 ↓
Session Update
```

### Policy

| Risk | Preview | Apply |
| --- | --- | --- |
| 🟢 **Safe** | Required | Enabled only after Verify (later) |
| 🟡 **Assisted** | Required | Manual approval |
| 🔴 **Design** | Explanation only | Disabled |

Phase 2 ships **Preview only** — UI label is “Apply Later”; `apply_enabled` stays false until verification exists.

### Domain objects

| Concept | Role |
| --- | --- |
| `FixProposal` | Title, description, summary, risk, confidence, reasoning, status, change_set, architecture_impact, verification |
| `FixProposalSummary` | intent · expected_outcome · file/check counts (CLI · React · GitHub) |
| `ChangeSet` | Explicit files + owned line/file summary (UI never recalculates) |
| `FileChange` | type · path · lines_added/removed · before/after |
| `ArchitectureImpact` | Before/after graph · removed/added deps · results |
| `VerificationPlan` | Ordered checks with status |
| `FixConfidence` | high/medium/low + reasons |
| `FixProposalReasoning` | Architecture rule · principle · benefits (not AI) |
| `FixProposalStatus` | created → **viewed** → reviewed → accepted → verified → completed |
| `ProposalDismissReason` | Future `ProposalDismissed` analytics (too_risky · unclear · not_now · wrong_context) |

Opening Preview = `viewed`. Explicit continue (Apply Later / Apply) = `reviewed`. Leaving after open is not review.

```php
$proposal = (new FixProposalService)->propose($snapshot, $issueId, $projectRoot);
// architect:workspace --propose=<issue-id> --format=json
// GET /architect/workspace/propose?issue_id=…  (UI package) → status: viewed
```

UI / VS Code / GitHub consume `FixProposal` — they never generate refactors.

```text
React → Request FixProposal → Core → FixProposal → UI Change Understanding
```

### Metrics

| Metric | Question |
| --- | --- |
| **Proposal Understanding Rate** | What / why / risk / verification in ~30s? |
| **Proposal Trust Rate** | Preview opened → Apply Later / Apply? (trust signal, not success/fail) |
| **Proposal Abandonment** | Future `ProposalDismissed` + reason (not a UI yet) |
| **Improvement Success Rate** | Completed Sessions / Started Improvements |

### Phase 2.1 — Change Understanding ✅

Milestone: understand **exactly what will change, where, and how architecture improves** before mutation.

Acceptance (≤30s):

| Question | Surface |
| --- | --- |
| What changes? | Change Navigator + comprehension diff |
| Why changes? | Reasoning + Architecture Impact |
| Is it safe? | Risk + Confidence |
| How do we know it worked? | Verification plan |

### Phase 3 — Controlled Change ✅

Frame as **Controlled Change**, not automatic fixes.

```text
Preview → Accept → Prepare → Apply → Verify (gate) → Session
```

| Concept | Role |
| --- | --- |
| `ProposalReviewed` | Intentional review (duration · confidence) — not “opened” |
| `ChangeExecution` | Immutable after start; append-only `events` ledger |
| `ExecutionEvent` | ExecutionStarted · FilesChanged · Verification* · SessionCompleted |
| `VerificationGate` | No completed Session without passing verification |
| `ArchitectureSession` | Replay-first: context · goal · before/after · changes · verification |
| `ArchitectureTimeline` | Product event stream for Replay (not a UI yet) |
| `ImprovementConfidence` | Phase 3.1 signal: did this improvement help? |

CTA wording: **Start Improvement** (Safe only). Assisted/Design stay gated; Apply Later = review without mutation.

Acceptance:

1. Open proposal → understand it  
2. Accept intentionally  
3. Allow Controlled Change  
4. See verification  
5. See Session updated  

Without hidden mutations or unclear ownership.

### Phase 3.1 — Improvement Confidence ✅

After a successful Session:

```text
Did this improvement help?  →  Yes / Not really
```

Stores `ImprovementConfidence` on the session + updates **Improvement Success Rate**:

```text
(completed sessions) / (started improvements)
```

### Product layers

| Layer | Question | Surfaces |
| --- | --- | --- |
| **1 — Understanding** | What is happening? | Analyze · Workspace · Issues |
| **2 — Guidance** | What should happen? | Explanation · Proposal · Impact |
| **3 — Improvement** | Safely make it happen | Controlled Change · Verify · Session |

### Feedback loop (next, not ecosystem)

```text
Session → History → Replay → Insights → Better Decisions
```

Do **not** jump to AI · VS Code · GitHub · Enterprise yet — events/artifacts are ready; deepen the loop first.

### Phase 4 — Architecture Memory ✅

Source of truth: **Architecture Event Stream** (`storage/architect/events/stream.jsonl`).

```text
Events → ArchitectureTimeline / History → Replay → Insights
```

Events stay separate from Sessions (failed attempts and abandoned proposals remain honest).

| Concept | Role |
| --- | --- |
| `ArchitectureEvent` | Append-only fact (IssueDetected … SessionCompleted …) |
| `ArchitectureBaseline` | First “how it was” reference |
| `ArchitectureHistory` | Context read model for History panel |
| `SessionConfidence` | Derived: verification · health · violation removed · developer signal |
| Replay v1 | Chronological list — not a fancy player |

History panel answers without re-analysis: what changed · why · did it improve · when.

Acceptance:

1. What changed?  
2. Why did we change it?  
3. Did it improve architecture?  
4. When did it happen?  

### Phase 4.1 — Architecture Story ✅

History explains itself (not a chart):

```text
Problem → Decision → Change → Proof → Result
```

| Projection | Role |
| --- | --- |
| `EventCorrelation` | finding → issue → proposal → execution → session |
| `ImprovementStory` | Readable arc for one journey |
| `ArchitectureTrend` | Queryable period knowledge (not a dashboard) |

Confidence stays derived from facts (`verification_passed`, `health_delta`, …) — never a stored “92%” primary.

UI order: **Story → View replay → Trend** (conclusion first).

### Architecture Decision Memory ✅

Not a new engine — expose Story decisions for a file/context.

```text
ProductService.php → “Why was this service created?”
  → Created during ProductController Improvement
  → Decision: Move business logic into services
  → Verification: Passed
```

`GET /architect/workspace/decision?file=ProductService.php`

### Phase 5 — Architecture Intelligence ✅

Read-only projections from Memory (no AI · no dashboards · no integrations):

| Projection | Question |
| --- | --- |
| Most improved areas | Where is architecture getting better? |
| Repeated problems | What keeps coming back? |
| Architecture drift | Where is friction / unresolved signal? |
| Common patterns | How do we usually improve? |

Facts stay immutable. Interpretations (intelligence summary) may evolve.

`GET /architect/workspace/intelligence`

### Phase 5.1 — Intelligence Explainability ✅

Every intelligence result answers:

| Question | Field |
| --- | --- |
| What did you observe? | `observed` |
| Why does it matter? | `why_it_matters` |
| What evidence supports it? | `evidence` (`events`, `contexts`, `time_range`) |
| What changed over time? | `over_time` |
| How sure is the insight? | `confidence` (`high` \| `medium` \| `low`) |

```text
Architecture Events
        ↓
Intelligence Projectors
        ↓
Explainable Read Models (+ Intelligence Confidence)
```

Not: Rules → Intelligence.

Insight confidence is confidence **of the projection**, not of the code — so later AI can ground on Evidence + Projection + Confidence instead of guesses.

Typed insights (not GenericInsight):

| Type | Evidence shape |
| --- | --- |
| `MostImprovedAreaInsight` | health before/after · improvements · main concept |
| `RepeatedProblemInsight` | occurrences · resolved · remaining · contexts |
| `ArchitectureDriftInsight` | baseline · current state · direction · related events |
| `ImprovementPatternInsight` | frequency · success rate · avg health impact |

### Architecture Vocabulary ✅

Internal naming only (not Team Language):

`Extract Service` · `Move Logic to Service` · `Service Boundary Improvement` → **Service Extraction**

### Phase 6 — Architecture Guidance ✅

Evidence → Guidance (not AI → Suggestions):

```text
Recommended next improvement
Area: Billing / recurring problem
Reason: similar fixes in memory · health below average · pattern repeats
Confidence: medium
```

`GET /architect/workspace/guidance`

### Phase 6.1 — Guided Improvement Journey ✅

Bridge: **Guidance → Proposal** (not automation).

```text
Guidance → Explore (Why now?) → Related history → Create proposal → Controlled Change
```

Opportunity ≠ Action. Human intent remains the gate.

| Field | Role |
| --- | --- |
| `why_now.current_state` | What is true now |
| `why_now.historical_evidence` | What memory already proved |
| `why_now.expected_impact` | What similar improvements did |
| `action` | Create Improvement Proposal when a related issue exists |

Tone: “might be worth looking at” — never “you must”.

Guidance confidence uses **evidence quality** (multiple contexts · repeated pattern · successful previous · recent) — not event count alone.

`GET /architect/workspace/journey`

### Guidance Decision Memory ✅

Not a new UI — append-only events for architecture learning:

| Event | Meaning |
| --- | --- |
| `guidance_viewed` | Opportunity was seen |
| `guidance_accepted` | Developer chose Create proposal |
| `guidance_dismissed` | “Not now” (still valuable) |

`POST /architect/workspace/guidance/decision`

### Phase 7 — Architecture Standards ✅

Lightweight semantic layer (before Team Language / AI):

```text
Concept: Service Extraction
Principle: Controllers orchestrate; services own business logic.
Evidence: successful improvements · average health impact
```

Future: AI → Standards → Evidence (not AI → Guess).

`GET /architect/workspace/standards`

### StandardEvidence ✅

Standards teach through proof:

```text
✓ Applied successfully N times
✓ Across M contexts
✓ Verification passed
```

Standards are versioned (`1.0`) so history remembers which principle was valued when — not retroactive rewriting.

**Standards ≠ Rules:** Rules are binary pass/fail; Standards are guiding + evidenced.

### Phase 8 — Architecture Governance ✅

Developer governance (not enterprise):

```text
Are we moving toward the architecture we value?
Standard: Service boundaries · 82% · ↑ improving
Evidence: 12 improvements · 4 remaining drift
```

No dashboards · no approvals · no management reports.

`GET /architect/workspace/governance`

### GovernanceSnapshot ✅

Stable read contract (not a dashboard):

```json
{
  "standard": "service_extraction",
  "alignment": { "score": 82, "direction": "improving" },
  "evidence": { "completed_improvements": 12, "remaining_drift": 4 },
  "confidence": "high",
  "last_updated": "2026-07-23T..."
}
```

Same shape for Workspace · CLI · future VS Code / GitHub / reports.

### Phase 9 — Architecture Evolution ✅

Standards → Governance → Evolution → (later) new Standards.

| Model | Answers |
| --- | --- |
| `ArchitectureDirection` | What trajectory are we taking? |
| `ArchitectureTrajectory` | Alignment over periods (Jan → Jul) |
| `ArchitectureMomentum` | Improvements vs drift (not a vanity score) |
| `ArchitectureRegression` | Learning when old problems return |

`GET /architect/workspace/evolution`

### ArchitectureChangeIntent ✅

Memory concept (not a UI feature): was this change intentional?

```json
{
  "area": "billing",
  "intent": "simplify_business_logic",
  "expected_direction": "increase_service_boundary",
  "created_from": "guidance"
}
```

Recorded when Guidance is **accepted** — distinguishes intentional evolution from accidental drift.

### Phase 10 — Architecture Learning ✅

Not ML. Learning from this project's history:

| Projection | Question |
| --- | --- |
| Successful Evolution Patterns | What worked best? |
| Evolution Risks | What tends to return? |
| Preferred Paths | When this issue appears, which path historically won? |

`LearningEvidence` justifies every claim (attempts · successful · contexts · average health delta). This is system learning from evidence — not machine learning.

`GET /architect/workspace/learning`

### Phase 11 — Architecture Collaboration ✅

How developers share architectural knowledge inside the Workspace (not GitHub, not VS Code yet):

| Concept | Purpose | Lifecycle |
| --- | --- | --- |
| Architecture Note | What should another developer know? | **contextual** (short-lived) |
| Architecture Rationale | Why does this architecture exist this way? | **permanent** |
| Architecture Ownership | Who owns / maintains this knowledge home? | context only — not permissions |

Notes and Rationales stay **distinct** — do not merge.

Events: `note_added` · `rationale_recorded` · `ownership_recorded`

`ArchitectureKnowledgeMap` connects Standards + Learning + human docs (relationships, not a graph UI).

`GET|POST /architect/workspace/collaboration`

### Phase 12 — Architecture Knowledge Transfer ✅

Living architecture knowledge — not documentation generation.

| Read model | Question |
| --- | --- |
| Architecture Onboarding | Welcome to this area — direction, decisions, risks |
| Context Brief | Before touching this file — why it exists, decisions, recent changes |
| Knowledge Map | How standards, improvements, and human knowledge connect |

`GET /architect/workspace/knowledge-transfer` · `GET /architect/workspace/knowledge-map`

Identity shift: Lara Architect **preserves why architecture exists**.

### Phase 13 — Architecture Questions ✅

Knowledge → Questions → Answers. Not a chatbot. Not AI.

```text
Question → Classify intent → Retrieve evidence → Compose answer → Show sources
```

| Question intent (`ArchitectureQuestionType`) | Routes to |
| --- | --- |
| `why_exists` | Rationale / Decision / Story |
| `what_changed` | Replay / History |
| `who_owns` | Ownership |
| `what_to_follow` | Standards |
| `what_worked` | Learning |

**Boundary:** Questions are **read-only**. `architect:ask "fix X"` is rejected — change belongs to Guidance → Proposal → Controlled Change.

**Answer sources** (`ArchitectureSourceType`): event · session · story · decision · rationale · note · standard · learning · ownership · regression · replay …

Every answer is traced (`sources` + `source_counts`).

```bash
php artisan architect:ask "why ProductService exists"
```

`GET|POST /architect/workspace/ask`

Workspace placement: after Guidance (navigation layer — not the main experience).

### Phase 14 — Architecture Conversations ✅

Questions = *what we know*. Conversations = *what we think about what we know*.

Not a chatbot. Event-based reasoning attached to architecture objects:

| Event | Meaning |
| --- | --- |
| `conversation_started` | Discussion opened on a standard / decision / context |
| `conversation_entry_added` | question · evidence · opinion · decision · rationale step |
| `conversation_decision_reached` | DecisionOutcome (may create durable Rationale) |
| `conversation_closed` | Terminal — recorded or explicitly **no decision** |

**Decision lifecycle:** Open → Discussing → Proposed → Accepted → Recorded → Referenced (or No decision made).

**Decision alternatives:** rejected / deferred options travel with the outcome so “why not X?” does not require reopening the conversation.

**ArchitectureDecisionHistory:** decision-only projection (onboarding) — not a dump of all conversations.

**Rule:** Conversation → Decision → Rationale. Conversations never replace rationales.

UI: **Discuss this architecture** · `GET|POST /architect/workspace/conversations` · `GET /architect/workspace/decision-history`

### Phase 15 — Architecture Identity ✅

System identity — not user identity. Not a score or grade.

> What kind of architecture does this codebase believe in?

**ArchitectureIdentitySnapshot** (stable contract for Workspace · CLI · VS Code · GitHub · AI):

```json
{
  "style": { "name": "Service-oriented Laravel", "confidence": "high" },
  "principles": [{ "name": "Controllers orchestrate", "evidence_count": 24 }],
  "strengths": [{ "area": "Service boundaries", "evidence": "18 successful improvements" }],
  "growth_areas": [{ "area": "Module coupling", "evidence": "4 recurring signals" }],
  "updated_at": "…"
}
```

**Product principle — Identity inertia:** Architecture Identity changes slower than code changes. Architecture is a pattern of decisions over time, not a file-structure snapshot. One new Repository class does not flip identity; months of repeated decisions + successful outcomes may.

**Identity history:** how we became this (period · style · reason). Explicit `observe()` records `identity_observed` — never mutate Memory from read paths. Reading ≠ changing.

`GET /architect/workspace/identity`

### Phase 16 — Architecture Communication ✅

How we help someone else understand what we are — transferable **Architecture Brief**, not static documentation.

Static docs rot. An Architecture Brief is composed from Identity + History + Decisions + Evidence → current understanding. Source of truth remains architecture memory.

**Audience** (presentation context, not permissions) — same knowledge, different emphasis:

| Audience | Question |
|---|---|
| Developer | How do I safely change this? |
| Architect | What direction are we moving? |
| New contributor | What should I understand first? |

Brief sections: Architecture Identity → Current Direction → Important Principles → Recent Evolution → Important Decisions → Known Growth Areas → Where To Start.

`GET /architect/workspace/communication?audience=contributor|developer|architect`

### Phase 17 — Architecture Context ✅

> What should I know about this exact thing before I touch it?

Unifies file/module context + identity + relevant decisions + recent evolution + current guidance into the developer's immediate workflow. AI later only explains an already-understood context.

`GET /architect/workspace/context?subject=ProductService.php&audience=developer`

Also projected on Workspace history as `architecture_context`.

### ArchitectureContextEnvelope — pre-AI boundary ✅

Stable contract for `lara-architect-ai` · VS Code · GitHub · external tools:

```json
{
  "schema_version": "1.0",
  "kind": "architecture_context_envelope",
  "context": { "target": "ProductService.php", "purpose": { "reason": "…" } },
  "identity": { "style": { "name": "Service-oriented Laravel", "confidence": "high" } },
  "evidence": { "watch": [], "sources": ["memory", "identity", "decisions", "evolution", "guidance"] },
  "decisions": { "important": [], "records": [] },
  "history": { "recent_evolution": [], "identity_history": [] },
  "guidance": { "hints": [], "opportunity": null },
  "brief": {},
  "allowed_questions": ["why_exists", "what_changed", "what_to_follow", "what_worked", "who_owns"],
  "boundary": {
    "can_explain": true,
    "can_modify": false,
    "may": ["explain", "summarize", "translate", "navigate", "onboard"],
    "must_not": ["new_rules", "new_findings", "new_architecture_decisions", "bypass_evidence", "direct_code_scan", "mutate_code"]
  }
}
```

`GET /architect/workspace/context?format=envelope&subject=ProductService.php`

**Ownership:** Core owns truth. Consumers interpret truth.

**AI boundary:** AI → Architecture Context Envelope → Explain / communicate.  
Not: AI → Code scan → Guess. Analyzer remains authority; AI is the language layer.

**AI speaks from architectural memory — never replaces it.**

First AI features (when ready): context explanation · architecture onboarding · change explanation — not generate refactoring / fix-all / rewrite.

Progression: Facts → Memory → Learning → Direction → Reasoning → Identity → Communication → Context → **Envelope** → AI (language layer).

### Phase 18+ — Architecture Moments (product discovery)

Surfaces are chosen **after** moments. Highest-value moment: before touching unfamiliar code (`ArchitectureContextEnvelope` already exists). Do not start with AI or GitHub.

**Artifact:** [Architecture Moment Map](moments.md) — capture Trigger · Question · Memory · Success; invest by Frequency × Value.

**Before Phase 19:** prove one moment is painful enough that developers will seek Lara Architect there. Then experiment (CLI · local context page · companion overlay) — not a full IDE first.

## WorkspaceSnapshot (API decision)

The Workspace must **not** query application databases for architecture state.

```php
$snapshot = WorkspaceService::snapshot(
    project: $root,
    context: WorkspaceContext::file('ProductController', $relativePath),
    analysis: $analysisResult, // or analyze inside the service
);
```

Returns one immutable payload:

```json
{
  "schema_version": "1.1",
  "workspace": { "id": "...", "project": "...", "health": {}, "today": {}, "metrics": {} },
  "context": {},
  "issues": [],
  "actions": [],
  "related": [],
  "neighborhood": {}
}
```

**Same snapshot** → React Workspace · Debugbar · VS Code · GitHub · CLI.

## First vertical slice

```text
Analyze → Findings → Issues (+ explanations) → WorkspaceSnapshot → CLI/JSON → Explain / Copy context
```

Phase 1.5 — Workspace Intelligence (breadcrumb, priority issues, impact dimensions, related + neighborhood).

Phase 2.1 — **Change Understanding**: `ChangeSet` · Architecture Impact · Navigator · comprehension diff.

Phase 3 — **Controlled Change**: append-only `ChangeExecution` events · Verify gate · Replay-shaped Session.

Phase 3.1 — **Improvement Confidence**: post-session signal + Improvement Success Rate.

## Recommended React structure (`lara-architect-ui`)

```text
workspace/     Context shell (Phase 1 / 1.5)
preview/       FixPreviewShell · ChangeNavigator · DiffViewer · ArchitectureImpact
               VerificationDetails · StartImprovementButton · SessionComplete
               ← no ApplyButton.tsx (boundary visible)
```

Domain folders over dashboard widgets. Preview is a **decision screen**, not a diff editor.
Inertia + React preferred (graphs, Monaco, diffs, Mermaid later).

## Composition reminder

```text
Workspace → Panels → Widgets → Actions
```

## Related

- [UI adapters](ui.md)
- [Events](events.md)
- [Engine](engine.md)
- PHP: `KarimAshraf\LaraArchitect\Workspace\*`
