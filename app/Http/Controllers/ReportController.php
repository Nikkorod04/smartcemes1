<?php

namespace App\Http\Controllers;

use App\Models\AssessmentSummary;
use App\Models\College;
use App\Models\Community;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\RenderedHours;
use App\Services\ReportPdfService;
use App\Services\TrainingHoursService;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * Institutional print reports (blueprint 12.2).
 *
 * 2026-11-20 redesign: the index is a REPORT WORKSPACE (choose a report + scope,
 * see the A4 landscape paper), and every report accepts the same scope filters:
 * year, college, broad program, and community.
 *
 * No PDF engine is used. "Save as PDF" is the browser's print destination; the
 * report layout is the print layout, rendered at A4 landscape on screen so the
 * preview and the paper agree.
 */
class ReportController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $projects = ExtensionProject::query()
            ->with('communities:id')
            ->orderBy('code')
            ->get(['id', 'code', 'title', 'college_id', 'program_id'])
            ->map(fn (ExtensionProject $p) => [
                'id' => $p->id,
                'code' => $p->code,
                'title' => $p->title,
                'college' => $p->college_id,
                'program' => $p->program_id,
                'communities' => $p->communities->pluck('id')->values()->all(),
            ])
            ->values();

        $years = ExtensionProject::query()
            ->get(['planned_start_date', 'planned_end_date'])
            ->flatMap(fn (ExtensionProject $p) => [
                $p->planned_start_date?->year,
                $p->planned_end_date?->year,
            ])
            ->filter()
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        $colleges = College::query()->orderBy('name')->get(['id', 'name', 'short_name']);
        $programs = Program::query()->orderBy('title')->get(['id', 'title', 'code']);
        $communities = Community::query()->orderBy('name')->get(['id', 'name', 'municipality']);

        return view('reports.index', [
            'years' => $years,
            'defaultYear' => now()->year,
            'colleges' => $colleges,
            'programs' => $programs,
            'communities' => $communities,
            'projects' => $projects,
            'collegeNames' => $colleges->mapWithKeys(fn ($c) => [(string) $c->id => $c->short_name ?: $c->name]),
            'programNames' => $programs->mapWithKeys(fn ($p) => [(string) $p->id => $p->code.' — '.$p->title]),
            'communityNames' => $communities->mapWithKeys(fn ($c) => [(string) $c->id => $c->name]),
        ]);
    }

    /** 1. Annual Extension Performance Report — the flagship institutional report. */
    public function annual(): View|Response
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $filters = $this->reportFilters();
        $hours = app(TrainingHoursService::class);

        $programs = $this->projectsForYear($filters)
            ->with(['programLead.user', 'communities', 'activities'])
            ->orderBy('code')
            ->get();

        $trainingDelivery = $programs->map(function (ExtensionProject $p) use ($hours) {
            $rollup = $hours->forProject($p);

            return [
                'code' => $p->code,
                'title' => $p->title,
                'trainors' => $rollup['trainors'],
                'trainees' => $rollup['trainees'],
                'training_hours' => $rollup['actual_hours'],
                'target_hours' => $rollup['target_hours'],
                'hours_pct' => $rollup['hours_pct'],
            ];
        });

        $budgetUtilization = $programs->map(function (ExtensionProject $p) {
            $allocation = $p->budgetAllocated();
            $utilized = $p->utilizedBudget();

            return [
                'code' => $p->code,
                'title' => $p->title,
                'allocation' => $allocation,
                'utilized' => $utilized,
                'pct' => $allocation > 0 ? round($utilized / $allocation * 100, 1) : null,
                'over' => $p->isOverAllocated(),
            ];
        });

        $projectIds = $programs->pluck('id');

        $facultyParticipation = Faculty::query()
            ->with('user')
            ->get()
            ->map(fn (Faculty $f) => [
                'name' => $f->user?->name ?? 'Unassigned faculty',
                'programs' => $programs->where('program_lead_id', $f->id)->count(),
                'hours' => (float) $f->renderedHours()
                    ->where('status', 'approved')
                    ->whereYear('date', $filters['year'])
                    ->when($projectIds->isNotEmpty(), fn ($q) => $q->whereHas(
                        'activity',
                        fn ($q) => $q->whereIn('extension_project_id', $projectIds)
                    ))
                    ->when($projectIds->isEmpty(), fn ($q) => $q->whereRaw('1 = 0'))
                    ->sum('hours'),
            ])
            ->filter(fn (array $row) => $row['programs'] > 0 || $row['hours'] > 0)
            ->values();

        // Only summaries for the reporting year, with data, whose community still
        // exists. A deleted community used to 500 this page.
        $communityIds = $programs->flatMap->communities->pluck('id')->unique();

        $summaries = AssessmentSummary::query()
            ->with('community')
            ->where('year', $filters['year'])
            ->where('total_responses', '>', 0)
            ->whereHas('community')
            ->when($filters['community'], fn ($q) => $q->where('community_id', $filters['community']))
            ->when(
                ($filters['college'] || $filters['program']) && $communityIds->isNotEmpty(),
                fn ($q) => $q->whereIn('community_id', $communityIds)
            )
            ->orderBy('community_id')
            ->orderByDesc('quarter')
            ->get();

        $summary = [
            'projects' => $programs->count(),
            'trainees' => (int) $trainingDelivery->sum('trainees'),
            'training_hours' => (float) $trainingDelivery->sum('training_hours'),
            'allocation' => (float) $budgetUtilization->sum('allocation'),
            'utilized' => (float) $budgetUtilization->sum('utilized'),
        ];
        $summary['budget_pct'] = $summary['allocation'] > 0
            ? round($summary['utilized'] / $summary['allocation'] * 100, 1)
            : null;

        return $this->respond(
            'reports.annual',
            compact(
                'filters',
                'programs',
                'trainingDelivery',
                'budgetUtilization',
                'facultyParticipation',
                'summaries',
                'summary',
            ) + [
                'year' => $filters['year'],
                'totalServed' => $summary['trainees'],
                'scopeLabel' => $this->scopeLabel($filters),
            ],
            'Annual Extension Performance Report',
            'Academic Year '.$filters['year'].' · Institutional report',
            'annual-extension-performance-'.$filters['year'].'.pdf',
        );
    }

    /** 2. Project Performance Report. */
    public function resultsFramework(ExtensionProject $program): View|Response
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $program->load(['programLead.user', 'communities', 'college', 'program']);

        return $this->respond(
            'reports.results-framework',
            [
                'program' => $program,
                'activityDates' => $program->activities()
                    ->orderBy('planned_start_date')
                    ->get()
                    ->mapWithKeys(fn ($a) => [$a->id => $a->planned_start_date->format('M j, Y')]),
            ],
            'Project Performance Report',
            $program->code.' · '.$program->title,
            'project-performance-'.$program->code.'.pdf',
        );
    }

    /** 3. Faculty Rendered Hours (approved only, by activity and project). */
    public function renderedHours(): View|Response
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $filters = $this->reportFilters();

        $entries = RenderedHours::query()
            ->with(['faculty.user', 'activity.program'])
            ->where('status', 'approved')
            ->whereYear('date', $filters['year'])
            ->when(
                $filters['college'] || $filters['program'] || $filters['community'],
                fn ($q) => $q->whereHas(
                    'activity.program',
                    fn ($q) => $this->applyProjectScope($q, $filters)
                )
            )
            ->get()
            ->groupBy(fn (RenderedHours $e) => $e->faculty?->user?->name ?? 'Unassigned faculty');

        return $this->respond(
            'reports.rendered-hours',
            [
                'filters' => $filters,
                'entries' => $entries,
                'grandTotal' => (float) $entries->flatten()->sum('hours'),
                'entryCount' => $entries->flatten()->count(),
                'scopeLabel' => $this->scopeLabel($filters),
            ],
            'Faculty Rendered Hours Report',
            'Approved entries only · by faculty, activity and project',
            'faculty-rendered-hours-'.$filters['year'].'.pdf',
        );
    }

    /** 4. Community Partner Impact Summary. */
    public function communityImpact(): View|Response
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $filters = $this->reportFilters();
        $hours = app(TrainingHoursService::class);

        $communities = Community::query()
            ->when($filters['community'], fn ($q) => $q->whereKey($filters['community']))
            ->where(function ($q) use ($filters) {
                $q->whereHas('extensionPrograms', fn ($q) => $this->projectsForYearQuery($q, $filters));

                // A community with only assessments (no project) is relevant to
                // the unfiltered impact report, but not to a college/program
                // slice: it has no project in that slice to attribute.
                if (! $filters['college'] && ! $filters['program']) {
                    $q->orWhereHas('assessmentSummaries', fn ($q) => $q
                        ->where('year', $filters['year'])
                        ->where('total_responses', '>', 0));
                }
            })
            ->with([
                'extensionPrograms' => fn ($q) => $this->projectsForYearQuery($q, $filters)
                    ->orderBy('code'),
                'assessmentSummaries' => fn ($q) => $q
                    ->where('year', $filters['year'])
                    ->where('total_responses', '>', 0)
                    ->orderByDesc('quarter'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Community $c) => (object) [
                'community' => $c,
                'programs' => $c->extensionPrograms,
                'summaries' => $c->assessmentSummaries,
                'served' => $c->extensionPrograms->sum(fn ($p) => $hours->forProject($p)['trainees']),
                'training_hours' => $c->extensionPrograms->sum(fn ($p) => $hours->forProject($p)['actual_hours']),
            ]);

        return $this->respond(
            'reports.community-impact',
            [
                'filters' => $filters,
                'communities' => $communities,
                'totals' => [
                    'communities' => $communities->count(),
                    'projects' => $communities->sum(fn ($c) => $c->programs->count()),
                    'served' => (int) $communities->sum('served'),
                    'training_hours' => (float) $communities->sum('training_hours'),
                ],
                'scopeLabel' => $this->scopeLabel($filters),
            ],
            'Community Partner Impact Summary',
            'Projects, reach, and validated assessment outcomes per community',
            'community-partner-impact-'.$filters['year'].'.pdf',
        );
    }

    /**
     * Render the HTML report, or stream the real PDF when `?pdf=1` is present.
     *
     * `?download=1` turns the same PDF into an attachment. The view is chosen
     * by the controller (never by the request), so the PDF route cannot render
     * an arbitrary template.
     */
    private function respond(
        string $view,
        array $data,
        string $title,
        string $subtitle,
        string $filename,
    ): View|Response {
        if (request()->boolean('pdf')) {
            return app(ReportPdfService::class)->stream(
                $view,
                $data,
                $title,
                $subtitle,
                $filename,
                request()->boolean('download'),
            );
        }

        return view($view, $data);
    }

    /** @return array{year:int,college:?int,program:?int,community:?int} */
    private function reportFilters(): array
    {
        $year = (int) request('year', now()->year);

        if ($year < 2000 || $year > 2100) {
            $year = (int) now()->year;
        }

        return [
            'year' => $year,
            'college' => $this->positiveId(request('college')),
            'program' => $this->positiveId(request('program')),
            'community' => $this->positiveId(request('community')),
        ];
    }

    private function positiveId(mixed $value): ?int
    {
        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    private function projectsForYear(array $filters): Builder
    {
        return $this->projectsForYearQuery(ExtensionProject::query(), $filters);
    }

    private function projectsForYearQuery(Builder $query, array $filters): Builder
    {
        return $this->applyProjectScope($query, $filters)
            ->whereYear('planned_start_date', '<=', $filters['year'])
            ->whereYear('planned_end_date', '>=', $filters['year']);
    }

    private function applyProjectScope(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['college'], fn ($q) => $q->where('college_id', $filters['college']))
            ->when($filters['program'], fn ($q) => $q->where('program_id', $filters['program']))
            ->when($filters['community'], fn ($q) => $q->whereHas(
                'communities',
                fn ($q) => $q->where('communities.id', $filters['community'])
            ));
    }

    /** A compact line for the report header: year + the three scope filters. */
    private function scopeLabel(array $filters): string
    {
        return implode(' · ', [
            (string) $filters['year'],
            $filters['college']
                ? (College::find($filters['college'])?->short_name ?? 'Selected college')
                : 'All colleges',
            $filters['program']
                ? (Program::find($filters['program'])?->code ?? 'Selected program')
                : 'All programs',
            $filters['community']
                ? (Community::find($filters['community'])?->name ?? 'Selected community')
                : 'All communities',
        ]);
    }
}
