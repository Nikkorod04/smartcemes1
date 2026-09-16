/* ============================================================
   SmartCEMES Layout Engine
   Renders role-aware sidebar + topbar, wires notifications,
   toasts, count-up animations, stagger reveals, chart theme.

   Page contract:
   <body class="..." data-role="admin|secretary|faculty" data-page="page-key">
     <div id="sc-topbar"></div>
     <div id="sc-sidebar"></div>
     <main> ...content... </main>
     <div id="sc-toasts" class="fixed bottom-5 right-5 z-[90] space-y-2 no-print"></div>
   </body>
   ============================================================ */

(() => {
  const ICONS = {
    grid:'<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>',
    users:'<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>',
    folder:'<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/></svg>',
    calendar:'<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>',
    pin:'<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>',
    people:'<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>',
    doc:'<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>',
    check:'<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    clock:'<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    wallet:'<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 12.75h.008v.008H12v-.008z"/></svg>',
    chart:'<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>',
    clipboard:'<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>',
    sparkles:'<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9.375 17.25l-.438-1.346a4.5 4.5 0 00-2.842-2.841L4.75 12.625l1.345-.438a4.5 4.5 0 002.842-2.841L9.375 8l.438 1.346a4.5 4.5 0 002.842 2.841l1.345.438-1.345.438a4.5 4.5 0 00-2.842 2.84zM18 9.75l-.375 1.152-.375-1.152a2.25 2.25 0 00-1.423-1.423L14.676 9l1.151-.375a2.25 2.25 0 001.423-1.423L17.625 6l.377 1.202a2.25 2.25 0 001.423 1.423l1.15.375-1.15.375a2.25 2.25 0 00-1.423 1.423z"/></svg>',
    shield:'<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>',
    bell:'<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>',
    logout:'<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>',
    search:'<svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>'
  };

  const NAV = {
    admin: [
      { section:'Overview', items:[
        { key:'dashboard-admin', label:'Dashboard', href:'dashboard-admin.html', icon:'grid' },
        { key:'calendar',        label:'Calendar',  href:'calendar.html', icon:'calendar' },
      ]},
      { section:'Management', items:[
        { key:'faculty-management', label:'Faculty Management', href:'faculty-management.html', icon:'users' },
        { key:'programs',           label:'Extension Programs', href:'programs.html', icon:'folder' },
        { key:'activities',         label:'Activities',         href:'activities.html', icon:'clipboard' },
        { key:'communities',        label:'Communities',        href:'communities.html', icon:'pin' },
        { key:'beneficiaries',      label:'Beneficiaries',      href:'beneficiaries.html', icon:'people' },
      ]},
      { section:'Approvals', items:[
        { key:'proposals',   label:'Proposals',            href:'proposals.html', icon:'doc', badge:3 },
        { key:'availability',label:'Availability Requests',href:'availability.html', icon:'clock', badge:2 },
      ]},
      { section:'Finance & Insights', items:[
        { key:'budget',  label:'Budget Monitoring', href:'budget.html', icon:'wallet' },
        { key:'reports', label:'Reports',           href:'reports.html', icon:'chart' },
      ]},
    ],
    secretary: [
      { section:'Overview', items:[
        { key:'dashboard-secretary', label:'Dashboard', href:'dashboard-secretary.html', icon:'grid' },
        { key:'calendar',            label:'Calendar',  href:'calendar.html', icon:'calendar' },
      ]},
      { section:'Validation', items:[
        { key:'assessment-review', label:'Assessment Review', href:'assessment-review.html', icon:'doc', badge:2 },
        { key:'ai-analysis',       label:'AI Analysis Review',href:'ai-analysis.html', icon:'sparkles', badge:1 },
      ]},
      { section:'Compliance', items:[
        { key:'compliance', label:'Compliance Tracker', href:'compliance.html', icon:'shield' },
      ]},
    ],
    faculty: [
      { section:'Overview', items:[
        { key:'dashboard-faculty', label:'Dashboard',   href:'dashboard-faculty.html', icon:'grid' },
        { key:'my-programs',       label:'My Programs', href:'my-programs.html', icon:'folder' },
        { key:'calendar',          label:'Calendar',    href:'calendar.html', icon:'calendar' },
      ]},
      { section:'My Extension Work', items:[
        { key:'proposal-new',  label:'Submit Proposal',     href:'proposal-new.html', icon:'doc' },
        { key:'proposals',     label:'My Proposals',        href:'proposals.html', icon:'folder' },
        { key:'assessment-form', label:'Encode Assessment', href:'assessment-form.html', icon:'clipboard' },
        { key:'availability',  label:'My Availability',     href:'availability.html', icon:'clock' },
      ]},
    ]
  };

  let ROLE = document.body.dataset.role || 'admin';
  // Demo role switching: ?role=admin|secretary|faculty overrides body attribute
  const __qp = new URLSearchParams(location.search);
  if (__qp.get('role') && ['admin','secretary','faculty'].includes(__qp.get('role'))) {
    ROLE = __qp.get('role');
  }
  let PAGE = document.body.dataset.page || '';
  const U = () => (window.DATA && window.DATA.users) ? window.DATA.users[ROLE] : { name:'â€¦', role:'', initials:'?' };

  function svg(name, cls) {
    return `<span class="${cls||''} w-5 h-5 inline-flex shrink-0 [&>svg]:w-full [&>svg]:h-full">${ICONS[name]||''}</span>`;
  }

  /* ---------- Sidebar ---------- */
  function buildSidebar() {
    const groups = NAV[ROLE].map(g => `
      <div class="mb-5">
        <p class="px-3 mb-2 text-[10.5px] font-bold uppercase tracking-[.12em] text-gray-400">${g.section}</p>
        <nav class="space-y-1">
          ${g.items.map(it => `
            <a href="${it.href}" class="sc-nav-link ${it.key===PAGE?'active':''}">
              ${svg(it.icon)}
              <span>${it.label}</span>
              ${it.badge ? `<span class="sc-nav-badge">${it.badge}</span>` : ''}
            </a>`).join('')}
        </nav>
      </div>`).join('');

    const el = document.getElementById('sc-sidebar');
    el.innerHTML = `
      <aside class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-100 flex flex-col no-print">
        <!-- Brand -->
        <div class="h-16 flex items-center gap-3 px-5 border-b border-gray-100">
          <div class="w-9 h-9 rounded-xl bg-lnu flex items-center justify-center shadow-sm">
            <span class="text-white font-black text-sm tracking-tight">SC</span>
          </div>
          <div class="leading-tight">
            <p class="font-extrabold text-charcoal text-[15px] tracking-tight">Smart<span class="text-gold-600">CEMES</span></p>
            <p class="text-[10.5px] text-gray-400 font-medium">Leyte Normal University</p>
          </div>
        </div>
        <!-- Nav -->
        <div class="flex-1 overflow-y-auto px-3 py-4">${groups}</div>
        <!-- Footer user -->
        <div class="border-t border-gray-100 p-3">
          <div class="flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-gray-50 transition cursor-pointer">
            <span class="avatar w-9 h-9 text-xs">${U().initials}</span>
            <div class="min-w-0">
              <p class="text-[13px] font-semibold text-charcoal truncate">${U().name}</p>
              <p class="text-[11px] text-gray-400 truncate">${U().role}</p>
            </div>
            <button onclick="location.href='login.html'" title="Sign out"
              class="ml-auto p-2 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition">${svg('logout')}</button>
          </div>
        </div>
      </aside>`;
  }

  /* ---------- Topbar ---------- */
  function pageTitle() {
    for (const g of NAV[ROLE]) {
      const hit = g.items.find(i => i.key === PAGE);
      if (hit) return hit.label;
    }
    return document.title.split('Â·')[0].trim() || 'SmartCEMES';
  }

  function notifIcon(key, color) {
    const map = { doc:'doc', check:'check', clock:'clock', chart:'chart', ai:'sparkles', cal:'calendar' };
    const tones = { blue:'text-lnu-600 bg-lnu-50', green:'text-emerald-600 bg-emerald-50', yellow:'text-amber-600 bg-amber-50', red:'text-red-600 bg-red-50', gold:'text-gold-700 bg-gold-50' };
    return `<span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 ${tones[color]||tones.blue}">${svg(map[key]||'bell','w-[18px] h-[18px]')}</span>`;
  }

  function buildTopbar() {
    const list = (window.DATA.notifications[ROLE]) || [];
    const unread = list.filter(n => n.unread).length;
    const el = document.getElementById('sc-topbar');
    el.innerHTML = `
      <header class="sticky top-0 z-30 h-16 bg-white/90 backdrop-blur border-b border-gray-100 flex items-center pl-72 pr-6 gap-4 no-print">
        <div>
          <h1 class="text-[17px] font-extrabold text-charcoal tracking-tight leading-none">${pageTitle()}</h1>
          <p class="text-[11px] text-gray-400 mt-1 font-medium">Community Extension Services Office Â· LNU</p>
        </div>
        <div class="ml-auto flex items-center gap-2">
          <label class="relative hidden md:block">
            ${svg('search','absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400')}
            <input class="input !w-64 !pl-9 !py-2 bg-gray-50 border-transparent focus:bg-white" placeholder="Search anythingâ€¦">
          </label>
          <div x-data="{open:false}" class="relative">
            <button @click="open=!open" class="relative p-2.5 rounded-xl hover:bg-gray-100 transition text-gray-500">
              ${svg('bell')}
              ${unread?`<span class="bell-dot"></span>`:''}
            </button>
            <div x-cloak x-show="open" @click.outside="open=false" x-transition.opacity.duration.150ms
                 class="absolute right-0 mt-2 w-96 card p-0 overflow-hidden shadow-pop" style="display:none">
              <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                <p class="font-bold text-[13.5px] text-charcoal">Notifications</p>
                <span class="badge badge-gold">${unread} new</span>
              </div>
              <div class="max-h-96 overflow-y-auto divide-y divide-gray-50">
                ${list.map(n => `
                  <div class="flex gap-3 px-4 py-3 hover:bg-gray-50 transition cursor-pointer ${n.unread?'bg-blue-50/30':''}">
                    ${notifIcon(n.icon,n.color)}
                    <div class="min-w-0">
                      <p class="text-[13px] font-semibold text-charcoal leading-snug">${n.title}</p>
                      <p class="text-[12px] text-gray-500 leading-snug mt-0.5">${n.body}</p>
                      <p class="text-[10.5px] text-gray-400 mt-1">${n.time}</p>
                    </div>
                    ${n.unread?'<span class="w-2 h-2 rounded-full bg-gold-500 mt-2 shrink-0 pulse-dot"></span>':''}
                  </div>`).join('')}
              </div>
              <button class="w-full py-2.5 text-[12.5px] font-semibold text-lnu-800 hover:bg-lnu-50 transition">Mark all as read</button>
            </div>
          </div>
          <div class="w-px h-7 bg-gray-200 mx-1"></div>
          <div class="flex items-center gap-2.5 pl-1 pr-2 py-1 rounded-xl hover:bg-gray-100 transition cursor-pointer">
            <span class="avatar w-8 h-8 text-[11px]">${U().initials}</span>
            <div class="hidden lg:block leading-tight">
              <p class="text-[12.5px] font-bold text-charcoal">${U().name}</p>
              <p class="text-[10.5px] text-gray-400">${U().role}</p>
            </div>
          </div>
        </div>
      </header>`;
  }

  /* ---------- Toasts ---------- */
  function toast(msg, type='success') {
    const tones = {
      success:{ cls:'border-emerald-200 bg-emerald-50 text-emerald-800', icon:'check' },
      info:   { cls:'border-blue-200 bg-blue-50 text-blue-800',          icon:'doc' },
      warn:   { cls:'border-amber-200 bg-amber-50 text-amber-800',       icon:'clock' },
      error:  { cls:'border-red-200 bg-red-50 text-red-800',             icon:'doc' }
    };
    const t = tones[type] || tones.success;
    const box = document.getElementById('sc-toasts') || (()=>{ const d=document.createElement('div'); d.id='sc-toasts'; d.className='fixed bottom-5 right-5 z-[90] space-y-2 no-print'; document.body.appendChild(d); return d; })();
    const el = document.createElement('div');
    el.className = `sc-toast flex items-center gap-3 rounded-xl border px-4 py-3 shadow-pop max-w-sm ${t.cls}`;
    el.innerHTML = `${svg(t.icon,'w-5 h-5')}<p class="text-[13px] font-semibold">${msg}</p>`;
    box.appendChild(el);
    setTimeout(()=>{ el.style.transition='opacity .3s, transform .3s'; el.style.opacity='0'; el.style.transform='translateX(24px)'; setTimeout(()=>el.remove(),320); }, 3600);
  }

  /* ---------- Count-up ---------- */
  function easeOut(t){ return 1 - Math.pow(1-t, 3); }
  function animateCount(el) {
    const target = parseFloat(el.dataset.count);
    const dec = parseInt(el.dataset.decimals||'0');
    const prefix = el.dataset.prefix||''; const suffix = el.dataset.suffix||'';
    const dur = parseInt(el.dataset.duration||'1200'); const t0 = performance.now();
    (function frame(now){
      const p = Math.min((now-t0)/dur, 1);
      const v = target * easeOut(p);
      el.textContent = prefix + v.toLocaleString('en-PH',{minimumFractionDigits:dec,maximumFractionDigits:dec}) + suffix;
      if (p<1) requestAnimationFrame(frame);
    })(t0);
  }
  function runCounters(scope=document) {
    scope.querySelectorAll('[data-count]').forEach(el=>{
      if (el.dataset.done) return; el.dataset.done = '1';
      animateCount(el);
    });
  }
  function stagger(scope=document) {
    scope.querySelectorAll('.reveal-item').forEach((el,i)=>{ el.style.animationDelay = (i*70)+'ms'; });
  }

  /* ---------- Inline icon tokens: [[icon-name]] ---------- */
  function expandIcons(scope) {
    scope = scope || document.body;
    const walker = document.createTreeWalker(scope, NodeFilter.SHOW_TEXT);
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(n => {
      if (!n.nodeValue || n.nodeValue.indexOf('[[') === -1) return;
      const frag = document.createDocumentFragment();
      n.nodeValue.split(/(\[\[[a-z][a-z0-9-]*\]\])/).forEach(part => {
        const m = part.match(/^\[\[([a-z][a-z0-9-]*)\]\]$/);
        if (m && ICONS[m[1]]) {
          const tmp = document.createElement('div');
          tmp.innerHTML = svg(m[1]);
          frag.appendChild(tmp.firstElementChild);
        } else if (part) {
          frag.appendChild(document.createTextNode(part));
        }
      });
      n.parentNode.replaceChild(frag, n);
    });
  }

  /* ---------- Keep demo role across in-app navigation ---------- */
  function carryRole() {
    if (!__qp.get('role')) return;
    document.querySelectorAll('#sc-sidebar a[href$=".html"], #sc-topbar a[href$=".html"]').forEach(a => {
      const url = new URL(a.getAttribute('href'), location.href);
      const file = url.pathname.split('/').pop();
      if (file === 'login.html') return;
      a.setAttribute('href', file + '?role=' + ROLE);
    });
  }

  /* ---------- Boot ---------- */
  function boot() {
    expandIcons();
    buildSidebar();
    buildTopbar();
    carryRole();
    document.body.insertAdjacentHTML('beforeend','<div id="sc-toasts" class="fixed bottom-5 right-5 z-[90] space-y-2 no-print"></div>');
    stagger();
    runCounters();
  }
  document.addEventListener('DOMContentLoaded', boot);

  /* ---------- Chart.js global defaults ---------- */
  document.addEventListener('DOMContentLoaded', () => {
    if (!window.Chart) return;
    Chart.defaults.font.family = 'Figtree, sans-serif';
    Chart.defaults.font.size = 11.5;
    Chart.defaults.color = '#6b7280';
    Chart.defaults.borderColor = '#f1f2f5';
    Chart.defaults.plugins.legend.labels.usePointStyle = true;
    Chart.defaults.plugins.legend.labels.boxWidth = 7;
    Chart.defaults.plugins.tooltip.backgroundColor = '#0a2a66';
    Chart.defaults.plugins.tooltip.padding = 12;
    Chart.defaults.plugins.tooltip.cornerRadius = 10;
    Chart.defaults.plugins.tooltip.titleFont = { weight:'700' };
  });

  /* ---------- Public helpers ---------- */
  window.SC = {
    icons: ICONS,
    svg,
    toast,
    runCounters,
    role: () => ROLE,
    money: n => '₱' + Number(n).toLocaleString('en-PH'),
    pct: (a,b) => b ? Math.round(a/b*100) : 0
  };
})();
