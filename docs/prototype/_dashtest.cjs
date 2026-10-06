/* Functional tests for the admin dashboard (dashboard-admin.html).

   This page had NO functional harness — _check.cjs only proves strings exist
   and _smoke.cjs only proves the page loads without throwing. Neither would
   notice a chart wired to the wrong canvas, a removed section leaving a dead
   render call behind, or a leaderboard reading a field that no longer exists.

   Covers:
     1. The one surviving chart (hours vs target) builds a correct config, reads
        PROJECT-level targets only, and BOTH removed charts (Community Reach and
        the budget grouped bars) are gone.
     2. The Action Center is gone and left no dead render wiring.
     3. Layout primitives are applied consistently (dash-section rhythm,
        dash-panel wrappers, responsive grid breakpoints).
     4. Both leaderboards render one row per entry from the seed.
*/
const { boot } = require('./_shim.cjs');
const fs = require('fs'), path = require('path');
const dir = __dirname;

let pass = 0, fail = 0;
const check = (label, cond) => { if (cond) { console.log('  [ok  ] ' + label); pass++; } else { console.log('  [FAIL] ' + label); fail++; } };

const PAGE = 'dashboard-admin.html';
const html = fs.readFileSync(path.join(dir, 'pages', PAGE), 'utf8');

console.log('\n=== 1. charts ===');
const D = boot(PAGE, { marker: 'chHours' });
if (D.err) { console.log('  [FAIL] page script threw — ' + D.err.message); fail++; }
const charts = D.charts;
const DATA = D.sandbox.DATA;

check('exactly ONE chart is constructed (Community Reach and the budget bars removed)',
  charts.length === 1);
check('no chart is bound to a #chReach canvas',
  !/chReach/.test(D.pageScript) && !/chReach/.test(html));
check('the removed chart left no dead canvas in the markup',
  !/id="chReach"/.test(html));

/* chart 0 — Training Hours vs Target */
const chH = charts[0];
check('hours chart has 2 series (target + rendered)',
  chH && chH.data.datasets.length === 2);
check('hours chart is per PROJECT (one bar pair per project)',
  chH && chH.data.labels.length === DATA.projects.length);
check('hours chart reads project-level trainingHoursTarget',
  chH && chH.data.datasets[0].data.join(',') ===
    DATA.projects.map(p => p.trainingHoursTarget).join(','));
check('hours chart reads renderedTrainingHours',
  chH && chH.data.datasets[1].data.join(',') ===
    DATA.projects.map(p => p.renderedTrainingHours).join(','));
check('hours chart y-axis is labelled in hrs',
  chH && /hrs/.test(chH.options.scales.y.ticks.callback(1000)));
check('hours chart stays non-responsive-height (maintainAspectRatio:false)',
  chH && chH.options.maintainAspectRatio === false);

/* Budget Utilized vs Annual Target — COMPACT BULLET ROWS (owner request
   2026-09-25). The grouped bar chart could only label its axis with project
   CODES, and a vertical axis has no room for a title, so it was replaced. */
const budgetRows = D.doc._byId['budgetRows'];
const budgetHtml = (budgetRows && budgetRows.innerHTML) || '';
check('the budget chart canvas is gone from the markup',
  !/id="chBudget"/.test(html));
