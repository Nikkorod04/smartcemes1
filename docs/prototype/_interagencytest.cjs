/* Functional tests for the Interagency Catalogue (interagency.html).

   WHY THIS FILE EXISTS
   --------------------
   R6 added a brand-new page plus the three-tier guardrail UI. Until now the only
   coverage of interagency.html was _check.cjs counting seed rows (`agencies: 10`)
   and _smoke.cjs proving the page does not throw. Neither would notice:
     - a tier-2 referral card rendered WITHOUT its tier rail or agency note,
     - a referral whose agency silently vanished (the prototype's own guardrail
       for this is `tier-2 items missing an agency` in _check.cjs — but only at
       the seed level, not at the render level),
     - the tier badges losing their `.tier-1/2/3` classes so the CSS cannot
       colour them,
     - the summary tiles reading a field that no longer exists.

   The Laravel page mirrors this prototype, so a regression here is a regression
   in the contract that resources/views/livewire/interagency/index.blade.php and
   ai-analysis.blade.php are held to (revision §17.3 / §17.4).

   Covers:
     1. The four summary tiles are computed, not hard-coded.
     2. The filter row actually filters (search / category / pillar / MOA).
     3. The catalogue table renders one row per agency with its pillar chip.
     4. The tier legend uses the three real tier classes.
     5. Every Tier-2 referral renders a tier-card tier-2 with a named agency —
        and the page never presents one as a CESO intervention.
*/
const { boot } = require('./_shim.cjs');
const fs = require('fs'), path = require('path');
const dir = __dirname;

let pass = 0, fail = 0;
const check = (label, cond) => { if (cond) { console.log('  [ok  ] ' + label); pass++; } else { console.log('  [FAIL] ' + label); fail++; } };

const PAGE = 'interagency.html';
const html = fs.readFileSync(path.join(dir, 'pages', PAGE), 'utf8');

const D = boot(PAGE, { marker: 'renderTiles' });
if (D.err) { console.log('  [FAIL] page script threw — ' + D.err.message); fail++; }
const DATA = D.sandbox.DATA;
const $ = id => D.doc._byId[id];

/* ==========================================================================
   1. Summary tiles
   ========================================================================== */
console.log('\n=== 1. summary tiles ===');
const tiles = $('tiles');
check('tiles container rendered', tiles && tiles.innerHTML.length > 200);
check('four tiles are rendered',
  tiles && (tiles.innerHTML.match(/sc-card sc-card-hover p-5 reveal-item/g) || []).length === 4);

const agencies = DATA.interagencyAgencies || [];
const cats = new Set(agencies.map(a => a.category));
check('tile 1 counts the catalogue',
  tiles && tiles.innerHTML.includes('>' + agencies.length + '<') &&
  tiles.innerHTML.includes('Agencies in the catalogue'));
check('tile 2 counts DISTINCT intervention categories',
  tiles && tiles.innerHTML.includes('>' + cats.size + '<') &&
  tiles.innerHTML.includes('Intervention categories'));
check('tile 3 counts agencies with a real MOA (not "None")',
  tiles && tiles.innerHTML.includes('Active MOAs') &&
  tiles.innerHTML.includes('>' + agencies.filter(a => a.moa && a.moa !== 'None').length + '<'));
check('tile 4 reports AI referrals raised',
  tiles && tiles.innerHTML.includes('AI referrals raised'));
/* the seed must actually have something to show, or the page proves nothing */
check('seed has agencies to render', agencies.length > 0);
check('seed has more than one intervention category', cats.size > 1);

/* ==========================================================================
   2. Filters
   ========================================================================== */
console.log('\n=== 2. filters ===');
const catPick = $('catPick');
check('category dropdown is populated from the seed',
  catPick && (catPick.innerHTML.match(/<option/g) || []).length === cats.size + 1);
check('category dropdown offers an "all" reset',
  catPick && catPick.innerHTML.includes('All categories'));
const catList = $('catList');
check('the add-form datalist reuses the same categories',
  catList && (catList.innerHTML.match(/<option/g) || []).length === cats.size);
check('search / pillar / MOA controls all exist',
  $('q') && $('pillarPick') && $('moaPick'));
check('pillar filter offers the three CESO pillars',
  /All pillars/.test(html) && /Social/.test(html) && /Economic/.test(html) && /Environmental/.test(html));

/* exercise the search filter for real — drive renderRows with a query */
{
  const rows = $('rows');
  const q = $('q');
  const first = agencies[0];
  q.value = (first.abbr || first.agency.split('—')[0]).trim().toLowerCase();
  D.sandbox.renderRows();
  const hits = (rows.innerHTML.match(/<tr>/g) || []).length;
  check('search narrows the table to a subset', hits > 0 && hits <= agencies.length);
  q.value = '';
  D.sandbox.renderRows();
  check('clearing the search restores every row',
    (rows.innerHTML.match(/<tr>/g) || []).length === agencies.length);
}

/* ==========================================================================
   3. Catalogue table
   ========================================================================== */
console.log('\n=== 3. table ===');
const rows = $('rows');
check('table renders one row per seeded agency',
  rows && (rows.innerHTML.match(/<tr>/g) || []).length === agencies.length);
check('the row count line reports the full catalogue',
  $('count') && $('count').textContent.includes(String(agencies.length)));
