# SmartCEMES — AI Analysis Page Redesign Plan

_Status: **IMPLEMENTED 2026-10-07 — except the prototype mirror.** The owner approved the direction and
took every recommendation, with ONE change: **Decision 6 is "no" — the prototype is deliberately NOT
mirrored**, so this is a recorded **Laravel-only divergence** of the same class as `revisions.md` §23.6 /
§31.3 / §32.3. **§13 records what was actually built, the two claims in this document that turned out to
be wrong, and the bugs the work exposed.**_

**Goal:** turn `/ai-analysis` from one very long page that does three jobs badly into **two focused
surfaces** — a queue you can scan and a review you can read — so that every analysis the system has ever
produced is reachable, and the pipeline's real state is visible instead of implied.

**Grounded in measurement, not impression.** Every figure below was read from `smartcemes_v4` on
2026-10-07.

---

## 1. What the page is today

One route (`/ai-analysis`, `App\Livewire\AiAnalysis`, admin-only) rendering **seven stacked sections**:

| # | Section | Notes |
|---|---|---|
| 1 | Toolbar | a `<select>` of **all 30** summaries + a Generate button |
| 2 | Hero | community · Q · year, chips (model, agencies, confidence), EXPORT / ANALYZE REVIEW |
| 3 | Review grid | `grid-cols-3` — narrative `col-span-2`, and a **1/3-width rail** holding interventions + referrals |
| 4 | Identified Priority Needs | inside the rail stack |
| 5 | Action row | Discard / Approve — **inside the rail**, so it scrolls away |
| 6 | Drafts awaiting approval | 2-up cards, **only** `status=completed AND approval_status=draft` |
| 7 | Analysis History & Pipeline States | a 7-column table of **all** analyses, + a state legend |

`render()` loads `summaries` = all 30 and `analyses` = all analyses, newest first. There is no filtering
anywhere. `$drafts = completed + draft` and `$hero = $drafts->first()`, so **the review surface can only
ever show one draft**.

### 1.1 The data reality

| Fact | Value |
|---|---|
| Analyses | **11** — 2 `completed` (1 `draft`, 1 `discarded`), **9 `failed`** |
| Failure cause | Google **HTTP 503** bodies stored **raw** in `error_message` |
| Summaries | **30**; only **4 distinct** summaries ever analysed → **26 unanalysed** |
| Previous generations | summary 1 → **4**, summary 2 → 2, summary 8 → 2, summary 16 → 3 |
| `needs_assessments` rows for Brgy. San Jose · Q2 2026 | **13** (one per respondent), uploaded by **2 users**, all `validated` |
| `assessment_summaries` key | **`unique(['community_id','quarter','year'])`** — one summary per community+period |

### 1.2 The five real problems

1. **An approved analysis can never be re-opened.** The review surface is fed from `$drafts`, so
   `approved` and `discarded` analyses have no reading surface at all. The history row for an approved
   analysis says "citable in reports" with **no view link** — where the prototype has `view →`.
2. **Three jobs in one scroll.** Generate / review one analysis / browse history. The review is squeezed
   into a 1/3 rail while the history sits two screens below the fold.
3. **Previous generations are indistinguishable.** Four rows all read "Brgy. San Jose · Q2 2026" — no
   generation number, no current/superseded marker, and nothing defines which one is authoritative.
4. **26 validated summaries are invisible.** The pipeline's commonest real state — "has a validated
   summary, no analysis yet" — exists only as a dropdown option. The prototype fakes it as a `pending`
   row.
5. **Failure detail is a JSON tooltip.** Nine failures read as "unavailable"; the Director must hover a
   `title=` attribute to get a raw 503 body.

### 1.3 Two structural faults found while measuring

