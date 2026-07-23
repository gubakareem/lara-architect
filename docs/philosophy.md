# Philosophy

> The constitution of Lara Architect. Every pull request should align with these beliefs. Pair with [VISION.md](../VISION.md).

## Ownership (frozen)

| Layer | Owns |
| --- | --- |
| **Analyzer** | Code → Facts |
| **Engine** | Facts |
| **Memory** | History |
| **Humans** | Intent · Decisions |
| **AI** | Explanation · Communication |

No layer may silently become the authority.

**Permanent principle — preserve forever:**

> AI speaks from architectural memory — never replaces it.

Everything above **ArchitectureContextEnvelope v1.0** is replaceable.  
Everything below it is the source of truth.

## Three permanent principles

1. **Evidence before intelligence** — No insight without a traceable source.  
   `Observation → Evidence → Understanding → Explanation`
2. **Intent before automation** — Automation assists a decision; it does not replace one.  
   `Developer intention → Decision → Controlled change → Verification`
3. **Memory before AI** — AI is a translator of understanding, not the creator of truth.  
   `Architecture memory → Context envelope → AI explanation`

## Feature gate

Every future proposal must answer:

> Which existing architectural memory does this feature help a developer access?

| Fit | Example | Why |
| --- | --- | --- |
| ✅ | VS Code: “Why should I change this file this way?” | Context · Decisions · History · Standards |
| ✅ | PR: “Does this change follow our direction?” | Identity · Governance · Evolution |
| ✅ | AI: “Explain this module.” | Brief · Context · Rationales |
| ❌ | “Automatically redesign my architecture.” | Creates new truth |

If the answer is unclear, it probably does not belong in Lara Architect.

## Lara Architect believes…

**Architecture is continuous.**  
It is designed, monitored, and evolved throughout the life of a project — not a one-time setup step.

**Convention beats configuration until it doesn’t.**  
Sensible presets and defaults first. Escape hatches when real projects need them.

**Explain before fixing.**  
Developers deserve to know *why* something violates architecture before they change it.

**Preview before applying.**  
Never surprise developers. Diffs earn trust; silent rewrites destroy it.

**Safe automation earns trust.**  
Only zero-behavior-change fixes may auto-apply. Assisted and manual work stays reviewable.

**The engine owns knowledge.**  
Analysis, rules, and results live in the Architecture Engine. UIs and integrations are consumers.

**Interfaces outlive implementations.**  
Stable public contracts and SemVer for extension points. Internals may change freely.

**One architecture, many surfaces.**  
CLI, Workspace, VS Code, GitHub, CI — same engine, different adapters.

**Facts before intelligence.**  
Replay, baselines, and measured deltas beat speculative AI. AI should consume history.

**Human meaning, then metrics.**  
Excellent / Good / Needs Attention / Critical first; percentages underneath.

**Context before the project.**  
Every Workspace screen should help improve the code the developer is currently touching. “You’re working on Product” beats “247 project issues.” Charts and project-wide dumps belong in Insights.

**Identity changes slower than code.**  
Architecture Identity is a pattern of decisions over time — not a file-structure snapshot. Reading identity never mutates Memory; only explicit observation may.

**Briefs, not documentation.**  
Architecture Communication produces a living Architecture Brief from memory. Audience shapes presentation; the source of truth stays the event stream.

**Context before AI.**  
Architecture Context answers what to know before touching this exact thing. AI later explains already-understood context — it is a language layer, not the foundation.

**AI speaks from architectural memory — never replaces it.**  
AI may explain, summarize, translate, onboard, and navigate. It must not create rules, findings, or decisions, bypass evidence, scan independently, or mutate code.

## North Star test

Does this change make architecture **easier to improve every day** without requiring an architecture expert?

If not, it probably belongs elsewhere — or not in this project.

## Intelligence gate

Before adding any AI feature:

> Does this help developers understand existing architectural intent?

If yes → it fits.  
If it creates new architectural truth → it belongs outside the AI layer.

## What comes next

The foundation is a stable platform layer. The engine has earned the right to stop growing.

Next: **Observe → Capture → Learn → Decide.** Moment #001 only when decision changes before code. See [moments.md](architecture/moments.md).
