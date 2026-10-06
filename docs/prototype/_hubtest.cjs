/* Functional test for the two-view hub in colleges.html.
   The smoke harness only proves the page *loads*; this drives the actual
   interaction: render view 1, click a college, assert view 2 shows that
   college's projects, then assert back-navigation restores view 1. */
const fs = require('fs'), path = require('path'), vm = require('vm');
const dir = __dirname;
const html = fs.readFileSync(path.join(dir, 'pages/colleges.html'), 'utf8');

/* ---------- minimal DOM with real event dispatch ---------- */
let idSeq = 0;
function makeEl(tag) {
  const e = {
    _id: ++idSeq, tagName: (tag || 'div').toUpperCase(),
    children: [], parentNode: null, nodeValue: null, nodeType: 1,
    className: '', id: '', dataset: {}, style: {}, _html: '', _listeners: {},
    classList: {
      _s: new Set(),
      add(...c) { c.forEach(x => this._s.add(x)); },
      remove(...c) { c.forEach(x => this._s.delete(x)); },
      toggle(c, on) { if (on === undefined) { this._s.has(c) ? this._s.delete(c) : this._s.add(c); } else { on ? this._s.add(c) : this._s.delete(c); } },
      contains(c) { return this._s.has(c); }
    },
    appendChild(c) { c.parentNode = this; this.children.push(c); return c; },
    removeChild(c) { this.children = this.children.filter(x => x !== c); return c; },
    insertAdjacentHTML() {},
    addEventListener(ev, fn) { (this._listeners[ev] = this._listeners[ev] || []).push(fn); },
    removeEventListener() {},
    dispatch(ev, target) {
      const t = target || this;
      /* the handler does e.target.closest(...), so the *target* must expose
         closest()/dataset — not the event object. */
      if (t && typeof t.closest !== 'function') {
        t.closest = s => findClosest(t, s);
      }
      const e = { target: t, currentTarget: this, preventDefault() {}, stopPropagation() {} };
      (this._listeners[ev] || []).forEach(fn => fn(e));
    },
    querySelector() { return null; },
    querySelectorAll() { return []; },
    setAttribute(k, v) { this[k] = v; },
    getAttribute(k) { return this[k]; },
    focus() {}, scrollIntoView() {},
    get firstElementChild() { return this.children.find(c => c.nodeType === 1) || null; },
    set innerHTML(v) { this._html = String(v); this.children = []; },
    get innerHTML() { return this._html; },
    set textContent(v) { this._text = String(v); },
    get textContent() { return this._text !== undefined ? this._text : this._html.replace(/<[^>]*>/g, ''); }
  };
  return e;
}
function findClosest(node, sel) {
  const cls = sel.replace(/^\./, '');
  let n = node;
  while (n) {
    if (n.classList && n.classList.contains(cls)) return n;
    if (n.dataset && n.dataset[cls]) return n;
    n = n.parentNode;
  }
  return null;
}

const byId = {};
const doc = {
  body: makeEl('body'), title: 'Manage Extension Programs · SmartCEMES',
  documentElement: makeEl('html'),
  getElementById: id => { if (!byId[id]) { const e = makeEl('div'); e.id = id; byId[id] = e; } return byId[id]; },
  querySelector: () => null, querySelectorAll: () => [],
  createElement: makeEl, createDocumentFragment: () => makeEl('fragment'),
  createTreeWalker: () => ({ nextNode: () => null }),
  addEventListener() {}, removeEventListener() {}
};

/* provide the ids colleges.html declares */
[...html.matchAll(/id="([A-Za-z][\w-]*)"/g)].forEach(m => doc.getElementById(m[1]));

const listeners = {};
const win = {
  addEventListener: (ev, fn) => { (listeners[ev] = listeners[ev] || []).push(fn); },
  removeEventListener() {},
  scrollTo() {}, location: { search: '', href: 'http://x/pages/colleges.html' }
};
const sandbox = {
  console, document: doc, window: win, URLSearchParams, navigator: { userAgent: 'node' },
  setTimeout: fn => fn(), clearTimeout() {},
  history: { pushState() {}, replaceState() {}, state: null },
  location: { search: '', href: 'http://x/pages/colleges.html', pathname: '/pages/colleges.html' },
  NodeFilter: { SHOW_TEXT: 4 }, requestAnimationFrame: fn => fn(0),
  MutationObserver: function () { return { observe() {}, disconnect() {} }; },
  Chart: function () { return new Proxy({}, { get: () => () => {} }); }
};
sandbox.Chart.defaults = new Proxy(function(){}, { get: () => new Proxy(function(){}, { get: () => ()=>{}, set: () => true, apply: () => undefined }), set: () => true });
sandbox.window = Object.assign(sandbox, { DATA: null });

vm.createContext(sandbox);
vm.runInContext(fs.readFileSync(path.join(dir, 'assets/js/seed-data.js'), 'utf8'), sandbox);
vm.runInContext(fs.readFileSync(path.join(dir, 'assets/js/layout.js'), 'utf8'), sandbox);

