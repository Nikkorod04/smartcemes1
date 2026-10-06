# SmartCEMES — Safe-Zone Plan: Extension Projects & Faculty Expertise

_Status: **PARTLY IMPLEMENTED.** The **project archive feature is built and tested** (§11). The **5-project
seeder is planned, not built** (§10) — owner decision 2026-10-05: build it later. **The expertise list is
NOT being changed** (§6 is deferred)._

**Goal:** bring the demo's projects into a "safe zone" — simple, plainly LNU-plausible, and easy to
defend — without contradicting anything the owner has already locked.

> **Decisions taken 2026-10-05**
> 1. **Retain just the 5 safe projects.** The 8 seeded projects are to be REPLACED by the 5 in §5.
> 2. **The expertise list is untouched for now** — §6 is a proposal, not a change.
> 3. **The Director gets an archive action** so the unsafe projects can be removed by hand rather than
>    with DB surgery. **Built** — see §11.

---

## 1. What I checked

`database/seeders/` — `ProgramSeeder` (the 6 thrusts), `Phase2Seeder` (the 6 legacy projects),
`FeedingProgramSeeder` (BUSOG), `GraduateProgramSeeder` (PANDAY), `FacultyCollegeSeeder` (the
expertise map), `UserSeeder` (specializations) · `config/smartcemes.php` (`expertise_options`,
`expertise_categories`) · `revisions.md` §23.4 (the **approved** college → thrust mapping).

---

## 2. Current state

### 2.1 Thrust coverage — the headline problem

| CESO thrust | Projects | College(s) |
|---|---|---|
| Literacy, Numeracy & Language | 1 — LITRAWIYA | COE |
| Information, Communication & Education | **4** — e-LITERACY, SENIOR CARE, BUSOG, PANDAY | CAS + GRAD |
| **Cultural Development** | **0 — EMPTY** | — |
| Physical Fitness & Sports Development | 1 — BATANG MATINIK | COE |
| Livelihood, Technical & Business Management | 1 — KABUHIAN | CME |
| Environmental Conservation & Disaster Preparedness | 1 — HANDA | CAS |

**Four of the eight projects sit in one thrust, and one thrust has nothing.** That is the first thing
an adviser will notice on the hub, and it is a data problem, not a UI problem.

### 2.2 The 8 seeded projects

| Code | Title | College | Thrust | Lead |
|---|---|---|---|---|
| EXT-2026-001 | LITRAWIYA: Barangay Reading Proficiency Program | COE | Literacy, Numeracy & Language | Bianca Oledan |
| EXT-2026-002 | HANDA: Disaster Preparedness Training for Coastal Households | CAS | Environmental Conservation & DRP | Nikko Villas |
| EXT-2026-003 | KABUHIAN: Livelihood Skills Training on Soap & Detergent Making | CME | Livelihood, Technical & Business | Kent Naputo |
| EXT-2026-004 | e-LITERACY: Digital Literacy for Parents & Senior Citizens | CAS | Information, Communication & Education | Carlo Sumile |
| EXT-2026-005 | SENIOR CARE: Health & Wellness for Senior Citizens | CAS | Information, Communication & Education | Bianca Oledan |
| EXT-2026-006 | BATANG MATINIK: Sports & Values Formation Clinic | COE | Physical Fitness & Sports | Kent Naputo |
| CAS-2026-00x | BUSOG: School-Based Supplementary Feeding & Nutrition | CAS | Information, Communication & Education | Nikko Villas |
| GRAD-2026-001 | PANDAY: Community Research & Documentation Capability Building | GRAD | Information, Communication & Education | Ramon Villamor |

### 2.3 Faculty and the 15 expertise areas currently assigned

