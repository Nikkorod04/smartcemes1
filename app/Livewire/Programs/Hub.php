<?php

namespace App\Livewire\Programs;

use App\Models\Activity;
use App\Models\ActivityImport;
use App\Models\Beneficiary;
use App\Models\BudgetUtilization;
use App\Models\Community;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\ProgramNarrative;
use App\Models\ProgramObjective;
use App\Models\RenderedHours;
use App\Notifications\SmartCemesNotification;
use App\Services\ActivityAttendanceImport;
use App\Services\ActivityEvaluationImport;
use App\Services\BeneficiaryTemplate;
use App\Services\KpiService;
use App\Services\ProgramNarrativeService;
use App\Services\TrainingHoursService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use PhpOffice\PhpSpreadsheet\IOFactory;

#[Layout('layouts.app')]
class Hub extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ExtensionProject $program;

    #[Url]
    public string $tab = 'overview';

    public bool $canManage = false;

    /** v4.12: beneficiary + attendance actions (Admin or Secretary). */
    public bool $canManageBeneficiaries = false;

    /* ---------------- executive narrative modal (5.15) ---------------- */
    public bool $showNarrativeModal = false;

    /* ---------------- objectives ---------------- */
    public bool $showObjList = false;

    public bool $showObjForm = false;

    public ?int $editingObjId = null;

    public array $objForm = [
        'objective' => '', 'kpi_metric' => '', 'unit' => '',
        'baseline_value' => '', 'target_value' => '', 'target_date' => '',
        'actual_value' => '', 'evidence_notes' => '',
    ];

    /* ---------------- activities ---------------- */
    public bool $showActivityForm = false;

    public ?int $editingActivityId = null;

    public array $activityForm = [
        'title' => '', 'description' => '', 'venue' => '',
        'planned_start_date' => '', 'planned_end_date' => '',
        'start_time' => '08:00', 'end_time' => '12:00',
        // R4 (§4.4): days carries the duration (0.5 = half day), participants is
        // the R-Q1 manual trainee fallback, trainors_snapshot overrides the
        // assigned-faculty count.
        'no_of_days' => '1', 'participants' => '', 'trainors_snapshot' => '',
        'allocated_budget' => '', 'status' => 'draft', 'faculty_ids' => [],
    ];

    public string $facultyConflict = '';

    /* ---------------- activity records: attendance + evaluation XLSX (v4.13) ---------------- */
    public ?int $recordsActivityId = null;

    public string $recordsTab = 'attendance';

    public $attendanceImportFile = null;

    public string $attendanceFileName = '';

    /** Upload → preview → confirm (D10); never bare Alpine state (§14). */
    public string $attendanceStep = 'upload';

    /** Parsed preview rows (screen-only, never persisted). */
    public array $attendanceRows = [];

    public array $attendanceSummary = [];

    public $evaluationImportFile = null;

    public string $evaluationFileName = '';

    public string $evaluationStep = 'upload';

    public array $evaluationRows = [];

    public array $evaluationSummary = [];

    /** Current aggregate columns (evaluation preview shows before → after). */
    public array $evaluationCurrent = [];

    /* ---------------- beneficiaries ---------------- */
    public bool $showEnroll = false;

    public string $enrollSearch = '';

    public bool $showRegister = false;

    public bool $registerConfirmed = false;

    public string $dedupWarning = '';

    /** Lowercased first|last|barangay the warning was raised for; editing the form invalidates the confirmation. */
    public string $registerCheckedKey = '';

    public array $registerForm = [
        'first_name' => '', 'middle_name' => '', 'last_name' => '',
        'age' => '', 'gender' => 'Female', 'phone' => BeneficiaryTemplate::DEFAULT_CONTACT_NUMBER,
        'beneficiary_category' => '', 'barangay' => '', 'municipality' => '',
    ];

    /* ---------------- beneficiary XLSX import ---------------- */
    public bool $showImport = false;

    public $importFile = null;

    public bool $importPreview = false;

    /** Parsed rows: data + errors + duplicate flag (screen-only preview). */
    public array $importRows = [];

    /* ---------------- budget ---------------- */
    public bool $showBudgetForm = false;

    /* ---------------- program edit ---------------- */
    public bool $showProgramEdit = false;

    public array $editForm = [
        'title' => '',
        'description' => '',
        'planned_start_date' => '',
        'planned_end_date' => '',
        'target_beneficiaries' => '',
        'allocated_budget' => '',
        'annual_target_hours' => '',
        'program_lead_id' => '',
        'community_ids' => [],
        'beneficiary_categories' => [],
        'status' => 'draft',
    ];

    public array $budgetForm = [
        'item_name' => '', 'description' => '', 'amount' => '',
        'date_used' => '', 'activity_id' => '', 'receipt_reference' => '',
    ];

    /**
     * R2: the route parameter is `{project}` (it binds an ExtensionProject) and
     * Livewire's implicit route-model binding matches the mount parameter BY
     * NAME, so this must be `$project` — verified by probe: with `$program` here
     * the HTTP request 500s while `Livewire::test(Hub::class, ['program' => …])`
     * still worked, which is exactly the kind of split that hides a bug.
     *
     * The public property stays `$program` because it is read ~40 times across
     * this class and the hub view; assigning across the name boundary once,
     * here, is the whole adaptation.
     */
    public function mount(ExtensionProject $project): void
    {
        // 5.2: Admin manages; faculty view read-only for projects they lead
        // or are assigned to; others are refused.
        abort_unless(auth()->user()->can('view', $project), 403, 'This project is not available for your account.');

        $this->program = $project;
        $this->canManage = auth()->user()->can('manage', $project);
        $this->canManageBeneficiaries = auth()->user()->can('manageBeneficiaries', $project);
    }

    public function render()
    {
        $hours = app(TrainingHoursService::class);

        $this->program->load(['activities', 'budgetUtilizations', 'communities']);

        // R4 / R-Q2: the results framework is SOFT-DEPRECATED and no longer has
        // a surface on this page (D-R7 removed the 8.6 KPI tiles; the Overview
        // card now shows the target model instead). `programObjectives` is
        // therefore not eager-loaded and no objective meta is computed — but the
        // relation, the table, `ProgramObjective`, `KpiService` and the
        // `smartcemes.kpi_metrics` config all remain in place (retained unread),
        // so historical rows stay inspectable and the migration stays
        // reversible. The objective CRUD methods below are likewise retained
        // but unreachable: no view calls them any more.

        $activities = $this->program->activities()->orderBy('planned_start_date')->get();

        // The enrolled roster is PAGINATED (owner request 2026-10-05): a mature
        // program can carry hundreds of beneficiaries and the table had no
        // bound. Its own page name keeps it independent of any other paginator
        // that may later appear on this component.
        //
        // The order is shared with `BeneficiaryExportController`, so the
        // exported sheet and this table agree row for row.
        $enrolledPaginator = $this->program->beneficiaries()
            ->whereNull('beneficiaries.deleted_at')
            ->orderBy('beneficiaries.last_name')
            ->orderBy('beneficiaries.first_name')
            ->paginate(10, ['*'], 'beneficiariesPage');

        $enrolledCount = $enrolledPaginator->total();

        $entries = $this->program->budgetUtilizations()->with('activity')->orderByDesc('date_used')->get();
        $utilized = (float) $entries->sum('amount');

        // Attendance counts: total rows + attendees (present/late) per activity.
        $attendanceCounts = Activity::query()
            ->where('extension_project_id', $this->program->id)
            ->withCount(['attendances', 'attendances as attendees_count' => fn ($q) => $q->whereIn('status', ['present', 'late'])])
            ->get();
        $attendancesByActivity = $attendanceCounts->pluck('attendances_count', 'id');
        $attendeesByActivity = $attendanceCounts->pluck('attendees_count', 'id');

        /* ------------------------------------------------------------------ */
        /* R4 PERFORMANCE SECTION (D-R7: the 8.6 KPI tiles are GONE) */
        /* */
        /* The Director tracks four things per project — trainors, trainees, */
        /* training hours rendered and budget — each against its annual target. */
        /* The 8.6 dictionary (community reach, participation rate, attendance */
        /* consistency, budget utilization %, completion rate, knowledge gain, */
        /* cost per beneficiary) is no longer surfaced at project level. */
        /* ------------------------------------------------------------------ */
        $performance = $hours->forProject($this->program);
        $trainingRows = $performance['rows'];
        $trainorsByActivity = $trainingRows->pluck('trainors', 'activity_id');
        $traineesByActivity = $trainingRows->pluck('trainees', 'activity_id');
        $traineeSourceByActivity = $trainingRows->pluck('trainees_source', 'activity_id');
        $trainingHoursByActivity = $trainingRows->pluck('hours', 'activity_id');

        // Budget has NO annual target (owner decision 2026-09-26): the project's
        // ALLOCATION is its only budget figure, so it is the denominator. Every
        // budget surface reads this same service value, so none can disagree.
        $budgetAllocation = $performance['allocated_budget'];
        $over = $budgetAllocation > 0 && $utilized > $budgetAllocation;

        $hoursTarget = $performance['target_hours'];
        $hoursActual = $performance['actual_hours'];

        // The four bars of the "Training Hours vs Annual Target" panel, in the
        // prototype's order. `bar` is a percentage for the progress element and
        // `tone` the colour class, so the view holds no arithmetic.
        $attainTone = function (?float $pct, bool $over = false): string {
            if ($over) {
                return 'bg-red-500';
            }
            if ($pct === null) {
                return 'bg-gray-300';
            }

            return $pct >= 100 ? 'bg-emerald-500' : ($pct >= 60 ? 'bg-lnu-600' : 'bg-gold-500');
        };

        // The overview card needs the same tone decision without the "over"
        // argument, so it is exposed as its own closure rather than duplicating
        // the thresholds in Blade (one place decides what "on track" means).
        $attainBg = fn (?float $pct): string => $pct === null
            ? 'bg-gray-300'
            : ($pct >= 100 ? 'bg-emerald-500' : ($pct >= 60 ? 'bg-lnu-600' : 'bg-gold-500'));

        $hoursPct = $performance['hours_pct'];
        $reachPct = $this->program->target_beneficiaries
            ? round($performance['trainees'] / (int) $this->program->target_beneficiaries * 100, 1)
            : null;
        $completionPct = $performance['activity_count']
            ? round($performance['completed_count'] / $performance['activity_count'] * 100, 1)
            : null;

        $hoursRows = [
            [
                'label' => 'Training hours rendered',
                'display' => number_format($hoursActual).' / '.($hoursTarget === null ? '—' : number_format($hoursTarget)).' hrs',
                'sub' => $hoursPct === null ? null : $hoursPct.'%',
                'bar' => (int) min($hoursPct ?? 0, 100),
                'tone' => $attainTone($hoursPct),
                'caption' => $performance['trainors'].' trainors × '.$performance['trainees'].' trainees × '
                    .$hours->formatDays($performance['training_days']).' days across '
                    .$performance['completed_count'].' completed activit'.($performance['completed_count'] === 1 ? 'y' : 'ies')
                    .' · formula trainors × trainees × days, no × 8',
            ],
            [
                'label' => 'Trainors assigned',
                'display' => (string) $performance['trainors'],
                'sub' => 'faculty',
                'bar' => (int) min($performance['trainors'] / 4 * 100, 100),
                'tone' => 'bg-lnu-600',
                'caption' => 'Lead plus co-lead faculty counted once per project',
            ],
            [
                'label' => 'Trainees / beneficiaries reached',
                'display' => number_format($performance['trainees']).' / '.($this->program->target_beneficiaries ? number_format((int) $this->program->target_beneficiaries) : '—'),
                'sub' => $reachPct === null ? null : $reachPct.'%',
                'bar' => (int) min($reachPct ?? 0, 100),
                'tone' => $attainTone($reachPct),
                'caption' => 'Distinct beneficiaries with present or late attendance',
            ],
            [
                'label' => 'Activities completed',
                'display' => $performance['completed_count'].' / '.$performance['activity_count'],
                'sub' => $completionPct === null ? null : $completionPct.'%',
                'bar' => (int) min($completionPct ?? 0, 100),
                'tone' => 'bg-gold-500',
                'caption' => 'Completed activities out of all activities in this project',
            ],
        ];

        $budgetVsAllocation = [
            'allocated' => (float) $budgetAllocation,
            'utilized' => $utilized,
            'remaining' => max((float) $budgetAllocation - $utilized, 0),
            'pct' => $budgetAllocation > 0 ? round($utilized / (float) $budgetAllocation * 100, 1) : null,
            'over' => $over,
        ];

        $attendanceChartRows = $activities
            ->where('status', 'completed')
            ->map(fn ($a) => [
                'label' => $a->title.' · '.$a->planned_start_date->format('M j'),
                'attendees' => (int) ($attendeesByActivity[$a->id] ?? 0),
            ])->values();

        $satisfactionRows = $activities
            ->where('satisfaction_rating', '!=', null)
            ->map(fn ($a) => [
                'label' => $a->title.' · '.$a->planned_start_date->format('M j'),
                'rating' => (float) $a->satisfaction_rating,
            ])->values();

        $facultyOptions = Faculty::with('user')->orderBy('id')->get();
        $assignedFaculty = Activity::query()
            ->where('extension_project_id', $this->program->id)
            ->whereHas('faculty')
            ->with('faculty.user')
            ->get()
            ->flatMap(fn ($a) => $a->faculty->map(fn ($f) => ['activity' => $a, 'faculty' => $f]));

        // The trainor roster tiles read the SAME service numbers as the bars, so
        // the panel and the roster can never disagree.
        $trainorRoster = $assignedFaculty
            ->unique(fn ($x) => $x['faculty']->id)
            ->map(fn ($x) => [
                'faculty' => $x['faculty'],
                'is_lead' => (int) $this->program->program_lead_id === (int) $x['faculty']->id,
                'hours' => $assignedFaculty
                    ->where('faculty.id', $x['faculty']->id)
                    ->sum(fn ($row) => (float) ($trainingHoursByActivity[$row['activity']->id] ?? 0)),
            ])
            ->sortByDesc('is_lead')
            ->values();

        // 5.15: latest narrative attempt + latest COMPLETED version (full-narrative modal).
        $latestNarrative = $this->program->programNarratives()->latest('id')->first();
        $fullNarrative = $latestNarrative?->status === ProgramNarrative::STATUS_COMPLETED
            ? $latestNarrative
            : $this->program->programNarratives()
                ->where('status', ProgramNarrative::STATUS_COMPLETED)
                ->latest('id')
                ->first();

        return view('livewire.programs.hub', [
            'latestNarrative' => $latestNarrative,
            'fullNarrative' => $fullNarrative,
            'activities' => $activities,
            'attendancesByActivity' => $attendancesByActivity,
            'attendeesByActivity' => $attendeesByActivity,
            'assignedFacultyByActivity' => $assignedFaculty
                ->groupBy(fn ($x) => $x['activity']->id)
                ->map(fn ($g) => $g->pluck('faculty.user.name')->all()),
            'enrolledPaginator' => $enrolledPaginator,
            'enrolledCount' => $enrolledCount,
            'entries' => $entries,
            'utilized' => $utilized,
            'remaining' => (float) $budgetAllocation - $utilized,
            'over' => $over,
            'facultyOptions' => $facultyOptions,
            'allCommunities' => Community::orderBy('name')->get(),
            'beneficiaryCategories' => config('smartcemes.beneficiary_categories'),
            'registry' => $this->showEnroll ? $this->registryResults() : collect(),
            'served' => $performance['trainees'],
            // R4 performance payload
            'performance' => $performance,
            'hoursRows' => $hoursRows,
            'attainBg' => $attainBg,
            'budgetVsAllocation' => $budgetVsAllocation,
            'trainorRoster' => $trainorRoster,
            'trainorsByActivity' => $trainorsByActivity,
            'traineesByActivity' => $traineesByActivity,
            'traineeSourceByActivity' => $traineeSourceByActivity,
            'trainingHoursByActivity' => $trainingHoursByActivity,
            'traineeSourceLabels' => TrainingHoursService::SOURCE_LABELS,
            'avgSatisfaction' => $satisfactionRows->isNotEmpty() ? (float) $satisfactionRows->avg('rating') : null,
            'satisfactionRated' => $satisfactionRows->count(),
            'attendanceChartRows' => $attendanceChartRows,
            'satisfactionRows' => $satisfactionRows,
            'conflictingFaculty' => $this->showActivityForm ? $this->conflictingFacultyIds() : [],
            // 6.19: import provenance for the activity whose Records modal is open.
            'recordsImports' => $this->recordsActivityId
                ? ActivityImport::with('importer')
                    ->where('activity_id', $this->recordsActivityId)
                    ->orderByDesc('imported_at')
                    ->get()
                : collect(),
        ]);
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    /**
     * The design-system paginator.
     *
     * Livewire's shipped pagination views use Tailwind classes that sit OUTSIDE
     * the Vite content globs, so the default theme renders unstyled — hence the
     * shared partial (§14).
     */
    public function paginationView(): string
    {
        return 'livewire.partials.pagination';
    }

    /* ==================== OBJECTIVES ==================== */

    /* ------------------------------------------------------------------ */
    /* R4 / R-Q2 — SOFT-DEPRECATED, RETAINED UNREAD */
    /* */
    /* The 8.6 objective surface was removed in Phase R4 (D-R7): the */
    /* Overview card, the Objective Manager modal and the objective form */
    /* are gone from `hub-overview` / `hub-modals`, so nothing in any view */
    /* calls the methods below any more. They are deliberately NOT deleted: */
    /* the route stays reversible, historical `program_objectives` rows */
    /* stay editable through the model layer, and R5/R6 can revive the */
    /* surface without re-writing this logic. Do not wire new UI to these */
    /* without re-reading §4.7 — the target model supersedes them. */
    /* ------------------------------------------------------------------ */

    public function openObjManager(): void
    {
        $this->abortUnlessManage();
        $this->showObjList = true;
    }

    public function newObjective(): void
    {
        $this->abortUnlessManage();
        $this->editingObjId = null;
        $this->objForm = [
            'objective' => '', 'kpi_metric' => '', 'unit' => '',
            'baseline_value' => '', 'target_value' => '', 'target_date' => '',
            'actual_value' => '', 'evidence_notes' => '',
        ];
        $this->showObjForm = true;
    }

    /** Switching metric mode invalidates a manual actual — clear it so it
     *  is never silently discarded on save (numeric objectives ignore it). */
    public function updatedObjFormKpiMetric(): void
    {
        $this->objForm['actual_value'] = '';
    }

    /** Live 8.6 value for the KPI metric currently selected in the form. */
    public function currentComputedKpi(): ?float
    {
        $metric = $this->objForm['kpi_metric'] ?? '';
        if ($metric === '' || ! in_array($metric, ProgramObjective::KPI_METRICS, true)) {
            return null;
        }

        $probe = new ProgramObjective(['kpi_metric' => $metric]);
        $probe->setRelation('program', $this->program);

        return app(KpiService::class)->liveKpi($probe);
    }

    public function editObjective(int $id): void
    {
        $this->abortUnlessManage();
        $o = $this->program->programObjectives()->findOrFail($id);
        $this->editingObjId = $id;
        $this->objForm = [
            'objective' => $o->objective,
            'kpi_metric' => (string) $o->kpi_metric,
            'unit' => (string) $o->unit,
            'baseline_value' => (string) $o->baseline_value,
            'target_value' => (string) $o->target_value,
            'target_date' => $o->target_date?->format('Y-m-d'),
            'actual_value' => (string) $o->actual_value,
            'evidence_notes' => (string) $o->evidence_notes,
        ];
        $this->showObjForm = true;
    }

    public function saveObjective(): void
    {
        $this->abortUnlessManage();

        $rules = [
            'objForm.objective' => 'required|string|max:1000',
            'objForm.kpi_metric' => 'nullable|in:'.implode(',', ProgramObjective::KPI_METRICS),
            'objForm.unit' => 'nullable|string|max:50',
            'objForm.baseline_value' => 'nullable|numeric',
            'objForm.target_value' => 'required|numeric',
            'objForm.target_date' => 'nullable|date|after_or_equal:'.$this->program->planned_start_date->format('Y-m-d'),
            'objForm.evidence_notes' => 'nullable|string|max:2000',
        ];

        if ($this->objForm['kpi_metric'] === '') {
            $rules['objForm.actual_value'] = 'nullable|numeric';
        }

        $this->validate($rules);

        $data = [
            'objective' => $this->objForm['objective'],
            'kpi_metric' => $this->objForm['kpi_metric'] ?: null,
            'unit' => $this->objForm['unit'] ?: null,
            'baseline_value' => $this->objForm['baseline_value'] !== '' ? $this->objForm['baseline_value'] : null,
            'target_value' => $this->objForm['target_value'],
            'target_date' => $this->objForm['target_date'] ?: null,
            'actual_value' => $this->objForm['kpi_metric'] === '' && $this->objForm['actual_value'] !== '' ? $this->objForm['actual_value'] : null,
            'evidence_notes' => $this->objForm['evidence_notes'] ?: null,
        ];

        if ($this->editingObjId) {
            $this->program->programObjectives()->findOrFail($this->editingObjId)->update($data);
            activity()->performedOn($this->program)->event('objective_update')
                ->log("Objective updated: \"{$this->objForm['objective']}\""
                    .($data['kpi_metric'] ? " ({$data['kpi_metric']})" : ' (qualitative)'));
            $this->dispatch('sc-toast', message: 'Objective updated', type: 'success');
        } else {
            $this->program->programObjectives()->create($data);
            activity()->performedOn($this->program)->event('objective_create')
                ->log("Objective added: \"{$this->objForm['objective']}\""
                    .($data['kpi_metric'] ? " ({$data['kpi_metric']})" : ' (qualitative)'));
            $this->dispatch('sc-toast', message: 'Objective added', type: 'success');
        }

        $this->showObjForm = false;
    }

    public function deleteObjective(int $id): void
    {
        $this->abortUnlessManage();
        $objective = $this->program->programObjectives()->findOrFail($id);
        $objective->delete();
        activity()->performedOn($this->program)->event('objective_delete')
            ->log("Objective removed: \"{$objective->objective}\"");
        $this->dispatch('sc-toast', message: 'Objective removed', type: 'warn');
    }

    /** 5.15: manual narrative trigger — Director-only, queued job. */
    public function generateNarrative(): void
    {
        $this->abortUnlessManage();
        app(ProgramNarrativeService::class)->generateFor($this->program->fresh());

        $this->dispatch('sc-toast', message: 'Narrative generated — aggregates only, Director-only', type: 'success');
    }

    /** Full-narrative modal — Admin-only (D4); shows the latest completed version. */
    public function openNarrativeModal(): void
    {
        $this->abortUnlessManage();
        $this->showNarrativeModal = true;
    }

    public function closeNarrativeModal(): void
    {
        $this->showNarrativeModal = false;
    }

    /* ==================== ACTIVITIES ==================== */

    public function openActivityForm(?int $id = null): void
    {
        $this->abortUnlessManage();
        $this->facultyConflict = '';

        if ($id) {
            $activity = $this->program->activities()->findOrFail($id);
            $this->editingActivityId = $id;
            $this->activityForm = [
                'title' => $activity->title,
                'description' => (string) $activity->description,
                'venue' => (string) $activity->venue,
                'planned_start_date' => $activity->planned_start_date->format('Y-m-d'),
                'planned_end_date' => $activity->planned_end_date->format('Y-m-d'),
                'start_time' => $activity->start_time->format('H:i'),
                'end_time' => $activity->end_time->format('H:i'),
                // NULL days are shown as 1 (a full day) rather than an empty box:
                // the form must always be saveable, and the service reads NULL
                // as a full day too, so the round trip is lossless.
                'no_of_days' => (string) ($activity->no_of_days ?? '1'),
                'participants' => $activity->participants !== null ? (string) $activity->participants : '',
                'trainors_snapshot' => $activity->trainors_snapshot !== null ? (string) $activity->trainors_snapshot : '',
                'allocated_budget' => (string) $activity->allocated_budget,
                'status' => $activity->status,
                'faculty_ids' => $activity->faculty()->pluck('faculty_id')->all(),
            ];
        } else {
            $this->editingActivityId = null;
            $this->activityForm = [
                'title' => '', 'description' => '', 'venue' => '',
                'planned_start_date' => $this->program->planned_start_date->format('Y-m-d'),
                'planned_end_date' => $this->program->planned_start_date->format('Y-m-d'),
                'start_time' => '08:00', 'end_time' => '12:00',
                'no_of_days' => '1', 'participants' => '', 'trainors_snapshot' => '',
                'allocated_budget' => '', 'status' => 'draft', 'faculty_ids' => [],
            ];
        }

        $this->showActivityForm = true;
    }

    public function toggleActivityFaculty(int $facultyId): void
    {
        $this->abortUnlessManage();

        $values = is_array($this->activityForm['faculty_ids'] ?? null) ? $this->activityForm['faculty_ids'] : [];

        $this->activityForm['faculty_ids'] = in_array($facultyId, $values, true)
            ? array_values(array_diff($values, [$facultyId]))
            : [...$values, $facultyId];
    }

    public function saveActivity(): void
    {
        $this->abortUnlessManage();

        $startMin = $this->program->planned_start_date->format('Y-m-d');
        $endMax = $this->program->planned_end_date->format('Y-m-d');

        $this->validate([
            'activityForm.title' => 'required|string|max:255',
            'activityForm.venue' => 'nullable|string|max:255',
            'activityForm.planned_start_date' => "required|date|after_or_equal:$startMin|before_or_equal:$endMax",
            'activityForm.planned_end_date' => "required|date|after_or_equal:activityForm.planned_start_date|before_or_equal:$endMax",
            'activityForm.start_time' => 'required',
            'activityForm.end_time' => 'required|after:activityForm.start_time',
            // R4 (§4.4): 0.5 increments, half a day minimum. `multiple_of:0.5`
            // is the rule that makes "half-day = 0.5" enforceable rather than a
            // convention: it rejects 0.3, 0.75 and 1.2.
            'activityForm.no_of_days' => 'required|numeric|min:0.5|max:60|multiple_of:0.5',
            'activityForm.participants' => 'nullable|integer|min:0|max:10000',
            'activityForm.trainors_snapshot' => 'nullable|integer|min:0|max:200',
            'activityForm.allocated_budget' => 'nullable|numeric|min:0',
            'activityForm.status' => 'required|in:draft,ongoing,completed,cancelled',
            'activityForm.faculty_ids' => 'array',
        ], [
            'activityForm.planned_start_date.after_or_equal' => "Activity dates must fall within the program range ($startMin – $endMax).",
            'activityForm.planned_start_date.before_or_equal' => "Activity dates must fall within the program range ($startMin – $endMax).",
            'activityForm.planned_end_date.before_or_equal' => "Activity dates must fall within the program range ($startMin – $endMax).",
            'activityForm.no_of_days.multiple_of' => 'Days must be in 0.5 increments — 0.5 for a half day, 1 for a full day.',
            'activityForm.no_of_days.min' => 'Days must be at least 0.5 (a half day).',
        ]);

        // 8.8 hard constraint: faculty assignment refused on schedule overlap.
        $conflicts = [];
        foreach ($this->activityForm['faculty_ids'] as $facultyId) {
            $conflictWith = $this->findScheduleConflict($facultyId);
            if ($conflictWith) {
                $conflicts[] = $conflictWith;
            }
        }

        if ($conflicts) {
            $this->facultyConflict = 'Assignment refused: '.$conflicts[0];

            return;
        }

        $activity = Activity::updateOrCreate(
            ['id' => $this->editingActivityId],
            [
                'extension_project_id' => $this->program->id,
                'title' => $this->activityForm['title'],
                'description' => $this->activityForm['description'] ?: null,
                'venue' => $this->activityForm['venue'] ?: null,
                'planned_start_date' => $this->activityForm['planned_start_date'],
                'planned_end_date' => $this->activityForm['planned_end_date'],
                'start_time' => $this->activityForm['start_time'],
                'end_time' => $this->activityForm['end_time'],
                // R4: three nullable columns. Empty strings become NULL so a
                // cleared field means "not recorded", never 0.
                'no_of_days' => $this->activityForm['no_of_days'] !== '' ? (float) $this->activityForm['no_of_days'] : null,
                'participants' => $this->activityForm['participants'] !== '' ? (int) $this->activityForm['participants'] : null,
                'trainors_snapshot' => $this->activityForm['trainors_snapshot'] !== '' ? (int) $this->activityForm['trainors_snapshot'] : null,
                'allocated_budget' => $this->activityForm['allocated_budget'] !== '' ? $this->activityForm['allocated_budget'] : null,
                'status' => $this->activityForm['status'],
            ]
        );

        $activity->faculty()->sync($this->activityForm['faculty_ids']);

        $this->showActivityForm = false;

        // R4: surface the recomputed training hours so the effect of adding days
        // and participants is visible at the moment of saving.
        $breakdown = $activity->trainingBreakdown();
        $this->dispatch('sc-toast', message: $this->editingActivityId
            ? 'Activity updated · '.$breakdown['formula']
            : 'Activity added · '.$breakdown['formula'], type: 'success');
    }

    public function completeActivity(int $id): void
    {
        $this->abortUnlessManage();
        $activity = $this->program->activities()->with('faculty.user')->findOrFail($id);

        if (! in_array($activity->status, ['draft', 'ongoing'])) {
            return;
        }

        $activity->update(['status' => 'completed', 'actual_end_date' => now()]);
        activity()
            ->performedOn($activity)
            ->event('status_transition')
            ->log("Activity '{$activity->title}' marked completed");

        // 8.9 auto-draft: one rendered-hours draft per assigned faculty with
        // hours = activity duration; skipped when end <= start (overnight guard).
        $autoHours = RenderedHours::autoHoursFor($activity);
        foreach ($activity->faculty as $faculty) {
            if ($autoHours !== null) {
                $entry = RenderedHours::firstOrCreate(
                    [
                        'faculty_id' => $faculty->id,
                        'activity_id' => $activity->id,
                    ],
                    [
                        'date' => $activity->planned_start_date,
                        'hours' => $autoHours,
                        'source' => RenderedHours::SOURCE_AUTO,
                        'status' => RenderedHours::STATUS_PENDING,
                        'remarks' => 'Auto-drafted from the activity schedule.',
                    ]
                );

                $entry->faculty->user?->notify(new SmartCemesNotification(
                    'Rendered hours drafted',
                    "Auto-draft created for '{$activity->title}' ({$autoHours} hrs) — review and submit for approval.",
                    'clock', 'yellow'
                ));
            }
        }

        $this->dispatch('sc-toast', message: 'Activity marked completed — rendered-hours drafts created for assigned faculty', type: 'success');
    }

    /* ==================== ACTIVITY RECORDS (v4.13) ==================== */

    /**
     * Opens the per-activity Records modal (attendance + evaluation XLSX).
     * Viewing stays open to all hub viewers; the import write paths require
     * the scoped guard (5.4/5.5/5.6).
     */
    public function openRecords(int $id): void
    {
        $activity = $this->program->activities()->findOrFail($id);

        $this->recordsActivityId = $id;
        $this->recordsTab = 'attendance';
        $this->resetAttendanceImport();
        $this->resetEvaluationImport();
        $this->evaluationCurrent = [
            'pre' => $activity->pre_assessment_score !== null ? (float) $activity->pre_assessment_score : null,
            'post' => $activity->post_assessment_score !== null ? (float) $activity->post_assessment_score : null,
            'satisfaction' => $activity->satisfaction_rating !== null ? (float) $activity->satisfaction_rating : null,
        ];
        $this->resetErrorBag();
    }

    public function closeRecords(): void
    {
        $this->recordsActivityId = null;
        $this->resetAttendanceImport();
        $this->resetEvaluationImport();
    }

    public function setRecordsTab(string $tab): void
    {
        if (! in_array($tab, ['attendance', 'evaluation'], true)) {
            return;
        }

        $this->recordsTab = $tab;
        $this->resetErrorBag();
    }

    public function updatedAttendanceImportFile(): void
    {
        $this->resetErrorBag('attendanceImportFile');
    }

    public function updatedEvaluationImportFile(): void
    {
        $this->resetErrorBag('evaluationImportFile');
    }

    public function parseAttendanceImport(): void
    {
        $this->abortUnlessBeneficiaryManage();
        $this->validate([
            'attendanceImportFile' => 'required|file|mimes:xlsx|max:'.config('smartcemes.uploads.max_kb'),
        ]);

        $activity = $this->program->activities()->findOrFail($this->recordsActivityId);

        try {
            $result = $this->parseUploadedFile($this->attendanceImportFile, fn (string $path) => app(ActivityAttendanceImport::class)->parse($activity, $path));
        } catch (\RuntimeException $e) {
            $this->addError('attendanceImportFile', $e->getMessage());

            return;
        }

        if ($result['errors'] !== []) {
            $this->addError('attendanceImportFile', $result['errors'][0]);

            return;
        }

        $this->attendanceRows = $result['rows'];
        $this->attendanceSummary = $result['summary'];
        $this->attendanceFileName = $this->attendanceImportFile->getClientOriginalName();
        $this->attendanceStep = 'preview';
    }

    /** Attendance rows are only written when the uploader confirms (D10). */
    public function confirmAttendanceImport(): void
    {
        $this->abortUnlessBeneficiaryManage();

        $activity = $this->program->activities()->findOrFail($this->recordsActivityId);

        if ($this->attendanceRows === [] || $this->attendanceImportFile === null) {
            $this->addError('attendanceImportFile', 'Nothing parsed to import — upload the file again.');

            return;
        }

        // Eloquent's date cast stores Y-m-d H:i:s — match that format in the
        // upsert key so the lookup is portable across MySQL and sqlite (§14).
        $date = $activity->planned_start_date->toDateTimeString();
        $applied = 0;
        $skipped = 0;
        $invalid = 0;
        $statusCounts = [];

        DB::transaction(function () use ($activity, $date, &$applied, &$skipped, &$invalid, &$statusCounts) {
            foreach ($this->attendanceRows as $row) {
                if ($row['state'] === 'error') {
                    $invalid++;

                    continue;
                }

                if ($row['status'] === null) {
                    $skipped++;

                    continue;
                }

                $activity->attendances()->updateOrCreate(
                    ['beneficiary_id' => $row['beneficiary_id'], 'attendance_date' => $date],
                    ['status' => $row['status']]
                );

                $applied++;
                $statusCounts[$row['status']] = ($statusCounts[$row['status']] ?? 0) + 1;
            }
        });

        $summary = $this->attendanceSummary;
        $summary['invalid'] = $invalid;
        $summary['statuses'] = $statusCounts;

        $this->archiveImport($activity, ActivityImport::TYPE_ATTENDANCE, $this->attendanceImportFile, [
            'processed' => $summary['processed'] ?? 0,
            'applied' => $applied,
            'skipped' => $skipped,
        ], $summary, 'attendance');

        activity()->performedOn($activity)->event('attendance_import')
            ->log("Attendance imported from XLSX for '{$activity->title}' — {$applied} record".($applied === 1 ? '' : 's').' applied');

        $message = "Attendance imported — {$applied} applied";
        $message .= $skipped > 0 ? ", {$skipped} blank skipped" : '';
        $message .= $invalid > 0 ? ", {$invalid} row".($invalid === 1 ? '' : 's').' with errors skipped' : '';
        $this->dispatch('sc-toast', message: $message, type: $applied > 0 ? 'success' : 'warn');

        $this->closeRecords();
    }

    public function parseEvaluationImport(): void
    {
        $this->abortUnlessBeneficiaryManage();
        $this->validate([
            'evaluationImportFile' => 'required|file|mimes:xlsx|max:'.config('smartcemes.uploads.max_kb'),
        ]);

        $activity = $this->program->activities()->findOrFail($this->recordsActivityId);

        try {
            $result = $this->parseUploadedFile($this->evaluationImportFile, fn (string $path) => app(ActivityEvaluationImport::class)->parse($activity, $path));
        } catch (\RuntimeException $e) {
            $this->addError('evaluationImportFile', $e->getMessage());

            return;
        }

        if ($result['errors'] !== []) {
            $this->addError('evaluationImportFile', $result['errors'][0]);

            return;
        }

        $this->evaluationRows = $result['rows'];
        // Projected means (before → after preview); null metrics keep the
        // existing aggregate (per-metric merge).
        $this->evaluationSummary = $result['summary'];
        $this->evaluationSummary['means'] = app(ActivityEvaluationImport::class)->means($result['rows']);
        $this->evaluationFileName = $this->evaluationImportFile->getClientOriginalName();
        $this->evaluationStep = 'preview';
    }

    /**
     * Aggregates the confirmed per-row scores into the activity's aggregate
     * columns (D13) — per-metric merge: a metric with no values in the file
     * leaves the existing aggregate untouched.
     */
    public function confirmEvaluationImport(): void
    {
        $this->abortUnlessBeneficiaryManage();

        $activity = $this->program->activities()->findOrFail($this->recordsActivityId);

        if ($this->evaluationRows === [] || $this->evaluationImportFile === null) {
            $this->addError('evaluationImportFile', 'Nothing parsed to import — upload the file again.');

            return;
        }

        $means = app(ActivityEvaluationImport::class)->means($this->evaluationRows);

        $before = [
            'pre' => $activity->pre_assessment_score !== null ? (float) $activity->pre_assessment_score : null,
            'post' => $activity->post_assessment_score !== null ? (float) $activity->post_assessment_score : null,
            'satisfaction' => $activity->satisfaction_rating !== null ? (float) $activity->satisfaction_rating : null,
        ];

        $updates = [];
        if ($means['pre'] !== null) {
            $updates['pre_assessment_score'] = $means['pre'];
        }
        if ($means['post'] !== null) {
            $updates['post_assessment_score'] = $means['post'];
        }
        if ($means['satisfaction'] !== null) {
            $updates['satisfaction_rating'] = $means['satisfaction'];
        }

        if ($updates === []) {
            $this->addError('evaluationImportFile', 'No scores to apply — the file has no numeric Pre-Test, Post-Test or Satisfaction values.');

            return;
        }

        DB::transaction(function () use ($activity, $updates) {
            $activity->update($updates);
        });

        $summary = $this->evaluationSummary;
        $summary['means'] = $means;
        $summary['before'] = $before;
        $summary['after'] = [
            'pre' => $updates['pre_assessment_score'] ?? $before['pre'],
            'post' => $updates['post_assessment_score'] ?? $before['post'],
            'satisfaction' => $updates['satisfaction_rating'] ?? $before['satisfaction'],
        ];

        $this->archiveImport($activity, ActivityImport::TYPE_EVALUATION, $this->evaluationImportFile, [
            'processed' => $summary['processed'] ?? 0,
            'applied' => $summary['applied'] ?? 0,
            'skipped' => $summary['skipped'] ?? 0,
        ], $summary, 'evaluation');

        activity()->performedOn($activity)->event('evaluation_import')
            ->log("Evaluation scores imported from XLSX for '{$activity->title}' — aggregates updated");

        $this->dispatch('sc-toast', message: 'Evaluation scores applied — the activity aggregates now feed knowledge gain, objectives and reports', type: 'success');

        $this->closeRecords();
    }

    /**
     * Copy a Livewire upload to a short temp path before PhpSpreadsheet
     * reads it (§14 Windows MAX_PATH gotcha).
     */
    protected function parseUploadedFile($file, callable $parse): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'sc_activity_').'.xlsx';

        try {
            copy($file->getRealPath(), $tmp);

            return $parse($tmp);
        } finally {
            @unlink($tmp);
        }
    }

    /**
     * Archive the confirmed source file and record its provenance (6.19).
     */
    protected function archiveImport(Activity $activity, string $type, $file, array $counts, array $summary, string $folder): void
    {
        $path = $file->store('activity-imports/'.$folder.'/'.now()->format('Y/m'), 'public');

        ActivityImport::create([
            'activity_id' => $activity->id,
            'type' => $type,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'imported_by' => auth()->id(),
            'imported_at' => now(),
            'rows_processed' => $counts['processed'] ?? 0,
            'rows_applied' => $counts['applied'] ?? 0,
            'rows_skipped' => $counts['skipped'] ?? 0,
            'summary' => $summary,
        ]);
    }

    protected function resetAttendanceImport(): void
    {
        $this->attendanceImportFile = null;
        $this->attendanceFileName = '';
        $this->attendanceStep = 'upload';
        $this->attendanceRows = [];
        $this->attendanceSummary = [];
    }

    protected function resetEvaluationImport(): void
    {
        $this->evaluationImportFile = null;
        $this->evaluationFileName = '';
        $this->evaluationStep = 'upload';
        $this->evaluationRows = [];
        $this->evaluationSummary = [];
        $this->evaluationCurrent = [];
    }

    /* ==================== BENEFICIARIES ==================== */

    public function openEnroll(): void
    {
        $this->abortUnlessBeneficiaryManage();
        $this->enrollSearch = '';
        $this->showEnroll = true;
    }

    public function enrollExisting(int $beneficiaryId): void
    {
        $this->abortUnlessBeneficiaryManage();
        $beneficiary = Beneficiary::findOrFail($beneficiaryId);

        if ($this->program->beneficiaries()->whereKey($beneficiaryId)->exists()) {
            return;
        }

        $this->program->beneficiaries()->syncWithoutDetaching([$beneficiaryId]);
        activity()->performedOn($this->program)->event('enroll')
            ->log("Beneficiary '{$beneficiary->fullName()}' enrolled into program");
        $this->dispatch('sc-toast', message: 'Beneficiary enrolled', type: 'success');
    }

    public function unenroll(int $beneficiaryId): void
    {
        $this->abortUnlessBeneficiaryManage();
        $beneficiary = Beneficiary::findOrFail($beneficiaryId);
        $this->program->beneficiaries()->detach([$beneficiaryId]);
        activity()->performedOn($this->program)->event('unenroll')
            ->log("Beneficiary '{$beneficiary->fullName()}' unenrolled from program");
        $this->dispatch('sc-toast', message: 'Beneficiary unenrolled', type: 'warn');
    }

    public function openRegister(): void
    {
        $this->abortUnlessBeneficiaryManage();
        $this->registerForm = [
            'first_name' => '', 'middle_name' => '', 'last_name' => '',
            'age' => '', 'gender' => 'Female', 'phone' => BeneficiaryTemplate::DEFAULT_CONTACT_NUMBER,
            'beneficiary_category' => '', 'barangay' => '', 'municipality' => $this->program->communities->first()?->municipality ?? '',
        ];
        $this->dedupWarning = '';
        $this->registerCheckedKey = '';
        $this->registerConfirmed = false;
        $this->showRegister = true;
    }

    public function checkDedup(): void
    {
        $this->abortUnlessBeneficiaryManage();

        $this->registerConfirmed = false;
        $this->dedupWarning = '';

        $this->trimRegisterForm();

        $this->validate([
            'registerForm.first_name' => 'required|string|max:255',
            'registerForm.last_name' => 'required|string|max:255',
            'registerForm.barangay' => 'required|string|max:255',
            'registerForm.beneficiary_category' => 'required|in:'.implode(',', config('smartcemes.beneficiary_categories')),
        ]);

        $dupes = Beneficiary::duplicatesFor(
            $this->registerForm['first_name'],
            $this->registerForm['last_name'],
            $this->registerForm['barangay']
        );

        $this->registerCheckedKey = $this->registerKey();

        if ($dupes->isNotEmpty()) {
            $names = $dupes->map(fn ($d) => $d->fullName())->implode(', ');
            $this->dedupWarning = 'A beneficiary with the same first name, last name, and barangay already exists: '.$names.'.';
        }
    }

    /** 5.4: dedup warns and requires explicit confirmation; never silently merges. */
    public function registerConfirmedSave(): void
    {
        $this->abortUnlessBeneficiaryManage();
        $this->registerConfirmed = true;
        $this->saveRegister();
    }

    public function saveRegister(): void
    {
        $this->abortUnlessBeneficiaryManage();

        $this->trimRegisterForm();

        $this->validate([
            'registerForm.first_name' => 'required|string|max:255',
            'registerForm.last_name' => 'required|string|max:255',
            'registerForm.barangay' => 'required|string|max:255',
            'registerForm.phone' => 'nullable|string|max:20',
            'registerForm.beneficiary_category' => 'required|in:'.implode(',', config('smartcemes.beneficiary_categories')),
        ]);

        // Confirmation only covers the exact values that were checked;
        // editing the form after the warning requires a fresh dedup pass.
        if ($this->registerCheckedKey !== $this->registerKey()) {
            $this->registerConfirmed = false;
        }

        if (! $this->registerConfirmed) {
            $this->checkDedup();

            if ($this->dedupWarning !== '') {
                return;
            }
        }

        $beneficiary = Beneficiary::create([
            'first_name' => $this->registerForm['first_name'],
            'middle_name' => $this->registerForm['middle_name'] ?: null,
            'last_name' => $this->registerForm['last_name'],
            'age' => $this->registerForm['age'] ?: null,
            'gender' => $this->registerForm['gender'],
            'phone' => $this->registerForm['phone'] ?: BeneficiaryTemplate::DEFAULT_CONTACT_NUMBER,
            'barangay' => $this->registerForm['barangay'],
            'municipality' => $this->registerForm['municipality'] ?: null,
            'community_id' => $this->program->communities->first()?->id,
            'beneficiary_category' => $this->registerForm['beneficiary_category'],
        ]);

        $this->program->beneficiaries()->syncWithoutDetaching([$beneficiary->id]);

        activity()->performedOn($this->program)->event('register')
            ->log("New beneficiary '{$beneficiary->fullName()}' registered and enrolled");

        $this->showRegister = false;
        $this->registerConfirmed = false;
        $this->dedupWarning = '';
        $this->registerCheckedKey = '';
        $this->dispatch('sc-toast', message: 'Beneficiary registered & enrolled', type: 'success');
    }

    /** Stored values and dedup keys must be whitespace-free (import path parity). */
    protected function trimRegisterForm(): void
    {
        foreach (['first_name', 'middle_name', 'last_name', 'phone', 'barangay', 'municipality'] as $field) {
            $this->registerForm[$field] = trim((string) ($this->registerForm[$field] ?? ''));
        }
    }

    /** Same key shape as the XLSX import registry check (first|last|barangay, lowercased). */
    protected function registerKey(): string
    {
        return mb_strtolower(
            $this->registerForm['first_name'].'|'.$this->registerForm['last_name'].'|'.$this->registerForm['barangay']
        );
    }

    /** 5.4 (blueprint v4.7): bulk XLSX import — upload → preview → confirm. */
    public function openImport(): void
    {
        $this->abortUnlessBeneficiaryManage();
        $this->resetErrorBag();
        $this->importFile = null;
        $this->importRows = [];
        $this->importPreview = false;
        $this->showImport = true;
    }

    public function updatedImportFile(): void
    {
        $this->resetErrorBag('importFile');
    }

    public function parseImport(): void
    {
        $this->abortUnlessBeneficiaryManage();
        $this->validate([
            'importFile' => 'required|file|mimes:xlsx|max:'.config('smartcemes.uploads.max_kb'),
        ]);

        // Livewire tmp paths can exceed Windows MAX_PATH (uploads encode
        // metadata in the filename) and PhpSpreadsheet's ZipArchive cannot
        // read those — copy to a short temp path first (§14 gotcha).
        $tmp = tempnam(sys_get_temp_dir(), 'sc_ben_').'.xlsx';
        $spreadsheet = null;
        try {
            copy($this->importFile->getRealPath(), $tmp);
            $spreadsheet = IOFactory::load($tmp);
        } catch (\Throwable) {
            $spreadsheet = null;
        } finally {
            @unlink($tmp);
        }

        if ($spreadsheet === null) {
            $this->addError('importFile', 'Could not read the file as an XLSX workbook.');

            return;
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $fieldByHeader = [];
        foreach (BeneficiaryTemplate::COLUMNS as $field => $col) {
            $fieldByHeader[$col['header']] = $field;
        }

        // Locate the fixed header row and map column letter -> field.
        $columnField = null;
        $dataRows = [];
        foreach ($rows as $row) {
            if ($columnField === null) {
                $mapped = [];
                foreach ($row as $colLetter => $header) {
                    $header = trim((string) $header);
                    if (isset($fieldByHeader[$header])) {
                        $mapped[$colLetter] = $fieldByHeader[$header];
                    }
                }
                if (count($mapped) >= 6) {
                    $columnField = $mapped;
                }

                continue;
            }

            $isEmpty = true;
            foreach ($row as $value) {
                if (trim((string) $value) !== '') {
                    $isEmpty = false;
                    break;
                }
            }
            if (! $isEmpty) {
                $dataRows[] = $row;
            }
        }

        if ($columnField === null) {
            $this->addError('importFile', 'This file does not use the official beneficiary template headers. Download the template and do not rename columns.');

            return;
        }

        if ($dataRows === []) {
            $this->addError('importFile', 'No data rows found under the template headers.');

            return;
        }

        if (count($dataRows) > BeneficiaryTemplate::MAX_ROWS) {
            $this->addError('importFile', 'Too many rows — the import accepts at most '.BeneficiaryTemplate::MAX_ROWS.' beneficiaries per file.');

            return;
        }

        // Dedup keys already in the global registry (5.4: first + last + barangay).
        $registryKeys = Beneficiary::query()
            ->get(['first_name', 'last_name', 'barangay'])
            ->map(fn ($b) => mb_strtolower($b->first_name.'|'.$b->last_name.'|'.$b->barangay))
            ->flip()->all();

        $this->importRows = [];
        foreach ($dataRows as $row) {
            $raw = [];
            foreach ($columnField as $colLetter => $field) {
                $raw[$field] = trim((string) ($row[$colLetter] ?? ''));
            }

            $result = BeneficiaryTemplate::normalizeRow($raw);

            $duplicate = false;
            if ($result['errors'] === []) {
                $key = mb_strtolower($result['data']['first_name'].'|'.$result['data']['last_name'].'|'.$result['data']['barangay']);
                $duplicate = isset($registryKeys[$key]);
                $registryKeys[$key] = true;
            }

            $this->importRows[] = [
                'data' => $result['data'],
                'errors' => $result['errors'],
                'category_other' => $result['category_other'],
                'duplicate' => $duplicate,
            ];
        }

        $this->importPreview = true;
    }

    /** Records are only created when the uploader confirms the preview (D10). */
    public function confirmImport(): void
    {
        $this->abortUnlessBeneficiaryManage();

        if ($this->importRows === []) {
            $this->addError('importFile', 'Nothing parsed to import.');

            return;
        }

        $communityByBarangay = [];
        $defaultCommunity = $this->program->communities->first();
        $defaultMunicipality = $defaultCommunity?->municipality;

        $created = 0;
        $duplicates = 0;
        $failed = 0;

        DB::transaction(function () use (&$created, &$duplicates, &$failed, $communityByBarangay, $defaultCommunity, $defaultMunicipality) {
            foreach ($this->importRows as $row) {
                if ($row['errors'] !== []) {
                    $failed++;

                    continue;
                }

                if ($row['duplicate']) {
                    $duplicates++;

                    continue;
                }

                $data = $row['data'];
                $barangay = $data['barangay'];

                if (! isset($communityByBarangay[$barangay])) {
                    $communityByBarangay[$barangay] = Community::where('name', 'like', "%{$barangay}%")->first() ?? $defaultCommunity;
                }

                $beneficiary = Beneficiary::create([
                    'first_name' => $data['first_name'],
                    'middle_name' => $data['middle_name'],
                    'last_name' => $data['last_name'],
                    'age' => $data['age'] !== null ? (int) $data['age'] : null,
                    'gender' => $data['gender'],
                    'phone' => $data['phone'] ?? BeneficiaryTemplate::DEFAULT_CONTACT_NUMBER,
                    'barangay' => $barangay,
                    'municipality' => $data['municipality'] ?? $defaultMunicipality,
                    'province' => 'Leyte',
                    'community_id' => $communityByBarangay[$barangay]?->id,
                    'beneficiary_category' => $data['beneficiary_category'],
                ]);

                $this->program->beneficiaries()->syncWithoutDetaching([$beneficiary->id]);
                $created++;
            }
        });

        if ($created > 0) {
            activity()->performedOn($this->program)->event('beneficiary_import')
                ->log("{$created} beneficiaries imported from the XLSX template and enrolled");
        }

        $summary = "{$created} imported & enrolled";
        $summary .= $duplicates > 0 ? " · {$duplicates} duplicate".($duplicates === 1 ? '' : 's').' skipped' : '';
        $summary .= $failed > 0 ? " · {$failed} row".($failed === 1 ? '' : 's').' with errors skipped' : '';
        $this->dispatch('sc-toast', message: $summary, type: $created > 0 ? 'success' : 'warn');

        $this->showImport = false;
        $this->importPreview = false;
        $this->importRows = [];
        $this->importFile = null;
    }

    /* ==================== BUDGET ==================== */

    public function openBudgetForm(): void
    {
        $this->abortUnlessManage();
        $this->budgetForm = [
            'item_name' => '', 'description' => '', 'amount' => '',
            'date_used' => now()->format('Y-m-d'), 'activity_id' => '', 'receipt_reference' => '',
        ];
        $this->showBudgetForm = true;
        $this->resetErrorBag('budgetForm.*');
    }

    /* ==================== PROGRAM EDIT ==================== */

    public function openProgramEdit(): void
    {
        $this->abortUnlessManage();

        $this->editForm = [
            'title' => $this->program->title,
            'description' => (string) $this->program->description,
            'planned_start_date' => $this->program->planned_start_date->format('Y-m-d'),
            'planned_end_date' => $this->program->planned_end_date->format('Y-m-d'),
            'target_beneficiaries' => (string) $this->program->target_beneficiaries,
            'allocated_budget' => (string) $this->program->allocated_budget,
            'annual_target_hours' => $this->program->annual_target_hours !== null ? (string) $this->program->annual_target_hours : '',
            'program_lead_id' => (string) $this->program->program_lead_id,
            'community_ids' => $this->program->communities->pluck('id')->all(),
            'beneficiary_categories' => $this->program->beneficiary_categories ?? [],
            'status' => $this->program->status,
        ];
        $this->resetErrorBag();
        $this->showProgramEdit = true;
        $this->dispatch('ms-sync-community_ids', ids: $this->editForm['community_ids']);
        $this->dispatch('ms-sync-beneficiary_categories', ids: array_values($this->editForm['beneficiary_categories']));
    }

    public function toggleEditArray(string $key, string|int $value): void
    {
        if (! in_array($key, ['community_ids', 'beneficiary_categories'], true)) {
            return;
        }

        $values = is_array($this->editForm[$key] ?? null) ? $this->editForm[$key] : [];

        $this->editForm[$key] = in_array($value, $values, true)
            ? array_values(array_diff($values, [$value]))
            : [...$values, $value];
    }

    public function saveProgramEdit(): void
    {
        $this->abortUnlessManage();

        $this->validate([
            'editForm.title' => 'required|string|max:255',
            'editForm.description' => 'nullable|string|max:4000',
            'editForm.planned_start_date' => 'required|date',
            'editForm.planned_end_date' => 'required|date|after_or_equal:editForm.planned_start_date',
            'editForm.target_beneficiaries' => 'nullable|integer|min:1',
            'editForm.allocated_budget' => 'nullable|numeric|min:0',
            'editForm.annual_target_hours' => 'nullable|numeric|min:0',
            'editForm.program_lead_id' => 'nullable|exists:faculties,id',
            'editForm.community_ids' => 'array',
            'editForm.community_ids.*' => 'exists:communities,id',
            'editForm.beneficiary_categories' => 'array',
            'editForm.beneficiary_categories.*' => 'string',
            'editForm.status' => 'required|in:draft,ongoing,completed,cancelled',
        ]);

        // 8.8 backstop: existing activities must still fall within the new range.
        $outside = $this->program->activities()
            ->where(function ($q) {
                $q->where('planned_start_date', '<', $this->editForm['planned_start_date'])
                    ->orWhere('planned_end_date', '>', $this->editForm['planned_end_date']);
            })
            ->pluck('title');
        if ($outside->isNotEmpty()) {
            $this->addError('editForm.planned_end_date', 'The new range would exclude existing activities: '.$outside->implode(', ').'. Reschedule or remove them first (8.8).');

            return;
        }

        $oldStatus = $this->program->status;

        $this->program->update([
            'title' => $this->editForm['title'],
            'description' => $this->editForm['description'] ?: null,
            'planned_start_date' => $this->editForm['planned_start_date'],
            'planned_end_date' => $this->editForm['planned_end_date'],
            'target_beneficiaries' => $this->editForm['target_beneficiaries'] ?: null,
            'beneficiary_categories' => $this->editForm['beneficiary_categories'],
            'allocated_budget' => $this->editForm['allocated_budget'] !== '' ? $this->editForm['allocated_budget'] : 0,
            'annual_target_hours' => $this->editForm['annual_target_hours'] !== '' ? $this->editForm['annual_target_hours'] : null,
            'program_lead_id' => $this->editForm['program_lead_id'] ?: null,
            'status' => $this->editForm['status'],
            'updated_by' => auth()->id(),
        ]);

        $this->program->communities()->sync($this->editForm['community_ids']);

        // 8.1: every status transition writes an activity log entry.
        if ($oldStatus !== $this->editForm['status']) {
            activity()->performedOn($this->program)->event('status_transition')
                ->log("Program status changed from '{$oldStatus}' to '{$this->editForm['status']}'");
        }

        $this->showProgramEdit = false;
        $this->dispatch('sc-toast', message: 'Program updated', type: 'success');
    }

    public function saveBudgetEntry(): void
    {
        $this->abortUnlessManage();

        $this->validate([
            'budgetForm.item_name' => 'required|string|max:255',
            'budgetForm.amount' => 'required|numeric|min:0.01',
            'budgetForm.date_used' => 'required|date',
            'budgetForm.activity_id' => 'nullable|exists:activities,id',
            'budgetForm.receipt_reference' => 'nullable|string|max:100',
        ]);

        $entry = new BudgetUtilization([
            'item_name' => $this->budgetForm['item_name'],
            'description' => $this->budgetForm['description'] ?: null,
            'amount' => $this->budgetForm['amount'],
            'date_used' => $this->budgetForm['date_used'],
            'activity_id' => $this->budgetForm['activity_id'] ?: null,
            'receipt_reference' => $this->budgetForm['receipt_reference'] ?: 'REC-'.now()->format('Y').'-'.str_pad((string) ($this->program->budgetUtilizations()->count() + 1), 4, '0', STR_PAD_LEFT),
        ]);
        $entry->extension_project_id = $this->program->id;
        $entry->save();

        // D7: over-allocation never hard-blocks — save succeeds, warn + log.
        if ($this->program->fresh()->isOverAllocated()) {
            $utilized = $this->program->fresh()->utilizedBudget();
            activity()->performedOn($this->program)->event('budget_overrun')
                ->log('Budget over-allocated: utilized ₱'.number_format($utilized, 2).' exceeds allocated ₱'.number_format((float) $this->program->allocated_budget));
            $this->dispatch('sc-toast', message: 'Warning: allocation exceeded — entry saved with over-allocation flag (D7)', type: 'warn');
        } else {
            $this->dispatch('sc-toast', message: 'Utilization entry saved', type: 'success');
        }

        $this->showBudgetForm = false;
    }

    public function deleteBudgetEntry(int $id): void
    {
        $this->abortUnlessManage();
        BudgetUtilization::findOrFail($id)->delete();
        $this->dispatch('sc-toast', message: 'Entry removed', type: 'warn');
    }

    /* ==================== helpers ==================== */

    protected function abortUnlessManage(): void
    {
        abort_unless($this->canManage, 403, 'Read-only view — only the Admin can manage this program.');
    }

    /**
     * v4.12: beneficiary enrollment/registry actions and attendance
     * recording are Admin OR Secretary; faculty remain read-only.
     */
    protected function abortUnlessBeneficiaryManage(): void
    {
        abort_unless($this->canManageBeneficiaries, 403, 'Only the Admin or Secretary can manage beneficiaries and attendance.');
    }

    protected function registryResults()
    {
        return Beneficiary::query()
            ->when($this->enrollSearch, fn ($q) => $q->where(fn ($w) => $w
                ->where('first_name', 'like', "%{$this->enrollSearch}%")
                ->orWhere('last_name', 'like', "%{$this->enrollSearch}%")
                ->orWhere('barangay', 'like', "%{$this->enrollSearch}%")))
            ->orderBy('last_name')
            ->limit(20)
            ->get();
    }

    protected function findScheduleConflict(int $facultyId): ?string
    {
        $faculty = Faculty::with('user')->find($facultyId);
        if (! $faculty) {
            return null;
        }

        $candidate = new Activity([
            'planned_start_date' => $this->activityForm['planned_start_date'],
            'planned_end_date' => $this->activityForm['planned_end_date'],
            'start_time' => $this->activityForm['start_time'],
            'end_time' => $this->activityForm['end_time'],
        ]);

        $assigned = $faculty->activities()
            ->whereNotIn('status', ['cancelled'])
            // MUST be qualified: `activities` is joined to `activity_faculty`,
            // and the pivot has its own `id`, so a bare `id` is ambiguous and
            // the query errors on every driver. This broke the 8.8 hard-block
            // for any faculty member who already had an activity.
            ->where('activities.id', '!=', $this->editingActivityId ?? 0)
            ->get();

        foreach ($assigned as $other) {
            if ($candidate->overlaps($other)) {
                // FOUR placeholders — the range needs both ends. Passing three
                // made the hard-block raise ArgumentCountError instead of
                // refusing, so the guard crashed rather than blocking.
                return sprintf(
                    '%s is already assigned to "%s" (%s – %s) which overlaps this schedule (8.8).',
                    $faculty->user->name,
                    $other->title,
                    $other->planned_start_date->format('M j, Y'),
                    $other->planned_end_date->format('M j, Y')
                );
            }
        }

        return null;
    }

    protected function conflictingFacultyIds(): array
    {
        $conflicts = [];
        foreach ($this->activityForm['faculty_ids'] as $facultyId) {
            if ($this->findScheduleConflict((int) $facultyId)) {
                $conflicts[] = (int) $facultyId;
            }
        }

        return $conflicts;
    }
}
