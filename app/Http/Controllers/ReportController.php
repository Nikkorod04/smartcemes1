<?php

namespace App\Http\Controllers;

use App\Models\Community;
use App\Models\ExtensionProgram;
use App\Models\Faculty;
use App\Models\RenderedHours;
use App\Services\KpiService;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        return view('reports.index', [
            'programs' => ExtensionProgram::orderBy('code')->get(),
        ]);
    }

    /** 1. Annual Extension Performance Report (sections I-VII). */
    public function annual(): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $kpi = app(KpiService::class);
        $year = request('year', now()->year);

        $programs = ExtensionProgram::with(['programLead.user', 'communities', 'activities'])
            ->whereYear('planned_start_date', '<=', $year)
            ->whereYear('planned_end_date', '>=', $year)
            ->get();

        return view('reports.annual', [
            'year' => $year,
            'programs' => $programs,
            'kpi' => $kpi,
            'totalServed' => $kpi->communityReachAll(),
            'facultyParticipation' => Faculty::with('user')->get()->map(fn ($f) => [
                'name' => $f->user->name,
                'programs' => ExtensionProgram::where('program_lead_id', $f->id)->count(),
                'hours' => $f->renderedHours()->where('status', 'approved')->sum('hours'),
            ]),
            'budgetUtilization' => $programs->map(fn ($p) => [
                'code' => $p->code, 'title' => $p->title,
                'allocated' => (float) $p->allocated_budget,
                'utilized' => $p->utilizedBudget(),
                'pct' => $kpi->budgetUtilization($p),
            ]),
        ]);
    }

    /** 2. Program Results Framework (per program objectives). */
    public function resultsFramework(ExtensionProgram $program): View
    {
        abort_unless(auth()->user()->isAdmin(), 403);
        $kpi = app(KpiService::class);

        return view('reports.results-framework', [
            'program' => $program->load(['programLead.user', 'communities']),
            'kpi' => $kpi,
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
        $kpi = app(KpiService::class);

        $communities = Community::with(['extensionPrograms'])->get()->map(fn ($c) => (object) [
            'community' => $c,
            'programs' => $c->extensionPrograms,
            'served' => $c->extensionPrograms->sum(fn ($p) => $kpi->communityReach($p)),
            'summaries' => $c->assessmentSummaries()->orderByDesc('year')->orderByDesc('quarter')->take(2)->get(),
        ]);

        return view('reports.community-impact', [
            'communities' => $communities,
        ]);
    }
}
