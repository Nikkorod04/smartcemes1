/* Functional tests for the faculty surfaces.

   Covers three contracts that the structural harness can only inspect, not exercise:

     1. faculty-management.html  — the Faculty Engagement board
        (chart + leaderboard + college split, driven by a metric switch whose
         modes are CONTRIBUTION measures only — attainment % was removed
         because there is no per-professor training-hours target)
     2. faculty-management.html  — the New Faculty Profile modal
        (expertise multi-select over DATA.expertiseOptions, position ladder
         over DATA.positionLadder, no Specialization / Target Hours)
     3. faculty-directory.html   — the directory page + the faculty-side
        self-edit view (editable contact/expertise, locked assignment fields)

   Uses a Chart.js CAPTURE STUB so assertions can inspect the config the page
   builds, rather than trusting that it rendered something.

   The DOM/Chart sandbox lives in _shim.cjs (shared with _dashtest.cjs) so
   there is exactly one implementation of the mini-DOM to keep correct. */
const { boot } = require('./_shim.cjs');
const fs = require('fs'), path = require('path');
const dir = __dirname;

let pass = 0, fail = 0;
const check = (label, cond) => { if (cond) { console.log('  [ok  ] ' + label); pass++; } else { console.log('  [FAIL] ' + label); fail++; } };

/* ==========================================================================
   SUITE 1 — Faculty Engagement board (faculty-management.html)
   ========================================================================== */
console.log('\n=== 1. engagement board: structure ===');
const B = boot('faculty-management.html');
if (B.err) { console.log('  [FAIL] page script threw —', B.err.message); fail++; }
const FAC = B.sandbox.DATA.faculty;
const N = FAC.length;
const charts = B.charts;

check('a chart was constructed', charts.length >= 1);
check('chart is horizontal (indexAxis:y)', charts[0] && charts[0].options.indexAxis === 'y');
check('chart has one bar per faculty', charts[0] && charts[0].data.datasets[0].data.length === N);
check('hours mode is a SINGLE series (no target line)', charts[0] && charts[0].data.datasets.length === 1);
check('bars coloured by college', charts[0] &&
  charts[0].data.datasets[0].backgroundColor.every(c => ['#003599','#F6B800','#10b981'].includes(c)));
check('y-axis labels are every faculty name', charts[0] && charts[0].data.labels.length === N);
check('no duplicate axis labels', charts[0] && new Set(charts[0].data.labels).size === N);
check('legend hidden (single series reads cleaner)', charts[0] && charts[0].options.plugins.legend.display === false);
check('tooltip configured', !!(charts[0] && charts[0].options.plugins.tooltip.callbacks));
check('no external/remote URLs in chart config', !JSON.stringify(charts[0]).includes('http'));

/* the chart tooltip must not reference a target that does not exist */
{
  const tt = charts[0] && charts[0].options.plugins.tooltip.callbacks.label;
  const lines = tt ? tt({ dataIndex: 0, parsed: { x: 10 }, dataset: { label: 'x' } }) : [];
  const text = (Array.isArray(lines) ? lines : [lines]).join(' ');
  check('tooltip shows hours + projects, never a target', /hrs rendered/.test(text) && !/target/i.test(text));
}

console.log('=== 1b. engagement board: leaderboard + split + insights ===');
const rowsHtml = B.doc._byId['engRows'].innerHTML;
check('leaderboard rendered', rowsHtml.length > 300);
check('leaderboard has one row per faculty', (rowsHtml.match(/class="eng-row"/g) || []).length === N);
check('leaderboard rows carry data-id', (rowsHtml.match(/data-id="\d+"/g) || []).length === N);
check('leaderboard has NO attainment rail', !/eng-rail/.test(rowsHtml));
check('leaderboard has NO progress bar', !/hours-bar/.test(rowsHtml));
check('college split rendered', B.doc._byId['engSplit'].innerHTML.length > 40);
check('college legend rendered', ['CAS','COE','CME'].every(c => B.doc._byId['engSplitLegend'].innerHTML.includes(c)));
check('insights rendered', B.doc._byId['engInsights'].innerHTML.length > 150);
check('insights mention top performer', B.doc._byId['engInsights'].innerHTML.includes('leads the board'));
check('insights never mention a target or attainment %', !/attainment/i.test(B.doc._byId['engInsights'].innerHTML));

