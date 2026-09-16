# SmartCEMES — ADMIN (Director) Testing Guide

_You are testing as the **Admin / CESO Director** — the only role that manages
programs, approves workflows, and uses the AI features. Work top to bottom.
Each step tells you what to click and what you SHOULD see._

## Getting in

1. Open **https://disorder-childless-moonlike.ngrok-free.dev**
2. If ngrok shows a warning page, click **Visit Site** (normal for free ngrok).
3. Log in:
   - **Email:** admin@lnu.com
   - **Password:** password

**Your sidebar (you see everything):** Dashboard · Analytics · Calendar ·
Extension Programs · Communities & Partner Schools · Proposals · Availability
Requests · Rendered Hours · AI Analysis Review · Program Narratives · Reports

---

## 1. Dashboard — the action center

1. After login you land on the Dashboard.
2. Look at the **action center** cards (proposals awaiting approval, rendered
   hours awaiting approval, objectives at risk, etc.), the KPI tiles, the
   charts, and the **AI panel**.

**Expected:** one screen summarizing every pending decision. The "Objectives
at risk" number is computed live — not a stored value.

3. Click the **bell icon** (top header) — you may see deadline notifications.

**Expected:** a notification dropdown opens; entries can be marked as read.

---

## 2. Create a program (race-safe codes)

1. Go to **Extension Programs**.
2. Click **New Program**, fill in the minimal required fields (title,
   description, planned start/end dates, target beneficiaries, allocated
   budget, program lead, linked communities via the searchable multi-select,
   beneficiary categories), set Status, and save.

**Expected:** a success toast showing an auto-generated code like
`EXT-2026-007` (or the next number if programs were already created). Duplicates
are impossible — codes are atomically sequenced and self-heal past seeded rows.

3. The new program appears in the list with its status badge.

---

## 3. Program hub deep-dive — open **EXT-2026-001 LITRAWIYA**

From the Programs list, click the program. The hub has four tabs:
**Overview | Activities | Beneficiaries | Budget**.

### 3.1 Overview — results framework & objective manager

1. Look at the objectives list: each has a **status badge**, **progress bar**,
   and `baseline → target → actual` with a source tag (`auto (live)` or
   `manual`).
2. Click **Manage** (objective manager), then **+ Add objective**.
3. In the form, select a **KPI metric** (e.g. Community Reach).

**Expected:** the **"Currently computed · live"** box appears showing the real
current program value — and the **Manual actual field disappears** for numeric
objectives (the system computes them itself). Leave the form without saving,
or save if you want to keep the objective.

4. Hover/click the **? tooltip** near the objective form title.

**Expected:** the KPI dictionary — every dashboard, report, and AI input
derives from these exact formulas.

5. Optional: open **EXT-2026-003 KABUHIAN** (completed program) from the
   Programs list.

**Expected:** the qualitative objective shows **Not met** (association
deferred — tracked manually with evidence notes, gold bar); the numeric
objectives show live-derived statuses from the seeded attendance/score data.

### 3.2 Activities tab — scheduling hard-block + records import

1. Click **Add Activity**; assign a faculty member; pick a date/time that
   **overlaps** an existing activity of the same faculty (check the existing
   rows first); save.

