# SmartCEMES — FACULTY Testing Guide

_You are testing as a **Faculty member** — you encode needs assessments, submit proposals,
respond to availability requests, and manage your rendered hours. Work top to bottom.
**Use a different faculty account per colleague** (see the cheat sheet) so you don't consume
each other's seeded queues._

> **The hierarchy is `College → Program → Project → Activity`.** A **Program** is one of the six broad
> CESO thrusts; a **Project** is the thing with dates, a budget, activities and targets. Where an older
> note says "program" and means the narrow entity, it means a *project*. **You work at the project
> level.**
>
> **There is NO per-professor target, and therefore no attainment % anywhere on your pages.** The
> Director sees your **contribution** — hours rendered, projects led, activities handled, proposals.
> A percentage there would be invented (D-R19).
>
> **Training hours are `trainors × trainees × days` — there is NO `× 8`.** `Days` carries the duration
> (0.5 = a half day). Note this is **training hours delivered**, a different concept from the
> **rendered hours** you submit for yourself — the two are named distinctly on purpose.
>
> **You have no AI access** (D4). Nothing you can click generates or shows AI output.

## Getting in

1. Open **https://disorder-childless-moonlike.ngrok-free.dev**
2. If ngrok shows a warning page, click **Visit Site** (normal for free ngrok).
3. Log in with ANY one of these (password: `password`):

| Account | Persona | Seeded goodies waiting for you |
|---|---|---|
| faculty1@lnu.com | Carlo Sumile | leads **e-LITERACY** · 1 pending availability request · pending 4.0 rendered hrs · rejected proposal to view · pending proposal SIKAD BUHAY |
| faculty2@lnu.com | Bianca Oledan | **2 pending availability requests · pending 2.5 rendered hrs (with note) · pending proposal GULAYAN** · leads 2 **projects** |
| faculty3@lnu.com | Nikko Villas | pending 7.0 rendered hrs · pending proposal SOLID Start · leads HANDA |
| faculty4@lnu.com | Kent Naputo | leads KABUHIAN (completed) · approved proposal with Special Order |

> **faculty5 / faculty6** are the Graduate School roster (Dr. Ramon L. Villamor, Dr. Cristina P. Manalo).

**Your sidebar:**

| Section | Items |
|---|---|
| Overview | Dashboard · My Projects · Calendar |
| My Extension Work | Submit Proposal · My Proposals · Encode Assessment · Availability Requests · Rendered Hours |

---

## 1. Dashboard + notifications

1. After login you land on your Dashboard.

**Expected:** your projects, upcoming activities, personal participation stats, your proposals'
status, **inline availability accept/decline cards** if you have pending requests, and a
**"My Rendered Hours"** summary.

2. Click the **bell icon** (top header).

**Expected:** notifications for things like rendered-hours drafts, proposal decisions, or returned
assessments. You can mark them read.

---

## 2. Encode a needs assessment — Sections I–IX wizard

Path: **Encode Assessment**.

Fill as much or as little as you like — but DO try these specific behaviors:

### 2.1 Section I — respondent info

1. Type a respondent **First name** — try including digits or symbols.

**Expected:** name fields are **letters-only** (spaces, periods, hyphens, apostrophes, Ñ allowed);
digits are rejected as you type.

2. Pick **Civil Status** and **Religion** from the dropdowns.

**Expected:** custom **searchable dropdowns** (not plain browser selects) — click one and type to
filter. **Religion is a closed list** (16 options, no "Other"); **Civil Status** includes *Divorced*
and no longer offers *Live-in*.

### 2.2 Conditional logic (the important part)

1. In the household section, set **Own toilet → Yes**.

**Expected:** the **Toilet type** question appears.

2. Change it to **No**.

**Expected:** the question hides **and its saved value is cleared** — hidden answers can never be
saved by accident.

3. Set **Electricity → No**.

**Expected:** *Light source without power* appears (answer only shows for No-power households).

### 2.3 Exclusive chips + selection caps

1. In **Preferred Training Days**, click **Flexible**, then click **Saturday**.

**Expected:** **Flexible drops out** when a normal day is picked (they are mutually exclusive).

2. In any **problems list** (Section VIII), click 4 options.

**Expected:** the **4th click is ignored** — maximum 3 problems per field. (All seven problem lists
are closed lists with no "Other".)

### 2.4 Save

1. Scroll through the step dots (nine sections) and press **Save** with whatever you have filled.

**Expected:** the record saves with review status **pending** — it goes straight to the
**Secretary's validation queue** (they will validate or return it with remarks).

---

## 3. Import from template (XLSX)

Path: the wizard page → **Import from template** (top of the form).

1. Click **Download template** and open the `.xlsx`.

**Expected:** vertical sheet — labels in column A (Sections I–IX), shaded answer cells with dropdown
suggestions in column B, option guides in column C. The dropdowns are **non-strict**: typed free text
is accepted and auto-mapped later.

2. Fill a few answers in column B (typing off-list values is allowed — they auto-map later), save,
   then on the import page pick **Community / Quarter / Year**, choose the file, and click
   **Parse file**.

**Expected:** preview with context badges, a gold **auto-map panel** (unknown values → "Other", raw
text kept), parsed values, and per-field errors if any. **Never a whole-file reject.**

3. Click **Confirm & create record**.

**Expected:** record created as **pending** → lands in the Secretary's queue.

> **Older horizontal (v1) template files are no longer accepted** — you will get a clear
> "download the current template" error.

---

## 4. Submit a proposal

Path: **Submit Proposal**.

1. Pick a **Project** (try **e-LITERACY**, `EXT-2026-004`), title, description, proposed dates,
   budget estimate, and a **community**.
