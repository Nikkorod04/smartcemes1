# SmartCEMES — SECRETARY Testing Guide

_You are testing as the **CESO Secretary** — you review and validate needs-assessment
submissions, import records from the official XLSX template, and manage a project's
beneficiaries and activity records. You have **zero AI access** (by design). Work top to bottom._

> **The hierarchy is `College → Program → Project → Activity`.** A **Program** is one of the six broad
> CESO thrusts; a **Project** is the thing with dates, a budget, activities and targets. Where an older
> note says "program" and means the narrow entity, it means a *project*. **You work at the project
> level.**
>
> **Your scope is deliberately narrow.** You may manage beneficiaries and activity records, and you
> validate assessments. **Project structure, targets and budget stay with the Director's office** —
> trying to reach those pages gives you a 403, which is correct (§6).
>
> **There is no compliance page.** The prototype's "Compliance Tracker" was a *phantom never-built*
> entry, deliberately removed in v4.12. Compliance monitoring lives on your dashboard.

## Getting in

1. Open **https://disorder-childless-moonlike.ngrok-free.dev**
2. If ngrok shows a warning page, click **Visit Site** (normal for free ngrok).
3. Log in:
   - **Email:** secretary@lnu.com
   - **Password:** password

**Your sidebar (intentionally small):**

| Section | Items |
|---|---|
| Overview | Dashboard · Calendar |
| Validation | Assessment Review |
| Management | Manage Beneficiaries |

**No AI items anywhere.** The Secretary never sees AI features — a locked design decision.

---

## 1. Dashboard — validation workspace

1. After login you land on the Dashboard.

**Expected — four stat cards:** **Pending Validations** · **Validated Submissions** ·
**Returned for Re-encoding** · **XLSX Imports Archived**.

2. Below them: a **Pending Validations** table (Community · Period · Respondent · Submitted By ·
   Submitted) with an **Open review queue →** link, and a **Recent Summaries** panel showing each
   community summary's response count and when it was last recalculated.

3. Click the **bell icon** (top header).

**Expected:** a notification dropdown (may be empty for you — most notifications go to faculty and
the Director).

> **There is no compliance panel and no action center.** The dashboard is the validation workspace.

---

## 2. Assessment Review — the validation queue

1. Click **Assessment Review** (sidebar or the dashboard link).

**Expected:** the queue lists records with the **default sort: pending first**, then returned, then
validated. Seeded records exist in every state (a faculty colleague may also have just encoded a new
pending one for you). A search box covers community / encoder / respondent, and there is a **quarter
filter** plus a **Period** column header you can click to cycle the sort (default → year+quarter asc →
desc).

2. Open a **pending** record.

**Expected:** a right-hand **dossier drawer** opens with the full **Sections I–IX** responses for that
respondent, a record-completeness progress bar, and Yes/No badges and chips for the multi-select
answers. Clicking **Review** on a row works the same way.

3. Click **Validate**.

**Expected:** a **confirmation modal** appears first (it shows the community/quarter and what
confirming will do: recompute the summary, notify the encoder, stamp the audit trail). On confirm: a
success toast, the record moves to **Validated**, and the community's `(community, quarter, year)`
**summary recomputes automatically**. This aggregate summary is exactly what the AI later receives —
never respondent identities.

4. Open another **pending** record and click **Return**.

**Expected:** remarks are **required (minimum 5 characters)** — try confirming empty first and watch
it block. When you submit, the record moves to **Returned** and the **faculty uploader gets a
notification** with your remarks.

5. Look at a **returned** record and a **validated** one.

**Expected:** reviewer stamps (who reviewed, when) and any remarks are visible. Re-reviewing an
already-reviewed record is refused ("Already reviewed").

---

## 3. Import a needs assessment from the XLSX template

You can both encode manually AND import. This tests the import flow.

1. In the address bar, go to:
   `https://disorder-childless-moonlike.ngrok-free.dev/assessments/create`
   (the encoding wizard page — the Secretary can use it even though it is not in your sidebar).
2. Click **Import from template**.
3. Click **Download template** and save the `.xlsx` file.

**Expected:** the official **vertical** template opens in Excel: field labels in column A (grouped by
Sections I–IX), shaded answer cells with dropdown suggestions in column B, and full option guides in
column C. The dropdowns are **non-strict** — typed free text is accepted and auto-mapped later.

4. Fill in a few answers in column B (names, age, some dropdown picks). Typing a value that is NOT in
   the list is fine — it should auto-map later. Save the file.
5. Back on the import page: pick **Community, Quarter, Year** in the Record context block, choose the
   file in the dropzone, and click **Parse file**.

