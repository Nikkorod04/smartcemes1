# SmartCEMES — FACULTY Testing Guide

_You are testing as a **Faculty member** — you encode needs assessments,
submit proposals, respond to availability requests, and manage your rendered
hours. Work top to bottom. **Use a different faculty account per friend** (see
the cheat sheet) so you don't consume each other's seeded queues._

## Getting in

1. Open **https://disorder-childless-moonlike.ngrok-free.dev**
2. If ngrok shows a warning page, click **Visit Site** (normal for free ngrok).
3. Log in with ANY one of these (password: `password`):

| Account | Persona | Seeded goodies waiting for you |
|---|---|---|
| faculty1@lnu.com | Carlo Sumile | 1 pending availability request · pending 4.0 rendered hrs · rejected proposal to view · pending proposal SIKAD BUHAY |
| faculty2@lnu.com | Bianca Oledan | **2 pending availability requests · pending 2.5 rendered hrs (with note) · pending proposal GULAYAN** · leads 2 programs |
| faculty3@lnu.com | Nikko Villas | pending 7.0 rendered hrs · pending proposal SOLID Start · leads HANDA |
| faculty4@lnu.com | Kent Naputo | leads KABUHIAN (completed) · approved proposal with Special Order |

**Your sidebar:** Dashboard · My Programs · Calendar · Submit Proposal · My
Proposals · Encode Assessment · Availability Requests · Rendered Hours.

---

## 1. Dashboard + notifications

1. After login you land on your Dashboard.

**Expected:** your programs, upcoming activities, and inline **availability
accept/decline cards** if you have pending requests.

2. Click the **bell icon** (top header).

**Expected:** notifications for things like rendered-hours drafts, proposal
decisions, or returned assessments. You can mark them read.

---

## 2. Encode a needs assessment — Sections I–IX wizard

Path: **Encode Assessment**.

Fill as much or as little as you like — but DO try these specific behaviors:

### 2.1 Section I — respondent info

1. Type a respondent **First name** — try including digits or symbols.

**Expected:** name fields are **letters-only** (spaces, periods, hyphens,
apostrophes, Ñ allowed); digits are rejected as you type.

2. Pick **Civil Status** and **Religion** from the dropdowns.

**Expected:** custom **searchable dropdowns** (not plain browser selects) —
click one and type to filter.

### 2.2 Conditional logic (the important part)

1. In the household section, set **Own toilet → Yes**.

**Expected:** the **Toilet type** question appears.

2. Change it to **No**.

**Expected:** the question hides **and its saved value is cleared** — hidden
answers can never be saved by accident.

3. Set **Electricity → No**.

**Expected:** *Light source without power* appears (answer only shows for
No-power households).

### 2.3 Exclusive chips + selection caps

1. In **Preferred Training Days**, click **Flexible**, then click **Saturday**.

**Expected:** **Flexible drops out** when a normal day is picked (they are
mutually exclusive).

2. In any **problems list** (Section VIII), click 4 options.

**Expected:** the **4th click is ignored** — maximum 3 problems per field.

### 2.4 Save

1. Scroll through the step dots (nine sections) and press **Save** with
   whatever you have filled.

**Expected:** the record saves with review status **pending** — it goes
straight to the **Secretary's validation queue** (they will validate or return
it with remarks).

---

## 3. Import from template (XLSX)

Path: the wizard page → **Import from template** (top of the form).

1. Click **Download template** and open the `.xlsx`.

**Expected:** vertical sheet — labels in column A (Sections I–IX), shaded
answer cells with dropdown suggestions in column B, option guides in column C.

2. Fill a few answers in column B (typing off-list values is allowed — they
   auto-map later), save, then on the import page pick **Community / Quarter /
   Year**, choose the file, and click **Parse file**.

**Expected:** preview with context badges, a gold **auto-map panel** (unknown
values → "Other", raw text kept), parsed values, and per-field errors if any.

3. Click **Confirm & create record**.

**Expected:** record created as **pending** → lands in the Secretary's queue.

---

## 4. Submit a proposal

Path: **Submit Proposal**.

1. Pick a **Program** (try EXT-2026-004 e-LITERACY), title, description,
   proposed dates, budget estimate, and a **community**.
2. Note the **proposed dates**: you can type any dates, but if they fall
   **outside the program's date range** the Director's approval will be
   **blocked** later (the 8.8 scheduling rule — the server is the backstop).