2. Note the **proposed dates**: you can type any dates, but if they fall **outside the project's
   date range** the Director's approval will be **blocked** later (the 8.8 scheduling rule — the
   server is the backstop).

3. Attach a file (pdf/docx/xlsx/jpg/png, up to 10 MB) and save.

**Expected:** status **Pending**. The Director is notified; on approval the system auto-creates the
activity for you (in **draft**).

4. Go to **My Proposals**.

**Expected:** your proposal listed, plus your seeded ones (see the cheat sheet). Open the rejected
one if you have it (Carlo: TESDA-Ready) — the Director's rejection reason is shown.

---

## 5. Availability requests — accept / decline

Path: **Availability Requests**.

1. Find a **pending** request (Bianca has two; Carlo has one — check your cheat sheet; or ask the
   admin colleague to create one for you).

**Expected:** request details: activity, date, times, and the Director's remarks to you.

2. Click **Decline** and try to confirm with an **empty** reason.

**Expected:** blocked — **decline requires a reason**. Enter one and confirm; the Director is
notified.

3. On a pending request, click **Accept**.

**Expected:** status flips to Accepted and the request feeds the shared calendar. (Accepting is
refused outright if it overlaps your other accepted requests or assignments — by design.)

---

## 6. Rendered hours — My view

Path: **Rendered Hours** (sidebar; this is the **My** view).

1. Look at your entries.

**Expected:** seeded entries in every state, e.g. an **auto-drafted** entry (source = auto, hours =
the activity's duration). Approved ones are **locked** — no edit buttons.

2. On a **draft/pending** entry, click **Adjust** and try typing a **HIGHER** number of hours.

**Expected:** **refused** — faculty may only adjust hours **DOWN** (with a note explaining the
partial participation).

3. Adjust down (e.g. 3 → 2.5) with a note, then **Submit**.

**Expected:** status becomes **pending** and the entry lands in the Director's approval queue (your
admin colleague can approve it — it then locks for good).

> **Rendered hours are YOUR service credit**, tracked per activity. They are **not** the same as the
> training hours delivered to beneficiaries, which are computed as `trainors × trainees × days`.

---

## 7. My Projects — read-only hub

Path: **My Projects**.

1. Open one of your projects (see the cheat sheet for which ones you lead — Carlo leads e-LITERACY).

**Expected:** the same project hub the Director uses, but rendered **READ-ONLY**: a banner says so,
and the Manage/Edit buttons are gone. Faculty view the performance block, activities, and budget;
they never edit them.

2. If a project is not yours, you cannot open it.

**Expected:** a **403**. Your access is scoped to the projects you lead or are assigned to.

---

## 8. Your own faculty profile

Your profile page carries your contribution blocks — college, position, status, expertise, and the
training/performance blocks.

**Open it from the sidebar:** **My Profile → My Faculty Profile**. The URL is `/my-profile`; the page
also answers at `/faculty/{your-id}` — for example `/faculty/1` for Carlo.

**Expected:** **200**, showing your own profile. **Your specialization, department, contact number,
address and expertise areas are yours to edit** — click **Edit profile**. **Employee ID, position,
college, status and your login account (name, email) are Admin-controlled** and read-only for you.
Opening a colleague's id gives a **403** — the policy scopes this to your own record.

> **Your edits save immediately** and are recorded in the audit log, so the Director can see a change
> to your expertise without having to approve it first.

> **A note on why expertise is yours:** the Faculty Directory's expertise filter is how the Director
> matches people to projects, and you are the one who knows your areas. The four locked fields are the
> ones the institution owns — your appointment, your rank, your employment status and the ID it issues.

---

## 9. Calendar

Path: **Calendar**.

**Expected:** the month grid showing **only your own activities**, marked with a **"(You)"** label,
plus project deadlines. Click a day for its side panel.

---

## 10. Security check — you must be locked out of these

While logged in as faculty, visit these URLs directly:

| URL | Why it should be closed |
|---|---|
| `/ai-analysis` | **AI is Admin-only** (D4) |
| `/rendered-hours` | The **approval queue** is Admin-only — you only have the *My* view |
| `/targets` | Targets are Director-only (D-R5) |
| `/programs` | Broad-program CRUD is Director-only |
| `/colleges` | The hierarchy hub is Director-only |
| `/faculty` | The Faculty Management board is Admin-only |
| `/faculty/directory` | The roster is Admin-only |
| `/reports` | Institutional reports are Director-only |
| `/audit-logs` | Admin-only audit trail |

**Expected:** **403 Forbidden** on all of them — role separation is enforced on every route, so you
cannot reach Admin-only pages even by typing the URL.

> **Two deliberate exceptions:** `/projects/{your-own-id}` returns **200** (read-only, §7), and
> `/faculty/{your-own-id}` returns **200** (§8). Everything else in the Admin's world is closed.

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
| 13 | My Projects hub read-only + 403 on someone else's | ☐ |
| 14 | Sidebar **My Profile → My Faculty Profile** = 200; Edit profile saves + logs | ☐ |
| 15 | Calendar shows own events with "(You)" | ☐ |
| 16 | 403 on all ten Admin-only URLs | ☐ |

## Testing together

- Your **encodes/imports feed the Secretary** — tell them when you save one.
- Your **proposals and submitted hours feed the Director** — after you submit, ask them to
  approve/reject and then check your bell for the decision.
- Ask the admin colleague to **create an availability request for your account** if your pending
  queue is empty, and to **complete an activity** to generate rendered-hours auto-drafts for you.
- If seeded queues are consumed, re-run the seeder (`php artisan migrate:fresh --seed`).
