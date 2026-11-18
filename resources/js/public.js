import Alpine from 'alpinejs';

// The public assessment page has no Livewire, so nothing else starts Alpine.
// (resources/js/app.js deliberately leaves the start to Livewire; this entry
// must NOT import app.js or it will pull in Chart.js and the staff bundle.)
window.Alpine = Alpine;

const DRAFT_PREFIX = 'sc.assess.';
const DEBOUNCE_MS = 400;

const clone = (value) => JSON.parse(JSON.stringify(value));

/**
 * The public needs-assessment form (after-defense revision, Item 1).
 *
 * Deliberately client-side: the staff wizard answers every chip with a server
 * round trip, which is fine in the office and unusable on barangay mobile data.
 * Here all answers live in the browser, the conditional follow-ups are computed
 * locally, and exactly one request will be made — on submit.
 *
 * Every answer is also written to localStorage, so a dropped signal, an
 * accidental back-swipe, or a dead battery does not lose the questionnaire.
 * Nothing reaches the office until the answers are actually sent.
 */
window.assessmentForm = function (config) {
    return {
        // ---- state ---------------------------------------------------------
        step: 1,
        done: false,
        consent: false,
        errors: {},
        restoredAt: null,
        form: clone(config.form),
        types: config.types || {},
        otherFields: config.otherFields || [],
        maxCaps: config.max || {},
        exclusives: config.exclusive || {},
        steps: config.steps || [],
        token: config.token,
        clientUuid: config.clientUuid,

        // ---- lifecycle -----------------------------------------------------
        init() {
            this._pristine = clone(config.form);
            this.restore();

            // Mobile browsers kill background tabs without warning, so save on
            // the way out rather than trusting the debounce.
            window.addEventListener('pagehide', () => this.saveNow());
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) this.saveNow();
            });
        },

        get draftKey() {
            return DRAFT_PREFIX + this.token;
        },

        get restoredLabel() {
            if (!this.restoredAt) {
                return '';
            }

            const time = this.restoredAt.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
            const sameDay = this.restoredAt.toDateString() === new Date().toDateString();

            return sameDay
                ? `Saved on this device at ${time}.`
                : `Saved on this device on ${this.restoredAt.toLocaleDateString()}.`;
        },

        // ---- draft persistence ---------------------------------------------
        persist() {
            clearTimeout(this._saveTimer);
            this._saveTimer = setTimeout(() => this.saveNow(), DEBOUNCE_MS);
        },

        saveNow() {
            try {
                localStorage.setItem(this.draftKey, JSON.stringify({
                    version: 1,
                    clientUuid: this.clientUuid,
                    step: Math.min(this.step, 5),
                    answers: this.form,
                    consent: this.consent,
                    saved_at: Date.now(),
                }));
            } catch (e) {
                // Private mode or a full quota: the form still works, drafts
                // simply will not survive a reload.
            }
        },

        restore() {
            let raw = null;

            try {
                raw = localStorage.getItem(this.draftKey);
            } catch (e) {
                return;
            }

            if (!raw) {
                return;
            }

            try {
                const draft = JSON.parse(raw);

                if (!draft || typeof draft.answers !== 'object' || draft.answers === null) {
                    return;
                }

                this.form = Object.assign(clone(this._pristine), draft.answers);
                this.step = Math.min(5, Math.max(1, parseInt(draft.step, 10) || 1));
                this.consent = draft.consent === true;

                if (draft.clientUuid) {
                    this.clientUuid = draft.clientUuid;
                }

                this.restoredAt = draft.saved_at ? new Date(draft.saved_at) : new Date();
            } catch (e) {
                // Corrupt draft: start clean rather than trap the respondent in
                // a half-restored form.
            }
        },

        startOver() {
            try {
                localStorage.removeItem(this.draftKey);
            } catch (e) {
                // Nothing to clean up.
            }

            this.form = clone(this._pristine);
            this.step = 1;
            this.consent = false;
            this.errors = {};
            this.restoredAt = null;
            this.done = false;
            this.clientUuid = this.uuid();
        },

        uuid() {
            if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                return window.crypto.randomUUID();
            }

            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
                const r = (Math.random() * 16) | 0;
                const v = c === 'x' ? r : (r & 0x3) | 0x8;

                return v.toString(16);
            });
        },

        // ---- answers -------------------------------------------------------
        isOn(field, option) {
            const value = this.form[field];

            if (this.types[field] === 'rating') {
                return !!value && value.overall === option;
            }

            if (Array.isArray(value)) {
                return value.indexOf(option) !== -1;
            }

            return value === option;
        },

        pick(field, option) {
            if (this.types[field] === 'rating') {
                this.form[field] = Object.assign({}, this.form[field] || {}, { overall: option });
            } else if (this.types[field] === 'multi') {
                this.pickMulti(field, option);
            } else {
                this.form[field] = option;
            }

            this.applyRules(field);
            this.persist();
        },

        pickMulti(field, option) {
            const current = Array.isArray(this.form[field]) ? this.form[field].slice() : [];
            const exclusive = this.exclusives[field];
            const cap = this.maxCaps[field];

            // "Flexible" / "No supplies" replace the whole selection...
            if (exclusive && option === exclusive) {
                this.form[field] = [option];

                return;
            }

            // ...and picking any normal option drops the exclusive one.
            if (current.indexOf(option) !== -1) {
                this.form[field] = current.filter((value) => value !== option);

                return;
            }

            const next = exclusive ? current.filter((value) => value !== exclusive) : current;

            // Capped groups (problems: up to 3) ignore the extra tap; the field
            // shows "3/3" so the respondent can see why nothing happened.
            if (cap && next.length >= cap) {
                return;
            }

            this.form[field] = next.concat([option]);
        },

        countOf(field) {
            return Array.isArray(this.form[field]) ? this.form[field].length : 0;
        },

        atCap(field) {
            const cap = this.maxCaps[field];

            return cap ? this.countOf(field) >= cap : false;
        },

        onNameInput(field, el) {
            const cleaned = el.value.replace(/[^a-zA-ZÑñ\s.\-']/g, '');

            if (cleaned !== el.value) {
                el.value = cleaned;
            }

            this.form[field] = cleaned;
            this.persist();
        },

        // ---- conditional rules ---------------------------------------------
        // Mirrors Assessments\Wizard::updated(): when a parent answer hides a
        // child, the child is cleared so the submitted record is internally
        // consistent instead of carrying answers nobody can see.
        applyRules(field) {
            const f = this.form;

            const clear = (...keys) => keys.forEach((key) => {
                f[key] = this.types[key] === 'multi' ? [] : '';
                delete f[key + '_other'];
            });

            const orgFields = [
                'organization_types',
                'organization_meeting_frequency',
                'organization_usual_activities',
                'position_in_organization',
            ];

            switch (field) {
                case 'interested_in_continuing_studies':
                    if (f[field] !== 'Yes') clear('areas_of_educational_interest');
                    break;

                case 'has_barangay_health_programs':
                    if (f[field] !== 'Yes') clear('benefits_from_barangay_programs', 'programs_benefited_from');
                    break;

                case 'benefits_from_barangay_programs':
                    if (f[field] !== 'Yes') clear('programs_benefited_from');
                    break;

                case 'has_own_toilet':
                    if (f[field] !== 'Yes') clear('toilet_type');
                    break;

                case 'keeps_animals':
                    if (f[field] !== 'Yes') clear('animals_kept');
                    break;

                case 'has_electricity':
                    if (f[field] === 'Yes') {
                        clear('light_source_without_power');
                    } else if (f[field] === 'No') {
                        clear('appliances_owned');
                    } else {
                        clear('appliances_owned', 'light_source_without_power');
                    }
                    break;

                case 'member_of_organization':
                    if (f[field] !== 'Yes') clear(...orgFields);
                    break;

                case 'available_for_training':
                    if (f[field] !== 'No') clear('reason_not_available');
                    break;

                case 'household_members_in_organization':
                    if (f[field] === 'None') {
                        clear(...orgFields);
                        f.member_of_organization = 'No';
                    } else {
                        f.member_of_organization = '';
                    }
                    break;

                default:
                    break;
            }
        },

        show() {
            const f = this.form;

            return {
                areas: f.interested_in_continuing_studies === 'Yes',
                benefits: f.has_barangay_health_programs === 'Yes',
                programs: f.has_barangay_health_programs === 'Yes' && f.benefits_from_barangay_programs === 'Yes',
                toilet: f.has_own_toilet === 'Yes',
                animals: f.keeps_animals === 'Yes',
                appliances: f.has_electricity === 'Yes',
                lighting: f.has_electricity === 'No',
                member: f.household_members_in_organization !== 'None',
                org: f.household_members_in_organization !== 'None' && f.member_of_organization === 'Yes',
                reason: f.available_for_training === 'No',
            };
        },

        // ---- navigation and submission --------------------------------------
        next() {
            if (this.step < 5) {
                this.step += 1;
                this.persist();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        prev() {
            if (this.step > 1) {
                this.step -= 1;
                this.persist();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        resumeFromDone() {
            this.done = false;
            this.step = 5;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        validate() {
            const errors = {};

            if (!String(this.form.respondent_first_name || '').trim()) {
                errors.respondent_first_name = 'Please enter the first name.';
            }

            if (!String(this.form.respondent_last_name || '').trim()) {
                errors.respondent_last_name = 'Please enter the last name.';
            }

            const age = String(this.form.respondent_age ?? '').trim();

            if (age !== '') {
                const parsed = Number(age);

                if (!Number.isInteger(parsed) || parsed < 15 || parsed > 120) {
                    errors.respondent_age = 'Enter an age between 15 and 120.';
                }
            }

            if (!this.consent) {
                errors.consent = 'Please confirm before sending.';
            }

            this.errors = errors;

            if (Object.keys(errors).length > 0) {
                this.done = false;

                // Send the respondent to the first step that actually has a
                // problem, so a missing name is never announced from step 5.
                const identityProblem = errors.respondent_first_name
                    || errors.respondent_last_name
                    || errors.respondent_age;

                this.step = identityProblem ? 1 : 5;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }

            return Object.keys(errors).length === 0;
        },

        submit() {
            if (!this.validate()) {
                return;
            }

            // Answers stay on the device until the office accepts them.
            this.saveNow();
            this.done = true;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    };
};

Alpine.start();