- **`assessment_analyses.needs_assessment_id` is vestigial.** `AiAnalysis::generate()` picks
  `NeedsAssessment::…->orderBy('id')->first()` and passes that single id to `generateFor()`. All **4**
  analyses on summary #1 therefore cite `needs_assessment_id = 1` — one respondent out of thirteen. The
  column reads as "the submission this came from"; it is really "the lowest-id respondent row". The
  analysis's true parent is the **summary**. See §8.
- **The summary carries a write-only AI mirror.** `AssessmentAnalysisService::approve()` also stamps
  `ai_analysis`, `ai_interventions`, `ai_analysis_sections` and `ai_analysis_generated_at` onto the
  summary. **Nothing reads them** (0 of 30 populated, because nothing is approved yet). See §13.

---

## 2. Target information architecture

```
/ai-analysis              the QUEUE    — every analysis, grouped by community; filters; generate
/ai-analysis/{analysis}   the REVIEW   — one analysis in depth, in ANY state
```

This mirrors the app's existing shape (`/faculty` board → `/faculty/{id}` profile; the `/colleges`
drill-down): a list surface that drills into a record surface. Note the analogy is **structural only** —
an analysis is keyed to a **community + period** (`assessment_summaries` is unique on
`community_id, quarter, year`), never to a faculty member. Several contributors roll up into one summary
and one analysis.

**Why two routes rather than one:**

- it gives an analysis a **deep link**, so reports and the audit log can cite one (impossible today);
- it fixes the 1/3-width squeeze **at the root** instead of patching it;
- it makes "approved = citable" real, because an approved analysis finally has a URL;
- the index can grow filters without lengthening the review.

**Alternatives, if two routes is more than you want:**

| Option | Shape | Trade-off |
|---|---|---|
| B — master–detail | one route, `#[Url] $analysisId`, list left / review right | no new route; the page stays heavy and the review stays narrow |
| C — tabs | one route, tabs for Review / Queue / History | smallest change; keeps the squeeze and no deep links |

---

## 3. Open decisions

**Decision 1 — route shape.** *Recommendation: two routes (§2).* B and C are the fallbacks above.

**Decision 2 — lineage.** *Recommendation: derive, don't store.* "Current" = the newest analysis for a
summary that is not `discarded`; label rows `gen N of M` by `created_at` within the summary, and mark
earlier completed ones `superseded`. No schema change; the existing 4/2/2/3 generations become legible.
An explicit `superseded_by` column is the alternative if lineage must be frozen against later edits.

**Decision 3 — the 26 unanalysed summaries.** *Recommendation: show them.* Add a derived state
**`awaiting analysis`** to the queue for every summary with no analysis, and move **Generate** onto the
row. That turns the dropdown into a genuine pipeline view. (The prototype gestures at this with a fake
`pending` row.)

**Decision 4 — approved analyses.** *Recommendation: yes, a linkable read-only surface.* This is what
makes the approval gate meaningful; the review route serves every state and simply hides the action bar
for non-drafts.

**Decision 5 — the `ai_analysis*` mirror on the summary.** *Recommendation: leave it for now, retire it
deliberately.* It is write-only, but `Phase5AiTest` asserts it, so removing it is its own change with its
own decision. Do **not** read from it in the new surfaces — the new surfaces read
`assessment_analyses` only, so no third source of truth appears.

**Decision 6 — prototype parity.** *Recommendation: mirror it.* `docs/prototype/pages/ai-analysis.html`
already contains most of the target design (§9). A Laravel-only change would be another accepted
divergence on top of §23.6 / §31.3 / §32.3.

---

## 4. Surface 1 — the queue (`/ai-analysis`)

**Header.** Title, and a `Generate` control that is secondary to the rows rather than the page's subject.

**Filter chips** — derived counts, never stored:

| Chip | Derivation |
|---|---|
| All | every analysis + every unanalysed summary |
| Awaiting review | `completed` + `draft` |
| Approved | `completed` + `approved` |
| Failed | `failed` |
| Awaiting analysis | summaries with **no** analysis |