/* run the page's own inline script */
const blocks = [...html.matchAll(/<script(?![^>]*\bsrc=)[^>]*>([\s\S]*?)<\/script>/g)].map(m => m[1]);
const pageScript = blocks.find(b => b.includes('viewColleges'));
if (!pageScript) { console.log('FAIL: could not locate the page script'); process.exit(1); }

/* Capture DOMContentLoaded from the page, then fire it. */
const domReady = [];
doc.addEventListener = (ev, fn) => { if (ev === 'DOMContentLoaded') domReady.push(fn); };

const pageSandbox = Object.create(null);
Object.assign(pageSandbox, sandbox);
pageSandbox.console = console;
pageSandbox.document = doc;
pageSandbox.window = pageSandbox;
pageSandbox.addEventListener = (ev, fn) => { (listeners[ev] = listeners[ev] || []).push(fn); };
pageSandbox.removeEventListener = () => {};
pageSandbox.location = sandbox.location;
pageSandbox.history = sandbox.history;
pageSandbox.URLSearchParams = URLSearchParams;
pageSandbox.NodeFilter = sandbox.NodeFilter;
pageSandbox.requestAnimationFrame = fn => fn(0);
pageSandbox.scrollTo = () => {};
pageSandbox.scrollBy = () => {};
pageSandbox.setTimeout = fn => fn();
pageSandbox.clearTimeout = () => {};
pageSandbox.getComputedStyle = () => ({ getPropertyValue: () => '' });
pageSandbox.MutationObserver = sandbox.MutationObserver;
/* auto-vivifying Chart stub — mirrors the real smoke harness */
const deepObj = () => new Proxy(function () {}, {
  get: (t, k) => (k === Symbol.toPrimitive || k === 'then' || k === 'toString') ? undefined : deepObj(),
  set: () => true, apply: () => undefined
});
pageSandbox.Chart = function () { return new Proxy({}, { get: (t, k) => (k === 'destroy' ? () => {} : () => {}) }); };
pageSandbox.Chart.defaults = deepObj();
pageSandbox.Chart.getChart = () => null;
pageSandbox.SC = sandbox.SC;
pageSandbox.DATA = sandbox.DATA;

vm.createContext(pageSandbox);
/* layout.js/seed-data.js already ran in `sandbox`; re-run them here so SC and
   DATA exist as page-sandbox globals, exactly like a real <script> load order. */
vm.runInContext(fs.readFileSync(path.join(dir, 'assets/js/seed-data.js'), 'utf8'), pageSandbox);
vm.runInContext(fs.readFileSync(path.join(dir, 'assets/js/layout.js'), 'utf8'), pageSandbox);
pageSandbox.SC = pageSandbox.window.SC || sandbox.SC;
pageSandbox.DATA = pageSandbox.window.DATA;

try { vm.runInContext(pageScript, pageSandbox); } catch (e) { console.log('FAIL: script threw —', e.message); process.exit(1); }
if (!domReady.length) { console.log('FAIL: page never registered DOMContentLoaded'); process.exit(1); }
try { domReady.forEach(fn => fn()); } catch (e) { console.log('FAIL: DOMContentLoaded handler threw —', e.message); process.exit(1); }

let pass = 0, fail = 0;
const check = (label, cond) => { if (cond) { console.log('  [ok  ] ' + label); pass++; } else { console.log('  [FAIL] ' + label); fail++; } };

console.log('=== view 1: colleges only ===');
const cards = byId['collegeCards'];
check('collegeCards renders content', cards && cards.innerHTML.length > 200);
/* count only card ROOTS: `<button class="college-card ..."` — the bare
   substring also matches college-card-top / college-card-cta / etc. */
const cardRoots = re => (cards.innerHTML.match(re) || []).length;
check('renders exactly 4 college cards', cardRoots(/class="college-card[ "]/g) === 4);
check('all 4 codes present (CAS/COE/CME/GRAD)', ['CAS','COE','CME','GRAD'].every(c => cards.innerHTML.includes('data-code="' + c + '"')));
check('view 1 contains NO project table', !cards.innerHTML.includes('<table'));
check('view 1 contains NO program table', !/Broad programs/.test(cards.innerHTML));
check('view 1 contains NO faculty grid', !cards.innerHTML.includes('fac-grid'));
/* no per-college training-hours figure: targets exist at university/project level only */
check('college cards carry NO "Training hours" bar', !/Training hours/.test(cards.innerHTML));
check('college cards carry NO college-level hours figure', !/trainingHoursTarget/.test(cards.innerHTML));
check('view 1 has NO university roll-up tiles', !/annual target/.test(cards.innerHTML));
check('college cards DO show projects/programs/faculty metrics', ['Projects','Programs','Faculty'].every(x => cards.innerHTML.includes(x)));

/* ---- P0l: card redesign + no page header ------------------------------
   Each card opens with a logo/media area, then the name/supporting text,
   then the metric row. The page header banner is gone entirely. */