| Faculty | College | Specialization | Assigned expertise |
|---|---|---|---|
| Carlo Sumile | CAS | Information Technology | Digital Literacy · ICT Training |
| Bianca Oledan | COE | Reading Education | Literacy & Reading · Remedial Instruction · Mother-Tongue Pedagogy |
| Nikko Villas | CAS | Environmental Science | Environmental Conservation · Solid Waste Management · Disaster Preparedness |
| Kent Naputo | CME | Entrepreneurship | Entrepreneurship · Business Planning · Financial Literacy |
| Dr. Ramon L. Villamor | GRAD | Research & Extension Management | Assessment Design · Local Governance |
| Dr. Cristina P. Manalo | GRAD | Community Development | Community Organizing · Peace & Conflict Resolution |

`config('smartcemes.expertise_options')` offers **24** areas; only **15** are ever assigned.

---

## 3. What is ALREADY APPROVED — do not contradict

`revisions.md` §23.4 is an owner-approved **seeding target**, so this plan works with it, not around it:

- **The 6 thrusts are verbatim CESO thrusts and are fixed.** No health/nutrition thrust exists — which
  is exactly why health projects had to be filed elsewhere.
- **The approved primary colleges:** Literacy → **COE** · ICT → **CAS** · Cultural Development → **CAS**
  (BM Music Education / BA Communication; secondary: **CME** via BS Tourism) · Sports → **COE only** ·
  Livelihood → **CME** · Environment & DRP → **CAS**.
- **Cultural Development is a known gap** with two legitimate homes. Filing one heritage project there
  is already the stated target.
- **SENIOR CARE under ICT is DELIBERATE** — it is retained there as the AI's interagency-referral demo
  case (D-R8). **Do not "fix" it.**
- **BUSOG under ICT is the acknowledged weakest fit of the eight**, retained on purpose.

---

## 4. What is genuinely NOT in the safe zone

1. **Cultural Development has 0 projects** — a thrust with an empty page.
2. **ICT carries 4 of 8 projects, and 3 of them are not ICT at all** (SENIOR CARE = health, BUSOG =
   nutrition, PANDAY = research). The thrust looks like a dumping ground.
3. **The expertise vocabulary is 24 options for 6 people**, so 9 are dead weight — and several sit
   outside a normal university's lane.
4. **The unused areas include the most defensible ones** (Numeracy & Math Instruction, Early Childhood
   Education, Special & Inclusive Education, Sports Coaching, Cultural Heritage) while the riskiest
   ones (**Geriatric Care** — a nursing domain; **Community Wellness** — vague) are the ones actually
   assigned. The list is backwards.
5. **BUSOG is led by an Environmental Science specialist.** Nutrition is not that person's field; the
   lead's expertise does not match the project.
6. **'Business Planning' and 'Entrepreneurship'** are near-duplicates, as are **'Digital Literacy' and
   'ICT Training'** — two slots spent on one concept.

---

## 5. Proposal A — 5 safe extension projects

Plain, descriptive names. Every one lands on the **approved** primary college for its thrust, and none
needs a health thrust to justify it.

| # | Project | College | Thrust | Why it is safe | Lead expertise |
|---|---|---|---|---|---|
| 1 | **KULTURA: Local Heritage & Folk Arts Appreciation** | CAS (alt. CME / BS Tourism) | Cultural Development | **Fills the empty thrust.** Folk dance, songs and local history with barangay youth — a standard extension activity with an obvious university basis. | Cultural Heritage |
| 2 | **NUMERO: Numeracy Enhancement Sessions for Grades 1–3** | COE | Literacy, Numeracy & Language | The thrust is named "Literacy, **Numeracy** & Language" but holds only a *reading* project. This gives it the missing half. | Numeracy & Math Instruction |
| 3 | **LINIS: Barangay Solid Waste Segregation & Composting** | CAS | Environmental Conservation & DRP | The thrust names two things; HANDA covers only disaster preparedness. This covers conservation, and segregation/composting is the most common SUC extension there is. | Solid Waste Management |
| 4 | **PAGKAON: Basic Food Processing & Product Costing** | CME | Livelihood, Technical & Business Management | A different livelihood skill from KABUHIAN's soap-making, so it strengthens CME without duplicating. Processing + costing is TESDA-adjacent and trivially defensible. | Entrepreneurship · Financial Literacy |
| 5 | **DIGITAL: Barangay Records & Online Safety Training** | CAS | Information, Communication & Education | A **genuinely ICT** project, which is what the over-filled ICT thrust lacks. Basic encoding and online safety for barangay workers. | Digital Literacy |

