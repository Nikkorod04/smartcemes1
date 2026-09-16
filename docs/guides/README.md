# SmartCEMES — Feature Guides (with real examples)

_Step-by-step walkthroughs for every major feature, each with **exact sample
data to type** and the expected result. Written for testing over the ngrok
demo link. Companion docs: `docs/adminguide.md` / `secretaryguide.md` /
`facultyguide.md` (role overviews), `docs/TEST-SCRIPT.md` (owner deep-dive)._

**Demo link:** https://disorder-childless-moonlike.ngrok-free.dev — click
**Visit Site** on the ngrok warning page. All accounts use password
`password` (admin@lnu.com · secretary@lnu.com · faculty1–4@lnu.com).

## Guide index

| # | Guide | What you will build | Role | Needs |
|---|---|---|---|---|
| 01 | [create-program.md](01-create-program.md) | A new extension program (SIKAD-DIGITAL) with auto-generated code | Admin | — |
| 02 | [objectives.md](02-objectives.md) | 5 measurable objectives w/ live-computed KPIs | Admin | guide 01 |
| 03 | [activities-attendance.md](03-activities-attendance.md) | 3 activities, faculty assignment, attendance, completion | Admin | guides 01–02 |
| 04 | [beneficiaries.md](04-beneficiaries.md) | 5 registered beneficiaries + enroll + XLSX import | Admin | guide 01 |
| 05 | [budget.md](05-budget.md) | Budget entries, 70% → 85% utilization, over-allocation warning | Admin | guides 01–04 |
| 06 | [assessment-wizard.md](06-assessment-wizard.md) | A complete needs assessment (Sections I–IX) for one respondent | Faculty or Secretary | — |
| 07 | [assessment-import.md](07-assessment-import.md) | The same assessment via the official XLSX template | Faculty or Secretary | — |
| 08 | [proposal-workflow.md](08-proposal-workflow.md) | Faculty proposal → Director approval → auto-created activity (+ the blocked case) | Faculty + Admin | two browser windows |
| 09 | [availability-rendered-hours.md](09-availability-rendered-hours.md) | Availability request cycle + rendered-hours lifecycle (draft → adjust → approve → lock) | Admin + Faculty | two browser windows |
| 10 | [ai-analysis-narratives.md](10-ai-analysis-narratives.md) | AI community insights (with approval gate) + program narratives | Admin | Secretary should validate assessments first |
| 11 | [communities-schools.md](11-communities-schools.md) | A new community + a partner school | Admin | — |

## Suggested test storyline

The guides chain into one story — do them in order:

1. **Admin** runs 01 → 05 (program, objectives, activities, beneficiaries,
   budget). This is the same SIKAD-DIGITAL example used in `TEST-SCRIPT.md`.
2. **Faculty or Secretary** encodes assessments (06 or 07); **Secretary**
   validates them (see `secretaryguide.md` §2).
3. **Faculty** submits a proposal; **Admin** approves it (08).
4. **Admin + Faculty** trade an availability request and rendered hours (09).
5. **Admin** closes the loop with AI insights and narratives (10).

## Rules that show up everywhere

- **8.8 scheduling**: activity dates must fall inside the program's date
  range; a faculty member can never be double-booked; approval of an
  out-of-range proposal is blocked.
- **Live derivation**: objective statuses, dashboard numbers, and the
  "Objectives met: X/Y" chip are computed from program data on every render —
  nothing is stored.
- **Never a hard reject**: over-budget saves with a warning (D7); XLSX
  imports skip bad rows, not the whole file (D10); unknown dropdown values
  auto-map to "Other" (D9).
- **Audit trail**: approvals, rejections, status changes, and overruns are
  always activity-logged (D8).
