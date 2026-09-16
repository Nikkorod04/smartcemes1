# Guide 07 — Importing a Needs Assessment from the XLSX Template (worked example)

**Role: Faculty or Secretary** · Path: **Encode Assessment → Import from
template** · Prerequisite: a spreadsheet app (Excel / Google Sheets→xlsx)

You will download the official **vertical** template, fill one respondent's
answers, and import it with the preview-and-confirm flow — including an
unknown value that auto-maps to "Other" and an invalid age that errors
per-field.

## 1. Download the template

1. Log in as faculty or secretary → sidebar **Encode Assessment**.
2. Click **Import from template** (top of the wizard).
3. Click **Download template** and open the `.xlsx`.

**Template structure (v3, vertical):**
- **Column A** — fixed field labels, grouped under blue Section I–IX
  separator rows plus an **Import Context** block at the top.
- **Column B** — the shaded **answer cell** (this is where you type).
- **Column C** — fill-up guides with the full option lists (self-contained
  for field work).
- Single-choice / Yes-No answer cells carry **non-strict dropdown
  suggestions** — you can also type free text (it will auto-map later).

## 2. Fill the Import Context block (column B, top rows)

| Label (column A) | Answer (column B) |
|---|---|
| Community | `Brgy. Sagkahan` |
| Quarter | `3` |
| Year | `2026` |
| Proposal Reference (optional) | *(blank)* |

## 3. Fill these sample answers (column B)

| Label (column A) | Answer (column B) |
|---|---|
| First Name | `Rodrigo` |
| Middle Name | `Estrada` |
| Last Name | `Mercado` |
| Age | `52` |
| Civil Status | `Married` |
| Sex | `Male` |
| Religion | `Iglesia ni Cristo` |
| Highest Educational Attainment | `Junior High School Graduate` |
| Family Composition | `4 members` |
| Household Member Currently Studying | `No` |
| Household Members in Organization | `None` |
| Main Source of Household Livelihood | `Online selling` ← **not in the list (demo of auto-map)** |
| Desired Livelihood Training | `Soap making` |
| Barangay Educational Facilities | `Elementary school, High school` (comma-separated for multi) |
| Interested in Continuing Studies | `No` |
| Preferred Training Time | `Afternoon 1:00-5:00` |
| Preferred Training Days | `Saturday` |
| Most Common Illness | `Cough / colds` |
| Action When Sick | `Self-medication` |
| Barangay Medical Supplies Available | `First aid kit, Paracetamol` |
| Has Barangay Health Programs | `No` |
| Water Source | `Deep well` |
| Water Source Distance | `250 meters away` |
| Garbage Disposal Method | `Collected by barangay` |
| Has Own Toilet | `Yes` |
| Toilet Type | `Flush toilet` |
| Keeps Animals | `Yes` |
| Animals Kept | `Chicken` |
| House Type | `Wooden house` |
| Tenure Status | `Owner` |
| Has Electricity | `Yes` |
| Appliances Owned | `Radio, Cellphone` |
| Barangay Recreational Facilities | `Basketball court` |
| Use of Free Time | `Resting, Watching TV` |
| Member of Organization | `No` |
| Family Problems | `Low income, Medical expenses` |
| Health Problems | `High blood pressure` |
| Economic Problems | `Low income` |
| Barangay Service Rating | `Good` |
| Available for Training | `Yes` |

Deliberate demo values:
- **Main Source of Household Livelihood = `Online selling`** — not in the
  standard list → the import will **auto-map it to "Other"** keeping the raw
  text (D9).
- Multi-select answers are **comma-separated**.
- Section VIII problem fields accept **up to 3** values each.

Save the file. (One file = one respondent.)

## 4. Upload and parse

Back on the import page:

1. Pick the **Record context** in the form: Community **Brgy. Sagkahan ·
   Tacloban City**, Quarter **Q3**, Year **2026** (the file's own context
   block fills these automatically after parsing; the form is the
   backstop).
2. Drop/choose the file in the dashed **dropzone**.
3. Click **Parse file** (shows a spinner while reading).

**Expected:** the **preview step** appears with:
- Target-context badges (community · Q3 2026).
- A **gold auto-map panel** — "Online selling" mapped to **Other** with the
  raw text kept.
- A 2-column parsed-values grid of everything read from column B.
- Any per-field errors listed on screen (nothing is persisted yet).

## 5. Error demo (optional)

Make a copy of the file, change **Age** to `12`, re-parse.

**Expected:** a per-field error — age must be a whole number **15–120** —
shown on screen; the field is dropped from the created record. The file is
**not** rejected as a whole (D10: per-field errors, never whole-file).

## 6. Confirm & create

Click **Confirm & create record**.

**Expected:** the record is created with review status **pending** — it now
appears in the Secretary's review queue (validated in `secretaryguide.md`
§2), and the source file is archived for audit.

## Notes

- **Only the vertical template works.** Old horizontal (v1) files are
  rejected with a "download the current template" error — by design.
- Do not rename labels in column A — the parser matches them exactly
  (unknown-label rows and section separators are ignored).
- The XLSX import does **not** enforce the wizard's conditional show/hide
  rules — parsed values are saved as-is (D10; the wizard enforces
  conditionals live instead).

## Report back

| Check | Pass? |
|---|---|
| Template downloads with labels/guides/dropdowns | ☐ |
| Parse shows preview with context badges | ☐ |
| "Online selling" auto-maps to Other (gold panel) | ☐ |
| Age 12 shows per-field error, file not rejected | ☐ |
| Confirm creates a pending record in the secretary queue | ☐ |