**If adopted as a replacement set**, they let you retire the two strained filings (SENIOR CARE, BUSOG)
— but see §3: both are currently retained *on purpose*, so retiring them is an owner decision, not a
correction.

---

## 6. Proposal B — 15 safe expertise areas

Curated from the existing 24. Each is plainly taught at a teacher-education university with arts,
sciences and management colleges, and each maps to at least one thrust.

| # | Expertise area | Category | Natural college | Serves thrust |
|---|---|---|---|---|
| 1 | Literacy & Reading | Literacy | COE | Literacy, Numeracy & Language |
| 2 | Remedial Instruction | Literacy | COE | Literacy, Numeracy & Language |
| 3 | Mother-Tongue Pedagogy | Literacy | COE | Literacy, Numeracy & Language |
| 4 | Numeracy & Math Instruction | Numeracy | COE | Literacy, Numeracy & Language |
| 5 | Early Childhood Education | Education & Sports | COE | Literacy, Numeracy & Language |
| 6 | Special & Inclusive Education | Education & Sports | COE | Literacy, Numeracy & Language |
| 7 | Digital Literacy | Technology | CAS | Information, Communication & Education |
| 8 | Entrepreneurship | Livelihood | CME | Livelihood, Technical & Business |
| 9 | Financial Literacy | Livelihood | CME | Livelihood, Technical & Business |
| 10 | Environmental Conservation | Environment | CAS | Environmental Conservation & DRP |
| 11 | Solid Waste Management | Environment | CAS | Environmental Conservation & DRP |
| 12 | Disaster Preparedness | Environment | CAS | Environmental Conservation & DRP |
| 13 | Cultural Heritage | Governance & Community | CAS | Cultural Development |
| 14 | Sports Coaching | Education & Sports | COE | Physical Fitness & Sports |
| 15 | Community Organizing | Governance & Community | CAS / GRAD | cross-cutting |

**Borderline but defensible** (keep as spares if you want 16–17): Assessment Design · ICT Training ·
Local Governance.

**Dropped from the safe zone:** Geriatric Care (nursing domain) · Community Wellness (vague) ·
Health Literacy (no health thrust to serve) · Media & Information Literacy (duplicates Digital
Literacy) · Peace & Conflict Resolution (defensible but a stretch, and currently misfiled) ·
Business Planning (duplicates Entrepreneurship).

---

## 7. Reassigning so every area has an owner

The 15 above must be distributed across the 6 faculty, and the lead's expertise should match the
project they lead (§4.5).

| Faculty | College | Proposed expertise | Leads |
|---|---|---|---|
| Carlo Sumile | CAS | Digital Literacy | DIGITAL |
| Bianca Oledan | COE | Literacy & Reading · Remedial Instruction · Mother-Tongue Pedagogy | LITRAWIYA |
| Nikko Villas | CAS | Environmental Conservation · Solid Waste Management · Disaster Preparedness | HANDA · LINIS |
| Kent Naputo | CME | Entrepreneurship · Financial Literacy | KABUHIAN · PAGKAON |
| Dr. Ramon L. Villamor | GRAD | Numeracy & Math Instruction · Assessment Design | NUMERO |
| Dr. Cristina P. Manalo | GRAD | Early Childhood Education · Special & Inclusive Education · Community Organizing | KULTURA |