**Expected:** save is **REFUSED** with a clear conflict message (e.g.
"Assignment refused: … is already assigned to '…' which overlaps this
schedule."). No warn-and-confirm — it is blocked.

2. Click **Records** on an activity → **Attendance** tab.

**Expected:** upload step with a **Download official template** link (the
enrolled roster is pre-filled) and a file picker. Attendance is import-only
(v4.13): download → fill the Status column (present / absent / excused /
late; blank = leave unrecorded) → upload → **Parse file →** → review the
per-row states → **Confirm & import attendance**. A toast reports the
applied/blank/error counts; the **Evaluation** tab of the same modal works
the same way for pre/post/satisfaction rows (averaged into the activity's
aggregate scores — D13). Late still counts as served.

### 3.3 Beneficiaries tab — dedup warning + import

1. Click **Register new**; type a first/last name and barangay that matches an
   existing beneficiary (look at the table first), then save.

**Expected:** a **de-duplication warning** appears and requires explicit
confirmation — never a silent merge.

2. Note the **Import XLSX** button (bulk import, 500 rows max, per-row
   Import/Duplicate/Skip preview) and **Enroll existing** (search + enroll
   from the global registry). Try either if you like.

### 3.4 Budget tab — over-allocation warning (D7)

1. Go back to the Programs list and open **EXT-2026-002 HANDA** instead. It is
   seeded **over-allocated by ₱2,000**.

**Expected:** a persistent **over-allocation warning banner** + badge on the
program. Overrun never blocks saving — it warns and writes an audit-log entry.

2. In any program's Budget tab, add an expense entry (item, amount, date,
   optional activity). The utilized total and progress bar update live.

---

## 4. Proposals — approval workflow

Go to **Proposals**. Seeded states: 3 pending, 1 approved, 1 rejected.

1. Open **GULAYAN SA PAARALAN** (pending, from Bianca).

**Expected:** detail modal shows downloadable attachments (click one — a real
PDF downloads), budget, dates, and a Special Order block.

2. Click **Approve** — optionally attach a Special Order PDF (skipping is
   fine; an informational notice shows), then confirm.

**Expected:** toast says an **activity was auto-created (draft)** under the
target program, copying title and dates. The faculty gets a notification.

3. Open **SIKAD BUHAY** (pending, from Carlo) and click **Approve**.

**Expected:** **BLOCKED** — its proposed dates (Dec 15, 2026 – Jan 10, 2027)
fall outside the parent program EXT-2026-005's range (scheduling is a hard
constraint; the server is the backstop). If it approves instead, the demo DB
was seeded before this demo row was fixed — ask the owner to re-run
`php artisan migrate:fresh --seed`, or use the out-of-range example in
`docs/guides/08-proposal-workflow.md` for a guaranteed block.

4. Open **TESDA-Ready** (rejected).

**Expected:** the recorded rejection reason is shown.

5. Try the **Reject** path on a pending proposal.

**Expected:** a reason is **required (minimum 10 characters)** — confirming
empty is blocked.

---

## 5. Availability requests

1. Go to **Availability Requests**.

**Expected:** the list shows requests in every state (pending, accepted,
declined-with-reason) — all admin-initiated.

2. Create a **new request**: pick a faculty (use the account a friend is
   testing with — see faculty guide), an activity, date/time (defaults from
   the activity schedule), and remarks.

**Expected:** the request is created as **pending** and appears in that
faculty's queue + notifications. Their accept/decline updates this list.

---

## 6. Rendered hours — approval & lock

1. Go to **Rendered Hours**.

**Expected:** an approval queue with **pending** entries (seeded: Bianca 2.5
hrs with an adjustment note, Nikko 7.0 hrs, Carlo 4.0 hrs — minus any already
approved by other testers).

2. Click **Approve** on a pending entry.

**Expected:** toast confirms the entry is **locked**. Approved entries are
immutable and audit-logged — faculty can never edit them again.

3. Try **Reject** on another entry.

**Expected:** remarks are **required** before it can be rejected.

---

## 7. Analytics — six tabs

Go to **Analytics** and click through: **Overview** (objective-status chart) ·
**Program Performance** (all 7 locked KPIs per program) · **Budget
Utilization** · **Community Reach** (by barangay) · **Faculty Contribution**
(approved hours) · **Pending Actions** (mirrors the dashboard action center).

**Expected:** every tab renders charts/tables with real seeded data; the
objective chart on Overview is derived live from program data.

---

## 8. Calendar

Go to **Calendar**.

**Expected:** a month grid combining **three feeds** — activities, ACCEPTED
availability requests, and objective/program deadlines. Click a day — the side
panel lists its events; a 60-day upcoming list is below. Where events overlap
in time, they carry red **conflict** markers. Navigate months; "today" is
ringed.

