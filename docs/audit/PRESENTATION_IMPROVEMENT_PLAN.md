# UniGPT — Presentation Improvement Plan

> Companion to `APPLICATION_AUDIT_REPORT.md`. Evaluates the pitch deck
> (`resources/js/presentation/deck.js`, 23 slides, rendered by `Presentation.vue`)
> as a **professional pitch / product story**, then gives a concrete improvement plan.
> Date: 2026-06-26. **Analysis only — no code or slides modified.**

---

## 0. TL;DR

The deck is **already strong** — well above typical student-project decks. It has a real
narrative arc, animated RAG/workflow diagrams, a 24-screenshot live demo, and a genuinely
rare **"honest limitations"** slide. It will land well with a technical audience.

The weaknesses are **pacing and emphasis**, not substance:

1. **Too long in the middle** — 23 slides with several dense 6–9-card grids that blur together.
2. **The single best differentiator (verifiable, cited answers) arrives at slide 08** — after 4 setup slides and an architecture slide. The "wow" should come sooner.
3. **No live-demo moment framed as a climax** — the 24-screenshot carousel is buried at slide 18 as a tour, not staged as proof.
4. **One factual slip** (chunk size "512" vs real 150) and **one missed credibility play** (owning "mock by default, OpenAI-ready").
5. **The close is competent but soft** — "Thank you" rather than a memorable, quantified ask.

A tightened ~16–18 slide cut with one reorder and a stronger open/close would take it from
"good engineering deck" to "compelling pitch."

---

# PHASE 4 — Presentation Quality Evaluation

## 4.1 Opening

**Current:** Slide 01 cover ("Run your university on intelligence, not paperwork") → 02 Problem
→ 03 Stakes → 04 Solution.

- ✅ **Strong tagline.** "Intelligence, not paperwork" is sharp and memorable.
- ✅ Problem/stakes framing is correct and academia-specific ("a wrong answer is worse than no answer" is an excellent line).
- ⚠️ **Hook is a statement, not a demonstration.** The most arresting asset — a *cited, confidence-scored answer* — doesn't appear until slide 08. The audience is told "no hallucinations" three times before they're *shown* it once.
- **Recommendation:** Move a single, live, cited-answer moment to **slide 02 or 03** ("Watch ChatGPT guess this policy — now watch UniGPT cite it"). Show the differentiator before explaining it.

## 4.2 Ordering / Flow

**Current arc:** Cover → Problem → Stakes → Solution → Architecture → RBAC → RAG → Grounded
answers → Core grid → Student feat → Student flow → Faculty feat → Faculty flow → Proctoring →
Messaging → Realtime-honesty → Admin feat → Governance → Metrics → Pluggable AI → Demo →
Roadmap → Impact → Close.

- ✅ Macro arc is textbook: problem → solution → proof → vision → close.
- ⚠️ **Architecture (05) and RBAC (06) come before the audience knows what the product *feels* like.** You're explaining *how it's built* before they want it. Governance/architecture are "why trust it" — they belong **after** the demo, not before.
- ⚠️ **Six consecutive feature/grid slides (08b–14)** create a "feature-listing" plateau — the exact failure mode the prompt warns about. Density is high and the slides start to feel interchangeable.
- ⚠️ **Two workflow slides (10, 12)** are good but back-loaded behind feature grids; the *flow* is more persuasive than the *grid* and should lead each persona, not trail it.
- **Recommendation:** Lead with **want** (demo the magic), then **breadth** (one consolidated platform slide), then **depth per persona** (flow-first), then **trust** (architecture/RBAC/governance as the "and it's safe" payoff), then proof/roadmap/close.

## 4.3 Content Quality / Density

- ⚠️ **Slide 06 (RBAC)** packs **21 feature bullets across 3 cards** — it doubles as the de-facto feature overview, which steals thunder from the dedicated grids and overloads a *security* slide with *feature* content.
- ⚠️ **Grids 08b/09/11/14** each carry 6–9 cards. Individually fine; **consecutively** they exceed working-memory and flatten emphasis.
- ✅ **Low-density slides (cover, statement, metrics, closing)** are well-judged pacing beats.
- ✅ **Animated diagrams (07 pipeline, 10/12 workflows, 08 chat)** are the deck's strongest content — concrete, not bullet soup.
- **Recommendation:** Cap any single slide at ~6 items; split RBAC's feature load back into the persona grids; keep one consolidated "core platform" grid and **delete the redundant second one** (08b overlaps 09/11/14).

