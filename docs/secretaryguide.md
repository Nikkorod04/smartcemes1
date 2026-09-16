# SmartCEMES — SECRETARY Testing Guide

_You are testing as the **CESO Secretary** — you review and validate
needs-assessment submissions and import records from the official XLSX
template. You have **zero AI access** (by design). Work top to bottom._

## Getting in

1. Open **https://disorder-childless-moonlike.ngrok-free.dev**
2. If ngrok shows a warning page, click **Visit Site** (normal for free ngrok).
3. Log in:
   - **Email:** secretary@lnu.com
   - **Password:** password

**Your sidebar (intentionally small):** Dashboard · Calendar · Assessment
Review — **no AI items anywhere**. The Secretary never sees AI features; that
is a locked design decision.

---

## 1. Dashboard — validation workspace

1. After login you land on the Dashboard.

**Expected:** stat cards for **Pending Validations**, **Validated
Submissions**, **Returned for Re-encoding**, and **XLSX Imports Archived**,
plus a "Pending Validations" table with an **Open review queue →** link.

2. Click the **bell icon** (top header).

**Expected:** a notification dropdown (may be empty for you — most
notifications go to faculty and the Director).

---

## 2. Assessment Review — the validation queue

1. Click **Assessment Review** (sidebar or the dashboard link).

**Expected:** the queue is ordered **pending → returned → validated**. Seeded
records exist in every state (a faculty friend may also have just encoded a
new pending one for you).

2. Open a **pending** record.

**Expected:** the full Sections I–IX responses for that respondent are shown.

3. Click **Validate**.

**Expected:** a success toast, the record moves to Validated, and the
community's `(community, quarter, year)` **summary recomputes automatically**.
This aggregate summary is exactly what the AI later receives — never
respondent identities.

4. Open another **pending** record and click **Return**.

**Expected:** remarks are **required (minimum 5 characters)** — try confirming
empty first and watch it block. When you submit remarks, the record moves to
Returned and the **faculty uploader gets a notification** with your remarks.

5. Look at a **returned** record and a **validated** one.

**Expected:** reviewer stamps (who reviewed, when) and any remarks are
visible.

---

## 3. Import a needs assessment from the XLSX template

You can both encode manually AND import. This tests the import flow.

1. In the address bar, go to:
   `https://disorder-childless-moonlike.ngrok-free.dev/assessments/create`
   (the encoding wizard page — the Secretary can use it even though it is not
   in your sidebar).
2. Click **Import from template**.
3. Click **Download template** and save the `.xlsx` file.

**Expected:** the official **vertical** template opens in Excel: field labels
in column A (grouped by Sections I–IX), shaded answer cells with dropdown
suggestions in column B, and full option guides in column C.

4. Fill in a few answers in column B (names, age, some dropdown picks). Typing
   a value that is NOT in the list is fine — it should auto-map later. Save
   the file.
5. Back on the import page: pick **Community, Quarter, Year** in the Record
   context block, choose the file in the dropzone, and click **Parse file**.

**Expected:** the preview step appears with target-context badges (community ·
Q# year), a **gold auto-map panel** (unknown/misspelled values mapped to
"Other" with raw text kept), a parsed-values grid, and any per-field errors.
It is **never** a whole-file reject.

6. Click **Confirm & create record**.

**Expected:** the record is created with review status **pending** — it now
appears in YOUR review queue (section 2 above), ready to validate.

---

## 4. Calendar

1. Go to **Calendar**.

**Expected:** the shared month grid (activities, accepted availability
requests, and deadlines). You see the office-wide view. Click a day for its
side panel, and check the 60-day upcoming list below.

---

## 5. Security check — you must be locked out of these

While logged in as the Secretary, visit these URLs directly:

1. `https://disorder-childless-moonlike.ngrok-free.dev/analytics`
2. `https://disorder-childless-moonlike.ngrok-free.dev/ai-analysis`
3. `https://disorder-childless-moonlike.ngrok-free.dev/programs`

**Expected:** **403 Forbidden** on all three. Role separation is enforced on
every route — the Secretary cannot reach Admin-only pages even by typing the
URL.

---

## Report-back checklist

| # | Feature | Pass? |
|---|---|---|
| 1 | Dashboard stat cards + queue link | ☐ |
| 2 | Review queue order (pending → returned → validated) | ☐ |
| 3 | Validate → record moves + summary recomputes | ☐ |
| 4 | Return → remarks required (min 5 chars) | ☐ |
| 5 | Returned record notifies the faculty uploader | ☐ |
| 6 | Template download (vertical, Sections I–IX) | ☐ |
| 7 | Import: parse → preview → confirm → pending | ☐ |
| 8 | Auto-map to "Other" on unknown values | ☐ |
| 9 | Calendar renders | ☐ |
| 10 | 403 on /analytics, /ai-analysis, /programs | ☐ |

## Testing together

- **Faculty friends encode/import assessments → they land in your queue.**
  Validate or return them and tell the faculty to check their bell — returns
  arrive with your remarks.
- Your **validations recompute community summaries**, which the Director
  (admin friend) then uses for AI Analysis Review — validate a few before they
  demo the AI.
- If the queue is empty, ask the owner to re-run the seeder
  (`php artisan migrate:fresh --seed`) or have a faculty friend encode a new
  record.
