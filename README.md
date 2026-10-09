# SmartCEMES

**AI-powered Community Extension Monitoring & Evaluation System** for
**Leyte Normal University (LNU)** — Community Extension Services Office (CESO).

A capstone project. Laravel 12 · Livewire 3 · Tailwind/Vite · Alpine · Chart.js · MariaDB · Google
Gemini (flash-class).

---

## Status (2026-10-07)

| | |
|---|---|
| **Tests** | **572 passing / 3318 assertions, 0 failures** |
| Original build (Phases 1–5) | ✅ Complete |
| Adviser revision (P0, R1–R6) | ✅ Complete |
| **R7 — hardening & docs** | 🔨 **In progress — only the production deploy remains (`AI_HANDOFF.md` §15.4)** |
| Post-R7 amendments | ✅ §20–§32 complete — see `revisions.md` |

**The system runs. No feature work is outstanding.** Everything a user can click exists and has been
smoke-tested by URL across all three roles. The demo set is the five safe-zone projects
(**KULTURA · NUMERO · LINIS · PAGKAON · DIGITAL**); the eight originals are archived and restorable.

## ⚠️ The hierarchy is four levels — read this before touching code

```
College  →  Program  →  Project  →  Activity
CAS/COE/CME/GRAD   6 CESO   (was "ExtensionProgram")   (unchanged)
                   thrusts
```

A post-defence adviser review restructured this. **"Program" used to mean the entity that is now a
"Project"** — the rename is the single most common source of confusion.

| Level | Table | Class | Routes |
|---|---|---|---|
| College | `colleges` | `College` | `colleges.index` |
| Program (**broad**, one of 6 CESO thrusts) | `programs` | `Program` | `programs.index` |
| Project (**narrow**, was "program") | `extension_projects` | `ExtensionProject` | `projects.index` / `projects.show` / `projects.my` |
| Activity | `activities` | `Activity` | — |

Four other decisions override older documentation:

- **Training hours = `trainors × trainees × days`. There is NO `× 8`** — `days` already carries duration.
  `TrainingHoursService` is the single implementation.
- **Targets live at University + Project level only.** Broad programs and colleges carry none. The
  university target is a *consumption pool*, not a ratio.
- **Budget has NO annual target (v4.19).** A project's `allocated_budget` is its only budget figure and
  every budget surface reads "Allocated Budget"; `annual_target_budget` is retained but **unread**.
- **The college set is FIXED at four and read-only** — CAS / COE / CME / GRAD. There is no college CRUD:
  `CollegeSeeder` is its only owner, so a name, description or coordinator is corrected *there*.

## Quick start

```bash
composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate          # then set GEMINI_API_KEY for any AI feature

php artisan migrate:fresh --seed  # 4 colleges, 6 programs, 5 live projects (+8 archived), demo data
php artisan serve
```

> **If you hit "table doesn't exist"**, your database is behind the migrations — the local MariaDB does
> not track the repo automatically. Run `php artisan migrate:status` first; a plain `migrate` on a
> populated database leaves the hierarchy orphaned, so use `migrate:fresh --seed`.

### Demo accounts

All **eight** use password `password` — **change or disable them before any public deployment**:

| Role | Email |
|---|---|
| Admin (Director) | `admin@lnu.com` |
| Secretary | `secretary@lnu.com` |
| Faculty | `faculty1@lnu.com` … `faculty4@lnu.com` |
| Faculty (Graduate School) | `faculty5@lnu.com` · `faculty6@lnu.com` |

Roles are fixed to exactly three. **AI is admin-only** — secretary and faculty have no AI surfaces.

## Verification

```bash
vendor/bin/phpunit                   # 572 tests / 3318 assertions — prefer this to `php artisan test`,
                                     # which can be SIGTERM'd in the foreground on this machine
vendor/bin/pint app tests            # style

# the prototype's own harnesses — run after ANY docs/prototype/ edit
node docs/prototype/_check.cjs
node docs/prototype/_smoke.cjs
node docs/prototype/_hubtest.cjs
node docs/prototype/_facultytest.cjs
node docs/prototype/_dashtest.cjs
node docs/prototype/_interagencytest.cjs
```