check('every card opens with a logo/media area',
  (cards.innerHTML.match(/class="college-media/g) || []).length === 4);
check('the media area carries a placeholder logo marker',
  (cards.innerHTML.match(/college-logo-ph/g) || []).length === 4);
check('the old 6px top strip is gone',
  !/college-card-top/.test(cards.innerHTML));
check('the media area is tinted with the college brand colour',
  /class="college-media w-full" style="background:#003599"/.test(cards.innerHTML) &&
  /class="college-media w-full" style="background:#F6B800"/.test(cards.innerHTML) &&
  /class="college-media w-full" style="background:#10b981"/.test(cards.innerHTML) &&
  /class="college-media w-full" style="background:#7c3aed"/.test(cards.innerHTML));
check('a crest renders inside each media area',
  (cards.innerHTML.match(/class="college-crest"/g) || []).length === 4);
check('the college name follows the media area',
  /<\/span>\s*<span class="block px-6 pt-5 pb-6 flex-1 text-left">\s*<span class="block font-extrabold text-\[17px\]/.test(cards.innerHTML));
/* balanced heights: the thrust text and the badge row are height-reserved
   so a short thrust cannot make its card shorter than the others */
check('the thrust block is height-reserved for balance',
  /min-h-\[54px\]/.test(cards.innerHTML));
check('the badge row is height-reserved even with no badges',
  /min-h-\[24px\]/.test(cards.innerHTML));
/* the header banner must be gone from the page source. NOTE: #viewColleges is
   a stub element in this harness (only its classList is exercised), so assert
   against the page source rather than its innerHTML. */
check('the header banner is removed from view 1',
  !/Manage Extension Programs<\/h2>/.test(html) &&
  !/Select a college to see its extension projects/.test(html));
check('view 1 still offers the all-programs route',
  /programs\.html\?role=admin/.test(html));

console.log('=== view swap: click CAS ===');
const casCard = makeEl('button');
casCard.classList.add('college-card');
casCard.dataset.code = 'CAS';
cards.appendChild(casCard);
cards.dispatch('click', casCard);
check('viewColleges hidden after click', byId['viewColleges'].classList.contains('hidden'));
check('viewCollege shown after click', !byId['viewCollege'].classList.contains('hidden'));
check('hero shows College of Arts and Sciences', byId['collegeHero'].innerHTML.includes('College of Arts and Sciences'));
check('hero carries NO college-level hours target', !/college's annual target/.test(byId['collegeHero'].innerHTML));
check('KPIs rendered', byId['collegeKpis'].innerHTML.length > 100);
check('KPIs carry NO college-level hours target', !/of \d[\d,]* target/.test(byId['collegeKpis'].innerHTML));
check('project grid rendered', byId['projGrid'].innerHTML.length > 300);
/* LITRAWIYA moved CAS -> COE on 2026-09-25. A project's college follows its
   SUBJECT DOMAIN (remedial reading is teacher education), not the college of its
   lead's specialization. CAS is therefore 4 projects, COE 2, CME 1. */
check('CAS projects listed (HANDA)', byId['projGrid'].innerHTML.includes('HANDA'));
check('CAS project count = 4', (byId['projGrid'].innerHTML.match(/class="proj-card[ "]/g) || []).length === 4);
check('does NOT leak COE project (BATANG MATINIK)', !byId['projGrid'].innerHTML.includes('BATANG MATINIK'));
check('does NOT leak COE project (LITRAWIYA)', !byId['projGrid'].innerHTML.includes('LITRAWIYA'));
check('does NOT leak CME project (KABUHIAN)', !byId['projGrid'].innerHTML.includes('KABUHIAN'));
check('faculty grid rendered', byId['facGrid'].innerHTML.length > 100);
check('back button present', !!byId['backBtn']);

console.log('=== back navigation ===');
byId['backBtn'].dispatch('click');
check('viewColleges shown again', !byId['viewColleges'].classList.contains('hidden'));
check('viewCollege hidden again', byId['viewCollege'].classList.contains('hidden'));

console.log('=== deep link: ?college=CME ===');
sandbox.location.search = '?college=CME';
pageSandbox.location = sandbox.location;
try { domReady.forEach(fn => fn()); } catch (e) { console.log('  [note] re-render threw —', e.message); }
check('CME deep link lands on view 2', !byId['viewCollege'].classList.contains('hidden'));
check('CME shows KABUHIAN', byId['projGrid'].innerHTML.includes('KABUHIAN'));

console.log('=== deep link: ?college=COE ===');
sandbox.location.search = '?college=COE';
pageSandbox.location = sandbox.location;
try { domReady.forEach(fn => fn()); } catch (e) { console.log('  [note] re-render threw —', e.message); }
check('COE deep link lands on view 2', !byId['viewCollege'].classList.contains('hidden'));
check('COE shows LITRAWIYA (domain-derived, not the lead college)', byId['projGrid'].innerHTML.includes('LITRAWIYA'));
check('COE shows BATANG MATINIK', byId['projGrid'].innerHTML.includes('BATANG MATINIK'));
check('COE project count = 2', (byId['projGrid'].innerHTML.match(/class="proj-card[ "]/g) || []).length === 2);

console.log('\n' + (fail === 0 ? 'PASS — ' + pass + ' assertions' : fail + ' FAILED of ' + (pass + fail)));
process.exit(fail === 0 ? 0 : 1);
