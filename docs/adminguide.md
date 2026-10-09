# SmartCEMES — ADMIN (Director) Testing Guide

_You are testing as the **Admin / CESO Director** — the only role that manages the
extension structure, approves workflows, and uses the AI features. Work top to bottom.
Each step tells you what to click and what you SHOULD see._

> **The hierarchy is `College → Program → Project → Activity`.** A **Program** is one of the six
> broad CESO thrusts; a **Project** is the thing with dates, a budget, activities and targets.
> Where an older note says "program" and means the narrow entity, it means a *project*.
>
> **Targets exist at two levels only — University and Project** (D-R5). Broad programs and colleges
> carry none; their figures are derived roll-ups of the projects beneath them. **Budget has no annual
> target at all** — a project's **allocation** is its only budget figure (v4.19).
>
> **Training hours are `trainors × trainees × days` — there is NO `× 8`.**
>
> **The admin sidebar collapses the hierarchy into ONE entry** — *Manage Extension Programs* — which
> opens the colleges hub. Colleges, Programs and Projects are reached from inside that hub, never as
> separate sidebar items.
>
> **The 8.6 KPI dictionary and the results framework were REMOVED from the UI** (D-R7).
> `KpiService` and `ProgramObjective` remain in the codebase but are **unread** (R-Q2). If you see an
> objectives panel or a KPI scorecard, that is a regression.

## Getting in

1. Open **https://disorder-childless-moonlike.ngrok-free.dev**
2. If ngrok shows a warning page, click **Visit Site** (normal for free ngrok).
3. Log in:
   - **Email:** admin@lnu.com
   - **Password:** password

**Your sidebar (you see everything):**

| Section | Items |
|---|---|
| Overview | Dashboard · University Targets · Calendar |
| Extension Programs | **Manage Extension Programs** (one entry — it opens the hub) |
| Management | Faculty Management · Communities & Partner Schools |
| Approvals | Proposals · Availability Requests · Rendered Hours |
| Intelligence & Reports | AI Analysis Review · Project Narratives · Interagency Catalogue · Reports · Audit Logs |

---

## 1. Dashboard

1. After login you land on the Dashboard. Read it top to bottom.

**Expected — the KPI row** (four tiles): **Extension Projects** · **Training Hours Rendered** ·
**Budget Utilized of ₱…** · **Pending Approvals**.

