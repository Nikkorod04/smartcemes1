# SmartCEMES — Presentation Demo Script

_Full feature walkthrough for the capstone defense/presentation. Runs on the
seeded demo data wherever possible so nothing depends on live typing speed.
Companion docs: `docs/TEST-SCRIPT.md` (from-scratch program deep-dive),
`AI_HANDOFF.md` (system state), `SYSTEM_BLUEPRINT_V4.txt` (the contract)._

**Target runtime:** ~30 minutes (cut priorities marked — minimum viable demo is
~18 min). Written 2026-09-15 against blueprint v4.11 / 169 green tests.

---

## PRE-DEMO CHECKLIST (run 15 minutes before)

```
php artisan migrate:fresh --seed   # reproducible demo data
php artisan storage:link           # persists on Windows — only needed once
php artisan serve                  # http://127.0.0.1:8000
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

**Accounts** (password `password` for all):

| Role | Email | Persona |
|---|---|---|
| Admin (Director) | admin@lnu.com | Dr. Lowell A. Quisumbing |
| Secretary | secretary@lnu.com | Jhoanna Ayles |
| Faculty | faculty1@lnu.com | Carlo Sumile |
| Faculty | faculty2@lnu.com | Bianca Oledan |
| Faculty | faculty3@lnu.com | Nikko Villas |
| Faculty | faculty4@lnu.com | Kent Naputo |

**Seeded data you will lean on:** 58 communities & partner schools ·
6 programs (EXT-2026-001…006) with objectives, activities, attendance,
budgets · 5 proposals in every workflow state (incl. one that violates the
scheduling rule) · availability requests · rendered hours in every state ·
needs assessments pending/validated/returned with remarks · downloadable
proposal attachments & Special Order PDFs · HANDA deliberately over-allocated
by ₱2,000 (D7 demo).

---

## DEMO MAP

| Act | Section | Min | Cut? |
|---|---|---|---|
| 0 | Login & the three roles | 2 | no |
| 1 | Faculty journey — encode, propose, respond, render | 7 | trim wizard |
| 2 | Secretary journey — validation queue | 3 | no |
| 3 | Director journey — programs, hub, approvals | 13 | see per-step flags |
| 4 | Security & governance wrap | 2 | no |
| — | AI deep-dive (inside Act 3) | 3 | last to cut — capstone centerpiece |

**Story line to say out loud:** *"A faculty member meets the community and
encodes a needs assessment → the secretary validates it → the Director turns
insights into a program with measurable objectives → activities, attendance,
budget and rendered hours flow through approval workflows → dashboards,
reports and AI summaries close the loop. Three roles, separation of duties,
everything audit-logged."*

---

## ACT 0 — LOGIN & THE THREE ROLES (2 min)

1. Show the login page. Log in as **admin@lnu.com**.
2. Point at the sidebar: **Dashboard, Analytics, Calendar, Extension Programs,
   Communities & Partner Schools, Proposals, Availability Requests, Rendered
   Hours, AI Analysis Review, Program Narratives, Reports** — the Director
   sees everything.
3. **Talking point:** exactly three locked roles, single `role` column,
   middleware on every route, no permission packages, no multi-role accounts
   (blueprint §2). Log out.

---

## ACT 1 — FACULTY JOURNEY (7 min) — login as **faculty2@lnu.com** (Bianca)

### 1.1 Faculty dashboard + notifications (1 min)
- Bell icon: show pending notifications (rendered-hours drafts, deadlines).
- Dashboard shows her programs, upcoming activities, inline availability
  accept/decline cards.

### 1.2 Encode a needs assessment — the Sections I–IX wizard (3 min)
Path: sidebar → **Encode Assessment**.

Show, don't fill everything (the seeded data already proves persistence):

- **Section I**: type a name — point out **letters-only sanitization**
  (digits are rejected as you type); pick Civil Status / Religion from the
  **custom searchable dropdowns** (not native selects).
- **Conditional logic live demo** (the wow moment): in the household section,
  toggle **Own toilet → Yes** → the *Toilet type* question appears; set it to
  **No** → the question hides **and its stored value is cleared** (say:
  "hidden children are cleared server-side, so stale answers can never be
  saved"). Same with **Electricity → No** → *Light source* appears.
- **Exclusive chips**: Preferred Training Days → click **Flexible** — it
  replaces any other selection; click **Saturday** — Flexible drops out.
- **Cap rule**: try clicking 4 problems in any problem list — the 4th click
  is ignored ("up to 3 per field").
- Scroll the step dots: nine sections, one respondent per record, saved as
  **pending** for secretary validation.
- Either finish quickly or press Save on realistic partial data — the record
  appears in the secretary's queue (validated in Act 2).

### 1.3 XLSX import (30 sec — mention, show template)
Path: wizard page → **Import from template**.
- Download the official **vertical template** — say: "field labels in column
  A, answer cells with dropdowns in B, full option guides in C; unknown
  values auto-map to *Other* with the raw text kept — never a whole-file
  reject" (D9/D10/D11).
- If pre-staged file ready: upload → **Parse** → preview shows parsed values,
  D9 auto-map panel, per-field errors → **Confirm & create record** (pending).

### 1.4 Submit a proposal (1 min)
Path: **Submit Proposal**.
- Pick a program — note the date pickers constrained to the program range.
- Show attachment upload (pdf/docx/xlsx, max 10 MB) and budget estimate.
- Save → status **pending**. Say: "approval is the Director's — separation of
  duties; on approval the system auto-creates the activity."

### 1.5 Availability + rendered hours (1.5 min)
- **Availability Requests**: show a pending request; **Decline requires a
   reason** (try confirming empty → validation blocks). Accept is hard-blocked
   on schedule overlap (8.8) — mention, don't spend time.
- **Rendered Hours → My**: show the **auto-drafted** entry (source = auto,
   hours = activity duration). Click **Adjust** — hours can only go **DOWN**
   with a note (try typing a higher number → refused, 8.9). Submit one for
   the Director to approve in Act 3.

**Log out.**

---

## ACT 2 — SECRETARY JOURNEY (3 min) — login as **secretary@lnu.com**

### 2.1 Assessment Review queue
Path: **Assessment Review**.
- Queue is ordered pending → returned → validated. Open a **pending** record
  (the one just encoded, or a seeded one).
- **Validate** → toast + the record's `(community, quarter, year)` **summary
  recomputes automatically** (6.10) — say: "this aggregate summary is exactly
  what the AI later receives — never respondent identities" (D3 setup).
- **Return** on another pending record → remarks required (min 5 chars) —
  the faculty uploader gets a notification with the remarks.
- **Talking point:** the secretary has **zero AI surfaces** — nav, dashboard,
  everything (D4). The sidebar proves it.

**Log out.**

---

## ACT 3 — DIRECTOR JOURNEY (13 min) — login as **admin@lnu.com**

### 3.1 Dashboard — the action center (1 min)
One screen, every pending decision: proposals awaiting approval, availability
awaiting response, rendered hours awaiting approval, programs nearing
deadline, AI analyses awaiting review, **objectives at risk** (derived live —
say "2 at risk" comes from the 8.6 rules, not a stored column). Plus KPI
tiles, status/budget/reach charts, and the AI panel with narrative health
labels.

### 3.2 Create a program — race-safe codes (1 min)
Path: **Extension Programs → New Program**.
- Fill minimal fields (title, dates, budget, lead, community via the
  searchable **multi-select**), save.
- **Expected:** toast `Program created — code EXT-2026-007`. Say: "codes are
  atomically sequenced per year — `EXT-{year}-{seq}` — and self-heal past any
  manually seeded rows, so duplicates are impossible" (5.1, v4.11).

### 3.3 Program hub deep-dive — open **EXT-2026-001 LITRAWIYA** (3 min)
Tabs: Overview | Activities | Beneficiaries | Budget.

**Overview — Results Framework:**
- Objectives with **live-derived status badges**, progress bars, and
  `baseline → target → actual` with an **actual-source tag** (`auto (live)`
  vs `manual`).
- Open **Manage**: the objective manager mirrors the same badges/progress.
  Click **+ Add objective** → select KPI metric **Community Reach** → the
  **"Currently computed · live"** box shows the real current value (e.g.
  "186.0 pupils") — say: "targets are set against live program data, and the
  Manual-actual field disappears for numeric objectives because the system
  computes them" (v4.10).
- Point at the **? tooltip**: the full §8.6 KPI dictionary — every dashboard,
  report and AI input derives from these exact formulas.
- Also show **EXT-2026-003 KABUHIAN** (completed): 2 achieved + **1 Not met**
  in gold — a qualitative objective (association deferred) tracked manually
  with evidence notes.

**Activities tab:**
- Show the **8.8 hard block** live: Add Activity on a date/time overlapping an
  existing one and assign the same faculty → save **refused** with a clear
  conflict message ("no warn-and-confirm — refused").
- Open **Records** on an activity → **Attendance** tab: download the
  generated template (roster pre-filled) → parse → per-row preview →
  confirm. Say: "attendance is import-only from the official template
  (v4.13); late counts as served per 8.6". The **Evaluation** tab imports
  each beneficiary's pre/post/satisfaction row and averages them into the
  activity's aggregate columns (D13).

**Beneficiaries tab:**
- **Register new**: type a name/barangay matching an existing beneficiary →
  **dedup warning requires explicit confirm** (5.4).
- **Import XLSX** (mention): 500 rows max, per-row Import/Duplicate/Skip
  preview, auto-enrollment.

**Budget tab:**
- Open **EXT-2026-002 HANDA** instead: it is seeded **over-allocated by
  ₱2,000** — show the persistent warning banner + badge and say: "overrun
  never blocks the save; it warns and writes an audit-log entry" (D7).

### 3.4 Proposals — approval workflow (2 min)
Path: **Proposals**.
- Open **GULAYAN SA PAARALAN** (pending, Bianca): detail modal shows
  downloadable attachments (click one — real PDF), budget, dates.
- **Approve**: attach the Special Order (or skip — informational), confirm →
  toast "activity auto-created (draft)". Say: "approval stamps the approver,
  auto-creates the activity under the program copying title and dates, and
  notifies the faculty."
- Now open **SIKAD BUHAY** (pending, Carlo): its proposed dates fall **outside
  the parent program's range** → click Approve → **BLOCKED** with the 8.8
  message. Say: "scheduling is a hard constraint — the UI constrains pickers
  and the server is the backstop."
- Show the rejected one (TESDA-Ready) with its recorded rejection reason.

### 3.5 Rendered hours — approval & lock (1 min)
Path: **Rendered Hours**.
- Approve Bianca's pending 2.5-hr entry → toast "entry **locked**". Say:
  "approved entries are immutable and audit-logged; faculty could only ever
  adjust down from the auto-draft" (8.9).
- Reject path requires remarks (mention).

### 3.6 Analytics (1 min)
Path: **Analytics** — six tabs: **Overview** (objective-status chart — derived
live), **Program Performance** (all 7 locked KPIs per program), **Budget
Utilization**, **Community Reach** (by barangay), **Faculty Contribution**
(approved hours), **Pending Actions** (mirrors the action center).

### 3.7 Calendar (1 min)
Path: **Calendar** — month grid with **three feeds**: activities, ACCEPTED
availability, and **objective/program deadline** events; overlapping events
carry red **⚠ conflict** markers; day panel + 60-day upcoming list. Available
to all roles, scoped by role.

### 3.8 Reports (1 min)
Path: **Reports** — four institutional print views with LNU letterhead:
**Annual** (I–VII), **Results Framework** (per program: baseline → target →
actual + evidence), **Rendered Hours** (per faculty/semester), **Community
Partner Impact**. Open one → Ctrl+P → show the print preview. (PDF/Excel
export is explicitly future work — D6.)

### 3.9 AI — the capstone centerpiece (3 min)
**Talking points first (D3/D4/D12):** "Admin-only. One pipeline, two outputs.
Everything sent to Gemini is **aggregates** — distributions and counts, never
respondent PII. Live Google Gemini API only — no mock mode — and when it
fails, the failure is a first-class UI state, not a broken page."

**a) Community insights — `/ai-analysis` (AI Analysis Review):**
- Pick a community summary (e.g. San Jose) → **Generate** — button shows the
  spinner, generation is synchronous → draft appears with **priority needs,
  recommended interventions, confidence chip** (say: "confidence is a derived
  **data**-confidence — sample size + coverage — never the model bragging").
- Open the **Community response data** accordion: "this is the EXACT aggregate
  payload sent to the API — our DPA audit view."
- **Approve** the draft (approval gate — institutional use) or Discard.
  History table shows model + prompt version + approving officer (provenance).

**b) Program narratives — `/program-narratives` (or hub → Generate program
narrative on EXT-2026-001):**
- Generate → executive summary with **health label** (On track / At risk /
  Needs attention), top risks, next actions, provenance footer.
- Show the **"Objectives met: X/Y"** chip — derived live from 8.6, not from
  the AI.
- **Version history**: generate again → new row, nothing overwritten (no
  approval gate — decision support, 5.15).

---

## ACT 4 — SECURITY & GOVERNANCE WRAP (2 min)

1. **Role separation, live**: in the Secretary window, visit
   `127.0.0.1:8000/analytics` → **403**. In a Faculty window, visit
   `/ai-analysis` → **403**. (Or just state it — the sidebars already show
   they have no such entries.)
2. **Read-only scoping**: as faculty2, open My Programs → a led program hub
   renders **read-only** (banner, no Manage/Edit).
3. **Audit trail**: back as admin — the dashboard recent-activity feed shows
   Spatie activity-log entries (approvals, status transitions, objective
   changes, budget overruns) — D8: sensitive actions are always logged.
4. **Data protection**: aggregates-only to the LLM (D3), 10 MB + MIME/extension
   validated uploads, login throttling, soft deletes everywhere.

---

## FALLBACK PLANS

| Risk | Fallback |
|---|---|
| **Gemini key/quota fails live** | Show the **first-class failure state**: "Analysis unavailable — (error)" with the **Retry** button and the persisted error. Frame it exactly as designed (D12: no mock mode, failure is a UI state, provenance still recorded). Then show a previously generated narrative from history. |
| Short on time | Cut in this order: 1.3 import → 3.7 calendar → 1.2 wizard details (keep ONE conditional toggle) → 3.6 analytics (keep Overview tab only) → 3.8 reports. **Never cut:** 8.8 block, D7 banner, objective live-derivation, AI, Act 4. |
| A live action errors | Every demo state exists in seeded data — restart the story from the seeded program/proposal instead of debugging on stage. |
| Notifications bell empty | Run `php artisan smartcemes:notify-deadlines` once as admin (7-day dedup keeps it clean). |

---

## PANEL Q&A QUICK REFERENCE

| If asked about… | Answer anchor |
|---|---|
| KPI correctness | §8.6 dictionary is the single source; objectives derive **live** from effective actuals — the stored status column is never read (v4.10) |
| Duplicate codes / race conditions | Atomic sequence table + `lockForUpdate`; floor = max stored suffix incl. soft-deleted (5.1, v4.11) |
| Data privacy (DPA) | Aggregation-before-send (D3): the /ai-analysis accordion shows the exact payload; PII never leaves the system (tested) |
| Why no mock AI? | D12: live API only; failure is a first-class state with provenance — demoed either way |
| Why approve AI analyses but not narratives? | Analyses feed institutional/community-facing use (gate, D4); narratives are Director-internal decision support (no gate, 5.15) |
| Budget overruns | D7: never hard-block; persistent warning + activity log |
| Scheduling conflicts | 8.8: program-range constraint, faculty overlap refusal, availability accept refusal, calendar conflict markers |
| Faculty service credit | 8.9: auto-draft on completion, adjust-down-only with note, admin approval, locked after, unique per (faculty, activity) |
| Validation of imported data | D9 auto-map to Other with raw text kept; D10 per-row/per-field errors, never whole-file reject |
| What's NOT in scope | §1.2: OCR, partner portal, PDF/Excel export, predictive analytics, dark mode, mobile, multi-role (deliberately parked) |
| Test coverage | 169 tests / 722 assertions green; `php artisan test` |
| Where's the design from? | `docs/prototype/` (24-page clickable HTML, v4.1-conformant) is the visual source of truth; blueprint wins on conflict |

---

## ONE-LINE CLOSER

*"From community needs assessment to AI-summarized program narratives — every
step role-separated, constraint-enforced, audit-logged, and derived from a
single KPI dictionary. SmartCEMES."*
