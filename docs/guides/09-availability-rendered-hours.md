# Guide 09 — Availability Requests & the Rendered-Hours Lifecycle (worked example)

**Roles: Admin (requests/approves) + Faculty (responds/submits)** · Needs
**two browser windows** · Prerequisite: none (guide 03's completed activities
add extra drafts to play with)

Two workflows live here: **availability** (Admin asks → Faculty answers) and
**rendered hours** (auto-drafted on activity completion → Faculty adjusts
down → Admin approves → locked forever).

## Part A — Availability request cycle

### A1. Admin creates the request

Log in as **admin@lnu.com** → **Availability Requests** → click **New
Request** (create button).

| Field | Value |
|---|---|
| Faculty | **Carlo Sumile** (or whichever account your friend is using) |
| Activity | any activity (e.g. *CHAT & CLICK* from guide 08, or a seeded one) |
| Date | auto-fills from the activity's schedule — keep it |
| Start / End time | auto-fill from the activity — keep them (editable) |
| Remarks | `Please lead the registration desk and participant tracking for this session.` |

Save.

**Expected:**
- Toast: "Availability request sent to faculty".
- The request appears in the admin's **Awaiting response** list.
- The faculty gets a **bell notification**: "Availability requested".

### A2. Faculty declines — reason required (min 5 chars)

Log in as that faculty → **Availability Requests** (or use the inline card
on their dashboard).

1. Find the pending request; click **Decline** and try to confirm **empty**.

**Expected:** blocked — a decline reason is **required**.

2. Type `Class conflict — university accreditation week.` and confirm.

**Expected:** status flips to **Declined** with the reason recorded; the
admin's list moves it to Responded and the admin gets a notification
("Availability declined — reason: …").

### A3. Admin sends another; faculty accepts

Repeat A1 (a second request), then as the faculty click **Accept**.

**Expected:**
- Status flips to **Accepted**; the admin is notified.
- The accepted slot now feeds the **shared Calendar** (check the date on the
  Calendar page — all roles see it).
- If the slot overlaps the faculty's other accepted requests or assigned
  activities, the accept is **refused** with an overlap message (8.8) —
  by design.

## Part B — Rendered-hours lifecycle (8.9)

If you did guide 03, **faculty4 (Kent)** already has two **auto-drafted**
entries (3.00 and 4.00 hrs). Otherwise use any seeded pending entry (e.g.
Bianca's 2.5-hr *Remedial Reading Session Batch 3*).

### B1. Faculty: adjust DOWN only

Log in as the faculty → **Rendered Hours** (the My view).

1. On a pending draft, click **Adjust**. Try typing a **HIGHER** number
   (e.g. 3 → 5).

**Expected:** refused — *"Hours may only be adjusted DOWN (max 3)."*

2. Adjust down — e.g. hours `2.5`, note
   `Left early to co-facilitate the second breakout group.` (note required,
   min 5 chars) — and save.

**Expected:** toast "Hours adjusted down"; the entry now reads 2.5 hrs with
the adjustment note appended to its remarks (source flips to manual).

3. Click **Submit** on the adjusted entry.

**Expected:** status becomes **Pending** (in the Director's approval queue);
the admin is notified ("Rendered hours submitted").

### B2. Admin: approve → locked

Log in as admin → **Rendered Hours** (approval queue).

1. Find the submitted entry; click **Approve**.

**Expected:**
- Toast: **"Rendered hours approved — entry locked"**.
- The entry is **immutable** from now on — the faculty's Adjust/Edit buttons
   are gone (try it), and the approval is audit-logged.
- The faculty is notified ("Rendered hours approved").

2. On another pending entry, try **Reject** with empty remarks.

**Expected:** blocked — rejection remarks are **required (min 5 chars)**.
Fill remarks (e.g. `Attendance sheet shows AM session only; please resubmit
half-day hours.`) and confirm.

### B3. Faculty: verify the lock

Back as the faculty → **Rendered Hours**.

**Expected:** the approved entry shows an **Approved/Locked** state with no
edit controls; your approved-hours total (top of the page) includes it. The
rejected entry shows the Director's remarks.

## Report back

| Check | Pass? |
|---|---|
| Admin request → faculty bell notification | ☐ |
| Decline blocked without a reason (min 5 chars) | ☐ |
| Accept flips status + admin notified + feeds calendar | ☐ |
| Adjust UP refused; adjust down + note works | ☐ |
| Submit → admin queue + notification | ☐ |
| Approve → "entry locked", immutable afterwards | ☐ |
| Reject requires remarks (min 5 chars) | ☐ |
