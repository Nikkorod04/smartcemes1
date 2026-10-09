# SmartCEMES — Manual Test Script (defence walkthrough)

_Written 2026-09-24 against **blueprint v4.15** and the post-revision architecture (phases P0 + R1–R7,
recorded in **`revisions.md`**). Supersedes the 2026-09-15 script, which described the pre-revision
hierarchy. Suite at the time of writing: **465 tests / 2057 assertions, 0 failures**._

> **Read this first.** The hierarchy is four levels, not three:
>
> ```
> College  →  Program  →  Project  →  Activity
> CAS/COE/CME   6 CESO      (was "ExtensionProgram")   (unchanged)
>                 thrusts
> ```
>
> **"Program" in any older note means what is now a "Project".** The name `Program` now means one of
> the six broad CESO thrusts. Getting this backwards is the single most common mistake.

---

## 0. Start the app

```bash
php artisan serve
```

Optional — only needed for step 11 (deadline notifications) and the nightly backup:

```bash
php artisan schedule:work
```

### Accounts

| Role | Email | Password |
|---|---|---|
| Admin (Director) | `admin@lnu.com` | `password` |
| Secretary | `secretary@lnu.com` | `password` |
| Faculty | `faculty1@lnu.com` … `faculty4@lnu.com` | `password` |

Faculty accounts: Carlo Sumile (CAS) · Bianca Oledan (COE) · Nikko Villas (CAS) · Kent Naputo (CME).

### Where things live in the sidebar

The admin nav **collapses the whole hierarchy into ONE entry** — this is deliberate
(prototype PATTERNS v4.3), so do not go looking for separate *Colleges* / *Extension Programs* /
*Extension Projects* items:

| Section | Items |
|---|---|
| Overview | Dashboard · **University Targets** · Calendar |
| Extension Programs | **Manage Extension Programs** ← the hub: the whole College → Program → Project chain |
| Management | Faculty Management · Communities & Partner Schools |
| Approvals | Proposals · Availability Requests · Rendered Hours |
| Intelligence & Reports | AI Analysis Review · Project Narratives · Interagency Catalogue · Reports |

**The hub is a three-view page.** It opens on the four college cards; clicking a card (or
`/colleges?college=CAS`) swaps in that college's KPIs and **the programs it delivers**; clicking a
program (or `/colleges?college=CAS&program=N`) swaps in that program's projects and their toolbar.
**All colleges** and the college code both go back up a level.

**There is no cross-college program or project list in the flow.** Programs are DERIVED from a
college's projects (`programs` has no college_id), and the drill-down is College → Program → Projects
→ Activities — see `revisions.md` §23.

**There is no drill-down rail** — it was removed by an owner decision (`revisions.md` §11.6). Do not
expect one.

---

## 1. Orientation — look at what already exists

Before creating anything, walk the seeded data. This is also the fastest way to show the hierarchy.

1. **Manage Extension Programs** → the four college cards. Each shows only **derived roll-ups** of
   its projects: Projects / Programs / Faculty, plus beneficiaries reached and budget utilized.
   **There is deliberately no per-college training-hours figure** — a college has no target (§2.2B /
   D-R5). The same rule explains why the cards carry no "% of target".
2. Click **CME** → view 2: the college hero, 4 derived KPIs, its projects, and its faculty.
3. **View all programs →** → `/programs`, the **broad** level. Six CESO thrusts. Note the **D-R5**
   notice: programs are a grouping level and carry no training-hours target. Every number on the page
   is a roll-up of the projects beneath the program.
4. **View all projects →** → `/projects`, the **narrow** level. Seven seeded projects. Codes are
   college-prefixed for new projects (`CAS-2026-001`) while the six inherited ones keep their legacy
   `EXT-2026-00X` codes.
5. Open **CAS-2026-001**. Tabs: **Overview · Activities · Beneficiaries · Budget**.

**Expected on CME-2026-001 · PAGKAON** (seeded — the safe-zone set, `revisions.md` §31):

