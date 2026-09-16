# SMARTCEMES — AI HANDOFF
_Last updated: 2026-09-17 (session 15, v4.13 activity imports COMPLETE — steps 6–7 landed; 222 tests passing / 0 failures) · Blueprint v4.14 (with the v4.13 sections + revision entry inserted BEFORE v4.14) · Phases 1–5 COMPLETE · Prototype v4.1-conformant + redesign mirror_

You are picking up **SmartCEMES**, an AI-powered Community Extension Monitoring and
Evaluation System for Leyte Normal University (capstone project). This document
transfers everything an AI agent needs to continue the work correctly. Read it
fully before writing any code.

---

## 1. CURRENT STATE (accurate as of this document)

- **SESSION 15 — v4.13 ACTIVITY IMPORTS: STEPS 6 (TESTS) + 7 (DOCS) COMPLETE
  (2026-09-17)** — the feature is now DONE. **222 tests passing / 0 failures
  (1082 assertions)**; pint clean.
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
  prototype (24 pages) brought to full blueprint-v4.1 conformance. All inline
  scripts syntax-verified (29/29 OK). It is the visual source of truth for every
  screen, flow, state, and interaction — replicate its look and behavior in Blade/Livewire.
- **`prev/` folder**: legacy v3 build reference. Its seeders (`prev/seeders/`) contain
  reusable realistic data (Tara Basa!, PURPPLE, Leyte communities, namelists) that
  MUST be remapped before reuse (see §5). `prev/prototype/` is v3 history — do NOT
  copy its structure (it has forbidden standalone pages and secretary AI access).

## 2. AUTHORITATIVE DOCUMENTS (read in this order)

1. `SYSTEM_BLUEPRINT_V4.txt` — THE contract. Where anything conflicts, blueprint wins.
   - §2 users/roles/workflows · §5 features · §6 data models (6.1–6.18)
   - §7 controlled vocabularies (needs-assessment form, Sections I–IX)
   - §8.6 KPI dictionary (single source of truth) · §8.8 scheduling hard constraints
   - §11 phases · §14 design decisions D1–D13 · §18 revision history
