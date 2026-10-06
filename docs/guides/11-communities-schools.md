# Guide 11 — Communities & Partner Schools (worked example)

**Role: Admin** · Path: **Communities & Partner Schools**

One registry, two record types: **communities** (beneficiary areas) and
**partner schools** (venues/partners, always active). You will create one
of each.

## 1. Explore the registry

Log in as **admin@lnu.com** → **Communities & Partner Schools**.

**Expected:**
- A **list view** of the 58 seeded records with icon cells and contact
  columns.
- Filter chips at the top: **All / Communities / Partner Schools** — school
  rows carry a gold document icon + level label (elementary / secondary /
  higher ed); community rows show beneficiary/project counts.

## 2. Create a community

Click the add button and fill:

| Field | Value |
|---|---|
| Name | `Brgy. 96 (Calanipawan)` |
| Type | **Community** |
| Municipality | `Tacloban City` |
| Province | `Leyte` |
| Status | **Prospecting** |
| Contact person | `Kagawad Rosa Mabini` |
| Contact number | `09185551234` |
| Email | `brgy96.calanipaway@tacloban.gov.ph` |
| Address | `Brgy. 96 Calanipawan, Tacloban City, Leyte` |
| Description / notes | `Coastal barangay near the city proper; initial scoping for a coastal livelihood project.` |

Save.

**Expected:** toast; the row appears in the list with a **Prospecting**
(gray) status chip. Clicking the row opens the detail modal showing
contact details plus beneficiaries/projects/needs-history sections.

## 3. Create a partner school

Click add again and fill:

| Field | Value |
|---|---|
| Name | `V & G Norte Elementary School` |
| Type | **School** |
| School level | **Elementary** (required for schools) |
| Municipality | `Tacloban City` |
| Province | `Leyte` |
| Contact person | `Dr. Elena Rosales` |
| Contact number | `09175552222` |
| Email | `vgnorte.elem@depedtacloban.ph` |
| Address | `V & G Norte, Tacloban City, Leyte` |

Save.

**Expected:**
- Toast; the row appears with the **gold school icon** + "Elementary"
  label.
- The school's status is **Active** — schools are always active partners
  (no prospecting state), saved automatically as active.
- The detail modal shows **Level / Principal** fields instead of the
  beneficiaries/projects/needs-history sections communities have.

## 4. Edit + filter

1. Open the community you created → **Edit**; change Status
   **Prospecting → Active**; save.

**Expected:** the status chip updates; the change is reflected immediately
in the list.

2. Click the **Partner Schools** filter chip.

**Expected:** only schools are listed (12 seeded + yours); switch back with
**All**.

3. Search for `Sagkahan`.

**Expected:** the list narrows to the Sagkahan community (and any matching
records).

## 5. Link a community to a project (where this matters)

1. Open the project hub: **Manage Extension Programs** → a college card → open one of its projects → **Edit**.
2. In **Linked communities**, search your new barangay
   (`Calanipawan`) → select it → save.

**Expected:** the community now appears on the project's header/overview,
and the community detail modal's project list includes this project. (The
multi-select also shows partner schools with a `· School` marker — schools
can be linked as partners/venues too.)

## Report back

| Check | Pass? |
|---|---|
| 58 records load; All/Communities/Schools filters work | ☐ |
| Community created with Prospecting chip | ☐ |
| School created with gold icon + level label, forced Active | ☐ |
| School detail shows Level/Principal (not beneficiary sections) | ☐ |
| Edit modal updates the row | ☐ |
| Community linkable to a project via Edit | ☐ |
