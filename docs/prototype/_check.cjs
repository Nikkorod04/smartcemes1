/* Static validation sweep for the SmartCEMES prototype.
   - loads seed-data.js in a vm sandbox (catches runtime errors in the data layer)
   - extracts inline <script> blocks from every page and syntax-checks them
   - checks <div> balance, BOM presence, and mojibake markers
*/
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const dir = __dirname;

function walk(d, out = []) {
  for (const e of fs.readdirSync(d, { withFileTypes: true })) {
    const p = path.join(d, e.name);
    if (e.isDirectory()) walk(p, out);
    else if (e.name.endsWith('.js')) out.push(p);
  }
  return out;
}

let fail = 0;

/* ---- 1. seed-data.js loads clean ---- */
const sandbox = { window: {}, console };
sandbox.window = sandbox;
vm.createContext(sandbox);
const seedSrc = fs.readFileSync(path.join(dir, 'assets/js/seed-data.js'), 'utf8');
const layoutSrc = fs.readFileSync(path.join(dir, 'assets/js/layout.js'), 'utf8');
try {
  vm.runInContext(seedSrc, sandbox, { filename: 'seed-data.js' });
  const D = sandbox.DATA;
  console.log('[seed] programs =', D.programs.length, '| projects =', D.projects.length,
    '| colleges =', D.colleges.length, '| faculty =', D.faculty.length);
  console.log('[seed] programs === projects ?', D.programs === D.projects);
  const stale = D.projects.filter(p => !p.budgetAllocated && !p.trainingHoursTarget);
  if (stale.length) { console.log('  ! projects missing new targets:', stale.map(p => p.code)); fail++; }
  const badProg = D.programs.filter(p => !p.code || !String(p.code).startsWith('PROG-'));
  if (badProg.length) { console.log('  ! non-PROG codes in DATA.programs:', badProg.map(p => p.code)); fail++; }
  let t1 = 0, t2 = 0, t3 = 0;
  (D.programNarratives || []).forEach(n => (n.recommendations || []).forEach(r => {
    if (r.tier === 1) t1++; else if (r.tier === 2) t2++; else t3++;
  }));
  console.log('[seed] narrative tiers  T1:' + t1 + '  T2:' + t2 + '  T3(suppressed):' + t3);
  const t2NoAgency = [];
  (D.programNarratives || []).forEach(n => (n.recommendations || []).forEach(r => {
    if (r.tier === 2 && !r.agency) t2NoAgency.push(r.action);
  }));
  if (t2NoAgency.length) { console.log('  ! tier-2 items missing an agency:', t2NoAgency); fail++; }
  console.log('[seed] universities targets:', (D.universityTargets || []).length,
    '| agencies:', (D.interagencyAgencies || []).length);
} catch (e) {
  console.log('[seed] FAILED:', e.message); fail++;
}

/* ---- 1b. no BOM / mojibake anywhere in shipped prototype sources ----
   The Edit tool has twice reintroduced a UTF-8 BOM at the top of a file;
   a BOM before <!DOCTYPE> or before an IIFE breaks strict parsing. */
const assetFiles = [
  ...fs.readdirSync(path.join(dir, 'assets/js')).filter(f => f.endsWith('.js')).map(f => path.join(dir, 'assets/js', f)),
  ...fs.readdirSync(path.join(dir, 'assets/css')).filter(f => f.endsWith('.css')).map(f => path.join(dir, 'assets/css', f)),
];
for (const f of assetFiles) {
  const raw = fs.readFileSync(f, 'utf8');
  const rel = path.relative(dir, f);
  if (raw.charCodeAt(0) === 0xFEFF) { console.log('[BOM ] ' + rel); fail++; }
  if (/\uFFFD/.test(raw)) { console.log('[MOJI] ' + rel); fail++; }
}

/* ---- 2. inline script syntax check per page ---- */
const pages = walk(path.join(dir, 'pages'));
for (const f of pages) {
  const html = fs.readFileSync(f, 'utf8');
  const rel = path.relative(dir, f);
  if (html.charCodeAt(0) === 0xFEFF) { console.log('[BOM ] ' + rel); fail++; }
  if (/\uFFFD/.test(html)) { console.log('[MOJI] ' + rel); fail++; }
  const open = (html.match(/<div\b/g) || []).length;
  const close = (html.match(/<\/div>/g) || []).length;
  if (open !== close) { console.log('[DIV ] ' + rel + '  open=' + open + ' close=' + close); fail++; }

  const blocks = [...html.matchAll(/<script(?![^>]*\bsrc=)[^>]*>([\s\S]*?)<\/script>/g)];
  blocks.forEach((m, i) => {
    try { new vm.Script(m[1]); }
    catch (e) { console.log('[JS  ] ' + rel + ' block#' + i + ': ' + e.message); fail++; }
  });
}

