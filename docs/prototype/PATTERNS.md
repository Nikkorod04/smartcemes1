# SmartCEMES Prototype — Page Building Patterns (MUST FOLLOW)

Read `pages/dashboard-admin.html` first — it is the golden exemplar. Every page must feel like the same app.

> v3.4 additions: availability is now ADMIN-INITIATED requests (faculty accept/decline), activities carry start/end times with conflict hard-blocking, calendar has 3 feeds + red conflict markers, program narratives (Director-only) and program objectives (baseline/target/actual/status) are new, and assessment-form supports XLSX import with a preview-and-confirm modal. See `assets/css/smartcemes.css` for the shared components below; seed data lives in `assets/js/seed-data.js` (availabilityRequests, programObjectives, programNarratives).
>
> v4.1 (blueprint-conformant) rules: there are NO standalone Activities / Beneficiaries / Budget pages — those live inside the program hub tabs (`program-detail.html`: Overview | Activities | Beneficiaries | Budget); cross-program aggregates live on the admin dashboard (`dashboard-admin.html`) and in the print reports (`reports.html`) — the six-tab Analytics page was removed by owner decision 2026-09-27. AI surfaces exist ONLY on admin pages (admin dashboard AI panel, ai-analysis.html, program-narratives.html) — never on secretary/faculty pages. AI pages must render first-class pending/failed states ("analysis unavailable" / "narrative unavailable").
>
> **v4.2 (adviser revision) rules — supersede v4.1 where they conflict:**
> - **Hierarchy is `College → Program → Project → Activity`.** `DATA.programs` is the **broad** thematic program list (codes `PROG-*`: LITERACY, ICE, CULTURE, SPORTS, LIVELIHOOD, ENVIRONMENT). `DATA.projects` is the **project** list (codes `CAS-2026-00X` / `CME-2026-00X` / `COE-2026-00X`). Never alias the two. A legacy code (`EXT-2026-00X`) is kept on each project as `legacyCode` for old deep links; resolve with `D.projects.find(p => p.code === c || p.legacyCode === c)`.
> - **The 8.6 KPI dictionary is REMOVED from the project view (D-R7).** A project is measured only on: trainors, trainees (beneficiaries), training hours rendered, budget, and activities. Do not reintroduce `participation_rate` / `knowledge_gain` / `cost_per_beneficiary` / `community_reach` etc. Objectives keep a plain `baseline → target → actual` triple with `kpi:null`.
> - **Training hours (D-R3):** `trainors × trainees × days` — **NO hourly factor**. ⚠️ This line originally
>   read `× 8`; that factor was REMOVED (see **§8 below**, which supersedes it — `days` already carries the
>   duration, so `× 8` double-counted it). A half day is `0.5` day. Trainors auto-count from assigned
>   faculty; trainees come from actual attendance.
> - **Training-hour targets exist at University and Project level only (D-R5)** — `DATA.universityTargets` (year-keyed) and `project.trainingHoursTarget`. **Budget has NO target at any level** (owner decision 2026-09-26): `project.budgetAllocated` is the only budget figure, so it is what utilization is measured against. Program-level targets are *derived*, never entered. Page: `targets.html`.
> - **AI scope guardrail (D-R8/D-R10) is three-tier:** tier 1 = CESO-deliverable (social / economic / environmental only); tier 2 = **interagency referral** naming the responsible agency from `DATA.interagencyAgencies`; tier 3 = suppressed, never surfaced as a CESO recommendation. Page: `interagency.html` (admin-editable catalogue). Render tiers with `SC.tierBadge()` / `SC.tierMeta()` and the `.tier-badge` / `.tier-card` classes.
> - **No KAHAYAG branding in any UI (D-R11).**
> - Helper contract on `window.SC`: `num, hours, money, pct, collegePill, pillarChip, tierBadge, tierMeta, collegeMeta, hoursFormula, attainTone, attainBadge, svg, icons, toast`.
> - `index.html` at the prototype root is the role-picker / change-summary entry page; `pages/targets.html` and `pages/interagency.html` are required by the nav.
>
> **v4.3 (collapsed hierarchy nav) rules:**
> - **The admin sidebar exposes exactly ONE extension-structure entry: `Manage Extension Programs`** (`icon:'folder'`, `badge:3`, `href:'colleges.html'`, section `Extension Programs`). `Colleges`, `Extension Programs` and `Extension Projects` must NOT appear as separate sidebar items — the whole `College → Program → Project → Activity` chain is navigated from inside the hub.
> - **`Communities & Partner Schools` lives under the `Management` section**, next to `Faculty Management` — it is not part of the hierarchy hub.
> - The hub entry declares `subs:['colleges','programs','projects','program-detail']`. `layout.js` `isActive(it)` lights the entry for any of those keys, and `pageTitle()` resolves the *parent* label for sub-pages. `program-detail.html` therefore sets `data-page="program-detail"`, not `data-page="programs"`.
> - Every page in the chain carries a **drill-down rail** at the top of `<main>`:
>   ```html
>   <div class="sc-card p-4">
>     <div class="flex items-center gap-2 flex-wrap">
>       <span class="text-[10.5px] font-bold uppercase tracking-[.12em] text-gray-400 mr-1">Manage Extension Programs</span>
>       <span class="text-gray-300">›</span>
>       <a href="colleges.html?role=admin" class="hier-node"><span class="hier-step">1</span> College</a>
>       <span class="text-gray-300 font-bold">→</span>
>       <a href="programs.html?role=admin" class="hier-node"><span class="hier-step">2</span> Program</a>
>       <span class="text-gray-300 font-bold">→</span>
>       <a href="projects.html?role=admin" class="hier-node"><span class="hier-step">3</span> Project</a>
>       <span class="text-gray-300 font-bold">→</span>
>       <a href="projects.html?role=admin" class="hier-node"><span class="hier-step">4</span> Activity</a>
>     </div>
>   </div>
>   ```
>   The level current on that page gets the `on` class **and** a trailing ` <span class="text-[10.5px] font-normal opacity-70">(this page)</span>`. `_check.cjs` asserts all four levels exist and that the current one is marked + highlighted.
> - Hub entry uses the `chevron` icon (declared in `ICONS`) rendered via `.nav-chevron` to signal drill-down.
>
> **v4.4 (icon + encoding correctness) rules — read before rendering any icon:**
> - **`SC.svg(name, cls)` sizing contract.** The helper emits a `w-5 h-5` default **only when `cls` contains no width/height class**. This matters because Tailwind orders `.w-5` *before* `.w-3.5` in its utility layer, so emitting both made the 20px default silently win and every "small" icon rendered oversized. Pass a size whenever you want something other than 20px:
>   - `SC.svg('check')` → 20px (default)
>   - `SC.svg('check', 'w-4 h-4 text-lnu-600')` → 16px ✅
>   - `SC.svg('check', 'text-red-500')` → 20px (colour only, size still defaults)
>   - Always pass **both** `w-` and `h-`. The harness rejects a lone one.
> - **`[[icon]]` tokens work anywhere, including JS template literals.** `boot()` expands once at `DOMContentLoaded`, and `watchIconTokens()` installs a `MutationObserver` that expands tokens in any node injected later (render functions, innerHTML assignments, toast bodies). You do **not** need to call `SC.expandIcons()` manually — but it is exported if you ever render into a detached tree.
> - **Never hand-write an `<span>`-sized icon.** Use `SC.svg()` or a `[[token]]`; both funnel through the one sizing rule above.
> - **Encoding: save all files as UTF-8 *without* BOM.** A BOM before `<!DOCTYPE>` or before the leading IIFE breaks strict parsing. Typographic characters (`· — – “ ” … × → ✓`) are correct as literal UTF-8; if they appear as `Â·` / `â€“` / `â€¦` the file was double-encoded. `_check.cjs` detects this by re-encoding cp1252-mappable runs and testing whether the result is valid UTF-8.