> **A passing suite does not mean the app runs.** `Livewire::test()` constructs components directly and
> bypasses routing, middleware and the view finder — a page can 500 while every test is green. That
> actually happened (`/my-projects`). `tests/Feature/RouteSurfaceTest.php` now walks every surface by URL
> per role; keep it that way.

## Documentation — read in this order

| Document | What it is |
|---|---|
| **`revisions.md`** | **The post-review record. §10 is the status tracker, §12–§19 are the phase write-ups and §20–§32 the later amendments.** Authoritative for everything after Phases 1–5. |
| **`AI_HANDOFF.md`** | Session handoff — current state, locked decisions, data model, gotchas. Start with the box at the top. |
| `SYSTEM_BLUEPRINT_V4.txt` | The design contract (**v4.23**). `revisions.md` records later owner amendments that supersede it. |
| `docs/prototype/` | The visual contract — a floor, not a ceiling. It **trails Laravel** on the college hub, the demo project set, the leaderboard sub-line and the dashboard glyphs, so a green harness run does *not* mean the app matches it. |

### Known documentation debt

- ~~**`docs/TEST-SCRIPT.md` now trails**~~ — **✅ fixed 2026-10-07.** It had described the pre-§31 demo
  (BUSOG's figures, the `CME-2026-001` code, a Feeding-Cycle-2 conflict demo). Corrected against the
  seeder and the live DB. Also corrected in the same pass: **`docs/PROJECT-EXPERTISE-PLAN.md`**, whose
  status line contradicted its own §10.
- **`docs/PROJECT-EXPERTISE-PLAN.md` contradicts itself** — its status line says the 5-project seeder is
  "planned, not built" while its own §10 is titled "✅ IMPLEMENTED (2026-10-05)". Its §6 (the faculty
  expertise vocabulary) is a genuine proposal the owner deferred.
- ~~`docs/guides/*` and the role guides~~ — **✅ current for the revision.** The 11 `docs/guides/*`
  walkthroughs were renamed and rewritten (`01-create-project.md`, `02-targets.md`, …), and
  `adminguide.md` / `features.md` / `01-create-project.md` were corrected again on 2026-09-28 for the
  Analytics removal, the hub's create/edit move and the retired target-budget field.
  ⚠️ **An earlier version of this section claimed the guides "still call a project a *program* and still
  reference the retired results framework / 8.6 KPIs". That was wrong — it was asserted, not checked.**
  Every 8.6 / results-framework hit in them is a deliberate *"this was removed — a regression if you see
  it"* statement, and `ExtensionProgram` appears in `docs/` only in `TEST-SCRIPT.md`.
  **Read before assuming either way** — this file has under-reported progress as often as over-reported it.

**Deployment:** there is no deploy script yet. The step-by-step checklist lives in `AI_HANDOFF.md` §15.4.

## Conventions that matter

- **Never inline training-hours or KPI maths in a view** — go through a service
  (`TrainingHoursService`, `FacultyContributionService`, `RankingService`).
- **NULL over 0.** No denominator / not measurable → `null`, and the UI says "not yet measurable" rather
  than printing a fabricated `0`.
- **Controlled vocabularies live in `config/smartcemes.php`** — never hardcode option lists in views.
- **Spatie Activity Log is required** on sensitive actions.
- **The nav is config-driven**, and items whose route does not exist are *silently hidden* — so a broken
  entry has no visible symptom. `RouteSurfaceTest` asserts every item resolves.
- **Tests run on SQLite `:memory:`** while dev runs on MariaDB — no MySQL-only SQL in app code paths.

## Out of scope

Per blueprint §1.2: OCR, extra XLSX imports, partner portal, PDF/Excel export, predictive analytics, dark
mode, ERP/HR integration, mobile, multi-role accounts.
