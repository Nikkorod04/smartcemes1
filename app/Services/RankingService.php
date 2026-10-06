<?php

namespace App\Services;

use App\Models\College;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The R5 rankings — "most active project / faculty / college" (§5 Phase R5).
 *
 * WHY THIS CLASS EXISTS
 * ---------------------
 * The prototype's `mostActiveProjects` / `mostActiveFaculty` sort predicates are
 * the contract the Director sees. Reproducing them in three different blades
 * would let them drift, and a ranking that disagrees with the number printed on
 * the row is worse than no ranking. So the sort rules live here, once.
 *
 * NO INVENTED NUMBERS (`revisions.md` §5 exit criteria)
 * -----------------------------------------------------
 * Every figure on every row is read from `TrainingHoursService` (training hours,
 * trainees, trainors, activities) or `FacultyContributionService` (rendered
 * hours, project involvement). Nothing is estimated, pro-rated or defaulted to a
 * plausible-looking value. Where a denominator does not exist the key is **null**
 * and the UI says "no target" rather than printing a percentage against zero.
 *
 * THE TWO SORT RULES (ported from the prototype, deliberately asymmetric)
 * ----------------------------------------------------------------------
 *   project : trainingHours + reached          DESC   — hours delivered then reach
 *   faculty : renderedHours + projects * 10    DESC   — hours then involvement
 *
 * The faculty weight exists because a professor who leads three projects and
 * renders 10 hours has demonstrably contributed more than one who renders 10
 * hours on a single activity. The prototype chose it and the Director has seen
 * it since P0, so it is preserved rather than re-derived.
 *
 * **There is NO per-professor target.** Faculty rank by contribution, never by
 * attainment — there is no denominator to attain against (D-R9). Any `pct` key
 * on a faculty row would be a fabrication, so no faculty row carries one.
 */
class RankingService
{
    public function __construct(
        private readonly TrainingHoursService $hours,
        private readonly FacultyContributionService $faculty,
    ) {}

    /** The involvement weight in the faculty sort. Prototype: `projectCount * 10`. */
    public const INVOLVEMENT_WEIGHT = 10;

    /**
     * The CAS/COE/CME/GRAD accent palette.
     *
     * The colour lives here rather than in a `colleges.color` column because it
     * is presentation, not data — and it must match
     * `resources/views/components/sc/college-pill.blade.php`, which the blade
     * still owns for its own markup. This map exists so a chart axis can resolve
     * the same colour without the service reaching into a view.
     */
    public const COLLEGE_COLORS = [
        'CAS' => '#003599',
        'COE' => '#F6B800',
        'CME' => '#10b981',
        // The Graduate School (owner request 2026-09-25). Violet, so it stays
        // distinct from CAS blue, COE gold and CME emerald on cards and charts.
        // Without an entry here the resolver returns null and every surface
        // silently falls back to CAS blue.
        'GRAD' => '#7c3aed',
    ];

    /** Resolve a college code to its accent colour, or null when unknown. */
    public static function collegeColor(?string $code): ?string
    {
        return $code === null ? null : (self::COLLEGE_COLORS[strtoupper($code)] ?? null);
    }

    /**
     * A project's short label for a chart axis.
     *
     * Projects have a `code` but no acronym column, so the prototype's `acr` is
     * derived the same way the axis needs it: the leading segment before a colon,
     * trimmed. `"BUSOG: Nutrition & Feeding"` becomes `"BUSOG"`. Where there is
     * no colon the whole title is used, so an axis never renders an empty label.
     */
    public function shortTitle(?string $title): string
    {
        $title = trim((string) $title);

        if ($title === '') {
            return '—';
        }

        $head = trim(explode(':', $title)[0]);

        return $head !== '' ? $head : $title;
    }

    /* ------------------------------------------------------------------ */
    /* Projects */
    /* ------------------------------------------------------------------ */

    /**
     * Most active projects, ranked by training hours rendered then reach.
     *
     * @param  int|null  $limit  null = every project
     * @return Collection<int, array<string, mixed>>
     */
    public function projects(?int $limit = null, ?int $collegeId = null, ?int $year = null): Collection
    {
        $query = ExtensionProject::query()->with(['college']);

        if ($collegeId !== null) {
            $query->where('college_id', $collegeId);
        }

        if ($year !== null) {
            $query->whereYear('planned_start_date', $year);
        }

        $rows = $query->get()->map(function (ExtensionProject $project) {
            $rollup = $this->hours->forProject($project);

            return [
                'id' => $project->id,
                'code' => $project->code,
                'title' => $project->title,
                'short_title' => $this->shortTitle($project->title),
                'status' => $project->status,
                'college' => $project->college?->code,
                'college_name' => $project->college?->name,
                'college_color' => self::collegeColor($project->college?->code),

                // All four read from the service — the same numbers the project
                // hub and the targets page print.
                'training_hours' => $rollup['actual_hours'],
                'target_hours' => $rollup['target_hours'],
                'hours_pct' => $rollup['hours_pct'],
                'trainees' => $rollup['trainees'],
                'trainors' => $rollup['trainors'],
                'activities' => $rollup['activity_count'],
                'completed' => $rollup['completed_count'],
                'budget_allocated' => $rollup['allocated_budget'],
                'budget_utilized' => $rollup['utilized_budget'],
                'budget_pct' => $rollup['budget_pct'],
                'over_budget' => $project->isOverAllocated(),

                // The prototype's engagement score, kept as a named key so the
                // sort rule is inspectable rather than buried in a comparator.
                'engagement' => $rollup['actual_hours'] + $rollup['trainees'],
            ];
        });

        $rows = $rows->sortByDesc('engagement')->values();

        return $limit === null ? $rows : $rows->take($limit)->values();
    }

