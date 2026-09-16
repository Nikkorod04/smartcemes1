<?php

namespace App\Livewire;

use App\Models\ActivityProposal;
use App\Models\AssessmentAnalysis;
use App\Models\AssessmentSummary;
use App\Models\Attendance;
use App\Models\AvailabilityRequest;
use App\Models\Community;
use App\Models\ExtensionProgram;
use App\Models\Faculty;
use App\Models\NeedsAssessment;
use App\Models\RenderedHours;
use App\Services\KpiService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity as ActivityLog;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();

        return match (true) {
            $user->isAdmin() => $this->renderAdmin(),
            $user->isSecretary() => $this->renderSecretary(),
            default => $this->renderFaculty($user),
        };
    }

    protected function renderAdmin()
    {
        $kpi = app(KpiService::class);

        $programs = ExtensionProgram::with('programObjectives')->get();

        $statusColors = [
            'ongoing' => '#003599',
            'completed' => '#10b981',
            'draft' => '#F6B800',
            'cancelled' => '#ef4444',
        ];

        $statusCounts = collect(config('smartcemes.statuses.program'))
            ->mapWithKeys(fn ($s) => [$s => $programs->where('status', $s)->count()])
            ->filter();

        $budget = $programs->map(fn ($p) => [
            'code' => $p->code,
            'planned' => (float) $p->allocated_budget,
            'actual' => $p->utilizedBudget(),
            'over' => $p->isOverAllocated(),
        ])->values();

        // 8.6 Community Reach: top 10 barangays (municipality · barangay) + Others.
        $reachRows = Attendance::query()
            ->whereIn('attendances.status', ['present', 'late'])
            ->whereHas('activity', fn ($q) => $q->whereNotIn('status', ['cancelled']))
            ->join('beneficiaries', 'attendances.beneficiary_id', '=', 'beneficiaries.id')
            ->selectRaw('beneficiaries.barangay, beneficiaries.municipality, COUNT(DISTINCT attendances.beneficiary_id) as served')
            ->groupBy('beneficiaries.barangay', 'beneficiaries.municipality')
            ->orderByDesc('served')
            ->get();

        $rankColors = ['#003599', '#003599', '#2547eb', '#2547eb', '#93b4fd', '#93b4fd', '#b9cdf7', '#b9cdf7', '#d3dff9', '#d3dff9'];
        $topReach = $reachRows->take(10)->values();
        $reachLabels = $topReach->map(fn ($r) => $r->barangay.($r->municipality ? ' · '.$r->municipality : ''))->values();
        $reachData = $topReach->pluck('served')->map(fn ($v) => (int) $v)->values();
        $reachColors = $topReach->map(fn ($r, $i) => $rankColors[$i] ?? '#b9cdf7')->values();

        $otherReach = $reachRows->slice(10)->values();
        if ($otherReach->isNotEmpty()) {
            $otherCount = $otherReach->count();
            $reachLabels->push('Others ('.$otherCount.' barangay'.($otherCount === 1 ? '' : 's').')');
            $reachData->push((int) $otherReach->sum('served'));
            $reachColors->push('#cbd5e1');
        }

        // 8.6 objective statuses derive live — never the stored column.
        $objectiveStatuses = ['achieved' => 0, 'on_track' => 0, 'not_met' => 0, 'not_started' => 0];
        foreach ($programs as $program) {
            foreach ($program->programObjectives as $objective) {
                $objectiveStatuses[$kpi->statusFor($objective)]++;
            }
        }

        $served = Attendance::whereIn('status', ['present', 'late'])->distinct('beneficiary_id')->count('beneficiary_id');
        $budgetUsed = (float) $programs->sum(fn ($p) => $p->utilizedBudget());
        $allocated = (float) $programs->sum(fn ($p) => (float) $p->allocated_budget);
        $overCount = $programs->filter(fn ($p) => $p->isOverAllocated())->count();
        $beneficiaryTarget = (int) $programs->sum(fn ($p) => (int) $p->target_beneficiaries);

        // Budget marker: allocation-weighted mean of budget_utilization
        // objective targets — hidden when no objective defines one.
        $budgetTarget = null;
        $targetWeight = 0.0;
        $weightedTarget = 0.0;
        foreach ($programs as $program) {
            foreach ($program->programObjectives as $objective) {
                if ($objective->kpi_metric === 'budget_utilization' && $objective->target_value !== null) {
                    $weight = max((float) $program->allocated_budget, 1);
                    $weightedTarget += (float) $objective->target_value * $weight;
                    $targetWeight += $weight;
                }
            }
        }
        if ($targetWeight > 0) {
            $budgetTarget = $weightedTarget / $targetWeight;
        }

        $pendingProposals = ActivityProposal::where('status', 'pending')->count();
        $pendingAvailability = AvailabilityRequest::where('status', 'pending')->count();
        $pendingHours = RenderedHours::where('status', 'pending')->count();

        $ayStart = now()->month >= 7 ? now()->year : now()->year - 1;
        $kpis = [
            'programs' => $programs->count(),
            'served' => $served,
            'allocated' => $allocated,
            'budgetUsed' => $budgetUsed,
            'budgetPct' => $allocated > 0 ? $budgetUsed / $allocated * 100 : null,
            'budgetTarget' => $budgetTarget,
            'overCount' => $overCount,
            'beneficiaryTarget' => $beneficiaryTarget,
            'pendingApprovals' => $pendingProposals + $pendingAvailability + $pendingHours,
            'pendingProposals' => $pendingProposals,
            'pendingAvailability' => $pendingAvailability,
            'pendingHours' => $pendingHours,
            'objectiveStatuses' => $objectiveStatuses,
            'objectiveTotal' => array_sum($objectiveStatuses),
            'ayLabel' => 'AY '.$ayStart.'–'.($ayStart + 1),
        ];

        // ACTION CENTER (12.1)
        $actionCenter = [
            'proposals' => ActivityProposal::with(['faculty.user', 'program'])->where('status', 'pending')->orderBy('submitted_at')->get(),
            'availability' => AvailabilityRequest::with(['activity', 'faculty.user'])->where('status', 'pending')->orderBy('date')->get(),
            'renderedHours' => RenderedHours::with(['faculty.user', 'activity'])->where('status', 'pending')->orderBy('date')->get(),
            'programsEnding' => ExtensionProgram::whereNotIn('status', ['completed', 'cancelled'])
                ->whereBetween('planned_end_date', [now()->startOfDay(), now()->addDays(14)])->get(),
            'aiAnalyses' => AssessmentAnalysis::where('approval_status', 'draft')->count(),
            'objectivesAtRisk' => $kpi->objectivesAtRisk(),
        ];

        $recentActivity = ActivityLog::query()->latest()->take(6)->get();

        // AI panel (12.1): insights drafts queue + latest narrative health per program.
        $aiDrafts = AssessmentAnalysis::with('assessmentSummary.community')
            ->where('approval_status', 'draft')->where('status', 'completed')
            ->orderByDesc('created_at')->take(3)->get();
        $aiPending = AssessmentAnalysis::where('status', 'pending')->count();
        $narrativeLabels = ExtensionProgram::with(['programNarratives' => fn ($q) => $q->with('generator')->latest()->take(1)])
            ->get()
            ->map(function ($p) {
                $narrative = $p->programNarratives->first();

                return [
                    'code' => $p->code,
                    'health' => $narrative?->health_label,
                    'status' => $narrative?->status,
                    'summary' => $narrative?->summary,
                    'generated_at' => $narrative?->generated_at,
                    'model' => data_get($narrative?->metadata, 'model'),
                ];
            });

        return view('livewire.dashboard.admin', [
            'kpis' => $kpis,
            'statusChart' => [
                'labels' => $statusCounts->keys()->map(fn ($s) => ucfirst($s))->values(),
                'data' => $statusCounts->values(),
                'colors' => $statusCounts->keys()->map(fn ($s) => $statusColors[$s] ?? '#cbd5e1')->values(),
            ],
            'budgetChart' => [
                'labels' => $budget->pluck('code'),
                'allocated' => $budget->pluck('planned'),
                'utilized' => $budget->pluck('actual'),
                'utilizedColors' => $budget->map(fn ($b) => $b['over'] ? '#ef4444' : '#F6B800')->values(),
            ],
            'reachChart' => [
                'labels' => $reachLabels,
                'data' => $reachData,
                'colors' => $reachColors,
            ],
            'actionCenter' => $actionCenter,
            'recentActivity' => $recentActivity,
            'aiDrafts' => $aiDrafts,
            'aiPending' => $aiPending,
            'narrativeLabels' => $narrativeLabels,
        ]);
    }

    protected function renderSecretary()
    {
        $pending = NeedsAssessment::with(['community', 'uploader'])->where('review_status', 'pending')->orderBy('created_at')->get();
        $validatedCount = NeedsAssessment::where('review_status', 'validated')->count();
        $returnedCount = NeedsAssessment::where('review_status', 'returned')->count();
        $importedCount = NeedsAssessment::whereNotNull('file_path')->count();

        return view('livewire.dashboard.secretary', [
            'pending' => $pending,
            'validatedCount' => $validatedCount,
            'returnedCount' => $returnedCount,
            'importedCount' => $importedCount,
            'communitiesNeeding' => Community::withCount(['needsAssessments' => fn ($q) => $q->where('review_status', 'pending')])
                ->get()
                ->filter(fn ($c) => $c->needs_assessments_count > 0)
                ->values(),
            'recentSummaries' => AssessmentSummary::with('community')->orderByDesc('last_calculated_at')->take(5)->get(),
        ]);
    }

    protected function renderFaculty($user)
    {
        $kpi = app(KpiService::class);
        $faculty = Faculty::where('user_id', $user->id)->first();

        if (! $faculty) {
            return view('livewire.dashboard.faculty', [
                'empty' => true,
                'programs' => collect(), 'activities' => collect(), 'myProposals' => collect(),
                'availability' => collect(), 'entries' => collect(), 'stats' => [],
            ]);
        }

        $ledIds = ExtensionProgram::where('program_lead_id', $faculty->id)->pluck('id');
        $assignedIds = $faculty->activities()->pluck('extension_program_id')->unique();
        $myPrograms = ExtensionProgram::whereIn('id', $ledIds->merge($assignedIds))->with('communities')->get();

        $myActivities = $faculty->activities()->with('program')->orderBy('planned_start_date')->get();
        $myProposals = $faculty->activityProposals()->with('program')->orderByDesc('submitted_at')->take(5)->get();
        $availability = $faculty->availabilityRequests()->with('activity')->where('status', 'pending')->orderBy('date')->get();
        $hours = $faculty->renderedHours()->with('activity.program')->orderByDesc('date')->get();

        return view('livewire.dashboard.faculty', [
            'empty' => false,
            'faculty' => $faculty,
            'programs' => $myPrograms,
            'activities' => $myActivities,
            'myProposals' => $myProposals,
            'availability' => $availability,
            'hours' => $hours,
            'stats' => [
                'approvedHours' => $hours->where('status', 'approved')->sum('hours'),
                'pendingHours' => $hours->where('status', 'pending')->sum('hours'),
                'programsLed' => $ledIds->count(),
                'activitiesAssigned' => $myActivities->count(),
                'activitiesCompleted' => $myActivities->where('status', 'completed')->count(),
            ],
        ]);
    }
}
