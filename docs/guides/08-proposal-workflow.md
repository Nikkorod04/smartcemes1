# Guide 08 — Proposal Workflow: Submit → Approve → Auto-created Activity (worked example)

**Roles: Faculty (submits) + Admin (approves)** · Needs **two browser
windows** (or two friends) · Prerequisite: none

You will submit a valid proposal (which the Director approves — the system
auto-creates the activity), then submit one with out-of-range dates (whose
approval is **blocked**), and finally test the rejection path.

## Part A — A valid proposal (faculty side)

Log in as **faculty1@lnu.com** (Carlo Sumile) → sidebar **Submit Proposal**.

| Field | Value |
|---|---|
| Program | **e-LITERACY: Digital Literacy for Parents & Senior Citizens** (EXT-2026-004, runs Jun 8 – Nov 28, 2026) |
| Community | **Brgy. Sagkahan · Tacloban City** |
| Title | `CHAT & CLICK: Weekend Digital Mentoring for Sagkahan Parents` |
| Description | `Weekend one-on-one mentoring sessions pairing LNU student volunteers with parents for smartphones, e-gov apps, and online safety.` |
| Proposed start date | `2026-10-04` *(inside the program range)* |
| Proposed end date | `2026-11-15` |
| Budget estimate | `18000` |
| Attachments | any PDF/DOCX/XLSX/JPG/PNG (max 5 files, 10 MB each) — e.g. a one-page concept paper |

Click **Submit**.

**Expected:**
- Toast: **"Proposal submitted — pending Director review"**.
- It appears under **My Proposals** with a Pending badge.
- The Director (admin@lnu.com) gets a bell notification: "New proposal
  submitted".

## Part B — Approve it (admin side)

Log in as **admin@lnu.com** in the second window → **Proposals**.

1. Open **CHAT & CLICK** (Pending, Carlo).

**Expected:** the detail modal shows the program, community, dates, budget,
and your attachment as a **downloadable row** (click it — the real file
downloads).

2. Click **Approve**. Optionally attach a **Special Order PDF** (skipping is
   fine — an informational notice appears), add remarks, confirm.

**Expected:**
- Toast: **"Proposal approved — activity auto-created (draft)"**.
- The proposal flips to **Approved** with approver + timestamp.
- A **draft activity** with the same title and dates appears in
   EXT-2026-004's hub → Activities tab.
- Carlo gets a bell notification: "Proposal approved".

## Part C — The blocked approval (8.8 range rule)

### C1. Submit an out-of-range proposal (faculty side, as Carlo)

| Field | Value |
|---|---|
| Program | **e-LITERACY … (EXT-2026-004)** — same program (range Jun 8 – Nov 28, 2026) |
| Community | **Brgy. Sagkahan · Tacloban City** |
| Title | `CODE NIGHT: Holiday Coding Camp for Kids` |
| Proposed start date | `2026-12-01` ← **after the program ends** |
| Proposed end date | `2027-01-15` |
| Budget estimate | `15000` |

Submit it. **Expected:** it saves fine — submission does not range-check
(the check happens at approval; that's the design).

### C2. Try to approve it (admin side)

Open **CODE NIGHT** → **Approve** → confirm.

**Expected:** **BLOCKED** with an error toast: *"Approval blocked: proposed
dates (Dec 1, 2026 – Jan 15, 2027) fall outside the program range (8.8)."*
The proposal stays Pending. (The seeded **SIKAD BUHAY** proposal is the same
demo — after the owner re-seeds, its dates are Dec 15 – Jan 10, outside
EXT-2026-005's range.)

## Part D — The rejection path (admin side)

1. Open any pending proposal (e.g. the seeded **SOLID Start** from Nikko).
2. Click **Reject** and try to confirm with an empty reason.

**Expected:** blocked — the reason is **required (minimum 10 characters)**.

3. Type a realistic reason — e.g. `Budget estimate exceeds the FY ceiling for this program; please revise costing or split into two phases.` — and confirm.

**Expected:**
- Toast: "Proposal rejected with remarks"; the proposal shows **Rejected**
  with the reason recorded.
- The faculty gets a bell notification: "Proposal rejected — see remarks".

## Report back

| Check | Pass? |
|---|---|
| Submit → Pending + admin notified | ☐ |
| Attachment downloads from the detail modal | ☐ |
| Approve → toast + draft activity auto-created in the hub | ☐ |
| Out-of-range proposal: submission OK, approval BLOCKED | ☐ |
| Reject requires a reason (min 10 chars) | ☐ |
| Faculty notified of both decisions | ☐ |