This also fixes §4.5 (BUSOG's lead no longer needs an Environmental Science match) and gives the two
Graduate School members areas that fit their specializations instead of the dropped Local Governance /
Peace & Conflict Resolution.

---

## 8. What changes if you approve this

**Data / config**
- `config/smartcemes.php` — `expertise_options` (24 → 15) and `expertise_categories`.
- `database/seeders/FacultyCollegeSeeder.php` — the `EXPERTISE` map.
- `database/seeders/Phase2Seeder.php`, `FeedingProgramSeeder.php`, `GraduateProgramSeeder.php` — project
  titles/colleges/thrusts, if projects are replaced.
- `database/seeders/R4TargetsSeeder.php`, `LegacyCohortSeeder.php` — targets and cohorts follow the
  projects.

**⚠️ The real cost — this is not a rename.**
`docs/prototype/assets/js/seed-data.js` is the **contract** (rule §11 rule 3), and its per-project
`activities[]` array is what the rendered hours are derived from — §30 pinned Laravel to it
**exactly** (199 / 654 / 63 / 52 / 0 / 0 hrs). `_check.cjs`'s `[FORMULA]` block asserts that arithmetic.
So any project or cohort change must move together with:

- `docs/prototype/assets/js/seed-data.js` (projects, activities, expertiseOptions, faculty)
- `docs/prototype/_check.cjs` (nav contract, `[FORMULA]`, the 224–307 hub block)
- `_hubtest.cjs` · `_facultytest.cjs` · `_dashtest.cjs`
- Laravel tests with pinned figures: `DefenceWalkthroughTest`, `ExtensionHubTest`, `FacultyModuleTest`,
  `TrainingHoursTest`, `FreshSeedHierarchyTest`, `CollegeProgramFoundationTest`

**Effort note:** adding expertise alone is cheap (config + one seeder + `seed-data.js`). Replacing
projects is the expensive half because of the pinned cohort figures.

---

## 9. Decisions taken (2026-10-05)

| # | Decision | State |
|---|---|---|
| A | **Retain just the 5 safe projects** — they REPLACE the 8 seeded ones | Seeder **planned**, not built (§10) |
| B | **Expertise untouched** — §6 stays a proposal | Not started |
| C | **Director can archive a project** from the hub | ✅ **Built and tested** (§11) |

---

## 10. The seeder — ✅ IMPLEMENTED (2026-10-05)

`database/seeders/SafeZoneProjectSeeder.php`, registered **LAST** in `DatabaseSeeder`. See §10.6 for the
results. The design notes below are kept as the specification it was built to.

**One new seeder, not edits to the existing five.** The 8 current projects arrive from `Phase2Seeder`
(6 legacy), `FeedingProgramSeeder` (BUSOG) and `GraduateProgramSeeder` (PANDAY). Rewriting those in
place would destroy the legacy codes and the demo cohorts that §30 pinned to `seed-data.js`. A new,
additive seeder is safer and reversible.

### 10.0 PREREQUISITE — ✅ DONE (2026-10-05)

The archive cascade used to live **inside `Colleges\Index::archiveProject()`**, i.e. inside a Livewire
component, so a seeder could not call it — and duplicating it would have recreated the
two-implementations-of-one-ruleset problem §25 already had to undo for the create forms.

It has been extracted to **`App\Services\ProjectArchiveService`**, with **`archive()`** and its mirror
**`restore()`** side by side so they cannot drift. The hub calls the service, and the seeder can now do
the same. `ProjectDeleteTest` + `ProjectRestoreTest` cover it through the hub.

### 10.1 Shape

`database/seeders/SafeZoneProjectSeeder.php`, registered **LAST** in `DatabaseSeeder` (after
`R4TargetsSeeder` and `LegacyCohortSeeder`, so there is something to archive).

1. **Archive the 8** via `ProjectArchiveService::archive()` — one call per project. **Do NOT hard-delete:**
   the legacy codes (`EXT-2026-00X`) are historical and `nextCode()`'s `withTrashed()` floor depends on
   them surviving.
2. **Create the 5** with college-prefixed codes from `ExtensionProject::nextCode($college->code)` →
   `CAS-2026-00X` / `COE-2026-00X` / `CME-2026-00X`.
3. **Give each a real cohort** — see §10.3. A project rendering 0 hours is the §26.4 problem again.
4. **Set `annual_target_hours` and `allocated_budget`** per §10.2.
5. **Link a community and a `program_lead_id`** whose expertise matches the project (§7).
6. **Do NOT write `ProgramObjective` rows.** `FeedingProgramSeeder` still does, but the 8.6 dictionary is
   retired (D-R7) and those rows are unread — new ones would be dead data.

### 10.2 The five projects, with their approved colleges and figures

| Project | College | Thrust | Lead | Community | Budget | Hours target |
|---|---|---|---|---|---|---|
| **KULTURA**: Local Heritage & Folk Arts Appreciation | CAS *(alt. CME / BS Tourism)* | Cultural Development | Dr. Cristina P. Manalo | Brgy. San Jose | ₱32,000 | 80 |
| **NUMERO**: Numeracy Enhancement Sessions for Grades 1–3 | COE | Literacy, Numeracy & Language | Dr. Ramon L. Villamor | Brgy. Sagkahan | ₱45,000 | 90 |
| **LINIS**: Barangay Solid Waste Segregation & Composting | CAS | Environmental Conservation & DRP | Nikko Villas | Brgy. Apitong | ₱38,000 | 80 |
| **PAGKAON**: Basic Food Processing & Product Costing | CME | Livelihood, Technical & Business | Kent Naputo | Brgy. Salvacion | ₱55,000 | 100 |
| **DIGITAL**: Barangay Records & Online Safety Training | CAS | Information, Communication & Education | Carlo Sumile | Brgy. El Reposo | ₱30,000 | 60 |

Resulting spread: CAS 3 · COE 1 · CME 1 · GRAD 0 — and **Cultural Development is no longer empty**, which
is the single most visible defect today (§2.1).

### 10.3 The cohort spec — how to make the hours non-zero

`TrainingHoursService` is the only implementation, and its formula is
**`trainors × trainees × days`** where:

- **`trainors`** = distinct faculty on the activity (`activity_faculty` pivot) — **1** if the lead is the
  only one assigned.
- **`trainees`** = distinct beneficiaries with `present`/`late` **attendance** (R-Q1). `activities.participants`
  is only the FALLBACK, and it reads 0 for the project's Trainees tile — so **attendance rows are what
  actually produce the figure**. Writing `participants` alone is not enough.
- **`days`** = `activities.no_of_days` (decimal(4,1); NULL already counts as 1.0). Set it explicitly so a
  half day is a real `0.5`.

Two activities per project, one faculty on each, attendance written per §30's pattern
(`Attendance::create([... 'status' => 'present'|'late'])`):

| Project | Activity A | Activity B | Total hours | Target | Attainment |
|---|---|---|---|---|---|
| KULTURA | 1.0 d × 24 = 24 | 0.5 d × 24 = 12 | **36** | 80 | 45.0 % |
| NUMERO | 1.0 d × 30 = 30 | 0.5 d × 30 = 15 | **45** | 90 | 50.0 % |
| LINIS | 1.0 d × 24 = 24 | 0.5 d × 20 = 10 | **34** | 80 | 42.5 % |
| PAGKAON | 2.0 d × 20 = 40 | 1.0 d × 18 = 18 | **58** | 100 | 58.0 % |
| DIGITAL | 1.0 d × 16 = 16 | 0.5 d × 16 = 8 | **24** | 60 | 40.0 % |

Every figure is **non-zero and under 100 %** — honest for a mid-term demo, and no project is
over-allocated (D7 stays quiet). Totals: **114 beneficiaries, ~250 attendance rows, 197 hours**.

Keep `participants` equal to the per-activity attendee count (§15.2's rule) so the hours do not *drop* when
attendance is imported later.

### 10.4 ⚠️ What this seeder must NOT do, and what must move with it

- **Do NOT edit `docs/prototype/assets/js/seed-data.js` silently.** It is the demo contract (§11 rule 3)
  and §30 pinned Laravel's rendered hours to its per-project `activities[]` array **exactly**
  (199 / 654 / 63 / 52 / 0 / 0). Replacing the projects breaks that correspondence by definition.
- **Consequence to accept up front:** after this seeder runs, Laravel and the prototype **diverge on
  projects** — the same class of accepted drift as the college hub (§23.6). That is fine, but it must be a
  *recorded* decision, not an accident, and `docs/prototype/PATTERNS.md` should say so.
- **`_check.cjs`'s `[FORMULA]` block and `_hubtest.cjs`** assert against the prototype's own data, so they
  keep passing — they do not cover Laravel. **A green harness run will NOT validate this seeder.**
- **Laravel tests that read seeded projects** must be re-checked, because several iterate whatever is in
  the DB: `ExtensionHubTest`, `DefenceWalkthroughTest`, `FacultyModuleTest`, `TrainingHoursTest`,
  `FreshSeedHierarchyTest`, `CollegeProgramFoundationTest`, `RankingServiceTest`,
  `ReportMetricDictionaryTest`, and `ProjectDeleteTest`.
- **`ProjectDeleteTest` depends on the seed having a project WITH activities** — its
  `projectWithActivities()` helper *skips* when none exists. The 5 new projects must carry activities or
  four tests silently skip instead of failing.
- **`LegacyCohortSeeder` / `R4TargetsSeeder`** write against the legacy projects, which this seeder
  archives — so they become no-ops. Run them first (harmless) rather than retiring them, so a
  `migrate:fresh --seed` still reproduces the §30 figures if the archive is ever skipped.
- **`Phase3Seeder`** creates proposals against legacy project codes. Archiving the projects leaves those
  proposals intact (not cascaded) but pointing at archived work — decide whether to archive the proposals
  too, or accept them as historical.

### 10.5 Order of work when it is built

1. Extract `ProjectArchiveService`; re-run `ProjectDeleteTest` (it must stay green — that is the refactor's
   safety net).
2. Write `SafeZoneProjectSeeder`; run `php artisan migrate:fresh --seed`.
3. Walk `/colleges` → each of the 5 → confirm non-zero hours, a real cohort and a set target.
4. Confirm Cultural Development is no longer empty on the CAS college view.
5. Re-run the full suite; fix whichever pinned figures moved.
6. Record the Laravel↔prototype divergence in `revisions.md` (a new section) and in `PATTERNS.md`.

### 10.6 What was actually built (2026-10-05)

**The seeder.** `SafeZoneProjectSeeder` archives every live project through `ProjectArchiveService`
(8 archived, not deleted — so each can be restored complete, with its activities and cohorts), then
creates the five with college-prefixed codes. Each carries **3 activities** with explicit `no_of_days`,
**a cohort of Leyte-named beneficiaries**, attendance for every session, three budget entries (including
one project-wide row with a NULL `activity_id`, which is the shape the archive cascade had to be fixed
for), `annual_target_hours` and `allocated_budget`. **No `ProgramObjective` rows** are written (D-R7).

**The figures — verified against the real database, not just asserted:**

| Code | Project | College | Thrust | Hours / target | Trainors | Trainees |
|---|---|---|---|---|---|---|
| CAS-2026-002 | KULTURA | CAS | Cultural Development | **84 / 100** (84 %) | 2 | 24 |
| COE-2026-001 | NUMERO | COE | Literacy, Numeracy & Language | **90 / 120** (75 %) | 2 | 30 |
| CAS-2026-003 | LINIS | CAS | Environmental Conservation & DRP | **70 / 100** (70 %) | 2 | 24 |
| CME-2026-001 | PAGKAON | CME | Livelihood, Technical & Business | **108 / 150** (72 %) | 2 | 20 |
| CAS-2026-004 | DIGITAL | CAS | Information, Communication & Education | **48 / 80** (60 %) | 2 | 16 |

Every project is non-zero and **under** its target, so the D7 over-allocation warning stays quiet.
114 beneficiaries, 15 activities, 336 attendance rows. **Cultural Development is no longer empty.**

**Three real bugs the seeder exposed — all fixed:**

1. **`/proposals` returned HTTP 500** the moment any project was archived.
   `ActivityProposal::violatesProgramRange()` dereferenced `$this->program` unconditionally, and the
   relation resolves to **null** for an archived project. It is called by the review drawer for *every*
   proposal in the list and by `Proposals\Index::approve()`, so one archived project took the whole page
   down. Now guarded, returning `false` (the 8.8 range rule cannot be evaluated against a project that is
   no longer live; `true` would invent a violation). Found by `RouteSurfaceTest`, which walks surfaces by
   URL — the unit tests could not see it.
   *This is the risk that was earlier written off as "guarded by accident". It was not.*
2. **The walkthrough's `CME-2026-001` assertion** — that code now belongs to the seeded PAGKAON project,
   so the walkthrough's own project takes the next one. Re-asserted as a pattern plus "not the seeded
   code" rather than a hardcoded literal.
3. **The walkthrough's 8.8 conflict demo** booked a faculty member against BUSOG's *Feeding Cycle 2*.
   Archiving BUSOG takes its activities with it, so the demo had to move to a LIVE booking — LINIS, also
   led by Nikko Villas, on 2026-08-22.

**Test fallout, all resolved by design:** 12 tests pinned the old eight-project demo set
(`FreshSeedHierarchyTest` ×3, `DefenceWalkthroughTest` ×4, `FeedingProgramSeederTest` ×2,
`CollegeProgramFoundationTest` ×1, `RouteSurfaceTest` ×2). They now assert the new reality — **5 live,
8 archived** — and `FreshSeedHierarchyTest` gained a test that pins the thrust coverage explicitly.

### ⚠️ Two things left open, deliberately

1. **Physical Fitness & Sports Development is now the empty thrust.** Archiving BATANG MATINIK moved the
   gap that Cultural Development used to have. The five were chosen as specified and none of them is a
   sports project, so this is a consequence of the agreed set rather than an oversight — and
   `FreshSeedHierarchyTest` asserts it rather than hiding it. A sixth project (`LAKAS: Youth Sports &
   Physical Fitness Clinic`, COE) would close it.
2. **`docs/TEST-SCRIPT.md` now trails.** It documents BUSOG's figures, the `CME-2026-001` code and the
   Feeding-Cycle-2 conflict demo — all of which have moved. The *test* is current; the prose is not.
   `docs/prototype/assets/js/seed-data.js` is deliberately untouched (see §10.3), so the prototype's
   harnesses stay green while no longer describing the Laravel demo set.

**Verification:** **534 tests / 3154 assertions, 0 failures**; `pint` clean; `migrate:fresh --seed`
reproduces the figures exactly; `/colleges?college=CAS` renders three thrusts (including Cultural
Development) and the KULTURA hub renders 84/100 with its cohort, budget doughnut and attendance chart.

---

## 11. The project archive feature (built 2026-10-05)

The Director had no way to remove a project — the hub's view 3 offered only "View" and "New project".
`/colleges` now carries an **archive action on every project card**.

**What was added**

| File | Change |
|---|---|
| `app/Policies/ProgramPolicy.php` | New **`delete()`** ability, Director-only. Its own ability rather than reusing `manage()`, so the destructive path is separately assertable. |
| `app/Livewire/Colleges/Index.php` | New **`archiveProject(int $id)`** — authorizes, then in one transaction soft-deletes the project **and its activities plus their attendance, rendered-hours, availability and budget rows**. |
| `resources/views/livewire/colleges/index.blade.php` | The card became a **DIV whose body is the link**, so the footer can carry the archive button (a `<button>` inside an `<a>` is invalid HTML and the click bubbles — it would navigate instead of archiving). Confirmed with `wire:confirm`, matching `Communities\Index::confirmDelete()`. |
| `resources/css/app.css` | `.proj-archive-btn` + `.proj-card:focus-within` (focus now lands on the inner anchor). `npm run build` re-run. |
| `tests/Feature/ProjectDeleteTest.php` | **10 new tests** — see below. |

**Why the cascade exists (the non-obvious part).** Deleting the project row alone is NOT enough to make
it disappear. Every project- and university-level figure iterates `ExtensionProject` and therefore
excludes trashed rows automatically — but **three surfaces query `Activity` globally** and would keep
showing a deleted project's work:

- the **Calendar** (`Calendar::render()` → `Activity::query()`)
- the **Availability activity picker** (`Activity::with('program')`)
- a **faculty's own rendered-hours list**, which eager-loads `activity.program` and would render a blank row

**Deliberately NOT cascaded:** proposals and needs-assessments — separate records with their own
workflow and audit trail.

**Everything is a SOFT delete**, so it is reversible, and `ExtensionProject::nextCode()` computes its
floor over `withTrashed()` — an archived project's code is never reissued.

**Tests (10):** archives the project and its activities · leaves every project query and the hub's rows ·
stops reaching the global `Activity` scope · leaves the Availability picker · never reissues the code ·
writes the D8 audit entry · Secretary refused · Faculty refused · one archive action per card ·
the button sits outside the card's link.

**Verification:** **511 tests / 2945 assertions, 0 failures**; `vendor/bin/pint` clean on all three
changed PHP files; `npm run build` OK (`app-*.css` 104.34 → 104.86 kB).

---

## 12. The archive, completed — visibility + undo (2026-10-05)

The archive shipped first as a **one-way door**: `Communities\Index` has had a `restore()` since v4.5,
and a project is a far larger thing to lose to a mis-click. It also had no way to *see* an archived
project, so "archived" was indistinguishable from "gone". Both halves are now built.

**What was added**

| File | Change |
|---|---|
| `app/Services/ProjectArchiveService.php` | **New.** Owns `archive()` **and** its mirror `restore()`, so they cannot drift. This is the §10.0 prerequisite, now satisfied. |
| `app/Policies/ProgramPolicy.php` | New **`restore()`** ability, Director-only — a separate ability from `delete()`, because "may archive" and "may bring back" are different powers. |
| `app/Livewire/Colleges/Index.php` | `archiveProject()` delegates to the service; new **`restoreProject()`** (resolves through `onlyTrashed()`); view 3 gained an **Archived** chip; `programRows()` and `programBelongsToCollege()` now include archived projects (see the bug below). |
| `resources/views/livewire/colleges/index.blade.php` | The **Archived** chip; archived cards get a gray *Archived* badge, a muted "data retained" note instead of figures, **no hub link**, and a **Restore** button. Program cards show "N archived — open to restore". |
| `tests/Feature/ProjectRestoreTest.php` | **10 tests** — visibility, the two reachability guards, the full cascade round-trip, the targets page, access, and the live-project guard. |

### 🐛 Two reachability bugs the work uncovered (both fixed, both mutation-tested)

Archiving **hides a project from every live query** — which is the point, but it also hid the route back:

1. **`programRows()` groups the college's LIVE projects**, so a thrust whose only project was archived
   dropped off the program list — taking the last route to that project with it. The project became
   unreachable **and un-restorable**. Fixed by grouping over `withTrashed()` while rolling up only the
   live members (so a fully-archived thrust honestly reads 0 projects).
2. **`programBelongsToCollege()` validated `?program=` against live projects only**, so a bookmark or
   refresh on a fully-archived thrust bounced silently back to view 2. Fixed with `withTrashed()`,
   matching the list.

Both are the *same* trap one layer apart, and neither was caught by the archive tests — they only
surfaced by driving the real page. **A test that the row is hidden is not a test that it is findable.**

### What deliberately did NOT change

- **No hard delete anywhere.** Archived rows survive, the code is never reissued, and D8 still records
  the deletion.
- **`status = 'cancelled'` was NOT repurposed.** It is a different concept: a real project that was
  called off, which should stay **visible and counted**. Archive means "take this out of the live set".
- **The archived card prints no figures.** The roll-up walks live activities, so it would show 0 hours /
  0 reach — and an archived project has not lost its data, so a 0 would be a false claim rather than an
  honest zero (the NULL-over-0 rule).

**Verification:** **533 tests / 3008 assertions, 0 failures**; `pint` clean; `npm run build` OK.
Verified on the real page: the Literacy thrust stays listed reading "1 archived — open to restore", and
the archived card renders with its Restore button — then the project was restored and the dev DB
returned to 8 live projects / 0 trashed.