| Figure | Value |
|---|---|
| Training hours rendered | **108** of an annual target of **150** → **72 %** |
| Trainors / Trainees | 2 / 20 |
| Activities | 3 |
| Allocated budget | **₱55,000** — the ALLOCATION is the budget basis (v4.19); there is no annual budget target |

**What must NOT be here:** any objectives list, KPI dictionary, or results-framework panel. Every 8.6
KPI surface was removed from the project hub and the dashboards (D-R7). If you see one, that is a
regression.

---

## 2. Create a project — `/projects` → **New Project**

| Field | Value |
|---|---|
| College * | **CME** |
| Program * (the broad level) | Livelihood, Technical & Business Management |
| Project title | `PANADERO: Barangay Bread & Pastry Livelihood Training` |
| Description | Hands-on bakery training for out-of-work mothers in Sagkahan. |
| Planned start / end | 2026-09-01 / 2026-12-31 |
| Target beneficiaries | 5 |
| Allocated budget | 30000 |
| Target training hours | 40 |
| Project lead | Kent Naputo |
| Status | Ongoing |
| Linked communities | Brgy. Sagkahan · Tacloban City |
| Beneficiary categories | Parent |

> Use a title that is **not** already seeded (`KULTURA:`, `NUMERO:`, `LINIS:`, `PAGKAON:` and
> `DIGITAL:` are the five LIVE projects; the eight originals — including `BUSOG:`, `LITRAWIYA:` and
> `HANDA:` — are ARCHIVED, `revisions.md` §31). A name that collides makes the demo confusing — two
> rows with the same prefix.

**Expected:** a success toast with the auto-generated code **`CME-2026-002`** — the prefix comes from
the college you picked (R-Q4), which is why the college is the *first* field. ⚠️ **`CME-2026-001` is
already taken** by the seeded PAGKAON project, so the next CME code is 002. The project appears in the
grid with an **Ongoing** badge.

> The code is generated **after** the college is known. Picking a different college gives a different
> prefix; the sequence is per college per year.

---

## 3. Add 2 activities — hub → **Add Activity**

The activity form is where the training-hours model lives. **`Days` carries the duration** — `0.5` is a
half day — and there is **no `× 8`**: the formula is `trainors × trainees × days` and `days` already
expresses the time.

| Activity | Date | Time | Days | Participants | Assigned faculty | Alloc. budget | Status |
|---|---|---|---|---|---|---|---|
| A1 · Dough Basics & Food Safety Orientation | 2026-10-20 | 08:00–12:00 | **1** | 5 | Kent Naputo **+** Nikko Villas | 6000 | Ongoing |
| A2 · Baking Practicum: Pan de Sal & Ensaymada | 2026-10-27 | 08:00–12:00 | **0.5** | 5 | Kent Naputo **+** Nikko Villas | 9000 | Ongoing |

> **Why these dates.** The seeded demo data already books the faculty, and the 8.8 guard is a **hard
> block** — it will refuse an assignment that overlaps an existing one. Nikko Villas leads **LINIS ·
> CAS-2026-003**, whose three activities fall on **2026-05-18**, **2026-08-22** and **2026-11-27**, so
> an activity on one of those dates would be refused. October 20 and 27 are clear for both
> facilitators. If you pick your own dates, expect the guard to name the clashing activity — that is
> the feature working, not a bug.

**Expected training hours, derived live:**

| Activity | Arithmetic | Hours |
|---|---|---|
| A1 | 2 trainors × 5 trainees × 1 day | **10** |
| A2 | 2 trainors × 5 trainees × 0.5 days | **5** |
| **Project total** | | **15** of 40 → **37.5 %** |

**Also check:**
- The date pickers are constrained to the project window (2026-09-01 → 2026-12-31, §8.8). Typing a
  date outside it is blocked.
- `Days` rejects anything below a half day, and rejects values that are not half-day increments.
- Clearing **Participants** writes NULL, not 0 — an absent number is not the same as zero.