check('budget rows rendered', budgetHtml.length > 200);
check('one bullet row per project',
  (budgetHtml.match(/class="bullet-track/g) || []).length === DATA.projects.length);
check('every row is labelled with the project TITLE, not its code',
  DATA.projects.every(p => budgetHtml.includes(p.title.split(':')[0])));
check('the 100% tick renders on every row',
  (budgetHtml.match(/class="bullet-tick"/g) || []).length === DATA.projects.length);
check('the amounts sit inline on every row',
  (budgetHtml.match(/tabular-nums/g) || []).length === DATA.projects.length);
/* Red is the OVER-ALLOCATION signal. Whether it appears depends on the seed, so
   assert the MAPPING rather than the presence of red: a row carries `is-over`
   iff its utilized exceeds its allocation. */
{
  const used = p => DATA.budgetEntries.filter(e => e.program === p.code).reduce((s, e) => s + e.amount, 0);
  const overCount = DATA.projects.filter(p => used(p) > p.budgetAllocated).length;
  check('a row is marked is-over iff it is over its allocation',
    (budgetHtml.match(/bullet-fill is-over/g) || []).length === overCount);
}

/* regression guard: NO chart may reference a per-program hours target */
check('no chart reads a per-program or per-college hours target',
  !/trainingHoursTarget/.test(html.replace(/p\.trainingHoursTarget/g, '')) &&
  !charts.some(c => JSON.stringify(c).includes('perProgram')));

console.log('\n=== 2. Action Center removed ===');
check('the Action Center section is gone', !/Action Center/.test(html));
check('no Action Center anchor survives',
  !/clipboard\]\]\s*Action Center/.test(html));
/* and it must not leave dead wiring behind — the same class of bug that
   produced the totHours / ico / bp orphans on other pages */
check('no dead Action Center render call remains',
  !/actionCenter/i.test(D.pageScript) && !/actionCenter/i.test(html));
check('the pending-approvals count still routes somewhere useful',
  /ai-analysis\.html\?role=admin/.test(html));
check('the Pending Approvals KPI still exists as a tile',
  /Pending Approvals/.test(html));

console.log('\n=== 3. layout primitives ===');
check('every section uses the shared .dash-section rhythm',
  (html.match(/class="dash-section"/g) || []).length >= 4);
check('no legacy ad-hoc "mt-5 / mt-4 grid" section wrappers remain',
  !/<section class="mt-[45]/.test(html));
check('panels use the shared .dash-panel wrapper',
  (html.match(/dash-panel/g) || []).length >= 8);
check('the KPI row uses the .dash-kpi rhythm',
  (html.match(/class="dash-panel dash-kpi[^"]*"/g) || []).length === 4);
check('KPI row collapses to 1 column on small screens',
  /grid-cols-1 sm:grid-cols-2 xl:grid-cols-4/.test(html));
check('chart rows collapse to 1 column below lg',
  (html.match(/grid-cols-1 lg:grid-cols-3/g) || []).length >= 3);
check('no bare grid-cols-3 remains (would not collapse on mobile)',
  !/class="[^"]*\bgrid-cols-3\b[^"]*"/.test(html.replace(/lg:grid-cols-3/g, '')));
check('no bare grid-cols-4 remains',
  !/\bgrid-cols-4\b/.test(html.replace(/xl:grid-cols-4/g, '')));
/* The KPI row follows the page head directly and needs no section header, so
   there are exactly 3 section-level titles (Trends / Performance / Intelligence)
   and 4 eyebrows (those three + the page head). */
check('every non-KPI section carries an eyebrow + title',
  (html.match(/dash-sec-title/g) || []).length === 3);
check('the page head adds one more eyebrow',
  (html.match(/dash-sec-eyebrow/g) || []).length === 4);
check('the page head carries its own eyebrow',
  /class="dash-sec-eyebrow">Dashboard</.test(html));
/* 6, not 7: the activity panel that became the Audit Logs page was one of them
   (owner request 2026-09-25). */
check('card heads use the shared .dash-head / .dash-head-title',
  (html.match(/dash-head-title/g) || []).length >= 6);

console.log('\n=== 4. leaderboards ===');
const topP = D.doc._byId['dashTopProjects'];
const topF = D.doc._byId['dashTopFaculty'];
check('most-performing projects rendered', topP && topP.innerHTML.length > 200);
check('most-performing faculty rendered', topF && topF.innerHTML.length > 200);
check('projects leaderboard shows 5 rows',
  topP && (topP.innerHTML.match(/program-detail\.html\?role=admin&program=/g) || []).length === 5);
check('faculty leaderboard shows 5 rows',
  topF && (topF.innerHTML.match(/faculty-management\.html\?role=admin/g) || []).length === 5);
check('projects ranked by rendered hours (descending)',
  topP && (D.sandbox.DATA.mostActiveProjects || []).slice(0, 5)
    .every((p, i, arr) => i === 0 || arr[i - 1].trainingHours >= p.trainingHours));
check('project rows carry the college colour stripe',
  topP && /style="background:#003599"|style="background:#F6B800"|style="background:#10b981"/.test(topP.innerHTML));
check('no leaderboard row references a removed target',
  topP && !/targetHours/.test(topP.innerHTML) && topF && !/targetHours/.test(topF.innerHTML));

console.log('\n' + (fail === 0 ? 'PASS — ' + pass + ' assertions' : fail + ' FAILED of ' + (pass + fail)));
process.exit(fail === 0 ? 0 : 1);
