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

/**
 * Reports workspace (2026-11-20): report catalog + scope filters + A4 paper.
 *
 * The report itself is server-rendered; this only builds the preview URL and
 * scales the A4 landscape page (1123 x 794 CSS px) into the available width.
 * "Print / Save as PDF" prints the iframe document, whose @media print rules
 * are the same rules used by the standalone report page.
 */
window.reportsWorkspace = function (config) {
    return {
        report: 'annual',
        year: config.year,
        college: '',
        program: '',
        community: '',
        projectId: config.projects[0]?.id ?? '',
        scale: 1,
        frameHeight: 794,
        reports: [
            { key: 'annual', label: 'Annual Extension Performance' },
            { key: 'community-impact', label: 'Community Partner Impact' },
            { key: 'rendered-hours', label: 'Faculty Rendered Hours' },
            { key: 'project-performance', label: 'Project Performance' },
        ],

        get selectedReport() {
            return this.reports.find((r) => r.key === this.report) || this.reports[0];
        },

        get filteredProjects() {
            return config.projects.filter((p) =>
                (!this.college || String(p.college) === String(this.college)) &&
                (!this.program || String(p.program) === String(this.program)) &&
                (!this.community || p.communities.map(String).includes(String(this.community)))
            );
        },

        get selectedProject() {
            return this.filteredProjects.find((p) => String(p.id) === String(this.projectId))
                || this.filteredProjects[0]
                || null;
        },

        get previewUrl() {
            if (this.report === 'project-performance') {
                return this.selectedProject
                    ? `/reports/results-framework/${this.selectedProject.id}?preview=1`
                    : null;
            }

            const params = new URLSearchParams({ year: String(this.year), preview: '1' });

            if (this.college) params.set('college', this.college);
            if (this.program) params.set('program', this.program);
            if (this.community) params.set('community', this.community);

            return `/reports/${this.report}?${params.toString()}`;
        },

        get fullUrl() {
            if (!this.previewUrl) return '#';

            const url = new URL(this.previewUrl, window.location.origin);
            url.searchParams.delete('preview');

            return url.pathname + (url.search || '');
        },

        get scopeLabel() {
            const parts = this.report === 'project-performance'
                ? ['All years']
                : [String(this.year)];

            parts.push(this.college ? (config.collegeNames[this.college] || 'Selected college') : 'All colleges');
            parts.push(this.program ? (config.programNames[this.program] || 'Selected program') : 'All programs');
            parts.push(this.community ? (config.communityNames[this.community] || 'Selected community') : 'All communities');

            return parts.join(' · ');
        },

        get paperStyle() {
            return `width:${Math.round(1123 * this.scale)}px;height:${Math.round(this.frameHeight * this.scale)}px;`;
        },

        get frameStyle() {
            return `width:1123px;height:${this.frameHeight}px;transform:scale(${this.scale});transform-origin:top left;border:0;`;
        },

        selectReport(key) {
            this.report = key;
            this.$nextTick(() => this.fit());
        },

        reset() {
            this.year = config.year;
            this.college = '';
            this.program = '';
            this.community = '';
            this.projectId = config.projects[0]?.id ?? '';
            this.$nextTick(() => this.fit());
        },

        init() {
            this.$nextTick(() => this.fit());
            this.$watch('previewUrl', () => this.$nextTick(() => setTimeout(() => this.fit(), 180)));
            this.$watch('filteredProjects', (projects) => {
                if (projects.length && !projects.some((p) => String(p.id) === String(this.projectId))) {
                    this.projectId = projects[0].id;
                }
            });

            if (window.ResizeObserver && this.$refs.viewport) {
                new ResizeObserver(() => this.fit()).observe(this.$refs.viewport);
            }
        },

        fit() {
            const frame = this.$refs.frame;
            const viewport = this.$refs.viewport;

            if (!frame || !viewport) {
                return;
            }

            let contentHeight = 794;

            try {
                contentHeight = Math.max(794, frame.contentDocument?.documentElement?.scrollHeight || 0);
            } catch (e) {
                // Same-origin should be readable; a failure falls back to one page.
            }

            this.frameHeight = contentHeight;
            this.scale = Math.min(1, viewport.clientWidth > 0 ? viewport.clientWidth / 1123 : 1);
        },

        printReport() {
            const win = this.$refs.frame?.contentWindow;

            if (win) {
                win.focus();
                win.print();
            }
        },
    };
};

document.addEventListener('livewire:init', () => {
    Livewire.on('sc-toast', ({ message, type }) => SC.toast(message, type));
});