> **Two things that surprise people here, both by design.**
>
> 1. **`Participants` is the trainee fallback, not the reach.** Until attendance is imported (step 5)
>    the hours use `Participants` (R-Q1). The project's **Trainees** *tile* is a different number — a
>    **distinct beneficiary count** from attendance — so it reads **0** until step 5.
> 2. **Imported attendance REPLACES the fallback.** That is why `Participants` is set to **5** here,
>    matching the five beneficiaries registered in step 4: the hours then stay at 15 before *and*
>    after the import, instead of silently changing when attendance lands.

---

## 4. Register 5 beneficiaries — hub → **Beneficiaries** tab → Register new (×5)

All with Barangay **Sagkahan**, Municipality **Tacloban City**, Category **Parent**:

| Name | Age | Sex |
|---|---|---|
| Maria Santos | 42 | Female |
| Josefina Bautista | 38 | Female |
| Rogelio Lim | 45 | Male |
| Analyn Custodio | 29 | Female |
| Eduardo Navarro | 51 | Male |

**Expected:** each row is created and auto-enrolled into the project; the tab shows **5 enrolled**.

---

## 5. Import attendance — **Activities** tab → **Records** → Attendance

Attendance is **import-only** (v4.13) through the official template — there is no manual grid.

1. **Download official template.** The enrolled roster is pre-filled with Beneficiary ID, last/first
   name and barangay.
2. Fill the **Status** column (Present / Absent / Excused / Late):
   - **A1** — all 5 → **Present**
   - **A2** — Maria, Josefina, Rogelio, Eduardo → **Present**; Analyn → **Late**
     *(late still counts as served — the trainee count is present + late)*
   - Leave a cell **blank** to leave that beneficiary unrecorded.
3. **Upload** the file → **Parse file →** → review the per-row preview (Apply / Blank / Skip, with
   per-row errors) → **Confirm & import attendance**.

**Expected:** a confirmation toast with applied / blank / error counts, and attendance counts under
each activity row. Re-importing the same file **updates** rather than duplicating.

> The **Evaluation** tab in the same modal imports pre/post/satisfaction rows and averages them into
> the activity's aggregate columns. Optional here.

**Important:** a beneficiary who attends three sessions is still **one** person reached.
`trainees_reached` is `COUNT(DISTINCT beneficiary_id)`, never a sum of per-activity counts.

---

## 6. Add 3 budget entries — **Budget** tab

| Item | Amount | Date used | Charge against |
|---|---|---|---|
| Ingredients & baking supplies | 6000 | 2026-10-20 | A1 |
| Oven rental & utilities | 5000 | 2026-10-27 | A2 |
| Packaging & labelling materials | 2500 | 2026-10-27 | A2 |

**Expected:** utilized **₱13,500** of the **₱30,000** annual target (45 %). No warnings — comfortably
under the allocation.

---

## 7. Complete A1 and A2 — **Activities** tab → **Complete**

