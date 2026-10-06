# SMARTCEMES — AI HANDOFF
_Last updated: 2026-09-28 · **491 tests / 2894 assertions passing** · Blueprint **v4.20** · **Phases 1–5 COMPLETE · revision phases P0 + R1–R6 COMPLETE · R7 (docs/hardening) IN PROGRESS** · prototype v4.3_

> ## ⚠️ READ THIS FIRST — the architecture changed after Phases 1–5
>
> Everything from Phases 1–5 still works, but the **extension hierarchy was restructured** by a
> post-defence adviser review, tracked in **`revisions.md`** (phases `R1`–`R7`). If you read only one
> thing before touching code, read this box and then `revisions.md` §10 (the tracker).
>
> **The hierarchy is now four levels, not three:**
>
> ```
> College  →  Program  →  Project  →  Activity
> (CAS/COE/CME/GRAD) (6 CESO thrusts) (was "ExtensionProgram")  (unchanged)
> ```
>
> | Level | Table | Class | Route family |
> |---|---|---|---|
> | College | `colleges` | `App\Models\College` | `colleges.index` |
> | Program (**BROAD**, one of 6 CESO thrusts) | `programs` | `App\Models\Program` | `programs.index` |
> | Project (**NARROW**, was called "program") | `extension_projects` | `App\Models\ExtensionProject` | `projects.index` / `projects.show` / `projects.my` |
>
> **The single most common mistake a new session makes is reading "program" in old prose and building
> the wrong level.** The R2 rename moved what used to be `extension_programs`/`ExtensionProgram` to
> `extension_projects`/`ExtensionProject`; the name `programs`/`Program` now means the broad CESO thrust.
> Legacy project codes (`EXT-2026-00X`) were kept for history; new projects get college prefixes
> (`CAS-2026-001`).
>
> **Two things about the COLLEGE level that will bite you (both added 2026-09-25):**
> - **The college set is FIXED at four — CAS, COE, CME and GRAD (the Graduate School) — and it is
>   READ-ONLY.** There is **no college CRUD**: the component exposes no `create`/`edit`/`save`, the view
>   has no New College or Edit button, and `CollegePolicy` has no write abilities. `CollegeSeeder` is the
>   set's **only** owner, so a name, description or coordinator is corrected THERE. `manage` survives in
>   the policy, but it now means **hub access** (the `/colleges` page is the Director's entry point to
>   the whole hierarchy), **not** a write permission.
> - **A project's college follows its SUBJECT DOMAIN, not its lead's college.** The R2 backfill derived
>   it from `program_lead_id`, which filed the sports project under the *business* college (its lead
>   teaches entrepreneurship) and the health project under *education*. Two leads now deliberately
>   differ from their project's college, and **nothing validates that pairing** — so do not "fix" it.
>
> **Other decisions that override anything older in this file:**
> - **Training hours = `trainors × trainees × days`. There is NO `× 8`.** §2.2A removed it — `days`
>   already carries duration, so `× 8` double-counted. `TrainingHoursService` is the single source of
>   truth. If you see `× 8` reappear, it is a regression.
> - **Budget has NO annual target — the ALLOCATION is the basis** (v4.19, 2026-09-26). A project's
>   `allocated_budget` is its only budget figure. `ExtensionProject::budgetAllocated()` is the single
>   accessor; `budgetTarget()` no longer exists, and the per-project `annual_target_budget` column is
>   **retained but unread** (the project form and `R4TargetsSeeder` no longer write it). The **university
>   budget pool is unaffected**. No budget surface may say "Annual Target" — grep `budgetTarget` /
>   `budgetVsTarget` if a regression is suspected.
> - **Targets live at University + Project level ONLY** (D-R5). Broad programs and colleges carry **no**
>   target. The university target is a *consumption pool*, not a ratio.
> - **All 8.6 KPI surfaces were removed** from project/performance views (D-R7). The 8.6 dictionary,
>   `KpiService` and `ProgramObjective` are **retained in code but unread** (R-Q2) — see §6.
> - **The Admin Analytics page is GONE** (v4.20, owner decision 2026-09-27). `/analytics` — blueprint
>   5.13's "six dashboards in six tabs" — was **deleted outright**: route, `App\Livewire\Analytics`,
>   `analytics.blade.php`, the six `analytics/partials/tab-*.blade.php`, the sidebar entry, its five
>   tests, and the prototype page. Four tabs duplicated the admin dashboard and Faculty Contribution was
>   superseded by the R3 module; the two tabs with no substitute — the aggregate **Pending Actions list**
>   and the **Community Reach chart** (with the retired `community_reach` measure) — were **accepted as
>   deliberate losses**. Do not restore the nav entry or the route without an owner decision:
>   `revisions.md` §21.
> - **The college hub is a THREE-view drill-down** (2026-09-27, §23). `/colleges` → college → **the
>   programs that college delivers** → that program's projects. **A college's programs are DERIVED from
>   its projects** (`programs` has no `college_id`, §3) — never query or assign them. There is **no
>   cross-college program or project list in the flow** any more; `/programs` and `/projects` still exist
>   (they host the create forms) but nothing in the UI links to them for browsing.
> - **The prototype now TRAILS Laravel on the hub.** `colleges.html` still shows the old two-view flow and
>   its harnesses still assert it, so `_check.cjs`/`_hubtest.cjs` passing does NOT mean the hub matches
>   the app. See `revisions.md` §23.6.
> - **ALL create/edit for programs and projects happens on `/colleges`** (§25). New program → view 2;
>   Edit program and New project → view 3, both as **modals**. `/programs` and `/projects` still resolve
>   but are **read-only lists with no inbound links** — do not "fix" that by re-linking them, and do not
>   re-add a deep link: the `?new=1` flag was deleted because the modal's Alpine `$wire.$watch` bridge
>   never fired when the flag was already true at mount.
> - **AI is admin-only and now three-tier** (§7.1): Tier 1 CESO intervention · Tier 2 interagency
>   referral (citable agencies come **only** from the `interagency_agencies` catalogue) · Tier 3
>   prohibited as CESO work.
> - **The AI no longer recommends things CESO cannot do.** Malnutrition, roads, water potability etc. are
>   *reclassified* as Tier-2 referrals, never dropped.
>
> **Where to look:** `revisions.md` is the authoritative record of every post-review change (§10 tracker;
> §12–§18 are the phase write-ups). Where `revisions.md` and this file disagree, **`revisions.md` wins**,
> and where either disagrees with `SYSTEM_BLUEPRINT_V4.txt`, the blueprint is the design contract but
> `revisions.md` records the owner's later amendments.

You are picking up **SmartCEMES**, an AI-powered Community Extension Monitoring and
Evaluation System for Leyte Normal University (capstone project). This document
transfers everything an AI agent needs to continue the work correctly. Read it
fully before writing any code.

---

## 1. CURRENT STATE (accurate as of this document)

### 1.0 Where the project actually stands (2026-09-26)

| Item | Value |
|---|---|
| **Tests** | **491 passing / 2894 assertions**, 0 failures |
| Phases 1–5 (original build) | ✅ Complete |
| P0 (prototype pass) | ✅ Complete |
| R1 colleges + broad Program level | ✅ Complete — `revisions.md` §12 |
| R2 `extension_programs` → `extension_projects` rename | ✅ Complete — §13 |
| R3 Faculty Management module (R3a + R3b + **R3c**) | ✅ Complete — §14 + §18 |
| R4 training-hours model + project performance | ✅ Complete — §15 |
| R5 filters, rankings, dashboards, reports | ✅ Complete — §16 |
| R6 AI guardrail + interagency catalogue | ✅ Complete — §17 |
| **R7 hardening & docs** | 🔨 **IN PROGRESS — the only phase left** |
| Local DB | MariaDB `smartcemes_v4` @ 127.0.0.1:3306, root/no password. **It does not auto-sync with the repo** — run `php artisan migrate:status` first if you hit a "table doesn't exist" error. |
| Seeded demo data | 4 colleges · 6 programs · 8 projects · 15 activities · 57 beneficiaries · 8 accounts (6 faculty) |

**R7 is complete except for the production deploy:**
1. ~~Blueprint stale sections~~ ✅ *done — the blueprint is now **v4.20**. §15.3 covers the v4.16
   stale-section rewrite; `revisions.md` §19.12 covers the v4.18 dashboard pass.*
2. This file's own update. ✅ *done (2026-09-24, and again 2026-09-25)*
3. Full test sweep + `pint` + a from-scratch re-seed. ✅ *done — **491 / 2894***
4. Re-run the defence walkthrough end to end. ✅ *done — `docs/TEST-SCRIPT.md` rewritten and executed;
   `tests/Feature/DefenceWalkthroughTest.php` pins every figure it prints.*
5. ~~`docs/guides/*` + the role guides~~ ✅ *done — **verified 2026-09-25 by reading them.** The guides
   were RENAMED and REWRITTEN for the revision: `01-create-program.md` → **`01-create-project.md`**,
   `02-objectives.md` → **`02-targets.md`**, plus 7 more modified. The staleness banners are gone, and
   `README.md` states the hierarchy, the two-level target rule and the no-`× 8` formula.*
   ⚠️ *An earlier revision of this file claimed they were "still bannered, not refreshed" — that was
   **wrong**; it had been asserted rather than checked. Do not re-open it without reading them.*

**The only thing left in R7 is the production deploy** — see item 5 of §15.

**Completed since the previous handoff (2026-09-24 → 2026-09-25), in order:**
- the `/colleges` two-view hub, the `/programs` fidelity pass (D-R5-legal), the **collapsed admin nav**,
  the **nightly database backup**, the **defence walkthrough re-run** — which found two real bugs in the
  8.8 conflict guard (§14.2) — and the **blueprint v4.16 stale-section rewrite**;
- the **college seals**: all four official seals now render on the cards and in the hero — the
  Graduate School's landed 2026-09-27 (`revisions.md` §19.9.5), so `.college-crest` no longer renders
  for any seeded college and is now a pure fallback, pinned by its own test;
- the **project→college mapping correction**: a project's college is now its SUBJECT DOMAIN, not its
  lead's college (§19.10);
- the **Graduate School (GRAD)**: a fourth college, the college set made **FIXED and read-only (no
  CRUD)**, and the graduate starter set — 2 faculty, a coordinator, the PANDAY project (§19.11);
- the **admin dashboard pass**: the Audit Logs page, budget bullet rows, leader bars, and every emoji
  and typographic glyph replaced with an SVG icon — in Laravel **and** the prototype (§19.12).

**Completed 2026-09-26 — v4.19, the budget-basis correction (`revisions.md` §20):**
- The dashboard's "Budget Utilized vs Annual Target" panel claimed a target that does not exist. Only
  **2 of 8** projects carried an `annual_target_budget` at all, and both were seeded *equal to* their
  allocation — so the denominator was already the allocation and **no figure changed**; the label was
  the only lie. Every project-level budget surface now reads **"Allocated Budget"**.
- `ExtensionProject::budgetAllocated()` is the single accessor; `budgetTarget()` is **gone**.
  `TrainingHoursService::forProject()` returns **`allocated_budget`** (was `target_budget`) and the hub
  payload is `budgetVsAllocation` (was `budgetVsTarget`).
- `annual_target_budget` is **retained but unread**; the project form and `R4TargetsSeeder` no longer
  write it. The **university budget pool is untouched** — that one is a real commitment.
- Mirrored in the prototype (its `budgetTarget` demo field is gone) and in **PromptV2**, so the AI
  narrative no longer calls an allocation a "target". **PromptV1 is left frozen** as the historical
  record — do not "fix" it.
- Two pre-existing prototype defects were fixed in passing: a stale `x 8` in the training-hours comment
  and a `0.5-day = 4 hrs` caption (both double-counted the retired hourly factor).

**Completed 2026-09-27 — v4.20, the Analytics removal (`revisions.md` §21):**
- The Admin Analytics page (`/analytics`, blueprint 5.13 / 12.1) is **deleted outright** — the route and
  its import, `App\Livewire\Analytics`, `analytics.blade.php`, the six `analytics/partials/tab-*.blade.php`,
  the sidebar entry, its five tests and `docs/prototype/pages/analytics.html`. Four of its six tabs
  duplicated the admin dashboard; Faculty Contribution was superseded by the R3 module.
- **Two surfaces were knowingly lost, not relocated**: the aggregate Pending Actions list (the dashboard
  keeps only *counts*; the four queues keep their own pages) and the Community Reach barangay chart, which
  retires the `community_reach` measure. `/reports/community-impact` is a per-community cut, not a
  replacement.
- `KpiService::objectivesAtRisk()` now has **no production caller at all** — both consumers are gone (the
  dashboard Action Center by P0m, the analytics pending tab by this change). Annotated retained-but-unread.
- Suite **467 / 2139** (exactly the 5 tests that covered the page); all six prototype harnesses green.
  The deleted files are archived under `.workbuddy-ai/backups/2026-09-27-analytics-removal/` — the last
  git commit (2026-09-17) predates the whole revision, so `git checkout` could not have restored them.

**Also completed 2026-09-27 — the Graduate School seal (`revisions.md` §19.9.5):**
- `public/gs.png` (611×611) supplied by the owner; derived to `public/img/colleges/grad.png` (256px,
  91.8 KB) with the same crop-to-alpha-bbox recipe as the first three, so all four seals fill 98% of
  their frame. **The crop mattered** — `gs.png` filled only 79% of its frame against the others' 87%,
  so a plain downscale would have rendered GRAD visibly smaller.
- One line in `config/smartcemes.php` `college_logos`; both views pick it up from the existing `'logo'`
  payload key, so **no view edit was needed**.
- **All four colleges are now sealed**, so `.college-crest` no longer renders. `ExtensionHubTest` now
  expects **0** crests (was 1), and a **new test pins the fallback** by dropping GRAD from the config —
  necessary because the college set is fixed at four with no CRUD, so a broken fallback could never
  surface in the UI.
- Suite **468 / 2145**. All four seal URLs verified over HTTP: 200, `image/png`, byte-identical to disk.
  The prototype was deliberately **not** mirrored (§19.9.2) and its harnesses stay green.

**Also completed 2026-09-27 — the faculty self-edit widened (`revisions.md` §22):**
- A faculty member now edits their own **specialization · department · contact number · address ·
  expertise areas** from their profile. Employee ID, college, position, status **and** the login account
  (name, email) stay Director-only. `FacultyPolicy::updateOwnContactDetails()` →
  **`updateOwnProfile()`**; `Profile::editContact()`/`saveContact()` →
  **`editProfile()`/`saveProfile()`**; the save writes an activity-log entry (`faculty_self_update`).
- **This was NOT a new feature — it was the code catching up with its own contract.** Blueprint §2.3/§5.1,
  `revisions.md` §11.10, the prototype's self-edit drawer and `docs/facultyguide.md` **all** said expertise
  was self-editable; `revisions.md` §14.3 said the opposite, and the implementation followed §14.3. That
  paragraph now carries a correction note. See §22.1 before touching this boundary.
- **The entry point now exists.** `/faculty/{id}` was correctly scoped but unreachable — the faculty
  sidebar had no link, so the only way in was to type the URL. New parameterless route **`faculty.me`**
  (`/my-profile`) + the faculty nav's **"My Profile"** section, mirroring the prototype's nav exactly
  (label `My Faculty Profile`, icon `users`). `Profile::mount()` takes the id as optional and falls back
  to the authenticated user's own record, so one component serves both routes.
- `Faculty::expertiseCategoryMap()` extracted from a *private* method on the Directory, because two
  components now write expertise and must file an area under the same category.
- Suite **476 / 2177**. All six prototype harnesses green — **the prototype needed no change**: its
  faculty nav and its self-edit drawer were already correct, which is how the contradiction went unseen.

**Also completed 2026-09-27 — the college hub drill-down (`revisions.md` §23):**
- `/colleges` is now a **THREE**-view page. View 1 the four college cards; view 2 that college's hero,
  KPIs and **the programs it delivers**; view 3 that program's projects. The drill-down reads
  College → Program → Projects → Activities, which is what the hierarchy always claimed.
- **Programs are DERIVED from the college's projects** — `programs` has no `college_id` (§3), so there is
  nothing to query or assign. `Colleges\Index::programRows()` groups by `program_id` and rolls up through
  `TrainingHoursService`, so this level cannot disagree with the project hub.
- **Every cross-college browsing link is gone**: the hub hero's "Programs" button, the view-1 "View all
  programs →", and the dashboard's "All projects →" (now "Manage programs →" at the hub). The project
  hub's back-link was repointed to return to *that project's college and program* instead of the flat list.
- **`/programs` and `/projects` still exist and `subs` was NOT trimmed.** They host the only create/edit
  forms, and `subs` is a **highlight** list, not a navigation list — trimming it would leave the sidebar
  with nothing lit on `/projects`, which is exactly where the New project action lands.
- **New project** sits in view 3 and links to `/projects?college=…&program=…&new=1`; `Programs\Index`
  gained a `#[Url] $new` flag that opens the form pre-filled. One create path, one validation ruleset.
- Suite **484 / 2208**. All six prototype harnesses green — **the prototype is deliberately NOT mirrored
  and is now BEHIND Laravel** (§23.6). That is the first time this project has recorded the prototype
  trailing rather than leading; reconciling later means moving `colleges.html`, `_hubtest.cjs`,
  `_check.cjs:224-307` and `PATTERNS.md` together.

**Also completed 2026-09-28 — the hub's college-selected view redesigned (`revisions.md` §24):**
- View 2 now leads with a **breadcrumb**, a **132px brand band** in the college's own colour carrying a
  112px seal (plus a soft halo — two of the four seals have a navy ring that vanishes into a navy band),
  a code watermark, and a 4-chip stat strip. The KPI tiles gained **tinted icon chips**; the program grid
  went **2-up** so a college delivering one or two thrusts fills its row.
- **View 3 was restyled too**, beyond the literal ask — once view 2 was rebuilt, view 3 looked like a
  different, older product on the same page. Presentation only; no data, query, route or policy moved.
- **One design RULE had been broken and is fixed:** PATTERNS §7 allows a `Training hours` label only on a
  *project* card (no college or program has an hours target). The view-3 program KPI tile said exactly
  that — it now reads **`Hours rendered`**. Pinned by
  `ExtensionHubTest::test_only_project_cards_label_training_hours`.
- **No new colour vocabulary** — every hue is already in `tailwind.config.js`. `npm run build` re-run
  (`app-*.css` 99.30 → 104.34 kB).
- **Verification needed a new tool.** Browser automation refuses on Windows, so `.workbuddy-ai/shot.sh`
  logs in over HTTP with curl and screenshots the real page with headless Chrome. Two gotchas are in §24.5:
  Chrome needs a **Windows** path for `--screenshot` (`cygpath -w`), and the snapshot must live under
  `public/` or the CSS and Figtree fonts 404 and you judge a page rendered in the wrong typeface.