## 4.4 Storytelling

- ✅ Clear thesis ("grounded, cited, governed AI") repeated throughout — good message discipline.
- ✅ **Problem → pain → solution** present and academia-specific.
- ⚠️ **Impact is asserted, not dramatized.** Slide 19b ("Less paperwork. More learning.") states benefits but doesn't *land* them with a before/after or a single human moment (e.g. "A student gets the late-policy answer at 11pm — no email, no wait, cited to page 4").
- ⚠️ **No protagonist.** The strongest academic pitches follow one person (a student or a lecturer) through the loop. The deck shows the *system*; it could show a *person using it*.
- **Recommendation:** Add a one-line "day in the life" thread that recurs (cover → demo → impact) to give the feature breadth a human spine.

## 4.5 Slide-Deck Craft

- ✅ Professional motion design: direction-aware transitions, staggered reveals, `prefers-reduced-motion` respected, light/dark, ambient 3D background, progress bar + dot-nav, full keyboard/touch nav.
- ✅ Data-driven (`deck.js`) — trivial to reorder/cut, which makes this plan cheap to apply.
- ⚠️ **No speaker-notes layer** — there's no per-slide talk track to support a live presenter.
- ⚠️ **24-screenshot carousel** auto-advances at 1400ms — too fast to read captions; as a climax it should be presenter-paced or trimmed to 6–8 hero shots.
- **Recommendation:** Add a speaker-notes field to `deck.js` and a notes overlay; slow/trim the demo carousel and stage it as the proof climax.

## 4.6 Ending

- ⚠️ **Soft close.** Slide 20 = "UniGPT / The AI academic copilot your campus can actually trust / Thank you." Competent but generic; no quantified takeaway, no clear ask.
- **Recommendation:** End on **one number + one ask**. E.g. "100% of core academic workflows, wired end-to-end, with zero external AI dependencies — ready to pilot on your campus this term. [contact]." Leave a single memorable stat on screen, not "Thank you."

## 4.7 Factual integrity (from the audit)

- ❌ Fix **slide 07**: "512-token semantic units" → **"~150-token overlapping chunks"** (matches `RAG_CHUNK_SIZE=150`).
- 💡 Consider **owning the mock-by-default reality** on slide 17 — "Ships in deterministic demo mode (zero keys); drop in an OpenAI key for production." Turns a hidden caveat into a trust signal.

---

# PHASE 5 — Improvement Plan

## 5.1 Missing Content

| # | Add | Why |
|---|---|---|
| 1 | A **live cited-answer "magic moment"** near the top (slide 2–3) | Show the differentiator before explaining it. |
| 2 | A **"mock today, OpenAI-ready" honesty beat** on the pluggable-AI slide | Pre-empts "is the AI real?"; candor reads as confidence. |
| 3 | A **human protagonist thread** (one student/lecturer journey) | Converts feature-listing into a story. |
| 4 | A **quantified, single-ask close** | Memorable exit + clear next step. |
| 5 | **Speaker notes** in `deck.js` | Supports a live presenter; currently none exist. |
| 6 | A **"demonstrable at realistic scale"** proof point (dense seeded data: 40–50 students/section) | Counters "it's just a toy demo." |

## 5.2 Ordering Issues

| Move | From → To | Reason |
|---|---|---|
| Architecture (05) + RBAC (06) | early → **after the demo** | "How it's built / how it's safe" is a *trust* payoff, not an opener. |
| Grounded-answers demo (08) | mid → **slide 2–3** | Lead with the wow. |
| Workflow slides (10, 12) | after grids → **before each persona's grid** | Flow persuades more than a feature list. |
| Core-platform grid (08b) | keep **one** | Redundant with 09/11/14; merge or cut. |
| Metrics (16) | mid → **just before close** | Proof should sit next to the ask. |

## 5.3 Weak Sections