**Expected per completion:**
- The status badge flips to **Completed**, and an **activity-log** entry is written (visible in the
  admin dashboard's recent activity).
- **Rendered-hours drafts are auto-created for each assigned faculty member.** The draft is
  `end_time − start_time` (A1 → 4 hrs, A2 → 4 hrs), source = `auto`.
- Log in as `faculty4@lnu.com` (Kent) → the bell shows *"Rendered hours drafted"* → **Rendered Hours →
  My** lists two pending drafts.

**The lifecycle, which is the point of this step:** a faculty member may adjust a draft **down only**,
then submit. The Admin approves, and the entry **locks** — a later edit attempt is refused. Hours are
never editable upward, because that would let service credit be invented.

---

## 8. Targets — two levels only

### 8a. The project's own target — hub → **Overview**

| Figure | Expected |
|---|---|
| Training hours vs annual target | **15 / 40 → 37.5 %** |
| Budget vs allocated budget | **₱13,500 / ₱30,000 → 45 %** |
| Trainors / Trainees / Activities | **2 / 5 / 2** |

The **Trainees** figure is now **5** — it was 0 before step 5, and the imported attendance is what
made it real. Note that it counts **people**, not attendances: the same five beneficiaries attended
both activities, and they are still five.

### 8b. The university pool — **University Targets**

The university target is a **consumption pool**, not a ratio: the projects draw from it.

**Expected:** the seeded pool is **2,500 hours / ₱668,000** for the academic year, with the year's
projects consuming a small share of the hours and a larger share of the budget. The page also lists
each project's own attainment and the "Training Hours Formula" panel spelling out
`trainors × trainees × days`.

**The rule to state out loud:** targets exist at **University** and **Project** level **only**.
Broad programs and colleges carry none — that is D-R5, and it is why `/programs` and the college cards
show roll-ups but never a percentage of target.

---

## 9. What you should now see

| Where | Expected |
|---|---|
| Hub → view 2 (CME) | The new project in the card grid; CME's roll-ups have risen by its figures |
| `/projects` | the **5 live** projects (8 more archived); the new one shows `CME-2026-002`, Ongoing |
| `/programs` | The Livelihood program's project count and rendered hours have risen — **but its target column does not exist** |
| Project hub → Overview | 15 / 40 hrs · ₱13,500 / ₱30,000 · 2 trainors · 5 trainees |
| `/targets` | The university pool's consumption includes the new project's hours |
| `/faculty` | Kent's and Nikko's activity counts have risen by 2 |

Everything derives **live** from the recorded activities on every render. Nothing was "saved" into a
status column.

---

## 10. The AI surfaces — admin only, and now guarded

**Prerequisite:** `GEMINI_API_KEY` must be set in `.env`. Without it the AI calls fail; nothing else
on this page depends on it.

- **AI Analysis Review** — one AI-assisted analysis per community summary, subject to Admin approval.
- **Project Narratives** — the Admin-only executive summary per project.
- **Interagency Catalogue** — the citable agencies (8 seeded). This is the *only* source of agency
  names the AI may cite.

**The three-tier rule (§7.1) — the thing the adviser asked for:**

| Tier | Meaning |
|---|---|
| **1** | CESO can deliver this itself → recommended as CESO intervention |
| **2** | Not CESO's work → **referred** to a named agency from the catalogue (malnutrition, roads, water potability, …) |
| **3** | Prohibited → shown as an audit note only, never as a CESO recommendation |

**The point:** the AI no longer silently drops a need it cannot serve. It reclassifies it as a Tier-2
referral with a named agency. Check that a Tier-2 card carries a *"Refer to <agency>"* note and that a
Tier-3 item never appears as a recommendation.

**Secretary and faculty have zero AI surfaces** (D4). Log in as either and confirm the AI items are
absent from the sidebar entirely — not merely disabled.

---

## 11. Deadline notifications — the scheduler

```bash
php artisan smartcemes:notify-deadlines
```

**Expected:** the Admin's bell receives notifications for projects ending within 14 days, and for
projects past their midpoint whose rendered hours are behind their annual target.

Re-run it → **no new notifications** (7-day de-duplication).

> The count depends on the seeded projects' end dates, so treat the exact number as environment
> dependent; what matters is that the second run is a no-op.

The nightly database backup runs on the same scheduler at **02:00**:

```bash
php artisan smartcemes:backup-database
```

**Expected:** `Backup written: …storage/app/backups/smartcemes_<db>_<timestamp>.sql (… KB)`, and older
dumps pruned beyond the retention count. If it fails with *"'mysqldump' is not recognized"*, set
`DB_DUMP_BINARY` in `.env` (on this machine: `C:/xampp/mysql/bin/mysqldump.exe`).

---

## 12. Optional bonus tests

- **8.8 conflict hard-block** — add an activity dated **2026-08-22** (the date of LINIS's *Composting &
  Materials Recovery Training*, `CAS-2026-003`) and assign **Nikko Villas** → the save is **refused**
  with a message naming the clashing activity and its date range. This is a hard block, not a warning.
  ⚠️ **Moved 2026-10-07.** This demo used to book against BUSOG's *Feeding Cycle 2* (2026-09-08 →
  2026-10-16). BUSOG is now **ARCHIVED**, and archiving takes its activities with it, so that booking
  no longer exists and the guard would not fire. LINIS is also led by Nikko Villas and is LIVE.
