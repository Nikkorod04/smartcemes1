# Validation Audit — SmartCEMES (after-defense revision)

Date: 2026-11-19

This is the working record for Item 5 / D9. It lists what each form requires, the rule behind it, and the
decision taken. The rule changes are covered by `tests/Feature/ValidationAuditTest.php` and by the updated
fixtures listed at the end.

## Decisions

| Area | Decision |
|---|---|
| Project creation (D9) | Require at least one community, target beneficiaries, allocated budget, project lead, and a description of at least 20 characters. |
| Project edit | **Creation only.** Existing projects are not forced to fill the new fields when edited. |
| Activity | Require venue and allocated budget. Participants and trainers stay optional: they are fallback/auto figures, and null still means "not recorded". |
| Proposal | Require description (≥20 characters) and budget estimate. |
| Beneficiary register / edit | Require age and municipality. Phone stays optional. |
| Assessment (staff wizard and public form) | Demographics stay optional; only names (and consent on the public form) are required. |
| Targets | Both figures stay optional by design — "no target" must remain representable. |
| Program creation | Unchanged: title, pillar, CESO thrust, and status remain the required set. |

## Form audit

### Project creation — `Colleges\Index::saveProject`

| Field | DB | Rule now | UI |
|---|---|---|---|
| College | required | `required|exists:colleges,id` | `*` (existing) |
| Program | required | `required|exists:programs,id` | `*` (existing) |
| Title | required | `required|string|max:255` | `*` (existing) |
| Description | nullable | `required|string|min:20|max:4000` | `*`, minlength 20, counter hint |
| Planned start / end | required | `required|date` + `after_or_equal` | `*` (existing) |
| Target beneficiaries | nullable | `required|integer|min:1` | `*`, required |
| Allocated budget | NOT NULL, default 0 | `required|numeric|min:0` | `*`, required (0 allowed for in-kind) |
| Annual target hours | nullable | `nullable|numeric|min:0` | optional (existing) |
| Project lead | nullable | `required|exists:faculties,id` | `*`, required |
| Linked communities | pivot | `required|array|min:1` | `*`, hint "at least one" |
| Beneficiary categories | JSON | `array` | optional (existing) |
| Status | required | `required|in:draft,ongoing,completed,cancelled` | `*` added |

### Project edit — `Programs\Hub::saveProgramEdit`

Unchanged (creation-only decision): description, target beneficiaries, budget, lead, and communities stay
`nullable`/`array`. This keeps older projects editable without a forced backfill.

### Activity — `Programs\Hub::saveActivity`

| Field | DB | Rule now | UI |
|---|---|---|---|
| Title | required | `required|string|max:255` | `*` (existing) |
| Venue | nullable | `required|string|max:255` | `*`, required |
| Dates / times / days | required | existing range + half-day rules | `*` (existing) |
| Participants | nullable | `nullable|integer|min:0|max:10000` | optional; null = not recorded |
| Trainers snapshot | nullable | `nullable|integer|min:0|max:200` | optional; can be auto-filled |
| Allocated budget | nullable | `required|numeric|min:0` | `*`, required (0 allowed) |
| Status | required | `required|in:draft,ongoing,completed,cancelled` | `*` (existing) |

### Proposal — `Proposals\Create::save`

| Field | DB | Rule now | UI |
|---|---|---|---|
| Title | required | `required|string|max:255` | `*` (existing) |
| Description | nullable | `required|string|min:20|max:4000` | `*`, minlength 20, hint |
| Project / community / dates | required | existing | `*` (existing) |
| Budget estimate | nullable | `required|numeric|min:0` | `*`, required |
| Attachments | — | `array|max:5` + file rules | optional (existing) |

### Beneficiary register / edit — `Programs\Hub`

| Field | DB | Rule now | UI |
|---|---|---|---|
| First / last name | required | `required|string|max:255` | `*` (existing) |
| Age | nullable | `required|integer|min:0|max:120` | `*`, required |
| Barangay | required | `required|string|max:255` | `*` (existing) |
| Municipality | nullable | `required|string|max:255` | `*`, required |
| Category | required | `required|in:beneficiary_categories` | `*` (existing) |
| Phone | nullable | `nullable|string|max:20` | optional (existing) |

The Secretary's edit modal mirrors these rules. The XLSX beneficiary import is **not** changed: imported
rows keep their own template validation, so a legacy sheet without age/municipality still imports; the
register/edit form is the controlled path.

### Assessment — `AssessmentRules`

Only `respondent_first_name` and `respondent_last_name` are required; age is `nullable|integer|between:15,120`;
sex, civil status, and the rest are optional. Consent is required by the public endpoint. This was kept
deliberately so the public form remains completable on a shared or borrowed phone.

### Targets

Both figures stay optional, intentionally: the UI and reports distinguish "no target" from a target of 0.

## Tests and fixtures

- `tests/Feature/ValidationAuditTest.php` — pins the required sets, the lenient project-edit path, and the
  still-optional activity counts.
- Updated for the new rules: `ProgramHubTest` (project creation), `DefenceWalkthroughTest` (project
  community + activity venue/budget), `TrainingHoursTest` (activity venue/budget), `BeneficiaryManagementTest`
  and `BeneficiaryImportTest` (register age/municipality).
- The existing `TrainingHoursTest::test_clearing_participants_writes_null_not_zero` still passes and is the
  guard for the "not recorded" semantic.