/* ---- 3. pages referenced by NAV / index that do not exist ---- */
const layout = fs.readFileSync(path.join(dir, 'assets/js/layout.js'), 'utf8');
const refs = new Set([...layout.matchAll(/href:\s*['"]([a-z0-9-]+\.html)/g)].map(m => m[1]));
const idx = fs.readFileSync(path.join(dir, 'index.html'), 'utf8');
[...idx.matchAll(/pages\/([a-z0-9-]+\.html)/g)].forEach(m => refs.add(m[1]));
for (const r of refs) {
  if (!fs.existsSync(path.join(dir, 'pages', r))) { console.log('[NAV ] missing page: pages/' + r); fail++; }
}

/* ---- 4. index.html + every page: deep-link integrity for program codes ---- */
const allHtml = fs.readdirSync(path.join(dir, 'pages')).filter(f => f.endsWith('.html'))
  .map(f => [f, fs.readFileSync(path.join(dir, 'pages', f), 'utf8')]);
allHtml.push(['index.html', idx]);
const codes = new Set(sandbox.DATA.projects.map(p => p.code));
const legacy = new Set(sandbox.DATA.projects.map(p => p.legacyCode).filter(Boolean));
const progCodes = new Set(sandbox.DATA.programs.map(p => p.code));
const badDeep = new Set();
allHtml.forEach(([f, html]) => {
  /* strip JS comments so illustrative code like '?program=PROG-xx' is not treated as a link */
  const clean = html.replace(/\/\*[\s\S]*?\*\//g, '').replace(/^\s*\/\/.*$/gm, '');
  [...clean.matchAll(/program=([A-Z0-9-]+)/g)].forEach(m => {
    const c = m[1];
    if (!codes.has(c) && !legacy.has(c) && !progCodes.has(c)) badDeep.add(f + ' → ' + c);
  });
});
if (badDeep.size) { console.log('[LINK] unknown program codes in deep links:'); [...badDeep].forEach(x => console.log('   ' + x)); fail++; }

/* ---- 5. inline [[icon]] tokens must exist in the ICONS map ---- */
const iconNames = new Set([...layout.matchAll(/^\s{4}([a-z][a-z0-9-]*):\s*(?:`|')/gm)].map(m => m[1]));
allHtml.forEach(([f, html]) => {
  [...html.matchAll(/\[\[([a-z][a-z0-9-]*)\]\]/g)].forEach(m => {
    if (!iconNames.has(m[1])) { console.log('[ICON] ' + f + ' uses unknown token [[' + m[1] + ']]'); fail++; }
  });
});

/* ---- 5b. every icon token must be reachable at expand time ----
   Tokens written inside a *static* HTML attribute are expanded by boot()'s first
   pass. Tokens emitted from a JS template literal are injected later; layout.js
   installs a MutationObserver so they still expand. This check asserts the
   observer exists, is wired into boot(), and that the sizing contract still
   honours a caller-supplied size class instead of forcing the w-5 h-5 default. */
if (!/function watchIconTokens\s*\(/.test(layout)) {
  console.log('[ICON] layout.js has no watchIconTokens() — late-rendered [[token]]s will stay literal'); fail++;
}
if (!/MutationObserver/.test(layout) || !/watchIconTokens\(\)\s*;/.test(layout)) {
  console.log('[ICON] watchIconTokens() is not installed during boot()'); fail++;
}
if (!/expandIcons,/.test(layout)) {
  console.log('[ICON] SC.expandIcons is not exported for manual re-expansion'); fail++;
}
/* the svg() helper must drop its default size when the caller passes one */
if (!/const hasSize\s*=/.test(layout) || !/hasSize \? '' : 'w-5 h-5 '/.test(layout)) {
  console.log('[ICON] svg() no longer suppresses its w-5 h-5 default when a size class is supplied'); fail++;
}
/* no call site may pass a size class that the default could override */
{
  const badSize = [];
  allHtml.forEach(([f, html]) => {
    [...html.matchAll(/SC\.svg\([^)]*?,\s*'([^']*)'/g)].forEach(m => {
      const cls = m[1];
      const hasW = /(^|\s)!?w-/.test(cls), hasH = /(^|\s)!?h-/.test(cls);
      if (hasW !== hasH) badSize.push(f + " → partial size '" + cls + "'");
    });
  });
  if (badSize.length) { console.log('[ICON] SC.svg() called with only one of w-/h-:'); badSize.forEach(x => console.log('   ' + x)); fail++; }
}

/* ---- 5c. mojibake guard ----
   Re-encode each run of cp1252-mappable high chars to bytes; if that yields valid
   UTF-8, the file holds double-encoded text (e.g. "Â·" where "·" belongs).
   A run must be >= 2 chars: a lone "·" or "—" is legitimate typography, whereas
   the mangled form always arrives as a multi-char cluster like "Â·" or "â€¦". */
const CP1252_REV = (() => {
  const m = new Map();
  for (let b = 0; b < 256; b++) {
    try { m.set(Buffer.from([b]).toString('latin1'), b); } catch (e) {}
  }
  return m;
})();
function mojibakeRuns(text) {
  const out = [];
  let run = [];
  const flush = () => {
    if (run.length >= 2) {
      const bytes = run.map(c => CP1252_REV.get(c));
      if (bytes.every(b => b !== undefined)) {
        try { Buffer.from(bytes).toString('utf8'); out.push(run.join('')); } catch (e) {}
      }
    }
    run = [];
  };
  for (const ch of text) {
    if (ch.charCodeAt(0) > 0x7E && CP1252_REV.has(ch)) run.push(ch);
    else flush();
  }
  flush();
  return out;
}
const mojiFiles = [];
[...allHtml, ...assetFiles.map(f => [path.relative(dir, f), fs.readFileSync(f, 'utf8')])].forEach(([f, t]) => {
  const runs = mojibakeRuns(t);
  if (runs.length) mojiFiles.push(f + ' → ' + runs.slice(0, 3).map(r => JSON.stringify(r)).join(', '));
});
if (mojiFiles.length) {
  console.log('[MOJI] double-encoded text found:');
  mojiFiles.forEach(x => console.log('   ' + x));
  fail++;
}

/* ---- 6. collapsed hub contract: the admin sidebar must expose exactly ONE
       extension-structure entry ("Manage Extension Programs") and the four
       hierarchy levels must be reachable from it ---- */
const NAV_ADMIN_ENTRY = 'Manage Extension Programs';
const adminBlock = layout.slice(layout.indexOf('admin: ['), layout.indexOf('secretary: ['));
/* entries that duplicate the hierarchy in the sidebar */
['label:\'Colleges\'', 'label:\'Extension Programs\'', 'label:\'Extension Projects\''].forEach(dup => {
  const spaced = dup.replace(/:/g, ': ');
  if (adminBlock.includes(dup) || adminBlock.includes(spaced)) {
    console.log('[NAV ] admin sidebar still has a separate ' + dup + ' entry — hierarchy must be collapsed');
    fail++;
  }
});
if (!adminBlock.includes(NAV_ADMIN_ENTRY)) {
  console.log('[NAV ] admin sidebar is missing the "' + NAV_ADMIN_ENTRY + '" entry'); fail++;
}
/* the hub entry must declare sub-keys so in-hub drill-downs keep it highlighted,
   and every sub-key must correspond to a real data-page somewhere */
const subsMatch = adminBlock.match(/subs:\[([^\]]*)\]/);
if (!subsMatch) { console.log('[NAV ] hub entry has no subs[] key list'); fail++; }
else {
  const subs = subsMatch[1].split(',').map(s => s.trim().replace(/['"]/g, '')).filter(Boolean);
  ['colleges','programs','projects','program-detail'].forEach(k => {
    if (!subs.includes(k)) { console.log('[NAV ] hub subs[] missing key: ' + k); fail++; }
  });
  const declaredPages = new Set(allHtml.map(([, html]) => (html.match(/data-page="([^"]+)"/) || [])[1]).filter(Boolean));
  subs.forEach(k => { if (!declaredPages.has(k)) { console.log('[NAV ] hub sub key "' + k + '" matches no page data-page'); fail++; } });
}

/* ---- 7. hub contract: two views, no drill-down rail ----
   Owner decision (see revisions.md §11.6): the hub must show ONLY the three
   college cards on arrival. Clicking a college swaps in that college's projects.
   The old College→Program→Project→Activity rail was removed everywhere. */
{
  const college = (allHtml.find(([n]) => n === 'colleges.html') || [,''])[1];

  /* the rail must be gone from every page */
  const railUsers = allHtml.filter(([, h]) => h.includes('hier-node') || h.includes('hier-step'));
  if (railUsers.length) {
    console.log('[RAIL] drill-down rail still present in: ' + railUsers.map(([f]) => f).join(', '));
    fail++;
  }

  /* colleges.html must be a two-view page */
  ['viewColleges','viewCollege','collegeCards','projGrid','backBtn','collegeKpis']
    .forEach(id => {
      if (!new RegExp('id="' + id + '"').test(college)) { console.log('[HUB ] colleges.html is missing #' + id); fail++; }
    });

  /* view 1 renders ONLY college cards — no project/program tables, no roll-up strip */
  ['strip','cards','uniStrip'].forEach(dead => {
    if (new RegExp('id="' + dead + '"').test(college)) {
      console.log('[HUB ] colleges.html still carries the old #' + dead + ' block — view 1 must be college cards only'); fail++;
    }
  });
  if (/Broad programs \(/.test(college) || /Projects \(\$\{/.test(college)) {
    console.log('[HUB ] colleges.html view 1 still renders program/project tables'); fail++;
  }

  /* NO per-college training-hours figure anywhere — targets exist at UNIVERSITY
     and PROJECT level only. A college-level hoursPct/trainingHoursTarget read,
     or a "Training hours" progress bar outside the project cards, is a defect. */
  ['college.hoursPct','c.hoursPct','college.trainingHoursTarget','c.trainingHoursTarget']
    .forEach(bad => {
      if (college.includes(bad)) {
        console.log('[HUB ] colleges.html still computes a per-college training-hours figure (' + bad + ') — no such target exists'); fail++;
      }
    });
  /* the only legitimate "Training hours" bar is inside the project card renderer */
  const hoursBars = (college.match(/Training hours/g) || []).length;
  if (hoursBars !== 1) {
    console.log('[HUB ] colleges.html has ' + hoursBars + ' "Training hours" labels — expected exactly 1 (the project card)'); fail++;
  }

  /* view 2 must be reachable from a click and via deep link */
  if (!/addEventListener\('click'[\s\S]{0,200}college-card/.test(college)) {
    console.log('[HUB ] colleges.html does not wire a click handler on .college-card'); fail++;
  }
  if (!/get\('college'\)/.test(college)) { console.log('[HUB ] colleges.html lost its ?college= deep link'); fail++; }

  /* back navigation + history support */
  ['goBack','popstate','pushState'].forEach(fn => {
    if (!college.includes(fn)) { console.log('[HUB ] colleges.html is missing ' + fn + ' handling'); fail++; }
  });

  /* ---- P0l: no header banner; cards carry a logo/media area ------------
     View 1 opens straight into the cards — the sidebar already names the
     entry, so a page header here was pure redundancy. */
  if (/Manage Extension Programs<\/h2>/.test(college)) {
    console.log('[HUB ] colleges.html still renders the "Manage Extension Programs" header'); fail++;
  }
  if (/Select a college to see its extension projects/.test(college)) {
    console.log('[HUB ] colleges.html still renders the header sub-heading'); fail++;
  }
  /* each card opens with a media area + placeholder marker; the old 6px strip is gone */
  if (/college-card-top/.test(college)) {
    console.log('[HUB ] colleges.html still renders the old .college-card-top strip'); fail++;
  }
  if (!/class="college-media w-full" style="background:\$\{c\.color\}"/.test(college)) {
    console.log('[HUB ] colleges.html college cards have no brand-tinted logo/media area'); fail++;
  }
  if (!/college-logo-ph/.test(college)) {
    console.log('[HUB ] colleges.html logo/media area carries no placeholder marker'); fail++;
  }
  /* a dead budget percentage left over from the removed bar must not linger:
     any per-card `bp` that is computed must also be rendered */
  if (/const bp\s*=/.test(college) && !/bp > 100/.test(college)) {
    console.log('[HUB ] colleges.html computes an unused per-card budget percentage (const bp)'); fail++;
  }
  /* balance: the thrust text and badge row must be height-reserved */
  if (!/min-h-\[54px\]/.test(college) || !/min-h-\[24px\]/.test(college)) {
    console.log('[HUB ] colleges.html cards are not height-balanced (missing reserved heights)'); fail++;
  }
}

/* ==========================================================================
   [FORMULA] Training-hours formula = trainors x trainees x days
   The x8 hourly factor was REMOVED by the owner ("its multiplied by days not
   hours"). No page, script, or seed string may reintroduce it.
   ========================================================================== */
{
  const FD = sandbox.DATA;
  const srcs = [['assets/js/seed-data.js', seedSrc], ['assets/js/layout.js', layoutSrc]]
    .concat(allHtml);
  srcs.forEach(([name, src]) => {
    /* literal '× 8' / 'x8 hrs' in any label or comment */
    if (/[×x]\s*8\s*(hrs|hours)/i.test(src)) {
      console.log('[FORMULA] ' + name + ' still states the removed "x 8 hrs" factor'); fail++;
    }
    /* code that multiplies a training-hours expression by 8 */
    if (/\*\s*8\s*;/.test(src) || /days\s*\*\s*8/.test(src) || /noOfDays\s*\*\s*8/.test(src)) {
      console.log('[FORMULA] ' + name + ' still multiplies training hours by 8 in code'); fail++;
    }
  });

  /* the canonical formula string must appear, un-suffixed, somewhere */
  const canonical = srcs.some(([, src]) => /trainors\s*×\s*trainees\s*×\s*days(?![^<]*×)/.test(src));
  if (!canonical) { console.log('[FORMULA] no page states the canonical "trainors × trainees × days" formula'); fail++; }

  /* seed activities must arithmetically satisfy the NEW formula */
  const badActs = FD.activities.filter(a => a.attendees && a.noOfDays &&
    a.trainors * a.attendees * a.noOfDays !== a.trainingHours);
  if (badActs.length) {
    console.log('[FORMULA] ' + badActs.length + ' activity row(s) do not equal trainors x trainees x days: ' +
      badActs.map(a => a.title.slice(0, 30)).join(' | ')); fail++;
  }

  /* project rollups must equal the sum of their activities' hours */
  const actSum = FD.activities.filter(a => a.status === 'Completed')
    .reduce((s, a) => s + (a.trainingHours || 0), 0);
  const projSum = FD.projects.reduce((s, p) => s + p.renderedTrainingHours, 0);
  if (actSum !== projSum) {
    console.log('[FORMULA] project rendered-hours total (' + projSum + ') != completed-activity sum (' + actSum + ')'); fail++;
  }
}

/* ==========================================================================
   [ENGAGE] faculty-management.html Faculty Engagement board.
   The "Most Active Faculty" TABLE was replaced by a chart + leaderboard +
   college split driven by a 3-mode metric switch. Guard the new contract.
   ========================================================================== */
{
  const fac = (allHtml.find(([n]) => n === 'faculty-management.html') || [,''])[1];
  if (!fac) { console.log('[ENGAGE] faculty-management.html not found'); fail++; }
  else {
    /* the old table-driven ranking must be gone */
    ['rankBody','Most Active Faculty','rank-medal'].forEach(dead => {
      if (fac.includes(dead)) {
        console.log('[ENGAGE] faculty-management.html still carries the old ranked table (' + dead + ')'); fail++;
      }
    });

    /* the new board's required anchors */
    ['engRows','engChart','engSplit','engSplitLegend','engSwitch','engInsights','engChartTitle']
      .forEach(id => {
        if (!new RegExp('id="' + id + '"').test(fac)) {
          console.log('[ENGAGE] faculty-management.html is missing #' + id); fail++;
        }
      });

    /* Chart.js must be vendored locally — never a remote script */
    if (!/src="\.\.\/assets\/vendor\/chart\.umd\.min\.js"/.test(fac)) {
      console.log('[ENGAGE] faculty-management.html does not load the vendored chart.umd.min.js'); fail++;
    }

    /* Three CONTRIBUTION modes only. 'pct' (attainment) is banned: there is
       no per-professor training-hours target to attain against. */
    if (/data-metric="pct"/.test(fac)) {
      console.log('[ENGAGE] faculty-management.html still offers an attainment % mode — there is no per-professor target'); fail++;
    }
    ['hours','projects','leads'].forEach(m => {
      if (!new RegExp('data-metric="' + m + '"').test(fac)) {
        console.log('[ENGAGE] metric switch is missing the "' + m + '" mode'); fail++;
      }
    });
    /* every metric in the table must be a contribution measure, never a ratio */
    if (/targetHours/.test(fac)) {
      console.log('[ENGAGE] faculty-management.html reads f.targetHours — per-professor targets were removed'); fail++;
    }
    if (/attainBadge|eng-rail|hours-bar/.test(fac)) {
      console.log('[ENGAGE] faculty-management.html still renders an attainment bar/badge'); fail++;
    }
    if (/f\.spec/.test(fac)) {
      console.log('[ENGAGE] faculty-management.html still reads f.spec — Specialization was removed'); fail++;
    }
    if (!/engSwitch\.querySelectorAll\('button'\)[\s\S]{0,140}addEventListener\('click'/.test(fac)) {
      console.log('[ENGAGE] the metric switch is not wired to a click listener'); fail++;
    }
    if (!/indexAxis:\s*'y'/.test(fac)) { console.log('[ENGAGE] the engagement chart is not horizontal'); fail++; }
    if (!/openFaculty\(list\[els\[0\]\.index\]\.id\)/.test(fac)) {
      console.log('[ENGAGE] clicking a chart bar does not open the faculty drawer'); fail++;
    }

    /* the college colour map must match the college brand colours */
    const cmap = /const COLORS = \{([^}]*)\}/.exec(fac);
    if (!cmap) { console.log('[ENGAGE] COLORS map missing'); fail++; }
    else {
      const expect = { CAS:'#003599', COE:'#F6B800', CME:'#10b981' };
      Object.entries(expect).forEach(([k, hex]) => {
        if (!new RegExp(k + "\\s*:\\s*'" + hex + "'").test(cmap[1])) {
          console.log('[ENGAGE] COLORS[' + k + '] is not the brand colour ' + hex); fail++;
        }
      });
    }

    /* the leaderboard must still route into the existing drawer */
    if (!/openFaculty\(\+el\.dataset\.id\)/.test(fac)) {
      console.log('[ENGAGE] leaderboard rows do not open the faculty drawer'); fail++;
    }
    /* and the page must expose the drawer functions it relies on */
    ['window.openFaculty','window.closeFaculty','window.closeAdd'].forEach(fn => {
      if (!fac.includes(fn)) { console.log('[ENGAGE] faculty-management.html lost ' + fn); fail++; }
    });

    /* ---- board-only contract (P0i) ------------------------------------
       The redundant page header, the KPI card grid, the filter toolbar and
       the Faculty Roster table all moved to faculty-directory.html. This
       page must stay a single-purpose engagement board. */
    ['id="kpis"','id="facSearch"','id="collegeTabs"','id="expertiseSel"',
     'id="statusSel"','id="sortSel"','id="facultyBody"','id="facCount"','id="facEmpty"']
      .forEach(dead => {
        if (fac.includes(dead)) {
          console.log('[ENGAGE] faculty-management.html still renders ' + dead + ' — the roster moved to the directory page'); fail++;
        }
      });
    if (/Faculty Roster/.test(fac)) {
      console.log('[ENGAGE] faculty-management.html still renders the Faculty Roster table'); fail++;
    }
    if (/Engagement dashboard — hours rendered and project involvement/.test(fac)) {
      console.log('[ENGAGE] faculty-management.html still carries the redundant sub-heading'); fail++;
    }
    /* there is no page-level action row: "Export roster" is gone and the
       Faculty Directory action lives in the board header, right of the AY chip */
    if (/btnExport|Export roster/.test(fac)) {
      console.log('[ENGAGE] faculty-management.html still offers "Export roster" — the page has no action row'); fail++;
    }
    if (!/id="btnAdd"[^>]*>\[\[users\]\]/.test(fac)) {
      console.log('[ENGAGE] the Faculty Directory action is missing from the board header'); fail++;
    }
    /* it must come AFTER the AY chip, inside the header's right-hand cluster */
    if (!/AY 2026–2027<\/span>\s*<button id="btnAdd"/.test(fac)) {
      console.log('[ENGAGE] the Faculty Directory action is not positioned right of the AY chip'); fail++;
    }
    if (/PAGE ACTIONS/.test(fac)) {
      console.log('[ENGAGE] faculty-management.html still has a PAGE ACTIONS row'); fail++;
    }
    /* the ?college= deep link must narrow the whole board through one SCOPE input */
    if (!/const SCOPE = college === 'All' \? FAC\.slice\(\) : FAC\.filter\(f => f\.college === college\)/.test(fac)) {
      console.log('[ENGAGE] faculty-management.html has no SCOPE filter for the ?college= deep link'); fail++;
    }
    if (/const ranked = \(\) => FAC\.slice\(\)/.test(fac)) {
      console.log('[ENGAGE] the board still ranks the unfiltered roster — ?college= would not narrow it'); fail++;
    }

    /* functional harness must exist alongside the others */
    if (!fs.existsSync(path.join(dir, '_facultytest.cjs'))) {
      console.log('[ENGAGE] the functional harness _facultytest.cjs is missing'); fail++;
    }
  }
}

/* ==========================================================================
   [FACDIR] Faculty Directory + the New Faculty Profile modal  (v4.8)

   Two linked contracts:
     1. the modal — basic info fields, no Specialization, expertise as a
        multi-select fed by DATA.expertiseOptions, position as the 18-step
        ladder, and NO Target Training Hours field.
     2. the directory page — a dedicated roster-management page reachable
        from the "Faculty Directory" entry point, with a faculty-side
        self-edit view whose assignment fields are locked.
   ========================================================================== */
{
  const fac  = (allHtml.find(([n]) => n === 'faculty-management.html') || [,''])[1];
  const dirP = allHtml.find(([n]) => n === 'faculty-directory.html');

  /* ---- 1. modal contract (asserted on whichever page hosts the modal) ---- */
  [['faculty-management.html', fac], ['faculty-directory.html', dirP && dirP[1]]].forEach(pair => {
    const name = pair[0], src = pair[1];
    if (!src) { console.log('[FACDIR] ' + name + ' not found'); fail++; return; }

    /* removed fields must not come back as form inputs. (A comment that
       merely says they were removed is fine — match on the input/select.) */
    if (/<input[^>]*id="afSpec|<select[^>]*id="afSpec/i.test(src)) {
      console.log('[FACDIR] ' + name + ' still renders a Specialization input'); fail++;
    }
    if (/<input[^>]*id="afTargetHours|<select[^>]*id="afTargetHours/i.test(src)) {
      console.log('[FACDIR] ' + name + ' still renders a Target Training Hours input'); fail++;
    }
    if (/class="label"[^>]*>\s*Specialization\s*</i.test(src)) {
      console.log('[FACDIR] ' + name + ' still labels a Specialization field'); fail++;
    }
    if (/class="label"[^>]*>\s*Target training hours/i.test(src)) {
      console.log('[FACDIR] ' + name + ' still labels a Target Training Hours field'); fail++;
    }

    /* required basic-info fields */
    ['afName','afEmployeeId','afBirthdate','afSex','afCivil','afEmail','afPhone','afAge','afAddress']
      .forEach(id => {
        if (!new RegExp('id="' + id + '"').test(src)) {
          console.log('[FACDIR] ' + name + ' modal is missing #' + id); fail++;
        }
      });

    /* expertise must be a multi-select, not a free-text input */
    if (!/id="msExpertise"/.test(src) || !/class="ms-chips"/.test(src) || !/class="ms-options"/.test(src)) {
      console.log('[FACDIR] ' + name + ' expertise field is not a chip multi-select'); fail++;
    }
    if (/id="afExpertise"/.test(src)) {
      console.log('[FACDIR] ' + name + ' still has the old free-text afExpertise input'); fail++;
    }
    /* the option source is the canonical seed vocabulary, not hardcoded */
    if (!/expertiseOptions/.test(src)) {
      console.log('[FACDIR] ' + name + ' expertise picker does not read DATA.expertiseOptions'); fail++;
    }

    /* position must be the grouped 18-step ladder */
    if (!/id="afPosition"/.test(src)) { console.log('[FACDIR] ' + name + ' missing #afPosition'); fail++; }
    if (!/positionLadder/.test(src)) {
      console.log('[FACDIR] ' + name + ' position select does not read DATA.positionLadder'); fail++;
    }
    /* no hand-written partial ladder may linger in the markup */
    if (/<option>Associate Professor<\/option>/.test(src)) {
      console.log('[FACDIR] ' + name + ' still hardcodes an incomplete position list'); fail++;
    }
  });

  /* ---- 2. the ladder + vocabulary themselves ---- */
  const LADDER = [
    'Instructor I','Instructor II','Instructor III',
    'Assistant Professor I','Assistant Professor II','Assistant Professor III','Assistant Professor IV',
    'Associate Professor I','Associate Professor II','Associate Professor III','Associate Professor IV','Associate Professor V',
    'Professor I','Professor II','Professor III','Professor IV','Professor V','Professor VI'
  ];
  LADDER.forEach((r, i) => {
    const esc2 = r.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    if (!new RegExp("label:'" + esc2 + "'").test(seedSrc)) {
      console.log('[FACDIR] positionLadder is missing rank ' + (i + 1) + ' — "' + r + '"'); fail++;
    }
  });
  if (!/rank:18/.test(seedSrc)) { console.log('[FACDIR] positionLadder does not run to rank 18'); fail++; }

  /* every seeded position must resolve to a ladder rank */
  {
    const FD = sandbox.DATA;
    const labels = new Set((FD.positionLadder || []).map(p => p.label));
    (FD.faculty || []).forEach(f => {
      if (!labels.has(f.position)) {
        console.log('[FACDIR] faculty "' + f.name + '" has an off-ladder position: ' + f.position); fail++;
      }
      if (typeof f.positionRank !== 'number') {
        console.log('[FACDIR] faculty "' + f.name + '" has no positionRank'); fail++;
      }
    });
    /* expertise values must all come from the canonical vocabulary */
    const opts = new Set(FD.expertiseOptions || []);
    if (opts.size < 20) { console.log('[FACDIR] expertiseOptions is suspiciously small (' + opts.size + ')'); fail++; }
    (FD.faculty || []).forEach(f => {
      (f.expertise || []).forEach(e => {
        if (!opts.has(e)) {
          console.log('[FACDIR] expertise "' + e + '" (' + f.name + ') is outside expertiseOptions'); fail++;
        }
      });
    });
    /* no per-professor target anywhere in the seed */
    (FD.faculty || []).forEach(f => {
      if ('targetHours' in f) { console.log('[FACDIR] faculty "' + f.name + '" still carries targetHours'); fail++; }
      if ('spec' in f)        { console.log('[FACDIR] faculty "' + f.name + '" still carries spec'); fail++; }
      if ('pct' in f)         { console.log('[FACDIR] faculty "' + f.name + '" still carries an attainment pct'); fail++; }
    });
    /* involvement index must be hydrated from the projects array */
    const withProjects = (FD.faculty || []).filter(f => (f.projectCount || 0) > 0).length;
    if (!withProjects) { console.log('[FACDIR] no faculty has a hydrated projectCount'); fail++; }
    (FD.faculty || []).forEach(f => {
      if (!Array.isArray(f.projectList)) {
        console.log('[FACDIR] faculty "' + f.name + '" has no projectList'); fail++;
      }
    });
  }

  /* ---- 3. directory page contract ---- */
  if (!dirP) {
    console.log('[FACDIR] pages/faculty-directory.html does not exist'); fail++;
  } else {
    const dir = dirP[1];
    ['dirGrid','dirSearch','dirCount','dirEmpty','dirAdd','profDrawer','profDrawerBody','selfPanel','hdrActions']
      .forEach(id => {
        if (!new RegExp('id="' + id + '"').test(dir)) {
          console.log('[FACDIR] faculty-directory.html is missing #' + id); fail++;
        }
      });
    /* ---- P0k: the card strip and the header export action were removed ----
       The page is the roster itself; KPI cards duplicated the board, and the
       export action duplicated what the roster already shows. */
    if (/id="summary"/.test(dir)) {
      console.log('[FACDIR] faculty-directory.html still renders the summary card strip (#summary)'); fail++;
    }
    if (/renderSummary/.test(dir)) {
      console.log('[FACDIR] faculty-directory.html still calls the removed renderSummary()'); fail++;
    }
    if (/btnExport|Export roster|Download my details/.test(dir)) {
      console.log('[FACDIR] faculty-directory.html still offers an export action'); fail++;
    }
    /* the header no longer carries a page description */
    if (/The single roster of extension faculty — profiles, declared expertise, and assignment details/.test(dir)) {
      console.log('[FACDIR] faculty-directory.html still carries the redundant header description'); fail++;
    }
    /* ...but the breadcrumb + heading anchors the rest of the page relies on must survive */
    ['id="heading"','id="subheading"','id="crumb"'].forEach(id => {
      if (!new RegExp(id).test(dir)) {
        console.log('[FACDIR] faculty-directory.html lost ' + id + ' — the role-aware header depends on it'); fail++;
      }
    });
    /* a removed function must not leave an orphaned helper behind */
    if (/const ico = /.test(dir)) {
      console.log('[FACDIR] faculty-directory.html still defines the now-unused ico() helper'); fail++;
    }
    /* the faculty-side self-edit view must exist and lock admin-controlled fields */
    ['selfEditBody','locked-field','You can edit your own'].forEach(m => {
      if (!dir.includes(m)) { console.log('[FACDIR] faculty-directory.html has no self-edit view (' + m + ')'); fail++; }
    });
    if (!/ROLE === 'faculty'/.test(dir)) {
      console.log('[FACDIR] faculty-directory.html is not role-aware'); fail++;
    }
    /* the entry point must be labelled "Faculty Directory" */
    if (!/Faculty Directory/.test(fac)) {
      console.log('[FACDIR] faculty-management.html has no "Faculty Directory" entry point'); fail++;
    }
    if (/Add Faculty<\/span>/.test(fac)) {
      console.log('[FACDIR] faculty-management.html still labels the header action "Add Faculty"'); fail++;
    }
    if (!/faculty-directory\.html\?role=admin&new=1/.test(fac)) {
      console.log('[FACDIR] faculty-management.html does not route its header action to the directory page'); fail++;
    }
    /* the nav must expose the new page to both roles */
    if (!/href:'faculty-directory\.html'/.test(layoutSrc)) {
      console.log('[FACDIR] layout.js nav does not reference faculty-directory.html'); fail++;
    }
    if (!/subs:\['faculty-directory'\]/.test(layoutSrc)) {
      console.log('[FACDIR] layout.js does not list faculty-directory as a sub-page'); fail++;
    }
  }
}

/* ==========================================================================
   [DASH] dashboard-admin.html — Community Reach + Action Center removed,
   and the page rebuilt on shared layout primitives (v4.11).
   The two removed blocks left orphans behind on other pages (totHours, ico,
   bp) — so guard the absence of the SECTIONS and of their dead wiring.
   ========================================================================== */
{
  const dash = (allHtml.find(([n]) => n === 'dashboard-admin.html') || [,''])[1];
  if (!dash) { console.log('[DASH] dashboard-admin.html not found'); fail++; }
  else {
    /* ---- removed surfaces ---- */
    if (/Community Reach/.test(dash)) {
      console.log('[DASH] dashboard-admin.html still renders the Community Reach panel'); fail++;
    }
    if (/id="chReach"/.test(dash)) {
      console.log('[DASH] dashboard-admin.html still has a #chReach canvas'); fail++;
    }
    if (/chReach/.test(dash)) {
      console.log('[DASH] dashboard-admin.html still references chReach in script'); fail++;
    }
    if (/Action Center/.test(dash)) {
      console.log('[DASH] dashboard-admin.html still renders the Action Center'); fail++;
    }

    /* ---- the surviving charts must remain ---- */
    /* ---- the surviving chart must remain. #chBudget is GONE: the budget panel
       became compact bullet rows on 2026-09-25, because the grouped bar chart
       could only label its axis with project CODES. ---- */
    ['chHours'].forEach(id => {
      if (!new RegExp('id="' + id + '"').test(dash)) {
        console.log('[DASH] dashboard-admin.html is missing #' + id); fail++;
      }
    });

    /* ---- Recent Activity MOVED (owner request 2026-09-25): it is now the Audit
       Logs page, and the dashboard must NOT still carry the panel. ---- */
    if (/Recent Activity/.test(dash)) {
      console.log('[DASH] dashboard-admin.html still renders the Recent Activity panel — it moved to audit-logs.html'); fail++;
    }
    if (!/audit-logs\.html/.test(dash)) {
      console.log('[DASH] dashboard-admin.html does not point at the Audit Logs page'); fail++;
    }

    /* ---- shared layout primitives ---- */
    if (!/\.dash-section|class="dash-section"/.test(dash)) {
      console.log('[DASH] dashboard-admin.html does not use the .dash-section rhythm'); fail++;
    }
    if (!/dash-panel/.test(dash)) {
      console.log('[DASH] dashboard-admin.html does not use the .dash-panel wrapper'); fail++;
    }
    /* responsive: no bare grid-cols-3 / grid-cols-4 (they must be breakpoint-prefixed) */
    if (/class="[^"]*\bgrid-cols-3\b[^"]*"/.test(dash.replace(/lg:grid-cols-3/g, ''))) {
      console.log('[DASH] dashboard-admin.html has a bare grid-cols-3 — it will not collapse on mobile'); fail++;
    }
    if (/\bgrid-cols-4\b/.test(dash.replace(/xl:grid-cols-4/g, ''))) {
      console.log('[DASH] dashboard-admin.html has a bare grid-cols-4 — it will not collapse on mobile'); fail++;
    }
    /* dead colour constants left behind by the removed chart */
    ['EMERALD','SLATE'].forEach(c => {
      if (new RegExp('\\b' + c + "\\s*=\\s*'#").test(dash)) {
        console.log('[DASH] dashboard-admin.html still declares the now-unused ' + c + ' colour constant'); fail++;
      }
    });

    /* ---- the functional harness must exist ---- */
    if (!fs.existsSync(path.join(dir, '_dashtest.cjs'))) {
      console.log('[DASH] the dashboard functional harness _dashtest.cjs is missing'); fail++;
    }
    /* ---- and the shared shim both functional harnesses depend on ---- */
    if (!fs.existsSync(path.join(dir, '_shim.cjs'))) {
      console.log('[DASH] the shared sandbox _shim.cjs is missing (_dashtest + _facultytest require it)'); fail++;
    }
  }

  /* the shared CSS primitives must actually be defined */
  const css = fs.readFileSync(path.join(dir, 'assets/css/smartcemes.css'), 'utf8');
  ['.dash-section','.dash-sec-eyebrow','.dash-sec-title','.dash-head','.dash-kpi','.dash-panel','.dash-chart']
    .forEach(cls => {
      if (!css.includes(cls + ' ') && !css.includes(cls + ',') && !css.includes(cls + '{')) {
        console.log('[DASH] smartcemes.css is missing the ' + cls + ' primitive'); fail++;
      }
    });
}

/* ============================================================================
   [TARGETS] targets.html — honest-fix pass (v4.12, P0n / 1B)
   The page does NOT yet implement the final target model, and must not claim
   to. Guarding both directions: the pending banner must exist, and the fields
   that asserted the rejected model must not come back.
   ========================================================================= */
{
  const tpath = path.join(dir, 'pages/targets.html');
  const tgt = fs.readFileSync(tpath, 'utf8');
  const seed = fs.readFileSync(path.join(dir, 'assets/js/seed-data.js'), 'utf8');

  /* ---- the model-pending banner is REQUIRED (§2.2C) ---- */
  if (!/does not yet reflect the final target model/i.test(tgt)) {
    console.log('[TARGETS] targets.html is missing the "does not yet reflect the final target model" banner (§2.2C)'); fail++;
  }
  if (!/badge-yellow shrink-0 ml-auto">Pending</.test(tgt)) {
    console.log('[TARGETS] targets.html is missing the Pending badge on the model-pending banner'); fail++;
  }

  /* ---- BANNED: per-program target fields (no program-level target exists) ---- */
  ['targetPrograms','targetProjects'].forEach(f => {
    /* the seed must not carry the field at all */
    if (new RegExp('\\b' + f + '\\s*:').test(seed)) {
      console.log('[TARGETS] seed-data.js still declares universityTargets[].' + f + ' — no program-level target exists (§2.2B)'); fail++;
    }
    /* and no page may read it */
    if (new RegExp('\\.' + f + '\\b').test(tgt)) {
      console.log('[TARGETS] targets.html still reads t.' + f); fail++;
    }
  });

  /* ---- BANNED: the over-promising drawdown prose ---- */
  if (/hrs remaining this year/i.test(tgt)) {
    console.log('[TARGETS] targets.html still uses the over-promising "hrs remaining this year" prose'); fail++;
  }

  /* ---- the FY/AY mix must not return ---- */
  if (/FY\s*\$?\{?t\.year|>FY \$\{|FY2026 targets/i.test(tgt)) {
    console.log('[TARGETS] targets.html still labels the year as FY instead of AY'); fail++;
  }
  if (!/label:'AY 2026–2027'/.test(seed) || !/label:'AY 2025–2026'/.test(seed)) {
    console.log('[TARGETS] universityTargets rows are missing their AY label'); fail++;
  }

  /* ---- REQUIRED: the pool/drawdown language is present ---- */
  if (!/drawn down by/i.test(tgt)) {
    console.log('[TARGETS] targets.html does not state the pool/drawdown relationship'); fail++;
  }
}

/* ============================================================================
   [OBJ] program-detail.html — the Objective authoring surface is GONE (P0o / 2A-ii)
   It was unreachable: openObjManager was defined but never called, and it was
   the only entry to the Objective Manager / form modals. The SEED survives
   because calendar.html and program-narratives.html read it read-only — so the
   guard bans the authoring surface WITHOUT banning the data.
   ========================================================================= */
{
  const detail = fs.readFileSync(path.join(dir, 'pages/program-detail.html'), 'utf8');
  const seed = fs.readFileSync(path.join(dir, 'assets/js/seed-data.js'), 'utf8');

  /* ---- BANNED: the two modals ---- */
  ['mObjList','mObjForm'].forEach(id => {
    if (detail.includes('id="' + id + '"')) {
      console.log('[OBJ] program-detail.html still renders the #' + id + ' modal'); fail++;
    }
  });
  ['Objective Manager','Objective statement *'].forEach(lbl => {
    if (detail.includes(lbl)) {
      console.log('[OBJ] program-detail.html still contains the "' + lbl + '" UI'); fail++;
    }
  });

  /* ---- BANNED: every function of the removed block ---- */
  ['openObjManager','renderObjManager','editObjective','deleteObjective',
   'openObjForm','saveObjective','objKpiChanged','computedForKpi'].forEach(fn => {
    if (new RegExp('\\b' + fn + '\\b').test(detail)) {
      console.log('[OBJ] program-detail.html still defines/uses ' + fn); fail++;
    }
  });

  /* ---- BANNED: the form field ids and the shared index state ---- */
  ['ofObjective','ofUnit','ofBaseline','ofTarget','ofActual','ofDate','ofEvidence',
   'ofActualWrap','ofComputedWrap','editObjIdx'].forEach(id => {
    if (new RegExp('\\b' + id + '\\b').test(detail)) {
      console.log('[OBJ] program-detail.html still references #' + id); fail++;
    }
  });

  /* ---- REQUIRED: the seed SURVIVES (the two read-only consumers depend on it) ---- */
  if (!/const programObjectives = \{/.test(seed)) {
    console.log('[OBJ] seed-data.js lost programObjectives — calendar.html Feed 3 and program-narratives.html objectivesMet read it'); fail++;
  }
  if (!/programObjectives, programNarratives,/.test(seed)) {
    console.log('[OBJ] programObjectives is no longer exported from seed-data.js'); fail++;
  }
  if (!/D\.programObjectives/.test(fs.readFileSync(path.join(dir, 'pages/calendar.html'), 'utf8'))) {
    console.log('[OBJ] calendar.html no longer reads programObjectives — Feed 3 would render empty'); fail++;
  }
  if (!/DATA\.programObjectives/.test(fs.readFileSync(path.join(dir, 'pages/program-narratives.html'), 'utf8'))) {
    console.log('[OBJ] program-narratives.html no longer reads programObjectives — the objectives-met chip would throw'); fail++;
  }
}

console.log(fail === 0 ? '\nOK — all checks passed.' : '\n' + fail + ' issue(s) found.');