- **D7 over-allocation** — add a budget entry of ₱40,000 → the entry **saves** with an
  over-allocation warning banner plus an activity-log entry. It never blocks.
- **Faculty read-only** — log in as `faculty1@lnu.com` → **My Projects** → the hub renders read-only
  (no Manage/Edit/Add Activity buttons). Faculty see their own projects, not the whole registry.
- **Rendered-hours lock** — as Kent, adjust a draft **down** (e.g. 4 → 3 hrs with a note) and submit;
  as Admin, approve it; try editing again as Kent → refused.
- **Secretary scope** — log in as `secretary@lnu.com` → no AI surfaces, no college/program/project
  management, no Faculty Management. Secretary validates and manages beneficiaries.
- **Nav collapse** — as Admin, confirm the sidebar shows **one** extension entry
  (*Manage Extension Programs*) and that it stays highlighted on `/colleges`, `/programs`,
  `/projects` and a project hub. The topbar keeps reading *Manage Extension Programs* throughout.
- **The prototype harnesses** (if you are reviewing the frontend contract, not the app):
  `node docs/prototype/_check.cjs` and `node docs/prototype/_smoke.cjs`.

---

## 13. Reset between runs

```bash
php artisan migrate:fresh --seed
```

Reproducible: 58 communities/schools, 4 colleges, 6 broad programs, **5 live projects** (the 8
originals are archived and restorable), 15 activities, 6 faculty, 114 beneficiaries, 336 attendance
rows, 8 interagency agencies, 1 university target. Eight accounts, all with password `password`.

> ⚠️ **Changed 2026-10-05 (`revisions.md` §31).** This line used to read *"8 projects, 15 activities,
> 57 beneficiaries, 130 attendance rows, 23 budget rows"*. `SafeZoneProjectSeeder` now archives the
> eight originals and creates the five safe-zone projects (KULTURA · NUMERO · LINIS · PAGKAON ·
> DIGITAL), so the LIVE demo set and its figures are different. Re-seed to reproduce them exactly.

> **Use this, not a plain `migrate`.** A plain `migrate` on a populated database leaves the hierarchy
> **orphaned without erroring** — the backfill needs `colleges`/`programs` *rows*, but `migrate` only
> creates those tables empty. See `AI_HANDOFF.md` §14.

---

## Appendix A — the seeded demo data

Handy when you want to demo without creating anything.

| Code | College | Broad program (thrust) | Lead | Rendered / target |
|---|---|---|---|---|
| `CAS-2026-002` | CAS | Cultural Development | Dr. Cristina P. Manalo | 84 / 100 hrs |
| `COE-2026-001` | COE | Literacy, Numeracy & Language | Dr. Ramon L. Villamor | 90 / 120 hrs |
| `CAS-2026-003` | CAS | Environmental Conservation & DRP | Nikko Villas | 70 / 100 hrs |
| `CME-2026-001` | CME | Livelihood, Technical & Business | Kent Naputo | 108 / 150 hrs |
| `CAS-2026-004` | CAS | Information, Communication & Education | Carlo Sumile | 48 / 80 hrs |

All five carry an `allocated_budget` and an `annual_target_hours`, and every one is **under** its
target — so the D7 over-allocation warning stays quiet on a fresh seed.

**The eight originals are ARCHIVED**, not deleted: `EXT-2026-001`…`EXT-2026-006`, `CAS-2026-001`
(BUSOG) and `GRAD-2026-001` (PANDAY). They are soft-deleted, restorable from the hub's project view,
and carry no annual targets — they predate the R4 model.

