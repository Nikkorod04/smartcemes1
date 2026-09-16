# Guide 03 — Activities, Faculty Assignment & Attendance (with real examples)

**Role: Admin** · Path: program hub → **Activities** tab
· Prerequisites: guides 01–02 (SIKAD-DIGITAL + objectives exist)

You will add 3 activities (assigning **Kent Naputo** to each), test the
scheduling hard-blocks, import attendance from the official template, and
complete activities — which auto-drafts rendered hours for Kent.

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
| Venue | `Sagkahan Learning Hub` | `Sagkahan Learning Hub` | `Sagkahan Barangay Hall` |
| Allocated budget | 3000 | 5000 | 2000 |
| Status | **Ongoing** | **Ongoing** | **Draft** |
| Assign faculty | Kent Naputo | Kent Naputo | Kent Naputo |

**Expected:** all three save cleanly ("Activity added" toast) and appear in
the date-ordered table with Kent listed under each.

## 2. Test the program-range block (8.8)

1. Click **Add Activity** again; set the start date to `2027-02-01`
   (outside the program's Sep 1 – Dec 31, 2026 range); save.

**Expected:** validation blocks it — *"Activity dates must fall within the
program range (2026-09-01 – 2026-12-31)."* Close the form without saving.

## 3. Test the faculty-overlap block (8.8)

1. Click **Add Activity**; fill: title `Overlap Test`, date
   **2026-10-03**, times **10:00 – 14:00**, assign **Kent Naputo**; save.

**Expected:** save is **REFUSED** with a clear message like:
*"Assignment refused: Kent Naputo is already assigned to 'Digital Bayanihan:
Community Tech Helpdesk' (Oct 3, 2026) which overlaps this schedule (8.8)."*
No warn-and-confirm — it is blocked outright. Close the form.

## 4. Register the attendees (needed for attendance)

Skip to **guide 04** now and register the 5 beneficiaries there, then come
back. (Attendance and evaluation imports are scoped to the program's
enrolled beneficiaries — the generated templates pre-fill the roster, so
there is nobody to record until they exist.)

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

**Expected:** imports cleanly. (Late still counts as served — attendance
consistency counts present + late. Rows are matched by Beneficiary ID +
name; unknown, duplicate, or invalid rows are skipped per row — never a
whole-file reject.)

*(Optional)* The same **Records** modal has an **Evaluation** tab: download
its template, enter each beneficiary's Pre-Test (0–100), Post-Test (0–100),
and Satisfaction (1–5), then confirm — the values are averaged into the
activity's aggregate scores (D13).

## 6. Complete A1 and A2

Click **Complete** on A1, then on A2 (confirm the dialog each time).

**Expected per completion:**
- Status badge flips to **Completed**.
- An activity-log entry is written (visible on the admin dashboard's recent
  activity feed).
- **Rendered-hours drafts are auto-created for Kent** — 3 hrs for A1
  (09:00–12:00) and 4 hrs for A2 (13:00–17:00); hours = end − start,
  source = auto.
- Kent gets a bell notification: "Rendered hours drafted".

## 7. Verify from Kent's side

Log in as **faculty4@lnu.com** (or open a second browser window):

1. **Bell icon** → notifications "Rendered hours drafted" for both
   activities.
2. **Rendered Hours** (sidebar — this is the "My" view): two new pending
   drafts (3.00 and 4.00 hrs, source auto). Guide 09 finishes their
   lifecycle (adjust down → submit → approve → lock).

## 8. Check the objective flip

Back as admin, refresh the hub **Overview** tab.

**Expected:** **O1 Achieved** (5/5 served — attendance fed Community Reach
live), **O2 On track 25%** (`auto (live)`). Nothing was stored — the
objective machinery read the attendance you just recorded.

## Report back

| Check | Pass? |
|---|---|
| 3 activities save with Kent assigned | ☐ |
| Out-of-program-range date blocked | ☐ |
| Faculty overlap assignment refused | ☐ |
| Attendance XLSX import (A1 all present; A2 + late) | ☐ |
| Completing A1/A2 creates rendered-hours drafts (3h/4h) | ☐ |
| Kent notified (bell + My Rendered Hours) | ☐ |
| O1 flips to Achieved live | ☐ |