1. **The 08b–14 grid plateau** — six dense feature slides in a row; highest blur risk. Consolidate.
2. **RBAC slide (06)** — overloaded with 21 feature bullets; refocus on *governance* (40+ perms, expiring grants, every route guarded, audit) and return feature bullets to persona slides.
3. **Impact (19b)** — tells, doesn't show; needs a concrete human before/after.
4. **Close (20)** — generic "Thank you"; no number, no ask.
5. **Demo carousel (18)** — 24 frames at 1400ms is unreadable as a climax; trim/slow/presenter-pace.

## 5.4 Suggested Improvements

- **Storytelling:** open with one person's unanswered question → show the cited answer → return to that person at "Impact." One spine through the whole deck.
- **Flow:** want → breadth → depth (flow-first per persona) → trust (arch/RBAC/governance) → proof (metrics + demo) → vision → ask.
- **Slide content:** ≤6 items per slide; one consolidated platform grid; diagrams over bullets wherever possible (the deck already does this well — extend it).
- **Visual communication:** stage the demo as a deliberate, presenter-paced climax; keep the excellent motion design but slow the carousel.
- **Impact:** every claim that can carry a number should (answer time, % workflows, scale tested) — and every number should be honest (drop the unsubstantiated "1.2s"; the audit flags it).

## 5.5 Recommended New Structure (~16–18 slides)

| # | Slide | Purpose | Source |
|---|---|---|---|
| 1 | **Cover** — "Intelligence, not paperwork" | Hook | current 01 |
| 2 | **The magic moment** — live cited, confidence-scored answer vs ChatGPT's guess | Show the differentiator first | from 08 |
| 3 | **The problem** — fragmented knowledge + hallucination + repetition | Pain | 02 + 03 merged |
| 4 | **The solution** — one platform, three roles, full academic loop | Frame | 04 |
| 5 | **How grounding works** — RAG pipeline (fix chunk size) | Make "cited" credible | 07 |
| 6 | **Student journey** — flow-first, then key features | Depth (persona 1) | 10 → 09 |
| 7 | **Faculty journey** — generate → refine → publish → auto-grade | Depth (persona 2) | 12 → 11 |
| 8 | **Proctored integrity** — fullscreen, auto-disqualify, server-authoritative | Differentiator | 13 |
| 9 | **Real-time messaging** — live, section-gated, separate from AI | Breadth | 13b |
| 10 | **Admin & governance** — approve-before-index, token metering, audit, RBAC | Trust | 14 + 15 + 06 (condensed) |
| 11 | **Architecture & no lock-in** — DDD, MySQL-native, 0 external AI deps, pluggable (own "mock→OpenAI") | Trust + candor | 05 + 17 |
| 12 | **Honest realtime trade-off** — Ably today, Reverb at scale | Credibility | 13c |
| 13 | **Live demo** — 6–8 hero screenshots, presenter-paced | Proof climax | 18 (trimmed) |
| 14 | **Proof / metrics** — 3 roles · 40+ perms · 100% workflows · 0 deps · scale tested | Proof | 16 |
| 15 | **Impact** — one human before/after | Payoff | 19b |
| 16 | **Roadmap** — shipped vs next | Vision | 19a |
| 17 | **Close** — one number + one ask + contact | Memorable exit | 20 (rewritten) |

**Net effect:** ~23 → ~17 slides; differentiator at #2 instead of #8; trust/architecture
moved to the payoff position; demo staged as climax; honest AI framing turned into a strength;
factual chunk-size fix applied; a stronger, quantified close.

---

## Quick-win checklist (lowest effort, highest impact)

- [ ] Fix slide 07 chunk size: "512-token" → "~150-token overlapping chunks."
- [ ] Remove/soften the unsubstantiated latency claims (deck has none; Landing has "1.2s" — see audit).
- [ ] Rewrite the closing slide to one stat + one ask (replace "Thank you").
- [ ] Add a candor line to the pluggable-AI slide: "mock by default, OpenAI-ready."
- [ ] Trim the demo carousel to 6–8 hero frames and slow/presenter-pace it.
- [ ] Add speaker notes to `deck.js`.

---

*End of Presentation Improvement Plan. Companion: `APPLICATION_AUDIT_REPORT.md`.*