3. Attach a file (pdf/docx/xlsx/jpg/png, up to 10 MB) and save.

**Expected:** status **Pending**. The Director is notified; on approval the
system auto-creates the activity for you.

4. Go to **My Proposals**.

**Expected:** your proposal listed, plus your seeded ones (see cheat sheet).
Open the rejected one if you have it (Carlo: TESDA-Ready) — the Director's
rejection reason is shown.

---

## 5. Availability requests — accept / decline

Path: **Availability Requests**.

1. Find a **pending** request (Bianca has two; Carlo has one — check your
   cheat sheet; or ask the admin friend to create one for you).

**Expected:** request details: activity, date, times, and the Director's
remarks to you.

2. Click **Decline** and try to confirm with an **empty** reason.

**Expected:** blocked — **decline requires a reason**. Enter one and confirm;
the Director is notified.

3. On a pending request, click **Accept**.

**Expected:** status flips to Accepted and the request feeds the shared
calendar. (Accepting is refused outright if it overlaps your other accepted
requests or assignments — by design.)

---

## 6. Rendered hours — My

Path: **Rendered Hours** (sidebar; this is the **My** view).

1. Look at your entries.

**Expected:** seeded entries in every state, e.g. an **auto-drafted** entry
(source = auto, hours = the activity's duration). Approved ones are **locked**
— no edit buttons.

2. On a **draft/pending** entry, click **Adjust** and try typing a **HIGHER**
   number of hours.

**Expected:** **refused** — faculty may only adjust hours **DOWN** (with a
note explaining the partial participation).

3. Adjust down (e.g. 3 → 2.5) with a note, then **Submit**.

**Expected:** status becomes **pending** and the entry lands in the Director's
approval queue (your admin friend can approve it — it then locks for good).

---

## 7. My Programs — read-only hub

Path: **My Programs**.

1. Open one of your programs (see the cheat sheet for which ones you lead).

**Expected:** the same program hub the Director uses, but rendered
**READ-ONLY**: a banner says so, and the Manage/Edit buttons are gone. Faculty
view the results framework, activities, and budget; they never edit them.

---

## 8. Calendar

Path: **Calendar**.

**Expected:** the month grid showing **only your own activities**, marked
with a **"(You)"** label, plus deadlines. Click a day for its side panel.

---

## 9. Security check — you must be locked out of these

While logged in as faculty, visit these URLs directly:

1. `https://disorder-childless-moonlike.ngrok-free.dev/analytics`
2. `https://disorder-childless-moonlike.ngrok-free.dev/ai-analysis`
3. `https://disorder-childless-moonlike.ngrok-free.dev/rendered-hours`

**Expected:** **403 Forbidden** on all three — Analytics and the AI pages are
Admin-only, and the rendered-hours approval queue too (you only have the My
view).

---

## Report-back checklist

| # | Feature | Pass? |
|---|---|---|
| 1 | Dashboard cards + bell notifications | ☐ |
| 2 | Wizard: letters-only name fields | ☐ |
| 3 | Wizard: toilet/electricity conditionals show + clear | ☐ |
| 4 | Wizard: Flexible exclusive chip | ☐ |
| 5 | Wizard: max-3 problems | ☐ |
| 6 | Wizard save → pending (secretary queue) | ☐ |
| 7 | Import: download → parse → preview → confirm | ☐ |
| 8 | Proposal: submit with attachment → pending | ☐ |
| 9 | Availability: decline requires reason | ☐ |
| 10 | Availability: accept works | ☐ |
| 11 | Rendered hours: adjust UP refused | ☐ |
| 12 | Rendered hours: adjust down + submit → pending | ☐ |
| 13 | My Programs hub read-only | ☐ |
| 14 | Calendar shows own events with "(You)" | ☐ |
| 15 | 403 on /analytics, /ai-analysis, /rendered-hours | ☐ |

## Testing together

- Your **encodes/imports feed the Secretary** — tell them when you save one.
- Your **proposals and submitted hours feed the Director (admin friend)** —
  after you submit, ask them to approve/reject and then check your bell for
  the decision.
- Ask the admin friend to **create an availability request for your account**
  if your pending queue is empty, and to **complete an activity** to generate
  rendered-hours auto-drafts for you.
- If seeded queues are consumed, ask the owner to re-run the seeder
  (`php artisan migrate:fresh --seed`).
