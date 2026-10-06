<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\RenderedHours;
use App\Services\TrainingHoursService;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('reports.index', [
            'programs' => ExtensionProject::orderBy('code')->get(),
        ]);
    }

    /** 1. Annual Extension Performance Report (sections I-VII). */
    public function annual(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $year = request('year', now()->year);
        $hours = app(TrainingHoursService::class);

        $programs = ExtensionProject::with(['programLead.user', 'communities', 'activities'])
            ->whereYear('planned_start_date', '<=', $year)
            ->whereYear('planned_end_date', '>=', $year)
            ->get();

        return view('reports.annual', [
            'year' => $year,
            'programs' => $programs,
            // R4 / D-R7: "trainees reached" replaces the 8.6 community-reach
            // figure, and comes from the training-hours service so the report
            // matches the dashboard and the project hubs exactly.
            'totalServed' => (int) $programs->sum(fn ($p) => $hours->forProject($p)['trainees']),
            // R5: the training-delivery table is the report's headline section —
            // the same four figures the dashboard and the project hubs print, so
            // a printed figure and an on-screen figure cannot disagree.
            'trainingDelivery' => $programs->map(function ($p) use ($hours) {
                $rollup = $hours->forProject($p);

                return [
                    'code' => $p->code,
                    'title' => $p->title,
                    'trainors' => $rollup['trainors'],
                    'trainees' => $rollup['trainees'],
                    'training_hours' => $rollup['actual_hours'],
                    'target_hours' => $rollup['target_hours'],
                    // NULL (not 0) when no target is set — the view says "no
                    // target set" rather than printing a fabricated 0%.
                    'hours_pct' => $rollup['hours_pct'],
                ];
            }),
            'facultyParticipation' => Faculty::with('user')->get()->map(fn ($f) => [
                'name' => $f->user->name,
                'programs' => ExtensionProject::where('program_lead_id', $f->id)->count(),
                'hours' => $f->renderedHours()->where('status', 'approved')->sum('hours'),
            ]),
            'budgetUtilization' => $programs->map(function ($p) {
                // Owner decision 2026-09-26: a project has NO annual budget
                // target — its allocation is the only budget figure, so it is
                // the denominator here and on every other surface.
                $allocation = $p->budgetAllocated();
                $utilized = $p->utilizedBudget();

                return [
                    'code' => $p->code, 'title' => $p->title,
                    'allocation' => $allocation,
                    'utilized' => $utilized,
                    'pct' => $allocation > 0 ? round($utilized / $allocation * 100, 1) : null,
                    'over' => $p->isOverAllocated(),
                ];
            }),
        ]);
    }

    /**
     * 2. Project Performance Report.
     *
     * R4 / D-R7: this report used to print the results framework (objectives
     * against the 8.6 KPI dictionary). It now prints what the Director actually
     * tracks — trainors, trainees, training hours rendered and budget against
     * the project's annual targets. `kpi` is no longer passed: the route and the
     * file name are unchanged so existing links keep working, but neither the
     * report nor this controller reads the KPI dictionary any more.
     */
    public function resultsFramework(ExtensionProject $program): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $program->load(['programLead.user', 'communities']);

        return view('reports.results-framework', [
            'program' => $program,
            // Activity dates pre-resolved so the view holds no queries.
            'activityDates' => $program->activities()
                ->orderBy('planned_start_date')
                ->get()
                ->mapWithKeys(fn ($a) => [$a->id => $a->planned_start_date->format('M j, Y')]),
        ]);
    }

    /** 3. Faculty Rendered Hours (per semester, by activity and program). */
    public function renderedHours(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $semester = request('semester', now()->month <= 5 ? 2 : (now()->month <= 10 ? 1 : 2));

        $entries = RenderedHours::with(['faculty.user', 'activity.program'])
            ->where('status', 'approved')
            ->get()
            ->groupBy(fn ($e) => $e->faculty->user->name);

        return view('reports.rendered-hours', [
            'entries' => $entries,
        ]);
    }

    /** 4. Community Partner Impact Summary. */
    public function communityImpact(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        // R4 / D-R7: the per-project "served" figure came from the 8.6 KPI
        // dictionary. It now comes from the training-hours service, which
        // resolves present/late attendance first and falls back to the manual
        // participant count — with a source tag the report can show.
        $hours = app(TrainingHoursService::class);

        $communities = Community::with(['extensionPrograms'])->get()->map(fn ($c) => (object) [
            'community' => $c,
            'programs' => $c->extensionPrograms,
            'served' => $c->extensionPrograms->sum(fn ($p) => $hours->forProject($p)['trainees']),
            'summaries' => $c->assessmentSummaries()->orderByDesc('year')->orderByDesc('quarter')->take(2)->get(),
        ]);

        return view('reports.community-impact', [
            'communities' => $communities,
        ]);
    }
}