## 1. Page skeleton (exact)

```html
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PAGE NAME · SmartCEMES</title>
<script src="../assets/vendor/tailwind.js"></script>
<script src="../assets/js/tw-config.js"></script>
<link rel="stylesheet" href="../assets/css/smartcemes.css">
</head>
<body class="bg-gray-50 text-charcoal font-sans antialiased" data-role="ROLE" data-page="PAGE-KEY">

<div id="sc-topbar"></div>
<div id="sc-sidebar"></div>

<main class="pl-72 pr-6 pb-12">
  <!-- page header -->
  <section class="pt-6 reveal-item"> ... </section>
  ...
  <footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">SmartCEMES v4.1 Prototype · Community Extension Services Office · Leyte Normal University</footer>
</main>

<script src="../assets/vendor/alpine.min.js" defer></script>
<script src="../assets/vendor/chart.umd.min.js"></script>   <!-- only if charts used -->
<script src="../assets/js/seed-data.js"></script>
<script src="../assets/js/layout.js"></script>
<script> /* page logic */ </script>
</body>
</html>
```

## 2. data-role / data-page values (must match nav keys exactly)
- admin: dashboard-admin, targets, calendar, **manage-programs hub → colleges / programs / projects / program-detail**, communities, faculty-management, proposals, availability, rendered-hours, ai-analysis, program-narratives, interagency, reports
- secretary: dashboard-secretary, assessment-review, compliance, calendar
- faculty: dashboard-faculty, my-programs, proposals, proposal-new, assessment-form, availability, rendered-hours, calendar

**Sidebar keys vs. page keys:** the hub is the only admin entry that aggregates keys — `subs:['colleges','programs','projects','program-detail']`. `colleges.html` is the hub landing page (`data-page="colleges"`), and the other three keep their own key. A page whose key is not listed verbatim falls back to `document.title.split('·')[0]`.

Shared pages (proposals.html, availability.html, rendered-hours.html, calendar.html): body data-role switches per role; prepare two small variant blocks and toggle with vanilla JS on `document.body.dataset.role`. `program-detail.html` is also variant-aware: `data-variant="faculty"` renders the hub READ-ONLY (all admin action buttons hidden via the `admin-only` class).

## 3. Icons — inline tokens anywhere in HTML text
`[[grid]] [[users]] [[folder]] [[calendar]] [[pin]] [[people]] [[doc]] [[check]] [[clock]] [[wallet]] [[chart]] [[clipboard]] [[sparkles]] [[shield]] [[bell]] [[logout]] [[search]] [[download]] [[upload]] [[eye]] [[chevron]]`
Wrap in a colored chip like the exemplar KPI cards. layout.js expands tokens automatically — both at boot and in any markup injected later. For anything other than the 20px default, call `SC.svg(name, 'w-4 h-4 …')` instead of writing a token (see the v4.4 sizing contract above).

## 4. Components (classes from smartcemes.css — do NOT reinvent)
- Card: `sc-card` (+ hover lift: add `sc-card-hover`)
- KPI card: copy exemplar pattern (chip icon + trend badge + big count-up number + label). Count-up: `<span data-count="653">0</span>`; prefix/suffix via `data-prefix="₱"` `data-suffix="%"`
- Buttons: `.btn .btn-primary | btn-secondary | btn-outline | btn-ghost | btn-danger-soft | btn-success-soft`; sizes via `!px-2.5 !py-1.5 text-[11px]`
- Badges: `.badge` + `badge-green|badge-yellow|badge-red|badge-blue|badge-gold|badge-gray`
  Status map: Ongoing/Approved/Validated/Active/Completed→green · Pending/Draft→yellow · Rejected/Returned/Cancelled/Failed→red · Upcoming/informational→blue · Prospecting/neutral→gray · Special/gold accents→gold