2. `docs/prototype/PATTERNS.md` — design-system rules (tokens, components, variants).
3. `docs/prototype/assets/` — `smartcemes.css` (component classes), `tw-config.js`
   (lnu blue #003599 / gold #F6B800 / Figtree), `seed-data.js` (demo data), `layout.js` (nav).
4. `prev/NEEDS ASSESSMENT_FORM.md` — source instrument behind §7 vocabularies.
5. `docs/F-CES-002..005` — official university form scans (context reference only).

## 3. LOCKED DECISIONS (do not reopen)

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

## 5. DATA MODEL (Section 6 — build exactly this)

18 entities: users(6.1) · faculty(6.2) · communities(6.3, status active|prospecting;
v4.5: also `type` community|school + nullable `school_level` — the table doubles
as the partner-schools registry, schools always active)
· extension_programs(6.4) · activities(6.5) · beneficiaries(6.6) · attendances(6.7)
· availability_requests(6.8, admin-initiated) · needs_assessments(6.9, ~60 JSON
columns per §7) · assessment_summaries(6.10, UNIQUE (community_id, quarter, year),
recomputed on submission/review change) · assessment_analyses(6.11, draft→approved|discarded)
· budget_utilizations(6.12, program required + optional activity) · activity_proposals
(6.13, status machine + auto-create Activity on approval) · proposal_documents(6.14)
· program_objectives(6.15, kpi_metric from locked keys or NULL=qualitative) ·
program_narratives(6.16) · rendered_hours(6.17, UNIQUE (faculty_id, activity_id))
· activity_imports(6.19, v4.13 — see SESSION 15).

Conventions: DECIMAL(12,2) money; JSON fields cast to array (arrays of strings);
timestamps + SOFT DELETES everywhere; enum-like columns are strings validated
against §6.18/§7 server-side. Pivots: extension_program_beneficiary,
activity_faculty, community-program pivot.

## 6. KPI DICTIONARY (8.6 — all dashboards/reports derive EXCLUSIVELY from this)

Locked ProgramObjective.kpi_metric keys: `participation_rate`,
`activity_completion_rate`, `attendance_consistency`, `budget_utilization`,
`knowledge_gain`, `cost_per_beneficiary`, `community_reach` (rendered hours is a
faculty-level KPI, NOT in this vocabulary). Objective status derivation:
achieved (actual ≥ target) · not_met (target_date passed, actual < target) ·
on_track (progressing, date not passed) · not_started. Numeric objectives
live-compute actuals; qualitative use manual actual + evidence. Display always
baseline → target → actual.

## 7. PHASES (Section 11) — STATUS: Phases 1–5 and v4.13 COMPLETE; next is Phase 6

All of the below is BUILT (see §1 for per-phase delivery notes and the code for
details). Do not restart here.

- **Phase 1 — Foundation: COMPLETE.** Laravel 12 at repo root, MySQL `smartcemes_v4`,
  Breeze (no self-registration), roles + `EnsureRole` middleware, race-safe IDs,
  six 2.4 accounts seeded.
- **Phase 2 — Core Data Modules: COMPLETE.** Design system ported to Vite/Tailwind;
  all §6 entities + pivots; `config/smartcemes.php` vocabularies; Livewire CRUD
  (communities, programs, tabbed hub w/ objectives/activities/attendance/enrollment/
  budgets); Sections I–IX wizard; authored official XLSX template (D11) + import
  flow (D9/D10); Phase2Seeder.
- **Phase 3 — Workflows: COMPLETE.** Proposals (Special Order, auto-create Activity,
  8.8 range hard-block), availability (admin-initiated, required decline reason,
  accept hard-block), secretary validation, rendered-hours lifecycle (auto-draft,
  adjust-down-only, locked), database notifications + `smartcemes:notify-deadlines`
  scheduler w/ 7-day dedup; Phase3Seeder.
- **Phase 4 — Dashboards/Reports: COMPLETE.** Three role dashboards (admin w/
  action center + AI panel; secretary; faculty), six-tab Analytics (8.6), custom
  calendar (3 feeds + conflicts + 60-day list), four print reports w/ letterhead.
- **Phase 5 — AI: COMPLETE.** Gemini pipeline (see §1).
- **Phase 6 — Hardening (NEXT)**: feature tests for
  the three approval workflows (partially covered already), query optimization,
  production deploy, nightly backup script, security pass, docs.

## 8. PROTOTYPE MAP (docs/prototype/pages — replicate these in Laravel)

- `login.html` — demo role pills (admin/secretary/faculty) → dashboards via ?role=
- `dashboard-admin/secretary/faculty.html` — role dashboards; admin has AI panel
  (insights queue + narratives) and full action center (6 items); secretary has
  ZERO AI surfaces; faculty has inline availability accept/decline
- `analytics.html` — ONE Admin Analytics page, six tabs: Overview, Program
  Performance, Budget Utilization, Community Reach, Faculty Contribution,
  Pending Actions
- `program-detail.html` — THE program hub: tabs Overview | Activities |
  Beneficiaries | Budget; objective manager (locked KPI keys + qualitative);
  Records modal (attendance/evaluation XLSX import, v4.13 — generated
  templates, parse → preview → confirm, read-only for faculty); enroll
  existing / register new
  (dedup warn + confirm) / unenroll; budget entries w/ live D7 warning;
  `?program=EXT-2026-00X` deep links; `?role=faculty` renders READ-ONLY
  (admin-only elements hidden via `.admin-only` + `data-variant` CSS)
- `programs.html` — list + Admin New Program modal (draft status) + `cancelled` status
- `communities.html`, `faculty-management.html` (credentials + soft delete),
- `proposals.html` (+proposal-new.html) — range validation BLOCKS approval (8.8),
  auto-create Activity, transition log, Special Order attach + absence notice
- `availability.html` — admin-initiated, accept/decline (required reason), overlap hard-block
- `assessment-form.html` — Sections I–IX wizard + XLSX import (template download,
  preview, auto-map to Other, per-field error list, confirm→pending)
- `assessment-review.html` — secretary queue, reviewer stamps
- `rendered-hours.html` — dual variant: admin approval queue (reject requires
  remarks) / faculty My Rendered Hours (adjust down only, approved locked)
- `calendar.html` — month grid + day panel + 60-day list; events derived from
  activities + ACCEPTED availability + objective deadlines; computed conflict markers
- `reports.html` — four print views (Annual I–VII, Results Framework w/ evidence,
  Rendered Hours per activity/program, Community Partner Impact)
- `ai-analysis.html` — insights workspace: priority needs, interventions, history &
  pipeline states (draft/approved/failed+retry/pending)
- `program-narratives.html` — per-program narratives, objectives-met, version history,
  "Narrative unavailable" first-class state
- Redirect stubs: activities/beneficiaries/budget.html → analytics or programs

Demo conventions: `?role=` switches role shell (layout.js NAV); `?program=` deep-links
the hub; footer says v4.1. In-page demo data that seed lacks (faculty assignments
map, attendance state) is hard-coded per-page and marked as demo.

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

## 10. VERIFICATION STATUS (prototype)

- 29/29 inline scripts pass `node --check`; `seed-data.js` + `layout.js` re-checked
  after the 2026-09-14 v4.5 mirror edits (El Reposo/Salvacion → Tacloban City,
  HANDA goal/venue strings, nav label).
- Zero AI strings on secretary surfaces; AI pages are admin-only.
- All KPI keys come from the locked 8.6 set; `beneficiaries_reached` purged.
- Budget entries sum exactly to program utilized (HANDA deliberately over-allocated
  by ₱2,000 to demo D7).
- No links to retired standalone pages; nav has Analytics; footers say v4.1.
- KNOWN DRIFT (accepted 2026-09-14): `communities.html` still shows the card
  grid; the Laravel page is now a list view w/ partner-schools type filter.

## 11. OPERATING RULES FOR THE NEXT AGENT

1. The blueprint is the contract; when UI and blueprint disagree, blueprint wins —
   then update the prototype page to match.
2. Never invent data that contradicts `seed-data.js` or §7 vocabularies.
3. Every change to vocabularies, models, or workflows must be reflected in BOTH the
   blueprint (bump revision history) and the prototype.
4. Security posture: role middleware on every route/Livewire action; policies on all
   controllers; upload validation (10 MB, MIME+extension); login throttling; Spatie
   activity log on sensitive actions; DPA — aggregates only to the LLM; Admin-only AI.
5. Do not add features listed in blueprint §1.2 (out of scope): OCR, extra XLSX
   imports, partner portal, PDF/Excel export, predictive analytics, dark mode,
   ERP/HR integration, mobile, multi-role accounts.
6. After any prototype edit: re-run `node --check` on inline scripts and re-grep for
   forbidden strings (secretary AI, beneficiaries_reached, retired page links).

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

Credentials — all six seeded accounts use password `password`:

| Role | Email | Name |
|---|---|---|
| Admin (Director) | admin@lnu.com | Dr. Lowell A. Quisumbing |
| Secretary | secretary@lnu.com | Jhoanna Ayles |
| Faculty | faculty1@lnu.com | Carlo Sumile |
| Faculty | faculty2@lnu.com | Bianca Oledan |
| Faculty | faculty3@lnu.com | Nikko Villas |
| Faculty | faculty4@lnu.com | Kent Naputo |

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
- Tests: `php artisan test` (222 passing / 0 failures, 1082 assertions as of
  2026-09-17 session 15; prior counts: 198 total session 14 → 192 session 13 →
  188 session 12 → 179 session 11 → 175 session 10 → 163 session 9 → 149
  session 8 → 131 session 7 → 128 session 6 → 126 session 5 → 117 session 4 →
  107 session 3 → 103 session 2 → 94 session 1). Style: `vendor/bin/pint app tests`.
  Manual end-to-end walkthrough (program → objectives → activities →
  attendance → budget with expected results per step): `docs/TEST-SCRIPT.md`.
  Full presentation demo script (~30 min, role-based acts, seeded-data
  driven, fallback plans + panel Q&A table): `docs/features.md`.
  Tests run on sqlite :memory: — never use raw MySQL-only SQL in app code that
  tests exercise (see §14).
- Git: repo initialized but NOTHING committed yet — make an initial commit
  before starting new work (never commit `.env` / `GEMINI_API_KEY`).
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
- **Chart.js canvases**: guard re-inits with
  `if (Chart.getChart($refs.c)) Chart.getChart($refs.c).destroy();` before
  `new Chart(...)` — morphs can reuse the canvas and a second init throws
  "Canvas is already in use", aborting Alpine init for the subtree.
- Eloquent attribute/relation shadowing: `ExtensionProgram` has an `objectives`
  TEXT column → the ProgramObjective relation is `programObjectives()`;
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

END OF HANDOFF — session 15 closed out v4.13 (activity imports: tests + docs + prototype mirror; all green at 222 tests / 0 failures). NEXT: Phase 6 (hardening: production deploy, nightly backup script, security pass, docs). Set GEMINI_API_KEY before any AI demo.