    /* ------------------------------------------------------------------ */
    /* Faculty */
    /* ------------------------------------------------------------------ */

    /**
     * Most active faculty, ranked by rendered hours then project involvement.
     *
     * Reads `FacultyContributionService` so the leaderboard and the Faculty
     * Management board cannot disagree about a professor's rendered hours.
     *
     * @param  int|null  $limit  null = every faculty member
     * @return Collection<int, array<string, mixed>>
     */
    public function faculty(?int $limit = null, ?int $collegeId = null, ?int $year = null): Collection
    {
        $query = Faculty::query()->with(['user', 'college', 'expertise']);

        if ($collegeId !== null) {
            $query->where('college_id', $collegeId);
        }

        // The contribution service is year-agnostic (rendered hours are dated,
        // project counts are not), so a year filter would silently mean "hours
        // in that year but projects across all time" — a misleading mix. The
        // parameter is therefore accepted for signature symmetry and ignored,
        // documented here rather than faked.
        $rows = $this->faculty->forFaculty($query->get())->map(function (array $row) {
            return [
                'id' => $row['id'],
                'name' => $row['name'],
                'initials' => $this->faculty->initials($row['name']),
                'college' => $row['college'],
                'college_name' => $row['college_name'],
                'position' => $row['position'],
                'status' => $row['status'],
                'expertise' => $row['expertise'],

                'rendered_hours' => $row['rendered_hours'],
                'pending_hours' => $row['pending_hours'],
                'projects' => $row['projects_involved'],
                'projects_led' => $row['projects_led'],
                'activities' => $row['activities_handled'],
                'proposals_submitted' => $row['proposals_submitted'],
                'proposals_approved' => $row['proposals_approved'],

                // The prototype's engagement score. Calculated from the same
                // keys the row prints, so the order is always explainable.
                'engagement' => $row['rendered_hours'] + $row['projects_involved'] * self::INVOLVEMENT_WEIGHT,

                // Deliberately absent: any attainment percentage. There is no
                // per-professor target (D-R9) — a `pct` here would be invented.
            ];
        });

        $rows = $rows->sortByDesc('engagement')->values();

        return $limit === null ? $rows : $rows->take($limit)->values();
    }

    /* ------------------------------------------------------------------ */
    /* Colleges */
    /* ------------------------------------------------------------------ */

    /**
     * Most active colleges, ranked by training hours rendered.
     *
     * A college has no target of its own either (§2.2B: targets exist at
     * University and Project level only), so this is a contribution ranking —
     * `hours_pct` is intentionally NULL on every row.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function colleges(?int $limit = null, ?int $year = null): Collection
    {
        $projects = $this->projects(null, null, $year)->groupBy('college');

        return College::ordered()
            ->get()
            ->map(function (College $college) use ($projects) {
                $mine = $projects->get($college->code, collect());

                $facultyCount = Faculty::where('college_id', $college->id)->count();
                $activeFaculty = Faculty::where('college_id', $college->id)
                    ->where(fn ($q) => $q->where('status', 'active')->orWhereNull('status'))
                    ->count();

                return [
                    'id' => $college->id,
                    'code' => $college->code,
                    'name' => $college->name,
                    'color' => self::collegeColor($college->code),
                    'projects' => $mine->count(),
                    'training_hours' => (float) $mine->sum('training_hours'),
                    'trainees' => (int) $mine->sum('trainees'),
                    'trainors' => (int) $mine->sum('trainors'),
                    'activities' => (int) $mine->sum('activities'),
                    'budget_utilized' => (float) $mine->sum('budget_utilized'),
                    'faculty' => $facultyCount,
                    'active_faculty' => $activeFaculty,

                    // No college-level target exists, so no attainment is shown.
                    'hours_pct' => null,

                    // Ranked by hours delivered; ties broken by reach so the
                    // order is stable rather than dependent on query order.
                    'engagement' => (float) $mine->sum('training_hours') * 1000 + (int) $mine->sum('trainees'),
                ];
            })
            ->sortByDesc('engagement')
            ->values()
            ->when($limit !== null, fn (Collection $c) => $c->take($limit)->values());
    }

    /* ------------------------------------------------------------------ */
    /* Filter option sources */
    /* ------------------------------------------------------------------ */

    /**
     * The filter dropdowns, read from real rows — never a hardcoded list, so a
     * new college or year appears in the filters automatically.
     *
     * The years are pulled in PHP rather than with `YEAR()`: that function is
     * MySQL-only and the test suite runs on SQLite, where it does not exist.
     * `DISTINCT YEAR(x)` would therefore pass locally and fail the whole suite.
     *
     * @return array<string, Collection<int, mixed>>
     */
    public function filterOptions(): array
    {
        return [
            'colleges' => College::ordered()->get(['id', 'code', 'name']),
            'years' => ExtensionProject::query()
                ->whereNotNull('planned_start_date')
                ->pluck('planned_start_date')
                ->map(fn ($d) => (int) Carbon::parse($d)->year)
                ->unique()
                ->sortDesc()
                ->values(),
            'statuses' => collect(config('smartcemes.statuses.program'))->values(),
        ];
    }
}
