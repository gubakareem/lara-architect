# Architecture Moment Map

> Phase 18 ✅ — discovery discipline locked.  
> Operating mode: **Observe → Capture → Learn → Decide**  
> Not: Build → Promote → Search for validation.

Invariant: *Access existing understanding — never invent it.*

## Operating constraints (locked)

```text
No sentence          →  no surface
No changed decision  →  no Moment #001
No evidence          →  no roadmap shift
```

Until a valid human signal exists, the best action is **restraint**.

Valid first signal:

> “I was going to change this by doing X, but after seeing why it exists, I did Y instead.”

```text
Fact
├── Trigger · Context shown · Initial assumption
├── Decision changed · Final action · Developer quote
Interpretation → What this teaches us
Decision      → What (if anything) Lara Architect should do next
```

The foundation already answers what is true, why it exists, direction, history, and what can be explained.  
Still to discover: **Where does a developer naturally reach for that understanding?**

The next commit is not a feature commit — it is when a developer changes their decision because Lara Architect helped them understand.

The next meaningful artifact is a **developer behavior change record** — not a technical design document.

## Hypothesis

> When a developer touches unfamiliar code, they sometimes need architectural intent before making a change.

## Architecture Moment #001 — acceptance criteria

Do **not** record because someone liked the idea.

Record **only** when this sequence happens:

```text
Trigger
    ↓
Developer encounters unfamiliar code
    ↓
Uncertainty appears
    ↓
Architecture Context is provided
    ↓
Developer changes intended approach
    ↓
Code is changed differently because of understanding
```

**Strongest evidence:**

> “Without this context, I would have implemented it differently.”

Acceptance bar (all required for #001):

- ✅ unfamiliar code  
- ✅ uncertainty existed  
- ✅ context was provided  
- ✅ intended implementation changed  
- ✅ developer can explain why  
- ✅ surface preference observed naturally  

Anything less is a **signal**, not the first product moment.

## Protect against assumption

For every observed moment, keep three separate layers — do not collapse them:

| Layer | Holds | Example |
| --- | --- | --- |
| **Fact** | What actually happened | Developer moved validation from Controller to Service after reading context. Quote: *“I was going to keep it here, but I saw this decision.”* |
| **Interpretation** | What we think it means | Architectural intent was useful before editing. |
| **Decision** | What we change because of it | Test Context Before Touch as a code-boundary experience. |

Fact ≠ Interpretation ≠ Decision. Patience: failed observations (“I saw the context, but it didn't change anything”) are not wasted.

## Suggested Moment #001 structure

```markdown
# Architecture Moment #001

## Trigger
What was the developer trying to change?

## Question
What did they need to know before touching it?

## Initial assumption
What would they have done without context?

## Architecture Context provided
- Why
- Changed
- Respect
- Avoid

## Decision changed
What changed in their approach?

## Implementation result
What was actually changed?

## Evidence
- Developer quote
- Context used
- Related decisions/history

## Surface signal
Where did they naturally want this information?
```

**Surface signal** answers the product question without guessing:

| They wanted it… | Direction |
| --- | --- |
| while editing | Code boundary |
| before planning | Workspace |
| before approving | PR |
| before joining | Onboarding |

## Keep the first moment ugly

Prefer raw reality over polish:

> “I was going to put this logic in the controller, then I saw the previous decision and moved it to the service.”

Avoid: “Lara Architect helped enforce our architecture.”

That raw sentence contains the product.

## Keep the first negative moments

They protect against overexpansion.

```text
Moment #002
Context shown.
Developer: "I already knew this."
Learning: No need at obvious code paths.

Moment #003
Context shown.
Developer: "Too much information."
Learning: Compression needs improvement.
```

Both are progress.

## Context Before Touch (experiment)

5 unfamiliar files · 2–3 developers · real tasks · Why / Changed / Respect / Avoid only.

### Interview

| When | Ask |
| --- | --- |
| Before | What were you unsure about? |
| After context | Did this answer what you needed? |
| After change | Did the context change your decision? |
| After finish | What would you have done differently without this context? |

## Current state

```text
Foundation
██████████

Memory
██████████

Context
██████████

Discovery
██████████

Moment #001
░░░░░░░░░░  ← next meaningful event

Surface
░░░░░░░░░░

AI
░░░░░░░░░░
```

## Next meaningful event (not a commit)

A developer sentence:

> “I was about to do X, but after seeing why this exists, I did Y instead.”

Lara Architect has enough architecture. The next valuable artifact is that sentence — proof of preserving architectural intent, not just displaying information.

Patience. Observe → Capture → Learn → Decide. Keep Fact / Interpretation / Decision separate.

## Validated moments

*(Append #001 only when acceptance criteria are met. Append failures as #002, #003, …)*