---

## 9. Reports (print views)

1. Go to **Reports** — four institutional reports: **Annual**, **Results
   Framework** (per program), **Rendered Hours**, **Community Partner
   Impact**.
2. Open one and press **Ctrl+P**.

**Expected:** a clean print preview with the LNU letterhead (screen chrome is
hidden when printing). PDF/Excel export is deliberately future work — printing
is the MVP path.

---

## 10. AI Analysis Review (community insights) — admin only

1. Go to **AI Analysis Review**.
2. Pick a community summary (e.g. a **San Jose** one) in the picker and click
   **Generate**.

**Expected:** the button shows a spinner (generation is synchronous — results
appear immediately), then a **draft** appears with: situation summary,
**priority needs**, **top recommended interventions**, and a confidence chip
(derived from data coverage — not the model bragging). If the AI service
fails, you instead see the first-class failure state **"Analysis
unavailable"** with a Retry button — that is also correct behavior.

3. Open the **Community response data** accordion on the draft.

**Expected:** the EXACT aggregate payload sent to the AI — counts,
percentages, distributions. **No respondent names/PII ever leave the system**
(this is the data-privacy audit view).

4. Click **Approve** (or Discard).

**Expected:** approving stamps you as the approving officer; the history table
shows model, prompt version, and approver (full provenance).

---

## 11. Program Narratives (executive summaries) — admin only

1. Go to **Program Narratives**.
2. Find **EXT-2026-001 LITRAWIYA** and click its **Generate** button (also
   available inside the program hub as "Generate program narrative").

**Expected:** an executive summary with a **health label** (On track / At
risk / Needs attention), top risks, recommended next actions, and the
**"Objectives met: X/Y"** chip — which is computed from program data, not
from the AI.

3. Generate again for the same program.

**Expected:** a **new version** is created — nothing is overwritten (browse
the version-history timeline). Narratives have no approval gate: they are
Director-internal decision support.

---

## 12. Communities & Partner Schools

1. Go to **Communities & Partner Schools**.

**Expected:** a list (58 seeded records). Filter chips: **All / Communities /
Partner Schools**. School rows have a gold document icon + level label
(elementary/secondary/higher ed).

2. Click a row — the detail modal opens (schools show Level/Principal instead
   of beneficiaries). Try **Edit**, or create a new community via the add
   button.

---

## Report-back checklist

| # | Feature | Pass? |
|---|---|---|
| 1 | Dashboard action center + bell | ☐ |
| 2 | New program → auto code toast | ☐ |
| 3 | Objective manager: live KPI box, manual field hidden | ☐ |
| 4 | Activity overlap → REFUSED | ☐ |
| 5 | Attendance XLSX import (parse → confirm) | ☐ |
| 6 | Beneficiary dedup warning | ☐ |
| 7 | HANDA over-allocation banner | ☐ |
| 8 | GULAYAN approve → activity auto-created | ☐ |
| 9 | SIKAD BUHAY approve → BLOCKED | ☐ |
| 10 | Rendered hours approve → locked | ☐ |
| 11 | Analytics 6 tabs | ☐ |
| 12 | Calendar 3 feeds + conflict markers | ☐ |
| 13 | Report Ctrl+P letterhead | ☐ |
| 14 | AI analysis generate → approve | ☐ |
| 15 | Narrative generate → version history | ☐ |
| 16 | Communities filter + detail | ☐ |

## Testing together

- Your **approvals feed your friends**: approving a proposal notifies the
  faculty who submitted it; approving rendered hours locks their entry; the
  availability request you create lands in a faculty's queue.
- The **secretary's validations** recompute the community summaries you pick
  in AI Analysis Review — ask them to validate a few first.
- If a queue looks empty, another tester probably consumed it — ask the owner
  to re-run the seeder (`php artisan migrate:fresh --seed`).