**Also completed 2026-09-28 — the hub owns create/edit (`revisions.md` §25):**
- **Three regressions from §23, all fixed.** (1) The 6 broad programs could not be added or edited —
  unlinking `/programs` left its create form with **zero inbound links**. (2) "New project" navigated to
  `/projects`, a page the owner had asked to remove. (3) Creation was broken: the modal's Alpine
  `$wire.$watch` visibility bridge **only fires on change**, and the `?new=1` deep link set the flag
  during `mount()`, so it never fired and the page rendered with **no form on it** (§14's known trap).
- **The forms MOVED into the hub**: New program (view 2), Edit program (view 3), New project (view 3, a
  **modal trigger**). Both modals render **server-side `@if`** with an empty `x-data`, so no Alpine
  visibility gate is left to fail. The `?new=1` flag, its `mount()` and the deep link are **deleted**.
- **MOVE, not duplicate**: `BroadPrograms` and `Programs\Index` lost their create/edit and form state —
  keeping a second copy would have meant two implementations of one validation ruleset. **`/programs` and
  `/projects` are now READ-ONLY lists** (they keep their roll-ups, filters and sort; `Programs\Index` went
  from 232 to 124 lines). No route was deleted, and both stay in the nav's `subs` (§23.5).
- **The 6 thrusts stay editable**, including adding one — the owner's decision, and a deliberate exception
  to §3's "verbatim CESO thrust" framing.
- Suite **485 / 2217**. 8 tests re-pointed at the hub (3 program-CRUD, 2 project-form, the walkthrough, 2
  replaced in `ExtensionHubTest`), plus a guard asserting `new=1` no longer appears. Both modals were
  **screenshotted open and confirmed visible** (§25.5).
- **Two screenshot-harness lessons**: Chrome needs **`--user-data-dir`** or a hung run poisons the default
  profile and later launches die with *no output and exit 0*; and **`--virtual-time-budget` hangs** waiting
  on a Livewire round-trip — to photograph a modal, temporarily default its flag to `true` instead.

**Verified working (smoke-tested by URL across all three roles, against the real MySQL driver):**
all **63 routes** resolve, no 5xx on any surface, and **zero phantom nav entries** (every sidebar item
points at a route that exists — historically some did not, and the sidebar silently hides them).

**Known gaps, carried deliberately:**
- `targets.html` has no Laravel counterpart contract — the real page is a **native build** (a recorded
  deliberate divergence, `revisions.md` §16.10).
- The faculty profile page has **no prototype at all** (the prototype renders faculty detail into a JS
  drawer), so its trend chart is a native build too — `revisions.md` §18.4. Note the prototype **does**
  carry the surrounding contract — its faculty nav has the "My Profile" section and its self-edit drawer
  defines the writable-vs-locked field split (§22) — so "no prototype" applies to the *page*, not to the
  behaviour.
- The `/colleges` **college seals** are a Laravel-only build: the prototype keeps its `Logo`
  placeholder (`revisions.md` §19.9). All **four** colleges are sealed in Laravel since 2026-09-27,
  so `.college-crest` no longer renders there.
- **The demo ranking is lopsided BY DECISION.** Five projects have 2 beneficiaries each, and BATANG
  MATINIK has none *and* no activities, so the Performance Leaders panel is dominated by BUSOG. The
  owner chose to skip the fix — see §16 G. It is **not** a bug, and any magnitude chart will show it.
- **The Admin Analytics page is REMOVED BY DECISION** (v4.20, 2026-09-27) — see §1.0 and `revisions.md`
  §21. Its aggregate Pending Actions list and its Community Reach chart are **gone, not relocated**.
  Do not "restore" either without an owner decision.

### 1.1 Historical session log (Phases 1–5 and v4.13 — superseded, kept for provenance)

> The entries below describe the pre-revision state and are **historically accurate but no longer the
> current design**. They are retained because they explain *why* certain code exists. Read them for
> background, not for the current data model.

- **SESSION 15 — v4.13 ACTIVITY IMPORTS: STEPS 6 (TESTS) + 7 (DOCS) COMPLETE
  (2026-09-17)** — the feature is now DONE. *(At the time: 222 tests / 1082 assertions.
  The suite is now 491 / 2894 — see §1.0.)*
  - Step 6 tests: `BeneficiaryManagementTest` updated (the attendance test is
    rewritten to the import flow; the forbidden-action list now covers
    parse/confirm attendance + evaluation); NEW `ActivityAttendanceImportTest`
    (13) — template gating/422/structure, parse errors, D10 preview-only,
    confirm upserts/provenance/archive/log, re-import update, blank-skip, KPI
    integration, faculty gating + read-only modal; NEW `ActivityEvaluationTest`
    (11) — template gating/structure, per-row numeric + identity errors, mean
    aggregation, per-metric merge, all-blank refusal, knowledge-gain objective,
    faculty gating.
  - FIX found by the tests: `Hub::confirmAttendanceImport()` keyed the upsert
    on `format('Y-m-d')` while Eloquent's date cast stores `Y-m-d H:i:s`, so
    re-imports duplicated rows on sqlite (MySQL DATE coercion masked it). Now
    uses `toDateTimeString()` (§14).
  - Step 7 docs: blueprint v4.13 sections landed (§1.1/1.2, §2.2/2.3,
    §5.4/5.5/5.6, §6.5/6.7/6.18, NEW §6.19 `activity_imports`, §8.2, D11/D13)
    with the v4.13 revision entry inserted BEFORE v4.14 (v4.14's stale "docs
    land later" note removed). Prototype `program-detail.html` mirrored: the
    manual attendance drawer is replaced by the **Records** modal
    (Attendance/Evaluation tabs, upload → parse → per-row preview → confirm,
    before→after means, last-import provenance, faculty read-only variant);
    the activity row action is now **Records**; the missing
    `download`/`upload`/`eye` icon tokens were added to `layout.js` (pages had
    been rendering those tokens literally); fixed a mojibake arrow in
    `assessment-form.html`. `docs/TEST-SCRIPT.md`, `docs/features.md`,
    `docs/adminguide.md` and `docs/guides/03-activities-attendance.md`
    attendance steps refreshed to the import flow. node --check + forbidden
    greps re-run clean.
- **SESSION 14 — ADMIN DASHBOARD + PROGRAM HUB REDESIGN (2026-09-16,
  owner-approved port of the `prev/prototype2` review mockups; blueprint bumped
  to v4.14)** — all verified, 196 passing / 2 EXPECTED failures (198 total; the
  2 failures are the v4.13 step-6 BeneficiaryManagementTest rewrites):
  - Owner decisions: both pages; full mockup look; **NO Program Health
    Snapshot**; hub markers from results-framework objective targets only
    (hidden otherwise); **NO Q1–Q4 period filter**; mirror prototype docs +
    blueprint.
  - Admin dashboard (`Dashboard::renderAdmin()` + `dashboard/admin.blade.php`):
    KPI row now Programs (AY badge + status mini-seg), Beneficiaries (served vs
    combined program target + `.marker`), Budget (bar red when over D7 +
    allocation-weighted `budget_utilization` objective marker), Objectives
    achieved (live status mini-seg + legend), Pending approvals (breakdown + AI
    link). Charts rebuilt with INLINE JS configs (JS callbacks/plugins needed;
    data injected via `@js()`) instead of the old PHP-array payloads: doughnut
    with a `centerText` plugin (total + PROGRAMS), grouped budget bars (per-bar
    `#ef4444` when over, ₱ Intl axis ticks, "% of allocation — over (D7)"
    tooltip), horizontal top-10 reach ("Barangay · Municipality" + "Others (N
    barangays)" + rank color ramp). Action Center now sits ABOVE the AI panel;
    narrative cards show the latest summary text + model/date with the
    remaining programs as health badges; recent-activity dots colored by log
    event.
  - Hub (`Hub::render()` + `hub.blade.php`): D7 banner moved above the stat
    tiles; knowledge-gain tile shows pre → post averages; NEW **KPI Scorecard
    vs Targets** (5 live 8.6 rows; `.marker` ONLY from objective targets; color
    emerald when target met / red when below or over / lnu when no target) +
    cost per beneficiary / knowledge gain / avg satisfaction tiles; NEW
    **Attendance & Satisfaction** card (horizontal present/late attendees chart
    for completed activities + satisfaction rating rows). New payloads:
    `scorecard`, `attendanceChartRows`, `satisfactionRows`, `avgPre`/`avgPost`,
    `costPerBeneficiary`, `avgSatisfaction`; the attendance-count query now
    also yields `attendeesByActivity` (present+late).
  - Shared CSS: `.marker`, `.mini-seg`, `.legend-dot` added to
    `resources/css/app.css` (mirrored in
    `docs/prototype/assets/css/smartcemes.css`); `npm run build` re-run.
  - Also fixed (review-found, same session, SEPARATE from the redesign):
    `Analytics.php` pending tab lists real draft `AssessmentAnalysis` rows (was
    hardcoded `collect()` + a missing render branch; meta route now
    `ai-analysis.index`); knowledge-gain `'+'` no longer hardcoded in
    `tab-performance.blade.php`, `tab-overview.blade.php` (now `—` when no
    scored activities) and `hub.blade.php` (negative gains red, caption
    "decline").
  - Tests: NEW `RedesignUiTest` (4) + NEW `AnalyticsFixesTest` (2);
    `ObjectiveStatusTest` action-center assertion updated to the new markup.
  - Prototype mirrored: `docs/prototype/pages/dashboard-admin.html` fully
    rewritten (it had been DOUBLE-ENCODED — `Â·` mojibake — now clean UTF-8,
    no BOM) + `program-detail.html` scorecard/attendance section + CSS
    additions; inline scripts re-checked OK; forbidden-string greps clean.
  - **STILL DEFERRED from the mockups (owner decision): Program Health
    Snapshot and the Q1–Q4/Full-year period filter.**
- **SESSION 13 — ACTIVITY RECORDS: ATTENDANCE + EVALUATION XLSX IMPORTS
  (2026-09-16, owner request; blueprint v4.13) — COMPLETE: steps 1–7 all DONE
  (steps 1–4 session 13, step 5 session 14, steps 6–7 session 15 — see the
  SESSION 15 entry above for the tests/docs and the upsert fix).**
  - Owner decisions driving this work: attendance recording becomes
    **import-only** via an official XLSX template — the manual attendance
    panel was **REMOVED** (no more `openAttendance`/`saveAttendance`).
    Per-activity evaluation scores get the same treatment: a generated
    template pre-filled with the enrolled roster is imported back and
    **aggregated (mean)** into the existing `activities.pre_assessment_score`
    / `post_assessment_score` / `satisfaction_rating` columns — **no
    per-beneficiary evaluation table (D13 intact)**. Both imports are
    Admin+Secretary (`abortUnlessBeneficiaryManage`); viewing stays open to
    all hub viewers. Matching is by Beneficiary ID + name; unknown/
    duplicate/mismatched/invalid rows are skipped per row and shown on
    screen only (D10). Blank status/score cells leave existing data
    untouched. One attendance date per activity (`planned_start_date`);
    multi-day attendance stays future work. Evaluation confirm is a
    **per-metric merge** (a metric with no values in the file keeps the
    existing aggregate).
  - **DONE (steps 1–4):**
    - `activity_imports` table (migration
      `2026_09_16_000400_create_activity_imports_table.php`, already applied
      to the dev DB) + `App\Models\ActivityImport`
      (`TYPE_ATTENDANCE`/`TYPE_EVALUATION`, `summary` JSON cast) +
      `Activity::activityImports()`.
    - Generated per-activity templates: `App\Services\ActivityAttendanceTemplate`
      + `App\Services\ActivityEvaluationTemplate` (context header, enrolled
      roster pre-filled, freeze pane, non-strict list/decimal validations,
      500-row cap) served by `ActivityAttendanceTemplateController` /
      `ActivityEvaluationTemplateController`; routes
      `activities.attendance-template` / `activities.evaluation-template`
      (`auth` + `role:admin,secretary`). Shared
      `App\Services\Concerns\StylesTemplateSheet` (the `showDropDown`
      inversion gotcha honored) and `App\Services\Concerns\MatchesActivityRoster`
      (label→field header matching, roster lookup, ID+name identity check,
      cross-row duplicate detection).
    - Parsers `App\Services\ActivityAttendanceImport` (case-insensitive
      canonical present|absent|excused|late) and
      `App\Services\ActivityEvaluationImport` (0–100 / 1–5 ranges,
      per-metric `means()`).
    - `Programs\Hub` refactor: manual attendance props/methods REMOVED; new
      state (`recordsActivityId`, `recordsTab`, `attendanceImportFile/Step/
      Rows/Summary/FileName`, `evaluationImportFile/Step/Rows/Summary/
      FileName/Current`) and methods `openRecords` / `closeRecords` /
      `setRecordsTab` / `parseAttendanceImport` / `confirmAttendanceImport` /
      `parseEvaluationImport` / `confirmEvaluationImport`, plus helpers
      `parseUploadedFile` (short `tempnam` copy per §14) and `archiveImport`
      (public disk `activity-imports/{type}/Y/m`, `ActivityImport` row,
      Spatie events `attendance_import` / `evaluation_import`; confirm
      attendance upserts on (activity, beneficiary, `planned_start_date`)).
    - Minimal view surgery so the hub does not error: the old manual
      attendance modal was deleted from `hub-modals.blade.php` and the row
      action renamed to **Records** (`hub-activities.blade.php`) — the Records
      modal itself landed in step 5 (session 14, before the redesign).
    - Verified: `php -l` clean; `vendor/bin/pint app routes <migration>`
      passed; scratch round-trip on dev activity #1 (roster 2) — blank sheet
      = 2 skipped, filled present/late = 2 applied, bad status / unknown ID
      = per-row errors, evaluation means pre 45 / post 65 / sat 4.5 with
      single-row satisfaction merge; `php artisan test` = 190 passed, 2 failed.
    - **STEP 5 — views: DONE (session 14, before the redesign).** Records modal
      in `hub-modals.blade.php` — server-side `@if` blocks on
      `$recordsActivityId` + the upload/preview steps, Attendance/Evaluation
      tabs via `$recordsTab`, template download links, file inputs
      (`wire:model="attendanceImportFile"` / `evaluationImportFile`), parse →
      preview tables with per-row state chips + error lists, confirm buttons,
      evaluation before→after means (`$evaluationCurrent` + projected means in
      `$evaluationSummary['means']`), last-import provenance from
      `$recordsImports` (render query, 6.19), read-only variant for non-managers;
      activities-table chips (attendance count · pre→post · satisfaction);
      `Hub::render()` also computes `attendeesByActivity` (present+late).
      Verified with a scratch render test covering all four states.
  - **STEPS 6–7 — DONE (session 15).** Tests: new
    `ActivityAttendanceImportTest` (13) + `ActivityEvaluationTest` (11) and
    the `BeneficiaryManagementTest` rewrite; docs: blueprint v4.13 +
    prototype `program-detail.html` Records modal + the stale attendance
    steps in TEST-SCRIPT/features/adminguide/guide 03. Details in the
    SESSION 15 entry at the top of this section.
  - **prototype2 workstream (superseded by SESSION 14):** the
    `prev\prototype2` mockups were owner-approved and ported in session 14
    (see the top of this section) — horizontal top-10 reach, ₱ budget axes
    with over-allocation red, status doughnut, KPI scorecards, plus the two
    review-found bugs (`Analytics.php` hardcoded `aiAnalyses => collect()`;
    hardcoded knowledge-gain `'+'`). Still intentionally deferred from those
    mockups: Program Health Snapshot and the Q1–Q4 period filter.

- **SESSION 12 — SECRETARY BENEFICIARY MANAGEMENT (2026-09-16, owner request;
  blueprint bumped to v4.12)** — all verified, 188/188 tests green (815
  assertions):
  - **Scoped hub permission**: `ProgramPolicy::manageBeneficiaries()` (Admin OR
    Secretary). The Hub's beneficiary cluster (enroll existing / register new
    w/ dedup / unenroll / XLSX import incl. parse+confirm) and attendance
    RECORDING now guard via `abortUnlessBeneficiaryManage()`; viewing the
    attendance panel stays open to all hub viewers (as before). Program edit,
    objectives, activity CRUD, budget, narrative remain `abortUnlessManage()`
    (Admin-only) — separation of duties intact. Defense-in-depth: previously
    unguarded form openers `newObjective()`/`editObjective()` now abort for
    non-admins (§11 rule 4; exposed by the new 403 tests).
  - **NEW Secretary-only page `/beneficiaries`** (`App\Livewire\Beneficiaries
    \Index`, route name `beneficiaries.index`, middleware `role:secretary`):
    KPI cards (programs / enrollments / what-you-can-do explainer), program
    search, sc-table with code+lead, status, communities, enrolled count,
    activities count, and **Beneficiaries** / **Attendance** deep-link buttons
    → `/programs/{id}?tab=beneficiaries|activities`. The Hub `$tab` property
    is now `#[Url]`-bound so deep links land on the right panel. Hub back-link
    routes secretary users to the new page (they can't reach admin-only
    /programs); the hub read-only banner differentiates secretary (gold,
    scoped powers) from faculty (blue, read-only).
  - **Secretary nav**: new "Management" section w/ "Manage Beneficiaries"
    (config). The phantom "Compliance Tracker" nav entry was REMOVED from
    config AND prototype layout.js — it pointed at a route that never existed
    in Laravel (secretary compliance monitoring lives on the dashboard; the
    prototype's compliance.html page was never a blueprint §5 feature — file
    kept as reference, no longer in nav).
  - Tests: NEW `tests\Feature\BeneficiaryManagementTest` (9: policy scoping,
    secretary enroll/register/unenroll, secretary XLSX import, secretary
    attendance save, secretary 403s on admin-only hub actions, faculty 403s
    on beneficiary actions, /beneficiaries role gating incl. guest redirect,
    page list + deep links + nav label, tab deep-link renders). NOTE: one
    Testable per forbidden action — an aborted Livewire action invalidates
    the instance (subsequent calls throw "Invalid Livewire snapshot").
  - Prototype mirrored: layout.js secretary nav (Manage Beneficiaries →
    programs.html stand-in; the Laravel page leads, recorded as accepted
    drift). node --check re-run OK.
- **SESSION 11 — ASSESSMENT REVIEW PAGE FIX + REDESIGN (2026-09-16, owner-reported
  during manual testing)** — all verified, 176/176 tests green (759 assertions):
  - **Owner hit "TypeError implode(): Argument #2 must be of type ?array, string
    given" (review.blade.php:42) when opening a submission's detail.** Root cause:
    the detail row still called implode() on fields that v4.9 converted from JSON
    arrays to single-select STRINGS (education, livelihood, water source, house
    type). Fixed via `Review::answer()` (scalar-or-array safe) + `chipValues()`.
  - **"Nothing happens when I click Review"**: the rebuilt dossier drawer was
    wrapped in `@if ($selected)` but still used the Alpine `$wire.$watch`
    visibility bridge — the block mounts AFTER `showDrawer` flipped true, so the
    watcher never fires (same class of bug as the session-5 import page). Fixed
    with server-side `@if ($showDrawer && $selected)` (morph-safe, §14); the
    `.sc-drawer` CSS slide-in plays on mount; empty `x-data` keeps the
    escape/backdrop Alpine handlers alive.
  - **Page rebuilt to prototype conformance** (assessment-review.html): 4 KPI
    cards (pending/validated/returned/total), search (community/encoder/
    respondent) + quarter filter, richer table (municipality, XLSX/Manual source
    badge, uploader avatar, reviewer stamp, returned-remarks preview, clickable
    rows), right-drawer dossier with §7 Sections I–IX accordions (kv rows, Yes/No
    badges, chips for multi fields), D9 other-text panel, and a record-
    completeness progress bar (computed vs AssessmentTemplate::COLUMNS).
  - **Validate now opens a CONFIRM MODAL** (owner request; supersedes the
    prototype's instant validate): `openValidate()`/`confirmValidate()` mirror
    the return flow; replaced the old `markValidated()` (tests updated). The
    modal shows community/quarter + "on confirm" effects (summary recompute,
    encoder notification, audit stamp); z-[60] stacks above the drawer (§14).
  - **Pagination + period sort**: queue is now `paginate(10)` via
    WithPagination; search/quarter/sort state carries `#[Url]` (filter changes
    reset the page). The Period header cycles default (pending-first) → year+-
    quarter asc → desc via `toggleSortPeriod()`. NEW reusable partial
    `resources\views\livewire\partials\pagination.blade.php` (design-system
    Prev/page-number-window/Next via `wire:click="setPage/previousPage/
    nextPage"` — Livewire's SHIPPED views use Tailwind classes outside the
    Vite content globs, so don't use the default theme; override via the
    component's `paginationView()`). Note: Livewire 3 tracks the page in
    `$paginators['page']`, not a public `page` property (tests assert
    `paginators.page`).
  - Tests: NEW `tests\Feature\AssessmentReviewTest` (10: KPI render, drawer
    implode-regression, search/quarter filters, validate modal flow, already-
    reviewed 422, return remarks required, role 403s, 10-per-page pagination
    (distinct created_at stamps — sqlite timestamps share one second),
    filter-change page reset, year-then-quarter sort cycle); Phase3WorkflowTest
    updated to the new validate flow.
- **SESSION 10 — DRIFT-PROOF CODE GENERATION (2026-09-15, owner-reported bug
  during manual testing)** — all verified, 169/169 tests green (722
  assertions); blueprint bumped to v4.11:
  - **Owner hit "Duplicate entry 'EXT-2026-001'" creating a program via the
    New Program modal.** Two stacked defects: (1) `ExtensionProgram::
    nextCode()` called `SequenceService::next()` STATICALLY (fatal error;
    the static-call bug was fixed first with `app(SequenceService::class)`)
    — the modal was the ONLY runtime caller and had zero test coverage;
    (2) `Phase2Seeder` hardcodes codes EXT-2026-001..006 WITHOUT reserving
    sequence slots, so on an already-seeded DB the fresh sequence row
    starts at 0 and reissues 001 (each failed insert also consumed a
    value, leaving the row further behind).
  - **Fix (v4.11)**: `SequenceService::next($key, ?int $floor = null)` —
    reserved value = max(sequence row, floor) + 1 inside the existing
    insertOrIgnore + lockForUpdate transaction. The FLOOR is the max code
    suffix already stored, computed in `ExtensionProgram::nextCode()` via
    `withTrashed()` (soft-deleted rows still hold the unique index) and
    identically in `EmployeeIdService::next()` (same latent bug — service
    was previously unwired; it now also imports Faculty). The locked row
    remains the serializer (5.1 intact); the floor only ever raises it —
    seeded/manual drift self-heals with NO DB surgery needed. Gaps from
    failed inserts are acceptable; duplicates are not.
  - Tests: +4 in ProgramHubTest (seeded-codes skip incl. stale sequence
    row → 007, soft-deleted code never reused, healthy sequence ahead of
    data honored → 010, EmployeeIdService skips existing LNU-2026-0004 →
    0005) plus the earlier +2 from the static-call fix (nextCode format/
    increment, full Livewire form-create path with auto code).
  - NOTE for future seeders: hardcoding codes is fine now (the floor
    self-heals), but reserving sequence slots in seeders is still tidier.
- **SESSION 9 — OBJECTIVE-STATUS DERIVATION ENFORCEMENT + MANAGER UX (2026-09-15,
  later still)** — all verified, 163/163 tests green (705 assertions); blueprint
  bumped to v4.10:
  - **Correctness fix (§8.6)**: five consumers were reading the STALE
    `program_objectives.status` column (written once by the seeder, never again)
    instead of deriving live. Now ALL derive via `KpiService`: admin dashboard
    at-risk list, Analytics objective-status chart + at-risk pending action,
    ProgramNarratives "Objectives met: a/b" chip, and the 5.14 deadline
    scheduler (the objective-deadline notification path was effectively DEAD —
    nothing was ever `status='on_track'` in the column). New shared helper
    `KpiService::objectivesAtRisk($withinDays=14)` (not_met any time +
    on_track within N days, derived). The stored status column is now
    write-never/read-never (schema-only, unchanged).
  - **Objective manager UX (program hub)**: objective meta (status, effective
    actual, progress %, source) is computed ONCE in `Hub::render()` as
    `$objectiveMeta` and shared by the overview card + manager modal (no
    doubled KPI queries). Manager list now shows derived status badge,
    progress bar, baseline → target → actual, and a source tag — new
    `KpiService::actualSource()` (live | stored | manual) + `liveKpi()`
    (extracted from effectiveActual). The form's "Manual actual" field only
    renders for qualitative objectives (server-side @if on
    `objForm.kpi_metric`, select is `wire:model.live`); switching to a metric
    CLEARS the manual actual (`updatedObjFormKpiMetric` hook — never silently
    discarded on save); selecting a metric shows a live "Currently computed"
    preview (`Hub::currentComputedKpi()` — a probe ProgramObjective with the
    program relation set). Objective create/update/delete now write Spatie
    activity-log entries (D8: events objective_create/update/delete).
  - Tests: NEW `tests\Feature\ObjectiveStatusTest` (14: live-vs-stored
    derivation numeric + qualitative, at-risk set membership, dashboard /
    analytics chart / narratives chip render assertions, scheduler on_track
    notify + not_started skip, manager modal badge/progress/source, manual-
    actual conditional + clear-on-switch, numeric save nulls actual, audit
    rows). Prototype mirrored (program-detail.html manager list + form +
    edit-population + demo live-preview via `computedForKpi`); node --check
    re-run OK; forbidden strings re-grepped clean.

- **Laravel app: PHASE 5 COMPLETE (2026-09-13).** Phases 1–4 built (foundation,
  data modules, workflows, dashboards/reports). Phase 5 delivered — note the
  **AI provider was changed to Google Gemini (flash class, free tier) by the
  project owner; blueprint bumped to v4.2** (pipeline rules D3/D4/D12 unchanged,
  §9 updated). Built:
  - `assessment_analyses` (6.11) + `program_narratives` (6.16) migrations/models
    (relation gotcha: `AssessmentAnalysis.summary` TEXT column shadows the
    belongsTo → relation is `assessmentSummary()`).
   - Backbone: `App\Services\Ai\GeminiClient` (live REST, `x-goog-api-key`,
     JSON response mode, `AiUnavailableException` for every failure path) +
     versioned `Prompts\PromptV1` (prompt_version recorded in provenance).
   - **AI generation is SYNCHRONOUS since v4.4** (owner request): services
     `dispatchSync()` the jobs in-request — results appear immediately, NO
     `queue:work` needed for AI. On failure the first-class failed state is
     persisted in the service catch (the jobs' failed() hooks are the
     worker-path fallback only). Jobs are still ShouldQueue-compatible.
  - Queued jobs (database queue): GenerateAssessmentAnalysis /
    GenerateProgramNarrative — tries=3, backoff [60,180], terminal `failed`
    state with error_message via failed() hook; aggregate-only inputs (D3,
    tested that respondent PII never leaves the system).
  - Services: AssessmentAnalysisService (generate/approve/discard; approval
    stamps AssessmentSummary ai_* fields per 6.10 + D4 gate), ProgramNarrativeService
    (no approval gate, new row per generation), ProgramAggregates (8.6 KPI +
    objective-status payload).
  - Admin surfaces: `/ai-analysis` insights workspace (summary picker → generate,
    drafts queue w/ review/approve/discard, history + pending/failed states +
    retry; "Analysis unavailable" first-class), `/program-narratives` (per-program
    latest health label, version history, "Narrative unavailable" first-class),
    admin dashboard AI panel fully wired, program hub "Generate program narrative"
    button + live panel states.
   - Env: GEMINI_API_KEY (empty = first-class failure until the owner sets the
     free-tier key for demos — D12 live-API-only), GEMINI_MODEL=gemini-3.6-flash
     (gemini-2.0-flash was retired by Google — live API 404; bumped 2026-09-13).
   - Tests: 84/84 green (adds D4 gating, queue dispatch, provenance, D3 no-PII
     assertion on the outbound request, terminal failed state, approval stamping,
     narrative no-gate, admin-only pages, Http::fake Gemini responses).
     Next: Phase 6 (hardening: deploy, nightly backups, security pass, docs).
- **SESSION 8 — ASSESSMENT INSTRUMENT v4.9 (2026-09-15, final)** — all
  verified, 149/149 tests green (665 assertions); blueprint bumped to v4.9:
  - **16 former multi-select fields are now SINGLE-select** with renamed
    labels (Highest Educational Attainment · Main Source of Household
    Livelihood · Area of Educational Interest · Most Common Illness ·
    Organization Type · Organization Usual): educational attainment, family
    composition, household members in organization, livelihood, desired
    training, areas of interest, common illnesses, action when sick, water
    source, garbage, toilet type, house type, tenure, light source, org
    type, org usual. Model casts dropped for these (plain nullable strings;
    migration `2026_09_15_000300` converts the columns json→string
    nullable AND flattens stored arrays to their first value).
  - **Vocabulary changes**: civil status `Single/Married/Widowed/Separated/
    Divorced` (Live-in + Unknown dropped); religion → 16-option CLOSED list
    (no Other; Evangelical Christianity etc.); water source drops Bottled
    water; `Other` removed from family composition, appliances,
    recreational facilities, use of free time, org type/usual/position, and
    all 7 problem lists (CLOSED — unknown import values become per-field
    on-screen errors, D9 auto-map only applies where Other survives);
    reason_not_available → closed 5-option single-select (was free text).
  - **Removed `interested_in_livelihood_training` entirely** — wizard, XLSX
    template, config, model fillable/YES_NO_FIELDS, and the DB column
    (migration drops it).
  - **9 conditional rules** (§7): continuing-studies→areas; health-programs
    →benefits→programs-benefited chain; toilet→toilet_type; animals→
    animals_kept; electricity Yes→appliances / No→light source (both hidden
    when unanswered); member-of-org→4 org fields; available-for-training=No
    →reason chips. Rule 9: household_members_in_organization='None' derives
    member_of_organization='No' (auto-set, org details cleared); changing
    away from None re-shows the question CLEARED. Implementation: Wizard
    `updated()` hook clears hidden children; views use server-side `@if`
    on $form (§14-safe). XLSX imports do NOT enforce conditionals (D10).
  - **Exclusive chips**: Preferred Training Days "Flexible" and Medical
    Supplies "No supplies" replace the selection; picking a normal option
    drops the exclusive one (`toggleOption` + `exclusive` metadata in
    `AssessmentTemplate::COLUMNS`). **Problems capped at 3 per field**
    (`max: 3` metadata; toggleOption ignores clicks at the cap; validation
    `array|max:3`; XLSX values >3 error per-field).
  - **Name inputs are letters-only** (frontend-only Alpine sanitizer,
    allows spaces . - ' Ñ on First/Middle/Last).
  - **XLSX template v3** (`assessment-template-v3.xlsx`): 34 non-strict
    dropdowns (9 yesno + 25 single); vocabs whose inline formula exceeds
    Excel's 255-char cap (religion) fall back to a HIDDEN `Lists` sheet
    with range-based validation (`addDropdown()` in
    AssessmentTemplateController); guides updated (choose one / up to 3 /
    closed-list warnings). Parser unchanged (label matching).
  - **AssessmentSummaryService.countByArray now handles scalar-or-array**
    (single fields contribute 1 bucket; multi still iterate).
  - **Seeder remapped**: Live-in→Single, Born Again→Evangelical
    Christianity, High School Graduate→JHS Graduate, first-element flattens,
    'First aid / disaster response' trainings→valid options, problems
    routed to the right category via vocab intersects, 'Debt' now lands in
    economic (was invalid in family). Dev DB migrated + fresh-reseeded and
    spot-verified.
  - **Tests**: unit AssessmentTemplateTest rewritten for closed lists /
    caps; AssessmentImportTest updated (reason canonical-only, religion
    strict, single livelihood); AssessmentWizardOtherTest moved to
    animals_kept/water_source (position lost Other); NEW
    `AssessmentWizardConditionalTest` (14 tests: all 9 rules, exclusives,
    max-3, removed field, sanitizer, Divorced present/Live-in absent).
  - Prototype mirrored (assessment-form.html): civil status + religion
    selects, single-select semantics + renamed labels, Interested in
    Livelihood Training removed, Bottled water removed, problems "up to 3"
    hint, import-modal D9 demo updated for closed lists. Inline script
    re-checked OK; forbidden strings re-grepped clean.
- **SESSION 7 — CUSTOM DROPDOWNS + IMPORT PAGE REDESIGN (2026-09-15, later
  still)** — all verified, 131/131 tests green (538 assertions):
  - **NEW reusable component `resources/views/components/sc/select.blade.php`**
    — a styled single-select dropdown mirroring the multi-select design
    (collapsed input-style button, chevron, z-30 panel, optional search box,
    check-marked selected row, option-count footer). API:
    `<x-sc.select model="communityId" :value="$communityId" :options="$options"
    placeholder="…" :search="false" />` where $options is an assoc
    value=>label map (a plain 0-based string list maps value=label; search
    auto-shows when >12 options). Sync: `pick()` sets local state instantly
    + `$wire.set(model, rawValue)`; re-sync from server via the `$wire.$watch`
    bridge in `init()` (so wizard save-reset and import context-block fills
    update the label). All inner buttons carry `type="button"` (the wizard
    wraps everything in a `wire:submit` form!) and the search input has
    `@keydown.enter.prevent` (Enter would submit that form). Deployed in the
    wizard (Community · Quarter · Year · Civil Status · Religion — all
    native `<select>`s eliminated) and the import page (Community · Quarter
    · Year).
  - **Year is now a dropdown of the past 5 → next 5 years** (current year
    preselected in the wizard; placeholder in import where year starts
    empty) — was a raw number input (import) / number input (wizard).
    Quarter labels gained month hints ("Q1 · Jan–Mar").
  - **Import page fully redesigned**: header with "Back to encoding form"
    link (→ /assessments/create) + page title + Download-template button;
    two-step indicator (Upload → Review & Confirm, server-side @if on $step
    — never bare Alpine, §14); "Record context" block with the sc-selects;
    dashed-border dropzone file picker (hidden input in a <label>, shows the
    chosen filename + replace hint, red state on file errors); Parse button
    gained a wire:loading spinner state ("Reading file…"); preview step
    shows target-context badges (community · Q# year), gold-tinted D9
    auto-map panel, 2-column parsed-values grid, and "← Back to upload".
    Test-relevant copy preserved: 'Official template (.xlsx)', 'Parse
    file', 'Confirm & create record'.
  - Tests: +3 (import back link, import year range, wizard has no native
    `<select` + year range). Prototype intentionally NOT mirrored — the
    Laravel import page now leads (modal vs page); recorded as accepted
    drift like the communities list view.
- **SESSION 6 — VERTICAL ASSESSMENT TEMPLATE v2 (2026-09-15, later)** — all
  verified, 128/128 tests green (528 assertions); blueprint bumped to v4.8:
  - **Assessment import template redesigned as a VERTICAL form** (owner
    request; D11/§5.6 updated): `assessment-template-v2.xlsx` — column A
    carries clean field labels (hints like "(comma-separated)" moved to the
    guide), column B is the shaded answer cell, column C carries fill-up
    guides with the FULL §7 option lists (self-contained for field use).
    LNU-blue merged section separator rows (Import Context + Sections I–IX,
    `AssessmentTemplate::SECTION_BREAKS`), freeze pane, fit-to-width print.
    Single-choice / Yes/No / reason answer cells get NON-STRICT dropdowns
    (TYPE_LIST + `setShowErrorMessage(false)` so typed free text is accepted
    — D9 auto-map intact); multi fields get guides only (single-pick lists
    fight comma-separated answers). COLUMNS keys renamed `header` → `label`;
    `headers()` → `labels()`; new `guide($field)`.
  - **Parser matches labels, not header rows** (`Assessments\Import::parse()`):
    iterate rows, exact-trim match column A → field, value = column B
    (first occurrence wins). ≥20 label matches required else the "download
    the current template" error — **horizontal v1 files are NO LONGER
    ACCEPTED** (owner decision; the error says so). Section separators,
    guide column, and unknown label rows are ignored; a new "No answers
    found" error covers all-blank column B. normalize/D9/D10/preview/
    confirm paths unchanged.
  - **NEW GOTCHA (added to §14)**: PhpSpreadsheet's `showDropDown` property
    is INVERTED vs the OOXML attribute — the property must be TRUE for the
    in-cell arrow to render (writer emits `showDropDown="0"`); leaving the
    default false writes "1" which SUPPRESSES the arrow. Also: yesno fields
    carry no `vocab` key in COLUMNS (implicit Yes/No) — the controller
    match must special-case them or their dropdowns silently disappear.
  - Tests: AssessmentImportTest rebuilt (11) — vertical builder keyed by
    FIELD names (junk guides in column C + optional unknown-label row),
    new structural download test (served file re-parsed: all labels in A,
    10 separators, guides in C, exactly 19 non-strict dropdowns with
    arrows visible + multi fields excluded).
  - Prototype mirrored: assessment-form.html modal copy + toast filename
    (inline script re-checked OK, forbidden strings re-grepped clean).
- **SESSION 5 — ASSESSMENT XLSX IMPORT FIX (2026-09-15)** — all verified,
  126/126 tests green (413 assertions):
  - **Import page showed ONLY the info banner** (owner-reported: "when I click
    Import from template all I see is [the D9 helper text]"): the step toggle
    was bare Alpine `x-show="step === 'upload'"` / `x-show="step ===
    'preview'"`, but `step` is a LIVEWIRE property — not in Alpine scope —
    so both expressions silently evaluated falsy and hid BOTH steps (preview
    also had x-cloak). Livewire tests never caught it (they don't execute
    Alpine). Fixed with server-side `@if ($step === 'upload')` /
    `@if ($step === 'preview')` blocks in
    `resources/views/livewire/assessments/import.blade.php` (Blade
    conditionals are morph-safe). New render assertions added
    (upload form present initially, preview content only after parse). See
    the new §14 gotcha.
  - **Import dropped every text/number/reason value** (owner-reported as
    faculty): `AssessmentTemplate::normalize()` had no switch cases for the
    `text` / `number` / `reason` column types — they fell into the
    vocab-driven `default:` branch, so respondent First/Middle/Last Name,
    Age, and Reason Not Available all returned the on-screen error
    "Value … is not in the standard list" AND the value was silently dropped
    from the created record. Fixed with explicit cases: text = kept as-is
    (255-char cap), number = integer age 15–120 (wizard parity; anything
    else = per-field on-screen error per D9/D10), reason = free text,
    canonicalized when it matches the §7 reason list.
  - **Data-row detection required column A (Community) non-empty**: a
    faculty leaving the Community context cell blank (picking the community
    in the form instead) got "No data row found under the template headers"
    even with a fully filled row. Detection now accepts the first row with
    ANY recognized column non-empty.
  - **parse() validated community/year BEFORE reading the file**, so the
    template's D11 context header could never be the sole source of
    community/quarter/year. Now parse() runs `validateOnly('file')`, the
    context header fills the fields, and a post-parse guard keeps the user
    on the upload step (where the selects + errors live) when
    community/year are still missing. Community match is exact-first
    (case-insensitive), then ordered LIKE fallback.
  - New `tests/Feature\AssessmentImportTest` (8 tests) — first suite to
    exercise the assessment import flow end-to-end (context header, blank
    community cell, free-text reason, invalid age per-field drop, D9
    auto-map, bad headers rejected).
- **SESSION FIXES + POLISH (2026-09-14)** — all verified, 92/92 tests green:
  - **Encoding wizard "Other — specify" input** (pattern from the owner's prior
    v3 build): every vocab containing "Other" now renders a companion text input
    in the chip partial (multi AND single groups; yesno excluded) when Other is
    selected → `form.{field}_other` (nullable|string|max:500 rules auto-added) →
    merged into `other_text` JSON on save (only when Other is actually selected),
    same D9 convention as the XLSX import. Tests: `AssessmentWizardOtherTest` (4).
  - **Livewire `$toggle` is broken for arrays in v3.8.8** — client impl is
    `set(name, !get(name))` (ignores the value arg), so every array chip
    ($toggle('form.X', 'val')) coerced the property to a bool → next render
    threw `in_array(): bool given` and the page died. Replaced with server-side
    toggle methods: `Wizard::toggleOption(field, option)` (guarded to multi
    COLUMNS), `Programs\Index::toggleFormArray(key, value)` (guarded to
    community_ids/beneficiary_categories), `Programs\Hub::toggleActivityFaculty(id)`.
    See §14 — never reintroduce `$toggle` for array membership.
  - **Chip selected-state fix**: chips emitted a duplicate HTML `class`
    attribute (`class="chip"` + `@class(['on' => ...])`) — browsers keep the
    FIRST attribute, so the blue `.chip.on` state never rendered. Fixed in 7
    spots (assessment field partial ×2, wizard service-ratings chips, programs
    index ×2, hub-modals faculty + attendance chips) by merging into one
    `@class(['chip', 'on' => $cond])`. Functional clicks were fine all along.
  - **AI admin pages rebuilt to prototype conformance** (`ai-analysis.html`,
    `program-narratives.html`): /ai-analysis now has the ai-panel hero (model /
    no-PII / n / confidence chips), 3-col narrative + interventions + priority-
    needs workspace, sticky approve/discard action bar, compact selectable draft
    cards, history table with Model + Approving Officer columns + pipeline
    legend; /program-narratives now has the ai-panel hero, full-width cards with
    Executive Summary / Top Risks / Next Actions boxes, "Objectives met: a/b"
    chip (ProgramNarratives component eager-loads communities + programObjectives
    and exposes objectivesAchieved/Total), provenance line, and sc-acc version-
    history timeline accordion. No route/service behavior changed.
  - **Sidebar/topbar active-state fix**: old `routeIs($key.'*')` made faculty's
    `proposals*` key highlight BOTH "Submit Proposal" (proposals.create) and
    "My Proposals" (proposals.index). Now matches the item's route name exactly +
    route-family wildcard (`family.*`) ONLY when no sibling nav item shares that
    family; topbar page-label logic shares the rule and filters items via
    Route::has (config contains routes that don't exist for every role).
  - **Calendar day-panel fix**: the month-grid loop did `@php($dayEvents = ...)`,
    clobbering the view-passed `$dayEvents` (Blade @php shares view scope) — the
    last padded cell is always empty, so the side panel was permanently "Nothing
    scheduled". Grid-local variable renamed to `$cellEvents`.
- **SESSION 2 — DOWNLOADS, AI PAGE POLISH, COMMUNITIES & PARTNER SCHOOLS
  (2026-09-14, later)** — all verified, 103/103 tests green:
  - **Proposal attachments + Special Order are now downloadable** (owner request;
    was plain text — no links existed). Method chosen by owner: PUBLIC
    `storage:link` URLs + HTML `download` attribute (force-download; anyone with
    the exact URL can fetch — accepted trade-off, no auth controller). Changes:
    `php artisan storage:link` run (junction on Windows); proposals view —
    detail-modal attachments are clickable rows, NEW Special Order block in the
    detail modal, table "⬇ Attached" badge links directly; `Phase3Seeder` now
    WRITES real stub files to the public disk (`proposal-documents/demo/*`,
    `special-orders/demo/*`) via a `minimalPdf()` helper (valid one-page PDF with
    correct xref offsets) so seeded rows download real files — also fixed
    `file_type` hardcoded 'pdf' for an xlsx row. Old demo rows were backfilled
    without a DB reset; fresh seeds self-contained. Tests: attachment/SO link
    rendering (2 in Phase3WorkflowTest).
  - **AI admin pages visual redesign** (`ai-analysis.blade.php`,
    `program-narratives.blade.php` — pure Blade/markup, zero behavior change):
    picker card w/ icon + D3 helper text; NEW empty state w/ 3-step flow when no
    drafts; provenance kv rows → 3-stat strip; `wire:loading` disable on
    approve/discard; draft cards w/ icon-chip meta; overflow-x-auto + nowrap on
    history table. Narratives: live hero stat chips (X/Y generated · on track ·
    need attention); card header divider + code badge; pending state w/ skeleton
    shimmer; priority badges on next-actions; provenance footer as chips.
  - **Communities page converted to a LIST VIEW** (owner overrode the prototype
    card grid — Laravel now leads). sc-table with icon cell, contact columns,
    right-aligned counts, clickable rows. Edit modal raised to `z-[60]` — it
    opens FROM the detail modal and sat earlier in the DOM (equal z-50 = later
    element wins → hidden). See §14 gotcha.
  - **Communities → "Communities & Partner Schools"** (blueprint v4.5, §5.3/§6.3):
    `communities` table gains `type` (community|school, default community,
    indexed) + `school_level` (elementary|secondary|higher_ed, nullable);
    vocab in `config/smartcemes.php` (`community_types`, `school_levels`);
    `Community::isSchool()`. One CRUD/page; type chip filter (All/Communities/
    Partner Schools); school rows get gold doc icon + level label; school detail
    modal shows Level/Principal instead of beneficiaries/programs/needs-history;
    create/edit modal has Type selector, school_level required+validated when
    type=school, schools force-saved as active. Nav label updated (config) +
    prototype layout.js.
  - **Demo geography corrected** (owner caught it; verified vs PhilAtlas/PSA):
    Brgy. El Reposo & Brgy. Salvacion are TACLOBAN CITY barangays (B55/55-A,
    B104) — were misassigned to Palo/Tanauan by the v3-era prototype
    seed-data.js. Fixed in Phase2Seeder (municipality, emails, HANDA partner →
    Tacloban City DRRMO, venue → Tacloban City Convention Center, KABUHIAN
    partner → Tacloban City LGU, 2 beneficiary municipalities) + mirrored in
    prototype seed-data.js. Palo/Tanauan/Dulag still represented by new rows.
  - **Seed expansion — 58 registry records (46 communities + 12 schools)**, all
    PhilAtlas-verified, contacts from `prev/seeders/namelist.txt` (Kagawad/Capt.
    titles; Principal/Dr. for schools): 24 new Tacloban barangays (17 active,
    6 northern-periphery prospecting: Utap/Tagapuro/Palanog/New Kawayan/Old
    Kawayan/San Paglaum), 8 Palo (Guindapunan, Libertad, Pawing, Gacao, Baras,
    Naga-naga, Cangumbang, Candahug), 9 Tanauan (Pago, Canramos, San Roque,
    Cabuynan, Sacme, Bislig, Catmon, Malaguicay, Mohon), 12 partner schools
    (5 elementary incl. Anibong; Leyte/Sagkahan/San Jose/Marasbaras NHS +
    Sto. Niño SHS; EVSU + ADFC). Cross-municipality name collisions handled by
    suffixing: "Brgy. Libertad, Palo" and "Brgy. San Roque, Tanauan" (emails
    libertad-palo@ / sanroque-tanauan@).
  - **SequenceService portability fix**: raw MySQL `INSERT ... ON DUPLICATE KEY
    UPDATE` → query-builder `insertOrIgnore` (same race-safe semantics under
    the transaction + lockForUpdate). This unblocked `$this->seed()` in tests
    (tests run on sqlite :memory: — raw MySQL syntax threw a parse error).
    New test file `CommunitiesPartnerSchoolsTest` (9 tests) seeds via
    `$this->seed()` — the first tests in the suite to do so.
  - **PENDING (owner deferred, questions dismissed mid-plan): Tara Basa Tutoring
    Program seeder.** Researched + planned: DSWD model from
    `prev/seeders/ExtensionProgramSeeder.php` (college tutors ₱545/day, YDWs,
    20-day reading sessions for Grade 1–2 learners, Nanay-Tatay parent
    sessions), natural lead Bianca Oledan (Reading Education), EXT-2026-007,
    beneficiary categories must map to locked vocab (Student/Parent). Open
    decisions: timeline (completed summer cycle vs ongoing wave), budget scale
    (flagship ~₱1.5M vs demo-scale), communities, beneficiary volume. Partner
    schools now exist to link via `partners`/venues.
  - Prototype conformance drift (accepted): communities page is a list view in
    Laravel only; prototype still shows the card grid. Update
    `docs/prototype/pages/communities.html` when convenient (§11 rule 3).
- **SESSION 4 — BENEFICIARY XLSX IMPORT + CONTACT NUMBER (2026-09-14)** — all
  verified, 117/117 tests green (361 assertions):
  - **Beneficiary XLSX import** (owner request; blueprint v4.7 §5.4): new
    `App\Services\BeneficiaryTemplate` (fixed headers: First Name, Middle
    Name, Last Name, Age, Sex, Contact Number, Barangay, Municipality / City,
    Beneficiary Category; one row = one beneficiary, max 500) +
    `BeneficiaryTemplateController` serving the authored template
    (`beneficiary-import-template-v1.xlsx`, example row, Admin-only route
    `/beneficiaries/template`). Hub Beneficiaries tab gains an "Import XLSX"
    button + modal: upload → `parseImport()` preview (per-row
    Import/Duplicate/Skip status, per-field error list, "kept as Other"
    notes) → `confirmImport()` creates + auto-enrolls. Blank contact numbers
    default to 09123456789; unknown categories auto-map to "Other" (D9);
    duplicate rows (first+last+barangay, in-file AND vs registry) and
    error rows are skipped per row — never a whole-file reject (D10 confirm
    flow). Admin-only (`abortUnlessManage`), activity-log entry on success.
  - **Contact number feature** (owner request "just put 09123456789 on
    everyone"): the `beneficiaries.phone` column (6.6, was never surfaced)
    is now "Contact number" — surfaced in the hub beneficiaries table (new
    Contact column), register-new modal, XLSX template + import; blank
    defaults to 09123456789 (`BeneficiaryTemplate::DEFAULT_CONTACT_NUMBER`).
    Migration `2026_09_14_000200_backfill_beneficiary_contact_numbers`
    backfilled ALL existing rows (dev DB verified 12/12); Phase2Seeder +
    BeneficiaryFactory seed it; config gains `beneficiary_genders`.
  - **NEW GOTCHA (added to §14)**: Livewire test uploads (and real uploads
    on Windows) store files under `storage/app/private/livewire-tmp/` with
    metadata-encoded filenames that can exceed Windows MAX_PATH (260) —
    PHP streams read them fine but PhpSpreadsheet's ZipArchive (C lib)
    returns error 5 and IOFactory throws "Unable to identify a reader".
    FIXED in BOTH `Assessments\Import::parse()` and `Programs\Hub::
    parseImport()` by copying to a short `tempnam()` path before loading.
  - Prototype mirrored: program-detail.html (Import XLSX button + demo
    modal with sample preview rows, Contact column, contact field in
    register modal, seed-data.js `contact` default). Blueprint bumped to
    v4.7 (§5.4, §6.6 note, revision history). node --check re-run OK.
- **SESSION 3 — AI PAGE UX, DERIVED CONFIDENCE, MULTI-SELECT (2026-09-14, later
  still)** — all verified, 107/107 tests green (329 assertions):
  - **AI analysis Generate button was permanently disabled**: the summary select
    used deferred `wire:model="summaryId"` — no request fired on change, so the
    DOM never re-rendered and the stale `pointer-events-none` class blocked the
    very click that would have synced the model. Fixed with
    `wire:model.live="summaryId"` (resources/views/livewire/ai-analysis.blade.php).
    Gotcha: deferred `wire:model` is only safe when some OTHER action will sync
    the value before a clickability-dependent button is used.
  - **Generate flow now has loading + honest outcome notices**: the button
    disables and swaps sparkles → spinning `loader` icon (NEW icon in
    `x-sc.icon` — Heroicons arrow-path) with "Generating…" label, scoped via
    `wire:target="generate"`; a live status strip shows under the picker during
    the synchronous Gemini call; Retry disables during its request too.
    `AiAnalysis::generate()` now refreshes the returned analysis and toasts
    success ONLY when status=completed; on failure it surfaces the persisted
    "Analysis unavailable — …" error_message (D12) instead of a false success.
  - **Community response data accordion** on the analysis workspace
    (ai-analysis.blade.php): renders from `$hero->raw_extracted_data` — the
    EXACT aggregates the job sent to Gemini (D3 audit view). Native `<details>`
    `sc-acc` panel with: Key indicators strip (Has electricity, Available for
    training — each as % + "n of N respondents" — and Avg service satisfaction
    /5; owner removed "Member of an organization" from the strip, though the
    data remains in the snapshot; grid is 3 cols) + distribution lists grouped
    by Respondent profile / Household & utilities / Priority problems reported
    / Interests & training, each value with count, %, and a mini-bar, sorted
    desc. Hidden entirely when raw_extracted_data is empty (failed generations).
  - **confidence_score was always NULL → blueprint v4.6**: both jobs previously
    hardcoded `'confidence_score' => null` (Gemini reports no confidence
    indicator), so every UI showed "Confidence —". NEW shared helper
    `App\Services\Ai\ConfidenceScore` derives a deterministic DATA-confidence:
    forAnalysis = 50% sample size (n/25 saturation) + 30% aggregate
    completeness + 20% output completeness (summary/problems/recommendations);
    forNarrative = 50% KPI coverage (7 keys) + 25% program richness
    (objectives>0 + activities>0) + 25% output completeness
    (summary/health_label/risks/recommendations); clamped [0.10, 0.95] (a
    derived score never claims certainty), rounded 2dp; basis string recorded
    in `metadata.confidence_basis`; tooltips on the hero chip + Provenance
    stat expose it. Guidance-only semantics per §6.11/§6.16 (blueprint updated
    to v4.6 with revision-history entry). Pre-existing rows keep NULL —
    regenerate (or Retry) to backfill.
  - **Linked communities / Beneficiary categories chip walls → searchable
    multi-select dropdowns** (58 communities made the program modals unusable):
    NEW reusable component
    `resources/views/components/sc/multi-select.blade.php` — collapsed field
    with selected names + count badge, dropdown w/ search, scrollable checkbox
    rows, "N selected" footer. Deployed in Hub program-edit modal AND Index
    New Program modal for BOTH fields (community labels carry a `· School`
    marker for partner schools; category ids are the vocab strings).
  - **Multi-select latency fix (optimistic UI)**: first version round-tripped
    every click (slow highlight on the big Hub component). Component now keeps
    a local Alpine `selected` array — clicks toggle highlight/check/count/
    summary INSTANTLY while `$wire.call(method, key, id)` syncs server-side in
    the background. `openProgramEdit()` dispatches `ms-sync-community_ids` /
    `ms-sync-beneficiary_categories` window events and `resetForm()` dispatches
    both with `[]` so reopening/clearing re-seeds local state (no desync).
    Server-side toggle methods (`toggleEditArray` / `toggleFormArray`) are
    unchanged and remain the source of truth for validation/sync.
  - **NEW GOTCHA (added to §14)**: Alpine `:class` on a Blade COMPONENT tag
    (e.g. `<x-sc.icon :class="open ? …">`) is compiled by Blade as a PHP
    attribute binding → "Undefined constant open". Must write `x-bind:class`
    on component tags; `:class` is fine on plain HTML elements.
- **Design target: DONE.** `SYSTEM_BLUEPRINT_V4.txt` (v4.14 — dashboard + hub
  redesign, with the v4.13 activity-import sections and revision entry
  inserted BEFORE v4.14) is the authoritative target design; Phases 1–5 are
  implemented.
- **UI reference: DONE.** `docs/prototype/` is a complete, clickable static HTML
  prototype (now 29 pages) brought to full blueprint conformance. All inline
  scripts syntax-verified (29/29 OK). It is the visual source of truth for every
  screen, flow, state, and interaction — replicate its look and behavior in Blade/Livewire.
- **`prev/` folder**: legacy v3 build reference. Its seeders (`prev/seeders/`) contain
  reusable realistic data (Tara Basa!, PURPPLE, Leyte communities, namelists) that
  MUST be remapped before reuse (see §5). `prev/prototype/` is v3 history — do NOT
  copy its structure (it has forbidden standalone pages and secretary AI access).

## 2. AUTHORITATIVE DOCUMENTS (read in this order)

1. **`revisions.md`** — **READ THIS SECOND (after the box at the top).** The post-defence adviser review
   plan, phases `R1`–`R7`. §10 is the **status tracker**; §5 is the phase spec; §12–§18 are the
   per-phase write-ups. This file records **every change made after Phases 1–5**, including the
   hierarchy restructure, the amended training-hours formula, the target model, the 8.6 removals and the
   AI guardrail. **Where this handoff and `revisions.md` disagree, `revisions.md` wins.**
   ⚠️ Its §8 lists the blueprint sections that are stale.
2. `SYSTEM_BLUEPRINT_V4.txt` — the design contract (v4.20). Where anything conflicts, the blueprint
   wins — *except* where `revisions.md` records a later owner amendment, which supersedes it.
   - §2 users/roles/workflows · §5 features · §6 data models
   - §7 controlled vocabularies (needs-assessment form, Sections I–IX)
   - §8.6 KPI dictionary — **superseded by D-R7 for project/performance surfaces; retained in code
     unread.** See §6 below before using it.
   - §8.8 scheduling hard constraints · §11 phases · §14 design decisions D1–D13 · §18 revision history
3. `docs/prototype/PATTERNS.md` — design-system rules (tokens, components, variants).
4. `docs/prototype/assets/` — `smartcemes.css` (component classes), `tw-config.js`
   (lnu blue #003599 / gold #F6B800 / Figtree), `seed-data.js` (demo data), `layout.js` (nav).
5. `prev/NEEDS ASSESSMENT_FORM.md` — source instrument behind §7 vocabularies.
6. `docs/F-CES-002..005` — official university form scans (context reference only).

**Prototype harnesses — run these after ANY `docs/prototype/` edit.** They are the prototype's own
test suite and they catch drift the Laravel suite cannot see:

```bash
node docs/prototype/_check.cjs          # structural: seed, nav contract, icons, deep links
node docs/prototype/_smoke.cjs          # every page's script executes
node docs/prototype/_hubtest.cjs        # 42  — the colleges hub click-through
node docs/prototype/_facultytest.cjs    # 124 — faculty board/directory/drawer
node docs/prototype/_dashtest.cjs       # 41  — admin dashboard
node docs/prototype/_interagencytest.cjs # 44 — R6 interagency catalogue
```

Note `_check.cjs` enforces a **nav contract**: the admin sidebar must contain exactly ONE
extension-structure entry (`Manage Extension Programs`) and must **not** contain separate
`Colleges` / `Extension Programs` / `Extension Projects` items. See §14 for why this matters.

## 3. LOCKED DECISIONS (do not reopen)

### 3.0 Revision decisions (2026-09-24) — these SUPERSEDE the older entries below where they conflict

Full rationale in `revisions.md`. The short form:

- **Hierarchy is `College → Program → Project → Activity`.** Program = one of the **6 verbatim CESO
  thrusts** (broad, university-wide, carries **no** college FK and **no** target). Project = the
  narrow, activity-bearing, target-bearing level that used to be called "program". See the table at the
  top of this file.
- **Training hours = `trainors × trainees × days` — NO `× 8`** (§2.2A). `days` is
  `activities.no_of_days`, a `decimal(4,1)` so a half day (`0.5`) survives. One implementation:
  `TrainingHoursService`. Never re-derive the formula in a view, a Livewire component, or another
  service — the project hub and the faculty pages must agree, and there is a test that proves they do.
- **Budget has NO annual target — the ALLOCATION is the basis** (owner decision 2026-09-26, v4.19).
  A project has ONE budget figure, `allocated_budget`, so utilization is measured against it on every
  surface. `ExtensionProject::budgetAllocated()` is the single accessor and `isOverAllocated()` uses it;
  `TrainingHoursService::forProject()` returns the key **`allocated_budget`** (it used to be
  `target_budget`). The per-project `annual_target_budget` column is **RETAINED BUT UNREAD** — the same
  treatment D-R7 gives the 8.6 KPIs — the project form no longer collects one, and `R4TargetsSeeder`
  no longer writes one. Training HOURS keep a real annual target at project level
  (`annual_target_hours`) with the university pool above them, and the **university budget pool is
  unaffected**. No budget surface may say "Annual Target" again — the strings to grep for are
  `budgetTarget` (Laravel + prototype) and `budgetVsTarget` (the hub payload, now `budgetVsAllocation`).
- **Trainee resolution order (R-Q1):** `attendance (present|late) → participants (manual) → 0`, and the
  figure always carries a **source tag** (`attendance` / `manual` / `none`) the Director can see.
  There is deliberately **no** fallback to enrolled beneficiaries — enrollment is project-level, so
  falling back would inflate hours.
- **Targets live at University + Project level ONLY** (D-R5). Broad programs and colleges carry none.
  The university target is a **consumption pool**, not a ratio: `remaining = max(target − Σ actuals, 0.0)`.
  Project targets are **never summed** into the pool (that is the double-counting bug D-R5 forbids).
- **NULL over 0, everywhere.** No denominator / not measurable → `null`, and the UI says "not yet
  measurable" rather than printing a fabricated `0`. A zero is a *claim*; absence of data is not.
- **All 8.6 KPI surfaces removed** from project/performance views (D-R7). `KpiService`,
  `ProgramObjective`, the `programObjectives` relation and `smartcemes.kpi_metrics` are **retained in
  code but unread** (R-Q2) so historical rows stay inspectable and migrations stay reversible. Do not
  add new readers.
- **AI is a three-tier guardrail** (§7.1 below). Citable agencies come **only** from the
  `interagency_agencies` catalogue, resolved by `agency_code`. A code that does not resolve is dropped
  and **counted**, never laundered.
- **The KAHAYAG brand is NOT referenced in the UI** — CESO's official agenda name is documented, but
  the product does not surface it.
- **No model declares `protected $table` by convention** — table names derive from the class. The
  deliberate exception is `Program` (`protected $table = 'programs'`).
- **Controlled vocabularies live in `config/smartcemes.php`.** Never hardcode option lists in views.
- **Nav is config-driven** and items whose route does not exist are **silently hidden** — which is how
  a broken entry can go unnoticed. `tests/Feature/RouteSurfaceTest.php` now asserts every nav item
  resolves, so this cannot regress silently.
- **The Admin Analytics page is REMOVED** (v4.20, owner decision 2026-09-27). `/analytics` no longer
  exists; cross-project aggregates live on the admin dashboard and in the print reports, and the
  `community_reach` measure retired with it. `revisions.md` §21 is the record.

### 3.1 Original decisions (Phases 1–5) — still in force unless 3.0 above overrides

- **Blueprint wins** over prototypes and over anything in `prev/`.
- **Roles**: exactly three — admin (Director persona), secretary, faculty — single
  `role` column + middleware. No permission packages. No multi-role accounts.
  Separation of duties: no role may perform another's approvals.
- **AI (D4, D12 / blueprint v4.2)**: Admin-only (generate/view/approve). Secretary
  & faculty have NO AI access. ONE pipeline, TWO output types: AssessmentAnalysis
  (community insights, approval gate) + ProgramNarrative (executive summary, no
  gate). Aggregation-before-send (D3) — aggregates only, never PII. **LIVE Google
  Gemini API (flash class, free tier) only — no mock/demo mode**; first-class
  failure states ("analysis unavailable" / "narrative unavailable") are the only
  fallback. Provenance (model, prompt version, generated_at/by) always recorded.
- **XLSX template (D11)**: we AUTHOR it as the first Phase 2 deliverable — fixed
  headers covering Sections I–IX + context header (community, quarter, year, optional
  proposal ref). One file = one respondent. Import: parse → preview modal with parsed
  values + per-field error list → uploader confirms → record created as `pending`.
  Unknown/misspelled values auto-map to "Other" with raw text kept in `other_text`.
  Never whole-file reject; errors shown on screen only, never persisted.
  Source file archived to `needs_assessments.file_path` (audit only, never machine-read).
- **Activity scores (D13)**: single aggregate columns on `activities`
  (`pre_assessment_score`, `post_assessment_score`, `satisfaction_rating`).
  NO separate evaluation-submissions table in MVP. v4.13: populated by the
  per-activity evaluation XLSX import as per-metric means (per-metric merge —
  a metric with no values in the file keeps its existing aggregate).
- **Reports**: browser-print views in MVP; PDF/Excel export is future work (D6).
- **Budget (D7)**: over-allocation never hard-blocks — save succeeds, persistent
  warning badge, activity-log entry.
- **Audit (D8)**: Spatie Activity Log REQUIRED on approvals, rejections, deletions,
  account changes, status transitions.
- **Employee IDs (5.1)**: `LNU-{year}-{4-digit seq}`, race-safe generation
  (sequence table or atomic increment — never read-max-then-write).
- **Rendered hours (8.9)**: auto-draft on activity completion (hours = end−start,
  source=auto); faculty may adjust DOWN only with a note; admin approves; approved
  entries LOCKED/immutable; unique per (faculty, activity). Overnight/invalid
  schedule (end ≤ start) → NO auto-draft; faculty records manually.
- **Scheduling (8.8) hard constraints**: activity dates MUST fall within parent
  program range (UI constrains + server validation); faculty assignment REFUSED on
  schedule overlap; availability accept REFUSED on overlap with accepted requests
  or assigned activities.
- **Quarters (D1)**: calendar quarters, integer 1–4 (never "Q1" strings).
- **UI policy**: `docs/prototype` (v4) is the primary reference — floor not ceiling;
  port tokens to a proper Vite/Tailwind build (Play CDN is dev-only); improve where
  the blueprint demands. `prev/prototype` must not drive structure.

## 4. TECH STACK (locked, Section 9)

Laravel 11+ / PHP 8.2+ · MySQL or MariaDB · Breeze (Blade stack) · Livewire 3 +
Blade · Tailwind CSS (Vite build) + Alpine.js · Chart.js (bundled via npm, no CDN)
· custom month-grid calendar (no external calendar lib) · maatwebsite/excel
(PhpSpreadsheet) for the XLSX import · Spatie Activity Log · Google Gemini API
flash-class model (free tier; GEMINI_API_KEY / GEMINI_MODEL in .env — v4.2
supersedes the earlier OpenAI spec) via QUEUED jobs (database queue driver is fine
for MVP) · local public disk storage for uploads (max 10 MB; pdf/jpg/png/docx/xlsx;
MIME + extension validated; dated folders) · Laravel database notifications →
header bell · nightly DB backups (deployment item).

## 5. DATA MODEL (Section 6 — as built, after the R1–R6 restructure)

**Hierarchy tables (the R1–R2 additions — read the box at the top first):**

| Table | Class | Notes |
|---|---|---|
| `colleges` | `College` | **4 rows: CAS / COE / CME / GRAD** (the Graduate School). Carries a coordinator FK. **No target.** The set is **FIXED** — the UI has no college CRUD (removed 2026-09-25) and `CollegeSeeder` is its only owner. |
| `programs` | `Program` | The **6 verbatim CESO thrusts** (broad level). No `college_id` — a thrust spans colleges. `nextCode()` → `PROG-{year}-{seq}`. |
| `extension_projects` | `ExtensionProject` | The narrow level (was `extension_programs`/`ExtensionProgram` pre-R2). Has `college_id`, `program_id`, `annual_target_hours` and `allocated_budget` — **the allocation is the budget basis** (v4.19). `annual_target_budget` is **retained but unread**. Legacy codes `EXT-2026-00X` kept; new ones are college-prefixed (`CAS-2026-001`). |
| `faculty_expertise` | `FacultyExpertise` | `faculty_id, area, category`, unique per (faculty, area). A table, not JSON, because the module filters and counts **by area**. |

**Core entities (Phases 1–5):** `users`(6.1) · `faculties`(6.2 — note the plural; **not** `faculty`) ·
`communities`(6.3, `status` active|prospecting; v4.5 adds `type` community|school + nullable
`school_level`, so the table doubles as the partner-schools registry) · `activities`(6.5, now carries
`no_of_days` decimal(4,1), `participants`, `trainors_snapshot` from R4) · `beneficiaries`(6.6) ·
`attendances`(6.7) · `availability_requests`(6.8, admin-initiated) · `needs_assessments`(6.9, ~60 JSON
columns per §7) · `assessment_summaries`(6.10, UNIQUE (community_id, quarter, year), recomputed on
submission/review change) · `assessment_analyses`(6.11, draft→approved|discarded; R6 adds
`interagency_referrals` JSON) · `budget_utilizations`(6.12) · `activity_proposals`(6.13, status machine
+ auto-create Activity on approval) · `proposal_documents`(6.14) · `program_narratives`(6.16) ·
`rendered_hours`(6.17, UNIQUE (faculty_id, activity_id)) · `activity_imports`(6.19, v4.13).

**R4/R6 additions:** `university_targets`(4.7 — **ONE pool per AY**, the sole input to the annual
target) · `interagency_agencies`(4.6 — `agency_code` unique, `agency_name`, `mandate`,
`need_category`, `sample_service`, `contact_info`, `active`, `sort_order`, soft deletes; **no** `moa`,
`pillar` or `abbr` columns, which were prototype-only).

**Retained but UNREAD (R-Q2 / D-R7) — do not add readers:** `program_objectives`(6.15) and the
`smartcemes.kpi_metrics` config. Historical rows stay inspectable; migrations stay reversible.

**Pivots:** `extension_project_beneficiary`, `community_extension_project`, `activity_faculty`.

Conventions: DECIMAL(12,2) money; JSON fields cast to array (arrays of strings); timestamps + SOFT
DELETES everywhere; enum-like columns are strings validated against §6.18/§7 server-side. All 37 tables
live in the DB, of which ~9 are Laravel infrastructure (`cache`, `jobs`, `sessions`, …).

## 6. KPI DICTIONARY (8.6) — ⚠️ RETAINED IN CODE, REMOVED FROM THE UI (D-R7)

**Read this before using anything in this section.** The 8.6 KPI dictionary is **no longer surfaced**.
R4 removed every 8.6 KPI tile from the project hub, dashboards and reports (decision **D-R7**), because
the adviser's review replaced the results-framework model with the **target model** (§2.2B / D-R5):

- **What replaced it:** trainors / trainees / training hours / budget, measured against the university
  pool and each project's annual targets. `TrainingHoursService` is the source; the project hub and the
  University Targets page are the surfaces.
- **What remains:** `KpiService`, `ProgramObjective`, the `programObjectives` relation, the
  `smartcemes.kpi_metrics` config and the `program_objectives` table are all **retained but unread**
  (R-Q2) — so historical rows stay inspectable and the migrations stay reversible. **Do not add new
  readers.** The objective CRUD block is annotated soft-deprecated in `hub-modals.blade.php` and pinned
  by `test_objective_crud_methods_remain_callable_for_compatibility`.
- **Deliberately retained readers:** `Programs\Hub::currentComputedKpi()` still calls
  `KpiService::liveKpi()` so the compatibility test passes. It is not reachable from the UI.

For historical reference only, the locked keys were: `participation_rate`,
`activity_completion_rate`, `attendance_consistency`, `budget_utilization`, `knowledge_gain`,
`cost_per_beneficiary`, `community_reach` (rendered hours is a faculty-level KPI, not in this
vocabulary). Objective status derivation was: achieved (actual ≥ target) · not_met (target_date passed,
actual < target) · on_track (progressing, date not passed) · not_started.

**Never inline KPI or training-hours maths in a view** — it goes through a service. That rule survives
the change; only *which* service changed.

## 7. PHASES — STATUS: Phases 1–5 COMPLETE · revision P0 + R1–R6 COMPLETE · R7 IN PROGRESS

**There are two phase series, and they are different things.** Phases 1–5 are the original build
(blueprint §11). `R1`–`R7` are the **post-defence adviser revision**, tracked in `revisions.md`. Do not
confuse `Phase 6` (the original plan's hardening phase) with `R7` — **R7 absorbed Phase 6's remaining
work**, and the "Phase 6" label is effectively retired.

### 7.1 Original build — all BUILT, do not restart

- **Phase 1 — Foundation: COMPLETE.** Laravel 12 at repo root, MySQL `smartcemes_v4`, Breeze (no
  self-registration), roles + `EnsureRole` middleware, race-safe IDs, six accounts seeded (the
  Graduate School pair arrived later — the blueprint's §2.4 ships **eight** accounts today).
- **Phase 2 — Core Data Modules: COMPLETE.** Design system ported to Vite/Tailwind; §6 entities +
  pivots; `config/smartcemes.php` vocabularies; Livewire CRUD; Sections I–IX wizard; authored official
  XLSX template (D11) + import flow (D9/D10); `Phase2Seeder`.
- **Phase 3 — Workflows: COMPLETE.** Proposals (Special Order, auto-create Activity, §8.8 range
  hard-block), availability (admin-initiated, required decline reason, accept hard-block), secretary
  validation, rendered-hours lifecycle (auto-draft, adjust-down-only, locked), database notifications +
  `smartcemes:notify-deadlines` scheduler w/ 7-day dedup; `Phase3Seeder`.
- **Phase 4 — Dashboards/Reports: COMPLETE.** Three role dashboards, custom calendar, four
  print reports w/ letterhead. *(Note: the 8.6 KPI content of these was later replaced — see R4 — and
  the Analytics page itself was REMOVED in v4.20, `revisions.md` §21.)*
- **Phase 5 — AI: COMPLETE.** Gemini pipeline (see §1.1). *(Later guardrailed — see R6.)*
- **Phase 6 — Hardening: RETIRED / ABSORBED INTO R7.** The feature tests it listed were written
  (`Phase3WorkflowTest` covers the approval workflows). Production deploy and the nightly backup script
  remain **outstanding deployment items** — see §15.

### 7.2 Adviser revision — R1–R6 complete, R7 in progress

Read `revisions.md` §10 for the authoritative tracker; §12–§18 hold the write-ups. Summary:

| Phase | What it did | Status |
|---|---|---|
| **P0** | Prototype pass — the whole `docs/prototype/` layer rewritten to the revised model | ✅ §11 |
| **R1** | `colleges` + broad `programs` level (CRUD, seeders, policies) | ✅ §12 |
| **R2** | The big rename: `extension_programs` → `extension_projects` (+ 7 FK columns, 2 pivots, college-prefixed codes) | ✅ §13 |
| **R3a/R3b** | Faculty `college_id` + `status`, `faculty_expertise`, the Faculty Management module (board + directory + profile) | ✅ §14 |
| **R3c** | Finished the two faculty-profile training blocks (training contribution + performance trend) | ✅ §18 |
| **R4** | Training-hours model (`trainors × trainees × days`, no `× 8`), `university_targets`, 8.6 KPI removal (D-R7) | ✅ §15 |
| **R5** | Filters, rankings, dashboards, reports rebuilt on the target model | ✅ §16 |
| **R6** | AI three-tier guardrail + the `interagency_agencies` catalogue + admin CRUD | ✅ §17 |
| **R7** | Hardening & docs — blueprint v4.20, this handoff, test sweep, walkthrough | 🔨 **IN PROGRESS** |

### 7.3 What R7 has left

**One item left, non-code: the production deploy** (absorbed from the retired "Phase 6").

Everything else is done: the blueprint (now **v4.20**), this file, the full sweep + `pint` + a
from-scratch re-seed (**491 / 2894**), the defence walkthrough (§15.2), the nightly backup (§15.1),
the **`docs/guides/*` refresh** (verified 2026-09-25 by reading them), the college seals
(`revisions.md` §19.9), the project→college mapping rule (§19.10), the Graduate School + the read-only
college set (§19.11), and the dashboard pass (§19.12).

**Nothing feature-shaped is outstanding.** Everything a user can click exists and is smoke-verified.

## 8. PROTOTYPE MAP (`docs/prototype/pages/` — 29 pages, the Laravel visual contract)

The prototype is the **review surface**: the frontend is validated there before it is ported. The owner's
standing directive is that **Laravel output must look like the prototype**. Run the harnesses (§2) after
any prototype edit.

**The revision pages (new/renamed by P0 — these are the ones a new session is most likely to get wrong):**

| Prototype page | Laravel route | Notes |
|---|---|---|
| `colleges.html` | `/colleges` | **The hub** — header reads "Manage Extension Programs"; 4 college cards, click one to swap in its projects. |
| `programs.html` | `/programs` | The **broad** level (6 CESO thrusts). |
| `projects.html` | `/projects` | The project list (what used to be called "programs"). |
| `program-detail.html` | `/projects/{project}` | The project performance hub. Tabs Overview / Activities / Beneficiaries / Budget. |
| `my-programs.html` | `/my-projects` | Faculty's own projects. |
| `faculty-management.html` | `/faculty` | The Engagement board. |
| `faculty-directory.html` | `/faculty/directory` | The roster. |
| `targets.html` | `/targets` | University pool + project attainment (R4). |
| `interagency.html` | `/interagency` | Interagency catalogue CRUD (R6). |
| `audit-logs.html` | `/audit-logs` | The audit trail — moved off the dashboard 2026-09-25 (`revisions.md` §19.12). A plain paginated list. |

**⚠️ There is NO prototype page for the faculty profile.** The prototype renders faculty detail into a JS
drawer (`#facDrawer` → `#facDrawerBody`) and never shows a per-person page, so `/faculty/{faculty}` has
**no visual contract** — it is a native build, recorded as a deliberate divergence in `revisions.md`
§18.4. Do not go looking for `faculty-profile.html`; it does not exist.

The prototype is *not* silent about the surrounding behaviour, though, and that matters: its faculty nav
carries the **"My Profile" section** with the `My Faculty Profile` item (which `/my-profile` mirrors), and
its self-edit drawer (`faculty-directory.html?role=faculty`) defines the writable-vs-locked split that
Laravel failed to implement until 2026-09-27 (`revisions.md` §22.1). Check it before changing the
self-edit boundary.

**⚠️ The prototype TRAILS Laravel on `/colleges`.** `colleges.html` still renders the old TWO-view hub
(college → projects) with its "View all programs" link and its "Open projects" CTA. Laravel has been a
three-view drill-down since 2026-09-27 (`revisions.md` §23), and that divergence is **accepted, not
pending** — the owner chose Laravel-only. So `_check.cjs:224-307` and `_hubtest.cjs` (42 assertions)
asserting the hub are asserting the *prototype's* older shape; a green harness run does **not** mean the
hub matches the app. Reconciling means moving the markup, both harness blocks and `PATTERNS.md` together.

**Unchanged from Phases 1–5:** `login.html` · `dashboard-admin|secretary|faculty.html` ·
`communities.html` · `proposals.html` (+`proposal-new.html`) · `availability.html` ·
`rendered-hours.html` · `assessment-form.html` · `assessment-review.html` · `calendar.html` ·
`reports.html` · `ai-analysis.html` · `program-narratives.html` · `beneficiaries.html` ·
Redirect stubs: `activities.html`, `budget.html` → `projects.html` (both pointed at `analytics.html`
until it was deleted in v4.20).

**⚠️ Two prototype-side stale artefacts found during the R7 audit (Laravel is correct in both cases):**
- **`compliance.html` ("Program Compliance Matrix")** is still present and is still linked from
  `index.html` and from the secretary dashboard's "Compliance Snapshot" card — but the blueprint's
  **v4.12 entry records the Compliance Tracker as a *"phantom never-built"* config entry that was
  deliberately removed**. The page and both links are stale leftovers. Laravel correctly has no
  `/compliance` route.
- `_check.cjs` enforces a **collapsed nav** (ONE `Manage Extension Programs` entry, not three) — but the
  Laravel admin nav currently has three separate items. See §14.

Demo conventions: `?role=` switches the role shell (layout.js NAV); `?program=` deep-links the hub;
in-page demo data that the seed lacks is hard-coded per page and marked as demo.

## 9. DESIGN SYSTEM (port to Vite/Tailwind)

- Colors: lnu-50…950 scale anchored on #003599; gold-50…900 anchored on #F6B800;
  charcoal #1f2937; white cards on gray-50; NO dark mode.
- Font: Figtree (woff2 files ship in prototype assets).
- Component classes to recreate as Blade components/Livewire markup: `sc-card`,
  `btn` variants, `badge` variants, `sc-table`, `chip`, `progress`, `tab`,
  `step-dot/step-line`, `ai-panel/ai-chip`, `avatar(.gold)`, `conflict-marker`,
  `narrative-health`, `sc-acc`, `kv`, `sc-toast`, `pulse-dot`, `skeleton`,
  `conflict-marker`. Status color map: Ongoing/Approved/Validated/Active/Completed→green;
  Pending/Draft→yellow; Rejected/Returned/Cancelled/Failed→red; Upcoming/info→blue;
  Prospecting/neutral→gray; Special→gold. Chart palette:
  ['#003599','#F6B800','#2547eb','#93b4fd','#fdd24a'].

## 10. VERIFICATION STATUS

### 10.1 Laravel (the deliverable)

| Check | Status |
|---|---|
| `php artisan test` | **491 passing / 2894 assertions**, 0 failures |
| `vendor/bin/pint` | Clean on all R1–R7-authored files. Repo-wide it still reports ~16 pre-existing issues, almost all `line_ending` (CRLF) noise plus a few operator-spacing nits in files the revision did not own. |
| `npm run build` | ✓ 62 modules; CSS ≈ 104.34 kB |
| `migrate:fresh --seed` | Clean on both SQLite and MariaDB 10.4 |
| Route smoke (all 3 roles, real MySQL driver) | **No 5xx on any surface**; guest routes redirect (302) |
| Nav integrity | **Zero phantom entries** — every nav item resolves to a real route (`RouteSurfaceTest`) |

**Automated guards worth knowing about** (they exist because these classes of bug actually happened):
`RouteSurfaceTest` (every nav item resolves; every nav + unlinked surface renders without a 5xx; the
import templates download) · `FreshSeedHierarchyTest` (a fresh seed converges on a complete hierarchy) ·
`ExtensionProjectRenameTest` (the R2 migration carries rows and children across, and `down()` restores
them) · `R6GuardrailTest` (the AI tier rules) · `FacultyModuleTest` (includes the training-hours
attribution and cross-view agreement checks).

### 10.2 Prototype (the visual contract)

- All **six** harnesses pass — `_check` · `_smoke` · `_hubtest` (42) · `_facultytest` (124) ·
  `_dashtest` (41) · `_interagencytest` (44). See §2 for the commands.
- Zero AI strings on secretary surfaces; AI pages are admin-only.
- **The 8.6 KPI vocabulary is no longer asserted as live** — it was removed from the UI (D-R7). The
  prototype's `programs.html` still *renders* per-program targets, which **contradicts D-R5**; that page
  was never updated when `colleges.html` was. Laravel is correct on this point. See §16.
- Budget entries sum exactly to project utilized (HANDA deliberately over-allocated by ₱2,000 to demo D7).
- **KNOWN DRIFT (accepted):** `communities.html` still shows the card grid; the Laravel page is a list
  view with a partner-schools type filter.

## 11. OPERATING RULES FOR THE NEXT AGENT

1. **`revisions.md` outranks this file** for anything after Phases 1–5. The blueprint is the design
   contract, but the revision records the owner's later amendments.
2. The blueprint is the contract; when UI and blueprint disagree, the blueprint wins — *then* update the
   prototype page to match.
3. Never invent data that contradicts `seed-data.js` or §7 vocabularies.
4. Every change to vocabularies, models, or workflows must be reflected in **both** the blueprint (bump
   the revision history) and the prototype.
5. Security posture: role middleware on every route/Livewire action; policies on all controllers; upload
   validation (10 MB, MIME+extension); login throttling; Spatie activity log on sensitive actions; DPA —
   aggregates only to the LLM; **Admin-only AI**.
6. Do not add features listed in blueprint §1.2 (out of scope): OCR, extra XLSX imports, partner portal,
   PDF/Excel export, predictive analytics, dark mode, ERP/HR integration, mobile, multi-role accounts.
7. **Never inline training-hours or KPI maths in a view.** Route it through a service
   (`TrainingHoursService` for training delivery, `FacultyContributionService` for faculty contribution,
   `RankingService` for rankings).
8. **After any prototype edit**, re-run the six harnesses in §2 — not just `node --check`.
9. **After any schema change**, `php artisan migrate:fresh --seed`, then `php artisan migrate:status` to
   confirm nothing is pending. The local MariaDB does **not** track the repo automatically.
10. Before claiming "the app works", walk the routes by URL per role — `Livewire::test()` bypasses
    routing, middleware and the view finder, so a page can 500 while the whole suite is green (§14).

## 12. ENVIRONMENT NOTES

- Windows machine; PowerShell 5.1 shell; `node` v22 available for syntax checks;
  PHP 8.2.12 (XAMPP) and Composer 2.9.7 are installed; MariaDB 10.4 (XAMPP) runs on
  port 3306 (root, no password); dev DB is `smartcemes_v4`.
  Note: PowerShell 5.1 strips `$vars`, double quotes, and `^` in native command
  args — write scratch PHP scripts that bootstrap the app instead, or edit
  composer.json directly for version constraints.
- Use `C:\Users\Nikko\AppData\Local\Temp\opencode` for scratch work.
- Working directory: `C:\Users\Nikko\Desktop\Capstone\smartcemes3`.

## 13. RUNNING THE APP (dev + demo)

Credentials — all eight seeded accounts use password `password`:

| Role | Email | Name |
|---|---|---|
| Admin (Director) | admin@lnu.com | Dr. Lowell A. Quisumbing |
| Secretary | secretary@lnu.com | Jhoanna Ayles |
| Faculty | faculty1@lnu.com | Carlo Sumile |
| Faculty | faculty2@lnu.com | Bianca Oledan |
| Faculty | faculty3@lnu.com | Nikko Villas |
| Faculty | faculty4@lnu.com | Kent Naputo |
| Faculty (Graduate School) | faculty5@lnu.com | Dr. Ramon L. Villamor |
| Faculty (Graduate School) | faculty6@lnu.com | Dr. Cristina P. Manalo |

Commands:
- Dev run: `php artisan serve` + `npm run build` (AI generation is SYNCHRONOUS
  since blueprint v4.4 — no `queue:work` needed for AI; jobs remain
  ShouldQueue-compatible if you ever flip them back to async, which would then
  require a worker). For live deadline notifications also run
  `php artisan schedule:work`.
- Reset demo data: `php artisan migrate:fresh --seed` (reproducible — 58
  communities/schools, 6 programs, proposals + demo attachment files, etc.).
  NOTE: fresh-seed regenerates the Phase3Seeder demo stub PDFs; the public
  storage link (`php artisan storage:link`) persists across resets on Windows.
- Tests: `php artisan test` — **491 passing / 2894 assertions, 0 failures** (2026-09-28).
  Growth across the revision: 232 (baseline) → 276 (R1) → 286 (R2) → 339 (R3a/R3b) → 378 (R4) →
  403 (R5) → 423 (R6) → 437 (R3c) → 444 (R7 route-surface) → 470 (R7 college seals) → 467
  (R7 graduate set — it REMOVED 5 college-CRUD tests and added 2, hence the dip) → 472
  (R7 dashboard pass) → 467 (v4.20 Analytics removal — the 5 tests that covered the page) →
  468 (the Graduate School seal) → 476 (the faculty self-edit widening) → 484 (the hub's Programs
  level) → 485 (the hub redesign) → 485 (create/edit moved into the hub — 8 tests re-pointed,
  2 replaced by 2) → **487 / 2233** (§26 — the hub's budget surfaces consolidated to one, the
  chart turned into a doughnut, and the annual hours target made settable from the Edit modal;
  2 new tests, 16 new assertions) → **488 / 2238** (§27 — the university targets page lost its
  year chart, its D-R5 guardrail card and its Training Hours Formula card, and the Project-targets
  sort was made to actually work; 1 net new test, 5 new assertions) → **489 / 2244** (§28 — the
  retired-vocabulary sweep: five stale labels across the prototype and `/projects`, plus a D-R7 leak on
  the faculty rendered-hours page that the project-scoped guards missed; +1 test, +6 assertions) →
  **491 / 2894** (§29 — the Secretary's import template widened, and a sweep of every `route()` call
  in the views against each route's role middleware found two more links a role could see but not
  reach; +2 tests, +7 assertions).
  Style: `vendor/bin/pint app tests`.
  ⚠️ ~~`docs/TEST-SCRIPT.md` and the `docs/guides/*` walkthroughs predate the revision~~ — **both are
  current as of 2026-09-25.** `TEST-SCRIPT.md` was rewritten and executed; the guides were renamed and
  rewritten (`01-create-project.md`, `02-targets.md`, plus 7 more). A stale walkthrough claim is now
  the exception, not the rule — but read before assuming either way (§16 E).
  Tests run on **sqlite :memory:** — never use raw MySQL-only SQL in app code that
  tests exercise (see §14).
- Git: **one commit exists** — `725a4e5` (2026-09-17, "Initial commit: SmartCEMES capstone",
  364 files) — and **nothing since**. So every change made through the entire revision (R1–R7, §20–§29)
  is **uncommitted working-tree state**. Never commit `.env` / `GEMINI_API_KEY`.
  ⚠️ This line previously read *"repo initialized but NOTHING committed yet"*, which contradicted §15's
  own *"the last git commit (2026-09-17) predates the whole revision"* — both statements sat in this file
  at the same time. The commit exists; only the *revision* is uncommitted.
- `.env`: DB is `smartcemes_v4` (root, no password); `GEMINI_API_KEY` is set by
  the project owner locally (excluded from git — a fresh clone must re-add it);
  `GEMINI_MODEL=gemini-3.6-flash` (live-API verified 2026-09-13; Google
  retired gemini-2.0-flash — if this id is ever rejected, check the 404
  error body for the recommended replacement and update `.env`,
  `.env.example`, and the `config/smartcemes.php` env default together).

## 14. KNOWN GOTCHAS (do not reintroduce)

- **NEVER let a BOM into a Blade file.** PowerShell 5.1's `Set-Content
  -Encoding UTF8` writes a UTF-8 BOM; a BOM before the component root makes
  Livewire's server-side root detection pick the WRONG element (e.g. the
  first `<section>`), so the first commit morphs into a teardown — every
  button on the page dies ("Could not find Livewire component in DOM
  tree"). Worse, `Get-Content | Set-Content` on a BOM-less UTF-8 file
  DOUBLE-ENCODES it (— → â€", ₱ → â‚±, · → Â·, ✕ → âœ•). Never round-trip
  view files through PowerShell text cmdlets; use the Edit tool or
  `[System.IO.File]::WriteAllText($p, $c)` (UTF-8, no BOM).
- **Modal visibility pattern**: use the `$wire.$watch` bridge —
  `<div x-data="{ open: false }" x-init="$wire.$watch('showX', v => open = v)"
  x-cloak x-show="open" ...>` — and NEVER `@this->prop` inside Alpine/JS
  expressions (compiles to `find('id')->prop`, a PHP arrow = JS syntax
  error) nor `x-effect="open = $wire.prop"` (Livewire and Alpine bundle
  separate reactivity engines — effects never re-fire). Also never inline
  `style="display:none"` on x-show wrappers. Do NOT call `Alpine.start()`
  in app.js or import Alpine separately (dual-instance bugs); Livewire
  starts its own.
  **⚠️ The `$watch` bridge is ONLY for a modal the user opens by CHANGING a property — a
  click, a button, an action. It fires on CHANGE, so it can NEVER open a modal whose flag is
  already `true` at `mount()`.** For a modal that may need to open at mount (a deep link, a
  pre-filled form, a flag set in `mount()`), render it **server-side with `@if` and an empty
  `x-data`** — no Alpine visibility gate at all. This exact mistake shipped a create form nobody
  could see (`revisions.md` §25); it is the third time a watcher bridge has failed this way
  (the import page, the assessment drawer, the project modal).
- **Chart.js canvases**: guard re-inits with
  `if (Chart.getChart($refs.c)) Chart.getChart($refs.c).destroy();` before
  `new Chart(...)` — morphs can reuse the canvas and a second init throws
  "Canvas is already in use", aborting Alpine init for the subtree.
- Eloquent attribute/relation shadowing: **`ExtensionProject`** (renamed from `ExtensionProgram` in R2)
  has an `objectives` TEXT column → the ProgramObjective relation is `programObjectives()`;
  `AssessmentAnalysis` has a `summary` TEXT column → the relation is
  `assessmentSummary()`. Never name relations after existing columns.
- Livewire 3 full-page components require a SINGLE root element in their view —
  wrap every Livewire view in one `<div>`.
- Livewire views read JSON-cast attributes; freshly created models in tests may
  hold `null` for `json`-cast columns with DB defaults — guard with `?? []`.
- **NEVER use `$wire.$toggle` (or `wire:click="$toggle(...)"`) for array
  membership.** In Livewire 3.8.8 the client impl is
  `set(name, !get(name), live)` — it IGNORES the value argument and coerces the
  property to a bool, so the next render throws `in_array(): bool given` and
  every subsequent action on the page dies. Always toggle arrays with a
  server-side component method (see `Wizard::toggleOption`,
  `Programs\Index::toggleFormArray`, `Programs\Hub::toggleActivityFaculty`).
  `$toggle` is only safe for boolean properties.
- **Never emit two `class` attributes on one element** — `class="chip"`
  followed by `@class(['on' => ...])` renders `class="chip"` AND
  `class="on"`; HTML parsers keep the FIRST attribute, so the selected style
  silently never applies (clicks still work — confusing to debug). Merge into
  ONE directive: `@class(['chip', 'on' => $cond])`.
- **Blade `@php(...)` shares the view's PHP scope** — a loop-local
  `@php($var = ...)` clobbers any view-passed variable of the same name. This
  broke the calendar day panel (grid cell `@php($dayEvents = ...)` overwrote
  the selected-date events with the last, empty grid cell). Never reuse a
  variable name that `render()` passes to the view.
- **`@class([...])` is an array literal — `default =>` keys are invalid**
  (that's match syntax; PHP throws a ParseError). Precompute the class with a
  ternary in `@php` and pass it as the key with `=> true`.
- **`collect()->where()` uses LOOSE comparison** — matches null/0/'' pitfalls;
  use strict `filter(fn ($r) => $r['x'] === $value)` when the value can be
  falsy. (Bit the owner's prior v3 build; keep it in mind here.)
- PowerShell 5.1 mangles `^`, `$vars`, and double quotes in native command args
  (see §12) — prefer editing composer.json directly for version constraints.
- **Modal-on-modal stacking**: when a second modal is opened FROM another modal
  and sits EARLIER in the DOM, equal `z-50` means the later element wins —
  the second modal renders hidden behind the first (bit the communities edit
  modal). Either place the secondary modal after the primary in the DOM, or
  raise it to `z-[60]`.
- **Tests run on sqlite :memory:, dev on MySQL/MariaDB** — never put raw
  MySQL-only syntax (`ON DUPLICATE KEY UPDATE`, backtick identifiers, etc.) in
  app code paths that tests exercise; use portable query-builder methods
  (`insertOrIgnore`, upsert(), etc.). SequenceService originally used
  `ON DUPLICATE KEY` and broke every `$this->seed()` test until fixed.
- **Eloquent date/datetime casts store `Y-m-d H:i:s`** — an
  `updateOrCreate` where-clause keyed on `format('Y-m-d')` silently fails to
  match on sqlite (TEXT comparison) and inserts a duplicate instead of
  updating (MySQL DATE coercion masks this). Key date-column upserts on
  `->toDateTimeString()` (see `Hub::confirmAttendanceImport`; found by the
  v4.13 attendance-import tests).
- `/dashboard` is the `App\Livewire\Dashboard` component (role-switched views) —
  keep it that way; do not restore a static placeholder view.
- **Alpine `:class` vs `x-bind:class` on Blade component tags**: `:class` on an
  `<x-…>` component tag is compiled by Blade as a PHP attribute binding (it
  tries to evaluate the expression as a constant — "Undefined constant open").
  Plain HTML elements accept Alpine `:class` fine. On COMPONENT tags always
  write `x-bind:class="…"` (bit the multi-select chevron; 8 tests 500'd).
- **Deferred `wire:model` + clickability-dependent UI**: if a button's enabled
  state renders from a bound property (e.g. `pointer-events-none` until
  `$summaryId`), a deferred `wire:model` never re-renders on change and the
  button stays dead — the click that would sync the model is exactly what's
  blocked. Use `wire:model.live` when the binding must immediately affect the
  DOM, or enable the button via Alpine off the select's own value.
- **Bare Livewire properties in Alpine expressions are `undefined`** —
  `x-show="step === 'upload'"` where `step` is a Livewire public property
  does NOT read the component state (Livewire only exposes `$wire` magic to
  Alpine); the expression silently evaluates falsy — no console error — and
  hides the element. Bit the assessments import page (both steps hidden,
  only static text visible). For server-state-driven sections use
  server-side `@if ($step === ...)` in Blade (morph-safe), or
  `x-show="$wire.step === '..."`, or the local-Alpine + `$wire.$watch`
  bridge pattern used by the modals.
- **PhpSpreadsheet `showDropDown` is inverted** vs the OOXML attribute: the
  property must be TRUE for the in-cell dropdown arrow to render (the
  writer emits `showDropDown="0"` = show). Default false writes "1" which
  SUPPRESSES the arrow — validations exist but users never see them. Also
  pair with `setShowErrorMessage(false)` for suggestion-only (non-strict)
  lists so typed free text is accepted (D9). And: yesno fields carry no
  `vocab` key in `AssessmentTemplate::COLUMNS` — special-case them when
  deriving dropdowns or their validations silently vanish.
- Phase 4 dashboards pull all figures through `KpiService` (8.6) — never inline
  KPI math in views.
- **Uploaded/seeded files are served via PUBLIC `/storage/...` URLs** (owner
  decision) — proposal documents and Special Orders are force-downloaded via the
  HTML `download` attribute but readable by anyone holding the exact URL. If
  the security posture changes (Phase 6 pass), switch to an authorized
  controller + `Storage::download()` and drop the public link.
- **Livewire uploads + PhpSpreadsheet on Windows**: uploaded files land in
  `storage/app/private/livewire-tmp/` with the original name + hash + MIME
  + size encoded INTO the filename — that path can exceed Windows MAX_PATH
  (260 chars). PHP stream functions read such files fine, but
  PhpSpreadsheet's ZipArchive (a C extension using CreateFile without the
  \\?\ prefix) fails with read error 5 and IOFactory throws "Unable to
  identify a reader for this file". PowerShell's Remove-Item also cannot
  delete them. ALWAYS copy the upload to a short `tempnam(sys_get_temp_dir(),
  ...)` path before `IOFactory::load()` (see `Assessments\Import::parse()`
  and `Programs\Hub::parseImport()`); clean long-path leftovers with PHP's
  `unlink()`, not PowerShell.

### 14.1 Gotchas introduced by the R1–R7 revision

These are new since the Phases 1–5 list above. Each one cost real debugging time.

- **⚠️ A passing suite does NOT mean the app runs.** `Livewire::test(Component::class)` constructs the
  component **directly** — it never resolves a route, the middleware stack, or the Blade view finder. So
  component tests can be 100% green while a page 500s on load. This actually happened: `GET /my-projects`
  returned **500** (`View [livewire.projects.my] not found.` — an R2 rename leftover) and survived a
  437-test suite, because **nothing tested that page at all** and the tests that existed all used
  `Livewire::test()`. **Before claiming "it works", walk the routes by URL per role.**
  `tests/Feature/RouteSurfaceTest.php` now does this permanently.
- **A nav entry pointing at a missing route is invisible.** The sidebar *silently hides* items whose
  route does not exist. That is how Faculty Management stayed invisible for months and how a broken entry
  can ship unnoticed. `RouteSurfaceTest::test_every_nav_item_points_at_a_real_route` now asserts it.
- **`$collection->get($id)` cannot fail safely.** If a roll-up is keyed sequentially rather than by the
  model id you are looking up, `get(1)`/`get(2)` all *resolve* — to the **wrong rows** — because ids start
  at 1 and array offsets at 0. The symptom is one person's page showing another's figures, not an
  exception. `FacultyContributionService::assertTrainingKeyedByFacultyId()` guards it.
  **A one-element collection hides this entirely — test attribution with a 2+ member batch.**
- **A service's return type and its consumer's parameter type are declared independently.** Probed the
  real path or you will miss it: `trendForActivities(array $activities)` threw a `TypeError` on every
  page load because the consumer hands over a `Collection`, while every unit test that hand-built an
  array passed. Hand-built fixtures cannot catch a contract mismatch — probe the seeded DB.
- **`php artisan migrate` on a populated DB silently leaves the hierarchy orphaned.** The R2 backfill
  needs `colleges`/`programs` **rows** to exist, but `migrate` only creates those tables **empty**, so
  projects get NULL links; the tightening step then sees the orphans, stays silent and leaves the columns
  nullable. And `migrate` + `db:seed` also fails, because `Phase2Seeder` uses an unconditional
  `ExtensionProject::create()` that collides on existing codes. **`migrate:fresh --seed` is the only
  clean path.** Rehearse destructive fixes on a scratch DB first —
  `DB_DATABASE=scratch php artisan migrate:fresh --seed` reuses `.env` credentials safely.
- **The local MariaDB does not auto-sync with the repo.** A "table doesn't exist" error usually means
  pending migrations, not a connection problem. `php artisan migrate:status` is the first diagnostic.
- **`YEAR()` is MySQL-only.** Compute years in PHP — the suite runs on SQLite.
- **PHP `+` on arrays keeps the LEFT operand.** `$defaults + $overrides` silently discards every
  override. Use `array_merge()`. This made a scheduler test pass for the wrong reason.
- **The `Faculty` model maps to the `faculties` table** (plural). A probe joined `faculty` and failed.
- **A test that passes for the wrong reason is worse than no test.** Two R3c assertions encoded
  rationales that were simply untrue (`substr($iso, 0, 7)` does *not* yield `'2026-03-15T00'`; the
  `'undated'` bucket does *not* need reordering after `ksort` — `'u'` sorts above digits). Verify a test's
  **rationale**, not just its colour.
- **When a probe's output looks alarming, verify the join before forming a hypothesis.** A
  differently-ordered collection once made correct wiring look like misattribution.
- **Prototype harnesses need a `marker`.** `_shim.cjs`'s `boot(page, { marker })` finds the page's inline
  script by a substring; the default (`renderEngagement`) only matches some pages. It returns rendered
  **markup strings** via `innerHTML`, not a queryable DOM — inspect the string.

### 14.2 Gotchas found by the walkthrough re-run (2026-09-24)

- **Unqualified columns are ambiguous on a pivot join.** `$faculty->activities()` is a `belongsToMany`
  over `activities` + `activity_faculty`, and **the pivot has its own `id`**. So
  `->where('id', '!=', $x)` is ambiguous and throws on every driver — qualify it (`activities.id`).
  The same shape is fine on a `hasMany` (`$project->activities()` joins nothing).
- **`sprintf` with more placeholders than arguments raises `ArgumentCountError`, not a warning.** A
  guard whose *message* is malformed fails **instead of guarding**. Count the `%s`.
- **A guard with no test is not a guard.** The 8.8 conflict block was dead for two independent reasons
  and the suite never noticed, because every existing test used faculty with **no prior activities** —
  so the guard's query returned nothing and its message was never built. **Test the positive branch of
  a guard, not just the absence of a crash.**
- **`activities.participants` is a trainee FALLBACK (R-Q1), not the reach.** The project's **Trainees**
  tile is a distinct beneficiary count from attendance, so it is 0 until attendance exists. Imported
  attendance **replaces** the fallback, so computed hours can *drop* when attendance lands for fewer
  people than `participants` claimed.
- **The seeded demo data books faculty.** Nikko Villas is committed 2026-09-08 → 2026-10-16, so a
  September activity assigned to him is refused by the (correct) hard block. Check the seeded
  assignments before picking walkthrough dates.
- **`php artisan test` can be killed (SIGTERM) in the foreground here while `vendor/bin/phpunit` runs
  fine.** Use `vendor/bin/phpunit` for single-class runs in this environment.
- **Never batch two `Edit` calls to the SAME file in one message.** They race — the second write
  clobbers the first, so a config value you "definitely changed" is still old and tests fail on routes
  that plainly exist.
- **`nextCode()` is not a peek — it consumes a sequence number.** Calling it to "check what code you'd
  get" mutates the `sequences` table. Restore it (or re-seed) afterwards.

### 14.3 Gotchas found on 2026-09-25 (college set, dashboard, prototype)

- **A harness that greps a page for a phrase matches the page's PROSE.** `_check.cjs` asserts the
  dashboard does **not** contain "Recent Activity" — and my own explanatory comment ("Recent Activity
  MOVED to its own page") satisfied it, so the harness reported the removed panel as still present.
  Write comments about a removed thing **without naming it**. The same happened with a glyph inside a
  code comment, which a sweep script then "helpfully" replaced.
- **A missing icon name fails SILENTLY.** `x-sc.icon` resolves `$paths[$name] ?? $paths['doc']`, so a
  typo renders a **document**; the prototype's `[[name]]` tokens render literally. Confirm a name
  exists before using it — `alert` had to be ADDED for the warning glyph.
- **`actingAs()` persists for the REST of the test.** A "guest" request after `actingAs($faculty)` is
  still authenticated, so it returns **403** (from `EnsureRole`) instead of the **302** a real guest
  gets. Assert the guest case FIRST. This made a test pass for the wrong reason — only `curl` against
  the running app exposed it.
- **A NULL `no_of_days` already means 1.0 day** (`TrainingHoursService`: `$row->no_of_days !== null ?
  (float) $row->no_of_days : 1.0`). Back-filling the R4 columns therefore changes almost nothing — it
  only flips `measurable` from false to true. **Do not "fix" thin training hours by filling them**;
  the real driver is beneficiary enrollment (§16 G).
- **`storage/framework/views/*.php` is in the Tailwind content globs**, so `php artisan view:clear`
  moves the built CSS size. A sudden ~10 kB drop is not lost styling — check the classes, not the byte
  count. And a regex looking for `.lg:grid-cols-3` must escape the COLON (`\.lg\\:grid-cols-3`).
- **A `mysqld` started as a background task may exit on its own OR linger as an orphan — it varies.**
  And **a `php artisan serve` left running while MariaDB is down does not error: it WEDGES.** It keeps
  holding the port but answers nothing (`curl` → HTTP 000), because every request needs the DB for its
  session. Always check 3306 alongside 8000.
- **These docs contain mojibake ON PURPOSE.** `revisions.md:726` and `AI_HANDOFF.md:181`/`:1276`
  document the sequences (`— → â€"`, `₱ → â‚±`, `· → Â·`), so a "does this file contain `Â·`?" check
  false-positives on them. Also `·` (U+00B7) **is** the bytes `0xC2 0xB7` — the real test is the
  DOUBLE-encoded form `0xC3 0x82 0xC2 0xB7`.

### 14.4 Gotchas found on 2026-09-26 (the budget-basis correction)

- **A "target" label can outlive the concept by a whole release.** The dashboard read
  `annual_target_budget ?? allocated_budget` — a *silent fallback* — so the tile said "Annual Target" while
  printing the allocation for 6 of 8 projects, and nothing on screen could tell you. A fallback that makes
  two different concepts render identically is exactly how a wrong label survives. Prefer `?? null` plus a
  visible "not set" state over `?? <something else>`.
- **Check the DATA before believing a label.** The fix looked risky until a one-line query showed only
  **2 of 8** projects had a target at all, and both equalled their allocation — i.e. zero figures would move.
- **A retained-but-unread column still attracts WRITERS.** `R4TargetsSeeder` and the project create/edit
  form were both still writing `annual_target_budget`. Leaving them would have kept an inert column looking
  alive. When you retire a concept (D-R7 did it to the 8.6 KPIs), retire its writers too — not just its
  readers.
- **The prototype's demo data can contradict the Laravel data.** The prototype modelled `budget` and
  `budgetTarget` as *different* numbers (48,000 vs 52,000) while Laravel's target was NULL — and HANDA was
  flagged `overAllocated: true` against a target it did not actually exceed. Collapsing the two fields
  fixed a demo bug, not just a label.
- **Renaming a payload key is a contract change for the AI.** `ProgramAggregates` feeds PromptV2, whose
  text stated `attainment_pct` is "against the project's ANNUAL TARGET". Renaming the key without touching
  the prompt would have made the model narrate an allocation as a target. **Update the LIVE prompt; leave
  the frozen one (PromptV1) alone** — the stored `raw_extracted_data` snapshot is what keeps old analyses
  reproducible, not the current payload shape.
- **`_check.cjs` guards can be written against a token that never existed.** Its colleges-hub guard tested
  for `const bp = c.budgetTarget` while the code actually used `college.budgetTarget` — so it passed
  vacuously. It now asserts the *property* (a computed `bp` must be rendered), not a literal string.

## 15. OUTSTANDING WORK (complete list as of 2026-09-26)

**Nothing feature-shaped is outstanding.** Everything a user can click exists and is smoke-verified.
**One item remains, non-code: the production deploy (#5).**

| # | Item | Type | Notes |
|---|---|---|---|
| 1 | ~~Blueprint revision entry + stale sections~~ | Docs | ✅ **DONE** — the blueprint is now **v4.20**. The v4.16 stale-section rewrite is §15.3; the v4.18 dashboard pass is `revisions.md` §19.12. |
| 2 | ~~`docs/guides/*` refresh~~ | Docs | ✅ **DONE — verified 2026-09-25 by reading them.** The guides were renamed and rewritten (`01-create-project.md`, `02-targets.md`, plus 7 more) and the staleness banners are gone. **`docs/TEST-SCRIPT.md` is also current** (rewritten + executed, §15.2). |
| 3 | ~~Defence walkthrough re-run~~ | Verification | ✅ **DONE (2026-09-24)** — script rewritten for the revised model, executed, and pinned by `DefenceWalkthroughTest`. Found two real bugs in the 8.8 guard. See §15.2. |
| 4 | ~~Nightly DB backup script~~ | Deployment | ✅ **DONE (2026-09-24)** — `smartcemes:backup-database`, scheduled 02:00 daily, 14-dump retention. See below. |
| 5 | Production deploy | Deployment | Absorbed from the retired "Phase 6". ← **the only remaining item of any kind**. Full step-by-step checklist: **§15.4**. |
| 6 | ~~Prototype-fidelity gaps on `/programs` and `/colleges`; nav-collapse rule~~ | UI | ✅ **DONE (2026-09-24)** — `/colleges` became the two-view hub, `/programs` restored to the prototype (D-R5-legal), and the admin nav collapsed to one guarded hub entry. **Superseded: since 2026-09-27/28 the hub is a THREE-view drill-down (College → Program → Projects) and owns all create/edit — `revisions.md` §§23–§25.** |
| 7 | ~~College seals · project→college mapping · Graduate School · dashboard pass~~ | UI + data | ✅ **DONE (2026-09-25)** — `revisions.md` §19.9 (the seals), §19.10 (the mapping rule), §19.11 (GRAD + the read-only college set), §19.12 (Audit Logs, budget bullet rows, leader bars, no emoji — in Laravel **and** the prototype). |

### 15.1 The nightly backup (item 4 — completed)

`php artisan smartcemes:backup-database`, registered in `routes/console.php` at **02:00 daily**,
retaining the last 14 dumps.

- **Driver-aware.** MySQL/MariaDB via `mysqldump` with `--single-transaction --skip-lock-tables
  --quick --routines`; SQLite via `VACUUM INTO` (atomic — a plain file copy of a live database can
  tear). A `:memory:` connection warns and exits 0, so a test suite never fails a scheduled run.
- **The password goes through `MYSQL_PWD`, never argv** (argv is world-readable in the process list).
  Asserted by `DatabaseBackupTest`.
- **A partial dump can never be mistaken for a good one** — output is staged at `.tmp` and renamed
  into place only after a non-empty check; any failure exits FAILURE and logs.
- **`DB_DUMP_BINARY` is configurable and usually needs to be.** `mysqldump` is very often outside the
  PATH on Windows (XAMPP/Laragon/WAMP) and on shared hosts. On this machine it lives at
  `C:/xampp/mysql/bin/mysqldump.exe`. See `.env.example` for the backup block.
- **Verified end to end on 2026-09-24**: a 250 KB dump of `smartcemes_v4` (37 tables), restored into a
  scratch database with row counts identical to the live DB (3 colleges / 6 programs / 7 projects /
  6 users). The scratch DB was dropped afterwards.

**Production still needs:** the scheduler running (one cron entry: `* * * * * php /path/artisan
schedule:run`), and `DB_DUMP_BINARY` set if `mysqldump` is not on the PATH.

### 15.2 The defence walkthrough (item 3 — completed)

`docs/TEST-SCRIPT.md` was rewritten for the revised model (it had described the pre-revision hierarchy
since 2026-09-15 and taught the removed 8.6 objectives) and then **executed**.
`tests/Feature/DefenceWalkthroughTest.php` drives steps 2, 3, 6, 7 and 8 through the real Livewire
components and asserts **every figure the script prints** — so a model change that invalidates a
number in the document fails a test rather than embarrassing someone mid-demo.

**It found two real bugs, both in the 8.8 faculty-conflict hard-block** — neither reachable from the
existing suite, because every existing test used faculty with **no prior activities**, so the guard's
query never returned a row and its message was never built:

1. `Hub::findScheduleConflict()` filtered `->where('id', '!=', …)` on the `$faculty->activities()`
   relation, which joins `activities` to `activity_faculty` — **the pivot has its own `id`**, so the
   column was ambiguous and the query threw on every driver. Assigning faculty to an activity
   **crashed instead of saving**.
2. The refusal message has **four `%s` placeholders and three arguments**, so the guard raised
   `ArgumentCountError` **instead of refusing**. The hard-block did not work even when a conflict was
   detected.

Both fixed and pinned; the sprintf fix was **mutation-tested** (reverting it reproduces the failure).
Also fixed: the `/projects` create button, list header and submit button still said **"New Program"**.

**Three traps the re-run exposed, now written into the script:**
- **Seeded bookings collide with naive dates.** Nikko Villas is committed to *Feeding Cycle 2* from
  2026-09-08 → 2026-10-16; a September activity assigned to him is refused. The walkthrough's dates
  moved to October, and step 12 deliberately uses the September window to *demonstrate* the block.
- **`activities.participants` is a trainee FALLBACK, not the reach.** The project's **Trainees** tile is
  a distinct beneficiary count from attendance, so it reads 0 until attendance is imported.
- **Imported attendance REPLACES the fallback**, so the computed hours can *drop* when attendance lands
  for fewer people than `participants` claimed. The script keeps `participants` equal to the number of
  beneficiaries it registers so the hours stay stable across the import.

> **Environment note:** in this environment `php artisan test` was killed (SIGTERM) in the foreground
> while `vendor/bin/phpunit` ran fine — use the latter for single-class runs here.

### 15.3 The blueprint stale-section rewrite (item 1 — completed)

`SYSTEM_BLUEPRINT_V4.txt` moves **v4.15 → v4.16**. `revisions.md` §8 had listed the remaining stale
sections; the cheap fixes landed in the v4.15 pass and the **full section rewrite** — the last
substantial item — is now done. Roughly **90 claims** described the pre-revision system, and the worst
were not cosmetic: §2 had the Director "defining and managing program objectives via the program hub's
objective manager", §5.10 defined M&E as "all KPIs computed strictly per 8.6", §12 described a KPI
scorecard and a Program Results Framework report, and §15 required that "all dashboard KPIs match the
8.6 metric dictionary exactly".

**Rewritten:** §2 (role workflows) · §3 (objectives) · §5.1/5.2/5.4/5.5/5.10/5.11/5.13/5.15 · §6.2,
§6.4c, §6.15, §6.16 · §8.1/8.3/8.5/8.6 · §12 · §15 · §16.
**Added:** **§14 D14–D20** — the revision's decisions as first-class design decisions (four-level
hierarchy · the training-hours formula · the target model · the 8.6 retirement · the AI three-tier
guardrail · contribution-based faculty performance · the collapsed navigation).
**Deliberately NOT changed:** the **v4.1–v4.15 revision entries** are historical records; rewriting
them would falsify what was decided when. A v4.16 banner says so explicitly.

**Two more naming leftovers found and fixed:** the Analytics tab still read **"Program Performance"**
(now "Project Performance", in **both** Laravel and the prototype — the prototype carried the same
stale label; harnesses re-run green), and the report route `reports.results-framework` keeps its name
deliberately (bookmarks) but §12.2 now says so.

**Integrity:** 1 686 → 1 870 lines, no BOM, **zero** mojibake sequences. Every remaining
`objective`/`KPI`/`results framework` hit was verified to be either a deliberate "retired" statement, a
historical revision entry, or a retained-model field definition.

### 15.4 The production deploy (item 5 — the ONLY outstanding item)

Audited 2026-09-26. Everything the app needs is in the repo; what is missing is the environment. The
checklist below is the whole deploy. **None of it has been run against a real server** — it is unexecuted
until the target host is chosen.

**What the repo already provides**
- `composer setup` — install → copy `.env` → `key:generate` → `migrate --force` → `npm install` →
  `npm run build`. It does **not** run `storage:link` or `db:seed`; add both.
- `php artisan smartcemes:backup-database` (02:00) and `smartcemes:notify-deadlines` (07:00), both
  registered in `routes/console.php` — confirmed by `php artisan schedule:list`.
- **No queue worker is required.** AI generation is synchronous (v4.4). Only the two AI jobs implement
  `ShouldQueue`, and both are `dispatchSync`ed; add a worker only if that is ever flipped back to async.
- No `dd()` / `dump()` / `var_dump()` left in `app/`, `resources/views/` or `routes/`.

**Steps**
1. **PHP + extensions** — PHP 8.2+, with `pdo_mysql`, `zip` and `gd` (PhpSpreadsheet), `mbstring`, `xml`.
2. **Database** — create the schema, then `php artisan migrate --force`. **Do NOT run a bare `migrate` on a
   populated DB** (§14.1: the R2 backfill silently orphans the hierarchy). A fresh box is fine.
3. **`php artisan key:generate`** — a clone without `APP_KEY` cannot decrypt sessions.
4. **Production `.env`** — `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` = the real URL,
   `LOG_LEVEL=warning`, plus the `DB_*` block. **`APP_DEBUG=true` in production leaks stack traces and
   environment values on every error page.** `.env.example` now documents each of these inline.
5. **`php artisan storage:link`** — uploads live on the **public** disk and are served from `/storage`.
   Without the link, every proposal attachment, special order and import source file 404s.
6. **`GEMINI_API_KEY`** — set it, or every AI surface shows its first-class "unavailable" state (D12: there
   is no mock mode). Verify the quota before any demo.
7. **Front-end assets** — `/public/build` is **gitignored**, so a fresh clone has NO compiled CSS/JS.
   `npm ci && npm run build` must run on the server (or the build must be shipped with the release).
8. **Scheduler** — one cron entry: `* * * * * cd /path/to/app && php artisan schedule:run`. Without it
   neither the nightly backup nor the deadline notifications ever fire.
9. **`DB_DUMP_BINARY`** — set it when `mysqldump` is not on the PATH (very common on XAMPP/Laragon/WAMP and
   on shared hosts). Then run `php artisan smartcemes:backup-database` once by hand and confirm a
   non-empty dump lands in `storage/app/backups`.
10. **Caches** — `php artisan config:cache && php artisan route:cache && php artisan view:cache`. Re-run
    `config:cache` after ANY `.env` change, or the old values keep being served.
11. **Walk the routes by URL per role** (§11 rule 10) — a green suite is NOT a running app. Confirm no 5xx on
    `/dashboard`, `/colleges`, `/programs`, `/projects`, `/projects/{id}`, `/targets`,
    `/reports/*`, `/audit-logs` as admin, then the role surfaces as secretary and faculty.

> **Before any PUBLIC deployment:** all eight seeded accounts (§13) use the password `password`. Change or
> disable them, or the Director account is wide open.

**Still needed from the owner:** the host (cPanel / Forge / VPS / other), whether the DB is local or managed,
the domain (for `APP_URL` and HTTPS), and whether demo data should be seeded in production.

## 16. KNOWN GAPS & INCONSISTENCIES (flagged, not silently resolved)

A new session should know these are *known* rather than rediscover them.

**A. Prototype pages that contradict a locked decision (Laravel is correct — do NOT "fix" Laravel):**
- **`programs.html` renders per-program training-hours targets and attainment %** ("of 2,640 annual
  target · 37%", per-card "149%"). This contradicts **D-R5** (targets at University + Project level
  only). `colleges.html` *was* updated for D-R5 and carries an explicit comment; `programs.html` never
  was. Note the nuance: the prototype's **project count and rendered hours** are legitimate *derived
  roll-ups* of child projects, which D-R5 does **not** forbid — only the targets are forbidden.

**B. Prototype-side stale leftovers:**
- **`compliance.html`** and its two inbound links (`index.html`, `dashboard-secretary.html`) are stale —
  the blueprint's v4.12 entry records the Compliance Tracker as a *"phantom never-built"* entry that was
  deliberately removed.

**C. Laravel `/programs` drift from the prototype — ✅ RESOLVED (2026-09-24):**
- Summary tiles, the full toolbar (search / pillar chips / college select / sort / grid↔list, all
  `#[Url]`), the 7-column list view, and the `← Colleges` / `View all projects →` header buttons are
  all restored.
- The card is now the prototype's: pillar accent bar, mono code badge, pillar chip, college pills,
  3-stat row, status badge and a `View projects →` drill-down CTA.
- **One deliberate correction:** the prototype's per-program training-hours target and attainment are
  NOT restored — they contradict D-R5. What is shown is the *derived* roll-up (project count, hours
  delivered, reach, budget consumed, college membership derived from the projects).
  `tests/Feature/ExtensionHubTest.php` pins both halves of that rule.
- The Laravel **Colleges** page no longer shares the invented gradient card either — it uses the
  prototype's `.college-card`. *(It was the two-view hub when this was written; it has been a
  **three-view** drill-down since 2026-09-27 and owns create/edit since 2026-09-28 —
  `revisions.md` §§23–§25. The prototype still renders the two-view shape; see §8.)*

**D. Nav structure — ✅ RESOLVED (2026-09-24):**
- The admin sidebar now carries exactly **ONE** hierarchy entry — `Manage Extension Programs`
  (`colleges.index`, `subs` = `colleges.index` / `programs.index` / `projects.index` / `projects.show`).
  `Colleges`, `Extension Programs` and `Extension Projects` are no longer sidebar items, so the
  prototype's own rule (`_check.cjs:196-210`) is satisfied.
- **And it is now asserted on the Laravel side** (it never was — that is why it went unnoticed):
  `CollegeProgramCrudTest` pins the collapse, the `subs`, the rendered sidebar and the topbar label.
- `subs` are **exact route names, not wildcards**, so `RouteSurfaceTest` can validate them and still
  walk `/programs` and `/projects` for a 5xx — without that the collapse would have silently *reduced*
  route coverage.
- Nav resolution is shared (`App\Support\Navigation`) by the sidebar and the topbar, so the highlight
  and the page title cannot drift.

**E. Documentation debt:**
- ~~`README.md` is still the default Laravel boilerplate~~ — ✅ replaced 2026-09-24.
- ~~`docs/TEST-SCRIPT.md`~~ — ✅ **rewritten for the revised model and executed 2026-09-24** (§15.2).
  It is now the current walkthrough; the 2026-09-15 version described the pre-revision hierarchy.
- ~~`SYSTEM_BLUEPRINT_V4.txt` stale sections~~ — ✅ **rewritten 2026-09-24 (v4.16, §15.3)**. The
  v4.1–v4.15 revision entries are historical records and are left as written on purpose.
- ~~`docs/guides/*`~~ — ✅ **DONE, verified 2026-09-25 by reading them.** The guides were renamed and
  rewritten for the revision: `01-create-program.md` → **`01-create-project.md`**,
  `02-objectives.md` → **`02-targets.md`** (which says outright that it *replaces* the old
  objectives/results-framework guide), plus 7 more modified and a rewritten `README.md`. No banners,
  no `ExtensionProgram` references. ⚠️ **The previous revision of this file claimed they were "still
  bannered" — that was asserted, not checked.**
- ⚠️ **That 2026-09-25 verification has since EXPIRED.** `docs/adminguide.md`, `docs/features.md` and
  `docs/guides/01-create-project.md` were current as of the revision — but **§21, §23, §24 and §25 landed
  on 2026-09-27/28 and were never propagated to them.** Found by grepping the doc set for the removed page
  and the removed flow, *not* by reading: all three read perfectly plausibly. **Fixed 2026-09-28:**
  - `adminguide.md` §9 was a six-tab walkthrough of the **deleted Analytics page** — as was its nav row
    and checklist step 14. The section is now a tombstone that says so; the number is kept so §10–§16
    keep theirs. Step 14 now verifies the hub's create/edit modals instead.
  - `features.md` §3.8 demoed the same deleted page — now a "skip this step" tombstone, same numbering
    trick. Its nav table lost the Analytics entry too.
  - `01-create-project.md` — the *create-project* guide — taught a **dead create path**
    (`View all programs → View all projects → New Project`), told the reader to go to `/projects` as the
    "create path" (read-only since §25), and listed a **`Target budget (₱)` form field that no longer
    exists** (retired by v4.19). All three corrected against `colleges/index.blade.php`, not from memory.
  - `docs/prototype/PATTERNS.md` (×2), `assets/css/smartcemes.css` and `pages/colleges.html` each said
    **"three college cards"** where the page renders four and `_hubtest.cjs:157` asserts four. Corrected.
  - All six harnesses re-run green after the prototype-touching edits (§11 rule 8).
  **Still outstanding, NOT fixed** (each needs a decision, not a grep): `revisions.md:3` carries a
  `Status (2026-09-24)` date above a growth chain running to 2026-09-28, and its §10 R7 row still scopes
  the phase to "blueprint v4.18" while the blueprint is v4.21. **Read the guides before trusting them** —
  the guides directory has now proved this file can *under*-report staleness as easily as over-report it.

  **Two items from this list are now CLOSED (2026-09-28).** `docs/prototype/pages/reports.html`'s retired
  "8.6 KPI dictionary" footer was removed with the rest of the retired-vocabulary sweep (§28). And
  `docs/prototype-backup-preR/` — a 1.4 MB, 24-page pre-revision copy of the prototype sitting *inside*
  `docs/`, carrying its own `analytics.html` for a page deleted in v4.20 — has been moved out of the docs
  tree. ⚠️ It was **untracked by git**, so a plain delete would have been unrecoverable; it was therefore
  ARCHIVED to `.workbuddy-ai/backups/2026-09-28-prototype-backup-preR/` (the same convention the Analytics
  removal used — §21), not hard-deleted.

**F. Carried deliberate divergences (documented, not bugs):** the `targets.html` native rework
(`revisions.md` §16.10), the faculty profile page having no prototype at all (§18.4), and the
**college seals** — Laravel renders the official seals on `/colleges` (all four colleges, since the
Graduate School's landed 2026-09-27 — §19.9.5), while the prototype still
carries the `Logo` placeholder (§19.9; its own harnesses still *require* the placeholder, so do not
"fix" the prototype without updating `_check.cjs:296` and `_hubtest.cjs:173–181` together).

**G. The demo ranking is lopsided BY DECISION (2026-09-25) — do NOT "fix" it without asking:**
- **Five projects have 2 beneficiaries each** (LITRAWIYA, HANDA, KABUHIAN, e-LITERACY, SENIOR CARE)
  and **BATANG MATINIK has 0 beneficiaries and no activities at all**, so `Performance Leaders` is
  dominated by BUSOG (134.5 h against 4.0, 3.0, 2.0). Any magnitude chart makes this visible.
- The root cause is **beneficiary enrollment**, NOT the R4 training-hours columns: a NULL
  `no_of_days` already counts as **1.0 day**, so back-filling them changes almost nothing (§14.3).
- Fixing it properly means enrolling cohorts and seeding attendance for five projects — the PANDAY
  job times five — and it would move pinned figures in `DefenceWalkthroughTest`, `FacultyModuleTest`
  and the prototype's dashboard data. **The owner chose to skip it** (`revisions.md` §19.12.1).
- Separately, the prototype's `universityTargets` rows still read
  `colleges:'CAS, COE, CME', activeColleges:3`. **Those two fields are read by nothing** — the page's
  college filter is built from `D.colleges` — so it is inert dead data, not a visible staleness. Left
  alone rather than rewriting a year-keyed approved record.

---

END OF HANDOFF — updated 2026-09-28 for the post-revision architecture (**491 tests / 2894 assertions, 0 failures** · blueprint **v4.20** · prototype **29 pages**).
**v4.19 (2026-09-26):** budget has NO annual target — the ALLOCATION is the basis (§3.0, §14.4, `revisions.md` §20).
**v4.20 (2026-09-27):** the Admin Analytics page is REMOVED — `/analytics`, its six partials, nav entry, 5 tests and prototype page are deleted; the aggregate pending list and the community-reach chart went with it (§1.0, §3.0, `revisions.md` §21).
**2026-09-27:** the Graduate School's seal landed — all four colleges are sealed and `.college-crest` is now a pure fallback (§1.0, `revisions.md` §19.9.5).
**2026-09-27:** the faculty self-edit was widened to expertise + academic details, and `/my-profile` gave the page the entry point it never had (§1.0, `revisions.md` §22). **The code had contradicted its own contract since R3 — see §22.1 before touching anything here.**
**2026-09-27:** `/colleges` became a three-view drill-down — College → Program → Projects → Activities, with no cross-college views in the flow (§1.0, `revisions.md` §23). **The prototype now TRAILS Laravel here, so a green `_hubtest.cjs` does not mean the hub matches the app.**
**2026-09-28:** the hub's college-selected view was **redesigned** — breadcrumb, brand-coloured hero band with a haloed seal, icon-chip KPI tiles, 2-up program grid; view 3 restyled to match (§1.0, `revisions.md` §24). Presentation only; **one design rule was fixed** (the program hours label read `Training hours`, which only a project card may say).
**2026-09-28:** **create/edit for programs and projects moved into the hub** as modals, fixing three §23 regressions — program CRUD unreachable, New project navigating to a removed-from-flow page, and a create modal that never opened (§1.0, `revisions.md` §25). `/programs` and `/projects` are read-only lists now.
**2026-09-28:** the project hub's Overview was **consolidated to a single budget surface** — the Budget
stat tile and the 4-row key/value list were removed, and the chart became a **doughnut** whose centre
carries the true % of the allocation (its three slices double-count that allocation — the caveat is
recorded in `hub.blade.php` and was accepted by the owner). **Target training hours is now settable from
the Edit modal**, which it never was, and all eight seeded projects carry a target — so the six legacy
ones read 0–0.7 % of target, which is the honest figure rather than a bug (§16 G). `revisions.md` §26.
**2026-09-28:** the **university targets page** lost three blocks at the owner's request — the
**Progress Across the Year chart**, the **D-R5 guardrail card** and the **Training Hours Formula card**
(§27). The **Project-targets sort was inert** (the blade pinned `sortBy('code')` while the select
offered three keys, for an Alpine comparator that was never written); it is now sorted server-side and
pinned by a test. ⚠️ A test had been **guarding** the removed copy — it now pins the absence. The D-R5
disclosure survives on the project hub. `revisions.md` §27.
**2026-09-28:** a **retired-vocabulary sweep** of the prototype found **five labels that outlived their
concepts** — budget *attainment* on `/targets`, "Budget vs **target**" on `/projects` (wrong in Laravel
AND the prototype), and an "8.6 KPI dictionary" footer on the print report. It also found a **D-R7 leak**
the guards missed: the faculty's rendered-hours page said its hours *"count toward the 8.6 KPI"*, and the
prototype's copy still carried a hardcoded **per-professor "Target 40 hrs / semester"** bar that P0h had
removed. D-R7's guards are all project-scoped and that page had **no test at all** — it has one now.
`revisions.md` §28.
**2026-09-28:** the **Secretary's beneficiary import template was 403'ing** — the hub showed the
download link inside a modal gated on `$canManageBeneficiaries` (admin + secretary) while the route
was `role:admin`. Widened to match its two sibling templates. **A sweep of every `route()` call in
`resources/views/` against each route's role middleware** (29 role-gated routes, 38 links) then found
**two more links a role could see but not reach**: the hub's "University pool →" (faculty AND
secretary) and the faculty profile's Directory breadcrumb (faculty). Both hidden for non-admins and
pinned by a test. `revisions.md` §29.
**How to SEE a page change:** `bash .workbuddy-ai/shot.sh <out.png> "<path>"` logs in and screenshots the real page with headless Chrome. Windows has no browser automation, so this is the only way to review a redesign (§24.5).
**NEXT: finish R7** — the ONLY item left is the **production deploy**. Everything else is done: the nightly DB backup (§15.1 — set `DB_DUMP_BINARY` if
`mysqldump` is not on the PATH), the defence walkthrough (§15.2), the blueprint stale-section rewrite
(§15.3), the college seals (§19.9), the project→college mapping correction (§19.10), the Graduate
School + read-only college set (§19.11), the dashboard pass (§19.12), the Analytics removal (§21), and
the faculty self-edit widening (§22).

Set `GEMINI_API_KEY` before any AI demo — and read `revisions.md` §19.9–§19.12, §21 and §22 first:
**the college set, the dashboard, the removed Analytics page and the faculty self-edit are the four
places where older prose in this file will mislead you.**
