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

## Date policy

The panel saw a project created with a planned start a day before today. The rule is now context-aware:

| Form | Rule |
|---|---|
| Project creation | `planned_start_date >= today` (`after_or_equal:today`), with a `min` on the date input and a “Cannot be earlier than today” hint. |
| Project edit | An existing past start can be kept; a start cannot be **moved** into the past. The edit modal says so. |
| Proposal | `proposed_start_date >= today`; a proposal is for a future activity. |
| Activity | Past dates are allowed for recording, but the form shows a confirmation checkbox (“This planned date is in the past…”) and the server requires it (`accepted`). |
| Availability / attendance / evaluation / rendered hours | Unchanged; these are historical records. |

Dates are compared in the application timezone (Asia/Manila). Seeded and existing rows are untouched —
the rules apply to new saves.

## Status/date consistency

A plan that has not started cannot carry an active or terminal status:

| Situation | Selectable statuses |
|---|---|
| Before the planned start | **Draft only** |
| On or after the planned start | Draft, Ongoing, Completed, Cancelled |
| Legacy future-dated record already Ongoing/Completed/Cancelled | It may keep that status (and start) when other fields change; changing the status or the start to another invalid combination is blocked. |
| Complete action on a future activity | Hidden in the table and refused server-side. |

`App\Support\ScheduleStatus` holds the shared rule; the project and activity forms use it for both the
select options and the server check. The status hints say “Only Draft is available before the planned start
date.”

## Tests and fixtures

- `tests/Feature/ValidationAuditTest.php` — pins the required sets, the lenient project-edit path, the
  still-optional activity counts, the past-date rules, the activity past-date confirmation, the
  Draft-only-before-start rule for projects and activities, the legacy status exception, and the refused
  completion of a future activity.
- Date-rule fixtures freeze the clock at 2026-01-01 where the 2026 dates are the subject; the past-activity
  test freezes at 2026-11-19 and uses the day before.
- Updated for the new rules: `ProgramHubTest` (project creation), `DefenceWalkthroughTest` (project
  community + activity venue/budget), `TrainingHoursTest` (activity venue/budget), `BeneficiaryManagementTest`
  and `BeneficiaryImportTest` (register age/municipality).
- The existing `TrainingHoursTest::test_clearing_participants_writes_null_not_zero` still passes and is the
  guard for the "not recorded" semantic.
