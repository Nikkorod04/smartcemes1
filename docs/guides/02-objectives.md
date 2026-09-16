# Guide 02 — Objectives & the Results Framework (with real examples)

**Role: Admin** · Path: program hub → **Overview** tab
· Prerequisite: guide 01 (SIKAD-DIGITAL exists)

You will add 5 objectives — a mix of **numeric** (live-computed by the
system) and **qualitative** (tracked manually) — and watch their statuses
derive automatically as you add data in later guides.

## 1. Open the objective manager

1. Log in as **admin@lnu.com** → **Extension Programs** → open
   **SIKAD-DIGITAL**.
2. On the **Overview** tab find the **Results Framework** card, then click
   **Manage**.

**Expected:** the objective manager modal opens. Before any data exists,
every reach/budget objective will show **Not started** — that is the live
derivation working (nothing to compute yet).

## 2. Add these 5 objectives

Click **+ Add objective** and fill each row below. After each save, click
**+ Add objective** again for the next.

| # | Objective statement | KPI metric | Unit | Baseline | Target | Target date | Manual actual |
|---|---|---|---|---|---|---|---|
| O1 | `Enroll parents & OSY in digital literacy sessions` | **Community Reach (count)** | `persons` | 0 | 5 | 2026-12-31 | — |
| O2 | `Reach 20 community members across the program` | **Community Reach (count)** | `persons` | 0 | 20 | 2026-09-25 | — |
| O3 | `Form a barangay digital-literacy volunteer pool` | *(leave blank = qualitative)* | `groups` | — | 1 | 2026-09-10 | 0 |
| O4 | `Secure a MOA with the Sagkahan Learning Hub` | *(leave blank = qualitative)* | `MOA` | — | 1 | 2026-12-31 | 1 |
| O5 | `Utilize at least 80% of the program budget` | **Budget Utilization (%)** | `%` | 0 | 80 | 2026-12-31 | — |

### What you should notice WHILE filling the form

1. When you pick a **KPI metric** (O1/O2/O5):
   - The **"Manual actual" field disappears** — numeric objectives compute
     their own actual from program data; you never type one.
   - A **"Currently computed · live"** box appears showing the real current
     value: for O1/O2 it says **"No data yet"** (no attendance exists yet);
     for O5 it shows **"0.0 %"** (budget allocated, nothing utilized yet).
2. When you leave KPI metric blank (O3/O4 — qualitative):
   - The **Manual actual** field stays visible (that's how you track
     qualitative progress), and an **evidence notes** field lets you record
     proof.
3. Click the **? tooltip** next to the form title — the full KPI dictionary:
   every dashboard, report and AI input derives from these formulas.

### Valid KPI metric options (the locked 8.6 set)

Participation Rate (%) · Activity Completion Rate (%) · Attendance
Consistency (%) · Budget Utilization (%) · Knowledge Gain (points) · Cost per
Beneficiary (currency) · Community Reach (count)

**Note:** target date must be on/after the program's start date (validation).

## 3. Verify the manager list

After saving all five, look at the manager list (and the Overview card).

**Expected:** each objective shows a **status badge**, a **progress bar**, a
`baseline → target → actual` line, and an **actual-source tag**:
`auto (live)` for O1/O2/O5, `manual` for O3/O4.

## 4. How statuses derive (why they'll change later)

The status is computed live on every render — never stored:

| Status | Rule |
|---|---|
| **Achieved** | actual ≥ target |
| **Not met** | target date has passed AND actual < target |
| **On track** | progressing, target date not yet passed |
| **Not started** | no data to compute yet |

As of mid-September 2026, O3 (target date Sep 10, manual actual 0) already
shows **Not met** — its gold progress bar marks a missed qualitative target.
O2 will flip from On track to Not met after Sep 25 if fewer than 20 people
have been served by then. That's correct behavior, not a bug.

## 5. What you'll see after guides 03–05

Once attendance and budget data exist (next guides), refresh the hub:

| # | Expected status |
|---|---|
| O1 | **Achieved** — 5/5 served, `auto (live)` |
| O2 | **On track** 25% (5/20) — until Sep 25, then Not met if still short |
| O3 | **Not met** (manual — date passed, actual 0) |
| O4 | **Achieved** (manual — 1/1) |
| O5 | **On track** 70% → flips to **Achieved** at 85% in guide 05 |

Also check: **/program-narratives** page — the SIKAD-DIGITAL card shows the
chip **"Objectives met: X/Y"** derived from these same rules (no AI needed).

## Report back

| Check | Pass? |
|---|---|
| KPI metric selection hides Manual actual + shows live box | ☐ |
| O1/O2 "No data yet" · O5 "0.0 %" | ☐ |
| Qualitative objectives keep Manual actual + evidence | ☐ |
| Badges + progress bars + source tags render | ☐ |
| O3 shows Not met (gold bar) | ☐ |
| Edit O1 later shows "Currently computed · live: 5.0 persons" | ☐ |
