# SmartCEMES — Presentation Demo Script

_Full feature walkthrough for the capstone defense/presentation. Runs on the seeded demo data
wherever possible so nothing depends on live typing speed. Companion docs: `docs/TEST-SCRIPT.md`
(the from-scratch deep-dive), `docs/guides/*` (step-by-step feature guides), `AI_HANDOFF.md`
(system state), `SYSTEM_BLUEPRINT_V4.txt` (the contract)._

> **The hierarchy is `College → Program → Project → Activity`.** A **Program** is one of the six broad
> CESO thrusts; a **Project** is the thing with dates, a budget, activities and targets. Where an older
> note says "program" and means the narrow entity, it means a *project*.
>
> **Targets exist at two levels only — University and Project** (D-R5). Broad programs and colleges
> carry none. **Budget has no annual target at all** — a project's **allocation** is its only budget
> figure (v4.19).
>
> **Training hours are `trainors × trainees × days` — there is NO `× 8`.**
>
> **The 8.6 KPI dictionary and the results framework were REMOVED from the UI** (D-R7). If you see an
> objectives panel or a KPI scorecard, that is a regression.

**Target runtime:** ~30 minutes (cut priorities marked — minimum viable demo is ~18 min).

---

## PRE-DEMO CHECKLIST (run 15 minutes before)

```
php artisan migrate:fresh --seed   # reproducible demo data
php artisan storage:link           # persists on Windows — only needed once
php artisan serve                  # http://127.0.0.1:8000
npm run build                      # if any asset changed
```

- [ ] Login works for all three roles (see accounts below)
- [ ] `.env` has `GEMINI_API_KEY` set (live API only — D12; verify with one
      Program Narrative generation before the audience arrives)
- [ ] Browser: one window per role logged in and ready (Admin / Secretary /
      Faculty) — avoids live login typing
- [ ] Optional pre-staged file: a pre-filled `assessment-template-v3.xlsx`
      (download from Encode Assessment → Import from template) on the desktop
      for the import demo
- [ ] Printer dialog works for Reports (Ctrl+P shows letterhead preview)

**Accounts** (password `password` for all eight):

| Role | Email | Persona |
|---|---|---|
| Admin (Director) | admin@lnu.com | Dr. Lowell A. Quisumbing |
| Secretary | secretary@lnu.com | Jhoanna Ayles |
| Faculty | faculty1@lnu.com | Carlo Sumile |
| Faculty | faculty2@lnu.com | Bianca Oledan |
| Faculty | faculty3@lnu.com | Nikko Villas |
| Faculty | faculty4@lnu.com | Kent Naputo |
| Faculty (Graduate School) | faculty5@lnu.com | Dr. Ramon L. Villamor |
| Faculty (Graduate School) | faculty6@lnu.com | Dr. Cristina P. Manalo |

**Seeded data you will lean on:** **4 colleges** (CAS · COE · CME · GRAD — a fixed, read-only set) ·
**6 broad programs** (the CESO thrusts) · **8 projects** (7 legacy `EXT-2026-00X` + `CAS-2026-001`
BUSOG + `GRAD-2026-001` PANDAY) · **15 activities** · **57 beneficiaries** · 58 communities &
partner schools · 5 proposals in every workflow state (incl. one that violates the scheduling rule) ·
availability requests · rendered hours in every state · needs assessments
pending/validated/returned with remarks · downloadable proposal attachments & Special Order PDFs ·
**the university pool (2,500 hrs / ₱668,000)** · HANDA deliberately over-allocated by ₱2,000
(D7 demo) · the interagency catalogue (10 agencies).

---

## DEMO MAP

| Act | Section | Min | Cut? |
|---|---|---|---|
| 0 | Login & the three roles | 2 | no |
| 1 | Faculty journey — encode, propose, respond, render | 7 | trim wizard |
| 2 | Secretary journey — validation + beneficiary management | 3 | no |
| 3 | Director journey — structure, hub, approvals, targets | 13 | see per-step flags |
| 4 | Security & governance wrap | 2 | no |
| — | AI deep-dive (inside Act 3) | 3 | last to cut — capstone centerpiece |

