# Guide 01 — Creating an Extension Program (with real example)

**Role: Admin (Director)** · Path: **Extension Programs → New Program**
· Prerequisite: none

You will create a real, working program — **SIKAD-DIGITAL** — that the next
guides build on (objectives → activities → attendance → budget).

## 1. Open the form

1. Log in as **admin@lnu.com** (password: `password`).
2. Sidebar → **Extension Programs**. You will see the six seeded programs
   (EXT-2026-001 … 006) as cards with status badges.
3. Click **New Program** (top-right).

## 2. Fill in exactly this sample

| Field | Value to type / pick |
|---|---|
| Program title | `SIKAD-DIGITAL: Digital Literacy for Parents & OSY` |
| Description | `Basic computer, e-gov, and online-safety training for Sagkahan parents and out-of-school youth.` |
| Planned start date | `2026-09-01` |
| Planned end date | `2026-12-31` |
| Target beneficiaries | `20` |
| Allocated budget | `20000` |
| Program lead | **Kent Naputo** |
| Linked communities (multi-select) | type `Sagkahan` → pick **Brgy. Sagkahan · Tacloban City** |
| Beneficiary categories (multi-select) | **Parent** + **Out-of-School Youth** |
| Status | **Ongoing** |

Tips:
- **Linked communities** and **Beneficiary categories** are searchable
  multi-select dropdowns — click the field, type to filter, click rows to
  select; the count badge shows how many you picked.
- Valid **beneficiary categories**: Farmer, Fisherfolk, Fisherman, Housewife,
  Parent, Senior Citizen, Out-of-School Youth, Student, Vendor, Tricycle
  Driver, Construction Worker, Barangay Worker, Unemployed, Other.
- End date must be **on or after** the start date (validation blocks
  otherwise).

## 3. Save and verify

Click **Save**.

**Expected:**
- Success toast: **"Program created — code EXT-2026-007"** (on a freshly
  seeded DB; a higher number if programs were already created — codes are
  atomically sequenced per year and can never duplicate, even past manually
  seeded rows).
- The new card appears in the grid with an **Ongoing** badge, Kent Naputo as
  lead, Sagkahan as community, and ₱0 utilized.

## 4. Open the hub

Click the SIKAD-DIGITAL card.

**Expected:** the **program hub** opens with four tabs —
**Overview | Activities | Beneficiaries | Budget** — plus the program header
(code, status badge, dates, budget). This hub is where guides 02–05 happen.

## 5. Things to try (validation & sequence)

1. **Create a second program** (any quick values — e.g. title
   `TEST PROGRAM`). **Expected:** it gets the **next** code
   (`EXT-2026-008`) — the sequence self-heals past seeded rows. Delete it
   afterwards via… (programs use soft deletes; if no delete button is shown,
   just leave it or set status Cancelled via Edit).
2. **Search & filter**: type `SIKAD` in the search box; filter by status
   Ongoing. **Expected:** the list narrows live.
3. **Edit modal** (hub → **Edit Program**): try shrinking the end date below
   an existing activity's date (after guide 03). **Expected:** blocked with
   "The new range would exclude existing activities…" (8.8 backstop).
4. **Faculty view**: log in as **faculty4@lnu.com** (Kent) → **My Programs**.
   **Expected:** SIKAD-DIGITAL appears (he is the lead) and the hub renders
   **read-only** — no Manage/Edit buttons.

## Report back

| Check | Pass? |
|---|---|
| Toast shows auto-generated code EXT-2026-007+ | ☐ |
| Card appears with Ongoing badge + lead + community | ☐ |
| Second program gets the next code (self-heal) | ☐ |
| End-before-start date is blocked | ☐ |
| Faculty sees the program read-only | ☐ |
