# SmartCEMES

**AI-powered Community Extension Monitoring & Evaluation System** for
**Leyte Normal University (LNU)** — Community Extension Services Office (CESO).

A capstone project. Laravel 12 · Livewire 3 · Tailwind/Vite · Alpine · Chart.js · MariaDB · Google
Gemini (flash-class).

---

## Status (2026-09-24)

| | |
|---|---|
| **Tests** | **444 passing / 1960 assertions, 0 failures** |
| Original build (Phases 1–5) | ✅ Complete |
| Adviser revision (P0, R1–R6) | ✅ Complete |
| **R7 — hardening & docs** | 🔨 **In progress — documentation and verification only, no features** |

**The system runs. No feature work is outstanding.** Everything a user can click exists and has been
smoke-tested by URL across all three roles.

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

Two other decisions override older documentation:

- **Training hours = `trainors × trainees × days`. There is NO `× 8`** — `days` already carries duration.
  `TrainingHoursService` is the single implementation.
- **Targets live at University + Project level only.** Broad programs and colleges carry none. The
  university target is a *consumption pool*, not a ratio.

## Quick start

```bash
composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate          # then set GEMINI_API_KEY for any AI feature

php artisan migrate:fresh --seed  # 4 colleges, 6 programs, 7 projects, demo data
php artisan serve
```

> **If you hit "table doesn't exist"**, your database is behind the migrations — the local MariaDB does
> not track the repo automatically. Run `php artisan migrate:status` first; a plain `migrate` on a
> populated database leaves the hierarchy orphaned, so use `migrate:fresh --seed`.

### Demo accounts

All six use password `password`:

| Role | Email |
|---|---|
| Admin (Director) | `admin@lnu.com` |
| Secretary | `secretary@lnu.com` |
| Faculty | `faculty1@lnu.com` … `faculty4@lnu.com` |

Roles are fixed to exactly three. **AI is admin-only** — secretary and faculty have no AI surfaces.

## Verification

```bash
php artisan test                     # 444 tests / 1960 assertions
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
| **`revisions.md`** | **The post-review record. §10 is the status tracker; §12–§19 are the phase write-ups.** Authoritative for everything after Phases 1–5. |
| **`AI_HANDOFF.md`** | Session handoff — current state, locked decisions, data model, gotchas. Start with the box at the top. |
| `SYSTEM_BLUEPRINT_V4.txt` | The design contract (v4.19). `revisions.md` records later owner amendments that supersede it. |
| `docs/prototype/` | The visual contract. Laravel output should look like these pages. |

### Known documentation debt

- **`docs/adminguide.md` · `docs/secretaryguide.md` · `docs/facultyguide.md` · `docs/features.md`** —
  refreshed for v4.13 but **not** for the post-defence revision, so they still call a **project** a
  "program" and still reference the retired results framework / 8.6 KPIs. Treat them as rough structural
  guides, not as current. (`facultyguide.md` had the worst single case; its retired-feature reference is
  fixed, but the "program" naming throughout is not.)
- ~~`docs/TEST-SCRIPT.md` and `docs/guides/*`~~ — **✅ current.** `TEST-SCRIPT.md` was rewritten and executed
  2026-09-24, and the 11 `docs/guides/*` walkthroughs were renamed and rewritten for the revision
  (`01-create-project.md`, `02-targets.md`, …) and verified 2026-09-25. **Read before assuming either way** —
  this file has under-reported progress as often as it has over-reported it.

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