**Grouping.** Community as the group header, then one row per analysis ordered newest-first, with the
`gen N of M` marker and `current` / `superseded` where they apply.

**Row anatomy** (one line where possible):

```
[state chip]  Q2 2026 · gen 3 of 4        Aug 24, 9:42 AM      gemini-3.6-flash      Review →
[state chip]  Q2 2026 · gen 2 of 4        HTTP 503 · overloaded                      Retry
[state chip]  Q1 2026                     Dr. Lowell A. Quisumbing                   View →
```

**Row affordances by state:**

| State | Action offered |
|---|---|
| `pending` | none — "generating…" |
| `completed` + `draft` | **Review →** (opens the review surface) |
| `completed` + `approved` | **View →** (read-only) |
| `completed` + `discarded` | **View →** (read-only, marked discarded) |
| `failed` | **Retry** + the failure reason as readable text, not a tooltip |
| `awaiting analysis` | **Generate** |

**Failure text.** Store the raw provider body as it is, but **render** a short, human line
("AI request failed — HTTP 503, provider overloaded") with the raw body behind a disclosure. The current
raw JSON in a `title=` tooltip is unreadable and unreachable by keyboard.

---

## 5. Surface 2 — the review (`/ai-analysis/{analysis}`)

**Header** — community · period, state chip, and a **provenance line**: model, prompt version,
`confidence_score`, `created_at`, and (for approved) the approver + `approved_at`.

**Body, full width** (this is the fix for the 1/3 squeeze):

1. **Analysis narrative** — `summary`, as today.
2. **Community response data** — the existing `<details class="sc-acc">` block, unchanged.
3. **Identified priority needs** — `problems_identified`.
4. **CESO interventions** and **Interagency referrals**, side by side in a 2-up grid.
5. **Provenance footer** — the agency catalogue snapshot and the source summary (§8).

**The two action panels** get the collapse agreed separately (see the companion note):

- each row is a `<details class="sc-acc">` — the house accordion already defined in `app.css:290-305` and
  already used on this page. **No new CSS, no JS, no Alpine.**
- interventions: `summary` = rank badge (`.acc-num`) + title + priority chip + chevron; body = `detail` +
  the thrust line. **Leave `High` rows `<details open>`** so triage is never hidden.
- referrals: lead the `summary` with the **agency chip** (the actionable part) and clamp `need` to one
  line, with the full text in the body. The `need` field averages 55 characters and reads as a sentence,
  so a title-only collapse would still leave a two-line heading.

**Sticky action bar** — ported from the prototype (`sticky bottom-4`): **Regenerate · Discard ·
Approve**, always reachable. State-dependent: drafts only. Today's action row lives inside the rail and
scrolls out of reach.

**Regenerate** — the prototype has it and Laravel does not. Semantics: generate a **new** analysis for the
same summary, leaving the previous one intact and marked `superseded` (this is what makes Decision 2's
lineage visible, and it matches how the 4 generations already arose).

---

## 6. Pipeline state model

Two independent axes, already in the schema — the redesign should say so explicitly rather than showing
one "Status" column:

- **`status`** — `pending` → `completed` | `failed` (the pipeline)
- **`approval_status`** — `draft` → `approved` | `discarded` (the human gate)

Plus one **derived** state that is not a row: **`awaiting analysis`** for a summary with no analysis.

The queue's chips are cuts across these axes; the review route accepts every combination.

---

## 7. What to port from the prototype (already designed, never built)

| Prototype artefact | Where | Laravel today |
|---|---|---|
| Sticky action bar (`sticky bottom-4`) | `ai-analysis.html:101` | action row inside the rail — scrolls away |
| **⟳ Regenerate** | `:109` | absent (only Retry-for-failed) |
| Discard **confirmation modal** | `:115-130` | `wire:click="discard(...)"` fires immediately |
| `view →` on approved rows | `:157` | "citable in reports" — no link |

