/* Shared DOM/Chart sandbox for the functional harnesses.

   Extracted from _facultytest.cjs so _dashtest.cjs can use the identical
   shim. Behaviour must stay byte-compatible with the original: pages use
   bare id references as implicit globals, and document.body needs a real
   dataset map seeded from <body data-role>.
*/
const fs = require('fs'), path = require('path'), vm = require('vm');
const dir = __dirname;

/* ==========================================================================
   DOM shims — shared by all three suites
   ========================================================================== */
let idSeq = 0;
function makeEl(tag) {
  const e = {
    _id: ++idSeq, tagName: (tag || 'div').toUpperCase(),
    children: [], parentNode: null, nodeType: 1,
    className: '', id: '', dataset: {}, style: {}, _html: '', _listeners: {},
    _attrs: {}, value: '', textContent: '',
    classList: {
      _s: new Set(),
      add(...c) { c.forEach(x => this._s.add(x)); },
      remove(...c) { c.forEach(x => this._s.delete(x)); },
      toggle(c, on) { if (on === undefined) { this._s.has(c) ? this._s.delete(c) : this._s.add(c); } else { on ? this._s.add(c) : this._s.delete(c); } },
      contains(c) { return this._s.has(c); }
    },
    appendChild(c) { c.parentNode = this; this.children.push(c); return c; },
    removeChild(c) { this.children = this.children.filter(x => x !== c); return c; },
    /* ChildNode.remove() — used by SC.toast (layout.js) to tear a toast down
       after its timer. Without this the toast's deferred callback throws
       "el.remove is not a function" and takes the whole page script with it. */
    remove() { if (this.parentNode) this.parentNode.removeChild(this); return this; },
    insertAdjacentHTML(pos, h) { if (pos === 'beforeend') this._html += h; },
    addEventListener(ev, fn) { (this._listeners[ev] = this._listeners[ev] || []).push(fn); },
    removeEventListener() {},
    dispatch(ev, target) {
      const t = target || this;
      if (t && typeof t.closest !== 'function') t.closest = s => findClosest(t, s);
      const e = { target: t, currentTarget: this, preventDefault() {}, stopPropagation() {}, native: { target: t } };
      (this._listeners[ev] || []).forEach(fn => fn(e));
    },
    querySelector(sel) { return (this._qs || {})[sel] || findInHtml(this, sel); },
    querySelectorAll(sel) { return (this._qsa || {})[sel] || []; },
    contains() { return true; },
    setAttribute(k, v) { this._attrs[k] = v; },
    getAttribute(k) { return this._attrs[k]; },
    focus() {}, blur() {}, scrollIntoView() {},
    get firstElementChild() { return this.children.find(c => c.nodeType === 1) || null; },
    set innerHTML(v) { this._html = String(v); parseChildren(this); },
    get innerHTML() { return this._html; }
  };
  /* textContent must not be clobbered by the plain-property assignment above */
  let _text = '';
  Object.defineProperty(e, 'textContent', {
    get() { return _text || this._html.replace(/<[^>]*>/g, ''); },
    set(v) { _text = String(v); },
    configurable: true
  });
  return e;
}
function findClosest(node, sel) {
  const cls = sel.replace(/^\./, '');
  let n = node;
  while (n) {
    if (n.classList && n.classList.contains(cls)) return n;
    n = n.parentNode;
  }
  return null;
}
/* a shallow querySelector over rendered markup — enough for the id/class lookups
   the page scripts actually perform (e.g. `#msPlaceholder` inside a chips box) */
function findInHtml(el, sel) {
  const m = /^#([\w-]+)$/.exec(sel);
  if (!m) {
    const cls = /^\.([\w-]+)$/.exec(sel);
    if (!cls) return null;
    if (new RegExp('class="[^"]*\\b' + cls[1] + '\\b').test(el._html)) return makeEl('span');
    return null;
  }
  if (new RegExp('id="' + m[1] + '"').test(el._html)) {
    const c = makeEl('span'); c.id = m[1]; return c;
  }
  return null;
}
/* when innerHTML is set, carve out child elements for the selectors the pages use */
function parseChildren(el) {
  el._qsa = {}; el._qs = {};
  const grab = (cls) => {
    const out = [];
    const re = new RegExp('<(button|div|span|a|article)\\b[^>]*class="[^"]*\\b' + cls + '\\b[^"]*"[^>]*>', 'g');
    let m;
    while ((m = re.exec(el._html))) {
      const child = makeEl(m[1]);
      child.classList.add(cls);
      const idm = /data-id="(\d+)"/.exec(m[0]); if (idm) child.dataset.id = idm[1];
      const mm  = /data-metric="(\w+)"/.exec(m[0]); if (mm) child.dataset.metric = mm[1];
      const vm2 = /data-v="([^"]*)"/.exec(m[0]); if (vm2) child.dataset.v = vm2[1];
      const cm  = /data-college="([^"]*)"/.exec(m[0]); if (cm) child.dataset.college = cm[1];
      child.parentNode = el;
      out.push(child);
    }
    return out;
  };
  el._qsa['.eng-row']    = grab('eng-row');
  el._qsa['.ms-option']  = grab('ms-option');
  el._qsa['.ms-chip']    = grab('ms-chip');
  el._qsa['.fac-card']   = grab('fac-card');
  el._qsa['.eng-row .eng-val'] = [];
}