**Expected:** the preview step appears with target-context badges (community · Q# year), a **gold
auto-map panel** (unknown/misspelled values mapped to "Other" with the raw text kept), a
parsed-values grid, and any per-field errors. It is **never** a whole-file reject — bad values are
reported per field, on screen only.

6. Click **Confirm & create record**.

**Expected:** the record is created with review status **pending** — it now appears in YOUR review
queue (§2), ready to validate. The source file is archived for audit.

> **Older horizontal (v1) template files are no longer accepted** — you will get a clear
> "download the current template" error. Always start from the Download button.

---

## 4. Manage Beneficiaries — your project-scoped workspace

1. Sidebar → **Manage Beneficiaries**.

**Expected:** three cards (**Projects** · **Beneficiary enrollments** · a "What you can do" explainer)
and a table of **projects** with a search box. Each row shows status, linked communities, the enrolled
count and the scheduled-activity count, with two actions: **Beneficiaries** and **Attendance**.

2. Click **Beneficiaries** on a project.

**Expected:** the project hub opens on its **Beneficiaries** tab. Here you can:
- **Enroll existing** — search the global registry (including beneficiaries not yet linked to any
  project) and attach them to this project.
- **Register new** — create a profile and auto-enroll it. A first + last + barangay match warns you
  and requires explicit confirmation; it is **never a silent merge**.
- **Import XLSX** — download the official beneficiary template (one row = one beneficiary, 500 max),
  upload it, review the per-row **Import / Duplicate / Skip** preview, then confirm. Rows with errors
  or duplicates are **skipped per row** — never a whole-file reject. Unknown categories auto-map to
  "Other". A blank contact number defaults to `09123456789`.
- **Unenroll** — detach from this project (the beneficiary stays in the global registry).

3. Go back and click **Attendance** on a project instead.

**Expected:** the hub opens on its **Activities** tab. Open an activity's **Records** modal to serve
the generated **attendance** template (the enrolled roster is pre-filled) or the **evaluation**
template (pre/post 0–100, satisfaction 1–5). Both are **import-only**: download → fill → upload →
parse → review the per-row states → confirm.

**Expected on confirm:** attendance rows are upserted per beneficiary; evaluation rows are
**averaged per metric** into the activity's aggregate scores (D13). A metric with no values in the
file leaves the existing aggregate untouched. Late still counts as served.

> **Attendance recording is import-only** — the old manual attendance panel was removed (v4.13).
> One attendance date per activity (its planned start date); multi-day attendance is future work.

> **What you cannot do here:** edit the project, change its targets or budget, or create activities.
> Those belong to the Director's office. The page says so, and the server enforces it.

---

## 5. Calendar

1. Go to **Calendar**.

**Expected:** the shared month grid — activities, accepted availability requests, and **project**
deadlines. You see the office-wide view. Click a day for its side panel, and check the 60-day
upcoming list below. Overlapping events carry red **conflict** markers.

---

## 6. Security check — you must be locked out of these

While logged in as the Secretary, visit these URLs directly:

| URL | Why it should be closed |
|---|---|
| `/ai-analysis` | **AI is Admin-only** (D4) |
| `/programs` | Broad-program CRUD is Director-only |
| `/colleges` | The hierarchy hub is Director-only |
| `/targets` | Targets are Director-only (D-R5) |
| `/reports` | Institutional reports are Director-only |
| `/audit-logs` | Admin-only audit trail |

**Expected:** **403 Forbidden** on all of them. Role separation is enforced on every route — the
Secretary cannot reach Admin-only pages even by typing the URL.

> **Analytics is gone** (v4.20, `revisions.md` §21) — it is not listed above because `/analytics` no
> longer resolves. Every row that remains returns a real **403**.

> **One deliberate exception:** `/projects/{id}` returns **200** — you *can* open a project hub,
> because that is how you reach its Beneficiaries and Activities tabs. It renders **read-only** for
> you; the edit controls are not there.

---

## Report-back checklist

| # | Feature | Pass? |
|---|---|---|
| 1 | Dashboard 4 stat cards + pending table + Recent Summaries | ☐ |
| 2 | Review queue defaults to pending-first | ☐ |
| 3 | Dossier drawer shows Sections I–IX + completeness bar | ☐ |
| 4 | Validate opens a confirm modal → record moves + summary recomputes | ☐ |
| 5 | Return → remarks required (min 5 chars) | ☐ |
| 6 | Returned record notifies the faculty uploader | ☐ |
| 7 | Template download (vertical, Sections I–IX, non-strict dropdowns) | ☐ |
| 8 | Import: parse → preview → confirm → pending | ☐ |
| 9 | Auto-map to "Other" on unknown values | ☐ |
| 10 | Manage Beneficiaries lists **projects** with both deep links | ☐ |
| 11 | Enroll / register (dedup warns) / XLSX import / unenroll | ☐ |
| 12 | Activity Records: attendance + evaluation import | ☐ |
| 13 | Calendar renders (3 feeds, conflict markers) | ☐ |
| 14 | 403 on all seven Admin-only URLs; 200 on a project hub | ☐ |

## Testing together

- **Faculty colleagues encode/import assessments → they land in your queue.** Validate or return
  them and tell the faculty to check their bell — returns arrive with your remarks.
- Your **validations recompute community summaries**, which the Director then uses for AI Analysis
  Review — validate a few before they demo the AI.
- Your **attendance imports** are what make the project's **Trainees** tile non-zero, and they feed
  the training-hours figures the Director sees. Imported attendance *replaces* the manual participant
  fallback, so the computed hours can shift.
- If the queue is empty, re-run the seeder (`php artisan migrate:fresh --seed`) or have a faculty
  colleague encode a new record.
