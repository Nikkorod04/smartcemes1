# SmartCEMES — Feature Guides (with real examples)

_Step-by-step walkthroughs for every major feature, each with **exact sample
data to type** and the expected result. Companion docs: `docs/adminguide.md` /
`secretaryguide.md` / `facultyguide.md` (role overviews), and
`docs/TEST-SCRIPT.md` (the end-to-end defence walkthrough)._

> **The hierarchy is `College → Program → Project → Activity`.** A **Program** is
> one of the six broad CESO thrusts; a **Project** is the thing with dates, a
> budget, activities and targets. Where an older note says "program" and means
> the narrow entity, it means a *project*.
>
> **Targets exist at two levels only — University and Project** (D-R5). Broad
> programs and colleges carry none; their figures are derived roll-ups of the
> projects beneath them.
>
> **Training hours are `trainors × trainees × days` — there is NO `× 8`.** `Days`
> carries the duration (0.5 = a half day).
>
> **The admin sidebar collapses the hierarchy into ONE entry** — *Manage
> Extension Programs* — which opens the colleges hub. Colleges, Programs and
> Projects are reached from inside that hub, never as separate sidebar items.

**Demo link:** https://disorder-childless-moonlike.ngrok-free.dev — click
**Visit Site** on the ngrok warning page. All accounts use password
`password` (admin@lnu.com · secretary@lnu.com · faculty1–4@lnu.com).

## Guide index

| # | Guide | What you will build | Role | Needs |
|---|---|---|---|---|
| 01 | [create-project.md](01-create-project.md) | A new extension **project** (SIKAD-DIGITAL) with a college-prefixed auto code | Admin | — |
| 02 | [targets.md](02-targets.md) | The university pool **and** a project's annual targets — the only two target levels | Admin | guide 01 |
| 03 | [activities-attendance.md](03-activities-attendance.md) | 3 activities (with Days + Participants), faculty assignment, attendance import, completion | Admin | guides 01–02 |
| 04 | [beneficiaries.md](04-beneficiaries.md) | 5 registered beneficiaries + enroll + XLSX import | Admin | guide 01 |
| 05 | [budget.md](05-budget.md) | Budget entries, 70% → 85% utilization, over-allocation warning | Admin | guides 01–04 |
| 06 | [assessment-wizard.md](06-assessment-wizard.md) | A complete needs assessment (Sections I–IX) for one respondent | Faculty or Secretary | — |
| 07 | [assessment-import.md](07-assessment-import.md) | The same assessment via the official XLSX template | Faculty or Secretary | — |
| 08 | [proposal-workflow.md](08-proposal-workflow.md) | Faculty proposal → Director approval → auto-created activity (+ the blocked case) | Faculty + Admin | two browser windows |
| 09 | [availability-rendered-hours.md](09-availability-rendered-hours.md) | Availability request cycle + rendered-hours lifecycle (draft → adjust → approve → lock) | Admin + Faculty | two browser windows |
| 10 | [ai-analysis-narratives.md](10-ai-analysis-narratives.md) | AI community insights (with the three-tier scope guardrail + approval gate) and project narratives | Admin | `GEMINI_API_KEY`; assessments validated first |
| 11 | [communities-schools.md](11-communities-schools.md) | A new community + a partner school | Admin | — |

## Suggested test storyline

The guides chain into one story — do them in order:

1. **Admin** runs 01 → 05 (project, targets, activities, beneficiaries,
   budget).
2. **Faculty or Secretary** encodes assessments (06 or 07); **Secretary**
   validates them (see `secretaryguide.md` §2).
3. **Faculty** submits a proposal; **Admin** approves it (08).
4. **Admin + Faculty** trade an availability request and rendered hours (09).
5. **Admin** closes the loop with AI insights and narratives (10).

## Rules that show up everywhere

- **8.8 scheduling**: activity dates must fall inside the **project's** date
  range; a faculty member can never be double-booked; approval of an
  out-of-range proposal is blocked.
- **Live derivation**: every performance figure is computed from the recorded
  activities on every render — nothing is stored. There is no objective status
  to go stale, and no "Objectives met" chip to drift.
- **Two target levels only**: the university pool (a *consumption* pool, not a
  ratio) and each project's own annual targets. Never a broad program, never a
  college, never a professor — an attainment % at those levels would be
  invented.
- **Never a hard reject**: over-budget saves with a warning (D7); XLSX imports
  skip bad rows, not the whole file (D10); unknown dropdown values auto-map to
  "Other" (D9).
- **Audit trail**: approvals, rejections, status changes, and overruns are
  always activity-logged (D8).
- **AI is Admin-only and scope-guarded**: a need CESO cannot serve is
  reclassified as a Tier-2 **interagency referral** naming a real agency from
  the catalogue — never dropped, never presented as CESO work (D-R8).