**Story line to say out loud:** *"A faculty member meets the community and encodes a needs
assessment → the secretary validates it → the Director turns the community's needs into a **project**
with an annual target, under one of the six broad CESO thrusts → activities, attendance, budget and
rendered hours flow through approval workflows → dashboards, reports and AI summaries close the loop.
Three roles, separation of duties, everything audit-logged."*

---

## ACT 0 — LOGIN & THE THREE ROLES (2 min)

1. Show the login page. Log in as **admin@lnu.com**.
2. Point at the sidebar — **five sections, and the whole hierarchy collapsed into ONE entry**:

   | Section | Items |
   |---|---|
   | Overview | Dashboard · University Targets · Calendar |
   | Extension Programs | **Manage Extension Programs** (opens the colleges hub) |
   | Management | Faculty Management · Communities & Partner Schools |
   | Approvals | Proposals · Availability Requests · Rendered Hours |
   | Intelligence & Reports | AI Analysis Review · Project Narratives · Interagency Catalogue · Reports · Audit Logs |

   Say: *"Colleges, Programs and Projects are reached from inside that one hub — never as separate
   sidebar items. That's a deliberate rule, and a test enforces it."*
3. **Talking point:** exactly three locked roles, single `role` column, middleware on every route, no
   permission packages, no multi-role accounts (blueprint §2). Log out.

---

## ACT 1 — FACULTY JOURNEY (7 min) — login as **faculty2@lnu.com** (Bianca)

### 1.1 Faculty dashboard + notifications (1 min)
- Bell icon: show pending notifications (rendered-hours drafts, deadlines).
- Dashboard shows her **projects**, upcoming activities, personal participation stats, inline
  availability accept/decline cards, and a **My Rendered Hours** summary.

### 1.2 Encode a needs assessment — the Sections I–IX wizard (3 min)
Path: sidebar → **Encode Assessment**.

Show, don't fill everything (the seeded data already proves persistence):

- **Section I**: type a name — point out **letters-only sanitization** (digits are rejected as you
  type); pick Civil Status / Religion from the **custom searchable dropdowns** (not native selects).
  Mention **Religion is a closed 16-option list** with no "Other".
