# Guide 04 — Beneficiaries: Register, Enroll, Dedup & XLSX Import (with real examples)

**Role: Admin** · Path: project hub → **Beneficiaries** tab
· Prerequisite: guide 01 (SIKAD-DIGITAL exists)

You will register 5 beneficiaries (auto-enrolled), trigger the
de-duplication warning, enroll someone from the global registry, and bulk
import a spreadsheet.

## 1. Register these 5 beneficiaries

On the **Beneficiaries** tab, click **Register new** and fill each row (the
municipality pre-fills from the project's community — Tacloban City). The
contact number may stay at its default `09123456789`.

| First name | Last name | Age | Sex | Barangay | Category |
|---|---|---|---|---|---|
| Maria | Santos | 42 | Female | `Sagkahan` | Parent |
| Josefina | Bautista | 38 | Female | `Sagkahan` | Parent |
| Rogelio | Lim | 45 | Male | `Sagkahan` | Parent |
| Analyn | Custodio | 29 | Female | `Sagkahan` | Parent |
| Eduardo | Navarro | 51 | Male | `Sagkahan` | Parent |

**Expected per save:** toast "Beneficiary registered & enrolled"; the row
appears in the table and the enrolled count climbs to **5 enrolled**.

Required fields: First name, Last name, Barangay, Beneficiary Category.
Valid categories: Farmer, Fisherfolk, Fisherman, Housewife, Parent, Senior
Citizen, Out-of-School Youth, Student, Vendor, Tricycle Driver, Construction
Worker, Barangay Worker, Unemployed, Other. Sex: Female / Male / Prefer not
to say.

## 2. Trigger the de-duplication warning (5.4)

1. Click **Register new** again; type first name `Maria`, last name
   `Santos`, barangay `Sagkahan`, any category; save.

**Expected:** a **warning appears** — "A beneficiary with the same first
name, last name, and barangay already exists: Maria Santos." — and you must
**explicitly confirm** to proceed (never a silent merge). Confirm or cancel,
your choice: confirming creates a second Maria Santos (allowed, but recorded);
canceling aborts.

## 3. Enroll an existing beneficiary from the registry

1. Click **Enroll existing**; search `Tan` (Roberto G. Tan — a seeded
   beneficiary from Brgy. San Jose, enrolled in other projects).
2. Click **Enroll** on his row.

**Expected:** toast "Beneficiary enrolled"; Roberto appears in the project's
beneficiary table. The registry is global — one person can be enrolled in
multiple projects.

2. Try enrolling him again.

**Expected:** nothing happens / he is not duplicated (the pivot is
set-unique).

## 4. Unenroll someone

Click **Unenroll** on any row you don't want (e.g. Roberto, or the duplicate
Maria).

**Expected:** toast "Beneficiary unenrolled"; the row leaves the table. (He
stays in the global registry — only the project link is removed.)

## 5. Bulk import via XLSX (v4.7 pattern)

### 5.1 Download the official template

Click **Import XLSX** → **Download template**. Open the `.xlsx` — it has
exactly these fixed headers (do not rename them):

`First Name | Middle Name | Last Name | Age | Sex | Contact Number | Barangay | Municipality / City | Beneficiary Category`

### 5.2 Enter these 5 sample rows

| First | Middle | Last | Age | Sex | Contact | Barangay | Municipality / City | Category |
|---|---|---|---|---|---|---|---|---|
| Lourdes | D. | Amper | 39 | Female | *(blank)* | Sagkahan | Tacloban City | Housewife |
| Nelson | | Padilla | 44 | Male | 09171234567 | Sagkahan | Tacloban City | Vendor |
| Precious | R. | Villar | 22 | Female | *(blank)* | Sagkahan | Tacloban City | Solo Parent |
| Maria | | Santos | 42 | Female | *(blank)* | Sagkahan | Tacloban City | Parent |
| Greg | | Mendoza | | Male | | Sagkahan | Tacloban City | *(blank)* |

What each row demonstrates:
- **Lourdes** — blank contact → defaults to `09123456789` on create.
- **Nelson** — a fully valid row with a real contact number.
- **Precious** — "Solo Parent" is **not a known category** → auto-maps to
  **Other** (shown in the preview with the raw label kept).
- **Maria Santos** — duplicate (same first + last + barangay as the registry)
  → skipped with a **Duplicate** status.
- **Greg** — missing Beneficiary Category (required) → **row error**,
  skipped; the file is NOT rejected.

Save the file (max 500 data rows per file).

### 5.3 Upload and preview

Back in the Import modal: choose the file → the preview appears after
parsing. Each row shows **Import / Duplicate / Skip** status, per-field
errors, and "kept as Other" notes.

### 5.4 Confirm

Click **Confirm & create** (records are only created on explicit confirm).

**Expected:** toast: **"3 imported & enrolled · 1 duplicate skipped · 1 row
with errors skipped"** — Lourdes, Nelson, and Precious (as Other) import and
auto-enroll; Maria Santos is skipped as a duplicate; Greg is skipped for the
missing category. The three new beneficiaries appear in the table.

## Report back

| Check | Pass? |
|---|---|
| 5 registrations auto-enroll ("5 enrolled") | ☐ |
| Dedup warning requires explicit confirm | ☐ |
| Enroll-existing search + enroll works | ☐ |
| Double-enroll does not duplicate | ☐ |
| Template download has the 9 fixed headers | ☐ |
| Preview shows per-row Import/Duplicate/Skip statuses | ☐ |
| Unknown category auto-maps to Other | ☐ |
| Error rows skipped, file not rejected | ☐ |
