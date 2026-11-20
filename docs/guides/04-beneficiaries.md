# Guide 04 — Beneficiaries: Add, Edit, Archive & Import (v2)

**Role: Admin or Secretary** · Path: **Manage Beneficiaries** (or a project hub → **Beneficiaries** tab)
· Prerequisite: guide 01 (SIKAD-DIGITAL exists)

You will add five beneficiaries to one project, trigger the de-duplication
warning, correct a profile, archive/restore one person, and bulk import a
spreadsheet. Every beneficiary belongs to **one project** and **one community /
partner school**.

## 1. Add these 5 beneficiaries

On the **Beneficiaries** tab, click **Add beneficiary** and fill each row. The
project is fixed to the project you opened. Choose the project's community or
partner school (for the seeded SIKAD-DIGITAL project, `Brgy. Sagkahan`). The
contact number may stay blank — it will show as **“No contact provided.”**

| First name | Last name | Age | Sex | Community / Partner School | Category |
|---|---|---|---|---|---|
| Maria | Santos | 42 | Female | `Brgy. Sagkahan` | Parent |
| Josefina | Bautista | 38 | Female | `Brgy. Sagkahan` | Parent |
| Rogelio | Lim | 45 | Male | `Brgy. Sagkahan` | Parent |
| Analyn | Custodio | 29 | Female | `Brgy. Sagkahan` | Parent |
| Eduardo | Navarro | 51 | Male | `Brgy. Sagkahan` | Parent |

**Expected per save:** toast "Beneficiary added"; the row appears in the table and
the active count climbs to **5 active**.

Required fields: First name, Last name, Age, Community / Partner School,
Beneficiary Category. Names accept letters, spaces, periods, hyphens and
apostrophes only; digits are rejected. Contact numbers accept digits, spaces,
`+`, hyphens and parentheses, with at least seven digits.

## 2. Trigger the de-duplication warning (5.4)

1. Click **Add beneficiary** again; type first name `Maria`, last name `Santos`,
   the same community, any category; save.

**Expected:** a **warning appears** — "This person is already in this project:
Maria Santos." — and you must **explicitly confirm** to proceed (never a silent
merge). Confirm or cancel, your choice: confirming creates a second Maria Santos
(allowed, but recorded); canceling aborts.

## 3. Edit a profile (Secretary only)

1. As the **Secretary**, click **Edit** on a row and change the phone number.

**Expected:** the change saves and is audited. The **Project** selector is
disabled and labelled **“Fixed once assigned.”** The community can still be
changed within the same project; a community outside that project is rejected.

> **Admin:** you can add, archive, import and export. Editing a profile stays
> with the Secretary (D8).

## 4. Archive and restore

1. Click **Archive** on a row. Confirm the prompt.

**Expected:** the row leaves the active roster, but its attendance history stays.
Open the **Beneficiaries** registry page, switch the status filter to
**Archived**, and click **Restore** to bring the person back.

## 5. Bulk import via XLSX (v2)

### 5.1 Download the official template

On **Manage Beneficiaries**, click **Download template** in the page header. (The same link is inside
the **Import beneficiaries** modal, where you also choose the **target project**; the project hub
already knows its project.) Open the `.xlsx` — it has exactly these fixed headers (do not rename them):

`First Name | Middle Name | Last Name | Age | Sex | Contact Number | Community / Partner School | Beneficiary Category`

The community must already exist and be linked to the target project. The old
v1 template (Barangay + Municipality columns) is rejected.

### 5.2 Enter these 5 sample rows

| First | Middle | Last | Age | Sex | Contact | Community / Partner School | Category |
|---|---|---|---|---|---|---|---|
| Lourdes | D. | Amper | 39 | Female | *(blank)* | Brgy. Sagkahan | Housewife |
| Nelson | | Padilla | 44 | Male | 09171234567 | Brgy. Sagkahan | Vendor |
| Precious | R. | Villar | 22 | Female | *(blank)* | Brgy. Sagkahan | Solo Parent |
| Maria | | Santos | 42 | Female | *(blank)* | Brgy. Sagkahan | Parent |
| Greg | | Mendoza | | Male | | Brgy. Sagkahan | *(blank)* |

What each row demonstrates:
- **Lourdes** — blank contact stays blank on create and displays as “No contact provided.”
- **Nelson** — a fully valid row with a real contact number.
- **Precious** — "Solo Parent" is **not a known category** → auto-maps to
  **Other** (shown in the preview with the raw label kept).
- **Maria Santos** — already in this project → **Update** status, not a duplicate row.
- **Greg** — missing Beneficiary Category (required) → **row error**, skipped;
  the file is NOT rejected.

Save the file (max 500 data rows per file).

### 5.3 Upload and preview

Back in the Import modal: choose the file → the preview appears after parsing.
It names the target project and shows counts, then each row shows **New / Update /
Unassigned / Other project / Skip** status, per-field errors, and "kept as Other"
notes.

### 5.4 Confirm

Click **Confirm import** (records are only written on explicit confirm).

**Expected:** toast reports created / updated / claimed / skipped counts. Lourdes,
Nelson and Precious (as Other) are created; Maria Santos is updated; Greg is
skipped for the missing category. A legacy person with **no project** is claimed
into this project; a person already owned by **another project** is skipped and
the other project's code is shown.

## Report back

| Check | Pass? |
|---|---|
| 5 additions create project-owned rows | ☐ |
| Dedup warning requires explicit confirm | ☐ |
| Edit works for the Secretary and the project selector is disabled | ☐ |
| Archive removes the row; Restore brings it back | ☐ |
| Template download has the 8 v2 headers | ☐ |
| Preview shows New/Update/Unassigned/Other project/Skip statuses | ☐ |
| Unknown category auto-maps to Other | ☐ |
| Error rows skipped, file not rejected | ☐ |
| Blank contact shows “No contact provided” | ☐ |