- Table: `<table class="sc-table">` inside `sc-card p-0 overflow-hidden`. Row hover reveals actions via `.row-actions` wrapper.
- Forms: `.input`, `.label`, chips multi-select: `.chip` toggling class `.on`
- Progress: `<div class="progress"><span style="width:64%" class="bg-lnu"></span></div>`
- Tabs: `.tab` / `.tab.on`
- Wizard steps: `.step-dot` (.on/.done) + `.step-line` (.done)
- AI panel: `.ai-panel` dark blue gradient + `.ai-chip`
- Avatar: `.avatar` (+ `.gold`)
- Toasts: `SC.toast('msg','success'|'info'|'warn'|'error')`
- Animations: put `reveal-item` on top-level sections/cards; layout.js auto-staggers them.

## 5. Alpine patterns (Alpine is loaded; keep it minimal and consistent)

Dropdown/modal/tab template:
```html
<div x-data="{open:false}">
  <button @click="open=true" class="btn btn-primary">Open</button>
  <div x-cloak x-show="open" x-transition.opacity.duration.150ms class="fixed inset-0 z-50 p-6 overflow-auto no-print" style="display:none">
    <div class="fixed inset-0 bg-charcoal/45 backdrop-blur-[2px]" @click="open=false"></div>
    <div class="sc-modal relative max-w-xl mx-auto mt-20 sc-card p-6 shadow-pop"> ...content... </div>
  </div>
</div>
```
Tabs:
```html
<div x-data="{t:'a'}" class="flex gap-1 bg-white rounded-xl border border-gray-100 p-1 w-max">
  <button :class="t==='a' ? 'tab on' : 'tab'" @click="t='a'">Tab A</button>
  ...
</div>
```

## 6. Content rules
- Realistic Philippine/Leyte context only; pull records from `window.DATA` (seed-data.js) where sensible; you may hardcode additional rows in-page when DATA lacks fields — but NEVER contradict seed values.
- Currency ₱ with `SC.money()`, dates like "Aug 24, 2026", peso amounts realistic (₱15k–₱80k programs).
- English UI copy, concise professional tone.
- Every list page needs: search input (filters rows client-side), at least one working filter tab or status dropdown, row click → detail modal/drawer, primary action button → SC.toast feedback.
- Objective status derivation (display only): achieved (actual ≥ target) · not_met (target_date passed, actual < target) · on_track (progressing, date not passed) · not_started (no data yet). Numeric objective objectives derive actuals live; qualitative use manual actual + evidence.
- Admin-only UI elements get the `admin-only` class; role/variant switching hides them (`body[data-variant="faculty"] .admin-only{display:none!important}`).
- AI generation surfaces must include a first-class failure state card (`analysis/narrative unavailable — retry`) and a pending state; never a silent error.
- Charts: use palette ['#003599','#F6B800','#2547eb','#93b4fd','#fdd24a'], borderRadius:6–7, maintainAspectRatio:false, legend bottom or hidden. Wrap canvas in `relative h-56/h-60`.
- NO external URLs, NO CDNs, NO comments explaining Tailwind basics, NO dark mode.
- Deep-link contract: `program-detail.html?program=EXT-2026-00X` renders that program's hub; list pages pass the program code on every hub link (alongside `?role=`).

## 7. v4.5 — two-view hub contract + NO drill-down rail

**Rule: the hierarchy is navigated by *cards*, not by a rail.** Do not add a
`College → Program → Project → Activity` step rail to any page — that pattern was removed in P0d
(`colleges.html`, `programs.html`, `projects.html`, `program-detail.html` are all rail-free).
`a.hier-node` / `.hier-step` CSS still exists but is dead; do not use it.

**`colleges.html` is the hub landing page and owns a two-view flow.** It must declare exactly two
view containers, toggled with the `hidden` class:

| Id | Role |
|---|---|
| `#viewColleges` | View 1 — the four college cards **and nothing else** |
| `#viewCollege` | View 2 — one college's projects (+ KPIs, faculty) |

Required ids in view 1: `collegeCards` (grid).
Required ids in view 2: `backBtn`, `collegeHero`, `collegeKpis`, `projHeading`, `projQ`,
`projStatusChips`, `projGrid`, `projEmpty`, `facHeading`, `facGrid`.

Required functions in the page script: `showCollege(code, pushUrl)`, `goBack(pushUrl)`,
`renderProjects()`. `history.pushState` on enter, `popstate` wired so browser Back/Forward
traverses the two views, and `?college=<CODE>` must deep-link straight into view 2.

**View 1 must not leak drill-down content.** No project table, no program table, no faculty grid,
**no university roll-up strip**, and no KPI tiles of any kind. A college is entered *only* by
clicking its card.

**RULE — no per-college training hours.** Training-hours targets exist at **university** and
**project** level **only**; there is no per-college hours target. Therefore:
- College cards show `Projects` / `Programs` / `Faculty` — **never** a `Training hours` bar, and
  must not read `c.hoursPct` / `c.trainingHoursTarget`.
- The college hero's second bar is **Beneficiaries reached**, not hours.
- The view-2 KPI tiles are Projects / Trainors / Trainees / **Budget utilized** — no hours tile.
- The **project** card's Training-hours bar *is* legitimate (project-level targets are real) —
  keep it. Exactly **one** `Training hours` label may exist on the page.
Both `_check.cjs` and `_hubtest.cjs` guard this rule.

Card markup (classes must come from `smartcemes.css`):
```html
<button class="college-card" data-code="CAS">
  <span class="college-media" style="background:${c.color}">   <!-- logo area -->
    <span class="college-logo-ph">Logo</span>                  <!-- placeholder marker -->
    <span class="college-crest">CAS</span>
  </span>
  <span class="block px-6 pt-5 pb-6 flex-1 text-left">
    <span class="block font-extrabold text-[17px]">College of Arts and Sciences</span>
    <span class="block text-[11.5px] text-gray-400">Dean · …</span>
    <span class="block text-[12.5px] text-gray-500 min-h-[54px]">…thrust…</span>
    <span class="college-metrics"><span><span class="m-val">5</span><span class="m-cap">Projects</span></span>…</span>
    <span class="flex flex-wrap gap-1.5 mt-4 min-h-[24px]">…badges…</span>
  </span>
  <span class="college-foot">…reached/utilized… <span class="college-card-cta">Open projects →</span></span>
</button>
```