2. Scroll to **Trends → Hours & budget against target**. Two panels:
   **Training Hours vs Target** (rendered vs each project's annual *hours* target) and
   **Budget Utilized vs Allocated Budget** (compact bullet rows — one per project, labelled with its
   **title**, a bar with a tick at 100%, over-allocation in red).

**Expected:** the budget note reads *"% of the allocated budget"*. If it says "Annual Target",
that is a regression — budget has no annual target (v4.19).

3. Scroll to **Performance → Most Performing Projects / Most Performing Faculty**, then
   **Intelligence → Community Insights Queue / Project Narratives**.

4. Click the **bell icon** (top header) — you may see deadline notifications.

**Expected:** a notification dropdown opens; entries can be marked as read.

> **There is NO action center and NO recent-activity panel on the dashboard.** The action center was
> removed in R5; the recent-activity feed moved to its own **Audit Logs** page (v4.18). Four rows
> could never answer "who changed this, and when". Pending work survives as the **Pending Approvals**
> KPI tile.

---

## 2. Create a project (college-prefixed, race-safe codes)

1. Sidebar → **Manage Extension Programs**. The hub opens on the **college cards**
   (CAS · COE · CME · GRAD — a fixed, read-only set of four).
2. Click a college card → its projects appear. Click **View all projects →** to reach the full list.
   (Shortcut: `/projects` directly.)
3. Click **New Project** and fill in: **College** (this decides the code prefix), **Under program**
   (the broad CESO thrust), title, description, planned start/end dates, target beneficiaries,
   **allocated budget**, **target training hours**, project lead, linked communities via the
   searchable multi-select, and beneficiary categories. Set Status and save.

> **Note there is no "target budget" field.** A project has ONE budget figure — its allocation. That
> is what utilization is measured against (v4.19). There *is* a target-training-hours field, because
> hours genuinely have an annual target.

**Expected:** a success toast showing an auto-generated, **college-prefixed** code — e.g.
`CAS-2026-002`, or `CME-2026-001` if you picked CME. Codes are atomically sequenced **per college per
year**, so duplicates are impossible and two colleges never share a sequence.

4. The new project appears in the list with its status badge.

---

## 3. Project hub deep-dive — open **EXT-2026-001 LITRAWIYA**

From the Projects list, click the project. The hub has four tabs:
**Overview | Activities | Beneficiaries | Budget**.

### 3.1 Overview — the performance block

1. Read the four **stat tiles** across the top: **Trainors · Trainees · Training Hrs · Activities**.
   Every figure is a live roll-up of the project's activities — nothing is stored.
2. Below them are two cards. Left: **Training Hours vs Annual Target** — four bars (hours rendered,
   trainors assigned, trainees reached, activities completed) plus **Hours remaining**,
   **Avg hours / activity** and **Training days**. Note the formula under the heading:
   `trainors × trainees × days`, with **no × 8** — `days` carries the duration (0.5 = a half day).
3. Right: **Budget vs Allocated Budget** — a **doughnut** of Allocated / Utilized / Remaining. It is
   the Overview's **only** budget surface: the old Budget tile and the key/value list that sat under
   this chart were removed on 2026-09-28 as duplicates of it.

**Expected (LITRAWIYA, the project this walkthrough opened):** **4 of 640** training hours (0.6 % of
the annual target), **₱31,500 of ₱48,000** utilized (65.6 %, not over), 1 trainor, 2 trainees,
3 activities. The ₱48,000 is its **allocation**, not a target — since v4.19 a project has no
annual budget target, and utilization is measured against the allocation.

**Read the doughnut honestly.** Its three slices do **not** partition one whole —
`Allocated = Utilized + Remaining`, so the ring sums to twice the allocation and the arc widths
overstate the utilized share. The **centre label** and the **legend** carry the true share **of the
allocation**; read those, not the arcs.

4. Compare with **BUSOG (`CAS-2026-001`)** — Sidebar → **Manage Extension Programs** → the **CAS**
   card → the BUSOG card.

**Expected:** BUSOG shows **134.5 of 150 training hours (89.7 %)** and **₱78,000 of ₱85,000**
(91.8 % of allocation, not over), with the doughnut centre reading **91.8 %** in agreement with the
badge beside the heading. The university pool these projects draw from is on the
**University Targets** page (§4).

5. See the over-allocated case: open **HANDA (`EXT-2026-002`)**, deliberately over-allocated by
   ₱2,000 to demo D7. Its Remaining is **negative**, which no doughnut can draw — so the slice is
   clamped to 0 and the overage is carried by the **red centre** ("103 % · over allocation"), the red
   badge and the amber banner. That is the intended reading, not a broken chart.

**The NULL-over-0 discipline:** an absent hours target is not a target of zero. All eight seeded
projects now carry one (2026-09-28), so demonstrate the rule deliberately — click **Edit**, clear
**Target training hours (annual)**, save: the Training Hrs tile then reads "no annual hours target
set" and the card badge "No target set", rather than a misleading 0 %. Put the value back afterwards.

**There is NO objectives list, NO results framework and NO KPI dictionary on this page.** They were
removed from the UI (D-R7) and replaced by the target model.

### 3.2 Activities tab — scheduling hard-block + records import

1. Click **Add Activity**; assign a faculty member; pick a date/time that **overlaps** an existing
   activity of the same faculty (check the existing rows first); save.

**Expected:** save is **REFUSED** with a clear conflict message (e.g.
"Assignment refused: … is already assigned to '…' which overlaps this schedule."). No
warn-and-confirm — it is blocked.

> Seeded bookings collide with naive dates: **Nikko Villas** is committed to *Feeding Cycle 2* from
> **2026-09-08 → 2026-10-16**, so a September activity assigned to him is refused by this (correct)
> rule. Pick October to demonstrate a successful assignment, or use the September window to
> *demonstrate the block*.

2. Click **Records** on an activity → **Attendance** tab.

**Expected:** an upload step with a **Download official template** link (the enrolled roster is
pre-filled) and a file picker. Attendance is **import-only** (v4.13) — the manual attendance panel
was removed: download → fill the Status column (present / absent / excused / late; blank = leave
unrecorded) → upload → **Parse file →** → review the per-row states → **Confirm & import
attendance**. A toast reports the applied/blank/error counts.

3. Switch to the **Evaluation** tab of the same modal.

**Expected:** the same flow for pre/post/satisfaction rows, **averaged into the activity's aggregate
scores** (D13 — there is no per-beneficiary evaluation table). A metric with no values in the file
leaves the existing aggregate untouched.

> **The project's Trainees tile is a distinct-beneficiary count from attendance**, so it reads 0
> until attendance is imported. And imported attendance **replaces** the manual `participants`
> fallback — so computed hours can *drop* if attendance lands for fewer people than `participants`
> claimed.

### 3.3 Beneficiaries tab — dedup warning + import

1. Click **Register new**; type a first/last name and barangay that matches an existing beneficiary
   (look at the table first), then save.

**Expected:** a **de-duplication warning** appears and requires explicit confirmation — never a
silent merge.

2. Note the **Import XLSX** button (bulk import, 500 rows max, per-row Import/Duplicate/Skip preview)
   and **Enroll existing** (search + enroll from the global registry). Try either if you like.

### 3.4 Budget tab — over-allocation warning (D7)

1. Go back to the Projects list and open **EXT-2026-002 HANDA** instead. It is seeded
   **over-allocated by ₱2,000** (₱64,500 utilized against a ₱62,500 allocation = 103.2 %).

**Expected:** a persistent **over-allocation warning banner** + badge on the project. Overrun never
blocks saving — it warns and writes an audit-log entry. The banner says the project "exceeded its
₱62,500 allocation".

2. In any project's Budget tab, add an expense entry (item, amount, date, optional activity). The
   utilized total and progress bar update live.

---

## 4. University Targets

1. Sidebar → **University Targets**.

**Expected:** one annual pool per academic year — **training hours and budget**. It is a
**consumption pool**, not a ratio: each project's *actual* hours are subtracted from it, and
`remaining = max(target − Σ actuals, 0)`. Project-level targets are deliberately **not** summed into
the pool (that would double-count).

2. Note the note on the page: broad programs carry **no** target of any kind.

**Expected:** the seeded AY 2026–2027 pool is **2,500 hrs / ₱668,000**. The budget pool is a real
commitment the Director sets — unlike the per-project budget figure, which is simply the allocation.

---

## 5. Faculty Management

1. Sidebar → **Faculty Management** — the **Engagement board** (a chart, ranked rows, and a college
   split, driven by a metric switch).

**Expected:** every figure is **contribution-based** (hours rendered, projects led, activities
handled, proposals). **There is NO per-professor target and therefore no attainment %** anywhere on
a faculty surface — a percentage there would be invented (D-R19).

2. Click **Faculty Directory** (in the board header) — the roster with filters and search.

3. Click a faculty member → their **profile**: the full contribution blocks (§6's eight blocks).

**Expected:** position, college and status are **Admin-controlled**; specialization, department,
contact details and expertise areas are the faculty member's own to edit, and their edits are recorded
in the audit log (`faculty_self_update`). Employee ID and the login account (name, email) are yours.

---

## 6. Proposals — approval workflow

Go to **Proposals**. Seeded states: 3 pending, 1 approved, 1 rejected.

1. Open **GULAYAN SA PAARALAN** (pending, from Bianca).

**Expected:** detail modal shows downloadable attachments (click one — a real PDF downloads), budget,
dates, and a Special Order block.

2. Click **Approve** — optionally attach a Special Order PDF (skipping is fine; an informational
   notice shows), then confirm.

**Expected:** toast says an **activity was auto-created (draft)** under the target project, copying
title and dates. The faculty gets a notification.

3. Open **SIKAD BUHAY** (pending, from Carlo) and click **Approve**.

**Expected:** **BLOCKED** — its proposed dates (Dec 15, 2026 – Jan 10, 2027) fall outside the parent
project's range (scheduling is a hard constraint; the server is the backstop). If it approves
instead, the demo DB was seeded before this demo row was fixed — re-run
`php artisan migrate:fresh --seed`, or use the out-of-range example in
`docs/guides/08-proposal-workflow.md` for a guaranteed block.

4. Open **TESDA-Ready** (rejected).

**Expected:** the recorded rejection reason is shown.

5. Try the **Reject** path on a pending proposal.

**Expected:** a reason is **required (minimum 10 characters)** — confirming empty is blocked.

---

## 7. Availability requests

1. Go to **Availability Requests**.

**Expected:** the list shows requests in every state (pending, accepted, declined-with-reason) — all
**admin-initiated**.

2. Create a **new request**: pick a faculty (use the account a friend is testing with — see the
   faculty guide), an activity, date/time (defaults from the activity schedule), and remarks.

**Expected:** the request is created as **pending** and appears in that faculty's queue +
notifications. Their accept/decline updates this list. Declining requires a reason.

---

## 8. Rendered hours — approval & lock

1. Go to **Rendered Hours**.

**Expected:** an approval queue with **pending** entries (seeded: Bianca 2.5 hrs with an adjustment
note, Nikko 7.0 hrs, Carlo 4.0 hrs — minus any already approved by other testers).

2. Click **Approve** on a pending entry.

**Expected:** toast confirms the entry is **locked**. Approved entries are immutable and
audit-logged — faculty can never edit them again.

3. Try **Reject** on another entry.

**Expected:** remarks are **required** before it can be rejected.

> **Rendered hours are faculty SERVICE CREDIT and a different concept from TRAINING hours
> delivered.** The two are named distinctly everywhere on purpose — do not conflate them.

---

## 9. Analytics — REMOVED (blueprint v4.20)

> **There is nothing to click through here any more.** The Admin Analytics page, its six tabs, its nav
> entry and its prototype page were deleted by owner decision on 2026-09-27 (`revisions.md` §21).
> `/analytics` no longer resolves. **Skip this number** — it is kept only so the sections below keep
> their familiar numbering.
>
> Two figures the page owned were **knowingly lost, not relocated**: the aggregate **Pending Actions**
> list (the dashboard keeps counts only, and the four queues keep their own pages) and the **Community
> Reach** barangay chart. Do not go hunting for them on another page.
>
> Where the cross-project numbers went instead: the **hub** (`Manage Extension Programs`) for anything
> per-college or per-program, and **University Targets** (§4) for the university pool.

---

## 10. Calendar

Go to **Calendar**.

**Expected:** a month grid combining **three feeds** — activities, ACCEPTED availability requests,
and **project** deadlines. Click a day — the side panel lists its events; a 60-day upcoming list is
below. Where events overlap in time, they carry red **conflict** markers. Navigate months; "today" is
ringed.

---

## 11. Reports (print views)

1. Go to **Reports** — four institutional reports: **Annual Extension Performance**,
   **Project Performance**, **Faculty Rendered Hours**, **Community Partner Impact**.
2. Open one and press **Ctrl+P**.

**Expected:** a clean print preview with the LNU letterhead (screen chrome is hidden when printing).

> The Project Performance report's ROUTE is still named `reports.results-framework` — a legacy name
> kept so old bookmarks do not break. Its **content** is the target model, and its table columns read
> "Allocated budget" / "Utilization". PDF/Excel export is deliberately future work (D6) — printing is
> the MVP path.

---

## 12. AI Analysis Review (community insights) — admin only

The page is a **QUEUE**, not a form: it lists every analysis this office has generated,
grouped by community, plus every validated summary still awaiting one. **There is no
summary picker any more** — you generate from the row that says a summary is awaiting
analysis.

1. Go to **AI Analysis Review** (`/ai-analysis`).
2. Find the community — type a name in the **search** box (partial and
   case-insensitive: `san jo` finds *Brgy. San Jose*), or narrow with the filter chips
   (*Awaiting review · Approved · Failed · Discarded · Awaiting analysis*) — then click
   **Generate** on the row marked **awaiting analysis**.

**Expected:** the button shows a spinner (generation is **synchronous** — results appear
immediately) and you land on **that analysis's own page**, where a **draft** appears with:
situation summary, **priority needs**, **top recommended interventions**, and a confidence
chip (derived from data coverage — not the model bragging). If the AI service fails, the
row turns **failed** with a readable reason and a **Retry** button — that is also correct
behavior.

> **Community pages.** Every community name in the queue is a **link** to its own history
> (`/ai-analysis/community/{id}`): every period, every generation, with Generate, Retry,
> **Delete** and a bulk **Clear failed**. Deleting is **scoped** — an **approved** analysis
> (citable in reports) and the **live draft** cannot be deleted.

> **Reading any analysis.** Each row opens its own page in **any** state — a draft, an
> approved one (read-only, citable), or a discarded one. An approved analysis used to have
> no reading surface at all; that was the point of the split (`revisions.md` §33).

3. Open the **Community response data** accordion on the draft.

**Expected:** the EXACT aggregate payload sent to the AI — counts, percentages, distributions.
**No respondent names/PII ever leave the system** (this is the data-privacy audit view).

4. Look for the **three-tier scope guardrail** (D-R8).

**Expected:** every recommendation is classified before display — **Tier 1** CESO can deliver it ·
**Tier 2** an interagency referral naming a real agency from the catalogue (never an invented one) ·
**Tier 3** prohibited as CESO work, surfaced as an audit note only. A need CESO cannot serve is
**referred, never dropped**. Malnutrition, roads, water potability etc. appear as Tier-2 referrals.

5. Click **Approve** (or Discard).

**Expected:** approving stamps you as the approving officer; the history table shows model, prompt
version, and approver (full provenance).

---

## 13. Project Narratives (executive summaries) — admin only

1. Go to **Project Narratives**.
2. Find **EXT-2026-001 LITRAWIYA** and click its **Generate** button (also available inside the
   project hub as "Generate narrative").

**Expected:** an executive summary with a **health label** (On track / At risk / Needs attention),
top risks, and recommended next actions — graded against the project's annual **hours** target and
its **allocation**, not against retired KPIs.

> There is **no "Objectives met: X/Y" chip** any more. It was removed with the results framework
> (D-R7); the narrative is now graded on the target model.

3. Generate again for the same project.

**Expected:** a **new version** is created — nothing is overwritten (browse the version-history
timeline). Narratives have **no approval gate**: they are Director-internal decision support, with
provenance still recorded.

---

## 14. Interagency Catalogue

1. Sidebar → **Interagency Catalogue**.

**Expected:** the closed list of agencies the AI is allowed to cite in Tier-2 referrals — agency
name, code, mandate, need category, sample service, contact, and active flag (10 seeded).

2. Click **+ Add agency**, then try saving it **incomplete**.

**Expected:** an incomplete agency is **rejected, not saved**. This catalogue is the only source of
citable agencies — the AI may not invent one.

---

## 15. Audit Logs

1. Sidebar → **Audit Logs**.

**Expected:** a plain, reverse-chronological list, 25 per page, using the shared paginator. It is
**read-only by design** — there is no write action anywhere on the page.

---

## 16. Communities & Partner Schools

1. Go to **Communities & Partner Schools**.

**Expected:** a list (58 seeded records). Filter chips: **All / Communities / Partner Schools**.
School rows have a gold document icon + level label (elementary/secondary/higher ed).

2. Click a row — the detail modal opens (schools show Level/Principal instead of
   beneficiaries/programs/needs-history). Try **Edit**, or create a new community via the add button.

---

## Report-back checklist

| # | Feature | Pass? |
|---|---|---|
| 1 | Dashboard KPI row + Trends + Leaders + AI panels (no action center) | ☐ |
| 2 | New project → college-prefixed auto code toast | ☐ |
| 3 | Hub Overview shows **Targets & Allocation**, no objectives panel | ☐ |
| 4 | Activity overlap → REFUSED | ☐ |
| 5 | Attendance XLSX import (parse → confirm) | ☐ |
| 6 | Evaluation import → aggregates into pre/post/satisfaction | ☐ |
| 7 | Beneficiary dedup warning | ☐ |
| 8 | HANDA over-allocation banner (₱62,500 allocation) | ☐ |
| 9 | University Targets pool = 2,500 hrs / ₱668,000 | ☐ |
| 10 | Faculty Management board → Directory → profile (no attainment %) | ☐ |
| 11 | GULAYAN approve → activity auto-created | ☐ |
| 12 | SIKAD BUHAY approve → BLOCKED | ☐ |
| 13 | Rendered hours approve → locked | ☐ |
| 14 | Hub: **New program** / **New project** / **Edit program** modals open and save (§25) | ☐ |
| 15 | Calendar 3 feeds + conflict markers | ☐ |
| 16 | Report Ctrl+P letterhead | ☐ |
| 17 | AI analysis generate → three tiers → approve | ☐ |
| 18 | Narrative generate → version history (no "Objectives met" chip) | ☐ |
| 19 | Interagency catalogue rejects an incomplete agency | ☐ |
| 20 | Audit Logs read-only, paginated | ☐ |
| 21 | Communities filter + detail | ☐ |

## Testing together

- Your **approvals feed your friends**: approving a proposal notifies the faculty who submitted it;
  approving rendered hours locks their entry; the availability request you create lands in a
  faculty's queue.
- The **secretary's validations** recompute the community summaries you pick in AI Analysis Review —
  ask them to validate a few first.
- If a queue looks empty, another tester probably consumed it — re-run the seeder
  (`php artisan migrate:fresh --seed`).