- **Conditional logic live demo** (the wow moment): in the household section, toggle **Own toilet →
  Yes** → the *Toilet type* question appears; set it to **No** → the question hides **and its stored
  value is cleared** (say: "hidden children are cleared server-side, so stale answers can never be
  saved"). Same with **Electricity → No** → *Light source* appears.
- **Exclusive chips**: Preferred Training Days → click **Flexible** — it replaces any other selection;
  click **Saturday** — Flexible drops out.
- **Cap rule**: try clicking 4 problems in any problem list — the 4th click is ignored ("up to 3 per
  field"). All seven problem lists are closed lists with no "Other".
- Scroll the step dots: nine sections, one respondent per record, saved as **pending** for secretary
  validation.
- Either finish quickly or press Save on realistic partial data — the record appears in the
  secretary's queue (validated in Act 2).

### 1.3 XLSX import (30 sec — mention, show template)
Path: wizard page → **Import from template**.
- Download the official **vertical template** — say: "field labels in column A, answer cells with
  dropdowns in B, full option guides in C; unknown values auto-map to *Other* with the raw text kept —
  never a whole-file reject" (D9/D10/D11). The dropdowns are **non-strict** — typed free text is
  accepted.
- If a pre-staged file is ready: upload → **Parse** → preview shows parsed values, the D9 auto-map
  panel, per-field errors → **Confirm & create record** (pending).

### 1.4 Submit a proposal (1 min)
Path: **Submit Proposal**.
- Pick a **project** — note the date pickers constrained to the project's range.
- Show attachment upload (pdf/docx/xlsx, max 10 MB) and budget estimate.
- Save → status **pending**. Say: *"approval is the Director's — separation of duties; on approval the
  system auto-creates the activity."*

### 1.5 Availability + rendered hours (1.5 min)
- **Availability Requests**: show a pending request; **Decline requires a reason** (try confirming
  empty → validation blocks). Accept is hard-blocked on schedule overlap (8.8) — mention, don't spend
  time.
- **Rendered Hours → My**: show the **auto-drafted** entry (source = auto, hours = activity duration).
  Click **Adjust** — hours can only go **DOWN** with a note (try typing a higher number → refused,
  8.9). Submit one for the Director to approve in Act 3.
- Say: *"These are my service credit. The training hours delivered to beneficiaries are a different
  number — `trainors × trainees × days` — and the UI names them differently on purpose."*

**Log out.**

---

## ACT 2 — SECRETARY JOURNEY (3 min) — login as **secretary@lnu.com**

### 2.1 Assessment Review queue
Path: **Assessment Review**.
- Queue is ordered **pending → returned → validated**. Open a **pending** record (the one just
  encoded, or a seeded one) — the dossier drawer shows Sections I–IX plus a record-completeness bar.
- **Validate** → a **confirm modal** first, then a toast + the record's `(community, quarter, year)`
  **summary recomputes automatically** (6.10) — say: *"this aggregate summary is exactly what the AI
  later receives — never respondent identities"* (D3 setup).
- **Return** on another pending record → remarks required (min 5 chars) — the faculty uploader gets a
  notification with the remarks.
- **Talking point:** the secretary has **zero AI surfaces** — nav, dashboard, everything (D4). The
  sidebar proves it.

### 2.2 Manage Beneficiaries (bonus, 30 sec)
Path: **Manage Beneficiaries**. Say: *"The Secretary can manage a project's beneficiaries and import
its attendance — but project structure, targets and budget stay with the Director's office. That's a
scoped permission, not a role bypass."*

**Log out.**

---

## ACT 3 — DIRECTOR JOURNEY (13 min) — login as **admin@lnu.com**

### 3.1 Dashboard (1 min)
One screen: **four KPI tiles** (Extension Projects · Training Hours Rendered · Budget Utilized of ₱… ·
Pending Approvals), then **Trends → Hours & budget against target** (Training Hours vs Target +
**Budget Utilized vs Allocated Budget** bullet rows), then **Performance → Most Performing Projects /
Most Performing Faculty**, then **Intelligence → Community Insights Queue / Project Narratives**.

Say: *"There is no action center and no recent-activity panel — the feed moved to its own Audit Logs
page, because four rows could never answer 'who changed this, and when'. Pending work survives as the
Pending Approvals tile."*

Point at the budget panel's note: **"% of the allocated budget"** — *"budget has no annual target; the
allocation is the figure."*

### 3.2 Create a project — race-safe, college-prefixed codes (1 min)
Path: **Manage Extension Programs → a college card → View all projects → New Project**.
- Fill minimal fields (title, dates, allocated budget, target training hours, lead, community via the
  searchable **multi-select**), save.
- **Expected:** toast with a **college-prefixed** code, e.g. `CAS-2026-002`. Say: *"codes are
  atomically sequenced **per college per year**, and self-heal past any manually seeded rows, so
  duplicates are impossible and two colleges never share a sequence"* (5.1, v4.11).
- Note there is **no "target budget" field** — only allocated budget and target training hours.

### 3.3 Project hub deep-dive — open **EXT-2026-001 LITRAWIYA** (3 min)
Tabs: Overview | Activities | Beneficiaries | Budget.

**Overview — the target model:**
- The **Targets & Allocation** card: **Training hours rendered** (134.5 of 150 for BUSOG = 89.7 %) and
  **Budget utilized** (₱78,000 of ₱85,000 **allocated**). Every figure is a live roll-up of the
  project's activities — nothing is stored, so nothing can go stale.
- Open **CAS-2026-001 BUSOG** to show a project that carries an hours target. Say: *"LITRAWIYA has no
  annual hours target, so its bar says 'no annual target set' rather than a misleading 0 %. An absent
  target is not a target of zero."*
- Say: *"There is **no objectives panel, no results framework and no KPI scorecard** on this page.
  They were removed (D-R7) and replaced by the target model. `KpiService` and `ProgramObjective` are
  still in the codebase but unread."*

**Activities tab:**
- Show the **8.8 hard block** live: Add Activity on a date/time overlapping an existing one and assign
  the same faculty → save **refused** with a clear conflict message ("no warn-and-confirm — refused").
  *Tip: Nikko Villas is booked 2026-09-08 → 2026-10-16, so a September activity for him is a
  guaranteed demonstration.*
- Open **Records** on an activity → **Attendance** tab: download the generated template (roster
  pre-filled) → parse → per-row preview → confirm. Say: *"attendance is import-only from the official
  template (v4.13)."* The **Evaluation** tab imports pre/post/satisfaction rows and averages them into
  the activity's aggregate columns (D13).

**Beneficiaries tab:**
- **Register new**: type a name/barangay matching an existing beneficiary → **dedup warning requires
  explicit confirm** (5.4).
- **Import XLSX** (mention): 500 rows max, per-row Import/Duplicate/Skip preview, auto-enrollment.

**Budget tab:**
- Open **EXT-2026-002 HANDA** instead: it is seeded **over-allocated by ₱2,000** (₱64,500 utilized
  against a ₱62,500 allocation = 103.2 %) — show the persistent warning banner + badge and say:
  *"overrun never blocks the save; it warns and writes an audit-log entry"* (D7).

### 3.4 University Targets (1 min)
Path: **University Targets**. Say: *"Targets exist at exactly two levels — this **university pool**
(2,500 hrs / ₱668,000) and each project's own annual hours target. The pool is a **consumption** pool,
not a ratio: project actuals are drawn down from it. Project targets are deliberately **not** summed
into it — that would double-count."*

Point at the note: *"Broad programs carry no target of any kind."*

### 3.5 Proposals — approval workflow (2 min)
Path: **Proposals**.
- Open **GULAYAN SA PAARALAN** (pending, Bianca): detail modal shows downloadable attachments (click
  one — real PDF), budget, dates.
- **Approve**: attach the Special Order (or skip — informational), confirm → toast "activity
  auto-created (draft)". Say: *"approval stamps the approver, auto-creates the activity under the
  project copying title and dates, and notifies the faculty."*
- Now open **SIKAD BUHAY** (pending, Carlo): its proposed dates fall **outside the parent project's
  range** → click Approve → **BLOCKED** with the 8.8 message. Say: *"scheduling is a hard constraint —
  the UI constrains pickers and the server is the backstop."*
- Show the rejected one (TESDA-Ready) with its recorded rejection reason.

### 3.6 Rendered hours — approval & lock (1 min)
Path: **Rendered Hours**.
- Approve Bianca's pending 2.5-hr entry → toast "entry **locked**". Say: *"approved entries are
  immutable and audit-logged; faculty could only ever adjust down from the auto-draft"* (8.9).
- Reject path requires remarks (mention).

### 3.7 Faculty Management (1 min)
Path: **Faculty Management** → the **Engagement board** (chart + ranked rows + college split). Click
**Faculty Directory** → a member → their **profile** with the full contribution blocks.

Say: *"Every figure here is **contribution** — hours rendered, projects led, activities handled,
proposals. There is **no per-professor target and therefore no attainment %** anywhere on a faculty
surface. A percentage there would be invented."*

### 3.8 Analytics — REMOVED, do not demo (0 min)

> **Skip this step.** The Analytics page was deleted on 2026-09-27 (`revisions.md` §21) — the nav
> entry, the six tabs, the aggregate Pending Actions list and the Community Reach chart all went with it.
> If older notes tell you to show it, they are out of date. The number is kept so 3.9–3.12 keep theirs.

### 3.9 Calendar (1 min)
Path: **Calendar** — month grid with **three feeds**: activities, ACCEPTED availability, and
**project deadlines**; overlapping events carry red **conflict** markers; day panel + 60-day upcoming
list. Available to all roles, scoped by role.

### 3.10 Reports (1 min)
Path: **Reports** — four institutional print views with LNU letterhead: **Annual Extension
Performance** (I–VII), **Project Performance** (trainors × trainees × days, budget vs allocation),
**Faculty Rendered Hours**, **Community Partner Impact**. Open one → Ctrl+P → show the print preview.
(PDF/Excel export is explicitly future work — D6.)

> The Project Performance report's route is still `reports.results-framework` — a legacy name kept so
> old bookmarks don't break. Its content is the target model.

### 3.11 AI — the capstone centerpiece (3 min)
**Talking points first (D3/D4/D12):** *"Admin-only. One pipeline, two outputs. Everything sent to
Gemini is **aggregates** — distributions and counts, never respondent PII. Live Google Gemini API
only — no mock mode — and when it fails, the failure is a first-class UI state, not a broken page."*

**a) Community insights — `/ai-analysis` (AI Analysis Review):**
- The page is a **QUEUE grouped by community**, not a form — it lists every analysis plus every validated
  summary still awaiting one. **There is no summary picker any more.** Search a barangay (`?q=`, partial
  and case-insensitive), or narrow with the chips (*Awaiting review · Approved · Failed · Discarded ·
  Awaiting analysis*), then click **Generate** on an **awaiting analysis** row — button shows the spinner,
  generation is synchronous, and you land on **that analysis's own page**.
- Each **community name is a link** to its history (`/ai-analysis/community/{id}`) — every period, every
  generation, with Generate, Retry, **Delete** and a bulk **Clear failed**. Deleting is **scoped**: an
  approved analysis and the live draft cannot be deleted.
- Any row opens in **any** state — draft, approved (read-only, citable) or discarded. **Regenerate**
  creates a new generation and keeps the old one.
- The draft shows **priority needs, recommended interventions, confidence chip** (say: *"confidence is a
  derived **data**-confidence — sample size + coverage — never the model bragging"*).
- Open the **Community response data** accordion: *"this is the EXACT aggregate payload sent to the
  API — our DPA audit view."*
- **The three-tier scope guardrail** (D-R8): *"Tier 1 CESO can deliver it · Tier 2 an interagency
  referral naming a real agency from our catalogue · Tier 3 prohibited as CESO work, shown only as an
  audit note. A need CESO cannot serve is **referred, never dropped** — malnutrition, roads and water
  potability come back as Tier-2 referrals, not as CESO recommendations."*
- **Approve** the draft (approval gate — institutional use) or Discard. History table shows model +
  prompt version + approving officer (provenance).

**b) Project narratives — `/program-narratives` (Project Narratives), or hub → Generate narrative:**
- Generate → executive summary with **health label** (On track / At risk / Needs attention), top
  risks, next actions, provenance footer. It is graded against the project's annual **hours** target
  and its **allocation**.
- **Version history**: generate again → new row, nothing overwritten (no approval gate — decision
  support, 5.15).

### 3.12 Interagency Catalogue + Audit Logs (1 min, optional)
- **Interagency Catalogue**: *"the closed list of agencies the AI may cite in a Tier-2 referral. Try
  saving an incomplete agency — it's rejected. The model may not invent one."*
- **Audit Logs**: *"read-only, reverse-chronological, 25 per page — who changed what, and when."*

---

## ACT 4 — SECURITY & GOVERNANCE WRAP (2 min)

1. **Role separation, live**: in the Secretary window, visit `/audit-logs` → **403**. In a Faculty
   window, visit `/ai-analysis` → **403**. (Or just state it — the sidebars already show they have no
   such entries.)
   > `/analytics` was DELETED in v4.20, so it now returns **404**, not 403 — do not demo it.
2. **Read-only scoping**: as faculty2, open **My Projects** → a led project hub renders **read-only**
   (banner, no Manage/Edit). Try another project's id → **403**.
3. **Audit trail**: back as admin — open **Audit Logs**. Spatie activity-log entries (approvals,
   status transitions, budget overruns) — D8: sensitive actions are always logged.
4. **Data protection**: aggregates-only to the LLM (D3), 10 MB + MIME/extension validated uploads,
   login throttling, soft deletes everywhere.

---

## FALLBACK PLANS

| Risk | Fallback |
|---|---|
| **Gemini key/quota fails live** | Show the **first-class failure state**: "Analysis unavailable — (error)" with the **Retry** button and the persisted error. Frame it exactly as designed (D12: no mock mode, failure is a UI state, provenance still recorded). Then show a previously generated narrative from history. |
| Short on time | Cut in this order: 3.12 catalogue/audit → 1.3 import → 3.9 calendar → 1.2 wizard details (keep ONE conditional toggle) → 3.8 analytics (keep Overview only) → 3.10 reports. **Never cut:** the 8.8 block, the D7 banner, the target model, the AI three tiers, Act 4. |
| A live action errors | Every demo state exists in seeded data — restart the story from the seeded project/proposal instead of debugging on stage. |
| Notifications bell empty | Run `php artisan smartcemes:notify-deadlines` once as admin (7-day dedup keeps it clean). |

---

## PANEL Q&A QUICK REFERENCE

| If asked about… | Answer anchor |
|---|---|
| Targets | Two levels only — the **university pool** (a *consumption* pool) and each **project's** annual hours target (D-R5/D16). Broad programs and colleges carry none. **Budget has no target** (v4.19) |
| Budget vs allocation | `allocated_budget` is a project's only budget figure and what utilization is measured against; over-allocation never hard-blocks (D7) |
| Training-hours formula | `trainors × trainees × days` — **no `× 8`**; `days` carries the duration (0.5 = half day). One implementation: `TrainingHoursService` |
| Trainee resolution | attendance (present/late) → the manual `participants` fallback → 0, and every figure carries a **source tag** |
| Why no KPI scorecard? | The 8.6 dictionary and the results framework were **removed from the UI** (D-R7); `KpiService`/`ProgramObjective` are retained but unread |
| Duplicate codes / race conditions | Atomic sequence table + `lockForUpdate`; floor = max stored suffix incl. soft-deleted (5.1, v4.11). Codes are **college-prefixed** (R-Q4) |
| Data privacy (DPA) | Aggregation-before-send (D3): the /ai-analysis accordion shows the exact payload; PII never leaves the system (tested) |
| Why no mock AI? | D12: live API only; failure is a first-class state with provenance — demoed either way |
| Why approve AI analyses but not narratives? | Analyses feed institutional/community-facing use (gate, D4); narratives are Director-internal decision support (no gate, 5.15) |
| Scheduling conflicts | 8.8: project-range constraint, faculty overlap refusal, availability accept refusal, calendar conflict markers |
| Faculty service credit | 8.9: auto-draft on completion, adjust-down-only with note, admin approval, locked after, unique per (faculty, activity) |
| Faculty performance | **Contribution-based** (D-R19) — hours, projects led, activities, proposals. No per-professor target, so no attainment % |
| The four-level hierarchy | `College → Program → Project → Activity`. Colleges are a **fixed, read-only set of four**; a Program spans colleges; a project belongs to one college (which sets its code prefix) |
| Why is the nav so short? | The hierarchy is **collapsed into ONE entry** (D-R20); Colleges/Programs/Projects live inside the hub |
| Validation of imported data | D9 auto-map to Other with raw text kept; D10 per-row/per-field errors, never whole-file reject |
| What's NOT in scope | §1.2: OCR, partner portal, PDF/Excel export, predictive analytics, dark mode, mobile, multi-role (deliberately parked) |
| Test coverage | **472 tests / 2,165 assertions green**; `php artisan test` |
| Where's the design from? | `docs/prototype/` (30-page clickable HTML) is the visual source of truth; the blueprint wins on conflict |

---

## ONE-LINE CLOSER

*"From community needs assessment to AI-summarized project narratives — every step role-separated,
constraint-enforced, audit-logged, and measured against one target model. SmartCEMES."*