The prototype also has the history table with **6** columns; Laravel's has **7** and is richer (separate
Status *and* Approval). **Keep the Laravel version** — this is one of the few places Laravel leads.

---

## 8. Provenance correction

The review surface must cite the **summary**, not a respondent row:

> Source: assessment summary — Brgy. San Jose · Q2 2026 · **13 validated responses from 2 contributors**

Both figures are derivable: `assessment_summaries.total_responses`, and
`count(distinct needs_assessments.uploaded_by)` over that community+quarter+year. That is honest, whereas
a "submitted by" column on the analysis would be **wrong** — `needs_assessment_id` points at whichever
respondent row sorts first, and a summary routinely has several contributors (13 rows, 2 uploaders, in the
live data).

Keep `needs_assessment_id` as-is for now (it is a stored FK with history), but **never render it as
"submitted by"**. Retiring it is a separate decision.

---

## 9. Blast radius

| Area | Change |
|---|---|
| `routes/web.php` | keep `ai-analysis.index`; add `ai-analysis.show` with `{analysis}`, `role:admin` |
| `app/Livewire/AiAnalysis.php` | becomes the queue: filters, grouping, lineage, per-row generate |
| new `app/Livewire/AiAnalysisReview.php` | the review surface (or one component with a mode — prefer two) |
| `resources/views/livewire/` | `ai-analysis.blade.php` (queue) + a new review view |
| `app/Services/AssessmentAnalysisService.php` | add `regenerate()`; leave `approve()`/`discard()` semantics alone |
| `app/Models/AssessmentAnalysis.php` | lineage helpers (generation index, isCurrent, superseded) |
| `tests/Feature/Phase5AiTest.php` | re-point the page tests at the queue; add review-route tests |
| `tests/Feature/R6GuardrailTest.php` | its tier-group test moves to the review route |
| `tests/Feature/RouteSurfaceTest.php` | `test_the_unlinked_surfaces_render_without_a_server_error` **skips model-parameter routes**, so the new `show` route needs its own test with a real analysis |
| `config/smartcemes.php` | nav unchanged — it already points at `ai-analysis.index` |
| `docs/prototype/pages/ai-analysis.html` | mirror if Decision 6 is "yes", then re-run all six harnesses |

---

## 10. Order of work

1. **The queue** — route split + index component/view with filters, grouping, lineage and per-row
   generate. Land the review route as a thin read-only port of today's hero + grid so nothing regresses.
2. **The review** — full-width layout, the two collapsible panels, the sticky action bar, the provenance
   footer.
3. **Regenerate + discard confirmation** — the two prototype affordances Laravel lacks.
4. **The failure surface** — readable reason on the row, raw body behind a disclosure.
5. **Prototype mirror** (if Decision 6 is "yes") + the six harnesses.

Each step is independently shippable and leaves the suite green.

---

## 11. Verification

- `vendor/bin/phpunit` (full suite) — baseline **538 tests / 3183 assertions, 0 failures**
- `vendor/bin/pint`
- `npm run build`
- all six prototype harnesses, if the prototype is touched
- **screenshot both surfaces** via `SHOT_PORT=8000 bash .workbuddy-ai/shot.sh <out.png> "<path>"` — the
  only way to review this on Windows
- a test that the review route renders for **each** state (`draft` / `approved` / `discarded` / `failed`),
  because that is the whole point of the split and no existing test covers it

---

## 12. Deliberately NOT in this pass

- **Prompt changes.** A short `need_label` field for referrals would let the collapsed row carry a real
  title, but that touches the **live PromptV2** and the stored JSON contract. Separate decision.
- **The `ai_analysis*` mirror** (§1.3) — Decision 5.
- **Retiring `needs_assessment_id`** (§8) — separate decision.
- **Backfilling the 9 failed analyses.** They are real `HTTP 503` failures; retry is the answer, not a
  migration.
