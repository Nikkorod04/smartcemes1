<?php

namespace App\Livewire\Interagency;

use App\Models\AssessmentAnalysis;
use App\Models\InteragencyAgency;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Interagency Catalogue (revision §5 Phase R6 / §4.6 / D-R10).
 *
 * WHY THIS SCREEN EXISTS
 * ----------------------
 * A barangay survey surfaces needs CESO must not deliver — feeding programmes,
 * medical missions, roads, water systems. The AI now hands those off as Tier-2
 * referrals instead of dressing them up as CESO recommendations. But a referral
 * is only defensible if the agency it names is real, which means the agency list
 * has to be something the Director can see, edit and retire.
 *
 * This page is that list. `PromptV2` injects exactly the active rows, so editing
 * a row here changes what the model is allowed to cite — the catalogue is the
 * guardrail's allow-list, not decorative copy.
 *
 * WHY IT MATCHES `docs/prototype/pages/interagency.html`
 * -----------------------------------------------------
 * The prototype is the visual and behavioural contract for this module (§16).
 * The header copy, the "How the AI uses this catalogue" explainer, the four
 * summary tiles, the filter row, the table columns and the "Referrals raised by
 * the AI" panel are all carried over deliberately.
 *
 * THREE DELIBERATE DIVERGENCES FROM THE PROTOTYPE (§17.4 records the first two)
 * ---------------------------------------------------------------------------
 *  1. **No Pillar / MOA / Abbreviation fields.** The real schema (§4.6) has
 *     `agency_code`, `agency_name`, `mandate`, `need_category`, `sample_service`,
 *     `contact_info`, `active`, `sort_order`. The prototype's `pillar` and `moa`
 *     columns were prototype-only scaffolding: CESO's three pillars classify
 *     *CESO's own* thrusts, so attaching a pillar to DOH or DPWH would assert
 *     something the mandate does not support; and MOA tracking was never in the
 *     approved scope. The code column replaces `abbr` (the code *is* the
 *     abbreviation) and `sort_order` replaces hand-dragged ordering.
 *  2. **Retire, not delete.** Preferring `active = false` (plus the soft delete)
 *     keeps historical referrals resolvable. A hard delete would make past
 *     analyses look like they cited a hallucinated agency.
 *  3. **Scope wording, not tier chips (2026-10-02).** The UI labels the two AI
 *     output groups "CESO intervention" and "Requires interagency referral", and
 *     the explainer carries TWO chips rather than three: the third tier is an
 *     internal rule that by design never produces output, so showing it only
 *     confused the reader. The prototype still renders three tier chips; that
 *     divergence is accepted and Laravel-only (revisions.md not yet updated).
 *
 * The referrals panel reuses `AssessmentAnalysis::resolvedReferrals()`, which is
 * the same resolver the AI review screen uses — so both surfaces agree on what
 * counts as verified.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [
        'agency_code' => '',
        'agency_name' => '',
        'mandate' => '',
        'need_category' => '',
        'sample_service' => '',
        'contact_info' => '',
        'active' => true,
        'sort_order' => 0,
    ];

    /* ------------------------------------------------------------------ */
    /* Filters — bound to the URL so a filtered catalogue is shareable */
    /* ------------------------------------------------------------------ */

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'category', except: '')]
    public string $categoryFilter = '';

    #[Url(as: 'status', except: '')]
    public string $statusFilter = '';

    public function mount(): void
    {
        Gate::authorize('manage', InteragencyAgency::class);
    }

    /* ------------------------------------------------------------------ */
    /* Form */
    /* ------------------------------------------------------------------ */

    public function create(): void
    {
        Gate::authorize('manage', InteragencyAgency::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        Gate::authorize('manage', InteragencyAgency::class);

        $agency = InteragencyAgency::withTrashed()->findOrFail($id);

        $this->editingId = $agency->id;
        $this->form = [
            'agency_code' => $agency->agency_code,
            'agency_name' => $agency->agency_name,
            'mandate' => $agency->mandate ?? '',
            'need_category' => $agency->need_category ?? '',
            'sample_service' => $agency->sample_service ?? '',
            'contact_info' => $agency->contact_info ?? '',
            'active' => (bool) $agency->active,
            'sort_order' => (int) $agency->sort_order,
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        Gate::authorize('manage', InteragencyAgency::class);

        $this->form['agency_code'] = strtoupper(trim((string) $this->form['agency_code']));

        $this->validate([
            'form.agency_code' => 'required|string|max:24|alpha_dash|unique:interagency_agencies,agency_code,'.($this->editingId ?? 'NULL').',id',
            'form.agency_name' => 'required|string|max:255',
            'form.mandate' => 'nullable|string|max:255',
            'form.need_category' => 'required|string|max:160',
            'form.sample_service' => 'nullable|string|max:2000',
            'form.contact_info' => 'nullable|string|max:255',
            'form.active' => 'boolean',
            'form.sort_order' => 'nullable|integer|min:0|max:9999',
        ], [
            'form.need_category.required' => 'The intervention category is required — it is what the AI matches a need against.',
            'form.agency_code.alpha_dash' => 'The code may contain letters, numbers, dashes and underscores only (e.g. DSWD).',
        ]);

        $payload = [
            'agency_code' => $this->form['agency_code'],
            'agency_name' => $this->form['agency_name'],
            'mandate' => $this->form['mandate'] ?: null,
            'need_category' => $this->form['need_category'],
            'sample_service' => $this->form['sample_service'] ?: null,
            'contact_info' => $this->form['contact_info'] ?: null,
            'active' => (bool) $this->form['active'],
            'sort_order' => (int) (($this->form['sort_order'] === '' || $this->form['sort_order'] === null)
                ? 0
                : $this->form['sort_order']),
        ];

        if ($this->editingId) {
            $agency = InteragencyAgency::withTrashed()->findOrFail($this->editingId);
            $wasActive = (bool) $agency->active;

            // Editing a retired row re-activates it — that is the restore path.
            $agency->deleted_at = null;
            $agency->fill($payload)->save();

            $note = $payload['active']
                ? ($wasActive ? '' : ' · now available for AI referrals')
                : ' · retired from AI referrals';

            $this->dispatch('sc-toast', message: "Agency {$agency->agency_code} updated{$note}", type: 'success');
        } else {
            $agency = InteragencyAgency::create($payload);
            $this->dispatch('sc-toast', message: "Agency {$agency->agency_code} added — available for AI referrals", type: 'success');
        }

        $this->showForm = false;
        $this->resetForm();
    }

    /**
     * Retire or restore an agency.
     *
     * Retiring flips `active` to false rather than deleting the row, because
     * `AssessmentAnalysis::resolvedReferrals()` resolves against the current
     * catalogue: a hard delete would retroactively turn every past referral to
     * this agency into an "unverified citation", which is a false alarm. A
     * retired row still loses `PromptV2` access immediately, which is the
     * behaviour that actually matters for the guardrail.
     */
    public function toggleActive(int $id): void
    {
        Gate::authorize('manage', InteragencyAgency::class);

        $agency = InteragencyAgency::withTrashed()->findOrFail($id);

        $agency->active = ! $agency->active;
        $agency->deleted_at = null;
        $agency->save();

        $this->dispatch(
            'sc-toast',
            message: $agency->active
                ? "Agency {$agency->agency_code} restored — citable by the AI again"
                : "Agency {$agency->agency_code} retired — no longer citable by the AI",
            type: $agency->active ? 'success' : 'warn',
        );
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->categoryFilter = '';
        $this->statusFilter = '';
    }

    /* ------------------------------------------------------------------ */
    /* Render */
    /* ------------------------------------------------------------------ */

    public function render()
    {
        // Every row, including retired ones — the Director needs to see what
        // has been taken out of the AI's vocabulary, not just what is left.
        $agencies = InteragencyAgency::withTrashed()
            ->ordered()
            ->get();

        $filtered = $agencies->filter(function (InteragencyAgency $a) {
            if ($this->categoryFilter !== '' && $a->need_category !== $this->categoryFilter) {
                return false;
            }

            if ($this->statusFilter === 'active' && ! $a->active) {
                return false;
            }

            if ($this->statusFilter === 'retired' && $a->active) {
                return false;
            }

            if ($this->search === '') {
                return true;
            }

            $haystack = strtolower(implode(' ', [
                $a->agency_code,
                $a->agency_name,
                (string) $a->mandate,
                (string) $a->need_category,
                (string) $a->sample_service,
                (string) $a->contact_info,
            ]));

            return str_contains($haystack, strtolower(trim($this->search)));
        })->values();

        $referrals = $this->referralCards();

        return view('livewire.interagency.index', [
            'agencies' => $filtered,
            'totalCount' => $agencies->count(),
            'activeCount' => $agencies->where('active', true)->count(),
            'categories' => $agencies->pluck('need_category')->filter()->unique()->sort()->values(),
            'categoryCount' => $agencies->pluck('need_category')->filter()->unique()->count(),
            'referrals' => $referrals,
            'referralCount' => $referrals->count(),
            'unverifiedCount' => $referrals->filter(fn (array $r) => $r['agency'] === null)->count(),
        ]);
    }

    /**
     * The Tier-2 referrals the AI has actually raised, for the panel at the
     * bottom of the page. Each card carries the resolved agency so the panel can
     * say "Refer to DOH — Department of Health", and an unresolved one is kept
     * in the list (flagged) rather than dropped.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function referralCards(): Collection
    {
        return AssessmentAnalysis::query()
            ->whereNotNull('interagency_referrals')
            ->with('assessmentSummary.community')
            ->latest('id')
            ->limit(40)
            ->get()
            ->flatMap(function (AssessmentAnalysis $analysis) {
                return collect($analysis->resolvedReferrals())->map(fn (array $referral) => [
                    'need' => $referral['need'],
                    'rationale' => $referral['rationale'],
                    'agency' => $referral['agency'],
                    'as_cited' => $referral['as_cited'],
                    'community' => $analysis->community?->name,
                    'analysis_id' => $analysis->id,
                    'created_at' => $analysis->created_at,
                ]);
            })
            // Newest analyses first, and within one analysis keep the model's order.
            ->sortByDesc(fn (array $card) => $card['created_at']?->timestamp ?? 0)
            ->values();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'agency_code' => '',
            'agency_name' => '',
            'mandate' => '',
            'need_category' => '',
            'sample_service' => '',
            'contact_info' => '',
            'active' => true,
            'sort_order' => 0,
        ];
        $this->resetErrorBag();
    }
}