> ⚠️ **Physical Fitness & Sports Development is the one thrust with no live project.** Archiving BATANG
> MATINIK moved the gap Cultural Development used to have. `FreshSeedHierarchyTest` asserts it rather
> than hiding it; a sixth project would close it (`revisions.md` §31.5).

---

## Appendix B — how this script was verified

- **`tests/Feature/DefenceWalkthroughTest.php` executes the write flow** (steps 2, 3, 6, 7, 8) through
  the real Livewire components and asserts every figure printed above: the generated project code, the
  `2 × 5 × days` arithmetic, 15 / 40 → 37.5 %, ₱13,500 / ₱30,000 → 45 %, the 4-hour rendered-hours
  drafts, and the `trainees` count moving 0 → 5 only once attendance exists. If a model change breaks
  a number in this document, that test fails.
  ⚠️ **The code is asserted as a PATTERN plus "not the seeded code"**, not as the literal
  `CME-2026-001` — that literal now belongs to the seeded PAGKAON project, so a hardcoded assertion
  would break on every re-seed (`revisions.md` §31.4).
- **Read-only observations (§1, §8b)** were executed against the seeded MariaDB on 2026-09-24 through
  a real admin login — the figures quoted are the values the pages actually rendered.
- **Rule-level behaviour** the walkthrough depends on but does not itself drive is covered elsewhere:
  `ActivityAttendanceImportTest` (the import UI), `TrainingHoursTest` (the formula, half days, the
  trainee fallback, distinct counting), `ProgramHubTest` (the hub, budget, completion),
  `CollegeProgramCrudTest` (project codes + the collapsed nav), `ExtensionHubTest` (the hub and the
  D-R5 rules), `DatabaseBackupTest` (the backup), `RankingServiceTest` (the roll-ups).
- **The backup (§11)** was run for real and its dump restored into a scratch database with row counts
  identical to live.
- ~~`docs/guides/*` and the role guides are **still bannered as pre-revision**~~ — **✅ corrected
  2026-10-07: that claim was WRONG.** The 11 `docs/guides/*` walkthroughs were renamed and rewritten
  for the revision (`01-create-project.md`, `02-targets.md`, …), and `adminguide.md` / `features.md` /
  `01-create-project.md` were corrected again on 2026-09-28 for the Analytics removal, the hub's
  create/edit move and the retired target-budget field. **This script no longer "replaces" them** —
  read them. The same over-claim was corrected in `README.md` (`AI_HANDOFF.md` §16 E).
- ⚠️ **This script was itself the trailing document** (`AI_HANDOFF.md` §16 H): until 2026-10-07 it
  described the pre-§31 demo — archived projects, a retired `Target budget (₱)` form field, and a
  conflict demo booked against an archived activity. **The `DefenceWalkthroughTest` was current; the
  prose was not.** Re-checked against the seeder and the live DB on 2026-10-07.

### Bugs this walkthrough re-run found

Worth knowing, because they were invisible to a green test suite and would have surfaced live:

1. **The 8.8 faculty-conflict hard-block was broken twice over.**
   - `Hub::findScheduleConflict()` filtered `->where('id', '!=', …)` on a relation that joins
     `activities` to `activity_faculty` — and the pivot has its own `id`, so the column was
     **ambiguous** and the query errored on every driver. Qualifying it (`activities.id`) fixed it.
   - The refusal message had **four `%s` placeholders and three arguments**, so the guard raised
     `ArgumentCountError` instead of refusing. It now names the clash and its date range.
   - Net effect before the fix: assigning faculty to an activity crashed rather than saving. Neither
     bug was reachable from the existing tests, because they all used faculty with no prior
     activities.
2. **The `/projects` create button still said "New Program"** (plus the list header and the submit
   button) — an R2 rename leftover, and exactly the naming confusion the handoff warns about.
