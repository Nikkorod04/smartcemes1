/* Headless smoke test: execute each page's inline <script> against a minimal
   DOM stub wired to the real seed data, then invoke the page's DOMContentLoaded
   handlers. Catches undefined helpers, bad property reads, and render crashes. */
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const dir = __dirname;

const seedSrc = fs.readFileSync(path.join(dir, 'assets/js/seed-data.js'), 'utf8');
const layoutSrc = fs.readFileSync(path.join(dir, 'assets/js/layout.js'), 'utf8');

/* ---- minimal DOM ---- */
function makeEl(tag) {
  const el = {
    tagName: (tag || 'div').toUpperCase(),
    _html: '', _text: '', className: '', dataset: {}, style: {},
    children: [], value: '', files: [], disabled: false,
    classList: {
      _s: new Set(),
      add(...c) { c.forEach(x => this._s.add(x)); },
      remove(...c) { c.forEach(x => this._s.delete(x)); },
      toggle(c, f) { f === undefined ? (this._s.has(c) ? this._s.delete(c) : this._s.add(c)) : (f ? this._s.add(c) : this._s.delete(c)); },
      contains(c) { return this._s.has(c); }
    },
    get innerHTML() { return this._html; },
    set innerHTML(v) { this._html = String(v); },
    get textContent() { return this._text; },
    set textContent(v) { this._text = String(v); },
    appendChild(c) { this.children.push(c); return c; },
    replaceChild() {}, insertAdjacentHTML() {}, setAttribute() {}, getAttribute() { return null; },
    addEventListener() {}, removeEventListener() {}, querySelector() { return null; },
    querySelectorAll() { return []; }, closest() { return null; }, focus() {}, blur() {},
    getBoundingClientRect() { return { width: 800, height: 400, top: 0, left: 0 }; },
    getContext() {
      if (this._ctx) return this._ctx;
      const grad = { addColorStop() {} };
      this._ctx = new Proxy({}, {
        get: (t, k) => {
          if (k === 'createLinearGradient' || k === 'createRadialGradient') return () => grad;
          if (k === 'measureText') return () => ({ width: 10 });
          if (k === 'canvas') return el;
          return () => {};
        },
        set: () => true
      });
      return this._ctx;
    }
  };
  return el;
}

function makeDoc(bodyRole, bodyPage, html) {
  const body = makeEl('body');
  body.dataset = { role: bodyRole || 'admin', page: bodyPage || '' };
  const listeners = {};
  /* Browsers expose every element with an id as a window/document global.
     Reproduce that here so pages using bare ids (e.g. `tiles.innerHTML`) work. */
  const byId = {};
  const titleMatch = (html || '').match(/<title>([^<]*)<\/title>/);
  const idSet = new Set([...(html || '').matchAll(/\bid="([A-Za-z][\w-]*)"/g)].map(m => m[1]));
  const doc = {
    body,
    title: titleMatch ? titleMatch[1] : 'SmartCEMES',
    documentElement: makeEl('html'),
    getElementById: id => { if (!byId[id]) { byId[id] = makeEl('div'); byId[id].id = id; } return byId[id]; },
    querySelector: () => null,
    querySelectorAll: () => [],
    createElement: makeEl,
    createDocumentFragment: () => makeEl('fragment'),
    createTreeWalker: () => ({ nextNode: () => null }),
    addEventListener: (ev, fn) => { (listeners[ev] = listeners[ev] || []).push(fn); },
    removeEventListener() {},
    _fire: ev => (listeners[ev] || []).forEach(fn => fn()),
    _ids: idSet,
    readyState: 'complete'
  };
  return doc;
}

const pagesDir = path.join(dir, 'pages');
const pages = fs.readdirSync(pagesDir).filter(f => f.endsWith('.html'));
let fails = 0;

for (const page of pages) {
  const html = fs.readFileSync(path.join(pagesDir, page), 'utf8');
  const mRole = html.match(/data-role="(\w+)"/);
  const mPage = html.match(/data-page="([\w-]+)"/);
  const doc = makeDoc(mRole && mRole[1], mPage && mPage[1], html);

  const sandbox = {
    console, document: doc,
    location: { search: '', href: 'http://x/pages/' + page, pathname: '/pages/' + page, replace() {}, assign() {}, reload() {} },
    URLSearchParams, setTimeout: (fn) => { try { fn(); } catch (e) { throw e; } }, clearTimeout() {},
    Chart: function () { return new Proxy({}, { get: (t, k) => (k === 'destroy' ? () => {} : () => {}) }); }, navigator: { userAgent: 'node' }, alert() {},
    NodeFilter: { SHOW_TEXT: 4, SHOW_ELEMENT: 1, FILTER_ACCEPT: 1, FILTER_REJECT: 2, FILTER_SKIP: 3 },
    /* history / scroll / matchMedia: pages that manage view state need these */
    history: { pushState() {}, replaceState() {}, back() {}, state: null },
    scrollTo() {}, scrollBy() {}, matchMedia: () => ({ matches: false, addEventListener() {}, removeEventListener() {} }),
    requestAnimationFrame: fn => { try { fn(0); } catch (e) { throw e; } }, cancelAnimationFrame() {},
    getComputedStyle: () => ({ getPropertyValue: () => '' }),
    innerWidth: 1440, innerHeight: 900, scrollY: 0
  };
  /* window.addEventListener must work — pages register popstate/resize listeners */
  const winListeners = {};
  sandbox.addEventListener = (ev, fn) => { (winListeners[ev] = winListeners[ev] || []).push(fn); };
  sandbox.removeEventListener = () => {};
  sandbox._fire = ev => (winListeners[ev] || []).forEach(fn => fn());
  sandbox.window = sandbox;
  /* deep auto-vivifying defaults tree so Chart.defaults.a.b.c = … never throws */
  const deep = () => new Proxy(function () {}, {
    get: (t, k) => (k === Symbol.toPrimitive || k === 'then' || k === 'toString') ? undefined : deep(),
    set: () => true,
    apply: () => undefined
  });
  sandbox.Chart.defaults = deep();
  sandbox.Chart.getChart = () => null;
  sandbox.Chart.register = () => {};
  /* expose element ids as globals, exactly like a real browser window */
  doc._ids.forEach(id => { if (!(id in sandbox)) sandbox[id] = doc.getElementById(id); });
  vm.createContext(sandbox);

  try {
    vm.runInContext(seedSrc, sandbox, { filename: 'seed-data.js' });
    vm.runInContext(layoutSrc, sandbox, { filename: 'layout.js' });
    /* run each inline script block */
    const blocks = [...html.matchAll(/<script(?![^>]*\bsrc=)[^>]*>([\s\S]*?)<\/script>/g)];
    blocks.forEach((b, i) => vm.runInContext(b[1], sandbox, { filename: page + '#' + i }));
    /* fire DOMContentLoaded so the page's render functions actually execute */
    doc._fire('DOMContentLoaded');
    /* also fire the layout boot a second time for pages that register late */
    console.log('[ok  ] ' + page);
  } catch (e) {
    const frames = (e.stack || '').split('\n').filter(l => /\.html#\d+/.test(l)).slice(0, 1);
    console.log('[FAIL] ' + page + ' → ' + e.message + (frames.length ? '   @' + frames[0].trim().replace(/.*\.html#/, '#') : ''));
    fails++;
  }
}

console.log(fails ? '\n' + fails + ' page(s) threw at runtime.' : '\nAll pages execute cleanly.');
