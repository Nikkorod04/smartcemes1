# Guide 03 — Activities, Faculty Assignment & Attendance (with real examples)

**Role: Admin** · Path: project hub → **Activities** tab
· Prerequisites: guides 01–02 (SIKAD-DIGITAL exists, with its annual targets)

You will add 3 activities (assigning **Kent Naputo** to each), test the
scheduling hard-blocks, import attendance from the official template, and
complete activities — which auto-drafts rendered hours for Kent.

## The two "hours" concepts — read this first

They are different numbers and the system names them distinctly:

| Concept | Formula | Where it shows | What it means |
|---|---|---|---|
| **Training hours** | `trainors × trainees × days` | The project hub's performance block | Delivery — how much training the project put out |
| **Rendered hours** | `end_time − start_time` | Rendered Hours (faculty service credit) | Service — what a faculty member claims for taking part |

**There is NO `× 8` in the training-hours formula.** `Days` already carries the
duration (1.0 = a full day, **0.5 = a half day**), so multiplying by 8
double-counted. `TrainingHoursService` is the single implementation.

Trainee resolution order: **imported attendance** (present + late) → the
activity's **Participants** field → 0. The figure carries a source tag so the
Director can see which one produced it.

---

## 1. Add these 3 activities

On the **Activities** tab, click **Add Activity** and save each row (click
**Add Activity** again for the next). Click **Kent Naputo's chip** under
**Assign faculty** in every one.

| | A1 | A2 | A3 |
|---|---|---|---|
| Title | `Digital Skills Pre-Assessment & Orientation` | `Hands-on Workshop: e-Gov Services & Online Safety` | `Digital Bayanihan: Community Tech Helpdesk` |
| Start = End date | 2026-09-05 | 2026-09-12 | 2026-10-03 |
| Start time | 09:00 | 13:00 | 08:00 |
| End time | 12:00 | 17:00 | 12:00 |
| **Days** | `0.5` | `0.5` | `0.5` |
| **Participants** | `5` | `5` | `5` |
| Venue | `Sagkahan Learning Hub` | `Sagkahan Learning Hub` | `Sagkahan Barangay Hall` |
| Allocated budget | 3000 | 5000 | 2000 |
| Status | **Ongoing** | **Ongoing** | **Draft** |
| Assign faculty | Kent Naputo | Kent Naputo | Kent Naputo |

**Expected:** all three save cleanly ("Activity added" toast) and appear in the
date-ordered table with Kent listed under each.

**Check the Days field's validation:** `Days` rejects anything below a half day
and rejects values that are not half-day increments (try `0.25` or `0.7`).
Clearing **Participants** writes NULL, not 0 — an absent number is not the same
as zero.

---

## 2. Test the project-range block (8.8)

