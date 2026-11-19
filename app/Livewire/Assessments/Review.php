<?php

namespace App\Livewire\Assessments;

use App\Models\Community;
use App\Models\NeedsAssessment;
use App\Notifications\SmartCemesNotification;
use App\Services\AssessmentTemplate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Review extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $quarter = 'all';

    /** Queue source filter: all | staff | public | import. */
    #[Url(except: 'all')]
    public string $source = 'all';

    /** Queue period sort: 'default' (pending-first) | 'asc' | 'desc' (year, then quarter). */
    #[Url(except: 'default')]
    public string $sortPeriod = 'default';

    #[Url(except: 'all')]
    public string $year = 'all';

    #[Url(except: 'all')]
    public string $status = 'all';

    /**
     * Set when the same queue is opened for one community (Item 2). The list,
     * KPIs and actions all stay inside that community.
     */
    public ?int $communityId = null;

    public ?string $communityName = null;

    public ?string $communitySubtitle = null;

    /** Admin may read the community directory, but only the Secretary validates. */
    public bool $readOnly = false;

    public ?int $drawerId = null;

    public bool $showDrawer = false;

    public bool $showValidate = false;

    public ?int $validatingId = null;

    public bool $showReturn = false;

    public ?int $returningId = null;

    public string $returnRemarks = '';

    /**
     * Dossier sections (§7 instrument) for the review drawer. Each section
     * lists kv rows (single/yesno/text fields) and chip groups (multi
     * fields). 'respondent_full_name' and 'barangay_service_ratings' are
     * resolved specially by answer().
     *
     * @var array<int, array{num: string, title: string, rows: array<int, array{string, string}>, chips: array<int, array{string, string}>}>
     */
    public const SECTIONS = [
        [
            'num' => 'I', 'title' => 'Respondent Profile',
            'rows' => [
                ['respondent_full_name', 'Name'],
                ['respondent_age', 'Age'],
                ['respondent_sex', 'Sex'],
                ['respondent_civil_status', 'Civil status'],
                ['respondent_religion', 'Religion'],
                ['respondent_educational_attainment', 'Educational attainment'],
            ],
            'chips' => [],
        ],
        [
            'num' => 'II', 'title' => 'Family Composition',
            'rows' => [
                ['family_composition', 'Household size'],
                ['household_member_currently_studying', 'Member currently studying'],
                ['household_members_in_organization', 'Members in organization'],
            ],
            'chips' => [],
        ],
        [
            'num' => 'III', 'title' => 'Economic / Livelihood',
            'rows' => [
                ['livelihood_options', 'Main livelihood source'],
                ['desired_training', 'Desired training'],
            ],
            'chips' => [],
        ],
        [
            'num' => 'IV', 'title' => 'Education',
            'rows' => [
                ['interested_in_continuing_studies', 'Continuing studies'],
                ['areas_of_educational_interest', 'Area of interest'],
                ['preferred_training_time', 'Preferred time'],
            ],
            'chips' => [
                ['barangay_educational_facilities', 'Educational facilities in the barangay'],
                ['preferred_training_days', 'Preferred training days'],
            ],
        ],
        [
            'num' => 'V', 'title' => 'Health and Sanitation',
            'rows' => [
                ['common_illnesses', 'Most common illness'],
                ['action_when_sick', 'Action when sick'],
                ['has_barangay_health_programs', 'Barangay health programs'],
                ['benefits_from_barangay_programs', 'Benefits from the programs'],
                ['water_source', 'Water source'],
                ['water_source_distance', 'Water source distance'],
                ['garbage_disposal_method', 'Garbage disposal'],
                ['has_own_toilet', 'Own toilet'],
                ['toilet_type', 'Toilet type'],
                ['keeps_animals', 'Keeps animals'],
            ],
            'chips' => [
                ['barangay_medical_supplies_available', 'Medical supplies available'],
                ['programs_benefited_from', 'Programs benefited from'],
                ['animals_kept', 'Animals kept'],
            ],
        ],
        [
            'num' => 'VI', 'title' => 'Housing and Amenities',
            'rows' => [
                ['house_type', 'House type'],
                ['tenure_status', 'Tenure status'],
                ['has_electricity', 'Electricity'],
                ['light_source_without_power', 'Light source without power'],
            ],
            'chips' => [
                ['appliances_owned', 'Appliances owned'],
            ],
        ],
        [
            'num' => 'VII', 'title' => 'Recreation and Organization',
            'rows' => [
                ['member_of_organization', 'Member of organization'],
                ['organization_types', 'Organization type'],
                ['organization_meeting_frequency', 'Meeting frequency'],
                ['organization_usual_activities', 'Usual activities'],
                ['position_in_organization', 'Position'],
            ],
            'chips' => [
                ['barangay_recreational_facilities', 'Recreational facilities'],
                ['use_of_free_time', 'Use of free time'],
            ],
        ],
        [
            'num' => 'VIII', 'title' => 'Problems and Priorities',
            'rows' => [],
            'chips' => [
                ['family_problems', 'Family'],
                ['health_problems', 'Health'],
                ['educational_problems', 'Educational'],
                ['employment_problems', 'Employment'],
                ['infrastructure_problems', 'Infrastructure'],
                ['economic_problems', 'Economic'],
                ['security_problems', 'Security'],
            ],
        ],
        [
            'num' => 'IX', 'title' => 'Service Ratings and Summary',
            'rows' => [
                ['barangay_service_ratings', 'Overall service rating'],
                ['available_for_training', 'Available for training'],
                ['reason_not_available', 'Reason not available'],
                ['general_feedback', 'General feedback'],
            ],
            'chips' => [],
        ],
    ];

    /**
     * The global queue passes no community. The Item 2 directory binds one and
     * locks the whole page to it; Admin gets a read-only view there.
     */
    public function mount(?Community $community = null): void
    {
        if ($community === null) {
            return;
        }

        $this->communityId = $community->id;
        $this->communityName = $community->name;
        $this->communitySubtitle = collect([$community->municipality, $community->province])
            ->filter()
            ->implode(', ');
        $this->readOnly = ! auth()->user()->isSecretary();
    }

    /**
     * Every query the page acts on. Scoping here — not only in render — is what
     * stops a tampered id from opening, validating or returning another
     * community's assessment from the directory.
     */
    private function assessments()
    {
        return NeedsAssessment::query()
            ->when($this->communityId !== null, fn ($query) => $query->where('community_id', $this->communityId));
    }

    /**
     * Scoped lookup. `abort(404)` rather than `findOrFail` so a tampered id is
     * a handled 404 (the Livewire test harness and the browser both see it as
     * "not found"), not an uncaught model exception.
     */
    private function findAssessment(?int $id, array $with = []): NeedsAssessment
    {
        $assessment = $id === null ? null : $this->assessments()->with($with)->find($id);

        abort_if($assessment === null, 404);

        return $assessment;
    }

    public function openDrawer(int $id): void
    {
        $this->drawerId = $this->findAssessment($id)->id;
        $this->showDrawer = true;
    }

    /** Period header sort: default (pending-first) -> asc -> desc -> default. */
    public function toggleSortPeriod(): void
    {
        $this->sortPeriod = match ($this->sortPeriod) {
            'default' => 'asc',
            'asc' => 'desc',
            default => 'default',
        };

        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedQuarter(): void
    {
        $this->resetPage();
    }

    public function updatedYear(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function paginationView(): string
    {
        return 'livewire.partials.pagination';
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
        $this->drawerId = null;
    }

    /**
     * Display value for a dossier field: handles the v4.9 scalar-or-array
     * reality (former multi fields are now single-select strings; true
     * multi fields are arrays) so the view never calls implode() on a
     * string.
     */
    public function answer(NeedsAssessment $a, string $field): ?string
    {
        if ($field === 'respondent_full_name') {
            $name = trim(sprintf(
                '%s %s %s',
                $a->respondent_first_name ?? '',
                $a->respondent_middle_name ?? '',
                $a->respondent_last_name ?? ''
            ));

            return $name !== '' ? $name : null;
        }

        $value = $field === 'barangay_service_ratings'
            ? ($a->barangay_service_ratings['overall'] ?? null)
            : $a->{$field};

        if (is_array($value)) {
            $value = implode(' · ', array_filter($value));
        }

        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return (string) $value;
    }

    /** @return array<int, string> */
    public function chipValues(NeedsAssessment $a, string $field): array
    {
        $value = $a->{$field};

        if (is_array($value)) {
            return array_values(array_filter($value));
        }

        return ($value === null || $value === '') ? [] : [(string) $value];
    }

    /** Percentage of §7 instrument fields answered (context fields excluded). */
    public function completeness(NeedsAssessment $a): int
    {
        $total = 0;
        $answered = 0;

        foreach (AssessmentTemplate::COLUMNS as $field => $col) {
            if ($col['type'] === 'context') {
                continue;
            }

            $total++;

            if ($this->answer($a, $field) !== null) {
                $answered++;
            }
        }

        return $total === 0 ? 0 : (int) round($answered / $total * 100);
    }

    public function openValidate(int $id): void
    {
        abort_unless(auth()->user()->isSecretary(), 403, 'Separation of duties — only the Secretary validates assessments.');

        $this->validatingId = $this->findAssessment($id)->id;
        $this->showValidate = true;
    }

    /** 5.6: pending -> validated (confirmed via modal). Summary recomputes via model events (6.10). */
    public function confirmValidate(): void
    {
        abort_unless(auth()->user()->isSecretary(), 403, 'Separation of duties — only the Secretary validates assessments.');

        $assessment = $this->findAssessment($this->validatingId, ['community', 'uploader']);
        abort_unless($assessment->review_status === 'pending', 422, 'Already reviewed.');

        $assessment->update([
            'review_status' => 'validated',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        activity()->performedOn($assessment)->event('validated')
            ->log("Assessment validated for {$assessment->community->name} Q{$assessment->quarter} {$assessment->year}");

        $assessment->uploader?->notify(new SmartCemesNotification(
            'Assessment validated',
            "{$assessment->community->name} Q{$assessment->quarter} {$assessment->year} passed validation.",
            'check', 'green'
        ));

        $this->reset(['showValidate', 'validatingId']);
        $this->closeDrawer();
        $this->dispatch('sc-toast', message: 'Assessment validated', type: 'success');
    }

    public function openReturn(int $id): void
    {
        abort_unless(auth()->user()->isSecretary(), 403, 'Separation of duties — only the Secretary validates assessments.');

        $this->returningId = $this->findAssessment($id)->id;
        $this->returnRemarks = '';
        $this->showReturn = true;
        $this->resetErrorBag();
    }

    /** 5.6: pending -> returned requires remarks. */
    public function confirmReturn(): void
    {
        abort_unless(auth()->user()->isSecretary(), 403, 'Separation of duties — only the Secretary validates assessments.');

        $this->validate([
            'returnRemarks' => 'required|string|min:5|max:2000',
        ]);

        $assessment = $this->findAssessment($this->returningId, ['community', 'uploader']);
        abort_unless($assessment->review_status === 'pending', 422, 'Already reviewed.');

        $assessment->update([
            'review_status' => 'returned',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_remarks' => $this->returnRemarks,
        ]);

        activity()->performedOn($assessment)->event('returned')
            ->log("Assessment returned: {$this->returnRemarks}");

        $assessment->uploader?->notify(new SmartCemesNotification(
            'Assessment returned',
            "{$assessment->community->name} Q{$assessment->quarter} {$assessment->year} — see remarks.",
            'doc', 'yellow'
        ));

        $this->reset(['showReturn', 'returnRemarks', 'returningId']);
        $this->closeDrawer();
        $this->dispatch('sc-toast', message: 'Assessment returned with remarks', type: 'warn');
    }

    public function render()
    {
        $stats = $this->assessments()
            ->selectRaw('count(*) as total')
            ->selectRaw("sum(case when review_status = 'pending' then 1 else 0 end) as pending")
            ->selectRaw("sum(case when review_status = 'validated' then 1 else 0 end) as validated")
            ->selectRaw("sum(case when review_status = 'returned' then 1 else 0 end) as returned")
            ->first();

        $quarter = in_array($this->quarter, ['1', '2', '3', '4'], true) ? (int) $this->quarter : null;
        $year = preg_match('/^\d{4}$/', $this->year) ? (int) $this->year : null;
        $status = in_array($this->status, ['pending', 'validated', 'returned'], true) ? $this->status : null;

        $periodDir = match ($this->sortPeriod) {
            'asc' => 'asc',
            'desc' => 'desc',
            default => null,
        };

        $queue = $this->assessments()
            ->with(['community', 'uploader', 'reviewer'])
            ->when($this->search !== '', function ($query) {
                $term = '%'.str_replace(['%', '_'], ['\%', '\_'], trim($this->search)).'%';
                $query->where(function ($query) use ($term) {
                    $query->whereHas('community', fn ($c) => $c->where('name', 'like', $term)->orWhere('municipality', 'like', $term))
                        ->orWhereHas('uploader', fn ($u) => $u->where('name', 'like', $term))
                        ->orWhere('respondent_first_name', 'like', $term)
                        ->orWhere('respondent_middle_name', 'like', $term)
                        ->orWhere('respondent_last_name', 'like', $term);
                });
            })
            ->when($quarter !== null, fn ($query) => $query->where('quarter', $quarter))
            ->when($year !== null, fn ($query) => $query->where('year', $year))
            ->when($status !== null, fn ($query) => $query->where('review_status', $status))
            ->when(
                in_array($this->source, ['staff', 'public', 'import'], true),
                fn ($query) => $query->where('source', $this->source)
            )
            ->when(
                $periodDir !== null,
                fn ($query) => $query->orderBy('year', $periodDir)->orderBy('quarter', $periodDir)->orderByDesc('created_at'),
                fn ($query) => $query->orderByRaw("CASE review_status WHEN 'pending' THEN 0 WHEN 'returned' THEN 1 ELSE 2 END")->orderByDesc('created_at'),
            )
            ->paginate(10);

        return view('livewire.assessments.review', [
            'queue' => $queue,
            'stats' => $stats,
            'years' => $this->assessments()->select('year')->distinct()->orderByDesc('year')->pluck('year'),
            'selected' => $this->drawerId
                ? $this->assessments()->with(['community', 'uploader', 'reviewer'])->find($this->drawerId)
                : null,
            'validating' => $this->validatingId
                ? $this->assessments()->with('community')->find($this->validatingId)
                : null,
            'returning' => $this->returningId
                ? $this->assessments()->with('community')->find($this->returningId)
                : null,
            'sections' => self::SECTIONS,
        ]);
    }
}