console.log('=== 1c. engagement board: metric switch ===');
const switchHtml = (/<div class="eng-switch"[^>]*id="engSwitch"[^>]*>([\s\S]*?)<\/div>/.exec(B.html) || [,''])[1];
const switchBtns = [...switchHtml.matchAll(/data-metric="(\w+)"/g)].map(m => m[1]);
check('switch offers exactly 3 metric modes', switchBtns.length === 3);
check('switch modes are contribution-only (hours / projects / leads)',
  ['hours','projects','leads'].every(m => switchBtns.includes(m)));
check('switch has NO attainment % mode', !switchBtns.includes('pct'));
check('hours mode is the default (marked .on)', /<button class="on" data-metric="hours">/.test(switchHtml));
check('switch is wired to a click listener',
  /engSwitch\.querySelectorAll\('button'\)[\s\S]{0,140}addEventListener\('click'/.test(B.pageScript));

const METRICS_SRC = /const METRICS = \{[\s\S]*?\n  \};/.exec(B.pageScript);
check('METRICS table defines exactly 3 metrics',
  METRICS_SRC && (METRICS_SRC[0].match(/^\s{4}\w+:/gm) || []).length === 3);
check('no METRICS entry computes a ratio against a target',
  METRICS_SRC && !/targetHours/.test(METRICS_SRC[0]));

/* exercise the two non-default modes by re-running the page script forced */
const modes = {};
['projects','leads'].forEach(m => {
  const R = boot('faculty-management.html', { forceMetric: m });
  if (R.err) { console.log('  [FAIL] ' + m + ' mode threw — ' + R.err.message); fail++; return; }
  modes[m] = { chart: R.charts[0], rows: R.doc._byId['engRows'], insights: R.doc._byId['engInsights'] };
});

if (modes.projects) {
  const c = modes.projects.chart;
  check('projects mode: single dataset', c && c.data.datasets.length === 1);
  const maxProj = Math.max(...FAC.map(f => f.projectCount || 0));
  check('projects mode: top value equals max project count',
    c && Math.max(...c.data.datasets[0].data) === maxProj);
  check('projects mode: leaderboard caption carries hours rendered',
    /hrs rendered/.test(modes.projects.rows.innerHTML));
}
if (modes.leads) {
  const c = modes.leads.chart;
  check('leads mode: single dataset', c && c.data.datasets.length === 1);
  const maxLead = Math.max(...FAC.map(f => f.leadCount || 0));
  check('leads mode: top value equals max lead count',
    c && Math.max(...c.data.datasets[0].data) === maxLead);
}

/* ==========================================================================
   SUITE 2 — New Faculty Profile modal (faculty-management.html)
   ========================================================================== */
console.log('\n=== 2. New Faculty Profile modal ===');
const modalHtml = B.html;

check('modal has a Full name field', /id="afName"/.test(modalHtml));
check('modal has an auto-assigned Employee ID', /id="afEmployeeId"[^>]*readonly/.test(modalHtml));
check('modal collects Birthdate', /id="afBirthdate"[^>]*type="date"/.test(modalHtml));
check('modal collects Sex', /id="afSex"/.test(modalHtml));
check('modal collects Civil status', /id="afCivil"/.test(modalHtml));
check('modal collects Contact number', /id="afPhone"/.test(modalHtml));
check('modal collects Email', /id="afEmail"[^>]*type="email"/.test(modalHtml));
check('modal computes Age from birthdate (readonly)', /id="afAge"[^>]*readonly/.test(modalHtml));
check('modal collects Address', /id="afAddress"/.test(modalHtml));

check('Specialization field is GONE', !/id="afSpec/.test(modalHtml) && !/>\s*Specialization\s*</.test(modalHtml));
check('Target training hours field is GONE', !/id="afTargetHours/.test(modalHtml) && !/>\s*Target training hours/i.test(modalHtml));
check('modal states there is no per-professor target',
  /no per-professor training-hours target/i.test(modalHtml));

/* expertise: a multi-select, not a text input */
check('expertise is a chip multi-select', /id="msExpertise"/.test(modalHtml) &&
  /class="ms-chips"/.test(modalHtml) && /class="ms-options"/.test(modalHtml));
check('expertise picker is no longer a free-text input', !/id="afExpertise"/.test(modalHtml));
check('expertise options come from DATA.expertiseOptions', /expertiseOptions/.test(B.pageScript));

/* position: the grouped 18-step ladder */
const LADDER = B.sandbox.DATA.positionLadder;
check('position ladder has 18 ranks', LADDER && LADDER.length === 18);
check('ladder spans Instructor I → Professor VI',
  LADDER && LADDER[0].label === 'Instructor I' && LADDER[17].label === 'Professor VI');
check('ladder is grouped into 4 series',
  LADDER && new Set(LADDER.map(p => p.group)).size === 4 &&
  ['Instructor','Assistant Professor','Associate Professor','Professor'].every(g =>
    LADDER.some(p => p.group === g)));
check('position select is built from the ladder', /positionLadder/.test(B.pageScript));
check('position select renders optgroups', /optgroup/.test(B.pageScript));
check('no hardcoded partial position list remains', !/<option>Associate Professor<\/option>/.test(modalHtml));

/* every seeded faculty position is a valid ladder rank */
check('all seeded positions resolve to a ladder rank',
  FAC.every(f => typeof f.positionRank === 'number' && f.positionRank >= 1 && f.positionRank <= 18));

/* expertise vocabulary */
const OPT = B.sandbox.DATA.expertiseOptions;
check('expertiseOptions is a canonical vocabulary (>= 20 values)', Array.isArray(OPT) && OPT.length >= 20);
check('every seeded expertise is in the vocabulary',
  FAC.every(f => f.expertise.every(e => OPT.includes(e))));
check('no seeded faculty carries targetHours', FAC.every(f => !('targetHours' in f)));
check('no seeded faculty carries spec', FAC.every(f => !('spec' in f)));
check('no seeded faculty carries an attainment pct', FAC.every(f => !('pct' in f)));

/* involvement index hydrated from projects */
check('every faculty has a projectList', FAC.every(f => Array.isArray(f.projectList)));
check('projectCount matches projectList length',
  FAC.every(f => (f.projectCount || 0) === f.projectList.length));
check('leadCount + coLeadCount === projectCount',
  FAC.every(f => (f.leadCount || 0) + (f.coLeadCount || 0) === (f.projectCount || 0)));

/* ==========================================================================
   SUITE 3 — Faculty Directory + faculty-side self-edit (faculty-directory.html)
   ========================================================================== */
console.log('\n=== 3. faculty-directory.html (admin view) ===');
const A = boot('faculty-directory.html', { marker: 'renderDirectory' });
if (A.err) { console.log('  [FAIL] page script threw — ' + A.err.message); fail++; }

check('directory renders one card per faculty',
  (A.doc._byId['dirGrid'].innerHTML.match(/class="sc-card[^"]*fac-card/g) || []).length === N);
check('directory cards carry data-id',
  (A.doc._byId['dirGrid'].innerHTML.match(/data-id="\d+"/g) || []).length === N);
check('directory cards show hours rendered',
  /Hours rendered/.test(A.doc._byId['dirGrid'].innerHTML));
check('directory cards show project count',
  /Project/.test(A.doc._byId['dirGrid'].innerHTML));
check('directory count reflects the roster',
  A.doc._byId['dirCount'].textContent.includes(String(N)));
/* P0k: the summary card strip was removed — the roster IS the content */
check('no summary card strip is rendered (P0k)',
  !/id="summary"/.test(A.html) && !('summary' in A.doc._byId));
check('no export action in the directory header (P0k)',
  !/btnExport/.test(A.html) && !/Export roster/.test(A.html) &&
  !/Download my details/.test(A.html));
check('the redundant header description is gone (P0k)',
  !/The single roster of extension faculty — profiles, declared expertise, and assignment details/.test(A.html));
check('the header description is still short and specific',
  /id="subheading"[^>]*>Roster of extension faculty</.test(A.html));
check('the breadcrumb survives the header trim',
  /id="crumb"/.test(A.html) && /Faculty Management/.test(A.html));
check('admin sees the Add Faculty button (visible)',
  !/hidden/.test(A.html.match(/id="dirAdd"[^>]*class="([^"]*)"/)?.[1] || ''));
check('admin does NOT get the self-service panel',
  A.doc._byId['selfPanel'].classList.contains('hidden'));

console.log('=== 3b. faculty-directory.html (faculty self-edit view) ===');
const F = boot('faculty-directory.html', { marker: 'renderDirectory', search: '?role=faculty' });
if (F.err) { console.log('  [FAIL] faculty view threw — ' + F.err.message); fail++; }

const selfHtml = F.doc._byId['selfPanel'].innerHTML;
check('faculty role activates the self-service panel',
  !F.doc._byId['selfPanel'].classList.contains('hidden'));
check('self panel explains what the faculty member may edit',
  /You can edit your own contact details and expertise areas/.test(selfHtml));
check('self panel states assignment fields are administrator-controlled',
  /administrator-controlled/i.test(selfHtml));
check('faculty role hides the Add Faculty button',
  F.doc._byId['dirAdd'].classList.contains('hidden'));
check('faculty role hides the college tabs',
  F.doc._byId['dirTabs'].classList.contains('hidden'));

/* the drawer auto-opens the self-edit form for the logged-in faculty */
const drawer = F.doc._byId['profDrawerBody'].innerHTML;
check('faculty drawer auto-opens their own profile', drawer.length > 500);
check('self-edit view is rendered', /You are editing <b>your own<\/b> profile/.test(drawer));
check('self-edit exposes editable Email', /id="seEmail"/.test(drawer));
check('self-edit exposes editable Contact number', /id="sePhone"/.test(drawer));
check('self-edit exposes editable Birthdate', /id="seBirthdate"/.test(drawer));
check('self-edit exposes editable Address', /id="seAddress"/.test(drawer));
check('self-edit exposes the expertise multi-select', /id="msExpertise"/.test(drawer) &&
  /id="seExpertiseInput"/.test(drawer));
check('self-edit LOCKS employee ID / position / college / status',
  (drawer.match(/locked-field/g) || []).length >= 4);
check('locked fields say "Admin only"', /Admin only/.test(drawer) || /locked-field/.test(F.html));
check('self-edit has a save action', /id="seSave"/.test(drawer));
check('self-edit shows contribution as read-only hours + projects',
  /training hours rendered/.test(drawer) && /projects involved/.test(drawer));
check('self-edit repeats that there is no per-professor target',
  /no training-hours target for individual faculty/i.test(drawer));
check('faculty view gets no admin summary strip (it is gone entirely)',
  !/id="summary"/.test(F.html) && !('summary' in F.doc._byId));

/* ==========================================================================
   SUITE 3c — faculty-management.html is BOARD-ONLY
   The redundant page header, the KPI cards, the filter toolbar and the
   Faculty Roster table were removed: the roster is managed in
   faculty-directory.html, so this page retains only the engagement board.
   ========================================================================== */
console.log('\n=== 3c. faculty-management.html is board-only ===');
const boardOnly = B.html;
check('the page keeps the Engagement board',
  ['engSwitch','engChart','engRows','engSplit','engInsights'].every(id =>
    new RegExp('id="' + id + '"').test(boardOnly)));
check('the redundant sub-heading paragraph is gone',
  !/Engagement dashboard — hours rendered and project involvement/.test(boardOnly));
check('no KPI card grid remains', !/id="kpis"/.test(boardOnly));
check('no filter toolbar remains',
  !/id="facSearch"/.test(boardOnly) && !/id="collegeTabs"/.test(boardOnly) &&
  !/id="expertiseSel"/.test(boardOnly) && !/id="statusSel"/.test(boardOnly) &&
  !/id="sortSel"/.test(boardOnly));
check('no Faculty Roster table remains',
  !/Faculty Roster/.test(boardOnly) && !/id="facultyBody"/.test(boardOnly) &&
  !/id="facCount"/.test(boardOnly) && !/id="facEmpty"/.test(boardOnly));
/* and the dead wiring must be gone too, not just the markup */
check('no dead toolbar listeners remain',
  !/facSearch\.addEventListener/.test(B.pageScript) &&
  !/sortSel\.addEventListener/.test(B.pageScript) &&
  !/collegeTabs\.querySelectorAll/.test(B.pageScript));
check('no dead KPI render call remains', !/kpis\.innerHTML/.test(B.pageScript));
check('no dead roster render() call remains', !/^\s*render\(\);/m.test(B.pageScript));
check('the page still exposes the Faculty Directory action',
  /id="btnAdd"/.test(boardOnly) && /Faculty Directory/.test(boardOnly));

/* ---- P0j: no page action row; the action sits in the board header -------
   "Export roster" was removed and "Faculty Directory" moved inside the
   board card, to the right of the AY chip. */
check('the "Export roster" action is gone',
  !/id="btnExport"/.test(boardOnly) && !/Export roster/.test(boardOnly));
check('no page-level action row remains', !/PAGE ACTIONS/.test(boardOnly));
check('the AY chip still anchors the header cluster',
  /badge badge-gold[^>]*>AY 2026–2027/.test(boardOnly));
check('the Faculty Directory action sits right of the AY chip',
  /AY 2026–2027<\/span>\s*<button id="btnAdd"/.test(boardOnly));
/* no dead listener for the removed button either */
check('no dead btnExport listener remains', !/btnExport\.onclick/.test(B.pageScript));
check('btnAdd is still wired to the directory page',
  /btnAdd\.onclick[\s\S]{0,120}faculty-directory\.html\?role=admin&new=1/.test(B.pageScript));

/* the ?college= deep link must narrow the WHOLE board, not just a heading */
const CB = boot('faculty-management.html', { search: '?college=CME' });
if (CB.err) { console.log('  [FAIL] ?college=CME threw — ' + CB.err.message); fail++; }
else {
  const cmeN = FAC.filter(f => f.college === 'CME').length;
  check('?college=CME narrows the chart to that college only',
    CB.charts[0] && CB.charts[0].data.datasets[0].data.length === cmeN);
  check('?college=CME narrows the leaderboard too',
    (CB.doc._byId['engRows'].innerHTML.match(/class="eng-row"/g) || []).length === cmeN);
  check('a narrowed board says so in the banner',
    /Board narrowed to this college/.test(CB.doc._byId['banner'].innerHTML));
  check('insights name the college when narrowed',
    /CME/.test(CB.doc._byId['engInsights'].innerHTML));
  check('the default (no-param) board is unnarrowed',
    charts[0].data.datasets[0].data.length === N);
}

/* ==========================================================================
   SUITE 4 — entry point
   ========================================================================== */
console.log('\n=== 4. entry point ===');
check('faculty-management header action is labelled "Faculty Directory"',
  /Faculty Directory/.test(B.html));
check('faculty-management no longer labels it "Add Faculty"',
  !/Add Faculty<\/span>/.test(B.html));
check('the header action routes to faculty-directory.html',
  /faculty-directory\.html\?role=admin&new=1/.test(B.pageScript));
check('the drawer routes into the directory edit view',
  /faculty-directory\.html\?role=admin&faculty=\$\{f\.id\}&edit=1/.test(B.pageScript));

const navSrc = fs.readFileSync(path.join(dir, 'assets/js/layout.js'), 'utf8');
check('nav exposes faculty-directory.html', /href:'faculty-directory\.html'/.test(navSrc));
check('nav lists faculty-directory as a sub-page (highlight rule)',
  /subs:\['faculty-directory'\]/.test(navSrc));

/* ==========================================================================
   SUITE 5 — colleges.html faculty cards lost their target bar
   ========================================================================== */
console.log('\n=== 5. colleges.html faculty cards ===');
const collegesHtml = fs.readFileSync(path.join(dir, 'pages/colleges.html'), 'utf8');
check('colleges.html no longer reads f.targetHours', !/targetHours/.test(collegesHtml));
check('colleges.html no longer reads f.spec', !/f\.spec/.test(collegesHtml));
check('colleges.html faculty card has no attainment bar', !/hours-bar/.test(collegesHtml));
check('colleges.html faculty card shows project involvement',
  /fac-mini-lbl">Project/.test(collegesHtml));
check('colleges.html faculty card deep-links into the directory',
  /faculty-directory\.html\?role=admin&faculty=/.test(collegesHtml));

console.log('\n' + (fail === 0 ? 'PASS — ' + pass + ' assertions' : fail + ' FAILED of ' + (pass + fail)));
process.exit(fail === 0 ? 0 : 1);
