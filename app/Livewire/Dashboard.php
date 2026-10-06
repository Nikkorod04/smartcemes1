<?php

namespace App\Livewire;

use App\Models\ActivityProposal;
use App\Models\AssessmentAnalysis;
use App\Models\AssessmentSummary;
use App\Models\AvailabilityRequest;
use App\Models\College;
use App\Models\Community;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\NeedsAssessment;
use App\Models\Program;
use App\Models\RenderedHours;
use App\Models\UniversityTarget;
use App\Services\RankingService;
use App\Services\TrainingHoursService;
use Livewire\Attributes\Layout;
use Livewire\Component;

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
        $hours = app(TrainingHoursService::class);
        $rankings = app(RankingService::class);

        // R4 / R-Q2: the results framework is soft-deprecated. Objectives are no
        // longer eager-loaded because the dashboard's KPI row is rebuilt on the
        // target model (§4.7) — see the note in the returned view for what
        // replaced the objective-status tile.
        $programs = ExtensionProject::with(['college'])->get();

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
            // The TITLE, not the code (owner request 2026-09-25): the budget panel
            // labels each row with the project a Director actually recognises.
            'title' => $p->title,
            // Budget has NO annual target (owner decision 2026-09-26): the
            // allocation is the only project-level budget figure, so it is the
            // denominator. `annual_target_budget` is retained but unread.
            'allocated' => $p->budgetAllocated(),
            'actual' => $p->utilizedBudget(),
            'over' => $p->isOverAllocated(),
        ])->values();

        /* ------------------------------------------------------------------ */
        /* R5: Community Reach and the Action Center were removed from the */
        /* dashboard by the prototype pass (P0m) — `_dashtest.cjs` asserts the */
        /* reach chart and every `actionCenter` reference are GONE, not merely */
        /* hidden. The pending-approvals count survives as a KPI tile. */
        /* ------------------------------------------------------------------ */

        $budgetUsed = (float) $programs->sum(fn ($p) => $p->utilizedBudget());
        $allocated = (float) $programs->sum(fn ($p) => $p->budgetAllocated());
        $overCount = $programs->filter(fn ($p) => $p->isOverAllocated())->count();

        /* ------------------------------------------------------------------ */
        /* R4: the university-wide training-hours actual, straight from the */
        /* service so it equals the sum of the project hubs exactly. */
        /* The annual target comes from `university_targets` (R-Q3). */
        /* ------------------------------------------------------------------ */
        $ayStart = now()->month >= 7 ? now()->year : now()->year - 1;
        $target = UniversityTarget::forYear($ayStart) ?? UniversityTarget::current();
        $trainingHours = 0.0;
        $trainingDays = 0.0;
        $traineesReached = 0;
        $activityCount = 0;

        foreach ($programs as $program) {
            $rollup = $hours->forProject($program);
            $trainingHours += $rollup['actual_hours'];
            $trainingDays += $rollup['training_days'];
            $traineesReached += $rollup['trainees'];
            $activityCount += $rollup['activity_count'];
        }

        $annualTargetHours = $target?->annual_target_hours !== null ? (float) $target->annual_target_hours : null;

        $pendingProposals = ActivityProposal::where('status', 'pending')->count();
        $pendingAvailability = AvailabilityRequest::where('status', 'pending')->count();
        $pendingHours = RenderedHours::where('status', 'pending')->count();
        $pendingAnalyses = AssessmentAnalysis::where('approval_status', 'draft')->count();

        $kpis = [
            'programs' => $programs->count(),
            'traineesReached' => $traineesReached,
            'allocated' => $allocated,
            'budgetUsed' => $budgetUsed,
            'budgetPct' => $allocated > 0 ? round($budgetUsed / $allocated * 100, 1) : null,
            'overCount' => $overCount,
            // R4 training-hours model
            'trainingHours' => $trainingHours,
            'trainingDays' => $trainingDays,
            'annualTargetHours' => $annualTargetHours,
            'hoursPct' => $annualTargetHours !== null && $annualTargetHours > 0
                ? round($trainingHours / $annualTargetHours * 100, 1)
                : null,
            'hoursRemaining' => $annualTargetHours !== null ? max($annualTargetHours - $trainingHours, 0.0) : null,
            'targetLabel' => $target?->display_label,
            'hasTarget' => $target !== null,
            'activityCount' => $activityCount,
            'pendingApprovals' => $pendingProposals + $pendingAvailability + $pendingHours,
            'pendingProposals' => $pendingProposals,
            'pendingAvailability' => $pendingAvailability,
            'pendingHours' => $pendingHours,
            'pendingAnalyses' => $pendingAnalyses,
            'facultyCount' => Faculty::count(),
            'activeFaculty' => Faculty::active()->count(),
            'collegeCount' => College::count(),
            'programCount' => Program::count(),
            'ayLabel' => 'AY '.$ayStart.'–'.($ayStart + 1),
        ];

        /* Recent Activity MOVED to its own page (owner request 2026-09-25):
           Audit Logs, under Intelligence & Reports in the sidebar. As a four-row
           dashboard panel it could never answer "who changed this, and when". */

        // AI panel (12.1): insights drafts queue + latest narrative health per project.
        $aiDrafts = AssessmentAnalysis::with('assessmentSummary.community')
            ->where('approval_status', 'draft')->where('status', 'completed')
            ->orderByDesc('created_at')->take(3)->get();
        $aiPending = AssessmentAnalysis::where('status', 'pending')->count();
        $narrativeLabels = ExtensionProject::with(['programNarratives' => fn ($q) => $q->with('generator')->latest()->take(1)])
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

        /* ------------------------------------------------------------------ */
        /* R5 (§5 step 1): the Performance Leaders rankings. Both lists read */
        /* from RankingService so a leaderboard can never disagree with the */
        /* number printed on the row it links to. */
        /* ------------------------------------------------------------------ */
        $topProjects = $rankings->projects(5);
        $topFaculty = $rankings->faculty(5);

        /* Performance Leaders gain a magnitude bar (owner request 2026-09-25), so
           rank AND scale read at a glance instead of rank alone.

           ONE scale for both lists — the largest target in the project set, or the
           largest value when nothing carries a target — so a row's target marker
           shares the axis with its bar. Scaling per row would make every bar full
           width and say nothing. The 1.0 floor keeps an empty seed from dividing
           by zero. */
        $leaderScale = (float) max(
            $topProjects->max(fn ($p) => max((float) ($p['training_hours'] ?? 0), (float) ($p['target_hours'] ?? 0))),
            $topFaculty->max(fn ($f) => (float) ($f['rendered_hours'] ?? 0)),
            1.0,
        );

        $topProjects = $topProjects->map(fn (array $p) => $p + [
            'bar_pct' => round((float) ($p['training_hours'] ?? 0) / $leaderScale * 100, 2),
            'target_pct' => ($p['target_hours'] ?? null) !== null
                ? round((float) $p['target_hours'] / $leaderScale * 100, 2)
                : null,
        ]);

        /* Faculty carry NO target (D-R19: contribution, never attainment), so their
           rows get a bar and no marker — inventing one would be a fabricated
           percentage. */
        $topFaculty = $topFaculty->map(fn (array $f) => $f + [
            'bar_pct' => round((float) ($f['rendered_hours'] ?? 0) / $leaderScale * 100, 2),
            'target_pct' => null,
        ]);

        // Chart rows: rendered vs each PROJECT's annual target. Targets exist at
        // university and project level only (D-R5) — never per program.
        $hoursRows = $rankings->projects();

        return view('livewire.dashboard.admin', [
            'kpis' => $kpis,
            'statusChart' => [
                'labels' => $statusCounts->keys()->map(fn ($s) => ucfirst($s))->values(),
                'data' => $statusCounts->values(),
                'colors' => $statusCounts->keys()->map(fn ($s) => $statusColors[$s] ?? '#cbd5e1')->values(),
            ],
            /* Compact bullet rows (owner request 2026-09-25). The previous grouped
               bar chart could only label its axis with the project CODE: a vertical
               axis has no room for a title, and a code tells the Director nothing.
               `pct` is utilization against the project's ALLOCATION — budget has no
               annual target (owner decision 2026-09-26) — which is what the row bar
               draws; the amounts sit inline beside it. */
            'budgetRows' => $budget->map(fn ($b) => [
                'title' => $b['title'],
                'allocated' => $b['allocated'],
                'actual' => $b['actual'],
                'over' => $b['over'],
                'pct' => $b['allocated'] > 0 ? round($b['actual'] / $b['allocated'] * 100, 1) : null,
            ])->values(),
            'hoursChart' => [
                'labels' => $hoursRows->pluck('short_title')->values(),
                'target' => $hoursRows->pluck('target_hours')->values(),
                'rendered' => $hoursRows->pluck('training_hours')->values(),
            ],
            'topProjects' => $topProjects,
            'topFaculty' => $topFaculty,
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
        // R4 / D-R7: this method used to instantiate `KpiService` here to feed the
        // 8.6 KPI tiles. Those tiles were removed and the assignment was left
        // behind, resolving the service (and its dependencies) on every faculty
        // dashboard load for a value nobody read. Removed in R7.
        $faculty = Faculty::where('user_id', $user->id)->first();

        if (! $faculty) {
            return view('livewire.dashboard.faculty', [
                'empty' => true,
                'programs' => collect(), 'activities' => collect(), 'myProposals' => collect(),
                'availability' => collect(), 'entries' => collect(), 'stats' => [],
            ]);
        }

        $ledIds = ExtensionProject::where('program_lead_id', $faculty->id)->pluck('id');
        $assignedIds = $faculty->activities()->pluck('extension_project_id')->unique();
        $myPrograms = ExtensionProject::whereIn('id', $ledIds->merge($assignedIds))->with('communities')->get();

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
