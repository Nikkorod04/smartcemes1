# SmartCEMES — Manual Test Script

_A complete walkthrough that exercises the program → objectives → activities →
beneficiaries → attendance → budget pipeline, with the expected result at every
step. Anchored to **today's date** so deadline features (scheduler, at-risk
lists) are demonstrable. Written 2026-09-15 against blueprint v4.11._

## 0. Start the app

```
php artisan serve
```

Optional (only for step 9, deadline notifications):

```
php artisan schedule:work
```

Login as the Director (the only role that can manage programs):

| Role | Email | Password |
|---|---|---|
| Admin (Director) | admin@lnu.com | password |
| Secretary | secretary@lnu.com | password |
| Faculty | faculty1–4@lnu.com | password |

All six seeded accounts use `password`. Faculty accounts: Carlo Sumile,
Bianca Oledan, Nikko Villas, Kent Naputo.

---

## 1. Create the program — Programs → New Program

| Field | Value |
|---|---|
| Program title | SIKAD-DIGITAL: Digital Literacy for Parents & OSY |
| Description | Basic computer, e-gov, and online-safety training for Sagkahan parents and out-of-school youth. |
| Planned start / end | 2026-09-01 / 2026-12-31 |
| Target beneficiaries | 20 |
| Allocated budget | 20000 |
| Program lead | Kent Naputo |
| Status | Ongoing |
| Linked communities | Brgy. Sagkahan · Tacloban City |
| Beneficiary categories | Parent, Out-of-School Youth |

**Expected:** success toast showing the auto-generated code — `EXT-2026-007`
on a fresh `migrate:fresh --seed` DB (sequence self-heals past the six seeded
programs; see blueprint v4.11). The program appears in the grid with an
Ongoing badge.

---

## 2. Add 5 objectives — hub → Overview → Results Framework → Manage

Before any data exists, every reach/budget objective shows **Not started** —
that is the 8.6 live derivation working (nothing to compute yet).

Add via **+ Add objective**:

| # | Objective statement | KPI metric | Unit | Baseline | Target | Target date | Manual actual |
|---|---|---|---|---|---|---|---|
| O1 | Enroll parents & OSY in digital literacy sessions | Community Reach (count) | persons | 0 | 5 | 2026-12-31 | — |
| O2 | Reach 20 community members across the program | Community Reach (count) | persons | 0 | 20 | 2026-09-25 | — |
| O3 | Form a barangay digital-literacy volunteer pool | Qualitative (manual tracking) | groups | — | 1 | 2026-09-10 | 0 |
| O4 | Secure a MOA with the Sagkahan Learning Hub | Qualitative (manual tracking) | MOA | — | 1 | 2026-12-31 | 1 |
| O5 | Utilize at least 80% of the program budget | Budget Utilization (%) | % | 0 | 80 | 2026-12-31 | — |

**While adding, observe the new manager features (v4.10):**

- Selecting a KPI metric **hides the "Manual actual" field** (a manual actual
  is never stored for numeric objectives — switching modes clears it) and
  shows a **"Currently computed · live"** box instead:
  - O1/O2 → "No data yet" (no attendance exists yet)
  - O5 → "0.0 %" (allocated budget exists, no utilization yet)
- The manager list shows each objective with a **status badge**, **progress
  bar**, baseline → target → actual line, and an **actual-source tag**
  (`auto (live)` / `manual`).
- The ? tooltip next to the form title explains how every KPI is calculated.

---

## 3. Add 3 activities — hub → Add Activity

Assign **Kent Naputo** to each (click his chip under Assign faculty).

| Activity | Start = End | Times | Venue | Alloc. budget | Status |
|---|---|---|---|---|---|
| A1 · Digital Skills Pre-Assessment & Orientation | 2026-09-05 | 09:00–12:00 | Sagkahan Learning Hub | 3000 | Ongoing |
| A2 · Hands-on Workshop: e-Gov Services & Online Safety | 2026-09-12 | 13:00–17:00 | Sagkahan Learning Hub | 5000 | Ongoing |
| A3 · Digital Bayanihan: Community Tech Helpdesk | 2026-10-03 | 08:00–12:00 | Sagkahan Barangay Hall | 2000 | Draft |

**Expected:** all three save cleanly. Date pickers are constrained to the
program range (8.8) — try typing a date outside 2026-09-01 → 2026-12-31 and
watch the validation block it.

---

## 4. Register 5 beneficiaries — Beneficiaries tab → Register new (×5)

All with Barangay **Sagkahan**, Municipality **Tacloban City**,
Category **Parent** (contact number may stay at the 09123456789 default):

| Name | Age | Sex |
|---|---|---|
| Maria Santos | 42 | Female |
| Josefina Bautista | 38 | Female |
| Rogelio Lim | 45 | Male |
| Analyn Custodio | 29 | Female |
| Eduardo Navarro | 51 | Male |

**Expected:** each row is created and auto-enrolled into the program; the
Beneficiaries tab shows "5 enrolled".

---

## 5. Import attendance — Activities tab → Records → Attendance

Attendance is import-only (v4.13) via the official template. For each
activity, click **Records** → **Attendance** tab, then:

1. **Download official template** — the enrolled roster is pre-filled with
   Beneficiary ID, last/first name, and barangay.
2. Fill the **Status** column (Present / Absent / Excused / Late):
   - **A1**: all 5 beneficiaries → **Present**
   - **A2**: Maria, Josefina, Rogelio, Eduardo → **Present**; Analyn →
     **Late** (late still counts as served — 8.6 counts present+late)
   - Leave a cell blank to leave that beneficiary unrecorded.
3. Upload the file → **Parse file →** → review the per-row preview (Apply /
   Blank / Skip states, per-row errors) → **Confirm & import attendance**.

**Expected:** confirmation toast with the applied/blank/error counts;
attendance counts appear under each activity row. Repeat for A2. (The
**Evaluation** tab in the same modal imports pre/post/satisfaction rows and
averages them into the activity's aggregate columns — optional here.)

---

## 6. Add 3 budget entries — Budget tab

| Item | Amount | Date used | Charge against |
|---|---|---|---|
| Internet & venue allowance | 3500 | 2026-09-05 | A1 |
| Training kits & handouts | 8500 | 2026-09-12 | A2 |
| Facilitator meals & transport | 2000 | 2026-09-12 | A2 |

**Expected:** Utilized = ₱14,000 · 70% progress bar · no warnings (under the
₱20,000 allocation).

---

## 7. Complete A1 and A2 — Activities tab → Complete

Click **Complete** on A1 and A2 (confirm the dialog).

**Expected per completion:**
- Status badge flips to Completed
- An activity-log entry is written (check the admin dashboard recent activity)
- Rendered-hours drafts are auto-created for Kent (3 hrs for A1, 4 hrs for A2 —
  hours = end − start, source = auto). Login as faculty4@lnu.com → header bell
  shows "Rendered hours drafted" notifications; Rendered Hours → My shows two
  pending drafts he can adjust down and submit.

---

## 8. What you should now see

| Where | Expected |
|---|---|
| **Hub → Overview** | O1 **Achieved** (5/5 · `auto (live)`) · O2 **On track** 25% (`auto (live)`) · O3 **Not met** gold bar (`manual` — target date 2026-09-10 passed with actual 0) · O4 **Achieved** (`manual` — 1/1) · O5 **On track** 70% |
| **Manage modal** | Same badges, progress bars, and source tags per objective |
| **Admin dashboard** | Action center → "Objectives at risk" = **2** (O3 not met + O2 due within 14 days) |
| **Analytics → Overview** | Objective chart: 2 achieved · 2 on track · 1 not met; Program Performance tab: reach 5, participation 100%, consistency ~67% |
| **/program-narratives** | Chip **"Objectives met: 2/5"** (no AI generation needed — the chip derives from 8.6) |
| **Objective form** | Edit O1 → "Currently computed · live: 5.0 persons" |

All of these derive live from the effective actual (§8.6) — the stored
`status` column is never consulted.

---

## 9. Deadline notification — scheduler

```
php artisan smartcemes:notify-deadlines
```

**Expected:** output `Deadline notifications sent: 1.` and the admin bell
shows *"Objective target date approaching — 'EXT-2026-007' objective 'Reach
20 community members across the program' is due Sep 25, 2026 (10 days)."*

Re-run the command → `0.` notifications sent (7-day dedup, 5.14).

> Note: on the seeded demo data this count also includes seeded programs
> ending within 14 days — run once on a fresh DB first if you want the
> objective notification isolated.

---

## 10. Live-derivation flip — the payoff demo

Budget tab → add a 4th entry: **"Printing of certificates" · 3000 ·
2026-10-03 · A3**. Utilization jumps to 85%.

**Expected immediately (refresh the hub):**
- **O5 flips On track → Achieved** (85 ≥ 80)
- Analytics objective chart becomes 3 achieved · 1 on track · 1 not met
- Narratives chip becomes **3/5**
- No status field was ever written anywhere — it is all derived from program
  data on every render.

---

## 11. Optional bonus tests

- **8.8 conflict hard-block**: Add Activity on **2026-10-03, 10:00–14:00**
  and try to assign Kent → save is refused: *"Assignment refused: Kent Naputo
  is already assigned to 'Digital Bayanihan…' which overlaps this schedule."*
- **D7 over-allocation**: add a budget entry of ₱10,000 → the entry SAVES
  with an over-allocation warning banner + activity-log entry (never blocks).
- **Faculty read-only**: login as faculty4@lnu.com → My Programs → the hub
  renders read-only (banner, no Manage/Edit buttons).
- **Rendered-hours lifecycle**: as Kent, adjust a draft DOWN (e.g. 3 → 2.5
  hrs with a note), submit; as admin, approve it → entry locks; try editing
  as Kent → refused.
- **Duplicate code self-heal (v4.11)**: create another program → it gets
  `EXT-2026-008` even though the seeded programs never touched the sequence.

---

## Reset between runs

```
php artisan migrate:fresh --seed
```

Reproducible: 58 communities/schools, 6 programs, proposals + demo attachment
files, 6 accounts (password `password`). The public storage link persists
across resets on Windows.
