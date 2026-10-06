# Guide 01 — Creating an Extension Project (with real example)

**Role: Admin (Director)** · Path: **Manage Extension Programs → a college → a program → New project**
· Prerequisite: none

You will create a real, working project — **SIKAD-DIGITAL** — that the next
guides build on (targets → activities → attendance → budget).

> **Why "project"?** The hierarchy is `College → Program → Project → Activity`.
> A **Program** is one of the six broad CESO thrusts. A **Project** is the thing
> with dates, a budget, activities and targets. This guide creates a *project*.
> Older notes that say "program" here mean this.

## 1. Get to the form

The admin sidebar collapses the whole hierarchy into **one** entry, and since
`revisions.md` §25 **all create/edit lives inside that hub as modals**. There is
no "New Program" button in the sidebar, and **no create action on `/projects`** —
that page is a read-only list now.

1. Log in as **admin@lnu.com** (password: `password`).
2. Sidebar → **Manage Extension Programs**. The hub opens on the four college
   cards.
3. Click the **CAS** card. View 2 shows the programs CAS delivers.
4. Click the **Information, Communication & Education** program. View 3 shows
   that program's projects.
5. Click **New project** (top-right of the program header). The form opens as a
   **modal** — the page does not navigate.

> **Do not try to create from `/programs` or `/projects`.** Both routes still
> resolve, but they are read-only lists with no inbound links; the hub is the only
> create path by design.

## 2. Fill in exactly this sample

| Field | Value to type / pick |
|---|---|
| College * | **CAS · College of Arts and Sciences** |
| Under program * | **Information, Communication & Education** |
| Project title * | `SIKAD-DIGITAL: Digital Literacy for Parents & OSY` |
| Description | `Basic computer, e-gov, and online-safety training for Sagkahan parents and out-of-school youth.` |
| Planned start * | `2026-09-01` |
| Planned end * | `2026-12-31` |
| Target beneficiaries | `20` |
| Allocated budget (₱) | `20000` |
| Target training hours | `120` |
| Project lead | **Kent Naputo** |
| Linked communities (multi-select) | type `Sagkahan` → pick **Brgy. Sagkahan · Tacloban City** |
| Beneficiary categories (multi-select) | **Parent** + **Out-of-School Youth** |
| Status | **Ongoing** |

Tips:
- **College is the first field for a reason:** it decides the project's CODE
  PREFIX (R-Q4). Pick CME and you get `CME-2026-00X`; pick CAS and you get
  `CAS-2026-00X`. The code is generated *after* the college is known.
- **Under program** is the BROAD level — the six CESO thrusts. A project sits
  inside exactly one. (`programs` has no college of its own: a thrust spans
  colleges, so a program's college membership is derived from its projects.)
- **Target training hours** is the project's ANNUAL target. There is **no budget
  target** — since v4.19 a project has ONE budget figure, the **allocation**, and
  utilization is measured against that. The two target levels are the **project**
  and the **university pool**. See guide 02.
- **Linked communities** and **Beneficiary categories** are searchable
  multi-select dropdowns — click the field, type to filter, click rows to
  select; the count badge shows how many you picked.
- Valid **beneficiary categories**: Farmer, Fisherfolk, Fisherman, Housewife,
  Parent, Senior Citizen, Out-of-School Youth, Student, Vendor, Tricycle
  Driver, Construction Worker, Barangay Worker, Unemployed, Other.
- End date must be **on or after** the start date (validation blocks
  otherwise).

## 3. Save and verify

Click **Create Project**.

**Expected:**
- Success toast: **"Project created — code CAS-2026-002"** (on a freshly seeded
  DB — `CAS-2026-001` already exists). Codes are atomically sequenced **per
  college per year**, so they can never duplicate.
- The new card appears in the list with an **Ongoing** badge, Kent Naputo as
  lead, and ₱0 utilized.

## 4. Open the hub

Click the SIKAD-DIGITAL card.

**Expected:** the **project hub** opens with four tabs —
**Overview | Activities | Beneficiaries | Budget** — plus the project header
(code, status badge, dates, budget). This hub is where guides 02–05 happen.

**Look at the Overview tab now.** It carries the PERFORMANCE block — training
hours vs annual target, budget vs **allocated budget**, and the
trainors / trainees / activities tiles — all at zero. There is deliberately
**no objectives list and no results framework**: those were removed (D-R7) and
replaced by the target model.

## 5. Things to try (validation & sequence)

1. **Create a second project** (any quick values). **Expected:** it gets the
   **next code for its college** — the sequence is per college per year, so a
   CME project and a CAS project number independently.
2. **Search & filter**: type `SIKAD` in the search box; filter by status
   Ongoing; try the college and broad-program filters. **Expected:** the list
   narrows live, and the URL carries the filters, so the view is shareable.
3. **Edit modal** (hub → **Edit**): try shrinking the end date below an
   existing activity's date (after guide 03). **Expected:** blocked with
   "The new range would exclude existing activities…" (8.8 backstop).
4. **Faculty view**: log in as **faculty4@lnu.com** (Kent) → **My Projects**.
   **Expected:** SIKAD-DIGITAL appears (he is the lead) and the hub renders
   **read-only** — no Edit / Add Activity buttons.
5. **The hub's roll-ups**: Sidebar → **Manage Extension Programs** → click the
   **CAS** card, then the **Information, Communication & Education** program.
   **Expected:** SIKAD-DIGITAL is in that program's project grid, and CAS's
   derived tiles have risen by one project.

## Report back

| Check | Pass? |
|---|---|
| Toast shows a COLLEGE-PREFIXED auto code (CAS-2026-002+) | ☐ |
| Card appears with Ongoing badge + lead + community | ☐ |
| A project in a different college numbers independently | ☐ |
| End-before-start date is blocked | ☐ |
| The hub's Overview shows the performance block, not objectives | ☐ |
| Faculty sees the project read-only | ☐ |
