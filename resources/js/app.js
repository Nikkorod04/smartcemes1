import './bootstrap';

import Chart from 'chart.js/auto';

window.Chart = Chart;
window.Chart = Chart;

// Chart.js global defaults (prototype layout.js theme)
Chart.defaults.font.family = 'Figtree, sans-serif';
Chart.defaults.font.size = 11.5;
Chart.defaults.color = '#6b7280';
Chart.defaults.borderColor = '#f1f2f5';
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.boxWidth = 7;
Chart.defaults.plugins.tooltip.backgroundColor = '#0a2a66';
Chart.defaults.plugins.tooltip.padding = 12;
Chart.defaults.plugins.tooltip.cornerRadius = 10;
Chart.defaults.plugins.tooltip.titleFont = { weight: '700' };

window.SC = {
    toast(msg, type = 'success') {
        const tones = {
            success: { cls: 'border-emerald-200 bg-emerald-50 text-emerald-800', icon: 'check' },
            info: { cls: 'border-blue-200 bg-blue-50 text-blue-800', icon: 'doc' },
            warn: { cls: 'border-amber-200 bg-amber-50 text-amber-800', icon: 'clock' },
            error: { cls: 'border-red-200 bg-red-50 text-red-800', icon: 'doc' },
        };
        const t = tones[type] || tones.success;
        const icons = { check: 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z', doc: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z', clock: 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z' };
        let box = document.getElementById('sc-toasts');
        if (!box) {
            box = document.createElement('div');
            box.id = 'sc-toasts';
            box.className = 'fixed bottom-5 right-5 z-[90] space-y-2 no-print';
            document.body.appendChild(box);
        }
        const el = document.createElement('div');
        el.className = `sc-toast flex items-center gap-3 rounded-xl border px-4 py-3 shadow-pop max-w-sm ${t.cls}`;
        el.innerHTML = `<span class="w-5 h-5 inline-flex shrink-0"><svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="${icons[t.icon]}"/></svg></span><p class="text-[13px] font-semibold"></p>`;
        el.querySelector('p').textContent = msg;
        box.appendChild(el);
        setTimeout(() => {
            el.style.transition = 'opacity .3s, transform .3s';
            el.style.opacity = '0';
            el.style.transform = 'translateX(24px)';
            setTimeout(() => el.remove(), 320);
        }, 3600);
    },
};

// NOTE: do NOT call Alpine.start() manually — Livewire starts Alpine after
// registering its components (an early start races Livewire and breaks
// $wire/@this expressions evaluated before component registration).

document.addEventListener('livewire:init', () => {
    Livewire.on('sc-toast', ({ message, type }) => SC.toast(message, type));
});