1. Click **Add Activity** again; set the start date to `2027-02-01`
   (outside the project's Sep 1 – Dec 31, 2026 range); save.

**Expected:** validation blocks it — *"Activity dates must fall within the
project range (2026-09-01 – 2026-12-31)."* Close the form without saving.

---

## 3. Test the faculty-overlap block (8.8)

1. Click **Add Activity**; fill: title `Overlap Test`, date
   **2026-10-03**, times **10:00 – 14:00**, assign **Kent Naputo**; save.

**Expected:** the save is **REFUSED** with a message naming the clash and its
date range:

> *"Assignment refused: Kent Naputo is already assigned to 'Digital Bayanihan:
> Community Tech Helpdesk' (Oct 3, 2026 – Oct 3, 2026) which overlaps this
> schedule (8.8)."*

No warn-and-confirm — it is blocked outright. Close the form.

> **This guard only fires when the faculty member already has an activity**, so
> it is easy to break without noticing. It is covered by
> `DefenceWalkthroughTest::test_the_schedule_guard_refuses_a_clashing_assignment_with_a_message`.

---

## 4. Register the attendees (needed for attendance)

Skip to **guide 04** now and register the 5 beneficiaries there, then come
back. (Attendance and evaluation imports are scoped to the project's enrolled
beneficiaries — the generated templates pre-fill the roster, so there is nobody
to record until they exist.)

---

## 5. Import attendance

Attendance is import-only (v4.13) through the official template.

1. On row **A1**, click **Records** → **Attendance** tab.
2. Click **Download official template** — the 5 enrolled beneficiaries are
   pre-filled with their Beneficiary ID, names, and barangay.
3. Fill the **Status** column with **Present** for all 5 (Maria, Josefina,
   Rogelio, Analyn, Eduardo). Leave a cell blank to leave that beneficiary
   unrecorded.
4. Upload the file and click **Parse file →**; review the per-row preview
   (Apply / Blank / Skip + any per-row errors), then **Confirm & import
   attendance**.

**Expected:** "Attendance imported — 5 applied" toast; the attendance count
appears on the activity row.

5. Repeat on **A2** via **Records**: Maria, Josefina, Rogelio, Eduardo →
   **Present**; Analyn → **Late**.

**Expected:** imports cleanly. (Late still counts as served — the trainee count
is present + late. Rows are matched by Beneficiary ID + name; unknown,
duplicate, or invalid rows are skipped per row — never a whole-file reject.)

> **Re-importing the same file UPDATES rather than duplicating.** And because
> imported attendance *replaces* the `Participants` fallback, the computed hours
> can move when attendance lands — keep the two consistent, as they are here
> (5 participants, 5 attendees).

*(Optional)* The same **Records** modal has an **Evaluation** tab: download
its template, enter each beneficiary's Pre-Test (0–100), Post-Test (0–100),
and Satisfaction (1–5), then confirm — the values are averaged into the
activity's aggregate scores (D13).

---

## 6. Complete A1 and A2

Click **Complete** on A1, then on A2 (confirm the dialog each time).

**Expected per completion:**
- Status badge flips to **Completed**.
- An activity-log entry is written (visible on the admin dashboard's recent
  activity feed).
- **Rendered-hours drafts are auto-created for Kent** — **3 hrs** for A1
  (09:00–12:00) and **4 hrs** for A2 (13:00–17:00); hours = end − start,
  source = auto.
- Kent gets a bell notification: "Rendered hours drafted".

---

## 7. Verify from Kent's side

Log in as **faculty4@lnu.com** (or open a second browser window):

1. **Bell icon** → notifications "Rendered hours drafted" for both
   activities.
2. **Rendered Hours** (sidebar — this is the "My" view): two new pending
   drafts (3.00 and 4.00 hrs, source auto). Guide 09 finishes their
   lifecycle (adjust down → submit → approve → lock).

---

## 8. Check the target model move

Back as admin, refresh the project hub **Overview** tab.

**Expected:**

| Figure | Value |
|---|---|
| Training hours rendered | **5** of the 120-hour annual target → **4.2 %** |
| Trainors / Trainees / Activities | 1 / 5 / 2 completed (3 total) |

The arithmetic: `1 trainor × 5 trainees × 0.5 days = 2.5` per activity, and two
activities are complete → **5 hours**. Nothing was stored — the roll-up reads
the activities you just recorded on every render.

Note the contrast with §7: **5 training hours** delivered, but **7 rendered
hours** (3 + 4) claimed by Kent. Different concepts, different numbers, both
correct.

**There is no objective to flip.** The old guide ended by watching an objective
turn "Achieved" from live attendance — that machinery was removed (D-R7). What
moved instead is the project's attainment against its own annual target, and the
university pool's consumption.

---

## Report back

| Check | Pass? |
|---|---|
| 3 activities save with Kent assigned, Days + Participants recorded | ☐ |
| Out-of-project-range date blocked | ☐ |
| Faculty overlap assignment refused, message names the clash + range | ☐ |
| Attendance XLSX import (A1 all present; A2 + late) | ☐ |
| Completing A1/A2 creates rendered-hours drafts (3h / 4h) | ☐ |
| Kent notified (bell + My Rendered Hours) | ☐ |
| Project Overview shows 5 / 120 hrs → 4.2 % | ☐ |