function makeDoc(html, pageUrl) {
  const byId = {};
  const declaredIds = [...html.matchAll(/id="([A-Za-z][\w-]*)"/g)].map(m => m[1]);
  /* body must carry a real `dataset` map — pages set data-role on it and the
     layout/page scripts read it back as document.body.dataset.role */
  const body = makeEl('body');
  body.dataset = {};
  const bodyRole = /<body[^>]*data-role="([^"]*)"/.exec(html);
  if (bodyRole) body.dataset.role = bodyRole[1];
  const doc = {
    body, title: '', documentElement: makeEl('html'),
    getElementById: id => {
      if (!byId[id]) {
        const e = makeEl('input');
        e.id = id;
        byId[id] = e;
        /* seed authored input values so page code can read them */
        const vm2 = new RegExp('id="' + id + '"[^>]*value="([^"]*)"').exec(html);
        if (vm2) e.value = vm2[1];
      }
      return byId[id];
    },
    querySelector: () => null, querySelectorAll: () => [],
    createElement: makeEl, createDocumentFragment: () => makeEl('fragment'),
    createTreeWalker: () => ({ nextNode: () => null }),
    addEventListener() {}, removeEventListener() {},
    _byId: byId, _declaredIds: declaredIds
  };
  declaredIds.forEach(id => doc.getElementById(id));
  return doc;
}

function deepObj() {
  return new Proxy(function () {}, {
    get: (t, k) => (k === Symbol.toPrimitive || k === 'then' || k === 'toString') ? undefined : deepObj(),
    set: () => true, apply: () => undefined
  });
}

/* Boot one page: build a sandbox, run seed + layout, run the page script,
   fire DOMContentLoaded. Returns { sandbox, doc, charts, pageScript }. */
function boot(pageFile, opts) {
  opts = opts || {};
  const html = fs.readFileSync(path.join(dir, 'pages', pageFile), 'utf8');
  const doc = makeDoc(html, pageFile);

  const sandbox = {
    console, document: doc, URLSearchParams, navigator: { userAgent: 'node' },
    setTimeout: fn => fn(), clearTimeout() {},
    history: { pushState() {}, replaceState() {}, state: null },
    location: {
      search: opts.search || '',
      href: 'http://x/pages/' + pageFile + (opts.search || ''),
      pathname: '/pages/' + pageFile
    },
    NodeFilter: { SHOW_TEXT: 4 }, requestAnimationFrame: fn => fn(0),
    MutationObserver: function () { return { observe() {}, disconnect() {} }; },
    getComputedStyle: () => ({ getPropertyValue: () => '' })
  };
  sandbox.window = sandbox;
  sandbox.scrollTo = () => {}; sandbox.scrollBy = () => {};

  /* pages use bare id references as implicit globals (kpis.innerHTML …) */
  sandbox.__declaredIds = doc._declaredIds;
  doc._declaredIds.forEach(id => { if (!(id in sandbox)) sandbox[id] = doc.getElementById(id); });

  const charts = [];
  sandbox.Chart = function (canvas, cfg) { charts.push(cfg); return { destroy() {}, update() {} }; };
  sandbox.Chart.defaults = deepObj();
  sandbox.Chart.getChart = () => null;

  vm.createContext(sandbox);
  vm.runInContext(fs.readFileSync(path.join(dir, 'assets/js/seed-data.js'), 'utf8'), sandbox);
  vm.runInContext(fs.readFileSync(path.join(dir, 'assets/js/layout.js'), 'utf8'), sandbox);

  const blocks = [...html.matchAll(/<script(?![^>]*\bsrc=)[^>]*>([\s\S]*?)<\/script>/g)].map(m => m[1]);

  /* the tiny inline role-bootstrapping script sets document.body.dataset.role
     from ?role= before anything else runs — execute it, like a browser would */
  const roleScript = blocks.find(b => b.includes("dataset.role") && b.length < 400);
  if (roleScript) { try { vm.runInContext(roleScript, sandbox); } catch (e) { /* non-fatal */ } }

  const pageScript = blocks.find(b => b.includes(opts.marker || 'renderEngagement'));
  const ready = [];
  doc.addEventListener = (ev, fn) => { if (ev === 'DOMContentLoaded') ready.push(fn); };

  const forced = opts.forceMetric
    ? pageScript.replace("let metric = 'hours';", `let metric = '${opts.forceMetric}';`)
    : pageScript;

  let err = null;
  try { vm.runInContext(forced, sandbox); ready.forEach(fn => fn()); }
  catch (e) { err = e; }

  return { sandbox, doc, charts, pageScript, html, err, ready };
}

module.exports = { makeEl, makeDoc, boot, deepObj };