- **The 26 unanalysed summaries' content** — the redesign surfaces them; generating for them is an owner
  action, not part of this work.

---

## 13. What was actually built (2026-10-07)

Landed in one pass. **551 tests / 3249 assertions, 0 failures** — up from the 538 / 3183 baseline, i.e.
+13 tests and +66 assertions, all from the new `AiAnalysisQueueTest`. `pint` clean on all eight changed
PHP files; `npm run build` OK; both surfaces screenshotted on the real page.

| File | Change |
|---|---|
| `routes/web.php` | added `ai-analysis.show` (`{analysis}`, `role:admin`) beside the existing index |
| `app/Livewire/AiAnalysis.php` | rewritten as the **queue**: `#[Url] $state` filter, community grouping, derived counts, per-row Generate, inline Retry |
| `app/Livewire/AiAnalysisReview.php` | **new** — the review surface for ANY state: approve / discard (confirmed) / regenerate / retry |
| `resources/views/livewire/ai-analysis.blade.php` | rewritten as the queue |
| `resources/views/livewire/ai-analysis-review.blade.php` | **new** — the old review markup moved and widened |
| `app/Models/AssessmentAnalysis.php` | `siblings()` · `generationIndex()` · `generationCount()` · `isCurrent()` · `isSuperseded()` · `queueState()` · `failureSummary()` |
| `app/Services/AssessmentAnalysisService.php` | `batchIdFor()` · `regenerate()` · `retry()` — retry **extracted** so the queue and the review cannot drift |
| `tests/Feature/AiAnalysisQueueTest.php` | **new** — 13 tests |
| `Phase5AiTest` · `R6GuardrailTest` | re-pointed at the review route |

### Two claims in this document were WRONG — both caught by checking

1. **§5/§7 said the sticky action bar was missing.** It was **already built** — `ai-analysis.blade.php:351`
   carries `sticky bottom-4`, outside the grid. What was genuinely missing is that it was not
   **state-dependent**. (I read a full-page screenshot, where a sticky element renders at its natural
   position, and concluded it lived in the rail.)
2. **§1.2's problem list implied the history table was missing.** It exists and is **richer** than the
   prototype's — separate Status *and* Approval columns, approving officer, retry-with-reason.

### One bug the real page exposed — fixed and pinned

The first cut of `isCurrent()` returned the newest non-discarded generation **regardless of status**, so a
**failed** attempt rendered as `current` — *"gen 3 of 4 · current · failed"* — pointing the Director at the
one analysis with no content. `isCurrent()` now requires `status = completed`, and `isSuperseded()`
excludes discarded. Pinned by
`AiAnalysisQueueTest::test_a_failed_newer_generation_does_not_demote_a_completed_draft`. Verified against
the live data: **0 failed rows marked current**, and #1 is the only current generation.

### Beyond the plan

- A **Discarded** filter chip was added, so the chips sum to the All count (they previously read
  1 + 0 + 9 + 26 = 36 against All = 37).
- `failureSummary()` renders the provider's raw body as a readable line (the raw JSON stays in `title=`).

### The owner's explicit constraint held

The **Community response data** block was moved **verbatim** and is asserted down to a breakdown row by
`test_the_review_surface_keeps_the_community_response_data_breakdown` — including `13 respondents`,
`Respondent profile`, `Sex`, `Hypertension` and `92%`.

### Not done

The **prototype mirror** (Decision 6 = no). `docs/prototype/pages/ai-analysis.html` therefore still shows
the old single-page shape — a **recorded, accepted Laravel-only divergence**.

### 13.1 The queue's presentation pass (owner review, same day)

Four changes to the queue only, at the owner's request:

1. **Page heading + the "Director-only" badge removed** — the sidebar/topbar already name the page, and the
   D3 boundary is stated in the hero and the legend (the P0i/P0j precedent). The chips and legend stay.