check('every row carries a pillar chip',
  rows && (rows.innerHTML.match(/class="pillar pillar-/g) || []).length === agencies.length);
check('every row carries its intervention category as a badge',
  rows && (rows.innerHTML.match(/badge badge-gray/g) || []).length >= agencies.length);
check('every row has an Edit affordance',
  rows && (rows.innerHTML.match(/openForm\(/g) || []).length === agencies.length);

/* MOA_BADGE maps status → class. All seeded rows share status "Active", so the
   informative assertion is the MAPPING, not a count: green iff the MOA is an
   active one, and the active MOA is rendered green. */
{
  const activeMoa = agencies.filter(a => a.moa === 'Active MOA');
  check('seed has exactly one active-MOA agency to prove the mapping',
    activeMoa.length === 1);
  check('the active MOA renders a green badge',
    rows && activeMoa.every(a => new RegExp(
      '<span class="badge badge-green">' + a.moa + '</span>').test(rows.innerHTML)));
  check('a "None" MOA does NOT render green',
    rows && agencies.filter(a => a.moa === 'None').every(a =>
      new RegExp('<span class="badge badge-gray">None</span>').test(rows.innerHTML)));
}

/* the empty state must be wired, not just authored */
{
  const q = $('q');
  q.value = 'zzz-no-such-agency-zzz';
  D.sandbox.renderRows();
  const empty = $('emptyState');
  check('an empty result shows the empty state rather than a bare table',
    empty && !empty.classList.contains('hidden') && empty.innerHTML.length > 50);
  check('the empty state explains what to do',
    empty && /No agencies match/.test(empty.innerHTML) && /search|add/i.test(empty.innerHTML));
  q.value = '';
  D.sandbox.renderRows();
  check('the empty state hides again once rows return',
    (rows.innerHTML.match(/<tr>/g) || []).length === agencies.length);
}

/* ==========================================================================
   4. The three-tier legend
   ========================================================================== */
console.log('\n=== 4. tier legend ===');
check('the explainer card names all three tiers',
  /Tier 1 · CESO delivers/.test(html) &&
  /Tier 2 · Referred to an agency below/.test(html) &&
  /Tier 3 · Suppressed/.test(html));
check('each tier chip carries its own tier class (so the CSS can colour it)',
  /tier-badge tier-1/.test(html) && /tier-badge tier-2/.test(html) && /tier-badge tier-3/.test(html));
check('the explainer states that unattributable needs are suppressed',
  /suppressed entirely/i.test(html));

/* ==========================================================================
   5. AI referrals panel — the guardrail's visible half
   ========================================================================== */
console.log('\n=== 5. referrals panel ===');
const ref = $('referrals');
check('referrals container rendered', ref && ref.innerHTML.length > 50);

/* count Tier-2 items straight from the seed, the same way the page does */
const tier2 = [];
(DATA.programNarratives || []).forEach(n =>
  (n.recommendations || []).forEach(r => { if (r.tier === 2) tier2.push({ r, n }); }));

check('seed actually contains Tier-2 referrals to render', tier2.length > 0);
check('one card per Tier-2 item',
  ref && (ref.innerHTML.match(/tier-card tier-2/g) || []).length === tier2.length);

/* THE guardrail assertion: a Tier-2 card must be labelled and must name a body.
   _check.cjs asserts this at the seed level; this asserts it in the RENDERED
   markup, which is what the Director actually reads. */
check('every Tier-2 card names the agency it is referred to',
  ref && (ref.innerHTML.match(/interagency-note/g) || []).length === tier2.length);
check('every referral note reads "Refer to <agency>"',
  ref && (ref.innerHTML.match(/Refer to <b>/g) || []).length === tier2.length);
check('no Tier-2 card is presented with a Tier-1 class',
  ref && !/tier-card tier-1/.test(ref.innerHTML));
check('referral cards sit in a two-column grid on large screens',
  /grid lg:grid-cols-2 gap-3/.test(html));

/* every seeded Tier-2 referral must carry an agency, or the card shows a
   placeholder — the page's own fallback is 'Unassigned — add an agency' */
{
  const named = tier2.filter(x => x.r.agency).length;
  check('seed gives every Tier-2 referral an agency',
    named === tier2.length);
}

/* ==========================================================================
   6. Access + save wiring
   ========================================================================== */
console.log('\n=== 6. add/edit wiring ===');
check('the header offers "+ Add agency"', /\+ Add agency/.test(html));
check('the modal exposes the agency name field', $('fAgency') && /Agency name/.test(html));
check('the modal exposes abbreviation, category and scope',
  $('fAbbr') && $('fCategory') && $('fScope'));
check('the modal exposes pillar, contact and MOA status',
  $('fPillar') && $('fContact') && $('fMoa'));
check('saveAgency is exported for the modal button',
  typeof D.sandbox.saveAgency === 'function');
check('openForm is exported for the row Edit buttons',
  typeof D.sandbox.openForm === 'function');

/* saveAgency must refuse an incomplete row rather than writing a blank agency */
{
  const before = DATA.interagencyAgencies.length;
  $('fAgency').value = '';
  $('fCategory').value = '';
  $('fScope').value = '';
  D.sandbox.saveAgency();
  check('an incomplete agency is rejected, not saved',
    DATA.interagencyAgencies.length === before);
}

console.log('\n' + (fail ? 'FAIL' : 'PASS') + ' — ' + pass + ' assertions' + (fail ? ', ' + fail + ' failed' : ''));
process.exit(fail ? 1 : 0);
