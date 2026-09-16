# SmartCEMES Prototype — Page Building Patterns (MUST FOLLOW)

Read `pages/dashboard-admin.html` first — it is the golden exemplar. Every page must feel like the same app.

> v3.4 additions: availability is now ADMIN-INITIATED requests (faculty accept/decline), activities carry start/end times with conflict hard-blocking, calendar has 3 feeds + red conflict markers, program narratives (Director-only) and program objectives (baseline/target/actual/status) are new, and assessment-form supports XLSX import with a preview-and-confirm modal. See `assets/css/smartcemes.css` for the shared components below; seed data lives in `assets/js/seed-data.js` (availabilityRequests, programObjectives, programNarratives).
>
> v4.1 (blueprint-conformant) rules: there are NO standalone Activities / Beneficiaries / Budget pages — those live inside the program hub tabs (`program-detail.html`: Overview | Activities | Beneficiaries | Budget); cross-program aggregates live in `analytics.html` (six tabs). Program objectives MUST use the locked KPI keys (`participation_rate, activity_completion_rate, attendance_consistency, budget_utilization, knowledge_gain, cost_per_beneficiary, community_reach`) or `kpi:null` for qualitative. AI surfaces exist ONLY on admin pages (admin dashboard AI panel, ai-analysis.html, program-narratives.html) — never on secretary/faculty pages. AI pages must render first-class pending/failed states ("analysis unavailable" / "narrative unavailable").

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
- admin: dashboard-admin, analytics, calendar, faculty-management, programs, communities, proposals, availability, rendered-hours, ai-analysis, program-narratives, reports
- secretary: dashboard-secretary, assessment-review, compliance, calendar
- faculty: dashboard-faculty, my-programs, proposals, proposal-new, assessment-form, availability, rendered-hours, calendar

Shared pages (proposals.html, availability.html, rendered-hours.html, calendar.html): body data-role switches per role; prepare two small variant blocks and toggle with vanilla JS on `document.body.dataset.role`. `program-detail.html` is also variant-aware: `data-variant="faculty"` renders the hub READ-ONLY (all admin action buttons hidden via the `admin-only` class).

## 3. Icons — inline tokens anywhere in HTML text
`[[grid]] [[users]] [[folder]] [[calendar]] [[pin]] [[people]] [[doc]] [[check]] [[clock]] [[wallet]] [[chart]] [[clipboard]] [[sparkles]] [[shield]] [[bell]] [[logout]] [[search]]`
Wrap in a colored chip like the exemplar KPI cards. layout.js expands tokens automatically.

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