2. **One container card.** Each community used to be its own card; they are now group header rows inside a
   single `sc-card`, divided by `divide-y`.
3. **Pagination by COMMUNITY GROUP, not by row** — so a community's generations never straddle a page
   break. `GROUPS_PER_PAGE = 8`. ⚠️ **`Collection::paginate()` does not exist in this Laravel**, so the
   slice is wrapped in a manual `LengthAwarePaginator`; the shared pager's "of N" therefore counts
   **communities**, hence the "21 communities · 37 rows" label. `filterBy()` calls `resetPage()`.
4. **Generate / Retry reveal on hover**, through a `.hover-reveal` utility in `app.css` gated on
   **`@media (hover: hover) and (min-width: 1024px)`** — not a bare breakpoint, because `lg:opacity-0`
   also hides the button on a **touch** device at ≥1024px, where no hover can bring it back.
   `:focus-within` covers keyboard users. `Review →` / `View →` stay visible, because opening an analysis
   is the primary action. ⚠️ **A new Tailwind class needs `npm run build`**: the reveal silently did
   nothing until the build ran.

### 13.2 Search on the queue (owner request)

The queue groups by community, so with 21 of them the only way to reach one barangay was to scan or
paginate. Added a **search over the community name**:

- `#[Url(as: 'q', except: '')]` — the **house URL alias** (`Faculty\Directory`, `Communities\Index`), so the
  term is shareable and the query string matches the rest of the app. Bound with
  `wire:model.live.debounce.300ms`; partial and case-insensitive (`san jo` finds *Brgy. San Jose*).
- ⚠️ **Applied BEFORE the chip counts**, so the chips describe what is on screen. Counting the unfiltered
  queue would print "Awaiting analysis 26" above a single row. A **Collection filter, not SQL**, so no LIKE
  escaping (contrast `Faculty\Directory`, which escapes `%`/`_`).
- `updatedSearch()` → `resetPage()`; `clearSearch()` too. Narrowing a search can otherwise leave you past
  the last page, which renders an empty list that looks like a failed search.
- The empty state distinguishes **"your search found nothing"** from **"this state is empty"**.
- **Not searched:** the period. The axis is the community — that is what the grouping is by.

### 13.3 The community history page and a scoped delete (owner request)

**Why it was worth adding.** The queue lists a community's generations inline but **paginates by group**,
so its rows can sit on any page and there is no way to **link** to one community.
`/ai-analysis/community/{community}` is that link — keyed to the **community**, not a period, so it lists
**periods**, each with its own generation lineage. Two path segments, so no collision with the
one-segment `{analysis}` route. It is also where housekeeping lives: Generate, Retry, per-row Delete, and a
bulk **Clear N failed**.

**The delete is scoped — and the scope IS the design decision.** A hard delete here is permanent
(`assessment_analyses` has no `deleted_at`):

| Generation | Deletable? | Why |
|---|---|---|
| `approved` | **No** | Citable in reports; its content is mirrored onto the summary — deleting it orphans a citation |
| current `draft` | **No** | The queue's only actionable row; `Discard` then delete |
| failed · pending · discarded · superseded | **Yes** | Past attempts |

Three verified facts made a hard delete acceptable: (1) **nothing has a foreign key to
`assessment_analyses`**; (2) **`AuditLogs\Index` already renders a NULL subject by design** (its docblock:
*"A deleted subject resolves to NULL, which the view handles"*), so the audit text survives; (3) the
deletion is **logged** — auto-logged by the model plus a semantic event naming the community.

⚠️ **The id is scoped to the community** (`analysisInScope()` → `findOrFail`), so a crafted call cannot
delete another community's analysis.

**Navigation forms a loop: queue ↔ community ↔ review.** The queue's group header is a link (with a
chevron), and the review page gained a **community breadcrumb** beside "Analysis queue" — before this, a
generation was a dead end: from it you could not reach the community's other periods.