Project cards use `.proj-card` + `.proj-card-stripe` + `.proj-stat` tiles
(trainors / trainees / activities) and link to
`program-detail.html?role=admin&program=<code>`.

College/project cards are `<button>` elements (keyboard-reachable), never bare `<div>`s.

### 7.1 College card design rules (v4.10, P0l)

The four college cards are the page's whole purpose, so they carry the most design weight.

- **No page header on view 1.** The view opens straight into the cards. The sidebar already names the
  entry ("Manage Extension Programs"), and the section eyebrow ("Colleges") plus the `h3` carry the
  context. Guarded: `_check.cjs` and `_hubtest.cjs` ban `Manage Extension Programs</h2>` and the old
  sub-heading from the page.
- **A card opens with a logo/media area, not a hairline strip.** `.college-media` is a fixed **116px**
  brand-tinted band holding the crest (`.college-crest`, now a translucent disc via
  `rgba(255,255,255,.18)` + border, *not* a solid brand fill) and a `Logo` placeholder chip
  (`.college-logo-ph`). The `.college-media::after` gradient adds a diagonal sheen so a flat brand
  colour does not read as a plain block. **When real logos arrive, swap the crest + chip for an
  `<img>`; keep the 116px height so card heights stay uniform.**
- **Height balance is deliberate, not incidental.** `.college-media` is fixed-height, and the thrust
  paragraph (`min-h-[54px]`) and badge row (`min-h-[24px]`) reserve space. The badge row is *always*
  rendered — an empty one still occupies 24px — so a college with fewer programs cannot produce a
  shorter card. Without these three, the three cards visibly desynchronise and their footer strips stop
  aligning. Guarded by `_check.cjs`.
- **Visual hierarchy:** media → name (17px extrabold) → dean (11.5px gray) → thrust (12.5px) → metrics
  → badges → footer. Padding is `px-6 pt-5 pb-6` in the body and `p-5` in the footer; keep them
  consistent across all three cards.
- **Shadow:** cards rest on a two-layer shadow (`0 1px 2px` + `0 8px 24px -18px`) and lift to a deeper
  one on hover, paired with a `-4px` translate. Radius is **20px**.
- The old `.college-card-top` hairline class still exists in the CSS but is **no longer used** — treat
  it as dead, and do not reintroduce it.
- A dead leftover from the removed budget bar (`const bp = c.budgetTarget ? …`) was deleted;
  `_check.cjs` fails if it returns. **When you remove a bar or a tile from this page, grep the map
  callback for the constants that fed it.**

## 8. v4.6 — training-hours formula & target model (AMENDED)

**RULE — the formula has NO hourly factor.**

```
TRAINING_HOURS = trainors × trainees × days
```

Do **not** multiply by 8. `days` already carries the duration — the earlier `× 8` double-counted it.
The half-day option lives in `days` (0.5 = half day), not in an hourly multiplier.

```
2 trainors × 112 trainees × 0.5 day  =  112 hrs      (NOT 896)
3 trainors × 141 trainees × 1   day  =  423 hrs      (NOT 3,384)
```

`_check.cjs` enforces this: no source may contain `× 8 hrs` / `x8 hours`, no code may multiply a
training-hours expression by 8, the canonical string must be present, every seeded activity must
satisfy the formula arithmetically, and the project rollup must equal the completed-activity sum.

**RULE — targets live at UNIVERSITY and PROJECT level only.**

- **One annual training-hours target** for the whole of SmartCEMES (a single Director-set number
  per year). This is the **sole** input to the annual target.
- **A target *and* an actual per PROJECT** — derived from that project's activities.
- Each project's **actual** hours are **subtracted from the annual target**:
  `annual_remaining = annual_target − Σ(project.actual_hours)`. This is a **consumption/draw-down**
  model, not a ratio. The project `target` is a planning figure for that project and is **not**
  summed to produce the annual target.
