<?php

namespace App\Livewire;

use App\Models\ActivityProposal;
use App\Models\AssessmentAnalysis;
use App\Models\Attendance;
use App\Models\AvailabilityRequest;
use App\Models\ExtensionProgram;
use App\Models\Faculty;
use App\Models\RenderedHours;
use App\Services\KpiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class Analytics extends Component
{
    #[Url]
    public string $tab = 'overview';

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function render()
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $kpi = app(KpiService::class);
        $palette = config('smartcemes.chart_palette');

        $programs = ExtensionProgram::with(['programLead.user', 'communities', 'programObjectives'])->get();

        $programRows = $programs->map(fn ($p) => (object) [
            'model' => $p,
            'participation' => $kpi->participationRate($p),
            'completion' => $kpi->activityCompletionRate($p),
            'consistency' => $kpi->attendanceConsistency($p),
            'budgetUtilization' => $kpi->budgetUtilization($p),
            'knowledgeGain' => $kpi->knowledgeGain($p),
            'costPerBeneficiary' => $kpi->costPerBeneficiary($p),
            'reach' => $kpi->communityReach($p),
            'over' => $p->isOverAllocated(),
        ]);

        $objectiveStatuses = collect(['achieved' => 'Achieved', 'on_track' => 'On track', 'not_met' => 'Not met', 'not_started' => 'Not started'])
            ->map(fn ($label, $s) => $programs->sum(fn ($p) => $p->programObjectives
                ->filter(fn ($o) => $kpi->statusFor($o) === $s)->count()));

        $reach = Attendance::query()
            ->whereIn('attendances.status', ['present', 'late'])
            ->whereHas('activity', fn ($q) => $q->whereNotIn('status', ['cancelled']))
            ->join('beneficiaries', 'attendances.beneficiary_id', '=', 'beneficiaries.id')
            ->selectRaw('beneficiaries.barangay, COUNT(DISTINCT attendances.beneficiary_id) as served')
            ->groupBy('beneficiaries.barangay')
            ->orderByDesc('served')
            ->get();

        $facultyRows = Faculty::with('user')->get()->map(fn ($f) => (object) [
            'name' => $f->user->name,
            'department' => $f->department,
            'programsLed' => ExtensionProgram::where('program_lead_id', $f->id)->count(),
            'activities' => $f->activities()->count(),
            'approvedHours' => $f->renderedHours()->where('status', 'approved')->sum('hours'),
            'pendingHours' => $f->renderedHours()->where('status', 'pending')->sum('hours'),
        ]);

        // Pending actions mirror the dashboard action center (12.1).
        $pendingActions = [
            'proposals' => ActivityProposal::with(['faculty.user', 'program'])->where('status', 'pending')->orderBy('submitted_at')->get(),
            'availability' => AvailabilityRequest::with(['activity', 'faculty.user'])->where('status', 'pending')->orderBy('date')->get(),
            'renderedHours' => RenderedHours::with(['faculty.user', 'activity'])->where('status', 'pending')->orderBy('date')->get(),
            'programsEnding' => ExtensionProgram::whereNotIn('status', ['completed', 'cancelled'])
                ->whereBetween('planned_end_date', [now()->startOfDay(), now()->addDays(14)])->get(),
            'aiAnalyses' => AssessmentAnalysis::with(['assessmentSummary.community'])
                ->where('approval_status', AssessmentAnalysis::APPROVAL_DRAFT)
                ->orderByDesc('created_at')
                ->get(),
            'objectivesAtRisk' => $kpi->objectivesAtRisk(),
        ];

        return view('livewire.analytics', [
            'tab' => $this->tab,
            'programRows' => $programRows,
            'kpi' => $kpi,
            'objectiveChart' => [
                'type' => 'bar',
                'data' => [
                    'labels' => $objectiveStatuses->keys()->values(),
                    'datasets' => [[
                        'label' => 'Objectives', 'data' => $objectiveStatuses->values(),
                        'backgroundColor' => $palette[1], 'borderRadius' => 6, 'maxBarThickness' => 40,
                    ]],
                ],
                'options' => ['maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]]],
            ],
            'budgetChart' => [
                'type' => 'bar',
                'data' => [
                    'labels' => $programs->pluck('code'),
                    'datasets' => [
                        ['label' => 'Planned', 'data' => $programs->map(fn ($p) => (float) $p->allocated_budget), 'backgroundColor' => $palette[0], 'borderRadius' => 6, 'maxBarThickness' => 16],
                        ['label' => 'Utilized', 'data' => $programs->map(fn ($p) => $p->utilizedBudget()), 'backgroundColor' => $palette[1], 'borderRadius' => 6, 'maxBarThickness' => 16],
                    ],
                ],
                'options' => ['maintainAspectRatio' => false, 'scales' => ['y' => ['beginAtZero' => true]]],
            ],
            'reachChart' => [
                'type' => 'bar',
                'data' => [
                    'labels' => $reach->pluck('barangay'),
                    'datasets' => [[
                        'label' => 'Served', 'data' => $reach->pluck('served'),
                        'backgroundColor' => $palette[0], 'borderRadius' => 6, 'maxBarThickness' => 40,
                    ]],
                ],
                'options' => ['maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]]],
            ],
            'facultyChart' => [
                'type' => 'bar',
                'data' => [
                    'labels' => $facultyRows->map(fn ($f) => $f->name),
                    'datasets' => [[
                        'label' => 'Approved hrs', 'data' => $facultyRows->map(fn ($f) => (float) $f->approvedHours),
                        'backgroundColor' => $palette[1], 'borderRadius' => 6, 'maxBarThickness' => 28,
                    ]],
                ],
                'options' => ['maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]]],
            ],
            'pendingActions' => $pendingActions,
            'reach' => $reach,
            'facultyRows' => $facultyRows,
        ]);
    }
}
