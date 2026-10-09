# SmartCEMES — Adviser Revision Plan

_Status (2026-10-07): **P0 ✅ · R1 ✅ · R2 ✅ · R3 ✅ (R3a/R3b §14, R3c §18) · R4 ✅ · R5 ✅ · R6 ✅ · R7 🔨 IN PROGRESS — only the production deploy remains (AI_HANDOFF §15.4).**_
_Created 2026-09-22. Baseline: Phases 1–5 complete, **232 tests / 1184 assertions**, blueprint v4.14._
_**Current: 572 tests / 3318 assertions passing, 0 failures** — growth: 276 (R1) → 286 (R2) → 339 (R3a/R3b) → 378 (R4) → 403 (R5) → 423 (R6) → 437 (R3c) → 444 (R7 route-surface) → 470 (R7 college seals) → 467 (R7 graduate set — 5 college-CRUD tests removed, 2 added, hence the dip) → 472 (R7 dashboard pass) → 472 / 2165 (v4.19 budget-basis correction — same tests, 4 net new assertions) → 467 / 2139 (v4.20 Analytics removal — the 5 tests that covered the page) → 468 / 2145 (the Graduate School seal — 1 new fallback test) → 476 / 2177 (the faculty self-edit widening — 1 test replaced, 9 added) → 484 / 2208 (the college hub Programs level — 8 new tests) → 485 / 2215 (the hub redesign — 1 new design-rule guard) → 485 / 2217 (§25 — 8 tests re-pointed at the hub, 2 replaced by 2) → **487 / 2233** (§26 — the
hub's budget surfaces consolidated to one, the chart turned into a doughnut, and the annual hours target
made settable from the Edit modal; 2 new tests, 16 new assertions) → **488 / 2238** (§27 — the
university targets page lost its year chart, its D-R5 guardrail card and its Training Hours Formula card,
and the Project-targets sort was made to work; 1 net new test, 5 new assertions) → **489 / 2244** (§28 — the
the retired-vocabulary sweep and a D-R7 leak the project-scoped guards missed; +1 test, +6 assertions)
→ **491 / 2894** (§29 — the Secretary's import template widened, plus two more links a role could see but not
reach, found by sweeping every `route()` call against each route's role middleware; +2 tests, +7 assertions)
→ **535 / 3168** (§31 — the safe-zone project set, the project archive with a visible restore, seeded
rendered hours for faculty performance, and two dashboard glyphs removed) → **538 / 3183** (§32 — the
faculty leaderboard's value carries its unit and its sub-line carries the activity count; +3 tests, +15
assertions) → **572 / 3318** (§33 — the AI analysis page split into a queue and a review surface, so an
approved analysis finally has a reading surface; +34 tests, +135 assertions across the split, its
presentation pass, the queue search, the per-community history page, and a lineage N+1 fix) → **592 / 3402**
(§34 — the project narratives page gains search / filters / pagination, and **six** AI-surface defects are fixed:
a lying toast, a `config:cache` API-key blocker, `prompt vv2` on five surfaces, an unreachable `pending`,
a never-completed attempt labelled as a narrative, and a hub audit view reading dead payload keys;
+20 tests, +84 assertions). **§30** (the demo cohorts)
recorded its own total in §30.5, and its test count held at 491 while assertions rose — several tests
iterate the seeded rows._
_**Blueprint v4.25. Prototype 29 pages.**_

> ### How to read this document (new sessions start here)
>
> This is the authoritative record of **everything that changed after Phases 1–5**. It supersedes
> `AI_HANDOFF.md` for anything post-Phase-5, and it records the owner's amendments that override
> `SYSTEM_BLUEPRINT_V4.txt`.
>
> | If you want… | Go to |
> |---|---|
> | The current status of every phase | **§10 — QUICK STATUS TRACKER** |
> | The phase specs (what each phase must do) | **§5** |
> | The locked owner decisions | §2 (target model), §3 (CESO programs), §7 (AI guardrail) |
> | What a completed phase actually shipped | §12 (R1) · §13 (R2) · §14 (R3a/R3b) · §15 (R4) · §16 (R5) · §17 (R6) · §18 (R3c) |
> | Which blueprint sections are still stale | **§8** |
>
> **R7 is the only phase outstanding, and it is documentation and verification only — no features.**
> Everything a user can click already exists and is smoke-verified.

This document plans a **structural revision** of SmartCEMES driven by the adviser's review.
It is deliberately a *plan*, not an implementation log. Each phase below is scoped, sequenced,
and independently shippable so the system stays green throughout.

---

## 0. ADVISER FEEDBACK — RAW INPUT (verbatim intent)

1. Put Extension **Programs** into different **colleges**: CAS, CME, COE.
2. **Program definition becomes broader.** Example: an *Educational* program contains an extension
   **project** (e.g. literacy-related), and under a project sit **activities**.
   → Today's `ExtensionProgram` (LITRAWIYA, HANDA, …) becomes a **Project**.
3. **Highlight Faculty Management** on the sidebar — the CESO Director checks faculty
   performance, involved programs, etc. (expertise, etc.)
4. **Highlight project performance**: trainors, trainees (beneficiaries), training hours rendered.
   **Remove a lot of the KPIs** — measure only the important things: total training hours rendered,
   trainors, trainees, budget, activities.
5. There is a **target training hours rendered per year**, and a **budget per year**.
6. Training-hours formula: `trainors × beneficiaries × days`, with a **half-day option** (recorded as
   0.5 in the `days` field; **amended 2026-09-23** — the earlier `× 8` hourly factor is removed, see §2.2).
7. Ability to **filter** the most active project, most active faculty, etc.
8. **AI guardrails** — must not recommend interventions outside CESO / LNU (CAS, COE, CME) agenda
   and expertise. Focus only on **social, economic and environmental**. Do not suggest feeding
   programs, construction, water testing, etc.
9. Such out-of-scope items **may** still be suggested, but must be flagged as
   **"interagency intervention"** with the **responsible government agency** named.
10. This is a huge change → **plan it, apply it slowly in phases.**

---

## 1. RESEARCH FINDINGS (grounds the plan in fact, not invention)

Verified from LNU's official website (lnu.edu.ph) on 2026-09-22.

### 1.1 The three colleges are real and official
| Code | Official name | Known programs |
|---|---|---|
| **CAS** | College of Arts and Sciences | BS Information Technology, BS Social Work, BA Communication |
| **COE** | College of Education | BEEd, BSEd, Teacher Certificate Program |
| **CME** | College of Management and Entrepreneurship | BS Hospitality Management, BS Tourism Management, BS Entrepreneurship |

Also confirmed: **each college has a named Extension Coordinator** (CAS / COE / CME) — the natural
owner of a college's extension work, and a candidate for the Project Lead role.

> Existing seeded faculty already map cleanly: Carlo Sumile (IT → CAS), Bianca Oledan
> (Reading Education → COE), Nikko Villas (Environmental Science → CAS), Kent Naputo
> (Entrepreneurship → CME).

### 1.2 CESO runs an official agenda called "KAHAYAG"
The Community Extension Services Office publishes an **eight-point extension program/agenda named
"KAHAYAG"** (CY 2014–2024). The CESO Director listed on the page is **Dr. Lowell A. Quisimbing** —
the same person already seeded as the Admin account. The project is therefore grounded in the real
institutional structure.

**Owner decision: do NOT reference the KAHAYAG brand in the UI.** The thrust names are still used
as the authoritative source for program definitions.

### 1.3 CESO's official "Extension Service Thrust and Priorities"
This is the authoritative list of what LNU CESO actually offers expertise in.

**Long-term Community-based Development Programs** (training-delivered):
1. Physical Fitness & Sports Development
2. Information, Communication & Education
3. Literacy, Numeracy & Language Enhancement
4. Cultural Development
5. Livelihood, Technical and Business Management
6. Environmental Conservation and Disaster Preparedness
7. Management and Leadership Management
8. Special Institute and Teacher Training Program

**Community Outreach Programs** (one-off services — *not* training):
- Food and Nutrition / Health and Sanitation / Maternal and child-care
- Medical / Dental / Optical Missions
- Clean and Green Community / Coastal Clean-up

> **This split is the key insight for the AI guardrail.** The 8 long-term thrusts are CESO's
> *training expertise*. The 3 outreach categories are precisely the kind of activity the adviser
> said not to recommend (feeding, medical missions, clean-ups) — unless flagged as interagency.

### 1.4 Interpretation note
The adviser's "focus only on social, economic and environmental" is treated as the **grouping
pillars** across CESO's thrusts. All eight thrusts fit those three pillars; the plan uses six
programs (see §3) rather than all eight, because two thrusts fall outside community training.

---

## 2. OWNER DECISIONS (locked 2026-09-22)

| # | Decision |
|---|---|
| D-R1 | Project/college codes: `CAS-2026-001`, `COE-2026-001`, `CME-2026-001` |
| D-R2 | Hierarchy naming: **Program → Project → Activity** |
| D-R3 | **AMENDED (see §2.2)** Training hours = `trainors × trainees × days` — ~~`× 8`~~. Trainors = **count of assigned faculty**, trainees = **actual attendees** (present/late attendance), days = **explicit per-activity field**. The `× 8` hourly factor was **removed**; `days` carries the duration |
| D-R4 | `days` is numeric in **0.5 increments**; 0.5 = half day. Because the hourly factor was removed, a 0.5-day activity with 2 trainors and 112 trainees yields **112 hrs**, not 896 |
| D-R5 | Targets live at **University + Project** levels only |
| D-R6 | Program list = **6 programs** (see §3) grouped under social / economic / environmental |
| D-R7 | **Remove all 8.6 KPIs from the project/performance view** (full removal) |
| D-R8 | SENIOR CARE becomes the **interagency demo case** |
| D-R9 | Faculty Management = **performance + expertise + involvement** |
| D-R10 | Interagency referral = an **admin-editable catalogue table** in the database |
| D-R11 | Do **not** reference the KAHAYAG brand in the UI |

### 2.1 Resolved open questions (decided 2026-09-22)

The four questions carried in the first draft of this plan are now settled:

| # | Question | Decision | Rationale |
|---|---|---|---|
| **R-Q1** | Trainee fallback when no attendance imported | **Add `activities.participants` (nullable, manual).** Resolution order: actual attendees → manual participants → 0 | There is no per-activity beneficiary roster to fall back to: enrollment is **project-level** (`extension_program_beneficiary`), not activity-level. Falling back to the project count would silently inflate hours (3 activities × 120 enrolled = 360). A manual field gives clean demo data, an honest audit trail, and no silent inflation. |
| **R-Q2** | Fate of `ProgramObjective` / the results framework | **Replace it with the training-hours target model.** Keep the table + `KpiService` in the schema, soft-deprecated and unread | `kpi_metric` is a locked FK into the 8.6 vocabulary being removed (R-D7), so every numeric objective would point at a non-existent metric. Keeping it "for the defense" invites an unanswerable question. Retaining the table/columns (unread) follows the v4.10 stale-column precedent and avoids an irreversible migration. |
| **R-Q3** | University annual targets: config or DB | **Database row** (`university_targets`, with `valid_from_year`) | Config requires a code deploy to change. The Director persona works in the UI, not in code. A per-year row also lets AY 2026–27 and AY 2027–28 hold different targets — needed to demo a second academic year. |
| **R-Q4** | New project code format | **College-prefixed (`CAS-2026-001`) — applied in Phase R2, not later** | Best presentation: the college is visible in the code. Must land in R2 so the `SequenceService` floor logic only ever handles one scheme. Requires the sequence key per college (`project_CAS_2026`) and the floor check extended across all three prefixes. **Note:** this is the only decision carrying real risk — it touches the v4.11 drift fix. If risk needs to be minimised before the defense, the fallback is to keep `EXT-{year}-{seq}` and surface college as a column. |

---

### 2.2 Amendments (owner review, 2026-09-23)

#### A. Training-hours formula — the `× 8` factor is REMOVED

**D-R3 / D-R4 are amended.** The formula is now:

```
TRAINING_HOURS = trainors × trainees × days
```

`days` already carries the duration, so multiplying by 8 double-counts it. Owner's wording:
*"when computing training hours dont multiply it by 8, its multiplied by days not hours."*

The half-day option is **unaffected** — it lives in the `days` field (0.5 = half day), not in the
hourly factor. What changes is only the removal of the trailing `× 8`:

| Scenario | Old (`× 8`) | **New (`× days`)** |
|---|---|---|
| 2 trainors × 112 trainees × 0.5 day | 896 hrs | **112 hrs** |
| 3 trainors × 141 trainees × 1 day | 3,384 hrs | **423 hrs** |
| 1 trainor × 126 trainees × 0.5 day | 504 hrs | **63 hrs** |

**This was already what the prototype's seed data did.** An arithmetic audit found all **6 / 6**
completed activities satisfy `trainors × trainees × days` exactly and **0 / 6** satisfy `× 8` — the
stored numbers were right and only the *labels* claimed `× 8`. The project rollup (968 hrs) also
equals the completed-activity sum (968 hrs) exactly. So the fix was primarily a **label/code**
correction, and the previously-reported "968 rendered" figure is correct under the new formula.

Consequence: the `× 8` was also making hierarchy-inconsistent figures look plausible. Under the new
formula the university total is a clean rollup of project hours, which is required by part B below.

#### B. Target model — annual target, drawn down by projects

Owner confirmed the two-level shape:

1. **One annual training-hours target for the whole of SmartCEMES.** A single university-level
   number the Director sets per year. **(Sole input to the annual target.)**
2. **A target *and* an actual per project** — **not per program**. Derived from the project's
   activities. Each project's **actual** hours are **subtracted from the annual target**.

```
annual_target_remaining = annual_target − Σ(project.actual_training_hours)
```

Note this is a **consumption** model, not a ratio. The annual target sits *above* the projects as a
pool; projects draw it down. The project-level `target` is a planning figure for that project and is
deliberately **not** summed to produce the annual target.

**Consequence — program-level targets do not exist.** This confirms D-C5 from the other direction:
because the annual target has exactly one input (the university figure) and project targets are
"not per program", the six broad programs must carry **no** training-hours target and must not be
shown as a target-vs-actual surface. The dashboard's "by program" hours chart was therefore wrong
and has been changed to **by project**.

#### C. University Targets page — honest-fix applied (P0n)

`targets.html` originally presented a University-targets register with a **per-program** breakdown,
inconsistent with (B) above. Investigation for P0n found the page had **already** been converted to
University tiles + a **project** table during P0f — no program table survived. What remained was a
smaller, sharper set of defects:

| Defect found | Fix |
|---|---|
| The banner §2.2C promised ("flagging it as not reflecting the final model") **was never added** | Added: an amber "does not yet reflect the final target model" banner with a `Pending` badge, stating explicitly that the annual target is not yet presented as a drawdown pool. |
| `universityTargets[].targetPrograms` / `targetProjects` — **fields that must not exist** (§2.2B: no program-level target) | Deleted from the seed; the 4th tile's caption that read them ("6 programs · 7 projects targeted") replaced with a project-rollup caption. |
| The hours tile said *"2,500 hrs remaining this year"* — an over-promise, since the page has no drawdown concept | Rewritten to state the pool relationship: *"N hrs of the annual pool still available · N drawn down by 7 projects"*, plus an explicit note that only training hours are drawn down. |
| Header read **FY2026** while the rest of the app reports **AY 2026–2027** | `universityTargets` rows gained a `label` (`AY 2026–2027` / `AY 2025–2026`); header, year picker, section subtitle ("academic year") and the seed comment all aligned. |

A guardrail note was also rewritten to state the actual model — pool above projects, project actuals
subtracted, broad programs carry **no** target — rather than the earlier "targets are derived" gloss.

**This is still not the final rewrite.** The page presents four parallel target tiles (hours, budget,
beneficiaries, activities); §2.2B defines exactly **one** annual training-hours target. The banner
keeps the page honest until R5 (dashboards/reports) reworks it natively, where the real table exists.
The page is no longer *wrong*, it is *explicitly provisional*.

#### D. There is NO per-professor training-hours target (added 2026-09-22, P0h)

The target model in (B) has exactly two levels: **one annual university-wide target**, and **a target
per project**. It stops there. Individual professors do **not** carry a training-hours quota and are
**not** measured against one.

A faculty member's contribution is therefore read from two numbers only:

1. **total training hours rendered** (the sum of the hours actually delivered), and
2. **project involvement** (how many projects they lead or co-lead).

**Consequences, enforced in the prototype:**

| Removed | Replaced by |
|---|---|
| `faculty[].targetHours` (seed field) | — deleted outright |
| `faculty[].spec` (Specialization) | folded into `expertise[]` |
| `faculty[].pct` (attainment %) | — no denominator exists |
| "Attainment %" metric mode on the engagement board | "Leads" mode (projects led) |
| Roster-wide "attained %" KPI card | "Avg Projects / Faculty" card → KPI strip is now 4 cards |
| `hours-bar` / `eng-rail` / `attainBadge` on faculty surfaces | plain hours + project-count figures |
| "of N hrs target" / "X hrs remaining" prose | "N projects" / "N hrs rendered" prose |
| A `Specialization` field in the profile modal | Expertise multi-select |
| A `Target training hours / year` field in the modal | — deleted |
| The old 8-item hand-written position list | the **18-rank** institutional ladder |

The 18-rank ladder (most junior first) is now canonical for the `Position` field:
Instructor I–III · Assistant Professor I–IV · Associate Professor I–V · Professor I–VI.

See §11.10 for the implementation, and `docs/prototype/PATTERNS.md` §11 for the enforced contract.

---

## 3. THE SIX EXTENSION PROGRAMS (target state)

Each program is a **verbatim CESO thrust**, tagged with its pillar.

| # | Program | Pillar | CESO thrust implemented |
|---|---|---|---|
| 1 | Literacy, Numeracy & Language | Social | Literacy, Numeracy & Language Enhancement |
| 2 | Information, Communication & Education | Social | Information, Communication & Education |
| 3 | Cultural Development | Social | Cultural Development |
| 4 | Physical Fitness & Sports Development | Social | Physical Fitness & Sports Development |
| 5 | Livelihood, Technical & Business Management | Economic | Livelihood, Technical and Business Management |
| 6 | Environmental Conservation & Disaster Preparedness | Environmental | Environmental Conservation and Disaster Preparedness |

**Excluded thrusts** (documented so the exclusion reads as a decision, not an oversight):
- *Management and Leadership Management* — internal/staff capability, not community training.
- *Special Institute and Teacher Training Program* — professional development, not community extension.

**Reclassified: the 3 Community Outreach Programs.**
CESO's official page publishes these as its second category of extension work. They are **NOT**
excluded because CESO never does them — they are **reclassified from CESO training programs into
interagency referral categories**, for two reasons:

1. **They are not training.** They are one-off service delivery, so they do not fit the
   `trainors × trainees × days` measurement model that drives the whole performance section.
2. **They are exactly what the adviser named as out-of-scope recommendations** (feeding programs,
   medical missions, clean-ups).

| CESO Community Outreach category | Referred to | Tier |
|---|---|---|
| Food and Nutrition / Health and Sanitation / Maternal and child-care | DSWD, DOH, LGU Nutrition Council | Tier 2 — interagency |
| Medical / Dental / Optical Missions | DOH, LGU Health Office | Tier 2 — interagency |
| Clean and Green Community / Coastal Clean-up | DENR, LGU, DPWH | Tier 2 — interagency |

These three categories are the **seed basis for the interagency catalogue** (§4.6 / §7.2), so the
system stays faithful to CESO's published priorities while keeping the recommendation surface
inside CESO's training mandate.

### 3.1 Existing project redistribution
| Existing `ExtensionProgram` (v4.14) | Becomes Project under Program |
|---|---|
| EXT-2026-001 LITRAWIYA | #1 Literacy, Numeracy & Language |
| EXT-2026-004 e-LITERACY | #2 Information, Communication & Education |
| EXT-2026-003 KABUHIAN | #5 Livelihood, Technical & Business Management |
| EXT-2026-002 HANDA | #6 Environmental Conservation & Disaster Preparedness |
| EXT-2026-006 BATANG MATINIK | #4 Physical Fitness & Sports Development |
| EXT-2026-005 SENIOR CARE | #2 Information, Communication & Education — **retained as the interagency demo case (D-R8)** |

---

## 4. TARGET DATA MODEL

### 4.1 New: `colleges`
```
id, code (CAS|COE|CME, unique), name, short_name, description,
extension_coordinator_id (nullable FK faculties), status, timestamps, soft deletes
```
Seeded with the three official colleges. Admin CRUD, but expect it to stay static.

### 4.2 New: `extension_programs` (the BROAD level — replaces nothing)
```
id, code (PROG-{YYYY}-{seq}), title, pillar (social|economic|environmental),
ceso_thrust (string; cites the official thrust implemented),
description, goals, annual_target_hours (decimal), annual_target_budget (decimal 12,2),
status (active|inactive), created_by, updated_by, timestamps, soft deletes
```
Only six rows in practice. Admin-managed.

### 4.3 Existing `extension_programs` → RENAMED to `extension_projects`
This is the big structural move. **The existing table is renamed**, not dropped, so all 6 rows,
their activities, beneficiaries, budgets, objectives, and proposals survive.

Added columns:
```
college_id        FK colleges        (required)
program_id        FK extension_programs (required — the new broad parent)
code              unchanged (EXT-2026-001 …) initially
annual_target_hours   decimal          (per-project target training hours, D-R5)
annual_target_budget  decimal(12,2)    (per-project annual budget, D-R5)
```
Removed from the project/performance surface (D-R7): nothing dropped from the schema, but the
8.6 KPI tiles are removed from the project hub. `KpiService` and `program_objectives` are retained
in the codebase but **soft-deprecated and no longer read** by the project hub, dashboards, or
reports (R-Q2). The results framework is superseded by the target model in §4.7.

Code policy (R-Q4): migrated rows **keep their existing `EXT-{year}-{seq}` codes** to preserve
history. **New projects adopt the college-prefixed pattern** (`CAS-2026-001`, `COE-2026-001`,
`CME-2026-001`), implemented in **Phase R2** so `SequenceService`'s floor logic handles only one
scheme. The sequence key becomes per-college (`project_CAS_{year}`, …) and the v4.11 floor check
is extended across all three prefixes — with tests covering duplicate protection for each.

### 4.4 Existing `activities`
```
+ no_of_days          decimal(4,1)   nullable   → 0.5 increments, 0.5 / 1 / 1.5 / 2 …
+ participants        int            nullable   → manual planned/actual trainee count (R-Q1)
+ trainors_snapshot   int            nullable   → optional override; default = count of assigned faculty
```
Training hours per activity (computed, **not stored**):
```
trainors   = trainors_snapshot ?? activity.faculty()->count()
trainees   = DISTINCT beneficiaries with present|late attendance on this activity
             ?? participants        (manual fallback, R-Q1)
             ?? 0
days       = no_of_days (0 allowed → contributes 0)
TRAINING_HOURS = trainors × trainees × days        # NOTE: no x8 — amended 2026-09-23 (§2.2)
```
The trainee resolution order is displayed in the UI as a **source tag** (`attendance` / `manual` /
`none`) so the Director can always see where the number came from — the same pattern as the v4.10
objective actual-source tag.

`rendered_hours` (faculty service hours, 8.9) is **unchanged and kept** — it is a different
quantity (per-faculty hours for service credit) and coexists with project training hours.

### 4.5 New: `faculty_expertise` (many-to-many)
```
id, faculty_id, area (string; e.g. "Reading Education"), category, timestamps
```
Powers the Faculty Management expertise view and the "most active faculty" filters.
Optionally, `faculty.college_id` is derived from `department` — see Phase 3.

### 4.6 New: `interagency_agencies` (D-R10)
```
id, agency_code (DSWD|DOH|DA|DENR|TESDA|DPWH|DTI|LGU), agency_name, mandate,
need_category, sample_service, contact_info, active (bool), timestamps, soft deletes
```
Admin-editable CRUD. Seeded with a starter set for owner review (§7.2).
Injected into the AI prompt so the model can **only** cite from the catalogue.

### 4.7 New: `university_targets` (R-Q3)
```
id, year (int, unique — calendar year or AY start year),
annual_target_hours   decimal   (university-wide training-hours target for the year)
annual_target_budget  decimal(12,2)  (university-wide annual budget)
notes, updated_by (FK users), timestamps, soft deletes
```
Director-editable in the UI (no code deploy needed). Per-project targets live on
`extension_projects` (§4.3); this table holds only the university-wide roll-up target.
Actuals are always computed — never stored.

---

## 5. PHASE PLAN

Sequenced so each phase leaves the app working and tests green. **Do not start a phase before the
previous one is committed and green.**

### Phase R1 — Colleges + Program level (foundation)
**Goal:** introduce the two new levels without moving any data.
1. Migrations: `colleges`, `extension_programs` (new broad table).
2. Models: `College`, `ExtensionProgram` (new shape), with `Program`-level relations.
3. Seeders: 3 colleges (CAS/COE/CME per §1.1), 6 programs (§3), attaching college codes to the
   existing sequence logic.
4. Admin CRUD: `/colleges`, `/programs` (broad level) + policy + nav entries.
5. Factory + tests for both.
**Exit criteria:** 3 colleges and 6 programs exist; new screens work; **no existing screen changes
behaviour**; all pre-existing tests still pass. *(Actual baseline: **232** tests / 1184 assertions — the
"222" figure was stale. **MET** — see §12.4.)*

### Phase R2 — The big rename: `extension_programs` → `extension_projects`
**Goal:** invert the hierarchy. This is the highest-risk step.
1. Rename the table + model + all relations (`ExtensionProgram` → `ExtensionProject`).
2. Add `college_id` and `program_id`; backfill the 6 rows per §3.1.
3. Add the per-project target columns `annual_target_hours` / `annual_target_budget` (§4.3).
4. **Implement R-Q4 here:** college-prefixed codes for NEW projects. Sequence key becomes
   per-college (`project_CAS_{year}`, …); extend the v4.11 floor check across all three prefixes;
   migrated rows keep their `EXT-{year}-{seq}` codes untouched. Add duplicate-protection tests
   per college — this is the risk item flagged in §9.2.
5. Repoint: activities, beneficiaries pivot, budget utilizations, objectives, proposals, narratives.
6. Update every reference — Livewire components, views, policies, reports, templates, tests.
   Expect the rename to touch **all** of `app/Livewire/Programs/*`, `app/Policies/*`,
   `resources/views/livewire/programs/*`, `ReportController`, and most feature tests.
7. **Update the 276 existing tests** (this phase is majority test-churn).
**Exit criteria:** zero `ExtensionProgram` references remain; hierarchy renders Program → Project → Activity; new projects get college-prefixed codes; all tests pass.
**MET — see §13.** *(276 → **286** tests / 1412 assertions.)*

### Phase R3 — Colleges on faculty + Faculty Management module  ✅ **COMPLETE (§14 + §18)**
**Goal:** deliver the highlighted sidebar module (D-R9).
1. `faculty.college_id` (backfilled from `department` — the seeded values are already full college
   names, so the mapping is deterministic), `faculty_expertise` table (§4.5).
2. New **Faculty Management** page — this replaces the currently *phantom* nav entry
   (`faculty.index` exists in config but the route does not, so it is silently hidden today).
3. Index: list + filters (college, expertise, active/inactive) + search.
4. Detail page: profile & expertise, assigned projects, activities handled, training hours
   delivered, rendered hours approved, proposals submitted/approved, performance trend.
5. Performance metrics per faculty (see §6).
6. Policies: Admin-only manage; Faculty see own profile only.
**Exit criteria:** Director can open any faculty member and read their full extension contribution.

### Phase R4 — Training hours model + project performance revamp
**Goal:** the adviser's headline ask (feedback #4, #5, #6).
1. Add `activities.no_of_days`, `activities.participants`, `activities.trainors_snapshot`
   (§4.4) with 0.5-step validation on days.
2. Add the `university_targets` table (R-Q3 / §4.7) + Director-editable form.
3. `TrainingHoursService` implementing `trainors × trainees × days` (**no `× 8`** — §2.2), with the trainee
   resolution order `attendance → participants → 0` and a source tag exposed to the UI (R-Q1).
4. **Remove all 8.6 KPI tiles** from the project hub, dashboards, and reports (D-R7) and
   **soft-deprecate `ProgramObjective` + `KpiService`** — retained unread, per R-Q2.
   Replace the results framework with the target model.
5. Rebuild the project hub performance section: trainors, trainees, training hours, budget,
   activity counts — target vs actual bars against the project's annual targets.
6. Activity form gains the days input (half-day = 0.5) and the manual participants field.
**Exit criteria:** project performance shows target vs actual training hours and budget; no 8.6 KPI tiles remain on those surfaces; trainee source is visible per activity.

### Phase R5 — Filters, ranking & dashboards
**Goal:** feedback #7.
1. "Most active project" / "most active faculty" / "most active college" rankings, sortable and filterable.
2. Filters: college, program, pillar, academic year, status.
3. Rework the Admin dashboard around the new metrics; demote/retire the old KPI cards.
4. Update the four print reports to the new metric dictionary.
**Exit criteria:** every ranking and filter is driven by real computed data, no invented numbers.

### Phase R6 — AI guardrail + interagency catalogue  ✅ **COMPLETE (§17)**
**Goal:** feedback #8 and #9.
1. `interagency_agencies` table + admin CRUD + seed set.
2. Rewrite `PromptV1` → `PromptV2` (keep v1 for provenance) embedding:
   - CESO scope: the 6 programs + the 3 pillars + the 8 thrusts.
   - A hard prohibition list (feeding, construction, medical missions, water testing, etc.).
   - The agency catalogue, citable **only** from the table.
3. Output schema gains `interagency_referrals[]` = `{need, agency_code, agency_name, rationale}`.
4. UI: recommendations render in two visually distinct groups — **CESO interventions** vs
   **Interagency referrals** (with agency name and a clear "outside CESO mandate" label).
5. Store the catalogue snapshot in `metadata` for audit/reproducibility.
**Exit criteria:** a needs assessment producing health/infrastructure needs yields referrals citing only catalogue agencies; no CESO-mandate violations.

### Phase R7 — Hardening & docs  🔨 **IN PROGRESS (§19) — documentation only, no features**
1. Blueprint v4.15 revision entry + section rewrites (see §8).
2. Fix stale blueprint sections found during review (§8, list included).
3. Full test sweep, pint clean, seeder re-seed from scratch.
4. Update `AI_HANDOFF.md` for the new architecture.
5. Re-run the defense walkthrough end to end.

---

## 6. FACULTY PERFORMANCE — WHAT THE DIRECTOR SEES (D-R9)

| Block | Contents | Source |
|---|---|---|
| Profile & identity | employee ID, position, college, department, specialization, contact | `faculties` |
| **Expertise** | tagged expertise areas / categories | `faculty_expertise` |
| **Involvement** | projects led, projects assigned, activities handled, communities served | pivots |
| **Training contribution** | training hours delivered (sum over their activities), sessions count, trainees reached | `TrainingHoursService` |
| Rendered hours | approved / pending / rejected hours per semester | `rendered_hours` |
| Proposals | submitted, approved, rejected, approval rate | `activity_proposals` |
| Performance trend | training hours + activities over time | computed |
| Flags | no activity in N months, pending submissions, over/under-loaded | computed |

**"Most active faculty" ranking** ranks by training hours delivered, then activities handled.

---

## 7. AI GUARDRAIL DESIGN (feedback #8, #9)

### 7.1 The three-tier output rule
The AI must classify every identified need into exactly one tier:

| Tier | Meaning | Example |
|---|---|---|
| **Tier 1 — Direct CESO intervention** | Within CESO's 6-program training expertise (§3) | "Conduct a reading enhancement training for parents" |
| **Tier 2 — Interagency referral** | Real need, outside CESO's **training** mandate → name the agency from the catalogue | "Supplementary feeding → DSWD" |
| **Tier 3 — Prohibited as a CESO recommendation** | Must **never** be presented as something CESO should deliver | CESO running a feeding program, constructing facilities, water potability testing |

**Tier 2 is grounded in CESO's own published priorities, not invented.** CESO's official page lists
a second category — *Community Outreach Programs* — covering Food/Nutrition/Health, Medical/Dental/
Optical Missions, and Clean & Green/Coastal Clean-up. Those are routed to Tier 2 because they are
**service delivery rather than training** (they break the training-hours model) and because the
adviser explicitly flagged them. Tier 3 items may only ever appear as Tier 2, always with the
agency named and an "outside CESO's training mandate" label in the UI.

### 7.2 Starter interagency catalogue (for owner review)
Seeded from CESO's published Community Outreach categories (§3), extended with the agencies that
actually deliver each mandate.
| Agency | Mandate | Need category | Sample referral service |
|---|---|---|---|
| DSWD | Social welfare & development | Food / nutrition / welfare | Supplementary feeding, 4Ps, senior social pension |
| DOH | National health services | Health / medical | Medical & dental missions, immunization |
| DA | Agriculture & fisheries | Farming / fishing livelihood inputs | Techno-demo, farm inputs, training support |
| DENR | Natural resources & environment | Environmental rehabilitation | Coastal/watershed rehabilitation, tree growing |
| TESDA | Technical-vocational skills | Skills certification | Free skills assessment & certification |
| DPWH | Public works & infrastructure | Roads / drainage / facilities | Barangay road and drainage construction |
| DTI | Trade & industry | Enterprise development | MSME mentoring, product development |
| LGU (Barangay/City) | Local governance & basic services | Water, sanitation, local facilities | Water system maintenance, sanitation enforcement |

> The owner reviews and edits these rows before the AI phase. The AI can cite **only** these rows,
> which eliminates hallucinated agencies and makes every referral defensible.

---

## 8. BLUEPRINT IMPACT

The revision moves the blueprint from **v4.14 → v4.15** (large structural change; warrants a major
revision entry).

**Sections to rewrite:** §1 (scope), §2 (role workflows), §5.1/5.2/5.4/5.5/5.10/5.11/5.13/5.15,
§6 (new + renamed models), §8.2/8.5/8.6, §9 (stack — unchanged), §12 (dashboards/reports),
§14 (new decisions D14–D18), §16.

**Stale sections to fix while in there** (found during this review, unrelated to the adviser input).
**Status as of R7 (2026-09-24)** — the cheap, unambiguous ones are now done:

| Item | Status |
|---|---|
| §1 header says *"IMPLEMENTATION STATUS: nothing has been built yet"* | ✅ **Fixed** — replaced with a v4.15 status banner pointing at `revisions.md` |
| §11 says *"ALL PHASES PENDING"* | ✅ **Fixed** — replaced with the current status; "Phase 6" relabelled as retired/R7 |
| §9 lists **"Spatie Activity Log" twice** (duplicate bullet) | ✅ **Fixed** — duplicate removed |
| §8.5 / §10.4 still describe a *"queued job pipeline"* although v4.4 made generation synchronous | ✅ **Fixed** — both now state synchronous in-request generation |
| §8.5 D12 line says *"live OpenAI API"* | ✅ **Fixed** — now reads Google Gemini |
| §14 gotchas: `ExtensionProgram::nextCode()` example needs renaming | ✅ **Fixed** — §6.4 rewritten as 6.4/6.4a/6.4b/6.4c; all `extension_program_id` FKs and `ExtensionProgram` references renamed (historical revision entries left intact, deliberately) |
| **§6.15, §8.6, §12 (dashboards/reports) still describe the results-framework model that D-R7 removed** | ✅ **Fixed (v4.16)** — §6.15 and §8.6 now carry RETIRED-IN-CODE status headers; §12 describes the target model and marks the legacy route name |
| **§2 (role workflows), §5.1/5.2/5.4/5.5/5.10/5.11/5.13/5.15, §8.2/8.5, §14 (D14–D18), §16** | ✅ **Fixed (v4.16)** — the full section rewrite landed; §14 gained **D14–D20**. See §19.8 |

**v4.16 (2026-09-24) — the stale-section rewrite is COMPLETE.** The blueprint now describes the
post-revision system: `program` = the broad level and `project` = the narrow one throughout; the
objective manager, results framework and 8.6 KPI scorecard are marked RETIRED; the target model, the
collapsed hierarchy navigation and the three-tier AI guardrail are described as the current design.
The v4.1–v4.15 revision entries are **historical records and were deliberately left as written**.

---

## 9. RISK REGISTER

| Risk | Severity | Mitigation |
|---|---|---|
| **The `extension_programs` rename touches nearly every module** | High | Isolate in Phase R2; do the rename + backfill as one atomic change; lean on the 276-test suite to catch fallout; commit before and after. |
| Training hours depend on imported attendance | Medium | If no attendance is imported, trainees = 0 → hours = 0. Decide whether to fall back to the enrolled roster count, or show "no attendance recorded" explicitly. **Needs owner decision.** |
| Removing 8.6 KPIs breaks objective auto-computation | Medium | `ProgramObjective.kpi_metric` currently maps to 8.6 keys. Decide whether objectives survive the revamp or are replaced by the training-hours target model. **Needs owner decision.** |
| Two "hours" concepts confuse users | Medium | Name them distinctly in the UI: **Training Hours** (project delivery) vs **Rendered Hours** (faculty service credit). Document the distinction in the blueprint. |
| Seeded demo data becomes inconsistent | Low | Re-seed from scratch after R4; redistribute projects per §3.1. Existing migrations are additive, so `migrate:fresh --seed` works. |
| AI cost/latency with a larger prompt | Low | Catalogue is small (~8 rows); negligible token increase. |

### 9.1 Resolved questions
All four questions from the first draft are **decided** — see the resolution table in **§2.1**
(R-Q1 trainee fallback → `activities.participants`; R-Q2 objectives → replaced by the target model;
R-Q3 university targets → DB table; R-Q4 new project codes → college-prefixed). The decisions are
reflected in the data model (§4.3–4.7) and the phase plan (§5).

### 9.2 Risk carried by R-Q4
The only decision with genuine residual risk is the college-prefixed project code (R-Q4), because it
re-touches the `SequenceService` floor mechanism fixed in blueprint v4.11 after a real duplicate-code
bug. Mitigation: land it inside Phase R2, add per-college duplicate-protection tests, and keep the
`EXT-{year}-{seq}` fallback documented if the change needs to be reverted before the defense.

---

## 10. QUICK STATUS TRACKER

| Phase | Scope | Status |
|---|---|---|
| **P0** | **Frontend prototype passes (preview only)** | ✅ **Complete — see §11** |
| **P0b** | **Collapsed hierarchy nav (single "Manage Extension Programs" entry)** | ✅ **Complete — see §11.4** |
| **P0c** | **Rendering defects: oversized icons, literal `[[icon]]` text, double-encoded characters** | ✅ **Complete — see §11.5** |
| **P0d** | **Colleges hub → two-view flow (cards → projects); drill-down rail removed; UI/UX polish** | ✅ **Complete — see §11.6** |
| **P0e** | **View 1 stripped to colleges only; per-college training hours removed (no such target)** | ✅ **Complete — see §11.7** |
| **P0f** | **Formula `× 8` removed; admin dashboard simplified to performance leaders; per-project target model** | ✅ **Complete — see §11.8** |
| **P0g** | **Faculty Management: "Most Active Faculty" table → interactive Faculty Engagement board** | ✅ **Complete — see §11.9** |
| **P0h** | **New Faculty Profile modal + faculty self-edit view; per-professor target removed; dedicated Faculty Directory page** | ✅ **Complete — see §11.10** |
| **P0i** | **Faculty Management reduced to the board only — header, KPI cards, toolbar and roster table removed** | ✅ **Complete — see §11.11** |
| **P0j** | **"Export roster" removed; "Faculty Directory" moved into the board header, right of the AY chip** | ✅ **Complete — see §11.12** |
| **P0k** | **Faculty Directory: summary cards, header description and export action removed** | ✅ **Complete — see §11.13** |
| **P0l** | **Manage Extension Programs: header banner removed; three college cards redesigned with a logo/media area** | ✅ **Complete — see §11.14** |
| **P0m** | **Admin dashboard: Community Reach + Action Center removed; page rebuilt on shared layout primitives, new functional harness** | ✅ **Complete — see §11.15** |
| **P0n** | **`targets.html` honest-fix: model-pending banner added, phantom per-program target fields removed, FY→AY aligned** | ✅ **Complete — see §11.16** |
| **P0o** | **Dead Objective authoring surface removed from `program-detail.html` (seed retained — two read-only consumers)** | ✅ **Complete — see §11.17** |
| R1 | Colleges + broad Program level | ✅ **Complete — see §12** |
| R2 | `extension_programs` → `extension_projects` rename (+ college codes, targets) | ✅ **Complete — see §13** |
| R3 | Faculty college + Faculty Management module | ✅ **Complete — R3a/R3b §14, R3c §18** |
| R4 | Training hours model + project performance revamp | ✅ **Complete — see §15** |
| R5 | Filters, rankings, dashboards, reports | ✅ **Complete — see §16** |
| R6 | AI guardrail + interagency catalogue | ✅ **Complete — see §17** |
| R7 | Hardening, docs, blueprint v4.23 | 🔨 **In progress — only the production deploy remains (AI_HANDOFF §15.4); see §19** |
| **§20** | **Budget-basis correction: no per-project annual budget target (blueprint v4.19)** | ✅ **Complete — see §20** |
| **§21** | **Admin Analytics page removed entirely (blueprint v4.20)** | ✅ **Complete — see §21** |
| **§22** | **Faculty self-edit widened to expertise + academic (code catching up with its own contract)** | ✅ **Complete — see §22** |
| **§23** | **College hub drill-down: College → Program → Projects, no cross-college views** | ✅ **Complete — see §23** |
| **§24** | **College hub view 2 visual redesign (and view 3 for consistency)** | ✅ **Complete — see §24** |
| **§25** | **The hub owns create/edit — fixes 3 regressions from §23** | ✅ **Complete — see §25** |
| **§26** | **Project hub: one budget surface, a doughnut chart, a settable annual hours target** | ✅ **Complete — see §26** |
| **§27** | **University targets: three blocks removed, Project-targets sort fixed (it was inert)** | ✅ **Complete — see §27** |
| **§28** | **Retired-vocabulary sweep + a D-R7 leak the project-scoped guards missed** | ✅ **Complete — see §28** |
| **§29** | **Access mismatches: a link a role can see but cannot reach — three fixed** | ✅ **Complete — see §29** |
| **§30** | **Demo cohorts for the six legacy projects — hours now match the prototype exactly** | ✅ **Complete — see §30** |
| **§31** | **The safe-zone project set (5 live / 8 archived) + the project archive with visibility and restore** | ✅ **Complete — see §31** |
| **§32** | **Faculty leaderboard: the value carries its unit; the sub-line carries activities** | ✅ **Complete — see §32** |
| **§33** | **AI analysis split into a queue + a review surface (approved analyses get a reading surface)** | ✅ **Complete — see §33** |
| **§34** | **Project narratives: search / filters / pagination, plus six AI-surface defects (a lying toast, a `config:cache` API-key blocker, `prompt vv2`, an unreachable `pending`, a mislabelled attempt, a hub audit view on dead keys)** | ✅ **Complete — see §34** |
| **§35** | **Vocabulary: "program" → "project" on the narrow entity (29 files, text only) and "Executive" dropped from the narrative's name** | ✅ **Complete — see §35** |

**PLAN STATUS: P0 + R1–R6 COMPLETE. R7 (documentation & verification) IN PROGRESS — no feature work
outstanding.** All owner decisions are locked (§2 / §2.1); all four open questions resolved. The
prototype pass (P0) validated the revised frontend before any Laravel work, and R1–R6 then shipped it.
Suite: **491 tests / 2894 assertions, 0 failures**; all six prototype harnesses green.
**Eleven post-R7 amendments have since landed — §20 (v4.19), the budget-basis correction; §21 (v4.20),
which removed the Admin Analytics page; §22, which widened the faculty self-edit; §23, which added the
Programs level to the college hub; §24, which redesigned that hub's college-selected view; §25, which
moved program/project create-and-edit into the hub; and §26, which consolidated the project hub's budget
surfaces, made its chart a doughnut, and made the annual hours target settable from the Edit modal;
and §27, which removed three blocks from the university targets page and fixed its Project-targets sort;
and §28, the retired-vocabulary sweep that also closed a D-R7 leak on the faculty rendered-hours page;
and §29, which widened the Secretary's import-template route and fixed two more links a role could see but
not reach; and §30, which gave the six legacy projects real demo cohorts so their rendered hours match the
prototype instead of reading 0.4–0.7 %.**

**R7 completed:** the docs reconciliation (handoff / README / blueprint / banners), then the code
items — the `/colleges` two-view hub, the `/programs` fidelity pass, the **collapsed admin nav**
(§19.6.3), the **nightly database backup** (§19.6.4) — the **defence walkthrough**, rewritten for the
revised model and executed (§19.7) — and finally the **blueprint stale-section rewrite, v4.15 → v4.16**
(§19.8). See §19.6, §19.7 and §19.8.

**What R7 still owes:** the production deploy itself — nothing else. The `docs/guides/*` and the role
guides were **renamed and rewritten** for the revision and verified by reading them on 2026-09-25 (an
earlier version of this line claimed they were "bannered, not rewritten"; that was **wrong**, asserted
rather than checked). `docs/TEST-SCRIPT.md` and the blueprint are **current**.

> **R5 is complete — see §16.** Rankings, filters, the Admin dashboard and the four print reports all
> run on the R4/R5 metric dictionary, and the R5 audit additionally **found and closed a D-R7 gap in
> the AI surface** (five classes were still shipping the retired 8.6 vocabulary to the Gemini prompt
> and the Director's inbox). Suite is now **403 tests / 1771 assertions**.
> Next: **R6** — the interagency catalogue (`interagency_agencies`) + `PromptV2`, with the D-R8
> SENIOR CARE programme.
> **R6 is complete — see §17.** **R3c is complete — see §18** (it was unblocked since R4 and has now been
> picked up and closed). **R7 is the only phase outstanding** — and it is documentation and verification
> only. Current suite: **444 tests / 1960 assertions**.

> **R4 is complete — see §15.** The training-hours formula is now `trainors × trainees × days` with no
> `× 8`, the target model is a consumption pool, and every 8.6 KPI surface has been removed (D-R7).
> `activities.no_of_days` now exists, so `trainingIsMeasurable()` is `true`. **R3c has since been picked
> up and completed — see §18.**

> **Both previously-open prototype items are now closed — see P0n (§11.16) and P0o (§11.17).**
> `targets.html` carries the §2.2C pending banner and no longer asserts the rejected model; the
> unreachable Objective authoring surface is gone from `program-detail.html`. The prototype is now
> free of known dangling items, so R1 can start from a clean state.

---

## 11. PHASE P0 — PROTOTYPE PASS (COMPLETE)

The static prototype under `docs/prototype/` now renders the revised model end to end, so the
frontend can be reviewed before the real implementation starts. Nothing in `app/`, `database/`,
or `resources/` was touched.

### 11.1 What changed

| Area | Change |
|---|---|
| **Data foundation** | `assets/js/seed-data.js` rewritten: `DATA.programs` = 6 broad programs (`PROG-*`), `DATA.projects` = 7 projects (college-prefixed), plus `universityTargets`, `universityActuals`, `interagencyAgencies` (10 rows), `mostActiveProjects`, `mostActiveFaculty`. Activities carry `noOfDays`/`trainors`/`trainingHours`/`formula`. Narratives carry tiered `recommendations` (T1:6 / T2:4 / T3:1). A throwing `programAlias` guard prevents re-introducing the programs/projects alias. |
| **Navigation** | `assets/js/layout.js` — admin nav regrouped into Overview / Colleges & Programs / Management / Approvals / Intelligence & Reports, with new **University Targets**, **Extension Projects**, **Project Narratives**, **Interagency Catalogue** entries. New helpers: `num, hours, collegePill, pillarChip, tierBadge, tierMeta, collegeMeta, hoursFormula, attainTone, attainBadge`. |
| **Styling** | `assets/css/smartcemes.css` — new components: `.college-pill`, `.pillar`, `.tier-badge`, `.tier-card`, `.stat-tile`, `.hier-rail`, `.hours-bar`, `.rank-medal`, `.expertise-tag`, `.interagency-note`. |
| **Pages rewritten** | `programs.html` (broad programs), `program-detail.html` (project performance hub — 8.6 KPI scorecard removed, replaced by Training Hours vs Annual Target + Budget vs Annual Target + Attendance; 5 stat tiles; Activities table now shows Trainors / Trainees / Days / Training Hrs), `faculty-management.html` (performance + expertise + involvement + Most Active Faculty ranking + drawer), `dashboard-admin.html` (target-vs-actual tiles, hours chart, guardrailed AI panels), `ai-analysis.html` (CESO Scope Guardrail panel + three-tier intervention cards), `program-narratives.html` (tier badges, agency attribution). |
| **Pages new** | `pages/projects.html` (project list + **Most Active Projects** rank view), `pages/colleges.html` (3-college comparison), `pages/targets.html` (**University Targets** vs actuals), `pages/interagency.html` (**Interagency Catalogue**, admin-editable), `index.html` (role picker + change summary at the prototype root). |
| **Legacy call-sites** | 10 sites across `analytics.html`, `calendar.html`, `faculty-management.html`, `communities.html`, `program-detail.html`, `program-narratives.html`, `proposal-new.html` updated from `DATA.programs` → `DATA.projects`. |
| **Stub redirects** | `activities.html` / `beneficiaries.html` / `budget.html` now redirect to `projects.html` (the project hub) instead of the old analytics/programs targets. |
| **Docs** | `docs/prototype/PATTERNS.md` gained a **v4.2 rules** block superseding v4.1 where they conflict (hierarchy, KPI removal, hours formula, two-level targets, three-tier guardrail, D-R11). |

### 11.2 Verification

Two harnesses live beside the prototype and are re-runnable:

```
node docs/prototype/_check.cjs    # data + structure
node docs/prototype/_smoke.cjs    # executes every page's scripts against a DOM stub
```

- `_check.cjs` — seed loads clean (6 programs / 7 projects / 3 colleges / 8 faculty); `DATA.programs === DATA.projects` is `false`; tier counts T1:6 / T2:4 / T3:1; all inline scripts parse; no BOM, no mojibake, no `<div>` imbalance; every nav/index-referenced page exists; every `[[icon]]` token resolves; every deep-linked program/project code is valid.
- `_smoke.cjs` — all **28 pages** load `layout.js` + `seed-data.js`, run their inline scripts, and fire `DOMContentLoaded` without throwing.

Bugs found and fixed by the harness during the pass:
1. `p.trainors` is a **number** (trainor count), not an array — `targets.html` was calling `.forEach` on it.
2. `ai-analysis.html` did `const TIER = SC.tierMeta` then indexed `TIER[tier]` — indexing the function instead of calling it.
3. `program-narratives.html` had a double `.join('')` (`.join('') : [...]).join('')`) — the fallback branch produced a string then tried to join it.

### 11.3 How to preview

Open `docs/prototype/index.html` in a browser (no build step, no server required — it is static
HTML with local vendored assets). Pick a role, or jump straight into a deep link.

### 11.4 P0 follow-up — collapsed hierarchy nav (P0b, COMPLETE)

Owner feedback after reviewing P0: the admin sidebar must show **one** entry for the whole
extension structure, not three. Implemented:

| Area | Change |
|---|---|
| **Sidebar** | The `Colleges & Programs` section is replaced by a single section `Extension Programs` holding exactly one item — **Manage Extension Programs** (`icon:'folder'`, `badge:3`, → `colleges.html`). `Colleges`, `Extension Programs` and `Extension Projects` no longer exist as sidebar items. `Communities & Partner Schools` moved to the `Management` section beside `Faculty Management`. |
| **Active-state fan-out** | New `isActive(it)` in `layout.js` lights a nav entry when the page key matches **or** is one of the entry's declared `subs[]`. The hub declares `subs:['colleges','programs','projects','program-detail']`, so every drill-down keeps the entry highlighted. `pageTitle()` now also resolves a **parent** label for sub-keys, so the topbar reads "Manage Extension Programs" while a project hub is open. |
| **Drill-down rail** | `colleges.html` / `programs.html` / `projects.html` / `program-detail.html` each carry a 4-step rail — College → Program → Project → Activity — under a `Manage Extension Programs` breadcrumb. The current level is marked `on` plus a trailing "(this page)". New CSS: `a.hier-node`, `.hier-step`, `.nav-chevron`. |
| **Hub landing page** | `colleges.html` is now the hub: header reads **Manage Extension Programs**, subtitle states the drill-down chain, plus a "Drill down" rail. A focused college (`?college=CME`) keeps the hub breadcrumb and just swaps the heading. |
| **Deep link** | `program-detail.html` `data-page` changed `"programs"` → `"program-detail"` so the hub entry (not a phantom page) reflects the active state. The Activities tab section gained `id="hubActivities"` for the rail's step-4 anchor. |
| **Docs** | `PATTERNS.md` gained a **v4.3** rules block (single hub entry, `subs[]` contract, required rail markup, `program-detail` page key). |

Verification: `_check.cjs` gained three new assertions — the admin nav must contain the hub entry,
must **not** contain separate `Colleges` / `Extension Programs` / `Extension Projects` items, every
`subs[]` key must match a real page's `data-page`, and all four rail levels must exist with the
current one marked and highlighted. Both harnesses pass.

New bug caught: the Edit tool reintroduced a **UTF-8 BOM** at the top of `assets/js/layout.js`,
which broke strict parsing of the file's leading IIFE. Stripped, and `_check.cjs` now scans
`assets/js/*.js` and `assets/css/*.css` for BOM/mojibake too (previously HTML pages only).

### 11.5 P0c — rendering defects found in review (COMPLETE)

Owner reported two visual faults: text showing as `{text}`, and oversized icons. Investigation
found three distinct root causes.

| # | Defect | Root cause | Fix |
|---|---|---|---|
| **1** | **Icons rendered larger than requested** (the "big icons") | `SC.svg(name, cls)` always emitted `w-5 h-5` **and** appended the caller's class. Tailwind orders `.w-5` *before* `.w-3.5` in its utility layer, so the 20px default won and every `SC.svg(x, 'w-3.5 h-3.5')` / `!w-4` / `!w-[18px]` call site rendered at 20px. 28 `.js-icon` wrappers + 9 direct `SC.svg` calls affected. | `svg()` now detects a `w-`/`h-` class in `cls` and **omits** the default when one is present. Also fixed call sites that passed only `w-` or only `h-`. |
| **2** | **`[[clipboard]]`, `[[pin]]`, `[[users]]` etc. shown as literal text** (the `{text}` symptom) | `boot()` expands `[[token]]`s once at `DOMContentLoaded`, but most pages render their markup **from JS afterwards** (hub stat tiles, activity card, community chips, interagency notes). Those tokens were injected after the only expansion pass, so they stayed literal. **14 occurrences** across 5 pages. | Added `watchIconTokens()` — a `MutationObserver` on `document.body` (`childList` + `subtree`) that re-expands tokens in any later-injected node. Re-entrancy guarded. `SC.expandIcons` is now exported too. |
| **3** | **Double-encoded text** — `Â· · Ã— â€“ â€" â€¦ â˜… Ã—` | Three page files had been saved through a cp1252 round-trip, so real UTF-8 bytes were re-encoded as cp1252 chars. 111 mangled sequences in `assessment-form.html` (46), `proposals.html` (43), `availability.html` (22). | Repaired losslessly: each run of cp1252-mappable chars was re-encoded to bytes and re-decoded as UTF-8, verified to round-trip. Backup taken first, then removed after verification. |

Verification extended in `_check.cjs`:
- **`[ICON]` guards** — `watchIconTokens()` must exist, be wired into `boot()`, and `expandIcons` must be exported; the `svg()` sizing ternary must still suppress the default; no `SC.svg()` call may pass only one of `w-`/`h-`.
- **`[MOJI]` guard** — re-encodes cp1252-mappable runs (length ≥ 2) and flags any that decode as valid UTF-8. A lone `·` or `—` is correctly treated as legitimate typography.
- Both guards were **mutation-tested** (deliberately broken, confirmed to fail, then restored).

Result: `_check.cjs` → `OK — all checks passed.`; `_smoke.cjs` → 28/28 pages execute cleanly;
zero BOM files; zero unknown icon tokens; zero remaining double-encoded sequences.

`PATTERNS.md` gained a **v4.4** block documenting the `svg()` sizing contract, the
`[[token]]`-works-in-JS guarantee, and the UTF-8-without-BOM rule.

### 11.6 P0d — Colleges hub becomes a two-view flow (COMPLETE)

Owner feedback: *"when Manage Extension Programs is clicked it should only show the cards of the
three colleges no other stuffs, then when a college is clicked, it will then show the projects of
that college, remove also the Drill down. Make the frontend UI/UX better."*

Two changes in one: the hub page became a **two-view flow**, and the **drill-down rail was deleted
system-wide**.

**View model** — `pages/colleges.html` now holds two mutually exclusive views toggled by `hidden`:

| View | Content |
|---|---|
| **`#viewColleges`** (default) | Header **Manage Extension Programs** + subtitle *"Select a college to see its extension projects"*; a 4-tile university roll-up strip (colleges / projects / training hours / beneficiaries); then **only** the three college cards — no project table, no program table, no faculty grid, no rail. |
| **`#viewCollege`** (hidden) | Back button (**← All colleges**), college hero (crest, name, dean, thrust, colour from `college.color`), 4 KPI tiles, **Projects** heading + search box (`#projQ`) + status chips, the project card grid, then a **Faculty** section. |

Interaction contract (page script):
- `showCollege(code, pushUrl)` — swaps views, fills hero/KPIs/project grid/faculty grid from
  `DATA.colleges`, and pushes `?college=<code>` into history.
- `goBack(pushUrl)` — reverses the swap and restores the bare hub URL.
- `renderProjects()` — re-filters the grid from `#projQ` + the status chips without leaving the view.
- `popstate` is wired to `history.pushState`/`replaceState`, so browser Back/Forward traverses the
  two views instead of leaving the page.

**Visual upgrade** — new components in `smartcemes.css`:
`.college-card` (hover `translateY(-4px)` + shadow), `.college-card-top` (colour stripe), `.college-crest`,
`.college-metrics`/`.m-val`/`.m-cap`, `.college-foot`, `.college-card-cta`, `.proj-card`,
`.proj-card-stripe`, `.proj-stat`/`.s-val`/`.s-cap` (trainors / trainees / activities), `.hub-back`,
`.hub-empty`, `.hub-eyebrow`. College cards are `<button>`s (keyboard-reachable), and the responsive
grid is `md:grid-cols-2 xl:grid-cols-3`.

**Drill-down rail removed** — the 4-step `College → Program → Project → Activity` rail was deleted
from **all four** pages that carried it (`colleges.html`, `programs.html`, `projects.html`,
`program-detail.html`). The rail's former job — showing hierarchy and deep links — is now served by
the hub card flow itself. `a.hier-node` / `.hier-step` CSS is retained but unused.

**Verification** — the obsolete rail assertion in `_check.cjs` was replaced by a new **§7 hub
contract** block, and a **third** harness was added:

```
node docs/prototype/_check.cjs     # structural
node docs/prototype/_smoke.cjs     # every page executes
node docs/prototype/_hubtest.cjs   # functional: the click-through itself
```

`_hubtest.cjs` drives the real interaction against a mini-DOM with working event dispatch —
render view 1, assert it holds **3 college cards and nothing else**, click CAS, assert the views
swapped and only CAS's 5 projects rendered (no COE/CME leakage), click back, assert view 1 returned,
then load `?college=CME` and assert the deep link lands on view 2 with KABUHIAN. **21 assertions.**

`_check.cjs` §7 now asserts: the rail is gone from **every** page; `colleges.html` contains
`viewColleges` / `viewCollege` / `collegeCards` / `projGrid` / `backBtn` / `collegeKpis`; it does
**not** contain the old `#strip` / `#cards`; the `.college-card` click is wired; `?college=` is still
handled; and `goBack` / `popstate` / `pushState` are all present.

Result: `_check.cjs` → `OK — all checks passed.`; `_smoke.cjs` → 28/28 pages clean;
`_hubtest.cjs` → `PASS — 21 assertions`.

Rig bugs fixed while building the harness (not page bugs): the dispatched event needed
`closest()` on `event.target` (not on the event object); the card-count regex had to count
`class="college-card[ "]` roots rather than the bare `college-card` substring, which also matched
`college-card-top` / `college-card-cta`; and `pageSandbox.window` needed `scrollTo`/`scrollBy`/
`setTimeout`/`getComputedStyle` stubs because the page script calls them directly.

`PATTERNS.md` gained a **v4.5** block (two-view hub contract, no-rail rule, `_hubtest.cjs` usage).

### 11.7 P0e — view 1 stripped to college cards only (COMPLETE)

Owner feedback: *"remove this cards, just show the colleges, also remove the training hours per
college since theres no 'per college' training hours target."*

Two corrections, one of which also **fixes a modelling error**:

| # | Change | Detail |
|---|---|---|
| **1** | **University roll-up strip removed** | The 4-tile `#uniStrip` (Colleges / Extension projects / Training hours rendered / Beneficiaries reached) is deleted from view 1, along with the `tile()` helper and the five reduce aggregations (`totHours`, `tgtHours`, `totProj`, `totFac`, `totReach`). View 1 is now **only** the header and the three college cards — nothing else. |
| **2** | **Per-college training hours removed (a real modelling bug)** | College cards, the college hero, and the view-2 KPI tiles all displayed a *college-level* `Rendered / Target` training-hours bar. **No such target exists** — per D-R (targets scope) training-hours targets live at **university and project level only**. College cards now show Projects / Programs / Faculty only; the hero's second bar is repurposed to **Beneficiaries reached**; the view-2 KPI tile became **Budget utilized** (`of ₱X annual`). |

The project card's own Training-hours progress bar is **correct and retained** — project-level
targets are real.

**Verification** — `_check.cjs` §7 gained two guards, and `_hubtest.cjs` three:

- `_check.cjs` — `uniStrip` added to the dead-id list; a new guard fails on any
  `college.hoursPct` / `c.hoursPct` / `college.trainingHoursTarget` / `c.trainingHoursTarget`
  reference; and the `Training hours` label count must be **exactly 1** (the project card).
- `_hubtest.cjs` — asserts college cards carry **no** `Training hours` bar and no
  `trainingHoursTarget` figure, view 1 has no roll-up tiles, the cards *do* still show
  Projects/Programs/Faculty, and neither the hero nor the KPI tiles carry a college-level hours
  target. **27 assertions** (was 21).

Both new guards were **mutation-tested**: reintroducing the roll-up container and a
`c.hoursPct` read made `_check.cjs` fail with both messages; making the card actually *render* a
Training-hours bar made `_hubtest.cjs` fail on the exact assertion. Restored and re-verified.

Result: `_check.cjs` → `OK — all checks passed.`; `_smoke.cjs` → 28/28 clean;
`_hubtest.cjs` → `PASS — 27 assertions`.

`PATTERNS.md` §7 updated with the **no-per-college-hours** rule.

### 11.8 P0f — formula `× 8` removed; dashboard refocused on performance (COMPLETE)

Owner review of the admin dashboard produced four changes, one of which **amends a locked decision**.

| # | Change | Detail |
|---|---|---|
| **1** | **`× 8` removed from the training-hours formula** | Formula is now `trainors × trainees × days`. See **§2.2A** for the full amendment, the before/after table, and the arithmetic audit. Touched: `seed-data.js` (formula string), `program-detail.html` (2 code sites + 1 caption + 1 label), `targets.html` (formula card + worked example), `dashboard-admin.html` (header). |
| **2** | **"FY2026 university targets" line removed** from the dashboard header | The formula note stays; the FY2026 framing is gone. The annual target is now labelled simply **"annual SmartCEMES target"**, with the draw-down behaviour stated on the card. |
| **3** | **"Trainees / Beneficiaries Served" card removed** | The KPI row is now **4 cards** (`grid-cols-5` → `grid-cols-4`): Extension Projects · Training Hours Rendered · Budget Utilized · Pending Approvals. Rationale accepted: the Director's job on this screen is performance and workload, not reach volume. |
| **4** | **"Performance Leaders" section ADDED** | A new two-panel section directly under the KPI row: **Most Performing Projects** (top 5, ranked by training hours rendered, each with activities/trainors and % of target) and **Most Performing Faculty** (top 5, by rendered hours, with college and project count). This delivers the "most performing programs / most performing faculty" intent the owner asked for. |

Also corrected while in there: the dashboard's **"Training Hours vs Target" chart was keyed off
`DATA.programs`** (the 6 broad programs), but programs carry no hours target — it now reads
**`DATA.projects`** and is labelled "by project". The projects card's sub-label had stale status
counts and now reads `5 ongoing · 2 draft · 3 colleges · 6 broad programs`.

**Data reconciliation (verified, not assumed).** Under the corrected formula the seed data is fully
coherent: project rendered-hours total **968** equals the completed-activity sum **968**
(6/6 activities satisfy `trainors × trainees × days`, 0/6 satisfy `× 8`), budget utilized
**₱164,950** of **₱315,000**, annual target **2,500 hrs**, project-target sum **2,640 hrs**.
The previously-displayed 968 was already correct; only its label was wrong.

**Verification** — `_check.cjs` gained a `[FORMULA]` guard block:
- no source (page / seed / layout) may contain the string `× 8 hrs` or `x8 hours`;
- no code may multiply a training-hours expression by 8 (`* 8;`, `days * 8`, `noOfDays * 8`);
- the canonical `trainors × trainees × days` string must exist somewhere;
- every seeded activity must arithmetically satisfy the new formula;
- the project rendered-hours rollup must equal the completed-activity sum.

All five were **mutation-tested**: reintroducing `× 8 hrs` in the formula string and corrupting one
activity row to 896 made `_check.cjs` fail with both messages. Restored and re-verified.

Result: `_check.cjs` → `OK — all checks passed.`; `_smoke.cjs` → 28/28 clean;
`_hubtest.cjs` → `PASS — 27 assertions`.

#### Deferred by this pass (open items)

- **`targets.html` needs a rewrite.** It still presents a University-targets register with a
  **per-program** breakdown, which contradicts §2.2B. Its formula card is corrected, but its
  structure is not final. Flagged for a later pass.
- **Objectives surface on `program-detail.html`** — still unreviewed (carried over from P0d).

### 11.9 P0g — Faculty Engagement board replaces the "Most Active Faculty" table (COMPLETE)

Scope: **`faculty-management.html` only.** No other page, and no shared file beyond the additive CSS
block, was touched.

#### What was replaced

The old section was a 7-column `sc-table` (`Rank · Faculty · College · Expertise · Projects ·
Hours vs Target · Attainment`) driven by `renderRank()` over `DATA.mostActiveFaculty`. Ranked tables
make the reader do the comparison work in their head, and on a page whose purpose is *"how is the
extension load distributed?"* that is the wrong instrument.

#### What replaced it — a 5/3 two-column board inside one card

| Zone | What it shows |
|---|---|
| **Header** | Title **Faculty Engagement**, a subtitle, the **AY 2026–2027** badge, and a segmented **metric switch** (Hours rendered / Attainment % / Projects). |
| **Left (col-span-3)** | A **horizontal bar chart**, one bar per faculty member, coloured by college. In *hours* mode a dashed **target line dataset** is overlaid so each person is read against their own target rather than a shared scale. Below it, a **load-by-college split strip** with a percentage legend. |
| **Right (col-span-2)** | A **leaderboard** of ranked rows — rank chip, avatar, name, college + project count, headline value with its caption, and a thin attainment rail. Each row has a proportional tint behind it, so the ranking is legible as a shape as well as a list. Then a **"What this says"** panel of insight notes. |

**Interaction:**
- The **metric switch** re-renders chart + leaderboard + insights in place. All three modes share one
  `METRICS` table (`{ label, sub, value, fmt }`), so adding a fourth metric is a one-line change.
- **Clicking a chart bar** or a **leaderboard row** opens the existing faculty drawer via
  `openFaculty(id)` — the drawer, filters, directory table and modal are untouched.
- In *pct* and *projects* modes the target-line dataset is correctly dropped (a target line is only
  meaningful against hours).

**Data honesty details:**
- Bars are coloured with the college brand colours (`CAS #003599`, `COE #F6B800`, `CME #10b981`).
- Faculty with `status === 'On Leave'` get a muted outline so the *active* load reads cleanly.
- The insights are **computed, not written**: top performer, roster-wide attainment, the active
  faculty below 50% of target (with the lowest named), and anyone with no project assignment.
  Verified against the seed: 53% roster attainment · CAS 47% / COE 40% / CME 13% of hours ·
  2 active faculty below 50% (Alcoy 30%, Maglasang 43%) · 1 unassigned (Alcoy).

#### CSS (additive only)

New `FACULTY ENGAGEMENT BOARD` block in `smartcemes.css`: `.eng-switch`, `.eng-row`,
`.eng-row-fill`, `.eng-row-inner`, `.eng-rank` (+ `.top1/.top2/.top3`), `.eng-val`, `.eng-sub`,
`.eng-rail`, `.eng-split`, `.eng-split-legend`, `.eng-dot`, `.eng-chart-wrap`, `.eng-note`.
Reuses existing tokens (`.sc-card`, `.avatar`, `.badge`, `.expertise-tag`) so nothing else shifts.
`Chart.js` is loaded from the **vendored** `assets/vendor/chart.umd.min.js` — no CDN.

#### Verification

`_check.cjs` gained an `[ENGAGE]` guard block: the dead table identifiers (`rankBody`,
`Most Active Faculty`, `rank-medal`) must be **absent**; the seven board anchors must exist; the
vendored chart script must be loaded; all three metric modes present; the switch wired; the chart
horizontal; a bar click must route to `openFaculty`; the `COLORS` map must match the three brand
hexes; leaderboard rows must open the drawer; and the drawer functions must survive.

A **fourth harness**, `_facultytest.cjs`, drives the board against a DOM stub with a **Chart.js
capture stub** (it records each config so assertions can inspect datasets/labels/scales). It proves
chart construction, one bar per faculty, the target-line dataset appearing in *hours* mode and
disappearing in the other two, college colouring, unique axis labels, leaderboard/attendance-rail
counts, the college split, and the leaderboard captions per mode. **30 assertions.**

Mutation-tested: corrupting `COLORS[CAS]`, deleting the bar `onClick`, and removing the vendored
chart script each produced exactly the expected `[ENGAGE]` failure; restored and re-verified.

Result: `_check.cjs` → `OK — all checks passed.` · `_smoke.cjs` → 28/28 pages clean ·
`_hubtest.cjs` → `PASS — 27 assertions` · `_facultytest.cjs` → `PASS — 30 assertions` · zero BOM.

---

### 11.10 P0h — New Faculty Profile modal, faculty self-edit, and the Faculty Directory page (COMPLETE)

Scope asked for: improve the **New Faculty Profile modal** *and* the **faculty-side edit view**; add
basic-info fields; drop Specialization; turn Expertise into a multi-select; put Position on a ranked
ladder; remove the per-professor Target Training Hours and adjust metrics to hours + involvement only;
and replace the "Add Faculty" entry point with a **"Faculty Directory"** button opening a new
dedicated page.

#### What changed

| File | Change |
|---|---|
| `assets/js/seed-data.js` | Removed `spec` + `targetHours` from every faculty record; added `birthdate`, `sex`, `civilStatus`, `address`; corrected all 8 positions to real ladder ranks. Added `expertiseOptions` (24 canonical values) and `positionLadder` (18 ranks, grouped, `{rank, group, label}`), plus derived `positionRank` and `initials`. Added a **faculty × project hydration** pass after `projects` is built, producing `projectList` / `projectCount` / `leadCount` / `coLeadCount` / `communityCount`. `mostActiveFaculty` no longer carries `targetHours`/`pct`. |
| `pages/faculty-management.html` | Header action **"Add Faculty" → "Faculty Directory"**, routing to `faculty-directory.html?role=admin&new=1`. KPI strip **5 → 4 cards** (attainment card deleted). Engagement-board switch **hours / pct / projects → hours / projects / leads**. All target-line, attainment-rail and target-prose sites removed. Modal rebuilt: Identity / Assignment / Contact sections, new personal fields, expertise chip multi-select, grouped 18-rank position select, auto-computed age, and an explicit "no per-professor target" notice. |
| `pages/faculty-directory.html` | **NEW.** The dedicated roster-management page: 4-card summary, search / college / expertise / status / sort toolbar, responsive card grid, profile drawer, and the full New-Faculty modal. **Role-aware** — `?role=faculty` renders "My Faculty Profile": the self-service panel, the toolbar trimmed to essentials, and a **self-edit drawer that auto-opens** with contact + expertise editable and Employee ID / Position / College / Status rendered as locked `.locked-field`s. |
| `pages/colleges.html` | Faculty card lost its `targetHours` progress bar and `spec` line; now shows **hours rendered + project involvement** and deep-links into the directory. |
| `pages/program-detail.html` | Trainor list dropped `pct`/`targetHours`; sub-label is now `expertise[0] || position`; trailing metric is "N hrs rendered". |
| `assets/css/smartcemes.css` | New blocks: `.fac-mini*` (college faculty cards), `.modal-legend` / `.req`, the `.ms-*` **multi-select** component, `.self-banner` / `.self-meter` / `.locked-field` (the faculty self-edit view). |
| `assets/js/layout.js` | Three new icons (`list`, `pencil`, `lock`). Nav: `faculty-management` gains `subs:['faculty-directory']` so the highlight rule covers both; faculty role gains a **My Profile** section → "My Faculty Profile". |
| `index.html` | Lists Faculty Directory for both admin and faculty; faculty-management card text rewritten; the stale "Most Active Faculty →" anchor (pointing at a deleted `#ranking`) replaced. |

#### Design decisions worth recording

- **The per-professor target was removed *everywhere*, not just in the modal.** The user's stated
  reason ("there is no per-professor training target") is a *model* fact, so leaving attainment bars on
  the roster table, the drawer or the colleges card while deleting the input would have been
  inconsistent. Blast radius was ~15 sites across three pages; all were converted to
  hours-rendered + project-involvement.
- **`projects:` on a faculty record was a hand-maintained count that could drift.** Replaced with a
  derived `projectList` built from the `projects` array. `_facultytest.cjs` now asserts
  `leadCount + coLeadCount === projectCount` and `projectCount === projectList.length`.
- **Expertise had to become a controlled vocabulary, not just a dropdown.** Project-to-faculty matching
  is the reason expertise exists, so free text was actively harmful. `expertiseOptions` (24 values) is
  now canonical, and every seeded expertise value is asserted to be a member.
- **The ladder is stored as data, not markup.** `positionLadder` lives in the seed with `rank` +
  `group`, and the select is built into `<optgroup>`s at runtime. The old 8-item hardcoded list
  (`Associate Professor`, `Professor I`, …) is explicitly banned by `_check.cjs`.
- **A separate page, not a bigger modal.** The user asked for a dedicated directory page, so
  `faculty-management.html` stays a *performance* screen and the roster/profile work moved to
  `faculty-directory.html`. The modal itself is reused on both, so there is one form to maintain.
- **Separation of duties drives the self-edit locks.** Faculty may correct their own contact details and
  expertise (they own that fact); they may **not** change their position, college, status or employee
  ID (the institution owns those). The locks are visible and labelled, not just disabled.

#### Verification

`_check.cjs` gained a **`[FACDIR]` block** and the `[ENGAGE]` block was rewritten. New assertions:

- the `pct` metric mode, `f.targetHours`, `attainBadge` / `eng-rail` / `hours-bar` and `f.spec` are all
  **banned** on `faculty-management.html`;
- no faculty record may carry `targetHours`, `spec` or `pct`;
- all 18 ladder labels must be present, `rank:18` must exist, and every seeded `position` must resolve
  to a ladder rank;
- every seeded `expertise` value must be a member of `expertiseOptions`;
- the modal must expose the nine required field ids and must **not** render a Specialization or
  Target-Training-Hours *input* (prose mentioning their removal is fine);
- `faculty-directory.html` must exist with its ten required ids, a role-aware branch, and a
  `selfEditBody` / `locked-field` self-edit view;
- the entry point must read "Faculty Directory", must **not** read "Add Faculty", and must route to
  the directory page;
- the nav must reference `faculty-directory.html` and list it under `subs`.

`_facultytest.cjs` was rewritten around a reusable `boot()` helper and grew from **30 → 100
assertions** across five suites (engagement board · modal contract · directory + self-edit · entry
point · colleges card). It now also exercises the faculty role end-to-end by booting
`?role=faculty` and asserting the self-edit drawer opens with locked assignment fields.

**Mutation-tested.** Reintroducing `data-metric="pct"` + `f.targetHours` → 2 `[ENGAGE]` failures.
Reintroducing `#afSpec` + `#afTargetHours` + their labels → 4 `[FACDIR]` failures. Both restored.

Result: `_check.cjs` → `OK — all checks passed.` · `_smoke.cjs` → 29/29 pages clean ·
`_hubtest.cjs` → `PASS — 27 assertions` · `_facultytest.cjs` → `PASS — 100 assertions` · zero BOM.

#### Still open (unchanged by P0h)

- `targets.html` still shows a per-program breakdown and needs the rewrite flagged in §2.2C.
- The **Objectives** surface on `program-detail.html` remains unreviewed.

---

### 11.11 P0i — Faculty Management reduced to the board only (COMPLETE)

**Ask:** remove the redundant header, the KPI cards and the Faculty Roster table from the admin's
Faculty Management page — "since it opens when Faculty Directory is clicked" — retaining only the
Faculty Engagement board.

#### What changed

`pages/faculty-management.html` went from **938 → 755 lines**. Removed:

| Removed | Reason |
|---|---|
| `<h2>Faculty Management</h2>` + the "Engagement dashboard — hours rendered and project involvement…" sub-heading | Redundant. The sidebar already identifies the page, and the board card now carries its own title, so the page starts with the board rather than two stacked headings. |
| the 4-card KPI grid (`#kpis`) | The board answers the same question more directly; the cards were a second summary of the same numbers. |
| the filter toolbar (`#facSearch`, `#collegeTabs`, `#expertiseSel`, `#statusSel`, `#sortSel`) | These filters exist to filter the **roster**, which is no longer on this page. |
| the **Faculty Roster** table (`#facultyBody`, `#facCount`, `#facEmpty`) | Pure duplication — the very thing the "Faculty Directory" action opens. |

Kept: a right-aligned action row (**Export roster** · **Faculty Directory**) and the board card. The
board's card header now holds `<h2 id="heading">Faculty Engagement</h2>`, so the heading anchor that
`_check.cjs` and the page script rely on still exists.

Also removed: all the now-dead JS — the KPI render block, `expertiseSel` options, the `query` /
`expertise` / `status` / `sort` state, `sortFn()`, `filtered()`, `render()`, and the five toolbar
listeners. The drawer, the modal and the multi-select are untouched.

#### The `?college=` deep link had to be repointed

The deep link previously tinted a toolbar tab and rewrote the `<h2>`; both of those are gone. Rather
than drop the feature, it now **narrows the board itself** through a single `SCOPE` constant that
`ranked()`, `drawSplit()` and `drawInsights()` all read:

```js
const SCOPE = college === 'All' ? FAC.slice() : FAC.filter(f => f.college === college);
```

So the chart, the leaderboard and the insight notes narrow **together** and cannot disagree, and a
`banner` states "Board narrowed to this college" with a link back to the full board. This is a
strictly better outcome than the old behaviour, which left the chart showing everyone.

#### A trap worth recording

Deleting the KPI block removed the only consumers of `totHours` and `totProjects` — but
`drawInsights()` was still reading `totHours`. `_check.cjs` passed (it only checks strings exist) while
`_smoke.cjs` caught it immediately: `totHours is not defined`. The insight notes now compute
`sumHours` locally. **When deleting a block on a prototype page, grep for every constant it defined
before assuming nothing else consumed them** — the smoke harness is what actually proves this.

#### Verification

`_check.cjs` — the `[ENGAGE]` block gained a board-only guard: nine ids (`#kpis`, `#facSearch`,
`#collegeTabs`, `#expertiseSel`, `#statusSel`, `#sortSel`, `#facultyBody`, `#facCount`, `#facEmpty`),
the `Faculty Roster` heading, and the old sub-heading string are all **banned** on this page; the
`SCOPE` line is required; and `ranked()` ranking `FAC.slice()` is banned.

`_facultytest.cjs` — new **suite 3c** (14 assertions) proving the board survives, the removed
surfaces and their dead listeners are gone, and that `?college=CME` narrows the chart **and** the
leaderboard to the three CME faculty. Now **114 assertions**.

**Mutation-tested.** Reinstating `#kpis` + `#facultyBody` + the sub-heading and reverting `ranked()`
to `FAC.slice()` produced exactly 4 `[ENGAGE]` failures. Restored and re-verified.

Result: `_check.cjs` → `OK — all checks passed.` · `_smoke.cjs` → 29/29 pages clean ·
`_hubtest.cjs` → `PASS — 27 assertions` · `_facultytest.cjs` → `PASS — 114 assertions` · zero BOM.

#### Still open (unchanged)

- `targets.html` still shows a per-program breakdown and needs the rewrite flagged in §2.2C.
- The **Objectives** surface on `program-detail.html` remains unreviewed.

---

### 11.12 P0j — no page action row; the Directory action joins the board header (COMPLETE)

**Ask:** remove "Export roster", and move "Faculty Directory" inside the card container, to the right
of the AY 2026–2027 chip.

#### What changed

`pages/faculty-management.html` went from **755 → 749 lines**.

| Change | Notes |
|---|---|
| `<section class="pt-6 … flex justify-end">` (the PAGE ACTIONS row) deleted | It existed to hold exactly two buttons; with one gone it was a full-width row holding a single control, floating above the card with nothing to align to. |
| `#btnExport` "Export roster" deleted | Its only handler was a demo toast. Deleted the listener too, not just the markup. |
| `#btnAdd` moved **into** the board card header | Now the last child of the header's right-hand cluster, directly after `<span class="badge badge-gold">AY 2026–2027</span>`. |

The header cluster is `flex items-center gap-2.5 flex-wrap`, so the button inherits the card's own
padding and gaps instead of being hand-positioned. The `<main>` top margin moved from `mt-4` to `mt-6`
on both `#banner` and the board section, so the board still starts at the same visual height the action
row used to occupy.

Sizing follows the established convention for header-level actions (`colleges.html` uses the same
`!px-3 !py-2 text-[12px]` beside the identical college chips) rather than nudging `.btn` globally:

```html
<button id="btnAdd" class="btn btn-primary !px-3 !py-2 text-[12px]">[[users]] <span class="ml-1">Faculty Directory</span></button>
```

#### Verification

`_check.cjs` `[ENGAGE]` gained four guards: `btnExport` / "Export roster" banned, a `PAGE ACTIONS` row
banned, `#btnAdd` required in the header, and — the one that actually pins the request — the ordering
assertion `AY 2026–2027</span>\s*<button id="btnAdd"`, so the button cannot drift back to the left of
the chip.

`_facultytest.cjs` suite 3c gained **+6 assertions** (now **120**): the row and the button are gone, no
dead `btnExport.onclick` listener remains, the AY chip still anchors the cluster, the button sits right
of it, and `btnAdd.onclick` still routes to `faculty-directory.html?role=admin&new=1`.

**Mutation-tested.** Reinstating the action row with `#btnExport` (plus a `PAGE ACTIONS` comment) and
moving `#btnAdd` back to the left of the chip produced 3 `[ENGAGE]` failures and 4 functional failures.
Restored and re-verified.

Result: `_check.cjs` → `OK — all checks passed.` · `_smoke.cjs` → 29/29 pages clean ·
`_hubtest.cjs` → `PASS — 27 assertions` · `_facultytest.cjs` → `PASS — 120 assertions` · zero BOM.

---

### 11.13 P0k — Faculty Directory: cards, header description and export removed (COMPLETE)

**Ask:** inside the Faculty Directory, remove the cards, remove the redundant "Faculty Directory /
The single roster of extension faculty — profiles, declared expertise, and assignment details", and
remove the export roster button.

#### What changed

`pages/faculty-directory.html` went from **929 → 864 lines**.

| Removed | Why |
|---|---|
| the 4-card `#summary` strip (`Faculty on the roster` · `Active` · `Ranks in use` · `Expertise areas declared`) | A KPI digest sitting **directly above the roster that already shows all four per person** — the same duplication pattern as the board/KPI split removed in P0i. |
| the header export action — `#btnExport`, "Export roster" (admin) / "Download my details" (faculty) | Its only handler was a demo toast; nothing consumed the export. Removed for both roles. |
| the header description "The single roster of extension faculty — profiles, declared expertise, and assignment details" | Redundant with the breadcrumb + heading. Replaced with the four-word rider **"Roster of extension faculty"**. |

The board card title "Roster" and `#dirCount` were kept — the count is live and useful, the redundant
KPI cards were not.

#### What had to be kept, and why

- **`#hdrActions` is retained as an empty container.** `applyRole()` still writes into it, and the
  faculty branch rewrites `#heading` / `#subheading` / `#crumb`. Deleting the header wholesale would
  have silently broken the "My Faculty Profile" surface. `_check.cjs` now asserts all three anchors
  survive the trim.
- Deleting `renderSummary()` also meant deleting its `#summary` classList toggle inside `applyRole()`.

#### The orphaned helper (same class of bug as the `totHours` trap, §11.11)

`renderSummary()` was the **only** caller of the local `ico()` helper
(`<span class="…">${SC.icons[n]}</span>`). Removing the function left `ico` defined and never used, so
it was removed too. `_check.cjs` now fails if `const ico = ` reappears on this page.

This is the second time on this prototype that deleting a block left orphans behind — see §11.11 for
the `totHours` case. **The rule generalises: when you delete a block, audit (a) every identifier it
*read*, and (b) every identifier that existed *solely to serve it*.** `_smoke.cjs` catches (a) as a
`ReferenceError`; only a deliberate sweep catches (b).

#### Verification

`_check.cjs` `[FACDIR]`: `#summary` and `renderSummary` banned; `btnExport` / "Export roster" /
"Download my details" banned; the old description string banned; `#heading` / `#subheading` / `#crumb`
**required**; `const ico = ` banned.

`_facultytest.cjs`: the obsolete `admin summary strip rendered` assertion (which asserted
`#summary` had > 400 chars) was **inverted** rather than deleted, and the matching faculty-view
assertion updated — now **124 assertions**.

**Mutation-tested.** Reinstating the `#summary` strip + `renderSummary()` + `renderSummary` call + the
`ico` helper + the export button + the old description produced **5 `[FACDIR]` failures and 5
functional failures**. Restored and re-verified.

Result: `_check.cjs` → `OK — all checks passed.` · `_smoke.cjs` → 29/29 pages clean ·
`_hubtest.cjs` → `PASS — 27 assertions` · `_facultytest.cjs` → `PASS — 124 assertions` · zero BOM.

#### Still open (unchanged)

- `targets.html` still shows a per-program breakdown and needs the rewrite flagged in §2.2C.
- The **Objectives** surface on `program-detail.html` remains unreviewed.

---

### 11.14 P0l — Manage Extension Programs: header removed, college cards redesigned (COMPLETE)

**Ask:** remove the redundant header "Manage Extension Programs / Select a college to see its extension
projects" so the section opens cleanly, then redesign the three college cards for a more polished,
modern UI — consistent sizing, better spacing/padding, soft shadow or border, rounded corners, a clear
visual hierarchy with a **logo/image area at the top using a placeholder for now**, followed by the
college name and supporting text, keeping the cards balanced and aligned.

#### What changed

`pages/colleges.html` went from **409 → 402 lines**; `assets/css/smartcemes.css` gained a card block.

| Change | Notes |
|---|---|
| Header `<section>` deleted from view 1 | It held only `<h2>Manage Extension Programs</h2>` + the sub-heading. The sidebar already names the entry, so it restated the nav. View 1 now opens on the `Colleges` eyebrow + `h3` and the cards. |
| "View all programs →" relocated | It was in the deleted header. It now sits in the section's top-right action cluster beside the "Click a college…" hint, so the route is not lost. |
| Cards rebuilt around a **logo/media area** | New `.college-media`: a **116px** brand-tinted band holding the crest + a `Logo` placeholder chip. Replaces the old 6px `.college-card-top` hairline. |
| Crest restyled | `.college-crest` is now a translucent disc (`rgba(255,255,255,.18)` + border + backdrop blur) sitting *on* the brand band instead of a solid brand square beside the text. |
| Body padding / hierarchy | `p-5` → `px-6 pt-5 pb-6`; name `15.5px` → **17px**; thrust `12px` → `12.5px`. Order is media → name → dean → thrust → metrics → badges → footer. |
| Card shell | Radius `18px` → **20px**; a resting two-layer shadow added (`0 1px 2px` + `0 8px 24px -18px`) lifting to a deeper one on hover. |

#### Balance and alignment — the actual mechanism

"Consistent sizing / balanced / aligned" is not achieved by the grid alone. Three things were needed,
and removing any one makes the three cards visibly desynchronise so their footer strips stop aligning:

1. `.college-media` is a **fixed 116px** — not content-driven.
2. The thrust paragraph reserves `min-h-[54px]` so a one-line thrust cannot shorten its card.
3. The badge row is **always rendered** with `min-h-[24px]` — even a college with fewer programs keeps
   the space, instead of the old conditional `progs.length ? … : ''` which removed the whole row.

Guarded: `_check.cjs` fails if `min-h-[54px]` or `min-h-[24px]` go missing.

#### Two leftovers deleted

- `const bp = c.budgetTarget ? SC.pct(c.utilized, c.budgetTarget) : 0;` in the card mapper was **dead** —
  the budget bar it fed was removed back in P0d. Deleted; `_check.cjs` fails if it returns. (Third
  instance of the delete-a-block-leave-an-orphan class of bug — see §11.11 and §11.13.)
- `.college-card-top` remains in the stylesheet but is now unreferenced — treat it as dead CSS.

#### Verification

`_check.cjs` `[HUB]` gained seven guards: `Manage Extension Programs</h2>` banned; the sub-heading
banned; `.college-card-top` banned; the brand-tinted `.college-media` required; `.college-logo-ph`
required; the dead `const bp` banned; and the two `min-h-*` balance classes required.

`_hubtest.cjs` gained **10 assertions → 37**: 3 media areas, 3 placeholder markers, the old strip gone,
all three brand colours present inline, 3 crests, the name follows the media area, both reserved
heights, and the header banner gone from the source.

**One harness-authoring note:** my first version of the "header banner removed" assertion read
`byId['viewColleges'].innerHTML` — but that element is a **stub** in `_hubtest.cjs` (only its
`classList` is ever exercised), so its `innerHTML` is always `''` and the assertion could never fail
meaningfully. Rewritten to assert against the page source. Recorded in `PATTERNS.md` §9 so the next
person does not repeat it.

**Mutation-tested.** Reinstating the header section, reverting the card to `.college-card-top`,
dropping the placeholder chip and both `min-h-*` classes, and reintroducing `const bp` produced
**7 `[HUB]` failures and 7 functional failures**. Restored and re-verified.

Result: `_check.cjs` → `OK — all checks passed.` · `_smoke.cjs` → 29/29 pages clean ·
`_hubtest.cjs` → `PASS — 37 assertions` · `_facultytest.cjs` → `PASS — 124 assertions` · zero BOM.

#### Still open (unchanged)

- `targets.html` still shows a per-program breakdown and needs the rewrite flagged in §2.2C.
- The **Objectives** surface on `program-detail.html` remains unreviewed.

---

---

### 11.15 P0m — Admin dashboard: two panels removed + full UI/UX pass (COMPLETE)

**Ask:** remove the Community Reach graph and the Action Center section, then redesign the overall
UI/UX with a cleaner layout, improved visual hierarchy, consistent spacing and typography, a modern
colour palette, and better component alignment — keeping the remaining sections well-organized,
responsive, and navigable.

#### What was removed

`pages/dashboard-admin.html` went from **426 → 387 lines**.

| Removed | Why |
|---|---|
| **Community Reach** panel — the `#chReach` canvas **and** its ~20-line chart config | Duplicated the beneficiary story already told by the project cards and Performance Leaders. It was also the only consumer of a hand-written `labels`/`data` array that was never wired to the seed. |
| **Action Center** — the whole section, all six tiles | Nine links pointing at four destinations (`proposals`, `availability`, `rendered-hours`, `ai-analysis`) that each surface the same queue themselves. The Pending Approvals KPI tile and the AI Decision Support section keep all four routes reachable, so nothing became unreachable. |

**Recent Activity was NOT part of the request and was preserved.** It had shared a row with Community
Reach, so deleting that row naively would have taken it too. It now occupies the third column of the
Trends grid. `_check.cjs` **fails if `Recent Activity` disappears** — worth stating as a rule: *a removal
request is scoped to what it names*, and the neighbouring panel is the easiest thing to lose.

#### The UI/UX pass

Rather than restyle each section ad hoc, the page was rebuilt on a small set of shared primitives added
to `smartcemes.css` — `.dash-section`, `.dash-sec-head` / `-eyebrow` / `-title`, `.dash-panel`,
`.dash-head` / `-title` / `-link`, `.dash-kpi*`, `.dash-chart`.

| Axis | Before | After |
|---|---|---|
| Section rhythm | ad-hoc `mt-5` / `mt-4` per section | one `.dash-section` step (30px; 26px below 1023px) |
| Section headers | inconsistent: some had an `h3` + note, some had none | uniform eyebrow → title → right-aligned note on every section |
| Card surface | `.sc-card p-5` (14px radius, one shadow) | `.dash-panel` (16px radius, two-layer shadow, 20px padding, hover deepening) |
| Card title rows | mixed `h3`/`p`, mixed breakpoints, legends inline | `.dash-head` — one title size, one gap, one baseline whether the right side is a link, a badge or a legend |
| KPI tiles | 4 different internal rhythms | `.dash-kpi*` with `.dash-kpi-foot { margin-top:auto }` |
| Page head | two stacked grey paragraphs, no title | eyebrow + `Extension Overview` h2 + date, formula note moved to a right-aligned footnote |
| Responsive | bare `grid-cols-4` / `grid-cols-3` — unusable below ~900px | every grid breakpoint-prefixed |

The KPI tile rhythm is the load-bearing detail: `.dash-kpi-foot { margin-top: auto }` pins the progress
bar / footnote to the bottom of the tile, so the four tiles' internal dividers align **even when one
caption wraps to two lines**. Without it the row visibly desynchronises at intermediate widths — the
same mechanism as the college-card balance fix (§7.1 / §11.14).

#### Orphan sweep (third time — now a written rule)

Deleting the Community Reach chart left two dead colour constants (`EMERALD`, `SLATE`) declared but
never used. Deleted, and `_check.cjs` now bans them. This is the **third** occurrence of the same class
of bug on this prototype: `totHours` (P0i, caught by `_smoke.cjs` as a `ReferenceError`), the `ico()`
helper (P0k, caught by nothing automatic), and `const bp` (P0l, caught by nothing automatic).

The rule, now recorded in `PATTERNS.md` §9: after deleting a block, grep for **(a)** every identifier it
*read* — `_smoke.cjs` catches these — and **(b)** every identifier that existed *solely to serve it*,
which **nothing automated catches**. Only a deliberate grep finds (b).

#### New functional harness

The admin dashboard had **no functional harness at all** — it is the highest-traffic admin page, and
neither `_check.cjs` (strings exist) nor `_smoke.cjs` (page loads) would have noticed a chart bound to
the wrong canvas. Added `_dashtest.cjs` (**39 assertions**), which asserts that exactly two charts are
constructed, each reads *project-level* targets, axis callbacks format correctly, the removed surfaces
and their wiring are gone, the layout primitives are applied, no bare grid remains, and both
leaderboards render 5 rows.

To avoid a second copy of the mini-DOM, the sandbox was **extracted into `_shim.cjs`** and both
functional harnesses now `require('./_shim.cjs')`. `_facultytest.cjs` still passes all 124 assertions
after the refactor.

**One assertion lesson worth keeping:** the first version tested `backgroundColor.some(c => c === '#ef4444')`
for the over-target red. That is a **fixture test, not a code test** — no seeded project is currently
over budget, so red is legitimately absent, and the assertion would start passing the moment someone
changes the data while saying nothing about the logic. Rewritten to assert the *mapping*:
`colors.every((c, i) => used[i] > planned[i] ? c === '#ef4444' : c !== '#ef4444')`.

**Mutation-tested.** Reinstating Community Reach (+canvas+JS), the Action Center, the dead colour
constants, a bare `grid-cols-3`, and renaming Recent Activity produced **7 `[DASH]` failures and 7
functional failures**. Restored and re-verified.

Result: `_check.cjs` → `OK — all checks passed.` · `_smoke.cjs` → 29/29 pages clean ·
`_hubtest.cjs` → `PASS — 37 assertions` · `_facultytest.cjs` → `PASS — 124 assertions` ·
`_dashtest.cjs` → `PASS — 39 assertions` · zero BOM.

#### Still open (as of P0m — both items now CLOSED, see §11.16 / §11.17)

- `targets.html` still shows a per-program breakdown and needs the rewrite flagged in §2.2C.
- The **Objectives** surface on `program-detail.html` remains unreviewed.

---

### 11.16 P0n — `targets.html` honest-fix (COMPLETE)

**Ask (option 1B):** apply the minimal honest fix rather than a full rebuild — add the banner §2.2C
promised, remove the fields that assert the rejected model, fix the over-promising prose, and align
the year label.

**Trigger:** while auditing the two loose ends recorded in the plan, the §2.2C description was found to
be **over-stated**. The page had already been converted to University tiles + a project table during
P0f — the per-program breakdown was gone. See §2.2C for the defect table.

#### What changed

| File | Change |
|---|---|
| `pages/targets.html` | Model-pending banner added (§2.2C finally delivers on its promise); guardrail note rewritten to state the pool/drawdown model; hours tile reworded from "remaining this year" to pool language; 4th tile no longer reads phantom fields; header + picker + section subtitle moved from FY to AY; chart gained a note that only hours are drawn down. |
| `assets/js/seed-data.js` | `universityTargets[]` lost `targetPrograms` / `targetProjects` / a redundant `targetActivities`; gained a `label` (`AY 2026–2027`, `AY 2025–2026`); the block comment corrected — it previously claimed "Project-level targets are carried on each project above", which was inverted. |

**261 → 280 lines** on `targets.html`.

#### A note on the `esc()` trap

The year picker was initially rewritten to `esc(t.label)`. `esc` is **page-local** in this prototype —
defined inline in `colleges.html`, `program-detail.html`, etc. — and is **not** a global or an `SC`
helper. Using it in `targets.html` would have thrown a `ReferenceError`. Caught before running the
harness. Recorded in `PATTERNS.md` §9 as a sweep item: **`esc` is not available unless the page defines
it.** Seed-owned literals need no escaping.

#### Verification

`_check.cjs` gained a **`[TARGETS]` block** (7 assertions) in both directions — the banner is
*required*, the phantom fields and the old prose are *banned*, and the AY labels are *required* in the
seed. Mutation-tested: reverting the banner, reinstating `targetPrograms`/`targetProjects`, restoring
the "remaining this year" prose each produced the expected `[TARGETS]` failure.

---

### 11.17 P0o — dead Objective authoring surface removed (COMPLETE)

**Ask (option 2A-ii):** remove the unreachable Objective surface from `program-detail.html`, **keeping**
the seed because two other pages read it.

**Trigger:** the plan recorded the Objectives surface as "unreviewed". Auditing it showed something
sharper — it was **unreachable dead code**. `window.openObjManager` was defined but called from
**nowhere** in `pages/` or `assets/`, and it was the only entry point to both objective modals.

#### What changed

`pages/program-detail.html` — removed:

| Removed | Detail |
|---|---|
| `#mObjList` modal | the Objective Manager |
| `#mObjForm` modal | the add/edit objective form |
| `openObjManager`, `renderObjManager`, `editObjective`, `deleteObjective`, `openObjForm`, `saveObjective`, `objKpiChanged`, `computedForKpi` | the whole manager/form JS block |
| `#ofActualWrap`, `#ofComputedWrap` | orphaned KPI-binding wrappers (empty, hidden) |
| `editObjIdx` | dropped from `let editActId = null, editObjIdx = null;` |

Replaced by a comment recording *why* the surface went and *why the seed stayed*.

#### The correction worth keeping

The first attempt at 2A deleted the `programObjectives` seed too — as option 2A literally specified.
That was **wrong**, and the harness would not have caught it:

| Consumer | What breaks if the seed goes |
|---|---|
| `calendar.html` (Feed 3) | Renders **zero** objective-deadline events — silent data loss, no error |
| `program-narratives.html` (`objectivesMet` → `omChip`) | Throws `TypeError: Cannot read properties of undefined` on every narrative card |

So the honest position is: **the *editing surface* was dead, the *data* is live.** The seed was restored
from backup before proceeding, and 2A-ii — remove the authoring surface only — was confirmed by the owner.

**The lesson, now enforced:** when a removal names a *symbol*, trace that symbol across **all** pages
before deleting it, not just the page being edited. The plan's §9 rule already says to grep for every
identifier a deleted block *read*; this adds its mirror — grep for every page that reads the identifier
the block *owned*. Both the ban and the requirement are now in `_check.cjs`.

#### Verification

`_check.cjs` gained an **`[OBJ]` block** that bans the removed surface (6 ids, 8 functions, 2 modal ids,
2 labels) **and requires the seed to survive** — including the two consumer call-sites, so re-deleting
the seed trips a named failure rather than a silent empty calendar. Mutation-tested: reinstating the
modal, reinstating `openObjManager`/`renderObjManager`, and dropping the seed export each produced the
expected `[OBJ]` failures.

Result: `_check.cjs` → `OK — all checks passed.` · `_smoke.cjs` → 29/29 pages clean ·
`_hubtest.cjs` → `PASS — 37 assertions` · `_facultytest.cjs` → `PASS — 124 assertions` ·
`_dashtest.cjs` → `PASS — 39 assertions` · zero BOM.

#### Still open (this list is now EMPTY)

Both items that stood open across P0h → P0m are closed:

- ~~`targets.html` still shows a per-program breakdown and needs the rewrite flagged in §2.2C.~~
  → **closed by P0n** (§11.16). The page no longer asserts the rejected model; it carries the banner.
- ~~The **Objectives** surface on `program-detail.html` remains unreviewed.~~
  → **closed by P0o** (§11.17). Reviewed and found dead; authoring surface removed.

The remaining known work on this page-set is the **R5** rework of `targets.html` (4 parallel tiles →
one training-hours pool), which is implementation-scoped, not a prototype defect.

---

## 12. PHASE R1 — COLLEGES + BROAD PROGRAM LEVEL (COMPLETE)

**Goal (§5):** introduce the two new levels *without moving any data*.

#### 12.1 What shipped

| Area | Change |
|---|---|
| **Migrations** | `2026_09_24_000100_create_colleges_table.php` (§4.1) and `2026_09_24_000200_create_programs_table.php` (the broad level, §4.2). Purely additive — the legacy `extension_programs` table is untouched. |
| **Models** | `App\Models\College` (coordinator relation, `active`/`ordered` scopes, soft deletes, activity log) and `App\Models\Program` (the broad level, `nextCode()` via `SequenceService`). |
| **Policies** | `CollegePolicy` and `BroadProgramPolicy` — read for all authenticated roles, manage for admin only. Registered in `AppServiceProvider`. |
| **Factories** | `CollegeFactory`, `ProgramFactory`. |
| **Seeders** | `CollegeSeeder` (3 official colleges from §1.1, coordinators resolved from the seeded faculty roster) and `ProgramSeeder` (6 verbatim CESO thrusts from §3, deterministic `PROG-{year}-{seq}` codes). Both idempotent; wired into `DatabaseSeeder` after `UserSeeder`. |
| **Livewire CRUD** | `App\Livewire\Colleges\Index` + `livewire.colleges.index`; `App\Livewire\Programs\BroadPrograms` + `livewire.programs.broad`. Admin-only, with `mount()` authorization as defence in depth. |
| **Config** | `smartcemes.pillars`, `smartcemes.pillar_colors`, college/program statuses; admin nav gains **Colleges** and **Extension Projects**. |
| **Tests** | `CollegeProgramFoundationTest` (**25 tests**) and `CollegeProgramCrudTest` (**19 tests**). |

#### 12.2 The naming decision (and why R2 is untouched)

The phase plan says the new broad table is `extension_programs` (§4.2) — but that name is **owned by the
legacy table until R2 renames it**. Two tables cannot share a name, and doing R2's rename inside R1 would
mean renaming 7 FK columns, a pivot table, ~126 class references across 42 files and 13 test files — i.e.
importing the single highest-risk phase into the foundation phase, against the plan's own rule
(*"Do not start a phase before the previous one is committed and green"*).

**Resolution (owner decision, option 1):** the new level takes the physical table **`programs`** and the
class **`App\Models\Program`**. The legacy side keeps `extension_programs` / `ExtensionProgram` and all
126 references intact. R2 then renames the legacy side to `ExtensionProject` / `extension_projects`,
vacating the canonical name — at which point `programs` can be renamed to `extension_programs` if wanted,
or left as `programs` (which reads better). **This is a naming deferral, not a model change**; the end
state matches §4.2 either way.

Consequences carried forward:
- `Program`'s `projects()` and `Program`'s `college()` / `College::programs()` relations are **documented
  stubs** — a relation cannot be declared against a class or column that does not exist yet. Each is
  written out in a comment as the exact code R2 should add.
- The broad policy is `BroadProgramPolicy`, not `ProgramPolicy` — the latter already governs the legacy
  entity.
- The broad component is `App\Livewire\Programs\BroadPrograms`, not `Programs\Index` — the latter is the
  legacy list.

#### 12.3 The route move (`/programs` → the broad level)

`/programs` now renders the **broad Program level**; the legacy list moved to **`/projects`** (route name
`projects.index`) with `/extension-programs` kept as a redirect. The hub stays at `programs.show`
(`/projects/{program}`) and the route *family* is renamed in R2.

Blast radius was small and fully traceable: `programs.index` had only **two** references (both in
`livewire/analytics/partials/tab-pending.blade.php`) plus **two** in tests. Three pre-existing tests broke
and were fixed by switching to named routes (`route('projects.index')`, `route('programs.show', …)`) so
R2's rename cannot break them again.

#### 12.4 Verification

Full suite green: **276 tests / 1308 assertions** (232 pre-existing + 44 new), all passing. The exit
criteria from §5 hold:

| Exit criterion | Result |
|---|---|
| 3 colleges and 6 programs exist | ✅ asserted by both seeders' tests |
| New screens work | ✅ `/colleges`, `/programs`, `/projects` all render; CRUD round-trips |
| **No existing screen changes behaviour** | ✅ the 232 pre-existing tests still pass (3 needed URL updates for the deliberate route move) |
| All pre-existing tests still pass | ✅ |

**Mutation-tested:** removing the `mount()` authorization guard made the defence-in-depth test fail as
designed; restored and re-verified.

#### 12.5 Notes for R2

1. Rename `extension_programs` → `extension_projects`, `ExtensionProgram` → `ExtensionProject`, and the 7
   FK columns (`extension_program_id` → `extension_project_id`) plus the pivot
   `extension_program_beneficiary` → `extension_project_beneficiary`.
2. Add `college_id` + `program_id` to the renamed projects table, plus
   `annual_target_hours` / `annual_target_budget` (§4.3).
3. Backfill the 6 rows per §3.1 and attach college codes.
4. Then either rename `programs` → `extension_programs`, or keep `programs` and reconcile the class name.
5. Add the three stubbed relations (documented in `College` and `Program`).
6. Rename the route family `programs.show` / `programs.my` → `projects.show` / `projects.my`, and consider
   renaming `projects.index` back or leaving it; `programs.index` is now taken by the broad level.
7. `programs.index` (broad) and `projects.index` (legacy list) are **distinct route names** — keep them so.

## 13. PHASE R2 — THE BIG RENAME (COMPLETE)

**Landed:** 2026-09-24. **Result: 286 tests / 1412 assertions passing** (was 276 / 1308 after R1).

R2 was the plan's designated **highest-risk** step. It inverted the hierarchy so the words finally
mean what §3 says they mean:

```
before:  extension_programs  ->  activities
after:   colleges -> programs -> extension_projects -> activities
```

### 13.1 The name decision (step 4 of the plan §5 R2)

The plan offered *"rename `programs` → `extension_programs`"*. **Rejected.** Reasons recorded in the
R2a migration docblock:

1. R2 was already the riskiest step; a second rename would have doubled the churn for zero function.
2. It would have **re-adopted the exact name R1 deliberately vacated**, inverting what "program"
   means mid-phase — the ambiguity R1 paid to escape.
3. The blueprint divergence is already R7's scope (§8 lists `SYSTEM_BLUEPRINT_V4.txt` as stale).

**Final naming:**

| Level | Table | Class | Route family |
|---|---|---|---|
| College | `colleges` | `College` | `colleges.index` |
| Program (BROAD, CESO thrust) | `programs` | `Program` | `programs.index` |
| Project (NARROW, carries activities) | `extension_projects` | `ExtensionProject` | `projects.index` / `projects.show` / `projects.my` |
| Activity | `activities` | `Activity` | — |

### 13.2 What shipped

**Migrations (2, both reversible):**

- `2026_09_25_000100_rename_extension_programs_to_extension_projects.php` — the table, both pivots
  (`extension_program_beneficiary`, `community_extension_program`), and the `extension_program_id`
  → `extension_project_id` column on **all seven** dependent tables.
- `2026_09_25_000200_add_hierarchy_links_to_extension_projects.php` — adds `college_id`,
  `program_id` (both nullable → backfilled → tightened where the driver allows), plus
  `annual_target_hours` / `annual_target_budget` (§4.3). Contains the **backfill** of the six
  inherited rows.

**Model:** `ExtensionProgram` → **`ExtensionProject`** (file renamed, class renamed, `$table` set
explicitly, all 8 relations repointed, `nextCode()` reworked for R-Q4).

**Relations finally wired** — the three R1 stubs are now real:

- `Program::projects()` → `hasMany(ExtensionProject::class, 'program_id')`
- `College::projects()` → `hasMany(ExtensionProject::class, 'college_id')`
- `ExtensionProject::college()` / `::program()` → the two parents

> **Design note.** `College::programs()` was **NOT** created, deliberately. The `programs` table has
> no `college_id`, because the six broad programs are university-wide CESO thrusts (§3) — a thrust is
> not owned by a college. A program spans colleges; its college membership is derived from its
> projects. `test_a_broad_program_spans_colleges_and_carries_no_college_fk` pins this so a future
> migration cannot quietly add the column.

### 13.3 R-Q4 — college-prefixed codes, and the risk item from §9.2

New projects adopt `CAS-2026-001` / `COE-2026-001` / `CME-2026-001`. The sequence key became
**per-college**: `project_{COLLEGE}_{year}`. **Migrated rows keep `EXT-2026-00{n}` untouched**, so
the two schemes coexist and cannot collide (the `nextCode()` prefix is never `EXT-` again).

Duplicate protection is covered by new tests, including a dedicated
`test_next_code_never_collides_across_colleges` and
`test_next_code_is_case_insensitive_on_the_college_code`.

### 13.4 Backfill mapping (derived, not hand-typed)

Derived from §3.1 + each row's `program_lead_id`, mapped by **specialization** against §1.1's
known-programme lists — *not* the literal `faculty.department` string, because `faculty4` (Kent
Naputo, Entrepreneurship) is seeded with the legacy department `"College of Business Administration"`,
which does not equal CME's official name. That string is a **Phase R3** fix.

| Code | Project | College | Broad program |
|---|---|---|---|
| EXT-2026-001 | LITRAWIYA | COE | Literacy, Numeracy & Language |
| EXT-2026-002 | HANDA | CAS | Environmental Conservation & Disaster Preparedness |
| EXT-2026-003 | KABUHIAN | CME | Livelihood, Technical & Business Management |
| EXT-2026-004 | e-LITERACY | CAS | Information, Communication & Education |
| EXT-2026-005 | SENIOR CARE | COE | Information, Communication & Education |
| EXT-2026-006 | BATANG MATINIK | CME | Physical Fitness & Sports Development |

The same mapping is held in `Phase2Seeder` (as a `hierarchy` key per row) so `migrate:fresh --seed`
produces correct links too — the migration repairs existing databases, the seeder creates them right.
`BUSOG` (FeedingProgramSeeder) is new-after-R2, so it gets `CAS-2026-001` and is linked to
Information, Communication & Education.

### 13.5 Route moves (R2 step 6)

| Route name | Path | Was |
|---|---|---|
| `projects.show` | `/projects/{project}` | `programs.show` |
| `projects.my` | `/my-projects` | `programs.my` (path `/my-programs`) |
| `programs.index` | `/programs` | unchanged — now genuinely the broad level |

`/extension-programs` still redirects to `/projects`. Nav key/label `my-programs`/"My Programs" →
`my-projects`/"My Projects".

### 13.6 Verification

- **`ExtensionProjectRenameTest`** (6 tests / 67 assertions) — the durable guard. Runs the migration
  against a **populated** database, proves all six rows and their children survive, proves `down()`
  restores every name *and* the row count, walks the hierarchy both directions, pins the
  no-`college_id`-on-programs design, and proves legacy + college-prefixed codes coexist.
- **Orphan sweep clean** — zero `ExtensionProgram`, `extension_program_id`,
  `extension_program_beneficiary`, or `community_extension_program` references remain outside the
  historical migrations and the two R2 files. The only hits inside the test suite are *negative*
  assertions (proving the old names are gone / are restored by `down()`).
- **Exit criterion met:** hierarchy renders College → Program → Project → Activity.

### 13.7 What broke, and the lessons

1. **Livewire route-model binding matches the mount parameter BY NAME.** I first kept
   `mount(ExtensionProject $program)` against the new `{project}` segment and reasoned that binding
   resolves by type. **Wrong** — HTTP 500s while `Livewire::test(Hub::class, ['program' => …])`
   still passed, i.e. the unit-level path hid the bug. Verified with a throwaway probe. Fix: the
   parameter became `$project`, and the **property stays `$program`** (read ~40 times), assigned once
   across the boundary. ~40 test call-sites updated from `['program' =>` to `['project' =>`.
2. **A global rename script will rewrite its own guards.** My rename pass corrupted
   `ExtensionProjectRenameTest`'s own assertions (`assertFalse(hasTable('extension_programs'))`
   became a self-contradictory `assertTrue(hasTable('extension_projects'))`) and the migration
   filename inside a `base_path()` call. Both caught by running the test. **Lesson: any rename
   script must skip the files that assert on the old names.**
3. **Historical migrations must never be rewritten.** The script initially included
   `database/migrations/2026_09_13_*` — changing those would make the schema build
   `extension_projects` directly, so the R2a rename would find nothing to rename and fail on a fresh
   `migrate`. Fixed with a date-prefix guard (`2026_09_13_` … `2026_09_16_`). **History is
   immutable; only forward migrations move.**
4. **Route-parameter keys need a scoped regex.** A naive `['program' =>` → `['project' =>` replace
   hit 23 seeder array literals in `Phase2Seeder` (row keys, nothing to do with routing). Fixed by
   requiring a `route('projects.show', [` prefix.
5. **Seven test failures were the change working correctly.** Five `nextCode` tests encoded the old
   `EXT-` contract; two R1 tests asserted the pre-R2 name. Rewritten to the new contract, with three
   tests added for the per-college scheme.

### 13.8 Notes for R3

1. `faculty.college_id` backfill can map from `department` — but `faculty4`'s seeded value is
   `"College of Business Administration"`, which does **not** match CME's official name. Fix the
   string or map by specialization (as R2 did).
2. `programs` deliberately has **no** `college_id`. If a UI need for "programs of a college" appears,
   derive it via `$college->projects->pluck('program')->unique()` — do not add the column.
3. `ExtensionProject` declares `$table` explicitly (the only model that does). Keep it — the class
   name and the table name no longer follow Laravel's convention in the same way as the others.
4. The two `annual_target_*` columns on projects are **populated by the CRUD form** and are distinct
   from the university-wide pool on `university_targets` (§4.7, R4). Do not conflate them.
5. `ProgramObjective` / `KpiService` remain wired and **read** — R4 soft-deprecates them (R-Q2).
   Nothing in R2 removed or unread them.

---

## 14. PHASE R3 — FACULTY COLLEGE + FACULTY MANAGEMENT MODULE (R3a + R3b COMPLETE)

**Status: R3a ✅ · R3b ✅ · R3c ✅ complete (§18).**
Landed 2026-09-24. **286 → 339 tests / 1412 → 1512 assertions passing.** R3c later took the suite to
**437 / 1940** (and R7 to 444 / 1960 — see §18).

### 14.1 The R3c reordering decision (owner, 2026-09-24)

§6's Faculty Performance table has **8 blocks**, and two of them — *Training contribution* and
*Performance trend* — depend on `TrainingHoursService`, which is **R4's deliverables** (it needs
`activities.no_of_days` / `participants`, added in R4).

Owner decision: **reorder R4 ahead of R3c** rather than stub the two blocks. Rationale: R4 is
additive and lower-risk, it unblocks both R3 blocks *and* R5's rankings, and it avoids putting
placeholder numbers on a page the Director reads. This deviates from §5's stated phase order —
deliberately, and recorded here so the deviation is not mistaken for drift.

**R3b's board is also partly an R4 surface.** It is driven by *training hours rendered* and ranks by
*hours delivered*. Those resolve today from `rendered_hours` (service credit), so the board is fully
functional now; the *delivered* training-hours figures light up automatically once R4 lands, because
`FacultyContributionService::trainingIsMeasurable()` flips on `activities.no_of_days`.

### 14.2 R3a — the data foundation

**Migrations (both reversible):**
1. `2026_09_25_000300_add_college_id_to_faculties_table.php` — adds `faculties.college_id`
   (nullable FK → `colleges`, `nullOnDelete`) **and** `faculties.status`
   (`active | on_leave | inactive`, default `active`). The status column is required by §5 R3 step 3
   ("filters (college, expertise, active/inactive)") and by the prototype's roster. Then backfills
   `college_id` from `department`.
2. `2026_09_25_000400_create_faculty_expertise_table.php` — `id, faculty_id, area, category,
   timestamps` per §4.5, with a **unique (faculty_id, area)** index.

**Why `faculty_expertise` is a table, not a JSON column:** the module filters and counts *by area*
("everyone who does Reading Education"). A JSON blob cannot be indexed or aggregated cleanly.

**The department → college mapping is deterministic, and the legacy label is handled explicitly:**
| `faculty.department` | College |
|---|---|
| College of Arts and Sciences | CAS |
| College of Education | COE |
| College of Management and Entrepreneurship | CME |
| **College of Business Administration** *(legacy label)* | **CME** |
| anything unrecognised | **NULL** |

The migration exposes `backfill()` as a **public, idempotent** method, because `migrate:fresh --seed`
runs migrations against an *empty* database — the roster does not exist yet, so the migration has
nothing to link. `FacultyCollegeSeeder` re-runs it once `UserSeeder` has created the rows. This also
required adding `FacultyCollegeSeeder` to `DatabaseSeeder` **between** `CollegeSeeder` and
`ProgramSeeder` (ordering is load-bearing).

`UserSeeder` was also corrected: `faculty4`'s department string is now
`"College of Management and Entrepreneurship"` rather than the legacy
`"College of Business Administration"`. The migration still maps the legacy value, so pre-existing
databases heal too.

**Backfill refuses to overwrite** a link an admin has already set, and leaves unrecognised
departments NULL — a NULL college is honest; a guessed one is not.

**Config additions** (`config/smartcemes.php`): `faculty_statuses`, `expertise_options` (the
canonical 24-area list, **ported verbatim** from the prototype's `seed-data.js`), `expertise_categories`,
and `position_ladder` (18 rungs, institutional order, most junior first). Porting verbatim means the
Laravel module and the prototype cannot drift.

### 14.3 R3b — the two pages

**The phantom nav entry is now real.** `config/smartcemes.php` pointed `faculty-management` at route
`faculty.index`, which **did not exist** — the nav hides items whose route is missing, so the item
was silently invisible. Registering the route makes it appear. A test pins this, because a
regression would hide the whole module with no visible error.

| Route | Component | Mirrors |
|---|---|---|
| `faculty.index` → `/faculty` | `App\Livewire\Faculty\EngagementBoard` | `faculty-management.html` |
| `faculty.directory` → `/faculty/directory` | `App\Livewire\Faculty\Directory` | `faculty-directory.html` |
| `faculty.show` → `/faculty/{faculty}` | `App\Livewire\Faculty\Profile` | profile drawer, promoted to a page |

**Declaration order matters:** `/faculty/directory` is declared before `/faculty/{faculty}`, or the
wildcard would capture it and 404 on model binding. Pinned by a test.

**The board is board-only, per the prototype.** No page heading, no KPI cards, no roster table —
P0i/P0j/P0k deliberately stripped those out and the roster lives on the Directory page, reached from
the board header. The single metric switch (Hours rendered / Projects / Leads) drives the chart, the
leaderboard **and** the insights from one input, so they can never disagree. `?college=CAS` narrows
all three together.

**`FacultyContributionService`** is the shared source of truth for every number on both pages
(project involvement via the `activity_faculty` pivot ∪ projects led, activities handled, rendered
hours by status, proposal counts and approval rate). Computed once in PHP from grouped queries, not
N+1 per row.

**`FacultyPolicy`** enforces the access model: Admin manages everything; a faculty member may view
and edit **their own** profile only; **Secretary has no access at all** (separation of duties
— they validate assessments and manage beneficiaries, not the roster). The self-edit writable set is
restricted **server-side** — employee ID, college, position and status are institutional records with
no writable path from the self-service form.

> **CORRECTION (2026-09-27 — see §22).** This paragraph originally listed **expertise** among the
> institutional records, which contradicted §11.10 of this same document ("Faculty may correct their
> own contact details and **expertise** (they own that fact)"), blueprint §2.3 and §5.1, and the
> prototype's own self-edit drawer — which renders contact + expertise as editable and only four
> fields as locked. The Laravel implementation followed **this** paragraph, so the app shipped a
> contact-only self-edit while the contract, the prototype and the user guides all promised
> expertise. §22 records the fix.

**CSS:** the `eng-*` classes were ported verbatim from
`docs/prototype/assets/css/smartcemes.css` into `resources/css/app.css`; `npm run build` re-run.

### 14.4 What the UI says instead of inventing numbers

Two of §6's blocks are **explicitly marked "not yet measurable"** rather than rendering a `0`:

- `FacultyContributionService` returns **NULL** (not 0) for `training_hours_delivered`,
  `training_sessions` and `trainees_reached` until R4.
- The profile page renders a dashed panel explaining that training hours *delivered* arrive with R4,
  and states the distinction: **rendered hours** are service credit a faculty member *claims*;
  **training hours** are hours they *delivered to others*. §9 flagged these two "hours" concepts as a
  confusion risk; this is the mitigation.
- `trainingIsMeasurable()` flips to `true` automatically when `activities.no_of_days` exists and
  `TrainingHoursService` is present, so R4 needs to change **nothing** on these pages.

### 14.5 Verification

- **339 tests / 1512 assertions passing** (was 286 / 1412 after R2). No regressions.
- `npm run build` clean; the new `eng-*` CSS is in the bundle.
- Full `migrate:fresh --seed` verified end to end: 3 colleges, 4 faculty — **all four linked to the
  correct college**, 11 expertise rows, `faculty3` seeded On Leave so the board's muted-outline
  treatment is demonstrable.
- All three pages rendered against a fully seeded database and checked for their key elements.

### 14.6 What broke, and the lessons

1. **A public Livewire property shadows a same-named view variable.** The board view used
   `$metric['key']`, but `$metric` is also a **public property** (a string) on the component, and
   Livewire injects public properties into the view scope. Every lookup fataled with
   *"Cannot access offset of type string on string"*. Fixed by naming the view array
   **`activeMetric`**. **Lesson: never give a `view()` payload key the same name as a public
   Livewire property.**
2. **`mount()` authorization runs before a Livewire snapshot exists.** A test doing
   `Livewire::test(...)->call(...)` for a forbidden action failed with *"Invalid Livewire snapshot
   structure"* — the mount had already 403'd. **Lesson: assert pre-mount authorization over HTTP
   (`$this->actingAs(...)->get(...)->assertForbidden()`), not through `Livewire::test`.**
3. **`Livewire::test` needs the *model*, not an already-computed row array.** The Directory blade
   called `forFaculty(collect([$row]))` where `$row` was a metric array — a `TypeError`. Fixed by
   reading the value directly and adding a public `formatHoursValue()` helper.
4. **No `FacultyFactory` existed.** Earlier phases built faculty rows through the seeders; R3 needed
   real factories. Added `FacultyFactory` plus `faculty()` / `admin()` states on `UserFactory`.

### 14.7 Notes for R4 (and how R3c should start)

1. **R4 must add `activities.no_of_days`.** That single column is the trigger that flips
   `trainingIsMeasurable()` on and lights up the two dormant R3 blocks. Nothing else in R3 needs
   touching.
2. **R4 must build `TrainingHoursService`** to the amended formula `trainors × trainees × days` —
   **no `× 8`** (D-R3/D-R4) — with the trainee resolution order `attendance → participants → 0` and a
   source tag exposed to the UI (R-Q1).
3. **R3c is then mostly assembly:** `FacultyContributionService` already returns the right shape;
   the profile page already has placeholders in the right two blocks.
4. The `?college=` deep-link contract is live on the board. Colleges' cards should link to
   `faculty.index?college=CAS` — the board reads and validates it.

---

## 15. PHASE R4 — TRAINING HOURS MODEL + PROJECT PERFORMANCE REVAMP (COMPLETE)

**Landed 2026-09-22.** Two layers: **R4a** (the computation + data model) and **R4b** (the surfaces
that read it). Exit criteria from §5, restated: *project performance shows target vs actual training
hours and budget; no 8.6 KPI tiles remain on those surfaces; trainee source is visible per activity.*

### 15.1 What shipped

| # | Deliverable | Where |
|---|---|---|
| 1 | `activities.no_of_days` decimal(4,1) NULL, `participants` int NULL, `trainors_snapshot` int NULL | migration `2026_09_26_000100` |
| 2 | `university_targets` table (year, hours, budget, notes) | migration `2026_09_26_000200` |
| 3 | `TrainingHoursService` — the single source of every training figure | `app/Services/` |
| 4 | `UniversityTarget` model + `forYear()` / `current()` / `display_label` accessor | `app/Models/` |
| 5 | Target-aware `budgetTarget()` / `hoursTarget()` / `isOverAllocated()` | `app/Models/ExtensionProject.php` |
| 6 | University Targets page (Director-editable, AY-switchable) | `app/Livewire/Targets.php` + `resources/views/livewire/targets.blade.php` |
| 7 | Route + admin nav entry | `routes/web.php`, `config/smartcemes.php` |
| 8 | Idempotent R4 demo seed (2500 hrs / ₱668,000; BUSOG 150 hrs / ₱85,000) | `database/seeders/R4TargetsSeeder.php` |
| 9 | 39 new tests + 9 inverted pre-R4 tests | `tests/Feature/TrainingHoursTest.php` et al. |

### 15.2 The formula, and the `× 8` audit

`trainors × trainees × days` — **no `× 8`** (D-R3/D-R4, §2.2A). Rendered from a real
`migrate:fresh --seed`:

| Activity | trainors | trainees | days | hours |
|---|---|---|---|---|
| Nutrition Baseline Assessment | 2 | 30 | 0.5 | **30** |
| Feeding Cycle 1 | 3 | 30 | 1.0 | **90** |
| Feeding Cycle 2 | 1 | 29 | 0.5 | **14.5** |

Under the retired rule these would have been 240 / 720 / 116 — the seed would have claimed
**~896 hrs** for the first activity (`2 × 112 × 0.5 × 8`), where the prototype now reads **112**.
BUSOG lands at **134.5 actual against a 150-hour project target = 89.7% attainment**.

**Audit result:** a regex sweep of all 240 PHP/Blade files in `app`, `resources`, `database`,
`config`, `routes` found **no live `× 8` factor**. The 15 lexical matches are all either blueprint
section numbers (`8.5`, `8.8`, `8.9`) or lines that explicitly *deny* the rule ("there is no `× 8`",
"removed the trailing `x 8`", "a regression, not a decision"). A test asserts this permanently:
`test_the_formula_is_not_multiplied_by_eight` checks that `compute()` differs from the `× 8` product
for all three prototype examples, so reintroducing the factor fails loudly.

### 15.3 The target model is consumption, not ratio (§2.2B / D-R5)

ONE annual university pool, drawn down by project **actuals**:

```
remaining = max(university_target_hours − Σ(project.actual_training_hours), 0.0)
drawn_down = min(Σ actuals, pool)          // capped — drawdown cannot exceed the pool
```

Verified live: pool **2500 hrs**, drawn **145.5**, remaining **2354.5**, `hours_pct` **5.8**.
Project targets are **never summed** to produce the pool — the double-counting bug D-R5 forbids.
The test asserts `target_hours === 100.0` and explicitly `assertNotSame(1998.0, …)` where two
projects carry 999-hour targets that must not be added.

**Broad programs carry no target.** `College` and `Program` have no target column at all; targets
exist only at University and Project level. The hub prints the guardrail prose rather than a zero.

`hoursTarget()` returns **NULL**, not `0`, when there is no target — a zero denominator makes
attainment meaningless, so the UI renders "No annual target set" and the tests assert
`assertNull`.

### 15.4 Two bugs found and fixed while verifying

1. **`remaining` could leak an integer.** `max($target - $hours, 0)` with an int literal returns an
   int, so an over-drawn pool produced `0` instead of `0.0` — which would surface as a bare integer
   in JSON and in the `number_format` path. **Fixed** to `max(…, 0.0)`. Confirmed `double` on a live
   rollup.
2. **`explain()` pluralised a half day.** `$days == 1 ? '' : 's'` produced "0.5 **days**", which is
   wrong English and contradicted the prototype's own wording. **Fixed** to `$days > 1 ? 's' : ''`,
   so `0.5 day` but `2.5 days`. Both forms are asserted.

A third pre-existing live bug was also repaired: `analytics/partials/tab-budget.blade.php` read
`$r->budgetUtilization`, which the R2 rename had removed — the tab would have fataled on any
budget view. It now reads `TrainingHoursService::forProject()`.

### 15.5 D-R7 — every 8.6 KPI surface removed

Removed from all six surfaces that carried them:

- **Project hub overview** — the whole "Results Framework" card → replaced by an "Annual Targets"
  card (training hours bar, budget bar, trainors/trainees sub-tiles, the D-R5 note).
- **Project hub modals** — the Objective Manager modal and the objective form **deleted**; the
  narrative modal's 8.6 grid and the `$objMet`/`$objTotal`/`$kpi->budgetUtilization()` tiles
  replaced by Trainors / Training-hours read from `$performance`.
- **Analytics** — `KpiService` injection dropped; the `objectivesAtRisk` pending action removed.
- **Dashboard** — Action Center "Objectives at risk" → "Projects over annual budget"; the
  `objectiveAtRisk` KPI tile removed; `renderAdmin()` no longer resolves `KpiService`.
- **Annual report** — section IV header dropped "(8.6)"; columns are now
  `Allocated → Annual target`, `Utilization % → Attainment`.
- **`tab-budget` / `tab-pending`** — both rebuilt on the service.

### 15.6 R-Q2 — the soft-deprecation list (retained, deliberately)

`KpiService`, `ProgramObjective`, the `programObjectives` relation and the `smartcemes.kpi_metrics`
config **all remain in the codebase**. Only *readers* were removed. The hub's objective CRUD methods
are still callable and are annotated in place as **soft-deprecated, retained unread — do not wire new
UI to these**. A test (`test_objective_crud_methods_remain_callable_for_compatibility`) locks that in,
so a future cleanup cannot delete them without a decision.

The reason is auditability: deleting the 8.6 machinery would make it impossible to show an adviser
what was replaced and why. R7 owns the final call on whether it is dropped.

### 15.7 §14.7 discharged — `trainingIsMeasurable()` is now true

§14.7 named `activities.no_of_days` as *"the trigger that flips `trainingIsMeasurable()` on and lights
up the two dormant R3 blocks."* That column now exists, so:

- `TrainingHoursService::isMeasurable()` → **`true`** (asserted).
- `FacultyContributionService::trainingIsMeasurable()` → **`true`** (asserted, test inverted from
  `test_the_service_reports_training_as_unmeasurable_before_r4`).
- The faculty profile's "Not yet measurable" copy is gone and replaced by the real formula
  (asserted with `assertDontSee('Not yet measurable')`).

**R3c was unblocked from this point** and, as §14.7 predicted, was "mostly assembly" — it has since been
completed. See §18.

### 15.8 What R5 must pick up

1. **`targets.html` still needs its native rework.** The prototype page still shows **four parallel
   tiles** whereas the real model is **one training-hours pool**. R5 must collapse them and, per the
   standing instruction, state plainly that no number is invented — the prototype's
   `targetActivities` has no backing column, so the real page renders a **"no target"** badge rather
   than a fabricated denominator. (The §2.2C "model pending" banner is already deleted by R4.)
2. **Legacy fallback.** `budgetTarget()` falls back to `allocated_budget` where
   `annual_target_budget` is NULL, so v4.14 rows still render. R5 should decide when to migrate them
   forward rather than carry the fallback indefinitely.

### 15.9 Verification (all green)

| Check | Result |
|---|---|
| PHPUnit full suite | **378 tests, 1632 assertions — OK** |
| `Tests:` delta | 339/1512 (post-R3b) → **378/1632** (+39 tests) |
| `pint` on authored files | clean (only pre-existing CRLF `line_ending` noise) |
| `npm run build` | 62 modules, 8.30s, 260.55 kB JS / 89.99 kB CSS |
| Prototype harnesses | `_check` OK · `_smoke` OK · `_hubtest` **37** · `_facultytest` **124** · `_dashtest` **39** |
| `migrate:fresh --seed` | clean end-to-end (verified on SQLite; local MariaDB not running) |
| `× 8` audit | **clean** — 240 files scanned, no live factor |

### 15.10 Livewire v3 gotcha reinforced (add to the standing list)

**A Blade view cannot call a public Livewire component method bare.** `targets.blade.php` called
`$yearLabel((int) $y)` and threw `Undefined variable $yearLabel` on every render — a 500 on the
whole page, and the cause of three test errors. Resolved by exposing `getYearLabelsProperty()` and
`getSelectedLabelProperty()` as computed properties and passing them as **view data**, so the Blade
reads `$yearLabels[(int) $y]` and `$selectedLabel`. This is the sixth entry in the cumulative
Livewire list (§13.6 / §14.6).

---

## 16. PHASE R5 — FILTERS, RANKINGS, DASHBOARDS, REPORTS (COMPLETE)

**Landed 2026-09-22.** Four steps per §5: (1) most-active rankings, sortable and filterable;
(2) college / program / pillar / academic-year / status filters; (3) the Admin dashboard rebuilt
around the new metrics with the old KPI cards demoted; (4) the four print reports moved to the new
metric dictionary.

**Exit criteria from §5, restated:** *every ranking and filter is driven by real computed data, no
invented numbers.* That constraint drives most of the design below — where a denominator does not
exist, the value is **NULL** and the UI says "no target set" rather than printing 0%.

### 16.1 What shipped

| # | Deliverable | Where |
|---|---|---|
| 1 | `RankingService` — the three rankings + filter option sources | `app/Services/RankingService.php` |
| 2 | Admin dashboard rebuilt on the prototype's `dash-*` primitives | `app/Livewire/Dashboard.php` + `resources/views/livewire/dashboard/admin.blade.php` |
| 3 | `dash-section` / `dash-panel` / `dash-kpi*` / `dash-sec-*` / `dash-head*` / `dash-chart` / `dash-leader*` | `resources/css/app.css` (ported verbatim from `docs/prototype/assets/css/smartcemes.css`) |
| 4 | College / broad-program / AY / sort filters with `#[Url]` persistence + `clearFilters()` | `app/Livewire/Programs/Index.php` + `resources/views/livewire/programs/index.blade.php` |
| 5 | Four print reports on the R4/R5 dictionary | `app/Http/Controllers/ReportController.php` + `resources/views/reports/*.blade.php` |
| 6 | **D-R7 gap closed:** the AI surface was still shipping the retired 8.6 dictionary | `ProgramAggregates`, `PromptV1`, `ConfidenceScore`, `ProgramNarratives`, `NotifyDeadlines` |
| 7 | 25 new tests (19 ranking + 6 report) | `tests/Feature/RankingServiceTest.php`, `tests/Feature/ReportMetricDictionaryTest.php` |

### 16.2 The ranking rules, ported verbatim from the prototype

| Ranking | Sort key | Weight |
|---|---|---|
| project | `training_hours + trainees` DESC | — (prototype `engagement`) |
| faculty | `rendered_hours + projects × 10` | `INVOLVEMENT_WEIGHT = 10` |
| college | `training_hours × 1000 + trainees` DESC | tie-break so order is stable |

The faculty weight is deliberately asymmetric with the project rule. A professor who **leads three
projects and renders no hours** outranks one who renders 10 hours on a single activity — that is the
prototype's behaviour and the Director has seen it since P0, so it is preserved rather than re-derived.
`test_the_faculty_weight_is_ten` pins the constant so a change must be deliberate.

### 16.3 "No invented numbers" — where the discipline actually binds

Three places where a plausible-looking number would have been a fabrication, and what was done:

1. **Faculty have no target (D-R9).** There is no per-professor denominator, so a faculty row
   carries **no `pct` key at all**. `test_a_faculty_row_carries_no_attainment_percentage` asserts the
   *absence* of `pct`, `attainment`, `hours_pct`, `target_hours` and `hours_target` rather than that
   they are null — a regression that adds one back fails.
2. **Colleges have no target (§2.2B).** Targets exist at University and Project level only, so every
   college row reports `hours_pct => null`. A college is a contribution ranking, not an attainment one.
3. **A project with no annual target.** `hours_pct` is `NULL`, never `0`. The dashboard, the projects
   index, the annual report and the project report all render **"no target set"** for that case, and
   `test_no_report_renders_a_fabricated_attainment_without_a_target` sweeps all four report routes
   for a fabricated `0%`.

The sort key is also **reconstructible from the printed keys** on every row — asserted for projects
(`engagement == training_hours + trainees`) and faculty
(`engagement == rendered_hours + projects × 10`), so a ranking can never disagree with the number
printed beside it.

### 16.4 The R5 dashboard, against the prototype

`dashboard-admin.html` keeps **exactly two charts** — Training Hours vs Target and Budget Utilized vs
Annual Target — and `_dashtest.cjs` pins that. R5 removed the Program Portfolio doughnut to match.
Also gone, per P0m: the **Community Reach** panel (`#chReach`), the **Action Center**, and the
`Trainees Reached` tile. What replaced them:

| Section | Contents |
|---|---|
| KPI row | Extension Projects · Training Hours Rendered (vs the annual pool, 100% marker) · Budget Utilized of ₱… · Pending Approvals |
| Trends | Training Hours vs Target (2 series) · Budget Utilized vs Annual Target (bar turns red iff over) · Recent Activity |
| Performance | Most Performing Projects (medal + college colour stripe + short title + "no target" badge) · Most Performing Faculty (avatar + initials) |
| Intelligence | Community Insights Queue · Project Narratives |

The project's short label is derived, not stored: there is **no `ExtensionProject.acronym` column**,
so `RankingService::shortTitle()` takes the leading segment before a colon — `"BUSOG: Nutrition &
Feeding"` → `"BUSOG"`.

### 16.5 The D-R7 gap this phase found and closed

R4's record claimed the AI surface had been rebuilt on the new dictionary. It had **not** — five
classes were still shipping the retired 8.6 vocabulary, which is worse than a stale view because the
Gemini prompt was actively *instructing* the model to narrate retired metrics:

| Class | What was wrong | Now |
|---|---|---|
| `Ai/ProgramAggregates` | fed `knowledge_gain`, `cost_per_beneficiary`, `community_reach`, per-objective statuses to the prompt | returns `project{}` / `training{}` / `budget{}` / `activities{}` |
| `Ai/Prompts/PromptV1` | told the AI to summarise *"objective achievement (X of Y met)"* and grade health by objective status | explains `trainors × trainees × days`, NULL-means-no-target, and **forbids** the retired vocabulary by name |
| `Ai/ConfidenceScore` | richness keyed on `objectives.total` | 6 dotted metric paths read via `data_get()` |
| `Livewire/ProgramNarratives` | rows carried `objectivesTotal` / `objectivesAchieved` | carries `training_hours`, `target_hours`, `hours_pct`, `trainees`, `activities` |
| `Console/NotifyDeadlines` | pushed **objective** deadline alerts into the Director's inbox | alerts on a **training-hours shortfall** (past midpoint, attainment below elapsed) |

The scheduler rewrite is the one with behaviour to explain: an unset target is an **absence of data,
not a shortfall**, so the alert fires only when `annual_target_hours > 0`, the project is past its
midpoint (`elapsedPct >= 50`), and `attainment < elapsedPct`. `test_scheduler_stays_silent_without_a_
target_or_a_shortfall` locks the silent case.

### 16.6 The PHP `+` operator bug (a real trap, worth recording)

`test_scheduler_notifies_a_training_hours_shortfall` failed with *"Output 'Deadline notifications
sent: 1.' was not printed"* while every input looked correct. Root cause was in the **test helper**:

```php
// WRONG — the union operator keeps the LEFT operand's value for every shared key
return ExtensionProject::create([
    'planned_start_date' => '2026-01-01',
    'planned_end_date'   => '2027-12-31',
    …
] + $overrides);          // ← every date override was silently discarded

// RIGHT
return ExtensionProject::create(array_merge([…defaults…], $overrides));
```

`$defaults + $overrides` does **not** let overrides win — `+` preserves the left side. So
`makeProgram(['planned_start_date' => now()->subDays(80)])` created a project starting `2026-01-01`,
and the scheduler's own date-window arithmetic (which reads the *stored* dates) computed
`39` days elapsed out of `729` → `elapsedPct = 5% < 50%` → the command correctly stayed silent.

Worth recording because the failure was **misleading**: the test read as if the command were broken,
and the command was right. The diagnostic that cracked it was dumping `getRawOriginal()` from inside
the *failing* context — not `create()` arguments — which showed the stored row disagreeing with the
test's intent. A sweep confirmed no other test file uses `] + $`. `TrainingHoursTest` already used
`array_merge`, which is why its date-sensitive tests always passed.

### 16.7 The SQLite portability trap, again

`filterOptions()` computes its year list in **PHP**, not SQL:

```php
->pluck('planned_start_date')
->map(fn ($d) => (int) Carbon::parse($d)->year)
->unique()->sortDesc()->values()
```

`DISTINCT YEAR(x)` is **MySQL-only**. The suite runs on in-memory SQLite, so a `selectRaw('DISTINCT
YEAR(...)')` implementation would pass against a local MariaDB and break the whole 403-test suite.
`test_filter_options_compute_years_in_php_for_sqlite_portability` asserts the portable path is the
one in use and that the result contains **ints**, not raw date strings.

### 16.8 Latent bug fixed in passing

`Programs\Index::resetForm()` was missing `college_id`, `program_id`, `annual_target_hours` and
`annual_target_budget`. The omission was invisible on a first create (the fields were never set) and
would have surfaced as a **validation failure on the second create** — the form would have re-posted
the previous project's college. Fixed alongside the R5 filter work.

### 16.9 Verification

| Check | Result |
|---|---|
| PHPUnit full suite | **403 tests, 1771 assertions — OK** |
| `Tests:` delta | 378/1632 (post-R4) → **403/1771** (+25 tests) |
| `pint` on authored files | 8 style issues **fixed**; only pre-existing CRLF `line_ending` noise remains |
| `npm run build` | 62 modules, 23.09s, 260.55 kB JS / **92.25 kB CSS** (was 89.99 — the `dash-*` block) |
| Prototype harnesses | `_check` OK · `_smoke` OK · `_hubtest` **37** · `_facultytest` **124** · `_dashtest` **39** |
| `migrate:fresh --seed` | clean end-to-end (verified on a file-backed SQLite DB; local MariaDB not running) |
| Ranking audit on seeded data | BUSOG 164.5 engagement / 90% attainment first; faculty `9 + 2×10 = 29` first; **CAS 139.5 hrs** first; **`pct` NULL on every college row**; **0** faculty rows carrying an attainment key |

### 16.10 What R5 did NOT do

- **`targets.html` native rework** — the prototype still shows four parallel target tiles; the
  Laravel page correctly renders **ONE** training-hours pool, because the prototype's
  `targetActivities` has no backing column, so the real page shows a **"no target"** badge instead of
  inventing one. This is deliberate divergence, not a gap.
- **R3c was picked up after R6 and is complete — see §18.** It was unblocked from R4 onward
  (`activities.no_of_days` exists, so `trainingIsMeasurable()` is `true`).
- **Two stale artefacts still disagree with reality** and are R7's job: `AI_HANDOFF.md` claims "222
  tests passing" (it is 423) and `SYSTEM_BLUEPRINT_V4.txt` says "nothing has been built yet".

---

## 17. PHASE R6 — AI GUARDRAIL + INTERAGENCY CATALOGUE (COMPLETE)

**Exit criteria (§5 R6, verbatim):** *"a needs assessment producing health/infrastructure needs yields
referrals citing only catalogue agencies; no CESO-mandate violations."*

Both halves are now proven by tests rather than asserted in prose — see §17.6.

### 17.1 The problem R6 exists to solve

CESO's mandate is community **training** — the six thrusts in §3. A barangay survey does not care about
that boundary: it reports malnutrition, no potable water, unpaved roads. The adviser flagged exactly this
(feedback #8): the AI was turning those into "recommendations", which misstated CESO's scope.

R6 does not *suppress* those needs — suppressing a real need is its own failure. It **reclassifies** them:

| Tier | Meaning | Where it lands |
|---|---|---|
| **Tier 1** | A CESO training intervention under one of the six thrusts | `recommendations[]` |
| **Tier 2** | A real need outside CESO's training mandate | `interagency_referrals[]`, citing a catalogue agency |
| **Tier 3** | Prohibited as CESO work | May appear **only** as Tier 2 |

### 17.2 What shipped

**1. The catalogue (data layer).**
- `interagency_agencies` — `agency_code` (unique), `agency_name`, `mandate`, `need_category`,
  `sample_service`, `contact_info`, `active`, `sort_order`, timestamps, soft deletes.
- `InteragencyAgency` model with `scopeActive`, `scopeOrdered`, `resolve()`, `promptCatalogue()`,
  `activeCodes()`, `label` / `sampleServices` accessors.
- `InteragencyAgencySeeder` — the eight §7.2 agencies, idempotent by code via
  `withTrashed()->updateOrCreate()`, so re-seeding restores a retired agency without duplicating rows.
- `InteragencyAgencyFactory` with `inactive()` / `withCode()` states.

**2. `PromptV2` (the guardrail).** Written fresh; **`PromptV1` is kept untouched and uncalled** so an
analysis stored with `metadata.prompt_version = "v1"` stays reproducible from the version tag alone.
`PromptV2` embeds:
- the six CESO thrusts with their pillar;
- the three pillars (Social / Economic / Environmental);
- an eight-entry **prohibition list**, each paired with the agency that does own it — that pairing is
  what lets the model route a prohibited need to Tier 2 instead of dropping it;
- a **reclassified-outreach table** mapping CESO's own published Community Outreach categories
  (Food/Nutrition/Health, Medical/Dental/Optical Missions, Clean & Green/Coastal Clean-up) onto the
  agencies that deliver them;
- the **live catalogue** as the only citable set, with an explicit `Allowed codes:` line;
- the three-tier classification rule, the output schema (`interagency_referrals[]` =
  `{need, agency_code, agency_name, rationale}`), and `0-5 entries … Use an EMPTY ARRAY`.

**3. Server-side validation** (`GenerateAssessmentAnalysis::validatedReferrals()`). Every referral is
resolved against the catalogue by `agency_code`. A code that does not resolve is **dropped and counted**,
never laundered — the catalogue's own `agency_code` casing and `agency_name` are backfilled so the model's
prose is never trusted for display. Counts land in `metadata`:
`referrals_returned` / `referrals_accepted` / `referrals_unverified`.

**4. Reproducibility.** The exact citable set is snapshotted into
`metadata.agency_catalogue_snapshot` (the narrow 4-key prompt shape — `contact_info` is **not**
transmitted). Retiring an agency afterwards does not rewrite the snapshot.

**5. Admin CRUD** (`/interagency`, `App\Livewire\Interagency\Index`, `InteragencyAgencyPolicy` registered
explicitly in `AppServiceProvider::boot()`). Retire/restore instead of hard delete — see §17.4.

**6. The two-tier UI** (task #47) — see §17.3.

### 17.3 The two groups are structurally separate, not cosmetically different

`resources/views/livewire/ai-analysis.blade.php` now renders the old single "Recommended Interventions"
panel as **two** panels:

- **CESO interventions** — `tier-card tier-1`, green rail, `tier-badge tier-1` reading
  *"Tier 1 · Within CESO mandate"*, plus each item's CESO thrust.
- **Interagency referrals** — `tier-card tier-2`, amber rail, `tier-badge tier-2` reading
  *"Tier 2 · Outside CESO mandate"*, the resolved agency via `$agency->label`, and an
  `.interagency-note` reading **"Refer to DOH — Department of Health"** with the explicit line
  *"Outside CESO's training mandate — logged as an interagency intervention, not a CESO activity."*

An unresolved citation is shown, not hidden: a red note reading *"Agency not in the catalogue"* naming the
code the model actually returned, plus a count of rejected citations. An empty referral list is stated
plainly ("No needs fell outside CESO's mandate for this assessment") rather than silently omitted — that
is a *result*, and the reviewer wants to see it.

The analysis panel also exposes the `agency_catalogue_snapshot` in a `<details>` block, so the reviewer can
see the exact vocabulary the model was working from.

The prototype's `.pillar*`, `.tier-badge` / `.tier-1..3`, `.tier-card*` and `.interagency-note` rules were
**ported verbatim** into `resources/css/app.css` (§17.5).

### 17.4 Two deliberate divergences from the prototype (prototype fidelity record)

`docs/prototype/pages/interagency.html` was mirrored for layout, copy and interaction. Two fields did not
carry over, and this is recorded deliberately — the same discipline as §16.10's `targets.html` note.

1. **No `Pillar`, `MOA` or `Abbr` columns.** The prototype's table had `Agency · Intervention category ·
   Scope · Pillar · Contact · MOA · Status`. The real schema has no pillar or MOA column, for a substantive
   reason: CESO's three pillars classify **CESO's own thrusts**, so tagging DOH or DPWH with a pillar
   would assert something the agency's mandate does not support. MOA tracking was never in the approved
   scope. `abbr` is derivable from `agency_code` (the code *is* the abbreviation), and the prototype's
   hand-dragged ordering became an explicit `sort_order`. The real table is therefore
   `Agency · Intervention category · Scope owned by the agency · Contact · Order · Status`.
2. **Retire, not remove.** The prototype's "Remove" spliced the row out and refused if the agency had
   referrals. The real implementation flips `active = false` (plus soft delete), because
   `resolve()` deliberately uses `withTrashed()`: a hard delete would retroactively turn every past
   referral to that agency into a false "unverified citation". Retirement still removes the agency from
   `PromptV2` immediately — which is the behaviour the guardrail actually needs — while history keeps
   resolving.

### 17.5 Files touched

**Created**

| File | Purpose |
|---|---|
| `app/Policies/InteragencyAgencyPolicy.php` | `viewAny`/`view` true; `manage`/`create`/`update`/`delete` = `isAdmin()` |
| `app/Livewire/Interagency/Index.php` | Catalogue CRUD + filters + referral panel |
| `resources/views/livewire/interagency/index.blade.php` | The page (prototype-mirrored) |
| `tests/Feature/R6GuardrailTest.php` | 20 tests / 117 assertions |
| `docs/prototype/_interagencytest.cjs` | 44-assertion functional harness for the new page |

**Modified**

| File | Change |
|---|---|
| `app/Providers/AppServiceProvider.php` | Registers `InteragencyAgencyPolicy` |
| `routes/web.php` | `GET /interagency` → `interagency.index` in the admin group |
| `config/smartcemes.php` | `nav.admin` → Management: **Interagency Catalogue** |
| `resources/css/app.css` | Ported `.pillar*`, `.tier-badge`/`.tier-1..3`, `.tier-card*`, `.interagency-note` |
| `resources/views/livewire/ai-analysis.blade.php` | Single panel → two tier groups (§17.3) |
| `tests/Feature/Phase5AiTest.php` | `prompt_version` assertion `v1` → `v2` |
| `docs/prototype/_shim.cjs` | Added `ChildNode.remove()` — see §17.7.5 |

*(Files created earlier in the phase and already verified: the two R6 migrations, `InteragencyAgency`,
`InteragencyAgencySeeder`, `InteragencyAgencyFactory`, `PromptV2`, plus the `AssessmentAnalysis` referral
helpers and the `GenerateAssessmentAnalysis` wiring.)*

### 17.6 Verification (all green)

| Check | Result |
|---|---|
| R6 suite | **20 passed / 117 assertions** |
| Full suite | **423 passed / 1,888 assertions** (was 403/1,771 — exactly +20, zero regressions) |
| `pint` | 6 files, 5 style issues fixed; re-run clean |
| `npm run build` | ✓ 62 modules, `app-BM9nBIci.css` 93.65 kB |
| `migrate:fresh --seed` (SQLite) | Clean; `InteragencyAgencySeeder` seeds the 8 agencies with `sort_order` 10…80 |
| Catalogue probe | 8 rows; `activeCodes()` = 8 codes; `promptCatalogue()` keys = `agency_code, agency_name, need_category, sample_service` (no `contact_info`); `resolve('doh')` → `DOH`; `resolve('NOPE')` → `NULL` |
| `/interagency` render | HTTP 200 as admin, **302 as guest**; all 8 rows; all prototype elements present (header copy, explainer card, three tier chips, 4 tiles, filters, table, referrals panel, Add-agency modal) |
| `/ai-analysis` render | HTTP 200; both tier groups, both tier badges, `tier-card tier-1`/`tier-card tier-2`, `.interagency-note`, resolved agency label, unverified-citation note, catalogue snapshot block |
| Prototype harnesses | `_smoke` OK · `_check` OK · `_hubtest` 37 · `_facultytest` 124 · `_dashtest` 39 · **`_interagencytest` 44 (new)** |

**The exit criteria, specifically.** `test_health_and_infrastructure_needs_yield_catalogue_only_referrals`
builds a summary skewed to malnutrition + no potable water, runs the job against a faked model reply that
returns `DOH` and `LGU` referrals, and asserts (a) both resolve against the catalogue, (b)
`unverifiedReferralCount()` is 0. The negative direction is covered by
`test_an_invented_agency_code_is_rejected_and_counted` (a plausible-but-absent `BFAR` is dropped and
counted) and by the prompt tests (`test_the_prompt_embeds_only_active_catalogue_rows`,
`test_an_empty_catalogue_forbids_naming_any_agency`, `test_a_prohibited_item_must_not_appear_as_a_ceso_recommendation`).

### 17.7 What broke, and the lessons

1. **A static prompt section is not the catalogue.** Two tests initially failed asserting `DPWH` was
   absent from the retired-catalogue prompt — because `PromptV2`'s *prohibition list* legitimately still
   names DPWH as the body a roads need belongs to. That is the Tier-3 → Tier-2 bridge and it **must**
   survive a retirement. The fix was in the **test**, not the product: added
   `catalogueSectionOf()` / `prohibitionSectionOf()` / `allowedCodesOf()` slice helpers so assertions read
   only the section they are about. *Lesson: when a prompt mixes a static vocabulary with a live one,
   assert against the section, not the whole string.*
2. **`resolve()` uses `withTrashed()` — and that is correct.** A test assumed retiring an agency would
   make its historical referrals report as unverified. It does not, deliberately: the point of
   `withTrashed()` is that retirement removes an agency from the model's *vocabulary* without rewriting
   *history*. Renamed the assertion to test what the code actually promises.
3. **`Livewire::test()` cannot assert a forbidden `mount()`.** The established R3 gotcha (mount
   authorization runs before a snapshot exists) bites in the test harness too: the component fails on
   `Invalid Livewire snapshot structure` rather than the expected `HttpException`. Replaced with a direct
   `$user->can(...)` policy assertion, which is the actual contract, plus the existing HTTP-level
   (`/interagency` 403) coverage.
4. **`storage/r6check.sqlite`** — the throwaway probe DB from the earlier segment — has been deleted, as
   planned. `storage/` now contains only `app/`, `framework/`, `logs/`.
5. **The prototype harnesses had a real gap.** R6 added a brand-new page, but `interagency.html` was only
   covered by `_check.cjs` (which counts *seed* rows) and `_smoke.cjs` (which only proves the page does not
   throw). Neither would have noticed a Tier-2 card losing its rail, its agency note, or its tier class —
   the exact contract the Laravel view is held to. Added `_interagencytest.cjs` (44 assertions) covering
   the tiles, the filters (driving `renderRows()` for real rather than regex-matching source), the table,
   the three-tier legend and the referrals panel, plus an assertion that an incomplete agency is
   *rejected* rather than saved.
   Writing it exposed a defect in the shared shim: `_shim.cjs` had no `ChildNode.remove()`, so
   `SC.toast()`'s deferred teardown threw `el.remove is not a function` and took the page script down with
   it. Added — a toast is now safe to fire in any harness, which is why the add/edit path was previously
   untestable.

### 17.8 Task-list hygiene

Tasks **#21, #22, #34, #35, #36, #37, #38** were still marked `in_progress`/`pending` although R1e/R1f and
all of R4 were completed and verified earlier. They are closed as part of this phase.

### 17.9 What R6 did NOT do

- **R3c** was still outstanding at the end of R6. It has since been completed — see §18.

---

## 18. PHASE R3c — THE TWO TRAINING BLOCKS (COMPLETE)

**Exit criteria (§14.7, verbatim):** *"`FacultyContributionService` already returns the right shape; the
profile page already has placeholders in the right two blocks."*

That sentence was written before R4 landed. R4 then built `TrainingHoursService` and added
`activities.no_of_days` / `participants`, which made `trainingIsMeasurable()` flip to `true` — and
silently moved **two faculty-profile blocks onto a live-but-unfinished branch**. Nothing asserted that
branch, so a green suite sat on top of it:

| Block | What was rendering |
|---|---|
| **Training contribution** | Real numbers, correctly — but under a `(R4 dependency)` heading comment, and its `@else` panel still claimed the model *"lands in Phase R4"* |
| **Performance trend** | The literal string `Trend chart available.` — a placeholder |

R3c finishes both. It is a **correctness fix, not a feature**: the page was already reading as broken.

### 18.1 The design decision that shapes everything

`trainors × trainees × days` now has **exactly one implementation**, and it is not in this phase.

The delivery maths lives in **`TrainingHoursService::forFaculty()`**, and `FacultyContributionService`
delegates to it. If the faculty pages re-derived the formula themselves, the profile, the engagement board
and the project hub could each disagree about the same activity — which is precisely the drift that service
exists to prevent. The delegation is verified end-to-end: with no activity assigned to more than one faculty
member, the sum of every faculty member's delivered hours equals the sum of every project's
(`145.5 = 145.5` on the demo seed).

### 18.2 What shipped

**1. `TrainingHoursService::forFaculty(Collection $faculty)`** — one query for every activity any of the
given faculty are assigned to, plus chunked attendee counts, trainor counts and a
`COUNT(DISTINCT beneficiary_id)` "reached" map, folded in PHP. Same shape as `forProject()` so the two
cannot diverge. Returns, keyed by faculty id:

`training_hours_delivered` · `training_sessions` · `trainees_reached` · `training_days` ·
`training_activities` · `training_measurable` · `training_sources`

`trainees_reached` is a **distinct-person count, deliberately not** the sum of the per-activity trainee
counts — a beneficiary who attended three sessions was still reached once.

**2. `TrainingHoursService::trendForActivities()`** — buckets delivered hours on the activity's
`planned_start_date`, the same date `forProject()` orders by, so the trend and the project timeline tell
one story. Undated activities land in an explicit `'undated'` bucket rather than being dropped, since
silently omitting delivered hours would understate the trend.

**3. `FacultyContributionService`** gained a constructor and now passes the seven `training_*` keys
through from the delegated roll-up, with an all-NULL `??` fallback so a missing row degrades to the honest
default instead of a fabricated 0.

**4. `Faculty/Profile.php`** computes `$trend` / `$trendPeak` and builds a **Chart.js line chart payload**
via `trendChart()`, plus a `$trendSources` breakdown (R-Q1) so the Director can see whether a headline
figure rests on imported attendance or on manual entry.

**5. `faculty/profile.blade.php`** — both blocks finished:
- *Training contribution*: the `(R4 dependency)` comment is gone; the formula is stated as a chip
  (`trainors × trainees × days`); four tiles instead of three (`training days` added); a note explaining
  the distinct-people rule; and R-Q1 source badges. A session with no trainee count contributes 0 hours, so
  that case says so explicitly — *"a data gap, not a claim that nothing was delivered"*.
- *Performance trend*: the placeholder is replaced by a real chart, with per-period chips (the peak
  highlighted) and prose naming where the figures come from.

### 18.3 Why the trend chart is withheld in three cases

A chart is a claim about shape, so it is only drawn when there is a shape to claim:

| Condition | Rendering |
|---|---|
| < 2 buckets, or every bucket is 0 | **"No trend to draw yet."** — with the reason |
| Model unavailable | The "not measurable" panel, unchanged |
| Otherwise | The line chart |

A one-point line is not a trend; a flat line at zero would read as *steady non-delivery*. Both are the
NULL-over-0 discipline (§9) applied to a chart rather than a number.

### 18.4 A deliberate divergence: the prototype has no faculty profile page

**This is the one R3 surface with no prototype contract.** `docs/prototype/pages/` contains
`faculty-management.html`, `faculty-directory.html` and `dashboard-faculty.html` — but **no per-person
profile page**. The prototype renders faculty detail into a JS-filled drawer (`#facDrawer` →
`#facDrawerBody`), and `grep -rn "Training contribution" docs/prototype/` returns nothing at all.

So the trend chart is a **native build**, matching the established pattern of §16.10 (`targets.html`) rather
than mirroring markup. It is held to the house style instead: `sc-card`, `badge badge-*`,
`font-extrabold text-[14px] tracking-tight` headings, the prototype's typographic scale, and the *same*
Chart.js wiring the analytics partials already use (`x-data x-init` +
`Chart.getChart($refs.c).destroy()` guard, so a Livewire re-render cannot stack a second canvas). The card
sits in the narrow right column at `h-52`, so the line stays legible beside the wider left column.

### 18.5 Verification (all green)

| Check | Result |
|---|---|
| Faculty suite | **51 passed / 122 assertions** (was 37/70 — +14) |
| Full suite | **437 passed / 1,940 assertions** (was 423/1,888 — exactly +14, zero regressions). *This is R3c's own gate; R7 later took the suite to 444 / 1960.* |
| `pint` | 4 files, 3 style issues fixed; re-run clean |
| `npm run build` | ✓ 62 modules, `app-BM9nBIci.css` 93.65 kB |
| Cross-view agreement | Faculty-side total **145.5** = project-hub total **145.5** |
| Formula audit | `2 × 30 × 0.5 = 30` (the prototype's worked example); `3 × 30 × 0.5 = 45`. **No `× 8`** |
| `/faculty/{1..4}` render | HTTP 200 × 4 (**38,893–43,211 bytes**); guest → **302** |
| Chart payloads | Valid JSON; e.g. `line` with labels `["Jun 2026","Jul 2026","Aug 2026","Sep 2026"]`, data `[31,90,2,14.5]`, `beginAtZero: true` |
| Withheld charts | Carlo Sumile (1 bucket) and Kent Naputo (1 bucket) correctly render *"No trend to draw yet"* |

Checks that pin the **branch that was previously unasserted**:

- `test_the_training_contribution_block_shows_delivered_figures` — 4 tiles, formula chip, no "Not yet measurable".
- `test_the_training_contribution_block_names_its_data_source` — R-Q1 tag reaches the UI.
- `test_the_training_contribution_block_flags_an_unrecorded_trainee_count` — the data-gap copy.
- `test_the_performance_trend_draws_a_chart_from_real_periods` — and asserts the placeholder is **gone**.
- `test_the_performance_trend_withholds_the_chart_for_a_single_point`
- `test_the_performance_trend_withholds_the_chart_when_every_period_is_zero`
- `test_an_undated_activity_is_bucketed_rather_than_dropped`
- `test_the_trend_buckets_every_date_format_the_same_way`
- `test_the_trend_accepts_a_datetime_object_as_well_as_a_string`
- `test_the_trend_accepts_the_collection_the_contribution_service_hands_over`
- `test_the_faculty_side_agrees_with_the_project_hub`
- `test_trainees_reached_counts_distinct_people_not_attendances`
- `test_each_faculty_member_gets_their_own_figures_in_a_batch` — see §18.6.1
- `test_the_roll_up_is_keyed_by_faculty_id` — see §18.6.1

### 18.6 What broke, and the lessons

1. **A signature that was right for the test and wrong for the page.** `trendForActivities(array $activities)`
   threw a `TypeError` on **every real page load**, because `FacultyContributionService` hands over the
   `Collection` that `forFaculty()` builds (via `->values()`) — while every unit test that hand-built an
   array passed. Caught by probing the seeded database, not by the suite. Widened to
   `array|Collection`. *Lesson: when a service's return type and its consumer's parameter type are declared
   independently, probe the real path — a hand-built fixture cannot catch a contract mismatch.*
2. **`$training->get($member->id)` is a lookup that cannot fail safely.** This was the near-miss worth
   recording. If the training roll-up were ever sequentially keyed — the natural shape, and what
   `Collection::map()` returns — then `get(1)`, `get(2)`, `get(3)` would all resolve, and resolve to the
   **wrong faculty members**, because faculty ids start at 1 and array offsets start at 0. The symptom is
   not an exception; it is one person's profile showing another's hours. Added
   `assertTrainingKeyedByFacultyId()`, which throws a `LogicException` on a stray key, and asserted the
   contract directly. Verified to fire by deliberately breaking the keying — the guard catches it before the
   lookup runs.
3. **Tests written per-single-member could not have caught #2.** Every existing test called
   `forFaculty(collect([$oneFaculty]))`, where a single row sits at offset 0 and the lookup still resolves.
   Added `test_each_faculty_member_gets_their_own_figures_in_a_batch` with two members holding
   deliberately different totals (10 vs 30), so a swap is visible. *Lesson: a lookup bug needs a batch test;
   one-element fixtures hide it.*
4. **Two claims in my own work were wrong, and the probe caught them.** A comment asserted that
   `substr($iso, 0, 7)` yields `'2026-03-15T00'` (it yields `'2026-03'` — `0,7` stops before the `T`), and a
   test claimed the undated bucket needed reordering after `ksort` (it does not — `'u'` sorts above any
   digit). Both were asserting rationales that were untrue. The comment was corrected and the dead reorder
   block removed; the tests were rewritten to assert what is actually true. *Lesson: a test that passes for
   the wrong reason is worse than no test — verify the rationale, not just the colour.*
5. **A misreading of my own probe output nearly became a phantom bug.** An early probe paired faculty names
   with figures from a differently-ordered collection, which read as misattribution. Re-deriving the
   ownership straight from `activity_faculty` showed the wiring was correct all along — the BUSOG activities
   belong to Nikko Villas (fid 3, 137.5 hrs), not the member I had assumed. *Lesson: when a probe's output
   looks alarming, verify the join before the hypothesis.*
6. **`Faculty` model → `faculties` table.** A query probe joined `faculty`, which does not exist; the table
   is `faculties`. Cheap to hit, worth knowing.

### 18.7 Task-list hygiene

The R3c blocker tasks are closed. **R3** in the §10 tracker moves from
`🟡 R3a + R3b complete — R3c deferred to after R4` to complete.

### 18.8 What R3c did NOT do

- **R7** remains entirely outstanding: blueprint v4.15 revision entry, the stale blueprint sections,
  `AI_HANDOFF.md`, and the end-to-end defence walkthrough.
- The two stale artefacts R7 owns were still stale at the time of writing: `AI_HANDOFF.md` said
  *"222 tests passing"* and `SYSTEM_BLUEPRINT_V4.txt` said *"nothing has been built yet"*. **Both were
  updated on 2026-09-24 as part of R7 — see §19.**
- **R5's `targets.html` native rework** remains a recorded deliberate divergence (§16.10).
- **R7** (blueprint v4.15 + `AI_HANDOFF.md`) is untouched; the two stale artefacts noted in §16.10 now read
  423 rather than 403 tests, and still need correcting.

---

## 19. PHASE R7 — HARDENING & DOCS (IN PROGRESS)

**Goal (§5 R7):** documentation and verification. **No features.**

### 19.1 What R7 has completed

**1. A real 500 found by smoke-testing every route.**
`GET /my-projects` returned **HTTP 500** — `View [livewire.projects.my] not found.` The component asked
for `livewire.projects.my` while the file lives at `livewire/programs/my.blade.php`; an R2 rename
leftover. Its three sibling components in the same directory all used the correct `livewire.programs.*`.

It survived a 437-test suite because **nothing tested that page at all** (`grep -rn "projects.my" tests/`
returned zero hits), and because every existing test went through `Livewire::test(Component::class)` —
which constructs the component directly and **never resolves a route, a middleware stack, or the Blade
view finder**. A broken *route→view* path was therefore structurally invisible to the entire suite.

Fixed, and pinned by **new `tests/Feature/RouteSurfaceTest.php` (7 tests)**, which walks surfaces **by
URL** per role:
- every nav item resolves to a real route (the sidebar *silently hides* missing ones, so a broken entry
  has no visible symptom);
- every admin / secretary / faculty nav surface renders without a 5xx;
- the **unlinked** surfaces (project hub, faculty profile, all four print reports) — exactly the routes
  nothing links from the sidebar, so they drift unnoticed;
- the four import templates download.

Verified to catch the bug by reverting the view name (2 failures naming the route and status).

**2. Two dead `KpiService` consumers removed** (D-R7 leftovers that resolved the service on every request
for a value nobody read): `Livewire/Dashboard::renderFaculty()` (plus its now-unused import) and
`Services/AssessmentSummaryService`'s unused constructor parameter. `KpiService` itself stays (R-Q2).

**3. `AI_HANDOFF.md` rewritten for the post-revision architecture.** It had claimed *"222 tests passing /
Phases 1–5"* and knew nothing of R1–R7 — actively misleading, since it opens by instructing the reader to
read it fully before writing code. Now: a prominent "read this first" box (the hierarchy, the formula, the
target model, the D-R7 removals, the AI tiers), a current-state table, `revisions.md` promoted to the
first authoritative document, the locked decisions, the rebuilt data model, the 8.6 dictionary marked
retained-unread, the phases including R1–R7, the updated prototype map, new §15 outstanding work and
§16 known gaps, and a §14.1 block of the revision's own gotchas.

**4. `SYSTEM_BLUEPRINT_V4.txt` corrected** — it said *"IMPLEMENTATION STATUS: nothing has been built
yet"*. Now: a status banner, a **v4.15 revision entry**, §6.4 rewritten as 6.4/6.4a/6.4b/6.4c with the
rename and the two new levels, all 6 stale `extension_program_id` FKs renamed (plus 5 further
`ExtensionProgram` references), the new `activities` training fields documented, and a **v4.15 status
notice at §8.6** marking the dictionary superseded for the UI.

**5. `revisions.md` itself** — header and tracker made current, the stale "phases R1–R7 remain untouched"
plan-status note replaced, and a "how to read this document" navigation table added for new sessions.

### 19.2 R7 findings: prototype-fidelity gaps on `/programs` (flagged, NOT fixed)

An audit of the extension-programs management surface against `docs/prototype/pages/programs.html` found
divergences in two categories. **The distinction is the finding.**

**Category A — Laravel is CORRECT, the prototype is stale.** `programs.html` **contradicts D-R5** by
rendering per-program training-hours targets and attainment (`968 Training Hours Rendered / of 2,640
annual target · 37%`, per-card `149%`). `colleges.html` *was* updated for D-R5 and carries an explicit
comment; `programs.html` never was — the prototype is internally inconsistent. Nuance worth keeping: the
prototype's **project count and rendered hours are legitimate *derived roll-ups*** of child projects
(`seed-data.js:698-708`), which D-R5 does **not** forbid. Laravel dropped those too — an over-correction.

**Category B — genuine drift (nothing justifies these):** no summary tiles; **no toolbar at all** (no
search, pillar chips, college filter, sort, or grid/list toggle — R5 was *"filters, ranking &
dashboards"* and gave other list surfaces `#[Url]` filters); no list/table view; no `← Colleges` /
`View all projects →` header buttons; and the card is a different component (a blue gradient banner with
a watermark, versus the prototype's pillar accent + mono code badge + college pill + 3-stat row +
progress bar + `View projects →` CTA). The card offers **"Edit"** instead of a drill-down link, so a
management card has no path to its own projects. The same invented card is on the Laravel **Colleges**
page while prototype `colleges.html` uses a different design.

**Category C — the nav rule is machine-checkable and currently failing.** The prototype deliberately
collapsed the three hierarchy levels into **ONE** sidebar entry (`Manage Extension Programs`,
`subs:['colleges','programs','projects','program-detail']`), and `_check.cjs:196-210` **fails** if the
admin sidebar contains `label:'Colleges'`, `label:'Extension Programs'` or `label:'Extension Projects'`.
`config/smartcemes.php` currently has **exactly those three labels** — R1 added them without reconciling
the P0 decision. Nothing on the Laravel side asserts the collapse rule.

**Category D — prototype-side stale leftovers.** `compliance.html` ("Program Compliance Matrix") is still
present and still linked from `index.html` and the secretary dashboard's "Compliance Snapshot" card, but
the blueprint's **v4.12 entry records the Compliance Tracker as a *"phantom never-built"* config entry
that was deliberately removed**. Laravel correctly has no `/compliance` route.

### 19.3 Documentation debt R7 has NOT yet cleared

- ~~`README.md` is still the **default Laravel boilerplate** — it says nothing about SmartCEMES.~~ ✅ **Fixed 2026-09-24** — replaced with a real project README (status, hierarchy, quick start, demo accounts, verification commands, doc reading order, conventions, known doc debt).
- `docs/TEST-SCRIPT.md` (written against blueprint v4.11) and the **11 `docs/guides/*`** walkthroughs
  predate the revision: they call projects "programs", and guide 02 teaches objectives with
  live-computed KPIs, which D-R7 removed. Guide 01 ("create a new extension program") now describes the
  wrong level.
- `docs/adminguide.md` / `secretaryguide.md` / `facultyguide.md` / `features.md` were refreshed for
  v4.13 but not for the revision.

### 19.4 Verification

| Check | Result |
|---|---|
| Full suite | **444 passed / 1960 assertions** (was 437/1940 — +7 from `RouteSurfaceTest`, zero regressions) |
| `pint` | Clean on all R7-touched files |
| Route smoke, all 3 roles, **real MySQL driver** | No 5xx on any surface; guest → 302 |
| Nav integrity | Zero phantom entries |
| Prototype harnesses | All six green — `_check` · `_smoke` · `_hubtest` 37 · `_facultytest` 124 · `_dashtest` 39 · `_interagencytest` 44 |
| `migrate:fresh --seed` | Clean on SQLite and MariaDB 10.4.32 |

Also resolved during R7: the local MariaDB was **10 migrations behind**, which surfaced as
`Table 'smartcemes_v4.extension_projects' doesn't exist`. Diagnosed via `php artisan migrate:status`;
fixed with `migrate:fresh --seed` **after** verifying the DB held only regenerable seed data (every table
count matched the seeders exactly) and rehearsing the fix on a throwaway database.

### 19.5 What R7 still has to do

1. ~~The blueprint's remaining stale sections (§8 lists them).~~ ✅ **Done — blueprint v4.16, see §19.8.**
2. Refresh `docs/guides/*` and the role guides — they are bannered, not rewritten. (`docs/TEST-SCRIPT.md` ✅ **rewritten and executed — see §19.7.**)
3. ~~Replace the default `README.md`.~~ ✅ **Done.**
4. ~~Re-run the defence walkthrough end to end.~~ ✅ **Done — see §19.7.**
5. ~~Decide the `/programs` + `/colleges` fidelity gaps and the nav-collapse rule (§19.2 B and C).~~ ✅ **Done — see §19.6.**
6. Deployment-side: ~~nightly DB backup script~~ ✅ **done (§19.6)** + production deploy (absorbed from the retired "Phase 6").

---

## 19.6 PHASE R7 (continued) — UI FIDELITY, NAV COLLAPSE, NIGHTLY BACKUP (COMPLETE)

Owner picked the **UI fidelity + nav rule** workstream out of §19.5. It was the only *code* item left.
Suite **444 → 458 tests / 1960 → 2029 assertions**, 0 failures.

### 19.6.1 `/colleges` becomes the two-view hub

Was a flat CRUD list wearing an invented gradient card. Now: **view 1** = the three college cards only
(media area + crest + placeholder, coordinator, thrust, Projects/Programs/Faculty metrics, program
badges, derived footer, `Open projects →` CTA); **view 2** = that college's hero, its derived KPIs, its
projects behind a search + status toolbar, and its faculty grid.

`#[Url(as:'college')] public string $college` gives the deep link (`?college=CAS`) **and** browser
history — replacing the prototype's hand-rolled `pushState`/`popstate`/`goBack`. An unknown `?college=`
degrades to view 1 rather than 404ing. College CRUD is retained (View 1 header + View 2 "Edit").

**No per-college training-hours figure anywhere** — a college has no target (§2.2B / D-R5), and
`_check.cjs:254-267` forbids one. Every card figure is a derived roll-up of the college's projects.

### 19.6.2 `/programs` restored to the prototype — minus what D-R5 forbids

Category B of §19.2 is closed: 4 summary tiles, the full toolbar (search / pillar chips / college
select / sort / grid↔list, all `#[Url]`), the 7-column list view, the `← Colleges` +
`View all projects →` header buttons, and the prototype's card (pillar accent, mono code badge, pillar
chip, college pills, 3-stat row, status badge, `View projects →` CTA). Modal wording aligned.

**The correction that mattered:** the prototype renders per-program **targets and attainment**, which
contradict D-R5 (§19.2 category A). Restored are the *derived roll-ups* the audit said Laravel
over-corrected away — project count, hours delivered, reach, budget consumed, and college membership
derived from the projects (`programs` has no `college_id` by design, §3). Every target stays out.
`ExtensionHubTest::test_a_program_exposes_no_target_or_attainment` asserts the view-model rows carry no
target/attainment key at all, so "no target" is pinned structurally rather than by string-matching.

### 19.6.3 The nav-collapse rule — the machine-checkable failure is fixed

§19.2 category C is closed. The admin sidebar now carries **one** hierarchy entry —
`Manage Extension Programs` (`colleges.index`, `subs` = `colleges.index` / `programs.index` /
`projects.index` / `projects.show`) — so the prototype's own rule (`_check.cjs:196-210`) is satisfied.
`University Targets` moved to Overview and `Interagency Catalogue` to Intelligence & Reports (both per
the prototype); `Program Narratives` → `Project Narratives`.

**Three decisions worth keeping:**

| Decision | Why |
|---|---|
| **`subs` are exact route names, not wildcards** | First written as `colleges.*`. A wildcard is not a registered route name, so `RouteSurfaceTest::test_every_nav_item_points_at_a_real_route` failed — and a typo in `subs` would otherwise fail *silently* (the highlight just stops). |
| **`RouteSurfaceTest` now walks `items + subs`** | `/programs` and `/projects` stopped being nav ITEMS. Walking items only would have **silently reduced 5xx coverage** for exactly the pages nothing else links — the failure that file exists to catch. Parameterised routes (`projects.show`) are skipped and covered by the unlinked-surfaces test. |
| **Nav resolution extracted to `App\Support\Navigation`** | The sidebar and the topbar each had their own copy of the active-state heuristic. With `subs` in play, two copies would have drifted. `pageLabel()` resolves a sub-page to its HUB's label, so a project hub still reads "Manage Extension Programs". |

### 19.6.4 The nightly database backup (absorbs the retired "Phase 6" item)

`php artisan smartcemes:backup-database`, scheduled **02:00 daily** in `routes/console.php`, retaining
14 dumps. See `AI_HANDOFF.md` §15.1 for the full contract. The parts that matter:

- **Driver-aware** — MySQL/MariaDB via `mysqldump --single-transaction --skip-lock-tables --quick
  --routines`; SQLite via `VACUUM INTO` (atomic; a plain copy of a live database can tear). A
  `:memory:` connection warns and exits 0 so a test suite never fails a scheduled run.
- **The password never reaches argv** (it goes through `MYSQL_PWD`) — asserted by a test, because argv
  is world-readable in the process list.
- **A partial dump cannot be mistaken for a good one** — staged at `.tmp`, renamed into place only after
  a non-empty check; any failure exits FAILURE and logs.
- **`DB_DUMP_BINARY` is configurable, and usually needs to be.** `mysqldump` is very often outside the
  PATH on Windows (XAMPP/Laragon/WAMP) and on shared hosts.
- **Verified end to end**: a 250 KB dump of `smartcemes_v4` (37 tables) restored into a scratch database
  with row counts identical to the live DB (3 colleges / 6 programs / 7 projects / 6 users). The scratch
  DB was dropped afterwards.

### 19.6.5 Bugs found while doing this

| Bug | Lesson |
|---|---|
| `database.default` is a **connection name**, not a driver. The command matched on it, so every backup reported "nothing to back up" and exited 0 — a silently useless backup. | Read the driver off the connection. Caught only because the test asserted a file was actually produced. |
| A filename built with `H:i:s` — a colon is **illegal on Windows**. | Use `His`. The app is developed on Windows. |
| Two `Edit` calls to the **same file in one message** raced, and the second clobbered the first, leaving a config value half-changed. | Never batch edits to one file in parallel. Symptom was a baffling "these route names don't exist" failure for routes that plainly did. |
| The `/projects` header still read *"Extension programs · codes EXT-…"* — a naming-contract violation, and the nav collapse had just removed its only sidebar path back to `/programs`. | Fixed: correct copy + a `← Programs` link. |

### 19.6.6 Verification

| Check | Result |
|---|---|
| Full suite | **458 passed / 2029 assertions**, 0 failures |
| `RouteSurfaceTest` | 7 green — nav→route (items **and** subs), every nav surface per role, the unlinked surfaces, import templates |
| `pint` | Clean on every touched file |
| `npm run build` | Green; the ported hub classes confirmed present in `public/build/assets/app-*.css` |
| Prototype harnesses | All six still green — the prototype was the *reference*, not the target of change |
| Real-path check | Logged in as admin against the seeded MariaDB: `/colleges`, `/colleges?college=CAS`, `/programs` all 200, with `hier-node` and per-college `Training hours` correctly absent |
| Backup | Dump written, then restored into a scratch DB and verified row-for-row |

---

## 19.7 PHASE R7 (continued) — DEFENCE WALKTHROUGH RE-RUN (COMPLETE)

Owner asked to continue with the walkthrough. `docs/TEST-SCRIPT.md` was rewritten for the revised
model — it had described the pre-revision hierarchy since 2026-09-15 and taught the removed 8.6
objectives — and then **executed**.

### 19.7.1 The script, rewritten

It is now a walkthrough of the post-revision system: the four-level hierarchy and where each level
lives in the **collapsed** admin nav; creating a **project** (college-prefixed code) rather than a
"program"; the `trainors × trainees × days` model with `Days` carrying the duration; the target model
at **University + Project** level only; the import-only attendance flow; the rendered-hours lifecycle;
and the three-tier AI guardrail. The 8.6 objective/KPI steps are gone.

Two appendices were added: the **seeded demo data** (so the walkthrough can be run on existing rows),
and a verification record.

### 19.7.2 The script is now executable, not aspirational

`tests/Feature/DefenceWalkthroughTest.php` drives steps 2, 3, 6, 7 and 8 through the real Livewire
components and asserts **every figure the script prints** — the `CME-2026-001` code, the arithmetic,
15 / 40 → 37.5 %, ₱13,500 / ₱30,000 → 45 %, the 4-hour rendered-hours drafts, and the `trainees`
count moving 0 → 5 only once attendance exists. A model change that invalidates a number in the
document now fails a test instead of embarrassing someone mid-defence.

### 19.7.3 Two real bugs found — both in the 8.8 faculty-conflict hard-block

Neither was reachable from the existing suite, because every existing test used faculty with **no
prior activities**, so the guard's query never returned a row and its message was never built.

| # | Bug | Why it mattered |
|---|---|---|
| 1 | `Hub::findScheduleConflict()` filtered `->where('id', '!=', …)` on the `$faculty->activities()` relation, which joins `activities` to `activity_faculty` — and **the pivot has its own `id`**. The column was ambiguous and the query threw on every driver. | Assigning faculty to an activity **crashed instead of saving**. Fixed by qualifying: `activities.id`. |
| 2 | The refusal message has **four `%s` placeholders and three arguments**. | The guard raised `ArgumentCountError` **instead of refusing** — so even when a conflict was detected, the hard-block did not work. Fixed by passing the end date, so the message now names the clash and its range. |

Both are pinned by `test_the_schedule_guard_refuses_a_clashing_assignment_with_a_message`, and the
sprintf fix was **mutation-tested** — reverting it reproduces the `ArgumentCountError`.

Also fixed: the `/projects` create button, list header and submit button still said **"New Program"**
(an R2 rename leftover) — the exact naming confusion `AI_HANDOFF.md` warns about.

### 19.7.4 Three traps the re-run exposed, now documented in the script

1. **Seeded bookings collide with naive dates.** Nikko Villas is committed to *Feeding Cycle 2* from
   2026-09-08 → 2026-10-16, so a September activity assigned to him is refused. The script's activity
   dates moved to 2026-10-20 / 2026-10-27, and step 12 now *deliberately* uses the September window
   to demonstrate the block.
2. **`Participants` is a fallback, not the reach.** Until attendance exists the hours use
   `activities.participants` (R-Q1); the project's **Trainees** tile is a distinct beneficiary count
   and reads 0. Two different numbers, by design.
3. **Imported attendance REPLACES the fallback**, so the computed hours can *drop* when attendance
   lands for fewer people than `participants` claimed. The script now sets `participants` to 5 —
   matching the five beneficiaries it registers — so the hours stay stable across the import instead
   of silently changing.

### 19.7.5 Verification

| Check | Result |
|---|---|
| `DefenceWalkthroughTest` | 4 tests / 45 assertions, green |
| Mutation test | Reverting the sprintf fix reproduces the failure — the guard is proven, not assumed |
| Full suite | **469 tests / 2102 assertions**, 0 failures |
| Read-only observations | Executed against the seeded MariaDB through a real admin login; the quoted figures are what the pages rendered |

> **Method note.** `php artisan test` was killed (SIGTERM) in the foreground in this environment while
> `vendor/bin/phpunit` ran fine — use the latter for single-class runs here.

---

## 19.8 PHASE R7 (continued) — THE BLUEPRINT STALE-SECTION REWRITE (COMPLETE)

`revisions.md` §8 had listed the remaining stale sections since the v4.15 pass: the cheap,
unambiguous fixes landed then, and the **full section rewrite** was the last substantial item.
It is done — the blueprint moves **v4.15 → v4.16**.

### 19.8.1 What was wrong

The blueprint is the design contract, and roughly **90 claims** still described the pre-revision
system. The worst of them were not cosmetic: §2 told the reader the Director "defines and manages
program objectives via the program hub's objective manager", §5.10 defined M&E as "all KPIs computed
strictly per 8.6", §12 described a KPI scorecard and a Program Results Framework report, and §15's
success criteria required that "all dashboard KPIs match the 8.6 metric dictionary exactly". Every one
of those describes a system that no longer exists.

### 19.8.2 What changed

| Section | Change |
|---|---|
| **§2** | Role workflows rebuilt: the objective manager replaced by the **target model**; "extension programs" → projects; the **collapsed hub navigation** documented; Faculty Management described as a real module (board + directory + profile, own-profile self-service); the AI access rule extended with the **three-tier** classification. |
| **§3** | §3.2 re-framed on the target model (8.6 marked retained-unread); §3.3 on project performance + contribution rankings + the tiered AI. |
| **§5** | §5.1 (Faculty Management) rewritten as the real R3 module; §5.2 retitled **Project Management (Project Hub)** with the target model and the removed objective manager; §5.4/§5.5 program→project; §5.10 retitled **Monitoring, M&E Metrics & the Target Model** (a full rewrite); §5.11 gained the synchronous-generation note and the three-tier rule; §5.13's per-objective progress bars replaced; §5.15 retitled **Project Narrative** and re-based on target-model inputs. |
| **§6** | §6.15 given a **RETAINED IN CODE, UNREAD** status header; §6.4c's `objectives` field clarified as free text only and the `ProgramObjectives` relation marked unread; §6.16's narrative summary and `raw_extracted_data` re-based on the target model; §6.2's "feed the 8.6 KPI" corrected. |
| **§8** | §8.1 retitled **Project Tracking**; §8.3 project-level budget; §8.5's output type 2 re-based on target-model inputs and given the **three-tier guardrail**; §8.6's header now reads **[RETIRED FROM THE UI - RETAINED IN CODE ONLY]**. |
| **§12** | Dashboards and reports rebuilt on the target model; the legacy `reports.results-framework` **route** name flagged as legacy with the content correct. |
| **§14** | **NEW: D14–D20** recording the revision's decisions as first-class design decisions — D14 four-level hierarchy · D15 `trainors × trainees × days` (no `× 8`) · D16 the target model · D17 the 8.6 retirement · D18 the AI three-tier guardrail · D19 contribution-based faculty performance · D20 the collapsed navigation. |
| **§15/§16** | The success criterion re-based on the target model; §16's summary paragraph corrected and a **v4.16 revision entry** added. |

**Deliberately NOT changed:** the **v4.1–v4.15 revision entries** are historical records. Rewriting
them would falsify the record of what was decided when — the same rule the v4.15 pass applied. A
v4.16 banner at the top and in §16 tells the reader so explicitly.

### 19.8.3 Two more naming leftovers found and fixed

- The **Analytics tab** still read **"Program Performance"** — an R2 rename leftover. It now reads
  **"Project Performance"**, in **both** Laravel and the prototype (`analytics.html`), because the
  prototype carried the same stale label. Prototype harnesses re-run green.
- The **report route** `reports.results-framework` keeps its name deliberately (bookmarks), but §12.2
  now says so, so the next reader does not mistake it for a live results-framework report.

### 19.8.4 Verification

| Check | Result |
|---|---|
| Blueprint integrity | 1 686 → 1 870 lines; no BOM; **zero** mojibake sequences; D14–D20 all present |
| Stale-vocabulary sweep | Every remaining `objective` / `KPI` / `results framework` hit is either a **deliberate** "retired" statement, a **historical** revision entry, or a retained-model field definition — verified section by section |
| Prototype harnesses | `_check.cjs` OK · `_smoke.cjs` all pages clean (after the tab-label change) |
| Full suite | **469 tests / 2102 assertions**, 0 failures |

### 19.8.5 What R7 still owes

Only two things, both non-code: the **`docs/guides/*` + role-guide refresh** (bannered, not
rewritten) and the **production deploy** itself. `docs/TEST-SCRIPT.md` is current, the blueprint is
current, the handoff is current.

---

## 19.9 PHASE R7 (continued) — THE COLLEGE SEALS (LARAVEL ONLY)

The three college cards and the view-2 hero carried a **placeholder** — a `Logo` chip
(`.college-logo-ph`) plus a translucent code crest (`.college-crest`) on a brand-tinted band. The
official LNU college seals were supplied on 2026-09-24 and are now rendered in both places.

### 19.9.1 What changed

- **Assets.** The authored originals stay exactly where they were placed — `public/CAS.png`,
  `public/COE.png`, `public/CME.png` (2560–3000px square, 2.3–3.0 MB each, transparent). New
  **256px derivatives** live at `public/img/colleges/{cas,coe,cme}.png`, cropped to their alpha
  bounding box and downscaled with GD (`imagecopyresampled` + `imagesavealpha`, so the transparency
  survives). The crop normalises the three seals' padding, so they carry the same visual weight.
  **~193 KB for the set instead of ~7.7 MB** at a ~96px display size.
- **Resolution.** `config/smartcemes.php` gains a `college_logos` map (code → path relative to
  `public/`). `College\Index::collegeCards()` exposes it as `'logo'`; `$selected` is derived from
  that payload, so the hero picks the same value up for free. No schema change, nothing hardcoded in
  the view.
- **Markup.** Both views render `<img class="college-logo" alt="… seal">` — on the card inside the
  existing **116px** `.college-media` band, and in the hero at the code badge's `w-14` slot. The
  image sits at `z-index: 1`, **above** the `.college-media::after` sheen, exactly as the crest did.
  `.college-logo-ph` is deleted from `resources/css/app.css`; `.college-logo` is added.
- **Fallback.** `.college-crest` survives and renders for a college with **no** entry in
  `college_logos`, so a college added through "New College" still renders. It is not dead CSS.

This is what `docs/prototype/PATTERNS.md` §7.1 prescribed in advance: *"When real logos arrive, swap
the crest + chip for an `<img>`; keep the 116px height so card heights stay uniform."* The 116px
height is unchanged.

### 19.9.2 Accepted drift — Laravel only (owner decision 2026-09-24)

`docs/prototype/` was **deliberately not mirrored**, so the prototype keeps its placeholder:

- `docs/prototype/pages/colleges.html` still renders `.college-logo-ph` + `.college-crest`.
- `docs/prototype/_check.cjs:296` and `_hubtest.cjs:173–181` still **require** the placeholder to
  exist. Because the prototype is untouched they still pass — this is the same class of accepted
  drift as the `communities.html` card-grid vs the Laravel list view (§16.10).
- `PATTERNS.md` §7.1 is left as written. Its forward-looking note now describes what **Laravel**
  does, so it remains accurate as prototype guidance and does not need rewriting.

Anyone reconciling the two later should delete the two harness assertions and the PATTERNS note
**together** — updating one without the others will fail the harnesses.

### 19.9.3 Verification

| Check | Result |
|---|---|
| Full suite | **470 tests / 2117 assertions**, 0 failures (was 469 / 2102) |
| New guard | `ExtensionHubTest::test_each_college_renders_its_official_seal` — every code maps to its file, the file is on disk, the `<img src>` renders on the card **and** in the hero, and `college-logo-ph` / `college-crest` are absent from view 1 |
| Prototype harnesses | all six green — `_check` · `_smoke` · `_hubtest` · `_facultytest` · `_dashtest` · `_interagencytest` (untouched) |
| `npm run build` | 62 modules; `app-*.css` 99.30 kB (was ≈93.65 kB) |
| Visual | Band + sheen + seal composited at true size for all three colleges — the seal reads on `#003599`, `#F6B800` and `#10b981` |

### 19.9.4 One observation, not acted on

The CAS seal's outer ring is navy, so on the CAS navy band (`#003599`) the ring blends into the
fill and the mark reads as a floating gold disc. It still reads correctly — the gold and green
artwork carries it — so nothing was changed. If it is ever wanted, a soft white halo behind the
seal would equalise the three.

---

### 19.9.5 The Graduate School seal — the fourth and last (2026-09-27)

The Graduate School's official seal was supplied on 2026-09-27 as `public/gs.png` (611×611, 300 KB,
transparent). It is the **last** college to get one, so `.college-crest` no longer renders for any
seeded college.

- **Asset.** Derived to `public/img/colleges/grad.png` with the same GD recipe as the first three —
  cropped to the alpha bounding box, squared with 2% breathing room, downscaled to 256px,
  transparency preserved. **The crop was load-bearing:** `gs.png` filled only **79%** of its frame
  against the other three's **87%**, so a plain downscale would have rendered GRAD visibly smaller
  than its siblings on the card. All four derivatives now fill **98%** of their 256px frame (bbox
  251–252px at a 2–3px margin), so the four carry the same visual weight. 91.8 KB.
- **Resolution.** One line — `'GRAD' => 'img/colleges/grad.png'` added to `config/smartcemes.php`
  `college_logos`. Both views (card **and** hero) pick it up from the existing `'logo'` payload key,
  so **no view edit was needed**.
- **The originals' filenames are not uniform.** CAS/COE/CME are `public/{CODE}.png`; the Graduate
  School's source is `public/gs.png`, not `GRAD.png`. The map is keyed by `colleges.code`, never by
  filename, so the mismatch is harmless — but it is recorded in the config comment so a future
  college follows `{CODE}.png`.
- **The set** is now ~285 KB for four seals (was ~193 KB for three) against ~8.0 MB of authored
  originals — a ~28× reduction.

**The fallback is now unexercised by the seed**, which needed handling rather than accepting:

1. `ExtensionHubTest::test_each_college_renders_its_official_seal` now covers all four codes and
   expects **0** crests — it used to expect exactly 1 (GRAD's). That inversion is the assertion
   proving the seal branch took over completely.
2. The fallback would otherwise have lost its only coverage, so a **new test pins it by dropping an
   entry from the config map**: `test_an_unsealed_college_falls_back_to_the_code_crest`. This matters
   because the college set is **fixed at four with no CRUD** — a fifth college can only arrive
   through a seeder, so a broken fallback would never surface in the UI.

*(Correction to 19.9.1: its rationale for keeping `.college-crest` reads "a college added through
'New College' still renders". There is no New College form — college CRUD was removed on 2026-09-25
(§19.11). The fallback is still right to keep; its real justification is a future **seeded** college,
not a UI form.)*

| Check | Result |
|---|---|
| Full suite | **468 tests / 2145 assertions**, 0 failures (was 467 / 2139 — the 1 new fallback test) |
| HTTP (not just markup) | all four seals fetch **200 / `image/png`, byte-identical to disk** (78,442 · 62,897 · 51,730 · 91,808 bytes); a wrong name 404s, so it is not a catch-all |
| Prototype harnesses | all green — `_check` · `_smoke` · `_hubtest` 42 · `_dashtest` 41 (prototype untouched, as §19.9.2 decided) |
| `pint` | clean on the four changed files |

---

## 19.10 PHASE R7 (continued) — PROJECT → COLLEGE ASSIGNMENTS CORRECTED

The owner spotted that the system showed **Physical Fitness & Sports Development under CME**. The
cause was not the program (a broad program has no college by design — D14); it was the college
assigned to that thrust's only project. Investigating turned up a second wrong row.

### 19.10.1 The root cause: the college followed the LEAD, not the subject

The R2 migration derived each project's college from its **`program_lead_id`** — the college of the
lead's specialization. That produced two rows the institution does not recognise:

| Project | Was | Lead | Why it was wrong |
|---|---|---|---|
| `EXT-2026-005` SENIOR CARE | COE | faculty2 — Reading Education | COE *only because the lead teaches reading*; nothing to do with health |
| `EXT-2026-006` BATANG MATINIK | CME | faculty4 — Entrepreneurship | CME *only because the lead teaches entrepreneurship*; nothing to do with sport |

Verified against **lnu.edu.ph** (`/program-offerings`, `/college-of-education`, 2026-09-25):
LNU has exactly **three colleges** plus a Graduate School; the **Bachelor of Physical Education**
sits in the **College of Education** with its own **BPEd Unit**, and its graduates are described as
*"PE teacher in Basic Education, Dance and Sports Club Moderator, School-Based Sports Program and
Events Moderator/Coordinator."* There is **no health college**, so health/wellness falls to CAS
beside BS Biology and BS Social Work.

### 19.10.2 The rule now

**A project's college follows its SUBJECT DOMAIN** — the college whose degree programmes actually
cover the work. The lead may differ from it; **nothing validates the pairing**, so no guard breaks.
Two rows still differ, and are documented in the migration docblock rather than silently tolerated:

- `EXT-2026-005` SENIOR CARE — CAS, led by faculty2 (Reading Education, COE)
- `EXT-2026-006` BATANG MATINIK — COE, led by faculty4 (Entrepreneurship, CME)

Corrected mapping: LITRAWIYA → COE (remedial reading is teacher education) · HANDA → CAS (DRR /
environmental science) · KABUHIAN → CME (enterprise) · e-LITERACY → CAS (IT) · SENIOR CARE → CAS
(health — no health college) · BATANG MATINIK → COE (BPEd).

### 19.10.3 What changed

| File | Change |
|---|---|
| `database/seeders/Phase2Seeder.php` | the two `hierarchy` rows (lines 181, 189) |
| migration `2026_09_25_000200_add_hierarchy_links…` | the `BACKFILL` map **and** the docblock derivation rule, rewritten to record the correction and the two remaining lead/college mismatches |
| `tests/Feature/ExtensionProjectRenameTest.php` | the expected map |
| `database/seeders/FeedingProgramSeeder.php` | a **false** comment claiming BUSOG had a "COE-affiliated lead (faculty3)" — faculty3 (Nikko Villas) is CAS, Environmental Science |
| `docs/prototype/assets/js/seed-data.js` | LITRAWIYA `college:'CAS'` → `'COE'`, and `PROG-01`'s legacy `college` to match (the prototype's programs page filters on `p.college`) |
| `docs/prototype/_hubtest.cjs` | CAS expectations updated (4 projects, LITRAWIYA now excluded) + a new **`?college=COE`** block |

**LITRAWIYA was the mirror case:** Laravel had it right (COE) and the **prototype** was wrong — its
own lead is COE (John Ryl E. Bautista, "Literacy & Reading") while the row said CAS. Its prototype
code stays `CAS-2026-001` by owner decision; renumbering to `COE-2026-00x` would have collided with
BATANG MATINIK and touched `index.html` ×3 plus `program-narratives.html`.

### 19.10.4 A test that had been proving the seed, not the model

`test_a_broad_program_spans_colleges_and_carries_no_college_fk` asserted that the seeded ICE thrust
was delivered by CAS **and** COE. SENIOR CARE's move made ICE CAS-only, so the assertion broke — and
that is the **correct** outcome, because the test had been proving an accident of the demo data.
It now proves the **model**: it asserts the seeded thrust starts CAS-only, moves one project to COE
inside the test, and asserts the thrust then reports both. The `programs.college_id` absence check
is unchanged.

### 19.10.5 Observation, not acted on

After this correction **every seeded thrust sits under exactly one college**, so "a thrust spans
colleges" is no longer visible anywhere in the demo data and `/programs` shows a single college pill
per program. That is arguably what made the original mapping *look* like a per-college program list.
Restoring a visible span would need a project deliberately placed in a second college under an
existing thrust — a demo-data decision, flagged for the owner, not taken here.

### 19.10.6 Verification

| Check | Result |
|---|---|
| Full suite | **470 tests / 2117 assertions**, 0 failures |
| Dev DB | re-seeded (`migrate:fresh --seed`); SENIOR CARE → CAS and BATANG MATINIK → COE confirmed by query |
| Prototype harnesses | all six green — `_hubtest` now **42** assertions (was 37) |
| Lint | `php -l` clean on all four touched PHP files |

---

## 19.11 PHASE R7 (continued) — THE COLLEGE SET FIXED AT FOUR, AND READ-ONLY

Owner request, 2026-09-25: add a **Graduate School** college, and make the colleges
**non-editable** — no CRUD — because the set is fixed at CAS, COE, CME and the Graduate
School.

### 19.11.1 The fourth college

- `CollegeSeeder` gains **GRAD** ("Graduate School"). Its Extension Coordinator is
  **NULL** until its faculty are seeded; the card renders "— unassigned —". The seeder's
  coordinator lookup is now null-guarded so a missing email cannot silently match.
- `RankingService::COLLEGE_COLORS` gains `'GRAD' => '#7c3aed'`. **Without that entry the
  resolver returns NULL and every surface silently falls back to CAS blue** — the same
  class of failure as reading `database.default` as a driver rather than a connection
  name.
- `/colleges`: the heading read *"Three colleges deliver CESO extension work"* → four,
  and the card grid went `md:grid-cols-2 xl:grid-cols-3` → **`md:grid-cols-2` (2×2)**,
  because four cards under a three-column grid strand a lone orphan on `xl`.
- **No seal is on file yet**, so the `.college-crest` fallback renders. That branch was
  kept deliberately when the seals landed; it is now load-bearing rather than
  speculative. When the icon arrives: `public/img/colleges/grad.png` plus one
  `college_logos` entry, and the seal appears with no code change.

### 19.11.2 No CRUD

Removed: `create()`, `edit()`, `save()`, `closeForm()`, `$showForm`, `$editingId`,
`$form`, `resetForm()`, the `Faculty` import and the `faculties` / `statuses` render
payload — plus the **New College** button, the hero **Edit** button and the whole form
modal.

`CollegePolicy` keeps `view`, `viewAny` and `manage`; `create` / `update` / `delete` are
gone. **`manage` SURVIVES but its meaning changed**: it is the **hub-access** ability
(`Colleges\Index::mount()` authorises it — the `/colleges` page is the Director's single
entry point to the hierarchy), **not** a write permission. Deleting it would have opened
the hub to every role, which is why it stays and is re-documented instead.

**Consequence:** the **Extension Coordinator is now a seeder concern** — correcting one
means editing `CollegeSeeder`, not the UI.

### 19.11.3 Tests — one broke correctly

- **5** college-CRUD tests removed; **2** added:
  `test_the_college_set_is_fixed_and_exposes_no_crud` pins the four codes **and** asserts
  `method_exists()` is false for `create` / `edit` / `save` / `closeForm`, so a re-added
  action fails the suite instead of silently reopening the set; and
  `test_seeded_colleges_carry_their_coordinators`.
- `CollegeProgramFoundationTest` 3 → 4 (three assertions).
- `FreshSeedHierarchyTest`: `assertSame(3, College::count())` → **4**.
- **`ExtensionHubTest` failed, and that was correct.** It asserted `college-crest` never
  appears in view 1 — true while every college had a seal. GRAD has none, so the
  fallback legitimately renders. It now asserts **exactly one** crest.
- Suite: 470 → **467 tests** (5 removed, 2 added), 2118 → **2114 assertions**, 0 failures.

### 19.11.4 Prototype and docs

- Prototype: a fourth college in `seed-data.js` (violet), the heading and card grid
  aligned to Laravel, `_hubtest.cjs`'s five *"exactly 3"* assertions → 4, and the violet
  band added to the brand-tint check. All six harnesses green.
- Docs: blueprint → **v4.17** (§5.1, §6.4a, D14, the banner, a revision entry);
  `AI_HANDOFF` §5 table + the hierarchy box; `README`; and the now-stale *"CAS/COE/CME"*
  comments in `RankingService`, `Faculty\EngagementBoard` and `sc\college-pill`.
- **Not changed — and it turns out it never needed changing:** the prototype's
  `universityTargets` rows still read `colleges:'CAS, COE, CME', activeColleges:3`. Checked
  afterwards: **neither field is read anywhere.** `activeColleges` has no consumer at all,
  and the targets page builds its college filter from `D.colleges` — the array, which *is*
  updated to four. So this is **inert dead data, not a visible staleness**; the earlier
  "flagged" note overstated it. Left alone rather than rewriting a year-keyed approved
  record. The two fields are a candidate for deletion if the prototype is ever tidied.

### 19.11.5 The Graduate School starter set (second pass — DONE)

Seeded so the fourth card is not an empty shell:

- **Two Graduate School faculty accounts** — Dr. Ramon L. Villamor (`faculty5@lnu.com`,
  Research & Extension Management, `LNU-2026-0005`) and Dr. Cristina P. Manalo
  (`faculty6@lnu.com`, Community Development, `LNU-2026-0006`) — added to `UserSeeder`,
  with expertise seeded in `FacultyCollegeSeeder` from the canonical vocabulary
  (Assessment Design · Local Governance / Community Organizing · Peace & Conflict
  Resolution).
- **A new `DEPARTMENT_TO_COLLEGE` entry** — `'graduate school' => 'GRAD'` (plus
  `'graduate studies'` and `'grad'`). **Without it the two new faculty link to NO college
  and the backfill silently leaves them NULL** — the same shape of silent failure as a
  missing `COLLEGE_COLORS` entry.
- **Villamor as GRAD's Extension Coordinator**, so the card names someone.
- **One project — PANDAY (`GRAD-2026-001`)**, a community research and documentation
  capability-building programme, created by a new `GraduateProgramSeeder` (registered in
  `DatabaseSeeder` after `FeedingProgramSeeder`). Its annual targets are set in that
  seeder rather than in `R4TargetsSeeder`, which only fills NULLs.
- **The project ships COMPLETE** — 2 activities, a 15-member beneficiary cohort with
  attendance, and a 3-line budget ledger (₱18,900 of ₱42,000). Two lessons were paid for
  here, both silent:
  - **A project with activities but no ATTENDANCE still reads 0 trainees.** The Trainees
    tile is a DISTINCT BENEFICIARY count from attendance (R-Q1), *not* the `participants`
    fallback — `participants` only feeds the **hours** formula. So the cohort exists to
    make the tile honest, and `participants` is kept EQUAL to each session's attendee
    count, because imported attendance REPLACES the fallback and a mismatch would move
    the computed hours.
  - **Attendance statuses must not mark the same person absent twice.** Marking a *range*
    absent rather than one shifting slot dropped a second attendee *and* moved the hours
    (54 h instead of 56). The absentee's position now shifts per session.
- **It sits under the Information, Communication & Education thrust on purpose.** A
  thrust is university-wide and spans colleges, so a graduate-led project beside the CAS
  ones is the model working as designed — and it **restores the only visible example of
  that span in the demo data**, which §19.10.5 had flagged as lost.

**Ripple, all handled:** blueprint §2.4 now ships **eight** seeded accounts (was six) and
the handoff §13 credential table grew by two rows; `FreshSeedHierarchyTest` **7 → 8**
projects plus a `GRAD-` code count; `FacultyCollegeLinkTest`'s map grew to six;
`CollegeProgramCrudTest` now asserts **every** college has a coordinator *and* that GRAD's
belongs to GRAD.

**One trap the tests caught:** `CollegeProgramCrudTest` seeded only `UserSeeder` +
`CollegeSeeder`, so the GRAD coordinator was found (matched on email) but carried a
**NULL `college_id`** — the "belongs to GRAD" assertion would have been vacuous. The test
now also seeds `FacultyCollegeSeeder`, which is what actually runs the department
backfill.

**Not aligned on purpose:** the prototype's `DATA.faculty` roster keeps its own eight
invented members. It has **never** matched the Laravel roster (Laravel had four faculty,
the prototype eight), so aligning it is not a fix — and each prototype member carries
invented `renderedHours` / `programs` figures that would have to be fabricated.

**Verification:** 467 tests / **2138 assertions**, 0 failures; dev DB re-seeded —
`8 users · 6 faculty`; GRAD card = 1 project / 2 faculty / coordinator Dr. Ramon L. Villamor;
PANDAY = `trainors 2 · trainees 15 · 56.0 h of 120 (46.7%) · ₱18,900 of ₱42,000 (45%)`.

### 19.11.6 Verification

| Check | Result |
|---|---|
| Full suite | **467 tests / 2114 assertions**, 0 failures |
| Prototype harnesses | all six green (`_hubtest` 42) |
| Dev DB | re-seeded — `CAS #003599` · `COE #F6B800` · `CME #10b981` · **`GRAD #7c3aed` (unassigned)** |
| `npm run build` | clean; `md:grid-cols-2` present, `xl:grid-cols-3` retained for the project grid |
| Lint | `php -l` clean on every touched PHP file |

---

## 19.12 PHASE R7 (continued) — ADMIN DASHBOARD: AUDIT LOGS, BULLET ROWS, LEADER BARS, NO EMOJI

Owner request, 2026-09-25. Four changes, staged by owner decision.

### 19.12.1 Stage 1 — the data fix, WITHDRAWN before it started

The agreed first step was "back-fill the training-hours inputs so every project reports real
hours". **That was the wrong diagnosis, and it was withdrawn rather than half-done.**

- `TrainingHoursService` already treats a NULL `no_of_days` as **1.0 day** and a NULL
  `trainors_snapshot` as the assigned-faculty count — so back-filling them only flips
  `measurable` from false to true. The hours barely move.
- The real cause of the lopsided Performance Leaders ranking is **beneficiary enrollment**:
  BUSOG has 30 beneficiaries and PANDAY 15, while LITRAWIYA / HANDA / KABUHIAN / e-LITERACY /
  SENIOR CARE have **2 each**, and BATANG MATINIK has **none and no activities at all**. With 2
  attendees and 1 trainor an activity can only compute `1 × 2 × 1 = 2 h`.
- Fixing it properly means enrolling cohorts and seeding attendance for five projects — the
  PANDAY job times five — and it would move pinned figures in `DefenceWalkthroughTest`,
  `FacultyModuleTest` and the prototype's dashboard data.

**Owner decision: skip the data, proceed to the audit page.** The demo ranking therefore stays
lopsided *by decision*, not by oversight.

### 19.12.2 Stage 2 — the Audit Logs page

- **NEW** `App\Livewire\AuditLogs\Index` + `resources/views/livewire/audit-logs/index.blade.php`.
  Route `audit-logs.index` in the `auth + role:admin` group; nav entry under **Intelligence &
  Reports**, icon **`list`** (`clipboard` was already Rendered Hours).
- **READ-ONLY by design** — and pinned: a test asserts the component exposes no
  `save`/`edit`/`create`/`delete`/`destroy`/`update`, so an added affordance fails the suite
  rather than shipping a mutable audit trail.
- A **plain reverse-chronological list, 25/page** (owner decision), using the shared
  design-system paginator `livewire.partials.pagination` (§14) — not Livewire's Tailwind default.
- The dashboard's **Recent Activity panel, its `$recentActivity` payload and the now-unused
  `ActivityLog` import were removed.** The panel sat in a `lg:grid-cols-3` grid with the hours
  chart (1 col) and the budget chart (2 cols), so removing it leaves the row exactly filled —
  no grid change needed.
- **Event colours are a LOCAL map**, deliberately not `config('smartcemes.status_colors')`: that
  vocabulary is keyed on record STATUS (ongoing/pending/rejected), so a log event name would
  never match and every badge would silently render gray.

### 19.12.3 Stage 3 — the two panels

- **Budget → compact bullet rows.** The grouped bar chart's x-axis could only carry project
  **codes** — a vertical axis has no room for a title. Now one row per project with the full
  TITLE, a utilization bar, a tick at 100%, over-target in red, and the ₱ amounts inline.
  `$budgetChart` became `budgetRows`, and `$budget` gained `title`.
- **Performance Leaders → leaderboard + inline bars.** Each row keeps its rank, college stripe,
  meta and figures, and gains a magnitude bar. **One scale for both lists** (the largest target
  in the project set, or the largest value when nothing has a target) so a row's target marker
  shares the axis with its bar; scaling per row would make every bar full width. Faculty get a
  bar and **no marker** — D-R19 says contribution, never attainment, so a marker would be a
  fabricated percentage.

### 19.12.4 Stage 4 — the emoji sweep

- **The only true emoji in the codebase was the medal array** `['🥇','🥈','🥉']` — and it lived in
  **both** Laravel and the prototype (not drift). Replaced by `$rank` / `rank` returning `#N`.
- `✕` (15 sites), `✓` (4) and `⚠` (6) were typographic glyphs, not emoji, but the owner asked for
  them gone too. All now render `x-sc.icon` SVG icons.
- **Two sites could not be swept mechanically**, and both are documented in place:
  - `calendar.blade.php` — the glyph sat inside a PHP **string literal**, where component markup
    would be echoed escaped. Restructured to `@if`.
  - `assessments/wizard.blade.php` — it sat inside an **Alpine expression**, where a Blade
    component would render as literal text, and the step state is client-side so Blade cannot
    branch on it. Now two `x-show` spans.
- **`alert` was a MISSING icon** — the component silently falls back to `doc`, so `⚠` would have
  rendered as a *document*. The path was added, with a comment warning that an unknown icon name
  fails silently rather than loudly.

### 19.12.5 A test that passed for the WRONG reason

`test_the_audit_log_page_is_admin_only` asserted a guest gets **403**, and passed. `curl` against
the running app returned **302**.

Cause: **`actingAs()` sets the user on the guard for the rest of the test**, so the "guest"
request was still authenticated as `faculty1` and hit `EnsureRole`'s abort. The guest assertion
now runs **first**, before any `actingAs()`. This is the fourth guard this session found to be
proving something other than what it claimed — and it was only caught by comparing the test
against the live server rather than trusting the green suite.

`RedesignUiTest` also failed correctly: it asserted the over-target colour `#ef4444` inline,
which moved into the stylesheet with the bullet rows. It now asserts the **state**
(`bullet-fill is-over`) rather than the hex.

### 19.12.6 Verification

| Check | Result |
|---|---|
| Full suite | **472 tests / 2161 assertions**, 0 failures (467 + 5 new) |
| Prototype harnesses | all six green |
| `npm run build` | clean |
| Glyph sweep | **zero** `✕` / `✓` / `⚠` left in `resources/views` |
| Live | `/login` 200 · `/audit-logs` **302 for a guest** (the correct behaviour) · 181 log rows |

### 19.12.7 The prototype, aligned

`docs/prototype/` was brought level with Laravel in the same pass:

- `dashboard-admin.html` — the activity panel removed; the budget chart replaced by the same
  bullet rows; a magnitude bar (and target marker) added to both leaderboards; the medal emoji
  replaced by `#N`.
- **NEW** `pages/audit-logs.html` — the audit list, with its own demo rows (the seed has no
  activity-log dataset, so the page carries per-page demo data, as other prototype pages do).
- `assets/js/layout.js` — the Audit Logs nav entry under Intelligence & Reports, icon `list`
  (a token that already existed).
- `assets/css/smartcemes.css` — the bullet-row and leader-bar rules, mirrored from `app.css`.

**The harnesses moved with it, and two of them encoded the OLD design:**
- `_dashtest.cjs` asserted six properties of the grouped budget chart. Those became bullet-row
  assertions, the chart count went **2 → 1**, and the `dash-head-title` floor went **7 → 6**
  because the removed panel was one of them. Now **41 assertions** (was 39).
- `_check.cjs` required `#chBudget`, and required the activity panel to EXIST ("it was not part of
  the removal"). Both inverted.

**A trap worth recording:** the first reword of the dashboard's own comment still contained the
literal phrase *"Recent Activity"*, so `_check.cjs` matched the COMMENT and reported the panel as
still present. **When a harness greps a page for a phrase, the page's prose is part of the page.**

All six harnesses green: `_check` · `_smoke` · `_hubtest` 42 · `_facultytest` 124 · `_dashtest` 41
· `_interagencytest` 44.

---

---

## 20. AMENDMENT (v4.19) — BUDGET HAS NO ANNUAL TARGET

**Owner request, 2026-09-26.** *"On the dashboard it shows as Budget Utilized vs Annual Target — note that
the allocated budget is not equal to annual target, there is no annual target for the budget, just the
allocated budget."*

### 20.1 What was actually wrong

The dashboard panel was titled **"Budget Utilized vs Annual Target"**, and `Dashboard.php` read:

```php
'planned' => (float) ($p->annual_target_budget ?? $p->allocated_budget),
```

A **silent fallback**: whenever `annual_target_budget` was NULL the tile printed the allocation while still
calling it a target. Querying the live DB settled it:

| Project | Allocated | Annual target budget | Utilized |
|---|---|---|---|
| CAS-2026-001 | 85,000 | 85,000 | 78,000 |
| EXT-2026-001…006 | 48,000 / 62,500 / 35,000 / 40,000 / 55,000 / 28,000 | **NULL** | … |
| GRAD-2026-001 | 42,000 | 42,000 | 18,900 |

Only **2 of 8** projects carried a target at all — and both were seeded *exactly equal* to their
allocation. So the denominator was **already** the allocation for every project and **no figure changed**;
the label was the only thing wrong. That is what made the fix safe.

### 20.2 The decision

**A project has ONE budget figure — its ALLOCATION.** `allocated_budget` is the basis for utilization
everywhere. `annual_target_budget` is **RETAINED BUT UNREAD**, the same treatment D-R7 gives the 8.6 KPIs:
the column, its cast and its fillable entry stay so historical rows remain inspectable and the migration
stays reversible — but nothing reads it and no new reader may be added.

**Deliberately out of scope:** the **university** annual budget pool
(`university_targets.annual_target_budget`, ₱668,000) is a real commitment the Director sets, and the
**broad program** `annual_target_*` columns are documented planning-only (§6.4b). Both untouched.
Training HOURS keep their project-level annual target and the university pool above them.

### 20.3 What changed

| Layer | Change |
|---|---|
| `ExtensionProject` | `budgetAllocated()` added as the single accessor; **`budgetTarget()` removed**; `isOverAllocated()` reads the allocation |
| `TrainingHoursService::forProject()` | payload key `target_budget` → **`allocated_budget`**; `budget_pct` divides by the allocation. `forYear()` (the university pool) is **untouched** |
| `Dashboard` · `Analytics` · `Programs\Hub` · `Programs\Index` · `Colleges\Index` · `RankingService` · `ReportController` | all read the allocation; hub payload `budgetVsTarget` → **`budgetVsAllocation`** |
| `ProgramAggregates` + **PromptV2** | AI payload `annual_target`/`attainment_pct`/`over_target` → `allocated`/`utilization_pct`/`over_allocated`, and the prompt now states an allocation is **not** a target. **PromptV1 left frozen** |
| Project form (`Programs\Index`) | the **"Target budget (₱)" input is removed** — a settable field nothing reads is a phantom |
| `R4TargetsSeeder` | no longer writes `annual_target_budget` |
| Laravel views | dashboard KPI + bullet rows, analytics ×3 tabs, project hub ×3 partials, reports ×3, colleges, targets — every budget surface reads **"Allocated Budget"** |
| Prototype | the `budgetTarget` demo field is **deleted**; `budgetAllocated` is the basis; roll-ups, labels, `_dashtest.cjs`, `_check.cjs`, `PATTERNS.md`, `smartcemes.css` all moved |
| Tests | `RedesignUiTest` (the pinned title), `TrainingHoursTest` §11 rewritten to the **new** semantics, `ObjectiveStatusTest`, `ReportMetricDictionaryTest`, `ProgramHubTest`, `RankingServiceTest`, `DefenceWalkthroughTest`, `FreshSeedHierarchyTest`, `ExtensionHubTest` |

### 20.4 Two pre-existing defects found in passing

The prototype's `program-detail.html` still carried **`Formula: trainors x trainees x days x 8`** in a
comment and a **`0.5-day sessions count as 4 hrs`** caption — both re-introduced the hourly factor the owner
removed in D-R3. Fixed. Also: the prototype flagged **HANDA** as `overAllocated: true` against a target it
did **not** exceed (62,500 allocation, 64,500 utilized, 65,000 "target"); collapsing the two fields makes
that flag true for the first time.

### 20.5 Verification

- Suite: **472 tests / 2165 assertions, 0 failures.**
- All six prototype harnesses green: `_check` · `_smoke` · `_hubtest` 42 · `_facultytest` 124 ·
  `_dashtest` 41 · `_interagencytest` 44.
- The prototype's dashboard KPI was recomputed from the data, not by hand: allocation sum **₱290,500**
  (was ₱315,000 — the sum of the retired targets), utilization **57%** (was 52%), over-allocation count **1**.
- Blueprint bumped **v4.18 → v4.19** (banner + §5.2 + §5.10 + §6.4c + §12.2 + D16 + revision entry).

### 20.6 The lesson worth keeping

**A `?? fallback` that makes two different concepts render identically is how a wrong label survives a
release.** `annual_target_budget ?? allocated_budget` produced a plausible number in every case, so nothing
ever looked broken. Prefer `?? null` plus a visible "not set" state.

---

## 21. AMENDMENT (v4.20) — THE ADMIN ANALYTICS PAGE IS REMOVED (COMPLETE)

**Owner decision, 2026-09-27.** The six-tab Admin page at `/analytics` (`analytics.index`) is **deleted
outright** — route, Livewire component, six partials, sidebar entry, its five tests, and its prototype
page with them. Blueprint bumped **v4.19 → v4.20**.

### 21.1 The decision

`/analytics` was blueprint **5.13**'s "six dashboards delivered as ONE Admin Analytics page with six
tabs" and **12.1**'s dashboard surface. Removing it is therefore a **scope reduction against the design
contract**, not a cleanup — which is why it is recorded here rather than only in the code.

The case was duplication:

| Tab | Verdict |
|---|---|
| Overview | Duplicated the admin dashboard's KPI row |
| Project Performance | Duplicated the dashboard's "Hours & budget against target" |
| Budget Utilization | Duplicated the dashboard's budget bullet rows |
| Pending Actions | Partly duplicated — see 21.2 |
| Community Reach | **No substitute** — see 21.2 |
| Faculty Contribution | Superseded by the R3 Faculty Engagement board + directory |

Four tabs duplicated the dashboard and the Faculty tab had a better home, so the owner chose to remove
the page rather than maintain a parallel surface.

### 21.2 What was accepted as a LOSS

Two things on the page had no substitute anywhere. The owner accepted losing both, knowingly:

1. **The aggregate Pending Actions list** (`tab-pending.blade.php`). Five sections listing real items
   with deep links: proposals awaiting approval, availability awaiting response, rendered hours awaiting
   approval, projects nearing deadline (≤14 days), and AI analyses awaiting approval. The dashboard keeps
   only **counts** (`pendingApprovals` = proposals + availability + hours, plus a draft-analyses badge).
   Each queue still has its own page (`/proposals`, `/availability`, `/rendered-hours`, `/ai-analysis`),
   so nothing became *unreachable* — the Director simply lost the one screen that listed all five
   together. The ≤14-day deadline list is the least painful part: the 5.14 scheduler still notifies.
2. **The Community Reach chart** (`tab-reach.blade.php`) — distinct beneficiaries served by barangay,
   as a chart and a table. The `community_reach` measure is **retired with it**, consistently with D-R7's
   retirement of the 8.6 dictionary. `/reports/community-impact` is a different cut (per *community*:
   trainees reached, training hours, budget utilization) and is not a replacement.

### 21.3 What changed

| Area | Change |
|---|---|
| Deleted | `app/Livewire/Analytics.php` · `resources/views/livewire/analytics.blade.php` · the six `resources/views/livewire/analytics/partials/tab-*.blade.php` · `tests/Feature/AnalyticsFixesTest.php` (2 tests) · `docs/prototype/pages/analytics.html` |
| `routes/web.php` | the route and its `use` import removed; a comment left in their place recording the decision |
| `config/smartcemes.php` | the `analytics` nav item removed from the admin **Overview** section |
| Tests edited | `Phase4Test.php` (2 tests: admin-only, all-tabs-render) · `ObjectiveStatusTest.php` (1 test: the chart is training-hours-not-objective-status). **`RouteSurfaceTest` needed no edit** — it derives the admin surfaces from the nav config, so the check simply stopped covering the page |
| Prototype | `pages/analytics.html` deleted, plus its four references: `index.html` (gallery), `assets/js/layout.js` (nav), and the "Go to Analytics" stubs in `budget.html` / `activities.html` (repointed to `projects.html`); `PATTERNS.md` page list and its 5.13 rule updated |
| Docs | blueprint banner + 5.2 + 5.4 + 5.13 + 10.4 + 12.1 + a v4.20 revision entry; this section; the handoff |

### 21.4 Two dangling references repaired

Deleting the page left prose pointing at it, so both were fixed rather than left to mislead:

- `app/Livewire/Faculty/Profile.php` — its trend-chart docblock justified itself against "the bar chart
  used on the analytics tab" and "the analytics partials". Now points at the dashboard charts. Worth
  noting: `config('smartcemes.chart_palette')` is now read by **only this file**.
- `app/Services/KpiService::objectivesAtRisk()` — its docblock cited "dashboard action center + analytics
  pending actions" as its consumers. **Both are gone** (the action center by P0m, the pending tab by this
  change), so the method now has **no production caller at all** — only `ObjectiveStatusTest`. It is
  annotated **RETAINED BUT UNREAD** (R-Q2) with an explicit "do not wire up a new caller".

### 21.5 Verification

- Suite: **467 tests / 2139 assertions, 0 failures** (was 472 / 2165 — exactly the 5 tests that covered
  the page). `pint` clean on every changed file.
- All six prototype harnesses green: `_check` · `_smoke` · `_hubtest` 42 · `_facultytest` 124 ·
  `_dashtest` 41 · `_interagencytest` 44.
- The deleted files are archived at `.workbuddy-ai/backups/2026-09-27-analytics-removal/` — the last git
  commit (2026-09-17) predates the whole revision, so `git checkout` could **not** have restored them.

### 21.6 The lesson worth keeping

**Check what a page uniquely OWNS before deleting it — not what it resembles.** The duplication argument
was sound for four tabs, but the case was first made against a *stale* description of the dashboard: the
handoff still described an Action Center and a Community Reach chart on the admin dashboard, both of which
had since been **removed** (P0m). Read literally, the dashboard looked like a superset. It was not.

So the two surfaces with no substitute were only discovered by reading the dashboard's actual render
method and finding the comment that says the panels are "GONE, not merely hidden". Had the change been
made from the documentation alone, two capabilities would have disappeared **silently** — with a green
suite, because nothing tests for the *absence* of a feature.

---

## 22. FACULTY SELF-EDIT WIDENED TO EXPERTISE + ACADEMIC (2026-09-27)

**Owner request: "when logged in as instructor, should be able to edit the profile such as Expertise,
Academic & contact, etc."** Investigating it turned up something better than a feature request — **the
app contradicted its own contract, and had since R3.**

### 22.1 The contradiction

Every layer of the design already said expertise is self-editable:

| Source | Says |
|---|---|
| Blueprint §2.3 | "maintains their OWN contact details **and expertise areas** via 'My Faculty Profile' (R3); assignment fields (position, college, status) stay Admin-controlled" |
| Blueprint §5.1 | "Faculty may edit their OWN contact details **and expertise areas**; assignment fields … stay Admin-only" |
| This document §11.10 (P0h) | "Faculty may correct their own contact details **and expertise** (they own that fact); they may **not** change their position, college, status or employee ID" |
| This document §11.10 (prototype) | the self-edit drawer has "**contact + expertise editable** and Employee ID / Position / College / Status rendered as locked `.locked-field`s" |
| `docs/facultyguide.md` | "Contact details **and expertise areas** are yours to edit; position, college and status are Admin-controlled" |
| `docs/adminguide.md` | "contact details **and expertise areas** are the faculty member's own to edit" |

Against all six, **§14.3 of this document** said the opposite — that expertise was an institutional
record with no writable path. The Laravel implementation followed §14.3, so the shipped app allowed
`phone` and `address` only, and its own docblocks and the modal's helper text repeated the error to
the user's face: *"Employee ID, college, position, status and expertise are institutional records and
can only be changed by the Director."*

So this was **not a policy reversal** — it was the code catching up with a contract it had never
implemented, including the prototype it was ported from. §14.3 carries a correction note.

### 22.2 What changed

| Area | Change |
|---|---|
| Writable set | `specialization` · `department` · `phone` · `address` · `expertise[]` |
| Still Director-only | `employee_id` · `college_id` · `position` · `status`, **plus** the `users` row (`name`, `email`) |
| `FacultyPolicy` | `updateOwnContactDetails()` → **`updateOwnProfile()`**; the name had become a lie |
| `Faculty\Profile` | `editContact()`/`saveContact()` → **`editProfile()`/`saveProfile()`**; new `toggleExpertise()`; the save writes an **activity-log** entry (D8) |
| Entry point | new parameterless **`faculty.me`** route + the faculty nav's **"My Profile" section** |
| `Faculty` model | new `expertiseCategoryMap()` — the area→category map was a *private* method on the Directory, so a second writer could have silently disagreed with it |
| Blade | modal retitled "Edit profile", helper text corrected, the two academic inputs and the expertise multi-select added |

**The lock is a deliberate superset.** The blueprint names four locked fields (employee ID, position,
college, status); we also lock the login account's name and email, because the `users` row is account
provisioning (§5.1, Admin-only). That extension is the owner's choice, not an oversight.

**Why "save immediately" needed no approval queue.** Expertise drives faculty→project matching, so
gating it was considered. The owner chose immediate save **with an activity-log entry** instead — D8
already requires an audit trail, so the Director can see the change without having to approve it. The
log records *changes*, not clicks: an unchanged save writes nothing, and the entry names the fields
that moved (`faculty_self_update`).

### 22.3 A bug caught before it shipped

`x-sc.multi-select` calls `$wire.call(method, key, id)` — **two** arguments. A natural
`toggleExpertise(string $area)` signature compiles and passes review, but PHP silently ignores surplus
arguments, so `$area` would have bound to the literal key `"profile-expertise"` and the component would
have toggled a **non-existent expertise area** on every click — a silent no-op instead of an error.
The signature is `(string $key, string $area)`, matching `Faculty\Directory`, and is documented at both
call sites.

### 22.4 Verification

| Check | Result |
|---|---|
| Full suite | **476 tests / 2177 assertions**, 0 failures (was 468 / 2145 — 1 replaced, 9 added) |
| New guards | the widened writable set · expertise toggle-on **and** toggle-off · the category is filed from the shared config map · institutional keys posted wholesale are **ignored** (with a writable field asserted to have saved, so the test cannot pass vacuously) · the activity log is written on a change and **not** on a no-op · `/my-profile` resolves the *authenticated* member (not the first row) · its role gating · the nav item's label/icon/section |
| `RouteSurfaceTest` | covers `/my-profile` for free — it walks the surfaces derived from the nav config |
| Prototype | **unchanged, and it was already right**: its faculty nav has the "My Profile" section with the `My Faculty Profile` item (`icon:'users'`), and its self-edit drawer already had expertise editable. All six harnesses green. |
| `pint` | clean |

### 22.5 The lesson worth keeping

**A spec that contradicts itself will be implemented from whichever paragraph the coder reads first.**
§11.10 and §14.3 disagreed for four days of phase work, and §14.3 won by being the one quoted in the
implementation notes. The prototype — the visual contract — had it right the whole time, and nobody
diffed against it.

The cheap detector was already in the repo: `docs/facultyguide.md` told faculty that expertise was
theirs to edit. A guide describing behaviour the app does not have is a **testable** claim, not prose.

---

## 23. THE COLLEGE HUB DRILL-DOWN: COLLEGE → PROGRAM → PROJECTS (2026-09-27)

**Owner request:** *"when cas is clicked it should show Programs under CAS, not Projects… i dont want to
see All Projects or All Programs for all colleges for a cleaner look, just College > program > Projects
> Activities."*

### 23.1 What changed

`/colleges` was a **two**-view page: view 1 the college cards, view 2 that college's **projects**. It is
now **three** views, and the Projects level moved down one:

| View | Trigger | Shows |
|---|---|---|
| 1 | `/colleges` | the four college cards |
| 2 | `?college=CAS` | hero, derived KPIs, **the programs CAS delivers**, faculty |
| 3 | `?college=CAS&program=N` | that program's projects (toolbar + cards) and the same faculty block |

The faculty block is rendered ONCE, outside the branch, because faculty belong to the college and not to
a program — so both views 2 and 3 show it without a second copy.

### 23.2 Programs are DERIVED, never assigned

`programs` has **no `college_id`** by design (§3: a CESO thrust spans colleges). "The programs under CAS"
is therefore the set of distinct `program_id` values among CAS's projects — there is nothing to query or
assign, and grouping is the only correct implementation. `Colleges\Index::programRows()` does that and
rolls each program's figures up from the same `TrainingHoursService` the project hub and the targets page
read, so the level cannot disagree with them.

### 23.3 The thin level — measured, then accepted

At the current seed volume the new level is thin, and that was put to the owner before building it:

| College | Projects | Derived programs |
|---|---|---|
| CAS | 4 | 2 → (3 projects, 1 project) |
| COE | 2 | 2 → (1, 1) |
| CME | 1 | 1 → (1 project) |
| GRAD | 1 | 1 → (1 project) |

For three of four colleges the step reveals a single project. **Owner decision: always show the Programs
step anyway**, for a consistent depth that matches the model, rather than skipping it for thin colleges.
The fix for the thinness is seed data, not UI — see 23.5.

### 23.4 The approved college → thrust mapping (the target for seeder work)

Every thrust below has a real LNU programme behind it, so a faculty member can plausibly lead it:

| CESO thrust | Primary college | LNU basis |
|---|---|---|
| Literacy, Numeracy & Language | COE | BEEd, BSEd (English/Filipino/Math), MAEd Reading/Filipino/Math. Secondary: CAS (BA English, BLIS), GRAD |
| Information, Communication & Education | CAS | BS IT, BLIS, BA Communication. Secondary: GRAD (MS IT, MIT), COE |
| Cultural Development | CAS | BM Music Education, BA Communication. Secondary: **CME (BS Tourism — heritage)**, GRAD (PhD Social Science Research) |
| Physical Fitness & Sports Development | COE **only** | BPEd, MAEd Physical Education — no other college has a sport programme |
| Livelihood, Technical & Business Management | CME | Entrepreneurship, BS Tourism, BS Hospitality. Secondary: COE (**BTLEd**), GRAD (Doctor of Management) |
| Environmental Conservation & Disaster Preparedness | CAS | BS Biology (Environmental Biology), BS Social Work (DRR). Secondary: GRAD (Master in Biology) |

**This is a SEEDING target, not a UI rule** — the hub renders whatever the data says. Applying it gives
CAS 3 programs, COE 2–3, CME 2 and GRAD 4–5, which removes the thinness above without adding a thrust:
**Cultural Development is currently empty** (0 projects), and it has two legitimate homes (CAS via Music
Education/Communication, CME via Tourism). Filing one heritage project there takes CME from 1 program to
2 and CAS from 2 to 3.

Two current filings are strained and were **left alone** (both deliberate):
- **SENIOR CARE** under *Information, Communication & Education* — health literacy is defensible as
  "education", and it is retained there as the AI's interagency demo case (D-R8, §3).
- **BUSOG** under the same thrust — the weakest fit of the eight, and §3 separately reclassifies feeding
  as a Tier-2 *referral* rather than a project.

### 23.5 What was unlinked, and the `subs` subtlety

Removed every **browsing** entry point to the cross-college lists: the hub hero's "Programs" button, the
view-1 "View all programs →" link, and the dashboard's "All projects →" (now "Manage programs →",
pointing at the hub). The project hub's back-link was repointed too — it used to return to the flat
`/projects` list labelled "Extension Programs" (both a detour and a misnomer); it now returns to **that
project's college AND program**, the exact view the Director drilled in from.

**`/programs` and `/projects` were NOT deleted, and `subs` was NOT trimmed.** Two reasons:

1. **They host the only create/edit forms.** `/projects` carries the New Extension Project form;
   `/programs` carries the New/Edit Extension Program form. Deleting them means relocating two forms.
2. **`subs` is a HIGHLIGHT list, not a navigation list** — it decides which sidebar entry stays lit and
   creates no link. Removing `projects.index` from it would have left the sidebar with *nothing*
   highlighted on `/projects` — the very page the New project action lands the Director on. An early
   draft did trim it; the nav tests caught the consequence.

**New project lives at the point of need.** View 3 carries a "New project" action linking to
`/projects?college={id}&program={id}&new=1`. `Programs\Index` gained a `#[Url] $new` flag that opens the
form pre-filled from those filters — so there is still **exactly ONE create path and one validation
ruleset**, rather than a second modal duplicated into the hub.

### 23.6 Accepted drift — the prototype is now BEHIND (Laravel only)

The prototype mirrors the hub exactly (`colleges.html`: `#viewColleges` → `#viewCollege`, with
`projHeading` set to `` `${code} extension projects` ``), and `_hubtest.cjs` (42 assertions) +
`_check.cjs:224-307` assert that shape. **Owner decision: do not mirror.** So:

- `colleges.html` keeps the old two-view flow, its "View all programs" link, and its "Open projects" CTA.
- Those harnesses keep passing against the prototype's own markup, which is now **older than Laravel** —
  the reverse of the usual direction, and the first time this project has recorded the prototype as
  trailing rather than leading.

Anyone reconciling later must move the markup, the two harness blocks **and** `PATTERNS.md` together.

### 23.7 Verification

| Check | Result |
|---|---|
| Full suite | **484 tests / 2208 assertions**, 0 failures (was 476 / 2180 — 8 new tests) |
| New guards | the derived programs list (with a **negative control**: a thrust CAS does not deliver must not appear) · view 3 filters to one program's projects, asserted both ways · the `?program=` deep link · a **foreign** program id degrades to view 2 · `clearProgram` and `selectCollege`-clears-program · the New project link carries college+program+`new=1` · that link's other half, via `Livewire::withQueryParams` (the only way to seed `#[Url]` props before `mount()`) |
| `RouteSurfaceTest` | still covers `/programs` and `/projects`, because `subs` was kept — the surface walk derives from it |
| Prototype harnesses | all six green (prototype untouched, per 23.6) |
| `pint` | clean, scoped to the changed files |

### 23.8 Two things found in passing

- **`except()` on an Eloquent collection keys by the MODEL's primary key.** Calling it on a `groupBy()`
  result — whose values are collections, not models — throws
  `BadMethodCallException: Collection::getKey does not exist`. Rewrote the test with plain queries.
- **`docs/TEST-SCRIPT.md` had drifted across several earlier changes**, not just this one: its nav table
  still listed the removed **Analytics** entry (§21), it said "three college cards" and "two-view page",
  and its reproducibility line claimed 3 colleges / 7 projects / 4 faculty / 42 beneficiaries against a
  real 4 / 8 / 6 / 57. All corrected against a live count.

### 23.9 The lesson worth keeping

**"Unlink" and "un-navigate" are different operations, and only one of them is safe to guess at.** The
first draft trimmed `subs` on the assumption that it promoted the pages. It does not — it only drives the
highlight — so the change would have quietly broken the sidebar on the create page. The nav tests caught
it, but the cheaper guard was reading what `subs` is *for* before editing it.

---

## 24. THE COLLEGE HUB, VIEW 2 — VISUAL REDESIGN (2026-09-28)

**Owner request:** *"Redesign the frontend UI for `/colleges?college=CAS` so the college-selected state
looks polished and engaging instead of bland and boring… improve visual hierarchy, layout, spacing,
typography, colour, and interactive elements. Keep the existing functionality and data intact."*

### 24.1 What was actually wrong (measured, not guessed)

The redesign was driven by looking at the rendered page rather than reading the markup. A headless-Chrome
screenshot harness (§24.5) was built first, and the "before" capture showed:

- **The hero was the LEAST prominent thing on a page that is about that college.** A 6px colour stripe, a
  56px seal, and a flat white body — the seal on the view-1 card was more visible than the same college's
  seal once you opened it.
- **No orientation.** Nothing said which level of the hierarchy you were on, and the only way back was an
  unlabelled-looking "All colleges" pill.
- **Four identical KPI tiles.** Same white box, same 10.5px grey uppercase label, no icon, no colour —
  the "bland and boring" complaint in one element.
- **The program grid was 3-up with one or two items**, so a third of the row sat empty directly under the
  page's primary content.

### 24.2 What changed

| Element | Before | After |
|---|---|---|
| Orientation | a lone "All colleges" pill | a **breadcrumb** — `All colleges › College of Arts and Sciences` — which also orients view 3 (`… › CAS › <program>`) |
| Hero | 6px stripe + 56px seal + flat white | a **132px brand band** in the college's own colour with a **112px seal**, a soft halo behind it, and an oversized `CAS` code watermark; then name at 23px, short name · coordinator · description, and a 4-chip stat strip |
| KPI tiles | 4 identical boxes | **tinted icon chips** (lnu / gold / emerald / slate), 26px tabular figures, uppercase caption + sub-caption |
| Program cards | 3-up, `sc-card`, small title | 2-up so the row fills; a new `.prog-card` with the same lift language as `.college-card`/`.proj-card`, 17px title, pillar badge, 4 stat tiles, footer with the CTA |
| Section heads | eyebrow + h3 | a shared `.hub-sec-head` + `.hub-sec-title`, with a count badge (`2 programs`) and a right-hand hint |
| CSS | — | ~110 lines added to `app.css` (`.hub-crumb`, `.college-hero*`, `.hub-kpi*`, `.hub-sec-*`, `.prog-card`, `.hero-chip`) |

**The halo is a fix, not decoration.** Two of the four seals carry a navy outer ring, and on a navy band
(CAS `#003599`) that ring disappears into the fill — the mark reads as a floating disc. §19.9.4 flagged
this on the 96px card seal and left it; at 112px it is much more visible, so `.college-hero-media::before`
now lays a soft white radial behind the seal. It helps all four colleges, not just CAS.

**One design RULE was being broken and is now fixed.** PATTERNS §7: only a *project* card may carry a
`Training hours` label, because no college or program has an hours target. The view-3 program KPI tile
said `Training hours` (added in §23), which put a second such label on the page and read as a target that
does not exist. It now says **`Hours rendered`**, and the program card footer says `hrs rendered`.
Pinned by `ExtensionHubTest::test_only_project_cards_label_training_hours`.

### 24.3 Scope note — view 3 was restyled too

The request named the college-selected view (view 2). Once it was rebuilt, **view 3 looked like a
different, older product** — two `hub-back` pills instead of a breadcrumb, the old plain KPI boxes, the
old section header. Leaving that would have made the redesign feel like a patch, and the two views are
one component and one page. So view 3 received the same treatment (breadcrumb, branded header card, icon
KPI tiles, section head). It is **a presentation change only** — no data, query or route moved.

### 24.4 What deliberately did NOT change

- **No new colour vocabulary.** Every hue used is already in `tailwind.config.js` (lnu / gold / emerald /
  charcoal) plus the existing badge tints. Nothing was invented.
- **No data, query, route or policy change.** All 484 tests pass **unmodified** except the one new guard.
- **The program grid is still 3-up for PROJECTS** (`md:grid-cols-2 xl:grid-cols-3`), so a program with a
  single project still leaves whitespace. Only the *programs* grid was narrowed to 2-up, because a college
  delivers one or two thrusts by construction. Left as-is rather than guessed at.
- **The prototype was not touched.** It trails Laravel on this page already (§23.6), so this widens an
  accepted divergence rather than creating one.

### 24.5 How it was verified — a screenshot harness, because Windows has no browser automation

There was no way to *look* at a redesign: the browser skill refuses on Windows and no Playwright/Puppeteer
is installed. So `.workbuddy-ai/shot.sh` was written to render the REAL page:

1. `php artisan serve` on a scratch port; log in over HTTP with `curl` and a cookie jar (parsing Breeze's
   `_token` out of the login form).
2. Fetch the authenticated page and save it to `public/__shot.html` — inside `public/` so the Vite build
   assets and the Figtree webfonts resolve normally, then delete it.
3. Headless Chrome screenshots it, and the PNG is read back.

Two gotchas worth keeping: **Chrome is a native Windows binary, so `--screenshot` needs a Windows path**
(a Git Bash `/c/...` path silently writes nothing — `cygpath -w` fixes it), and **the snapshot must live
under `public/`** or the fonts and CSS 404 and you end up judging a page rendered in the wrong typeface.

| Check | Result |
|---|---|
| Full suite | **485 tests / 2215 assertions**, 0 failures (was 484 / 2208 — 1 new guard added) |
| Prototype harnesses | all six green (prototype untouched) |
| `npm run build` | 62 modules; `app-*.css` 99.30 kB → **104.34 kB** (the new classes) |
| Visual | captured and reviewed at 1440px for **CAS** (navy band), **COE** (gold band — the light-colour case, where the seal's navy ring actually reads best), view 3, and view 1 |

### 24.6 The lesson worth keeping

**You cannot redesign what you cannot see.** The markup looked reasonable in source; the screenshot showed
a page whose hero was weaker than the card that led to it. Every judgement in 24.1 came from the image, not
from reading Blade — and the single rule violation (the duplicate `Training hours` label) was only obvious
once the rendered page put two of them in front of you.

Building the harness cost about four tool calls and paid for itself on the first capture.

---

## 25. THE HUB OWNS CREATE/EDIT — THREE BUGS FROM §23 (2026-09-28)

**Owner report:** *"I cant add or edit extension programs, also when i click new project, it takes me to a
page that should have been removed, and the creation of new project is not working properly."*

All three were **regressions introduced by §23**, and all three had the same root cause: unlinking the two
pages took their *actions* out of reach along with their listings.

### 25.1 The three bugs, each confirmed before fixing

| # | Symptom | Cause |
|---|---|---|
| 1 | Can't add or edit the 6 broad programs | §23 removed the view-1 "View all programs →" link **and** the hero "Programs" button. `/programs` still worked, but **nothing pointed at it** — `grep -rn "route('programs.index')"` returned only a back-link *on* `/projects`. The capability was never broken; it became unreachable. |
| 2 | "New project" navigates to a page that should be gone | My own §23 design: the button linked to `/projects?college=…&program=…&new=1`. |
| 3 | Project creation does not work | The create modal is `<div x-data="{ open: false }" x-init="$wire.$watch('showForm', v => open = v)">`. **`$watch` fires only on CHANGE.** The deep link set `showForm = true` during `mount()`, so the value never changed, the watcher never ran, `open` stayed `false`, and the modal stayed hidden — a screenshot of `?new=1` showed the page with **no form on it**. This is the failure §14 documents twice before (the import page, the assessment drawer); both were fixed with a server-side `@if`. |

### 25.2 The fix — the forms MOVED into the hub

Both create/edit forms now live on `/colleges`, where the Director is already standing:

| Action | Where | Was |
|---|---|---|
| New program | view 2, programs section header | `/programs` → `create()` |
| Edit program | view 3, next to New project | `/programs` → `edit($id)` |
| New project | view 3, a **modal trigger** | a link to `/projects?new=1` |

**Both modals render server-side (`@if`), with an empty `x-data` for the escape/backdrop handlers.** There
is no Alpine visibility gate in the path at all, so bug 3 cannot recur — and the `?new=1` flag, its
`mount()` and the deep link are **deleted**, not patched.

**MOVE, not duplicate.** `BroadPrograms` and `Programs\Index` lost their `create`/`edit`/`save`/`resetForm`
and their form state, because keeping a second copy would have meant two implementations of the same
validation rules. `/programs` and `/projects` are now **read-only lists** (they keep their roll-ups,
filters and sort). `Programs\Index` went from 232 lines to 124.

### 25.3 What did NOT change

- **No route was deleted.** `/programs` and `/projects` still resolve, still render, and are still walked
  by `RouteSurfaceTest` (they remain in the nav's `subs`, which is a *highlight* list — §23.5).
- **The project edit path is untouched** — it already lived on the project hub (`openProgramEdit`).
- **No policy changed.** The hub was already Admin-only via `CollegePolicy::manage`; the writes
  additionally check `ProgramPolicy::manage`, matching what `BroadPrograms` did.
- **The 6 thrusts stay editable** (owner decision) — including adding a new one, which is a deliberate
  exception to §3's "verbatim CESO thrust" framing.

### 25.4 Verification

| Check | Result |
|---|---|
| Full suite | **485 tests / 2217 assertions**, 0 failures (assertions up 2; test count unchanged — 8 tests re-pointed, 2 replaced by 2) |
| Re-pointed | `CollegeProgramCrudTest` (3 program-CRUD tests → the hub), `ProgramHubTest` (2 project-form tests), `DefenceWalkthroughTest` (step 2), `ExtensionHubTest` (2 replaced: the `?new=1` link test became an action-presence test, and a new test asserts the modal **pre-fills** college + program on open) |
| Guard added | `ExtensionHubTest` asserts `new=1` **no longer appears** on the page — the dead deep link cannot come back |
| **Visual** | both modals screenshotted open and **confirmed visible** with their full field sets (see 25.5) |
| Prototype harnesses | all six green (prototype untouched) |
| `pint` | clean |

### 25.5 Two harness lessons (Windows screenshots)

Screenshotting a *modal* needed two fixes to `.workbuddy-ai/shot.sh`:

1. **Chrome needs `--user-data-dir`.** Without it, a hung run leaves processes holding the default profile
   and every later launch dies with **no output and exit 0** — the file is simply never written. An
   isolated profile directory fixes it and makes runs independent.
2. **Do NOT use `--virtual-time-budget` to wait for a Livewire round-trip** — it hung Chrome outright
   (9 orphaned processes). To photograph a modal, temporarily default its flag to `true`, shoot, and
   revert. The flag was reverted and verified (`grep -c TEMP` → 0).

### 25.6 The lesson worth keeping

**"Unlink the page" silently means "delete the actions on it."** §23 was reviewed as a navigation change —
remove two links, add a Programs level — and it passed 484 tests. But a page's *listing* and its *actions*
are not separable by removing the links to it: the Director lost the ability to create a program, and the
one path I did preserve (New project) was broken by an Alpine watcher that had been latent in that modal
all along, waiting for the first caller that opened it without a transition.

The 484-test suite could not see any of this, because **no test asserted that a create action was
reachable** — only that the components worked when called directly. Reachability is not covered by unit
tests; it took the owner clicking.

---

## 26. PROJECT HUB — ONE BUDGET SURFACE, A DOUGHNUT, AND A SETTABLE HOURS TARGET (2026-09-28)

**Owner request:** *"In the extension project's viewing page, remove the Budget card since a Budget vs
Allocated Budget chart already exists below it. Then change that chart from its current type to a pie
chart, or recommend and implement a more suitable chart type if better for the data. Also add a 'Target
Training Hours' field per project so the view can display rendered/actual training hours versus the target
training hours. Before making any changes, ask me clarifying questions and get my permission first."*

Four clarifying questions were put to the owner before anything was touched.

### 26.1 What the page actually had

`/projects/{project}` is `Programs\Hub`. Its Overview carried **three** budget surfaces: a **Budget stat
tile** (tile 4 of 5), and inside the "Budget vs Allocated Budget" card a **3-bar chart**
(Allocated / Utilized / Remaining) *plus* a **4-row key/value list** (Allocated / Utilized to date /
Remaining / Utilization). The tile and the list both restated what the chart already drew. The owner's read
— "remove the Budget card since the chart already exists below it" — was correct, and the decision was to
leave **the chart as the only budget surface**.

### 26.2 The doughnut — the owner overrode the recommendation, and the caveat is recorded

The owner asked for a pie and offered to accept a better type. A 3-slice pie was recommended against and
chosen anyway, after being told why:

- `Allocated = Utilized + Remaining`, so the three slices do **not** partition one whole — the ring sums to
  **2x the allocation**, and Chart.js's own share-of-ring percentage would read **~50 % at full spend**.
- **Over-allocation is the sharp edge.** HANDA is deliberately over-allocated by ₱2,000 to demo D7, so
  `Remaining` is **negative** — and no doughnut can draw a negative slice.

Implemented with the owner's decision plus two mitigations, recorded as a comment in `hub.blade.php` and
`program-detail.html`:

- The **centre label** and the **legend** carry the true share **of the allocation**, so the figure cannot be
  misread off the arc widths.
- `remaining` is **clamped to 0**; the overage is carried by the red centre ("103 % · over allocation"), the
  red badge and the existing D7 banner. **Verified by screenshot on HANDA**, the over-allocated case.

**Do not "fix" this to two slices without asking the owner** — it was a decision, not an oversight.

### 26.3 "Target Training Hours" was NOT a missing field

The owner asked to *add* it. It already existed: `extension_projects.annual_target_hours`, present in the
**New-project** modal, and **already rendered** on the hub (`actual / target`, "% of annual target",
"Hours remaining"). Two things were genuinely wrong:

| # | Symptom | Cause |
|---|---|---|
| 1 | A project could never acquire a target | The hub's **Edit modal** (`hub-modals.blade.php`) had **no input** for it. The field was creatable but not editable, so a project created before the column existed was stuck at NULL with no UI path out. |
| 2 | Misleading copy | A null target said *"the Director sets this on University Targets"* — but it is a per-**project** field. |

Fixed: the input plus `openProgramEdit` / `saveProgramEdit` wiring and validation. `''` persists as
**NULL, never 0** — 0 would render a 0 %-of-target bar that reads as a broken figure rather than an unset
one (the NULL-over-0 discipline).

### 26.4 The backfill, and its honest consequence

**6 of 8 projects were NULL.** The six legacy projects took their targets **verbatim from the prototype's
`seed-data.js`** (`trainingHoursTarget`, matched by `legacyCode`): LITRAWIYA 640 · HANDA 440 · KABUHIAN 480
· e-LITERACY 420 · SENIOR CARE 300 · BATANG MATINIK 200. **AI_HANDOFF §11 rule 3 forbids inventing data
that contradicts `seed-data.js`**, so smaller "nicer" targets were deliberately not used.

The consequence is stark and is **not** a bug: those six render only **0–4 hours** (§16 G — five have 2
beneficiaries each, BATANG MATINIK none), so their pages now read **0–0.7 % of target**. **The remedy is
enrolling cohorts, not shrinking the denominators** — lowering them would contradict `seed-data.js`. ✅ **DONE — see §30.**

### 26.5 Verification

| Check | Result |
|---|---|
| Full suite | **487 tests / 2233 assertions**, 0 failures (was 485 / 2217 — 2 new tests, 16 new assertions) |
| New coverage | `FreshSeedHierarchyTest` asserts **every** demo project carries a target; `ProgramHubTest` gained the Edit-modal set/clear round-trip plus a negative-value rejection; `RedesignUiTest` asserts the tile and list are gone and the chart is a doughnut |
| Prototype | mirrored in `program-detail.html` — tile, rows, chart type and centre plugin — so the two do not drift |
| Harnesses | all six green |
| `pint` | clean, scoped to the five changed files |
| Visual | HANDA (over-allocated) and BUSOG (healthy, 91.8 %) screenshotted and reviewed |

**One inconsistency was caught by measuring, not eyeballing:** the centre label rounded to a whole percent
(92 %) while the badge showed 91.8 % — one figure rendered two ways on one card. Aligned to one decimal.
The screenshot was first *misread* as "89.8 %"; only `round(utilized/allocated*100, 1)` settled it.

### 26.6 Two lessons worth keeping

**A rendered PNG is not a measurement.** Reading a number off a screenshot produced a wrong value and a
false hypothesis about an inconsistency; one query settled it. Check the data before believing a label —
including a label you drew yourself.

**Preserve each file's line endings.** `hub.blade.php` and `hub-modals.blade.php` are CRLF while `Hub.php`
is LF. A multi-line replacement pass matched **nothing** until the patterns were newline-aware; the
dry-run-before-write assertion caught it as a clean `0 matches` rather than half-applying the edit.

## 27. UNIVERSITY TARGETS — THREE BLOCKS REMOVED, AND A SORT THAT NEVER WORKED (2026-09-28)

**Owner request:** *"on the university targets page, remove the Progress Across the Year chart and fix the
sorting of Project targets. remove also [the D-R5 two-levels paragraph]. remove also the Training Hours
Formula"*

### 27.1 The sort was inert — and the markup said so

The blade rendered `@forelse ($tableRows->sortBy('code') as $row)` while the select beside it offered
"hours attainment up" / "budget utilization down" / "code". So the table was **always in code order** and
the dropdown did nothing. The rows even carried `data-hours` / `data-budget` keys for an Alpine comparator
that **was never written** — the blade's own comment claimed "Sorting is client-side (Alpine) for parity
with the prototype", and the scaffolding was there, but the comparator was not.

Fixed **server-side** (`Targets::$sort` + `wire:model.live`) rather than by writing the missing Alpine
comparator. A client-side order would be lost on every Livewire re-render — saving the annual target
re-renders this component — putting the table back in code order while the select still claimed "hours
up": the exact mismatch being fixed. Keys mirror the prototype's comparator exactly (`targets.html:241-243`):
hours ASCENDING, budget DESCENDING, code A-Z.

### 27.2 Removed, in Laravel AND the prototype

| Block | Why it was safe to remove |
|---|---|
| "Progress Across the Year" chart | `_check.cjs` never asserted it; no test pinned it |
| "Training Hours Formula" card | `_check.cjs` never asserted it; no test pinned it |
| The D-R5 guardrail card ("Targets are set at two levels only...") | `_check.cjs` requires the *pending banner* and the phrase **"drawn down by"** — both live in OTHER blocks (the banner, and the "Training hours rendered" KPI tile), so removing this card keeps the harness green |

The prototype's `renderChart()` and both its call sites went with the chart, so `_smoke.cjs` still passes
(an orphaned `renderChart()` would have thrown on the missing canvas).

### 27.3 A test was GUARDING the removed copy

`TrainingHoursTest::test_the_targets_page_states_the_guardrails()` asserted the D-R5 card
(`assertSee('Broad programs carry')`, `assertSee('D-R5')`) **and** the formula card
(`assertSee('multiplied by 8')`) — i.e. it pinned **exactly** what was asked to be removed. It now pins
their **absence**, so a future pass cannot "restore" them from the old copy or from the prototype.

**The D-R5 disclosure is not lost from the app**: the project hub carries its own, separately worded
statement (`hub-overview.blade.php:79`), which `ObjectiveStatusTest` still asserts. Only this page's copy
went.

### 27.4 Verification

| Check | Result |
|---|---|
| Full suite | **488 tests / 2238 assertions**, 0 failures (net +1 test, +5 assertions) |
| New coverage | `test_the_project_targets_table_honours_its_sort_dropdown` pins the ORDER — two projects whose code order and attainment order are deliberately opposite, so a table stuck in code order cannot pass |
| Prototype | mirrored — cards and chart removed, `renderChart` and both call sites deleted |
| Harnesses | all six green |
| `pint` | clean |
| Visual | `/targets` screenshotted: the page runs header → KPI row → Project targets, and the table reads 0 %, 0 %, 0.4 %, 0.5 %, 0.6 %, 0.7 %, 46.7 %, 89.7 % |

### 27.5 The lesson worth keeping

**The probe settled in one run what guessing got wrong.** The new sort test failed, and the first instinct
was that the sort was broken. A throwaway probe printing the real `hours_pct` values and the real `strpos`
offsets showed the sort was **correct** and the *assertion was inverted* — `assertGreaterThan($a, $b)`
asserts `$b > $a`, and the test wanted the opposite. **A failing test is a claim about the code, not proof
of it**: print the values before rewriting the implementation. (It also caught a missing NOT NULL
`start_time` in the probe's own fixture.)

## 28. RETIRED-VOCABULARY SWEEP, AND A D-R7 LEAK THE GUARDS MISSED (2026-09-28)

Not an owner request — a sweep of the one artifact not yet audited: the **prototype** (29 pages +
`assets/js/`), grepping for vocabulary **retired by v4.19/v4.20** rather than for the current terms.

### 28.1 Five labels that outlived their concepts

| Where | Said | Now |
|---|---|---|
| `targets.html` sort option | "Sort · budget **attainment** ↓" | "budget utilization ↓" |
| `targets.html` column header | "Budget **attainment**" | "Budget utilization" |
| `projects.html` column header | "Budget vs **Target**" | "Budget vs Allocation" |
| `programs/index.blade.php` (Laravel `/projects`) | "Budget vs **target**" | "Budget vs allocation" |
| `reports.html` footer | "SmartCEMES **v4.1** · figures per the **8.6 KPI dictionary**" | "SmartCEMES prototype v4.3 · figures roll up from the project target model" |

**The `/projects` header was wrong in BOTH Laravel and the prototype** — they agreed with each other and
both contradicted `PATTERNS.md` §13, which says outright *"Budget has NO target at any level"*. The sibling
page (`/targets`) had already been corrected to "Budget utilization" for v4.19; `/projects` never was.

**A sixth find, in the design contract itself.** `PATTERNS.md`'s v4.2 rules block still stated
`trainors × trainees × days × 8` — the formula **removed** by the owner — while **§8 of the same file** says
outright *"RULE — the formula has NO hourly factor"* and even notes *"the earlier `× 8` double-counted it"*.
A **self-contradicting contract**, where both halves read as authoritative. Nothing caught it because
`_check.cjs`'s `[FORMULA]` guard scans `seed-data.js`, `layout.js` and the page HTML — **not `PATTERNS.md`**,
which is the one file that must be allowed to name the banned string in order to document its removal. A reader
implementing from the v4.2 block would have rebuilt the exact regression the guard exists to prevent. Corrected,
with that line now pointing at §8.

### 28.2 The leak: a D-R7 violation on the faculty's own page

`rendered-hours/my.blade.php` told faculty their approved hours *"count toward the 8.6 KPI"* — a metric
**D-R7 retired**. And the prototype's copy of that page still carried a hardcoded **"Target 40 hrs /
semester"** attainment bar (`#rhApprovedBar`, `Math.round(approved / 40 * 100)`), which **P0h removed
everywhere else**.

**Why nothing caught it:** every D-R7 guard is scoped to **project-level** surfaces
(`DefenceWalkthroughTest` "the project hub must carry NO VISIBLE 8.6 KPI surface", `ObjectiveStatusTest`
"removed ... from every project-level surface", `RankingServiceTest` "none of the retired 8.6 KPI names may
reappear") — and **this page had no test at all**: `grep -rn "rendered-hours/my" tests/` returned nothing.

Fixed: the caption now reads *"count toward your faculty contribution"* (R3: contribution, never
attainment), the prototype's target and bar are gone, and **the page now has its first test** —
`FacultyModuleTest::test_the_faculty_rendered_hours_page_names_no_retired_kpi_or_target`.

### 28.3 Verification

| Check | Result |
|---|---|
| Full suite | **489 tests / 2244 assertions**, 0 failures (+1 test, +6 assertions) |
| Harnesses | all six green |
| Left alone | `seed-data.js`'s 13 `kpi:'...'` values contradict `PATTERNS.md` §11's "`kpi:null`", but **no page reads `.kpi`** — dead data, flagged not changed |

### 28.4 The lesson worth keeping

**A guard is only as wide as its scope.** D-R7's retirement was enforced on the surfaces where it was first
noticed (the project hub) and nowhere else, so the same retired vocabulary survived on a faculty page for a
whole phase. When a rule is retired the guard must cover **every** surface that names it — and a page with
no test is exactly where it will hide.

## 29. ACCESS MISMATCHES — A LINK A ROLE CAN SEE BUT CANNOT REACH (2026-09-28)

Owner request: *"secretary should be able to manage beneficiaries ... check the current setup of the system,
what can the secretary do?"* — then, after the investigation, four answers: **widen the route** · **route
change only (no new test)** · **sweep every action link** · **leave `/beneficiaries` secretary-only**.

### 29.1 The reported gap

The Secretary could manage beneficiaries end-to-end — enroll, register, import, unenroll, and record
attendance — **verified live**, not merely read. One button was broken: `hub-modals.blade.php` renders
"Download official template" inside the import modal, which is gated on `$canManageBeneficiaries` (admin +
secretary), while `beneficiaries.template` was **`role:admin`**. The Secretary saw it and got a **403**.

**Fixed:** `role:admin` → **`role:admin,secretary`**, matching its two siblings
(`activities.attendance-template`, `activities.evaluation-template`) and `secretaryguide.md`, which states the
Secretary's scope includes importing from this template. Verified live: **admin 200 · secretary 200 ·
faculty 403**.

### 29.2 The sweep, and the method

`php artisan route:list --json` exposes each route's middleware as `EnsureRole:admin,secretary` (**not**
`role:...` — the first parse matched nothing and nearly read as "no gated routes"). Every **nav item** was then
cross-checked against its route's roles — **0 mismatches** — and every `route('...')` in `resources/views/`
against the 29 role-gated routes: **38 links, 35 correctly gated.**

Note why the sidebar is clean: `Navigation::groups()` filters on `Route::has()` only. It checks that a route
**exists**, never that the role may **enter** it — so a role-gated nav item would hide silently in the sidebar
and 403 on click, and nothing tests for it.

### 29.3 Two MORE of the same class — found by the sweep, and fixed

| Where | Link → route | Who saw it | Fix |
|---|---|---|---|
| `hub-overview.blade.php:43` | "University pool →" → `targets.index` (admin-only) | **faculty AND secretary** — the Targets & Allocation card carried no role guard | the link is now inside `@if ($canManage)` |
| `faculty/profile.blade.php:23,25` | breadcrumb "Faculty Management" / "Faculty Directory" → `faculty.index` / `faculty.directory` (both admin-only) | **faculty** — the view is served by `faculty.show` (`auth` only) as well as `faculty.me` | the whole trail is now `@if (auth()->user()->isAdmin())` |

Both **hidden rather than widened**: the target model is Director-only by design, so opening `/targets` to
faculty was not the answer, and the Directory trail exists to orient the Director, who arrives from the
Directory itself.

**Verified live:** faculty on `/projects/4` and secretary on `/projects/2` — no `targets.index` link; the admin
keeps it. Faculty on `/my-profile` — no `faculty.directory` link; the admin on `/faculty/{id}` keeps it.

### 29.4 Verification

Full suite **491 tests / 2894 assertions**, 0 failures (+2 tests, +7 assertions).

- `RedesignUiTest::test_the_hub_hides_the_university_pool_link_from_non_admins` — the Director sees the pool
  link; the secretary and the project's faculty lead do not.
- `FacultyModuleTest::test_the_profile_breadcrumb_is_admin_only` — the Director's trail renders on
  `/faculty/{id}`; a faculty member's own `/my-profile` does not.
- `pint` clean. Per the owner's explicit choice the **template route itself (§29.1) still has no test** — the
  Secretary's access there remains unpinned.

### 29.5 A verification trap worth keeping

The first faculty check read `'University pool'=0` and looked like the link was correctly hidden. It was a **403
error page**: `faculty1` may only view project 4, not project 2 (`ProgramPolicy::view`). **Read the status code
before the body**, or a permission denial looks exactly like correct behaviour.

## 30. DEMO COHORTS FOR THE SIX LEGACY PROJECTS (2026-09-28)

Owner decision, taken from the §26.4 finding: **seed cohorts** rather than revert the backfilled targets.

### 30.1 The problem §26.4 recorded, and the remedy §16 G names

The six legacy projects carried **two beneficiaries each**, so `trainors x trainees x days` rendered **0–4 hours
against targets of 200–640 — 0.4 % to 0.7 % attainment**, which reads as a broken figure rather than a thin one.
§16 G names the remedy: ENROL COHORTS, do not shrink the targets.

### 30.2 Where the numbers come from — seed-data.js, not invention

`docs/prototype/assets/js/seed-data.js` is the authority (§11 rule 3) and ALREADY carried the demo's intended
cohort: `projects[].trainees` gives the enrolled count, and its `activities[]` array gives per-activity
`attendees` / `trainors` / `noOfDays` that satisfy the D-R3 formula EXACTLY (2 x 112 x 0.5 = 112) — which
`_check.cjs`'s `[FORMULA]` block asserts. `LegacyCohortSeeder` mirrors both.

**Note what the contract actually is:** the prototype's per-project `trainingHours` field is NOT what it
displays. `renderedTrainingHours` is DERIVED from the activities, so the per-activity array is the real
contract — and it is internally coherent, unlike the project-level `trainors`/`trainees`/`noOfDays` figures,
which do not multiply out. Read the activities, not the summary.

### 30.3 Result — Laravel now matches the prototype's rendered hours EXACTLY

| Project | enrolled | rendered | target | attainment |
|---|---|---|---|---|
| EXT-2026-001 LITRAWIYA | 186 | **199.0** | 640 | 31.1 % |
| EXT-2026-002 HANDA | 171 | **654.0** | 440 | 148.6 % |
| EXT-2026-003 KABUHIAN | 126 | **63.0** | 480 | 13.1 % |
| EXT-2026-004 e-LITERACY | 74 | **52.0** | 420 | 12.4 % |
| EXT-2026-005 SENIOR CARE | 96 | 0.0 | 300 | 0 % |
| EXT-2026-006 BATANG MATINIK | 0 | 0.0 | 200 | 0 % |

The unevenness is the PROTOTYPE'S OWN STORY (31 / 149 / 13 / 12 / 0 / 0), and §16 G already records that the
demo ranking is lopsided BY DECISION. **Do not "even it out"** — that would contradict the contract.
SENIOR CARE and BATANG MATINIK stay at 0 because the prototype records no attendance for the former and no
activities for the latter; their 0 % is correct, not missing data.

### 30.4 Two traps this hit

- **The obvious idempotency guard defeated the whole purpose.** Skipping an activity that already had an
  attendance row left every project at ~0.5 %, because the earlier demo seeders had written 2–4 rows each. The
  seeder now CLEARS the project's attendance before writing the prototype's counts.
- **A pre-existing caption bug, newly visible.** `Hub`'s hours caption multiplies the project's DISTINCT faculty
  count x DISTINCT trainee count x SUMMED days — which is NOT the formula (`sum(trainors_i x trainees_i x
  days_i)`). It now reads "1 trainors x 186 trainees x 1.5 days" beside "199 / 640 hrs". It was wrong before
  too, just invisibly small. **Reported, not fixed** — it is a wording/product choice, and no test pins it.

### 30.5 Verification

**491 tests / 2894 assertions, 0 failures.** (Assertions rose 643 while the TEST count stayed at 491: several
tests iterate the seeded rows, so a larger cohort adds assertions without adding tests.) All six prototype
harnesses green. `migrate:fresh --seed` reproduces the figures exactly.

**The predicted fallout did NOT happen.** `DefenceWalkthroughTest` and `FacultyModuleTest` build their own
fixtures rather than reading seeded data, so no pinned figure moved — the risk flagged when this was approved
was overstated.

---

## 31. THE SAFE-ZONE PROJECT SET, AND THE PROJECT ARCHIVE (2026-10-05)

Owner decision: replace the demo's eight seeded projects with **five simpler, plainly LNU-plausible
ones**, and give the Director a way to remove a project without DB surgery. Full specification and the
figure table live in `docs/PROJECT-EXPERTISE-PLAN.md` §§10–12.

### 31.1 The archive, and why it needed a second half

`/colleges` view 3 gained an **Archive** action per project card (`ProgramPolicy::delete()`, Director-only,
confirmed with `wire:confirm`). It is a **SOFT delete**: the row survives, `nextCode()`'s `withTrashed()`
floor keeps the code reserved, and D8 logs the deletion.

Archiving the project row alone was **not enough to make it disappear** — three surfaces query `Activity`
GLOBALLY (the Calendar, the Availability picker, and a faculty's own rendered-hours list), so the cascade
archives the activities and the rows that render through them, plus the project's own budget entries
whose `activity_id` is NULL.

**The archive then had to be completed, because it was a one-way door:** `Communities\Index` has had a
`restore()` since v4.5, and a project is a far larger thing to lose to a mis-click. So view 3 gained an
**Archived** chip (archived cards carry a Restore button, no hub link, and no figures — the roll-up walks
live activities, so a 0 would be a false claim), and `ProgramPolicy::restore()` was added as its own
ability. The cascade and its mirror now live in **`App\Services\ProjectArchiveService`**, side by side so
they cannot drift — which is also what makes the seeder able to reuse them.

### 31.2 Two reachability bugs the completion uncovered

Both are the *same* trap one layer apart, and **neither was caught by the archive's own tests** — they
only surfaced by driving the real page. A test that the row is **hidden** is not a test that it is
**findable**:

1. `programRows()` grouped `$college->projects` (live only), so a thrust whose ONLY project was archived
   dropped off the college's program list — taking the last route to that project with it. It became
   unreachable **and un-restorable**.
2. `programBelongsToCollege()` validated `?program=` against live projects only, so a bookmark or refresh
   on a fully-archived thrust bounced silently back to view 2.

### 31.3 The seeder

`SafeZoneProjectSeeder`, registered **LAST** in `DatabaseSeeder`. It **archives** every live project
through `ProjectArchiveService` (never hard-deletes, so each restores complete) and creates five:

| Code | Project | College | Thrust | Hours / target |
|---|---|---|---|---|
| CAS-2026-002 | KULTURA: Local Heritage & Folk Arts Appreciation | CAS | Cultural Development | 84 / 100 |
| COE-2026-001 | NUMERO: Numeracy Enhancement Sessions for Grades 1-3 | COE | Literacy, Numeracy & Language | 90 / 120 |
| CAS-2026-003 | LINIS: Barangay Solid Waste Segregation & Composting | CAS | Environmental Conservation & DRP | 70 / 100 |
| CME-2026-001 | PAGKAON: Basic Food Processing & Product Costing | CME | Livelihood, Technical & Business | 108 / 150 |
| CAS-2026-004 | DIGITAL: Barangay Records & Online Safety Training | CAS | Information, Communication & Education | 48 / 80 |

Each carries **3 activities** with explicit `no_of_days`, a **cohort of Leyte-named beneficiaries**,
attendance for every session, three budget entries and an `annual_target_hours`. **No `ProgramObjective`
rows** (D-R7). 114 beneficiaries, 15 activities, 336 attendance rows.

**The prototype is deliberately NOT mirrored.** `docs/prototype/assets/js/seed-data.js` is untouched, so
Laravel and the prototype now describe **different project sets** — an accepted divergence of the same
class as the college hub (§23.6). Its harnesses stay green because they assert the prototype's OWN data;
**a green harness run does NOT validate this seeder.**

### 31.4 Three real bugs the seeder exposed

1. **`/proposals` returned HTTP 500 as soon as any project was archived.**
   `ActivityProposal::violatesProgramRange()` dereferenced `$this->program` unconditionally, and that
   relation resolves to **null** for an archived project. It is reached by the review drawer for every
   proposal in the list *and* by `Proposals\Index::approve()`. Now guarded, returning `false` — the 8.8
   range rule cannot be evaluated against a project that is no longer live, and `true` would invent a
   violation. **Found by `RouteSurfaceTest`, which walks surfaces by URL; no unit test could see it.**
   ⚠️ This is the risk that had been written off as "guarded by accident". It was not.
2. The walkthrough asserted the project code `CME-2026-001`, which now belongs to the seeded PAGKAON.
   Re-asserted as a pattern plus "not the seeded code" rather than a hardcoded literal.
3. The walkthrough's 8.8 conflict demo booked a faculty member against BUSOG's *Feeding Cycle 2*.
   Archiving takes its activities too, so the demo moved to a LIVE booking (LINIS, also led by Nikko
   Villas, 2026-08-22).

**Test fallout:** 12 tests pinned the old eight-project demo set and were re-pointed at the new reality
(**5 live, 8 archived**). `FreshSeedHierarchyTest` additionally pins the thrust coverage so the gap below
cannot drift silently.

### 31.5 Left open, deliberately

1. **Physical Fitness & Sports Development is now the empty thrust.** Archiving BATANG MATINIK moved the
   gap Cultural Development used to have. None of the five is a sports project, so this follows from the
   agreed set — and it is asserted rather than hidden. A sixth project would close it.
2. **`docs/TEST-SCRIPT.md` now trails** — it documents BUSOG's figures, the `CME-2026-001` code and the
   Feeding-Cycle-2 conflict demo, all of which have moved. The test is current; the prose is not.
   **✅ FIXED 2026-10-07** — re-checked against the seeder and the live DB: the orientation figures now
   use the seeded **PAGKAON**, the expected code is **`CME-2026-002`** (001 belongs to PAGKAON), the
   create form's retired **`Target budget (₱)`** field is gone, the conflict demo moved to
   **LINIS · 2026-08-22**, Appendix A lists the five live projects, and the stale *"the guides are still
   bannered"* claim is corrected. `AI_HANDOFF.md` §16 H carries the closure.
3. **The faculty expertise vocabulary is untouched** — `docs/PROJECT-EXPERTISE-PLAN.md` §6 remains a
   proposal (the owner deferred it).

### 31.6 Faculty performance data, and two dashboard glyphs (2026-10-05)

**Rendered hours are now seeded** (owner request: "assign some faculties on the 5 projects to have data
on the faculty performance").

Assigning faculty to activities was not enough. The faculty board's headline figure is
`SUM(rendered_hours.hours) WHERE status = approved`, and `rendered_hours` rows are produced only by the
activity lifecycle — so every professor read **0.0 hours rendered** however many projects they led. The
seeder now writes one entry per assigned faculty on every COMPLETED activity, with
`hours = end − start` (the 8.9 auto-draft rule, deliberately NOT multiplied by `no_of_days`).

Resulting contribution, read from the database:

| Faculty | Approved hrs | Pending hrs | Projects led | Activities |
|---|---|---|---|---|
| Dr. Ramon L. Villamor | 21 | 0 | 1 | 4 |
| Carlo Sumile | 20 | 0 | 1 | 4 |
| Bianca Oledan | 16 | 0 | 0 | 2 |
| Nikko Villas | 13 | 0 | 1 | 3 |
| Kent Naputo | 12 | 0 | 1 | 3 |
| Dr. Cristina P. Manalo | 12 | **8** | 1 | 4 |

**A trap worth keeping:** the first version marked EVERY co-lead's entries `pending`, which left
**Bianca Oledan — who only ever co-leads — with 0 approved hours**, i.e. the exact bug the change was
meant to fix. Pending is now driven by an explicit `pending_co_lead` flag on ONE project's spec. The
lesson: a status rule applied per-role rather than per-record can starve whoever holds only that role.
`FreshSeedHierarchyTest::test_every_faculty_member_carries_a_contribution_record` pins it.

**Two dashboard glyphs removed.** The gold `sparkles` SVG before **Performance Leaders** and **AI Decision
Support** is gone — it read as a star emoji beside both headings. This is a Laravel-only change; the
prototype's `dashboard-admin.html` still carries it (same accepted-drift class as the rest of this
section).

**Verification: 535 tests / 3168 assertions, 0 failures.** `pint` clean; `npm run build` OK; verified on
the real pages — the faculty board ranks all six with non-zero hours (Villamor 21 → Manalo 12) and the
dashboard renders both headings icon-free.

> **Checked, not assumed:** the dashboard's Training Hours tile reads **400** with the note *"16 % of the
> annual SmartCEMES target · 2,100 hrs still to be rendered"*. That is correct — 84+90+70+108+48 = 400
> across the five live projects, and 400/2,500 = 16 %. An apparent mismatch with the caption turned out to
> be a misreading of a scaled screenshot, not a bug.

## 32. FACULTY LEADERBOARD: THE VALUE CARRIES ITS UNIT (2026-10-07)

Owner request: on the admin Faculty Engagement board, label the leaderboard value rather than printing a
bare number — "21 hrs rendered" / "1 Project" / "1 Project led".

### 32.1 What was wrong

The row rendered a **value + caption pair**, mirrored 1:1 with the prototype's `headline()` → `{v, s}`.
The unit lived ONLY in the caption — and the caption carries the *other* metric — so the **hours tab was
the one tab whose number said nothing about what it counted** (value `21`, caption `2 projects`).

Two hooks for the fix were already in the codebase and **both were dead**: `.eng-val small` (defined in
`resources/css/app.css` AND the prototype's `smartcemes.css`, rendered nowhere) and the `unit` key in
`FacultyContributionService::metricDefinitions()`, read by nothing. The wording slot existed; it had
never been used.

### 32.2 What landed

- `EngagementBoard::headlineUnit()` renders the unit in the pre-existing `.eng-val small` style.
  `unit` is now the plural/default wording and `unit_one` the singular. **Hours keep a FIXED label** —
  "hrs rendered" is the house term used by the hub tile, the caption and the chart tooltip, so it must
  not singularise to "1 hr rendered" here.
- The row sub-line now carries the **activity count**, not the project count: `GRAD · 4 activities`.
  The project count was printed twice on the hours tab (caption + sub-line) and twice on the projects tab
  (value + sub-line). A row now reads hours / projects / activities with no figure repeated.
- The drawer badges pluralise through a shared `countLabel()`; they used to print **"2 lead"** and
  **"1 activities"**.
- **Deliberately NOT changed:** the metric switch still reads "Hours rendered · Projects · Leads" and the
  chart title still says "Training hours rendered". The owner reviewed the repetition and accepted it.
- **The caption is kept on every tab** — it is the only place the second metric appears.

### 32.3 The prototype mirror — and the one field that could not cross

`faculty-management.html`'s `headline()` gained a `u` field and the row renders it in `<small>`. All six
harnesses stay green; `_facultytest.cjs:102` asserts the projects-mode **caption** carries "hrs rendered",
which is precisely why the caption was kept.

⚠️ **The sub-line could NOT be mirrored.** The prototype's `activities[]` carry **no faculty assignment
at all** — only `program` and a `trainors` *count* — so a per-faculty activity count is not derivable
from `seed-data.js`, and §11 rule 3 forbids inventing it. The prototype's sub-line therefore keeps its
project count. **Accepted divergence, same class as §23.6 / §31.3.** Check `seed-data.js` for a field
BEFORE promising to mirror it.

### 32.4 Verification

**538 tests / 3183 assertions, 0 failures** (535/3168 → +3 tests, +15 assertions). `pint` clean on the
three changed PHP files; `npm run build` OK (CSS 104.86 kB); all six prototype harnesses green; the page
was screenshotted through `.workbuddy-ai/shot.sh` and the units, the activity sub-line and the
`On leave` branch all read correctly. The new render test was **mutation-tested** — removing the
`<small>` wrapper reproduces the failure. `assertSeeHtml` was used deliberately: "hrs rendered" also
appears in the chart bootstrap script, so a plain `assertSee` would have passed with the unit removed
(a test passing for the wrong reason).

---

## 33. THE AI ANALYSIS PAGE: A QUEUE AND A REVIEW (2026-10-07)

Owner decision, from the plan in `docs/AI-ANALYSIS-REDESIGN-PLAN.md`. The page did three jobs in one
scroll — pick a summary and generate / review one analysis / browse history — and could only ever show
**one** reviewable analysis.

### 33.1 What was actually broken

`$drafts = completed + approval_status = draft`, and `$hero = $drafts->first()`. So the review surface
could only render a draft, and **an APPROVED analysis had no reading surface at all** — the history row
said "citable in reports" with no link. Neither did a discarded one.

Measured before the change: **11 analyses** (2 completed — 1 draft, 1 discarded — and **9 failed**),
**30 summaries** of which only **4** had ever been analysed, and **multiple generations** on one summary
(summary 1 → 4, 2 → 2, 8 → 2, 16 → 3) with nothing distinguishing them.

### 33.2 What landed

- **`/ai-analysis` is the QUEUE.** Community grouping, derived filter chips
  (All · Awaiting review · Approved · Failed · Discarded · Awaiting analysis), `gen N of M` lineage with
  derived `current`/`superseded`, a readable failure reason, per-row **Generate** and inline **Retry**.
- **`/ai-analysis/{analysis}` is the REVIEW**, for **any** state. Full width, 2-up panels, a
  state-dependent sticky action bar, a provenance footer, and a generation-history block.
- **`awaiting_analysis` is a DERIVED state** — a validated summary with no analysis yet. 26 of the 30 live
  summaries are in it; before, they were 26 options in one dropdown.
- **The panels collapse** into the pre-existing `sc-acc` accordion (no new CSS, no JS, no Alpine), with
  **High-priority interventions left `open`** so triage is never hidden. Referral rows lead with the
  **agency chip** and clamp `need` to one line — `need` averages 55 characters and reads as a sentence.
- **Regenerate** creates a NEW generation and leaves the previous one intact (`isCurrent()` is derived, so
  no `superseded_by` column is needed). **Discard is confirmed** via a server-rendered `@if`, never an
  Alpine visibility bridge.
- `AssessmentAnalysisService::retry()` was **extracted** so the queue and the review cannot drift.

### 33.3 The bug the rendered page exposed

The first cut of `isCurrent()` returned the newest non-discarded generation **regardless of status**, so a
**FAILED** attempt rendered as `current` — *"gen 3 of 4 · current · failed"* — pointing the Director at the
one analysis with no content. `isCurrent()` now requires `status = completed`, and `isSuperseded()`
excludes discarded. Verified on live data: **0 failed rows marked current**. Pinned by
`AiAnalysisQueueTest::test_a_failed_newer_generation_does_not_demote_a_completed_draft`.
**Lesson: for a derived "authoritative" marker, newest is not the same as newest USABLE.**

### 33.4 Provenance

`assessment_analyses.needs_assessment_id` is **vestigial** — `generate()` picks the lowest-id respondent
row, and all four generations of summary 1 cite row #1 out of thirteen. The analysis's real parent is the
**summary**, so the review footer cites the summary plus counts (`13 validated responses · 2 contributors`)
and **never** renders "submitted by". Retiring the FK is left as its own decision.

### 33.5 Verification

**551 tests / 3249 assertions, 0 failures** (538 / 3183 → **+13 tests, +66 assertions**, all from the new
`AiAnalysisQueueTest`). `pint` clean on the 8 changed PHP files; `npm run build` OK; both surfaces
screenshotted on the real page. The **Community response data** block (the D3 evidence surface) was moved
**verbatim** and is asserted down to a breakdown row by
`test_the_review_surface_keeps_the_community_response_data_breakdown`.

### 33.6 The prototype is NOT mirrored (owner decision)

`docs/prototype/pages/ai-analysis.html` still renders the old single-page shape. **Accepted Laravel-only
divergence**, the same class as §23.6 / §31.3 / §32.3 — the six harnesses were **not** re-run, because the
prototype was not touched. Note the prototype already contains most of this design (a sticky action bar, a
Regenerate button, a discard confirmation, `view →` links); the queue/review split itself is Laravel-only.

### 33.7 The queue's presentation pass (owner review, same day)

Four owner-requested changes to the queue only:

1. **The page heading and the "Director-only · aggregated inputs only (D3)" badge are REMOVED.** The
   sidebar and the topbar already name the page, and the D3 boundary is stated in the hero panel and the
   legend — the same call the Faculty Management board made in P0i/P0j. The **filter chips and the
   pipeline legend stay**.
2. **One container card.** Each community used to be its own card, so 21 communities meant 21 floating
   cards. They are now group header ROWS inside a single `sc-card`, divided by `divide-y`.
3. **Pagination, by COMMUNITY GROUP rather than by row.** A community's generations must not straddle a
   page break — you would otherwise read "gen 2 of 3" on one page and "gen 3 of 3" on the next with
   nothing tying them together. `GROUPS_PER_PAGE = 8`, so 21 communities ≈ 3 pages. ⚠️
   **`Collection::paginate()` does not exist in this Laravel**, so the page slice is wrapped in a manual
   `LengthAwarePaginator` — and the shared `livewire.partials.pagination` renders "Showing 1–8 of 21",
   which counts **communities**, hence the "21 communities · 37 rows" label beside the chips.
   `filterBy()` calls `resetPage()` (the `Faculty\Directory` pattern), so a filter change cannot strand
   you on a page that no longer exists.
4. **Generate / Retry are revealed on hover**, as requested, through a `.hover-reveal` utility in
   `app.css` gated on **`@media (hover: hover) and (min-width: 1024px)`** — **not** on a bare breakpoint.
   A plain `lg:opacity-0` also hides the button on a **touch** device at ≥1024px, where there is no hover
   to bring it back: an invisible but tappable control. With the gate, any pointer that cannot hover keeps
   the action visible, and `:focus-within` covers keyboard users. Opening an analysis is the PRIMARY
   action, so `Review →` / `View →` stay always visible — only the secondary action hides.
   ⚠️ **A new Tailwind class needs `npm run build`** — the reveal silently did nothing until the build ran.
   (Hand-written CSS in `app.css` is also immune to Tailwind's content scan, unlike a utility class.)

Verified: **554 tests / 3265 assertions, 0 failures** (+3 tests, +16 assertions from this pass).

### 33.8 Search on the queue (owner request, same day)

The queue groups by community, so with 21 communities the only way to reach one barangay was to scan or
paginate. Added a **search over the community name**:

- `#[Url(as: 'q', except: '')] public string $search` — the **house URL alias** (`Faculty\Directory`,
  `Communities\Index` both use `q`), so the query string stays consistent across the app, and the term is
  shareable/bookmarkable.
- Bound with `wire:model.live.debounce.300ms` (house pattern). **Matching is partial and
  case-insensitive**, so `san jo` finds *Brgy. San Jose*.
- ⚠️ **The search is applied BEFORE the chip counts**, so the chips describe what is on screen. Counting
  the unfiltered queue instead would print "Awaiting analysis 26" above a single row. It is a **Collection
  filter, not SQL**, so no LIKE escaping is needed (contrast `Faculty\Directory`, which escapes `%`/`_`).
- `updatedSearch()` calls `resetPage()` — narrowing a search can leave you past the last page, which
  renders an empty list that looks like a failed search. `clearSearch()` also resets.
- The empty state distinguishes **"your search found nothing"** from **"this state is empty"** — they need
  different fixes — and offers Clear search / Show all states.
- **Not searched:** the period. Typing `Q2` will not match; the axis is the community, which is what the
  grouping is by.

Verified: **560 tests / 3285 assertions, 0 failures** (+6 tests, +20 assertions).

### 33.9 A per-community history page, and a scoped delete (owner request)

Owner: *"make the community or school clickable, so when clicked i can see the past generates of that
certain community or school, inside that page, make also a feature to delete past requests."*

**Why the page was worth adding.** The queue already lists a community's generations inline, but it
**paginates by group** — so a community's rows can sit on any page, and there was no way to LINK to one
community. `/ai-analysis/community/{community}` is that link. It is keyed to the **community**, not a
period: a community has one summary per quarter/year, so the page lists **periods**, each with its own
generation lineage. Two path segments, so it cannot collide with the one-segment `{analysis}` route.

It is also where housekeeping lives: **Generate** on a period with no analysis, **Retry** on a failed one,
**Delete** per row, and a bulk **Clear N failed** (9 of the 12 live generations are failed noise).

**The delete is SCOPED, and the scope is the design decision.** A hard delete here is permanent
(`assessment_analyses` has no `deleted_at`), so:

- **Never deletable: an APPROVED analysis.** It is citable in reports and its content is mirrored onto the
  summary (`ai_analysis*`), so deleting it would orphan a citation.
- **Never deletable: the CURRENT draft.** It is the queue's only actionable row — `Discard` is the way to
  retire it, then delete. The row reads *"live draft — locked"* with that reason on hover.
- **Deletable: everything else** — failed, pending, discarded, superseded. `isDeletable()` on the model.

Three facts made a hard delete acceptable, all verified rather than assumed:

1. **Nothing carries a foreign key to `assessment_analyses`** (checked
   `information_schema.COLUMNS` for any `%analysis%` column) — so no referential integrity breaks.
2. **`AuditLogs\Index` already renders a NULL subject by design** — its own docblock says *"A deleted
   subject resolves to NULL, which the [view handles]"* — so the audit trail keeps the stored description
   text and degrades gracefully instead of breaking.
3. **The deletion is logged** — the model auto-logs it (`LogsActivity`) and the component adds a semantic
   event naming the community. Two rows per action is the existing house behaviour: `approve()` and
   `discard()` do exactly the same.

⚠️ **The id arrives from the browser, so it is scoped to the community** (`analysisInScope()` →
`findOrFail`). Without that, a crafted call could delete another community's analysis.

**Navigation now forms a loop: queue ↔ community ↔ review.** The queue's group header is a link (with a
chevron affordance), and the review page gained a **community breadcrumb** next to "Analysis queue" —
before this, a generation was a dead end: you could not reach the community's other periods from it.

Verified: **571 tests / 3312 assertions, 0 failures** (+11 tests, +27 assertions).

### 33.10 A self-inflicted N+1 in the lineage badges, found by measuring

The lineage helpers (`generationCount`, `generationIndex`, `isCurrent`, `isSuperseded`) each call
`siblings()`, and a rendered row reads up to four of them. Measured on the real pages with `DB::listen`
against **six generations on one summary**:

| Page | Before | After |
|---|---|---|
| `/ai-analysis` (the queue) | **36 queries** | **13** |
| `/ai-analysis/community/{id}` | **41 queries** | **12** |
| `/ai-analysis/{analysis}` (the review) | — | **13** |

⚠️ **The review page was measured last, and only because I checked my own work for a gap** — I had
measured two of the three surfaces I built. It was fine (13), but it had never been verified.

So ~4 queries per row were lineage, i.e. **24 of the queue's 36** — two thirds of the page's queries were
self-inflicted. `siblings()` is now **memoised per instance**, with a `refresh()` override that drops the
cache so a state change (approve / discard / regenerate / retry) is never read back through a stale
generation list.

⚠️ **Note the method of finding it.** Nothing failed; the suite was green and both pages looked right. It
took *counting queries* to see it. This is the same class of defect as `AuditLogs\Index`'s eager-load
comment — and worth re-measuring whenever a per-row badge is added.

Pinned by `AiAnalysisQueueTest::test_the_lineage_badges_do_not_fire_a_query_each`, which now measures
**all three surfaces** (not just the queue) with a deliberately loose bound (25) so it catches a
regression without pinning an exact count that framework drift could break.

Verified: **572 tests / 3318 assertions, 0 failures**.

### 33.11 The GUIDES described the pre-§33 page (fixed)

The split invalidated three documents that describe the AI analysis **workflow** — the same class of debt
§31 left in `TEST-SCRIPT.md`. All three told the reader to *"pick a community summary in the picker and
click Generate"*, and **that picker no longer exists**:

- `docs/adminguide.md` §12 — the flow steps
- `docs/features.md` §3.11(a) — the demo script
- `docs/guides/10-ai-analysis-narratives.md` Part A — the worked walkthrough

Each now describes the **queue** (grouped by community, search, chips), the **per-community history page**,
generation from an `awaiting analysis` **row**, the failure state as a **row** with a readable reason, the
**scoped delete**, the collapsible panels, and **Regenerate**. The **Community response data** accordion is
unchanged and remains documented in all three.

⚠️ **A structural change invalidates PROSE, not just counts.** When §33 split the page I updated the four
authoritative documents and the plan, and verified the tests — but the *guides* describe the same workflow
and were never on my checklist. **Grep the doc set for the removed UI, not only for the changed numbers.**

---

## 34. PROJECT NARRATIVES — SEARCH, FILTERS, PAGINATION, AND SIX AI-SURFACE DEFECTS (2026-10-07)

`/program-narratives` was **a reading surface pretending to be an index**. Its own heading promised
"status at a glance" while offering no search, no filter and no pagination, and rendering every
project's full body — so scanning five projects was already a long scroll. It worked only because the
live set is five projects. It is now the sibling of the AI-analysis **queue** (§33).

### 34.1 The mechanism is the QUEUE's, the SHAPE is not

Reused from `AiAnalysis` verbatim, so the two AI surfaces cannot drift: `use WithPagination`;
`#[Url(as: 'q', except: '')] $search`; `#[Url(as: 'state', except: '')] $state` + `stateFilters()` +
`mount()` validation + `filterBy()`→`resetPage()`; `updatedSearch()`→`resetPage()`; `clearSearch()`;
counts via `countBy('state')` taken **after the search, before the state filter**; the shared pager
(`livewire.partials.pagination`); and the two distinct empties.

⚠️ **`GROUPS_PER_PAGE` was NOT copied, and must not be.** The queue groups by community, so it
paginates *groups* to stop a lineage straddling a page break. Here the unit **is** the project — one row
per project — so plain pagination over projects is correct and group-slicing would be a cargo cult.

The card shape is also deliberately different: the queue is a **triage** surface (compact rows), this is
the **reading** surface, so the card keeps its summary / risks / next-actions body. What changed is that
the body now **collapses** (Alpine, keyed by `wire:key` so Livewire's morphing keeps the open state),
while the header and a new **metric strip** (trainors · trainees · hours vs target · activities) always
show. Only the **title area** toggles, so the Generate button beside it cannot double as a collapse
control.

**"Not generated" and "Failed" do NOT collapse.** There is nothing to read, and the failure reason is
the one thing that must be visible without a click.

### 34.1a `pending` gets a chip, because the real data says so

The queue **omits** `pending` from its chips, reasoning that generation is synchronous (v4.4) so a
pending row resolves inside the request that created it. The page was built to match — and then
**querying the dev database disproved the premise**: project 9 carried **two `pending` rows with
`generated_at = NULL`**, left by the 2026-10-06 quota failures.

The reasoning holds only while the request **completes**. If the PHP process dies mid-generation — a
timeout against `GeminiClient`'s 120s wall-clock budget, a fatal, an aborted Livewire request — the row
is left `pending` and **nothing will ever move it**.

⚠️ **Whether such a row is the project's LATEST narrative is chance, and in the dev data it was not.**
Both interrupted rows sat *behind* a later `failed` attempt, so project 9 read as **failed** and the two
rows appeared in its **version history** — not as cards. Had either been latest, the old page and the
first cut of this one would have rendered it as a skeleton reading *"Generating…"* forever, with no chip
to find it and no action to escape it: **the exact silent error the first-class failure state exists to
prevent.** The chip is justified by the mechanism being reachable, not by which row happened to be newest
on the day it was measured.

So this page now has **seven** chips (`All · Not generated · Generating · Failed · Needs attention ·
At risk · On track`) and the pending card carries **Start a new generation**. The general rule it
establishes: ⚠️ **a state the page RENDERS but no chip can REACH is a filter that lies by omission.**
Adding a render branch means checking the chip list.

⚠️ **The AI-analysis queue had the same latent gap, and it is fixed in this same pass.** `queueState()`
maps any non-completed, non-failed status to `pending`, so an interrupted analysis generation lands in
exactly the same dead end — and there it was **worse**: the queue had no `pending` chip, the row carried
no link in, and `retry()` refuses anything but `failed` (**422**, *"Only a failed generation can be
retried"*). The queue now has the chip and the pending row offers **Start a new generation**, which
leaves the stuck attempt as history rather than mutating it — the retry guard is deliberately **not**
relaxed, because re-running a row in place would erase the record of the attempt that hung.
`AiAnalysisQueueTest::test_a_stuck_pending_row_is_findable_and_recoverable` pins it.

### 34.2 The structural win: paginate BEFORE enriching

`TrainingHoursService::forProject()` runs ~4 queries per project (activities + `withCount`, the distinct
`activity_faculty` join, `traineesReached()`, budget), and the old `render()` called it for **every**
project inside `->map()`. The page was an **N+1 by design**.

The pipeline is now `search → count → filter → sort → PAGINATE → enrich only the visible page`, so the
rollup cost is bounded by the page instead of the set. Measured: a page of 8 projects fires **38
queries**, ~32 of them the rollup.

⚠️ **The corollary is a constraint, not a preference:** filter and sort may read only CHEAP fields (the
narrative state, the title). Sorting on hours attainment would force a rollup for every project and undo
the win. The default sort is **attention-first** — the same order the chips read in — so the list and the
controls tell one story.

### 34.3 Defect A — the success toast lied (both entry points)

`ProgramNarratives::generate()` and `Programs\Hub::generateNarrative()` both dispatched
`sc-toast … type: 'success'` **unconditionally**. `ProgramNarrativeService::generateFor()` catches its own
`Throwable` and persists the first-class `failed` state, so it **never throws** — the Director got a green
"Narrative generated" toast above a red "Narrative unavailable" card. The row was honest; the toast was not.

Both now read the returned narrative's status and toast `type: 'error'` with `error_message` otherwise —
the same rule `AiAnalysis::generate()` already followed. ⚠️ **A newer sibling surface had this right and
the older one did not**; nothing failed, because **no test asserted the narrative toast type** (the only
`sc-toast` assertions in the suite are on project archive/restore).

### 34.4 Defect B — the API key would have vanished in production

`GeminiClient` read `config('smartcemes.ai.key') ?: env('GEMINI_API_KEY')`, but **`config/smartcemes.php`
defined no `key` entry** — so the `config()` half was always null and the real read was a **runtime
`env()` call**. That works in development and breaks on the deployed server: `php artisan config:cache`
(§15.4 item 10, which the deploy checklist *mandates*) stops Laravel loading `.env`, so `env()` returns
null at request time and **every AI surface** falls into its "unavailable" state with *"No API key
configured"* — while `GEMINI_MODEL` and `GEMINI_ENDPOINT`, which ARE read through config, keep working.

The fix is two lines: `'key' => env('GEMINI_API_KEY')` in the `ai` block, and the client now reads
**config only** — the `env()` fallback is gone, because it is what made the failure invisible locally.

⚠️ **Two things hid it.** `bootstrap/cache/config.php` does not exist in development, so `env()` still
resolved and the 2026-10-07 smoke test passed. And the suite masked it: `Phase5AiTest` and
`GeminiRetryTest` both set `config(['smartcemes.ai.key' => 'test-key'])` by hand, so the real resolution
path was never exercised. Pinned now by
`GeminiRetryTest::test_the_api_key_is_read_from_config_not_from_a_runtime_env_call`, which puts a key in
the **environment** and none in config — the shape of a cached production config — and asserts the client
refuses to start rather than silently using it.

### 34.5 Also in this pass

- **`$detailId` / `toggleDetail()` deleted** from the component: dead code, never referenced by the view
  (the version-history accordion is a native `<details class="sc-acc">`).
- **Vocabulary:** the hero read "Executive **Program** Narratives" / "Program Status" while the sidebar
  and the prototype hero both say "**Project** Narratives" — and "program" means the BROAD level
  everywhere else (§28 swept exactly this). The user-visible strings now say *project*. The route name,
  the component class and the `ProgramNarrative` model keep their legacy names, consistent with the
  `reports.results-framework` precedent.
- **The hero counts are labelled PORTFOLIO** and each badge is a shortcut to its own chip. The hero is
  unfiltered (the whole set); the chips are view-scoped. ⚠️ One screen must never show two numbers that
  look like the same number — so the label is part of the design, not decoration.
- The `⟳` glyph was **not** changed: `ai-analysis.blade.php:150` uses `⟳ Retry` too. It is a house-wide
  inconsistency across both AI surfaces, not a narratives defect — fixing one page would have made it
  worse. (This corrects an over-claim in the audit that prompted this pass.)

### 34.5a A third defect, found by writing the test for §34.1: every AI surface read "prompt vv2"

`config('smartcemes.ai.prompt_version')` is **`'v2'`** — the canonical label *including* its `v`, pinned by
`R6GuardrailTest::test_prompt_version_is_tagged_v2`. **Five render sites prepended another `v`**, so the
Director has been reading **"prompt vv2"** (and `vv2` on the review's Prompt tile) since R6:

| Site | Was | Now |
|---|---|---|
| `program-narratives.blade.php` (pending state) | `prompt v{{ … }}` | `prompt {{ … }}` |
| `program-narratives.blade.php` (provenance footer) | `prompt v{{ … }}` | `prompt {{ … }}` |
| `programs/partials/hub-modals.blade.php` | `prompt v{{ … }}` | `prompt {{ … }}` |
| `programs/partials/hub-overview.blade.php` | `prompt v{{ … }}` | `prompt {{ … }}` |
| `ai-analysis-review.blade.php` (Prompt tile) | `v{{ … }}` | `{{ … }}` |

⚠️ **It was found only because a new test asserted the rendered string.** The stored value was asserted
(R6), the *rendered* value never was — so a doubled prefix in a template could not fail anything. **A
provenance label is part of the audit surface; assert what the page prints, not only what the row holds.**
`ProgramNarrativesTest::test_a_completed_narrative_renders_its_body_and_provenance` now pins `prompt v2`
and asserts `vv2` is absent.

### 34.5b A version that never finished was labelled a narrative

The version-history timeline branched on **`failed` alone**:

```php
@php($failed = $v->status === 'failed')
@if ($failed) Generation attempt — failed
@else Narrative v{{ … }} · {{ $v->health_label }} …
```

A **`pending`** version is neither failed nor completed, so it fell through to the completed branch and the
Director read **"Narrative vN · Generated \<date\> · \<model\>"** — a narrative that was never produced. This
is the same defect class as §34.1a, one level down: a state the page RENDERS as something it is not.

⚠️ **It was live in the data.** Project KULTURA's two interrupted `pending` versions (§34.1a) were being
presented as narratives, with the completed sibling sitting beside them.

Now three states, with distinct labels and tones: **completed** (`Narrative vN` + health label, blue dot) ·
**failed** (`Generation attempt — failed` + the reason, red) · **never completed** (`Generation attempt —
never completed` + *"Started \<date\> · interrupted before it finished"*, gold). Pinned by
`ProgramNarrativesTest::test_a_version_that_never_completed_is_not_shown_as_a_narrative`.

⚠️ **Note the shape of the miss.** The timeline was written for two states and the model has three. When a
status column gains a value, every `if/else` that reads it becomes a silent default — the *else* branch is
where the lie lives.

### 34.5c The project hub's D3 audit view read a payload shape that no longer exists

The full-narrative modal on the project hub (`hub-modals.blade.php`) carries a **"Data the AI reviewed"**
accordion — the D3 evidence surface where the Director sees the aggregate snapshot sent to the model. It was
reading keys **`ProgramAggregates` stopped emitting in R5**:

| Read | Reality | Symptom |
|---|---|---|
| `$raw['program']['period']` | the key is **`project`** | the **Period tile always showed "—"** |
| `count($raw['objectives']['list'] ?? [])` | no `objectives` key at all | the header **always read "0 objectives"** — naming a concept **D-R7 retired** |
| the whole `@if (! empty($raw['objectives']['list']))` block | never non-empty | **dead markup**, carrying retired 8.6 status badges |

Confirmed against the live database: real snapshots carry exactly `project, training, budget, activities`.
So the one screen built for cross-checking the narrative was permanently printing a retired metric and a blank
period — and **omitting the `training` block**, which is the dictionary the narrative is actually built from
and the figure guide B3 tells the Director to verify.

Fixed: `project.period`, the objectives block replaced by a **Training reviewed** block (trainors · trainees ·
training hours · training days, the formula, attainment against the annual hours target, and the
`trainee_sources` note the prompt itself uses to caveat a manual count), and the modal's four live tiles
labelled **"Current figures"** — they read `$performance` (live) while the narrative and the accordion are the
frozen `raw_extracted_data`, which on an audit surface must not be silently mixed.

⚠️ **THE TEST WAS ENFORCING THE BUG.** `RedesignUiTest::completedNarrative()` fabricated the **pre-R5** shape
(`'program' => […]`, `'objectives' => ['list' => [['objective' => 'Reach 30 pupils' …]]]`, `'kpis' => […]`) and
the modal test asserted `Reach 30 pupils` rendered. The fixture **supplied** the dead keys, so the suite was
green and **would have failed if the view were corrected** to match reality. The fixture now mirrors
`ProgramAggregates::build()` exactly, and the test asserts the real content
(`50% of the 60-hr annual target`, the trainee-source line, the period) while asserting `Objectives reviewed`
and `0 objectives` are **absent**.

⚠️ **The general rule: a fixture that hand-builds a payload is a contract with the PAST.** It pins the shape
the producer used to emit, so it cannot notice the producer moving on — and it silently converts "this view is
broken" into "this view is required to stay broken". Derive fixtures from the producer (or assert against a
real payload), the same lesson as the Gemini config key in §34.4.

### 34.6 The prototype is NOT mirrored (accepted divergence)

`docs/prototype/pages/program-narratives.html` still renders the pre-§34 shape — no search, no chips, no
pager, always-expanded cards. Accepted by the owner, following the §33 / §23.6 / §31.3 / §32.3 precedent
for Laravel-only divergences.

### 34.7 Test coverage, and one test deliberately NOT written

New `tests/Feature/ProgramNarrativesTest.php` (the page had no dedicated test file): search across all
four axes (title, code, lead, community), case-insensitive partial matching, chips-and-counts,
attention-first ordering, the unknown-`?state=` fallback, pagination bounds, `resetPage` on both search
and filter, the three empty states, and the failure/success toast on both entry points.

⚠️ **An absolute query bound was written, measured, and then DELETED.** "8 projects must cost fewer than N
queries" cannot do the job it appears to do: a NEW per-row query hits the visible page in **both**
requests equally, so no set-scaling comparison can see it — and a bound loose enough to survive framework
drift (measured 38 → ~56) would still miss the +8 one per-row query adds. A brittle guard that cannot
catch its own target is worse than an honest relative one. The surviving test compares **page 1 vs page
2** on a 12-project set, which pins the property that actually regressed (the rollup following the set
rather than the page).

⚠️ **Livewire 3 keeps the page in `paginators.page`, not a `$page` property** — `set('page', 2)` throws
`PublicPropertyNotFoundException`. Use `call('setPage', 2)` / `assertSet('paginators.page', 1)`.
`Paginator::resolveCurrentPage('page')` is wired to that state by `SupportPagination`, which is why the
house manual-paginator pattern works.

### 34.8 Files touched

| File | Change |
|---|---|
| `app/Livewire/ProgramNarratives.php` | search / chips / sort / pagination; paginate-before-enrich; status-checked toast; dead code removed |
| `resources/views/livewire/program-narratives.blade.php` | control bar, collapsible cards, metric strip, pager, three empties, Project vocabulary, `prompt vv2` fix, version-history three states (§34.5b) |
| `resources/views/livewire/programs/partials/hub-overview.blade.php` | `prompt vv2` fix |
| `resources/views/livewire/programs/partials/hub-modals.blade.php` | `prompt vv2` fix; the D3 audit view's dead payload keys → `project.period` + a **Training reviewed** block, and the live tiles labelled (§34.5c) |
| `resources/views/livewire/ai-analysis-review.blade.php` | `vv2` fix on the Prompt tile |
| `resources/views/livewire/partials/program-narrative-heading.blade.php` | **new** — the shared identity block (phrasing content only, because one branch renders it inside a `<button>`) |
| `app/Livewire/Programs/Hub.php` | status-checked toast in `generateNarrative()` |
| `app/Livewire/AiAnalysis.php` | `pending` chip (§34.1a) |
| `resources/views/livewire/ai-analysis.blade.php` | pending row's **Start a new generation** action |
| `docs/guides/10-ai-analysis-narratives.md` | Part B rewritten (stale budget-target wording, the archived `LITRAWIYA` reference, and the new controls); Part A's chip list gains *Generating* |
| `config/smartcemes.php` | `ai.key` — the `config:cache` blocker |
| `app/Services/Ai/GeminiClient.php` | config-only key resolution; `env()` fallback removed |
| `tests/Feature/ProgramNarrativesTest.php` | **new** |
| `tests/Feature/GeminiRetryTest.php` | the config-not-env regression test |
| `tests/Feature/RedesignUiTest.php` | the narrative fixture now mirrors `ProgramAggregates::build()` exactly, and the D3 assertions follow it (§34.5c) |

**Verified:** **592 tests / 3402 assertions, 0 failures** (was 572 / 3318 — **+20 tests, +84 assertions**:
18 new tests in `ProgramNarrativesTest`, one in `GeminiRetryTest`, one in `AiAnalysisQueueTest` for the queue's
`pending` chip, and one re-pointed in `ObjectiveStatusTest`). `npm run build` re-run and the new classes
verified present in the built CSS — `group-hover/t:` needed it, exactly the §33.7 lesson.

---

## 35. VOCABULARY: "PROGRAM" → "PROJECT" ON THE NARROW ENTITY, AND "EXECUTIVE" DROPPED (2026-10-07)

The hierarchy is `College → **Program** → **Project** → Activity`, where **Program** is one of the six broad
CESO thrusts and **Project** is the narrow entity that carries the activities, the budget and the targets.
The app nonetheless called the narrow entity a **"program"** on most of its project surfaces — so the same
word meant two different levels depending on which page you were on. That is the exact confusion §2.1/D14
name as *"the single most common mistake a new session makes"*, except here it was user-facing, on the page
the Director opens from **"Manage Extension Programs"**.

**This is a TEXT-ONLY change.** No migration, no route or class rename, no behaviour change.

### 35.1 The classification rule (the part worth keeping)

"Program" appears ~200 times in the codebase and **most of those are correct**. The rule that separates them:

| Means | Where | Action |
|---|---|---|
| the **BROAD** thrust (correct) | `colleges/index.blade.php` (the whole hub — `Program` is literally the level it browses), `programs/broad.blade.php`, "Broad programs carry **no** target", "no per-**program** target exists" (admin dashboard, targets page), the CESO-thrust badge on the AI review (`$r['ceso_program']`), the Program CRUD (`programForm`) | **leave** |
| the **NARROW** entity (wrong) | project hub + its tabs, `/projects`, `/my-projects`, proposals, the communities page's linked-rows column, the faculty dashboard, the print reports, the import errors, the generated XLSX headers | **sweep** |
| **unrelated** to the hierarchy | the assessment instrument's `has_barangay_health_programs` / `programs_benefited_from`, the AI prompt's "school feeding programmes" | **leave** |
| **invisible** | `$program`, `ProgramPolicy`, `program_lead_id`, `config('smartcemes.statuses.program')`, the `program-narratives` route, the `ProgramNarrative` model, `Community::extensionPrograms()`, `violatesProgramRange()` | **leave** — house precedent: the report route is still `reports.results-framework` |

⚠️ **Two relations carry the legacy name and point at the PROJECT**: `Community::extensionPrograms()` is
`belongsToMany(ExtensionProject::class, 'community_extension_project')`, and `Activity`'s lead relation is
`program`. So the *labels* on those screens were wrong even though the code was right — verify the relation's
target before deciding, never the label alone.

### 35.2 What changed

| Layer | Examples |
|---|---|
| Project hub + its four tabs | "**Program Goal**" → "**Project Goal**"; "Generate **program** narrative" → "**Generate narrative**"; "**Program** is locked to this hub… the **program** range" → project; "Edit **program** details"; "Unenroll this beneficiary from the **program**?"; "Charged against this **program**"; "**Program-level** (no specific activity)" |
| `/projects` and `/my-projects` | "Search **programs** by title or code…", "No **programs** match your filters", "**Programs** you lead" |
| Proposals | "Target **program** \*", "Outside **program** range — approval blocked", the "**Program** · Community" column, "the **program** hub" |
| Communities | the linked-rows column "**Programs**", "No linked **programs** yet" |
| Faculty dashboard | the "**Programs** … led" tile, the "**Program**" column |
| Print reports | "I · **Program** Portfolio", "IV · Faculty Participation by **Program**", "**Programs** Led" |
| **Import error messages** | "…is not enrolled in this **program**", "No beneficiaries are enrolled in this **program** yet" |
| **Generated XLSX headers** | `Program: %s (%s) · Activity: …` → `Project: …`, "This is the **program's** enrolled list" |
| Prototype | `program-detail.html`, `program-narratives.html`, `reports.html` |

**"Executive" is dropped from the feature's name** (owner request, same pass): the modal chip, the hub card
heading and the narratives-page hero chip now read **"Project Narrative"** / **"Project Narratives"** — not
"Executive Program Narrative". The report section "Program Executive Narratives" → "Project Narratives".

**"Executive Summary" → "Summary"** — the block label *inside* a narrative (the card, the modal, and the
prototype), plus the guide's wording. So **"Executive" no longer appears anywhere user-visible.**

⚠️ **I first left this one, and that was under-delivering.** My reasoning was that "executive summary" is a
genre term rather than a scope claim, so I flagged it instead of changing it. But the instruction was
*"remove the 'Executive' part"* — unqualified — and flagging is not the same as doing. **When an instruction
is broad enough to cover a case you are unsure about, do the case and say you did, rather than deferring the
whole thing.**

### 35.3 ⚠️ The inventory must cover EVERY layer — my first pass did not

My first inventory grepped **only `resources/views/livewire/programs/`** and produced a 12-file, ~35-string
plan. The real scope was **29 files** — the reports, the communities page, the faculty dashboard and the
proposals had all been missed, and the reports are *printed documents*. The
`user-facing-vocabulary-rename` skill's Step 1 says to grep the term across every layer and **report the
inventory as a table before editing**; I skipped the reporting step and under-scoped as a result.

⚠️ **An under-scoped sweep is worse than no sweep**: it leaves the layers actively disagreeing — a page
saying "Project" beside a sheet headed "Program:" — which is the drift this project repeatedly warns about.
Grep `resources/views/` **entirely** (not one subdirectory), plus `resources/js/`, `app/`, `docs/prototype/`,
and the tests.

### 35.4 Tests

Three pinned assertions followed the text: `RedesignUiTest` (`Generate narrative`),
`ActivityAttendanceImportTest` and `ActivityEvaluationTest` (the import error). No prototype harness asserted
any of the changed strings.

**Verified:** **592 tests / 3402 assertions, 0 failures** — **unchanged** from §34, and that is the
expected result for a text-only change: three pinned assertions were swapped one-for-one, so neither the
test count nor the assertion count moves (the `user-facing-vocabulary-rename` skill calls this out
explicitly — confirm by the pass count, not by a delta).

**Left alone on purpose:** `layouts/guest.blade.php`'s login tagline "…for community extension programs" —
generic institutional phrasing, not a reference to a specific entity.

---

_End of plan. Update this file as each phase lands — record what actually changed, what broke,
and any decision that shifted. This file is the working record for the revision._