- **Broad programs carry NO target at all.** Never key a target-vs-actual surface off `DATA.programs`;
  use `DATA.projects`. (The dashboard's hours chart was wrong on this and was corrected.)

**RULE — the admin dashboard is a performance screen.** The Director must be able to see, at a
glance: how work is progressing, which projects are performing, which are lagging, and which faculty
are carrying the load. Keep the KPI row to four cards (Extension Projects · Training Hours Rendered ·
Budget Utilized · Pending Approvals). Do **not** add reach/volume cards such as
"Trainees / Beneficiaries Served" — reach is visible elsewhere and dilutes the screen. The
**Performance Leaders** section (Most Performing Projects + Most Performing Faculty) is a
first-class part of the dashboard, not an optional extra.

## 9. Verification harnesses (run all five before calling a prototype pass done)

```
node docs/prototype/_check.cjs       # data + structure + contracts  → "OK — all checks passed."
node docs/prototype/_smoke.cjs       # every page's scripts execute   → "All pages execute cleanly."
node docs/prototype/_hubtest.cjs     # the colleges hub click-through → "PASS — N assertions"
node docs/prototype/_facultytest.cjs # the faculty engagement board  → "PASS — N assertions"
node docs/prototype/_dashtest.cjs    # the admin dashboard           → "PASS — N assertions"
```

`_check.cjs` only proves strings *exist* and `_smoke.cjs` only proves a page *loads*. Neither proves
behaviour. So every page with real interaction gets a **functional harness**:

**Both functional harnesses share `_shim.cjs`** — the mini-DOM, `makeDoc`, `deepObj` and the `boot()`
helper live there once, and each harness does `const { boot } = require('./_shim.cjs')`. If you need to
change shim behaviour, change it in `_shim.cjs`; do not re-fork it into a harness.

- `_hubtest.cjs` — builds a mini-DOM with real event dispatch, renders view 1 (asserting 3 college
  cards, the logo/media area + placeholder, the removed header banner, and no leakage), clicks CAS,
  asserts the view swap and that only CAS's projects render, clicks back, then loads `?college=CME`.
  **37 assertions.** Note: `#viewColleges` is a *stub* element in this harness — only its `classList`
  is exercised, so its `innerHTML` is always empty. Assert anything about view-1 *markup* against the
  page source (`html`), not `byId['viewColleges'].innerHTML`.
- `_facultytest.cjs` — uses the shared `boot()`. It installs a **Chart.js capture stub** (records each
  config object so assertions can inspect `data`/`labels`/`options` rather than just "a chart was
  made"), and can **force** a branch by string-replacing the source before evaluation
  (`{ forceMetric: 'leads' }`) and can boot a page with a **query string** (`{ search: '?college=CME' }`).
  Six suites: (1) the engagement board, (2) the profile modal contract, (3) the directory page +
  faculty self-edit view, (3c) the board-only contract for `faculty-management.html`, (4) the entry
  point, (5) the colleges faculty card. **124 assertions.**
- `_dashtest.cjs` — the admin dashboard, which previously had **no functional harness at all**. Using
  the shared `boot()`, it asserts: exactly two charts are constructed and the removed third is gone;
  each surviving chart reads *project-level* targets and labels its axis correctly; the budget bars are
  coloured red **iff** `utilized > budgetAllocated` (assert the *mapping*, not the presence of red — the
  seed contains no over-target project, so red is legitimately absent); the Action Center is gone with
  no dead wiring; the shared layout primitives are applied; and both leaderboards render 5 rows.
  **39 assertions.**

When you add interactive behaviour to a page, add or extend its functional harness in the same pass.
**A page with no functional harness is a page whose behaviour is unverified** — the dashboard sat in
that state until P0m, and nothing would have caught a chart bound to the wrong canvas.

When asserting on **data-dependent** output, assert the *rule*, not a sampled value. The over-target
red is the cautionary example: `colors.some(c => c === '#ef4444')` fails on today's seed and would
pass the moment someone adds an over-budget project — it tests the fixture, not the code. Asserting
`colors.every((c, i) => used[i] > planned[i] ? c === '#ef4444' : c !== '#ef4444')` tests the code.

**Some guards only `_smoke.cjs` can catch.** `_check.cjs` asserts on *source text*, so it happily
passes a page that references a constant you just deleted along with the block that defined it.
Deleting a section is a **two-part** change: remove the markup/JS *and* verify nothing else consumed
the variables it introduced. When in doubt, run `_smoke.cjs` — it evaluates the page for real.

**Orphan sweep — do this after every block deletion.** Five separate bugs on this prototype came from
  deleting a block and leaving something behind: `totHours` (P0i, caught by `_smoke.cjs` as a
  `ReferenceError`), the `ico()` helper (P0k, caught by nothing automatic), `const bp` (P0l, caught by
  nothing automatic), and `EMERALD` / `SLATE` (P0m, caught by nothing automatic). After deleting a
  block, grep for:
1. every identifier the block **read** — `_smoke.cjs` will catch these as `ReferenceError`;
2. every identifier that existed **solely to serve** it (helpers, colour constants, local maps) —
   **nothing automated catches these**; only the grep does;
3. **every page that reads a symbol the block owned** — see the mirror rule below.
So: `grep -n "<identifier>" pages/<page>.html` for each name the deleted block mentioned, and add a
`_check.cjs` ban for any that must not return.

**Mirror rule (P0o).** Rule 2 covers things existing *below* the deleted block. The failure mode at P0o
was the opposite direction: the removal targeted the seed symbol `programObjectives`, but that symbol
was **live data read by two other pages** — `calendar.html` (Feed 3, objective deadlines) and
`program-narratives.html` (`objectivesMet` → `omChip`). Deleting it would have silently emptied the
calendar feed and thrown on every narrative card. So before deleting a *symbol* (not a local), run:
```
grep -rn "<symbol>" pages/ assets/
```
and read every hit. Distinguish **the authoring surface** (dead — remove it) from **the data it owns**
(may be live — trace it). At P0o the surface was dead but the data was live, so only the surface went.
Where a symbol must survive, encode that as a `_check.cjs` **requirement** (including its consumer
call-sites), so a future deletion trips a named failure instead of a silent empty render.

**`esc` is not global.** It is defined **inline per page** (e.g. `colleges.html`, `program-detail.html`)
and is not on `window` or `SC`. Using it in a page that does not define it throws a `ReferenceError`.
Before writing `esc(...)`, confirm the page has `const esc =` in its own script; seed-owned literals
need no escaping at all.

Harness gotchas (learned the hard way):
- When dispatching a click, `closest()` must exist on **`event.target`**, not on the event object.
- Count card roots with `class="college-card[ "]`, never the bare `college-card` substring — the
  latter also matches `college-card-top` / `college-card-cta`.
- The page sandbox must stub `window.scrollTo` / `scrollBy` / `setTimeout` / `getComputedStyle`,
  because page scripts call them directly.
- Pages use **bare id references as implicit globals** (`kpis.innerHTML`, `engRows.innerHTML`), which
  a real browser provides. The sandbox must mirror that: `declaredIds.forEach(id => sandbox[id] = ...)`.
- To test a mode/variant, **re-run the page script with the branch forced** (`let metric='pct'`)
  rather than trying to synthesise a click through a hand-rolled DOM. Far more robust.
- `document.body` in the shim needs a **real `dataset` map** seeded from the `<body data-role="…">`
  attribute, and the page's tiny inline role-bootstrap script must actually be executed — otherwise
  every `ROLE === 'faculty'` branch silently takes the admin path and the assertions fail confusingly.
- When asserting on a phrase a page mentions in a *comment* (e.g. "no Specialization field"), match on
  the rendered markup (`<input id="afSpec` / `<label …>Specialization`), not the bare word — prose
  about a removed field is not the removed field.
- To assert that a JS render call is gone, anchor the regex (`/^\s*render\(\);/m`) — a bare
  `/render\(\)/` also matches `renderEngagement()` and `renderDirectory()`.
- Save files as UTF-8 **without BOM**; `_check.cjs` scans HTML + JS + CSS for BOM and mojibake.

## 10. v4.7 — Faculty Engagement board (faculty-management.html)

**Rule: rank with a chart + leaderboard, not a wide table.** The `Most Active Faculty` table was
removed in P0g. Do not reintroduce `rankBody` / `rank-medal` ranking tables — `_check.cjs` asserts
they are absent from `faculty-management.html`.

The board is a **metric-driven** component: ONE `METRICS` table
(`{ label, sub, value, fmt }` per metric) feeds the chart, the leaderboard *and* the insight notes,
so all three stay in lockstep. Adding a metric is a one-line change plus a switch button.

Required anchors: `engSwitch`, `engChart`, `engChartTitle`, `engChartSub`, `engChartMeta`,
`engRows`, `engSplit`, `engSplitLegend`, `engInsights`.

Required behaviour:
- Horizontal bars (`indexAxis:'y'`), one per faculty, coloured by **college brand colour**
  (`CAS #003599`, `COE #F6B800`, `CME #10b981`). The `COLORS` map is guarded — do not drift from it.
- **One series in every mode.** There is no target line any more (see §12) — `_facultytest.cjs`
  asserts `datasets.length === 1` for every metric.
- Clicking a **bar** or a **leaderboard row** opens the faculty drawer via `openFaculty(id)`.
- Faculty `status === 'On Leave'` is visually muted so active load reads cleanly.
- The insight notes are **computed from the data**, never hardcoded prose.
- Load **`assets/vendor/chart.umd.min.js`** — never a CDN.

## 11. v4.9 — faculty-management.html is BOARD-ONLY

`faculty-management.html` is **one screen with one job**: the Faculty Engagement board. Everything
else that used to share the page now lives in `faculty-directory.html`.

**Removed in P0i — do not reinstate on this page:**

| Removed | Why | Where it lives now |
|---|---|---|
| the page header `<h2>Faculty Management</h2>` + its sub-heading paragraph | redundant — the sidebar already names the page, and the board's own card header states the title | the board's card header (`#heading`, "Faculty Engagement") |
| the 4-card KPI grid (`#kpis`) | the board already answers the same question, better | `faculty-directory.html` `#summary` (4 cards) |
| the filter toolbar (`#facSearch`, `#collegeTabs`, `#expertiseSel`, `#statusSel`, `#sortSel`) | filters belong with the roster they filter | `faculty-directory.html` toolbar |
| the **Faculty Roster** table (`#facultyBody`, `#facCount`, `#facEmpty`) | it duplicated the directory page, which the header action opens | `faculty-directory.html` card grid |

The page keeps exactly two things above the footer: the **board card**, and the `#banner` slot that
only appears when `?college=` narrows the board.

#### There is no page action row (P0j)

"Export roster" was removed and **Faculty Directory** moved *into* the board card's header, as the last
child of the right-hand cluster — directly after the `AY 2026–2027` badge:

```html
<div class="flex items-center gap-2.5 flex-wrap">
  <div class="eng-switch" id="engSwitch"> … </div>
  <span class="badge badge-gold">AY 2026–2027</span>
  <button id="btnAdd" class="btn btn-primary !px-3 !py-2 text-[12px]">[[users]] … Faculty Directory</button>
</div>
```

Because that cluster is `flex-wrap`, the button inherits the header's own padding and `gap-2.5` instead
of being hand-positioned, and it wraps gracefully on narrow viewports. Size header-level actions with
the `!px-3 !py-2 text-[12px]` convention (same as `colleges.html`) rather than editing `.btn` globally —
`.btn` is the shared 9px/16px base for every button in the prototype.

Guarded by `_check.cjs`: `btnExport` / `Export roster` / a `PAGE ACTIONS` row are banned, and the
ordering assertion `AY 2026–2027</span>\s*<button id="btnAdd"` pins the button to the right of the
chip. `_facultytest.cjs` additionally asserts no dead `btnExport.onclick` listener survives.

**Consequences:**

- `_check.cjs` fails the build if any of those nine ids, the `Faculty Roster` heading, or the old
  sub-heading string reappear on this page.
- **The `?college=` deep link now narrows the board itself.** One `SCOPE` constant
  (`college === 'All' ? FAC.slice() : FAC.filter(f => f.college === college)`) is read by `ranked()`,
  `drawSplit()` and `drawInsights()`, so the chart, the leaderboard and the insight notes narrow
  *together* and can never disagree. A `banner` explains the narrowing.
  Guarded: `_check.cjs` asserts the `SCOPE` line exists and that `ranked()` no longer ranks
  `FAC.slice()`. `_facultytest.cjs` boots `?college=CME` and asserts the chart, the leaderboard and
  the insights all narrow to the three CME faculty.
- Because the KPI constants were the only consumers of `totHours`/`totProjects`, deleting the cards
  silently broke `drawInsights()`. The insight notes now compute their own `sumHours` locally — when
  you delete a block on this page, grep for the constants it defined before assuming nothing else
  used them. (`_smoke.cjs` catches this; `_check.cjs` alone would not.)

## 12. v4.8 — faculty contribution, the profile modal, and the Faculty Directory

### 12.1 Rule: there is NO per-professor training-hours target

An annual training-hours target exists **once**, university-wide, and is drawn down by **project**
actuals (§8). Individual professors do **not** carry a quota. A faculty member's contribution is read
from exactly two numbers:

1. **total training hours rendered**, and
2. **project involvement** (projects led vs co-led).

Consequences that are enforced, not advisory:

- **No attainment %.** The `pct` metric mode was deleted from the engagement board's switch, along
  with `eng-rail`, `hours-bar` and `attainBadge` on faculty surfaces. `_check.cjs` fails the build if
  `data-metric="pct"` or `f.targetHours` reappears on `faculty-management.html`.
- The KPI strip is now **4 cards** (was 5): Total Faculty · Active · Training Hours Rendered ·
  Avg Projects / Faculty. The old roster-wide "attained %" card is gone — a ratio needs a denominator
  that no longer exists.
- Faculty are ranked by a **contribution score** (`renderedHours + projectCount × 10`), never a ratio.
- `faculty[].targetHours`, `faculty[].spec` and `faculty[].pct` **must not exist in the seed** —
  `_check.cjs` walks every faculty record and fails on any of the three.
- Any prose that says "of N hrs target" / "X% attained" / "hrs remaining" on a faculty surface is a bug.

### 12.2 Rule: `projects × faculty` involvement is hydrated, not hand-written

`seed-data.js` derives, **after** the `projects` array is built (order matters — the faculty array is
declared first):

```js
f.projectList   // [{ code, acr, title, status, college, hours, role:'Lead'|'Co-Lead' }]
f.projectCount  // projectList.length
f.leadCount / f.coLeadCount
f.communityCount
f.initials      // derived from name, stripping Prof./Dr.
f.positionRank  // 1…18, looked up from positionLadder
```

Never hand-maintain a `programs:` count on a faculty record again — it drifts from the projects array
immediately. `_facultytest.cjs` asserts `leadCount + coLeadCount === projectCount` and
`projectCount === projectList.length`.

### 12.3 The New Faculty Profile modal contract

Required fields (ids are asserted by `_check.cjs`):
`afName`, `afEmployeeId` (readonly), `afBirthdate`, `afSex`, `afCivil`, `afCollege`, `afPosition`,
`msExpertise` (the picker), `afEmail`, `afPhone`, `afAge` (readonly, auto-computed), `afAddress`.

**Removed for good — do not reintroduce:**
- `Specialization` — folded into Expertise. There must be no `#afSpec` input and no
  `<label>Specialization</label>`.
- `Target training hours / year` — see §11.1. There must be no `#afTargetHours` input.

**Position** is a grouped `<optgroup>` select built at runtime from `DATA.positionLadder`, not
hardcoded in markup. The ladder is **18 ranks in institutional order, most junior first**:

| # | Series | Ranks |
|---|--------|-------|
| 1–3 | Instructor | I, II, III |
| 4–7 | Assistant Professor | I, II, III, IV |
| 8–12 | Associate Professor | I, II, III, IV, V |
| 13–18 | Professor | I, II, III, IV, V, VI |

Every seeded `position` must resolve to a ladder label (the seed derives `positionRank`); an
off-ladder value is a hard failure. The old hand-written 8-item list (`Associate Professor`,
`Professor I`, …) is explicitly banned.

### 12.4 The expertise multi-select

Replaces the old free-text input. Built on the `.ms` component (`ms-chips` / `ms-picker` /
`ms-options` / `ms-hint` in `smartcemes.css`).

- Options come from **`DATA.expertiseOptions`** (a canonical 24-value vocabulary), so project-to-faculty
  matching stays consistent. Do not hardcode a second list.
- Behaviour: type-to-filter, click to add, chip with `✕` to remove, `Backspace` on an empty input pops
  the last chip, `Esc` closes, clicking outside closes, capped at **5** with a visible warning.
- Every seeded `expertise` value must be a member of `expertiseOptions`.

**Rebuild-the-placeholder gotcha:** `renderChips()` must **create** the placeholder
(`document.createElement('span')` + `className='ms-placeholder'`), not try to re-append a captured
node via `chipsEl.querySelector('#msPlaceholder')`. The captured-node approach returns `null` under
the smoke sandbox and throws `Cannot read properties of null`.

### 12.5 `faculty-directory.html` — the dedicated roster page

Entry point is the header button **labelled "Faculty Directory"** on `faculty-management.html`, which
routes to `faculty-directory.html?role=admin&new=1`. The old inline "Add Faculty" modal is no longer
the header action (`_check.cjs` fails on `Add Faculty</span>`).

The page is **role-aware**:

| | admin | faculty |
|---|---|---|
| Heading | Faculty Directory | My Faculty Profile |
| Sees | full roster grid | own record only |
| Toolbar | search / college tabs / expertise / status / sort / **Add Faculty** | hidden |
| Drawer | read-only profile + *Edit profile* | **self-edit form, auto-opened** |
| Editable | everything | contact + expertise only |

#### No summary card strip, no export action, no header description (P0k)

Three things were removed because the **roster itself** is the content:

| Removed | Why |
|---|---|
| the 4-card `#summary` strip (Faculty on roster / Active / Ranks in use / Expertise areas) | It was a KPI digest sitting directly above the roster that already showed all four numbers per person. Removing it also removes the last consumer of `SC.pct` on this page. |
| the header export action (`#btnExport` — "Export roster" for admin, "Download my details" for faculty) | Its only handler was a demo toast; nothing consumed the export. |
| the header description "The single roster of extension faculty — profiles, declared expertise, and assignment details" | Redundant — the breadcrumb and heading already say it. Replaced with a four-word rider, `Roster of extension faculty`. |

Consequences:

- `#hdrActions` is **retained as an empty container**, and `#heading` / `#subheading` / `#crumb` are
  retained, because `applyRole()` still rewrites them for the faculty role. Deleting the header
  wholesale would silently break "My Faculty Profile".
- Because `renderSummary()` was the only caller of the local `ico()` helper, deleting it left `ico`
  orphaned. It was removed too — **`_check.cjs` now fails if `const ico = ` reappears.** This is the
  same class of bug as the `totHours` trap in §11: when you delete a block, audit everything it
  defined *and* everything defined solely for it.
- `_check.cjs` bans `#summary`, `renderSummary`, `btnExport` / "Export roster" / "Download my details",
  and the old description string on this page.

**The faculty-side self-edit view** (`.self-banner`, `selfEditBody()`) is the answer to "faculty update
their own information". It must:

- expose editable `seEmail`, `sePhone`, `seBirthdate`, `seSex`, `seCivil`, `seAddress` + the
  expertise multi-select;
- render **`Employee ID` / `Position` / `College` / `Employment status` as `.locked-field`** — visibly
  disabled with an "Admin only" affordance, because these are administrator-controlled
  (separation of duties);
- repeat, in the drawer, that contribution is hours + involvement and there is no individual target;
- show a **completeness meter** in the `.self-banner` (`.self-meter`), computed from the fields that
  are actually filled.

`openProfile(id)` picks the body: `ROLE === 'faculty'` → `selfEditBody`, else `adminProfileBody`.
An admin editing still goes through the same self-edit shape (`openEditAsSelf`), so there is exactly
one editable form to maintain.

### 12.6 Nav wiring

`faculty-directory` is added as a **`subs`** entry under `faculty-management` so the sidebar
highlight rule keeps the Management item lit on both pages, and it is also a first-class nav item
under the faculty role (`My Profile → My Faculty Profile`). Both are asserted by `_check.cjs`.

`layout.js` gained three icons: `list`, `pencil`, `lock`.


## 13. v4.11 — admin dashboard layout primitive & the two removed panels

**Removed (P0m) — do not reinstate:**

| Removed | Why |
|---|---|
| the **Community Reach** panel (`#chReach` + its ~20-line chart config) | Duplicated the beneficiary story already told by the project cards and the Performance Leaders. Removing it also drops the last read of the hand-written reach array, which was never wired to the seed. |
| the **Action Center** section (all six tiles) | Every tile linked to a page that already surfaces the same queue (`proposals`, `availability`, `rendered-hours`, `ai-analysis`). Nine links to four destinations. The Pending Approvals KPI tile plus the AI Decision Support section keep the routes reachable. |

**Recent Activity was NOT part of the request and must stay.** It moved from sharing a row with
Community Reach into its own column. `_check.cjs` now fails if `Recent Activity` disappears — a
*removal request is scoped to what it names*, and it is easy to sweep a neighbouring panel away with
the one being deleted.

### Shared layout primitives

The dashboard is the reference implementation of the v4.11 layout language. Every dashboard section
builds from these five classes — **use them rather than ad-hoc `mt-5`/`grid-cols-3`**:

| Class | Role |
|---|---|
| `.dash-section` | vertical rhythm between sections (30px, 26px under 1023px) |
| `.dash-sec-head` + `.dash-sec-eyebrow` + `.dash-sec-title` | section header: eyebrow → title, right-aligned note |
| `.dash-panel` | the card surface (16px radius, two-layer shadow, 20px padding) |
| `.dash-head` + `.dash-head-title` + `.dash-head-link` | uniform title row inside a panel — the right-hand side may be a link, a badge or a legend and the baseline stays put |
| `.dash-kpi*` | KPI tile with fixed internal rhythm (`-top` / `-icon` / `-value` / `-label` / `-foot`) |

Why the KPI tiles use a fixed rhythm: `.dash-kpi-foot { margin-top: auto }` pushes the bar/footnote to
the bottom, so the four tiles' dividers align **even when one caption wraps to two lines** — the same
balance principle as the college cards (§7.1).

### Responsive contract

**Every grid must be breakpoint-prefixed.** `grid-cols-1 sm:grid-cols-2 xl:grid-cols-4` for the KPI row,
`grid-cols-1 lg:grid-cols-3` for content rows. A bare `grid-cols-3` is a bug: at phone width it produces
three unreadable slivers and forces horizontal scroll. `_check.cjs` and `_dashtest.cjs` both ban a bare
`grid-cols-3` / `grid-cols-4` on this page.

### Chart contract (unchanged)

Two charts remain: Training Hours vs Target, and Budget Utilized vs Allocated Budget. Both read
**project-level** figures only — no per-program and no per-college hours target exists (§7, §8). The
hours chart draws target + rendered as two series; the budget chart colours each bar **red iff it is
over its allocation**.

---

## 14. v4.12 — closing the two dangling prototype items (P0n, P0o)

Both items carried as "still open" through P0h → P0m are now closed. Neither required a redesign; both
were cases of the prototype *claiming* something the model had moved past.

### 14.1 P0n — `targets.html` is provisional, and now says so

The final target model is **one** annual training-hours pool drawn down by project actuals (§8 / D-R5).
`targets.html` still presents four parallel target tiles (hours, budget, beneficiaries, activities) —
that rewrite belongs to **R5**, where the real table exists. Until then the page must be *honest*, not
*correct*:

- **The §2.2C banner is required.** An amber "does not yet reflect the final target model" banner with a
  `Pending` badge must stay at the top. `_check.cjs` `[TARGETS]` fails if it is missing.
- **`targetPrograms` / `targetProjects` are banned.** No program-level target exists. The seed must not
  carry these fields and no page may read them.
- **Pool language, not ratio language.** The hours tile states `N hrs of the annual pool still available
  · N drawn down by N projects`. The phrase "hrs remaining this year" is banned — it implied a live
  drawdown the page does not model.
- **AY, never FY.** Rows carry `label: 'AY 2026–2027'`; the header, picker and section subtitle all say
  *academic* year. `fiscal` must not reappear on this page.

The banner's `[[clock]]` icon is deliberate: `clock` + amber is the established pending vocabulary
(`layout.js` toast map, `SC.toast` warn). Do not invent an `[[alert]]` token — `_check.cjs` verifies every
`[[icon]]` token resolves against `SC.icons`.

### 14.2 P0o — objectives: the surface was dead, the data is live

`program-detail.html` had an Objective Manager + Objective form that **could not be reached**:
`window.openObjManager` was defined but called from nowhere. Removed: both modals, all eight functions,
the two orphaned KPI wrappers, and `editObjIdx`.

**But `programObjectives` stays.** `calendar.html` (Feed 3) and `program-narratives.html`
(`objectivesMet` → `omChip`) read it as read-only displays. Consequences now encoded in `_check.cjs`
`[OBJ]`:

- the modals, the eight functions, the seven `of*` field ids and `editObjIdx` are **banned**;
- `const programObjectives = {` and its export line are **required** in the seed;
- the two consumer call-sites (`D.programObjectives` in `calendar.html`, `DATA.programObjectives` in
  `program-narratives.html`) are **required** — so re-deleting the seed trips a named failure instead of
  silently emptying the calendar's deadline feed.

Objectives are therefore **authored nowhere** in the prototype but still **displayed** in two places.
R4 decides whether the concept survives into the Laravel build (R-Q2 soft-deprecates `ProgramObjective`).
