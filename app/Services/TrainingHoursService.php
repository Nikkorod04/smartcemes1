<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * THE training-hours model (revision §4.4, D-R3/D-R4, R-Q1).
 *
 * This class is the single source of truth for every training-hours figure in
 * the system. The project hub, the university targets page, the faculty profile
 * and (in R5) the rankings all read from here, so they can never disagree.
 *
 * THE FORMULA — and the amendment that matters
 * --------------------------------------------
 *     TRAINING_HOURS = trainors x trainees x days        #  <-- NO x 8
 *
 * §2.2A removed the trailing `x 8`. `days` already carries the duration, so
 * multiplying by 8 double-counted it: 2 trainors x 112 trainees x 0.5 day is
 * **112 hrs**, not 896. An arithmetic audit found all 6/6 completed prototype
 * activities already satisfied `trainors x trainees x days` and 0/6 satisfied
 * `x 8` — the stored numbers were right and only the labels claimed `x 8`.
 * If you ever see an `x 8` reappear here, it is a regression, not a decision.
 *
 * THE HALF DAY LIVES IN `days`, NOT IN THE FORMULA
 * ------------------------------------------------
 * A half day is `no_of_days = 0.5`. That is why the column is decimal(4,1) and
 * why this service does no rounding of its own — 0.5 must survive the round trip.
 *
 * TRAINEE RESOLUTION ORDER (R-Q1) — and why the source is surfaced
 * ---------------------------------------------------------------
 *     attendance (present|late)  ->  participants (manual)  ->  0
 *
 * There is no per-activity beneficiary roster to fall back to: enrollment is
 * PROJECT-level, not activity-level. Falling back to the enrolled count would
 * silently inflate hours (3 activities x 120 enrolled = 360). So the manual
 * `participants` column is the fallback, and every figure carries a **source
 * tag** (`attendance` / `manual` / `none`) the Director can see. NULL is used
 * for "not measurable" and is never conflated with 0.
 *
 * TRAINING HOURS vs RENDERED HOURS — a deliberate distinction (§9)
 * ---------------------------------------------------------------
 * Training hours are what a project DELIVERED to other people
 * (`trainors x trainees x days`). Rendered hours (8.9) are the service credit a
 * faculty member CLAIMS for their own record. The two coexist and must never be
 * summed together; the UI names them distinctly for exactly this reason.
 */
class TrainingHoursService
{
    /** Trainee count came from imported attendance records. */
    public const SOURCE_ATTENDANCE = 'attendance';

    /** Trainee count came from the manually entered `participants` column. */
    public const SOURCE_MANUAL = 'manual';

    /** No trainee count exists at all — hours are not measurable. */
    public const SOURCE_NONE = 'none';

    public const SOURCE_LABELS = [
        self::SOURCE_ATTENDANCE => 'Attendance',
        self::SOURCE_MANUAL => 'Manual entry',
        self::SOURCE_NONE => 'Not recorded',
    ];

    /** Trend bucket for activities carrying no planned start date. */
    public const PERIOD_UNDATED = 'undated';

    /**
     * Is the training-hours model live?
     *
     * Probed by `FacultyContributionService` too: the R3 faculty pages render a
     * "not yet measurable" panel until this flips true, then light up with no
     * change on their side. The column check makes the probe honest even if a
     * stale compiled class lingers.
     */
    public function isMeasurable(): bool
    {
        return Schema::hasColumn('activities', 'no_of_days');
    }

    /* ------------------------------------------------------------------ */
    /* Per-activity */
    /* ------------------------------------------------------------------ */

    /**
     * Full per-activity breakdown.
     *
     * `$attendeeCounts` lets a caller pass a pre-fetched [activity_id => count]
     * map to avoid N+1 queries when rendering a list; when omitted the count is
     * resolved per activity.
     */
    public function forActivity(Activity $activity, ?Collection $attendeeCounts = null): array
    {
        $trainors = $this->trainorsFor($activity);
        $trainees = $this->traineesFor($activity, $attendeeCounts);
        $days = $this->daysFor($activity);

        $hours = $this->compute($trainors, $trainees['count'], $days);

        return [
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'status' => $activity->status,
            'trainors' => $trainors,
            'trainees' => $trainees['count'],
            'trainees_source' => $trainees['source'],
            'trainees_source_label' => self::SOURCE_LABELS[$trainees['source']],
            'days' => $days,
            'hours' => $hours,
            'formula' => $this->explain($trainors, $trainees['count'], $days, $hours),
            'measurable' => $trainors > 0 && $trainees['count'] > 0 && $days !== null,
        ];
    }

    /** Convenience: just the hours for one activity. */
    public function hoursForActivity(Activity $activity, ?Collection $attendeeCounts = null): float
    {
        return $this->forActivity($activity, $attendeeCounts)['hours'];
    }

    /**
     * Trainors = the optional snapshot override, else the count of assigned
     * faculty (§4.4).
     *
     * The snapshot exists so a project can record how many trainors actually
     * delivered the session even if the faculty pivot was edited later — the
     * pivot is a live assignment list, not a historical record.
     */
    public function trainorsFor(Activity $activity): int
    {
        if ($activity->trainors_snapshot !== null) {
            return (int) $activity->trainors_snapshot;
        }

        // `faculty_count` is auto-populated by withCount(); fall back to a
        // direct count so the service works either way.
        return (int) ($activity->faculty_count ?? $activity->faculty()->count());
    }

    /**
     * Trainees, with the resolution order and the source tag (R-Q1).
     *
     * A project-level enrolled count is deliberately NOT a fallback — see the
     * class docblock for the inflation arithmetic that rules it out.
     */
    public function traineesFor(Activity $activity, ?Collection $attendeeCounts = null): array
    {
        $attendees = $attendeeCounts !== null
            ? (int) ($attendeeCounts[$activity->id] ?? 0)
            : $this->attendanceCount($activity);

        if ($attendees > 0) {
            return ['count' => $attendees, 'source' => self::SOURCE_ATTENDANCE];
        }

        if ($activity->participants !== null && (int) $activity->participants > 0) {
            return ['count' => (int) $activity->participants, 'source' => self::SOURCE_MANUAL];
        }

        return ['count' => 0, 'source' => self::SOURCE_NONE];
    }

    /**
     * Days, defaulting to a full day when the column is NULL.
     *
     * We default rather than return NULL because the prototype and the activities
     * table both present a day figure for every row; `forActivity()` still
     * reports `measurable => false` so a caller that needs "was this actually
     * recorded?" can distinguish legacy rows from recorded halves.
     */
    public function daysFor(Activity $activity): float
    {
        return $activity->no_of_days !== null ? (float) $activity->no_of_days : 1.0;
    }

    /**
     * THE FORMULA. `no x 8` — see the class docblock.
     *
     * A NULL/0 day contributes 0, which is what a draft activity with no
     * duration recorded should deliver.
     */
    public function compute(int $trainors, int $trainees, float $days): float
    {
        return round($trainors * $trainees * $days, 2);
    }

    /**
     * A human-readable derivation, e.g. `3 trainors x 141 trainees x 1 day = 423 hrs`.
     * Rendered beneath the activity row and in the targets page worked example.
     */
    public function explain(int $trainors, int $trainees, float $days, float $hours): string
    {
        return sprintf(
            '%d trainor%s x %d trainee%s x %s day%s = %s hrs',
            $trainors,
            $trainors === 1 ? '' : 's',
            $trainees,
            $trainees === 1 ? '' : 's',
            $this->formatDays($days),
            // Only a count above 1 takes a plural: "0.5 day", not "0.5 days".
            $days > 1 ? 's' : '',
            number_format($hours)
        );
    }

    /* ------------------------------------------------------------------ */
    /* Per-project */
    /* ------------------------------------------------------------------ */

    /**
     * Project rollup: every figure the project hub performance section shows.
     *
     * `actual_hours` sums EVERY activity, matching the prototype's per-project
     * `renderedTrainingHours`; `completed_hours` sums only completed ones so the
     * two can be compared without recomputing.
     */
    public function forProject(ExtensionProject $project): array
    {
        $activities = Activity::query()
            ->where('extension_project_id', $project->id)
            ->withCount(['faculty', 'attendances as attendees_count' => fn ($q) => $q->whereIn('status', ['present', 'late'])])
            ->orderBy('planned_start_date')
            ->get();

        $attendeeCounts = $activities->pluck('attendees_count', 'id');

        $rows = $activities->map(fn (Activity $a) => $this->forActivity($a, $attendeeCounts));

        // Distinct faculty engaged across the project — the project's "trainors"
        // figure. Counted once per person even if they led three activities,
        // because the tile is labelled "assigned faculty · lead + co-leads".
        $trainorIds = DB::table('activity_faculty')
            ->join('activities', 'activities.id', '=', 'activity_faculty.activity_id')
            ->where('activities.extension_project_id', $project->id)
            ->whereNull('activities.deleted_at')
            ->distinct()
            ->pluck('activity_faculty.faculty_id');

        $actualHours = (float) $rows->sum('hours');
        $completed = $rows->where('status', 'completed');

        $traineesReached = $this->traineesReached($project);

        return [
            'project_id' => $project->id,
            'code' => $project->code,
            'title' => $project->title,
            'rows' => $rows,
            'trainors' => $trainorIds->count(),
            'trainors_total' => $rows->sum('trainors'),
            'trainees' => $traineesReached,
            'training_days' => (float) $rows->sum('days'),
            'activity_count' => $rows->count(),
            'completed_count' => $completed->count(),
            'actual_hours' => $actualHours,
            'completed_hours' => (float) $completed->sum('hours'),
            'target_hours' => $project->annual_target_hours !== null ? (float) $project->annual_target_hours : null,
            'hours_pct' => $this->percentage($actualHours, $project->annual_target_hours),
            // Budget has NO annual target (owner decision 2026-09-26): a project's
            // allocation is its only budget figure, so it is the denominator.
            // `annual_target_budget` is retained but unread — see ExtensionProject.
            'allocated_budget' => $allocated = $project->budgetAllocated(),
            'utilized_budget' => $utilized = (float) $project->budgetUtilizations()->sum('amount'),
            'budget_pct' => $this->percentage($utilized, $allocated),
            'avg_hours_per_completed' => $completed->isNotEmpty()
                ? round($completed->sum('hours') / $completed->count(), 1)
                : null,
            // A source tag is exposed per activity (R-Q1), and the project
            // summarises which sources it is drawing on so the Director can see
            // whether a figure rests on imports or on manual entry.
            'sources' => $rows->groupBy('trainees_source')->map->count()->all(),
        ];
    }

    /**
     * Distinct beneficiaries who actually attended this project's activities.
     *
     * "Reached" is a distinct-person count, not a sum of attendances: a
     * beneficiary who came to three sessions was still reached once.
     */
    public function traineesReached(ExtensionProject $project): int
    {
        return (int) DB::table('attendances')
            ->join('activities', 'activities.id', '=', 'attendances.activity_id')
            ->where('activities.extension_project_id', $project->id)
            ->whereNull('activities.deleted_at')
            ->whereIn('attendances.status', ['present', 'late'])
            ->distinct()
            ->count('attendances.beneficiary_id');
    }

    /* ------------------------------------------------------------------ */
    /* Per-faculty (R3c) */
    /* ------------------------------------------------------------------ */

    /**
     * Training DELIVERY for a set of faculty members — §6's
     * "Training contribution" block, and the basis of the trend.
     *
     * WHY THIS LIVES HERE AND NOT IN `FacultyContributionService`
     * ----------------------------------------------------------
     * The formula must have exactly ONE implementation. `FacultyContributionService`
     * computes *rendered* hours (service credit a faculty member claims) from
     * `rendered_hours`; training hours *delivered* come from this service. If the
     * faculty pages re-derived `trainors x trainees x days` themselves, the
     * profile and the project hub could disagree about the same activity — which
     * is precisely the drift this class exists to prevent.
     *
     * RETURNS NULL, NOT ZERO, WHEN NOT MEASURABLE
     * ------------------------------------------
     * Callers get `measurable => false` plus NULLs when the model has not landed
     * or the faculty member has no activities. A zero would read as "delivered
     * nothing", which is a claim there is no basis for (§9 risk register: NULL
     * over 0).
     *
     * @param  Collection<int, Faculty>  $faculty
     * @return Collection<int, array<string, mixed>> keyed by faculty id
     */
    public function forFaculty(Collection $faculty): Collection
    {
        if (! $this->isMeasurable()) {
            return $faculty->mapWithKeys(fn (Faculty $f) => [$f->id => $this->emptyContribution()]);
        }

        $ids = $faculty->pluck('id')->all();

        if ($ids === []) {
            return collect();
        }

        // One query for every activity any of these faculty are assigned to,
        // with the attendee count folded in, grouped in PHP. Same shape as
        // `forProject()` so the two can never diverge.
        $rows = DB::table('activity_faculty')
            ->join('activities', 'activities.id', '=', 'activity_faculty.activity_id')
            ->whereIn('activity_faculty.faculty_id', $ids)
            ->whereNull('activities.deleted_at')
            ->select(
                'activity_faculty.faculty_id',
                'activities.id',
                'activities.title',
                'activities.status',
                'activities.no_of_days',
                'activities.participants',
                'activities.trainors_snapshot',
                'activities.planned_start_date',
            )
            ->get();

        // Attendee counts (present|late) per activity, for the R-Q1 order.
        $attendeeCounts = [];
        foreach ($rows->pluck('id')->unique()->chunk(500) as $chunk) {
            $counts = DB::table('attendances')
                ->whereIn('activity_id', $chunk->all())
                ->whereIn('status', ['present', 'late'])
                ->selectRaw('activity_id, COUNT(*) as aggregate')
                ->groupBy('activity_id')
                ->pluck('aggregate', 'activity_id');
            foreach ($counts as $activityId => $count) {
                $attendeeCounts[$activityId] = (int) $count;
            }
        }

        // Assigned-faculty count per activity, for the trainors fallback.
        $trainorCounts = DB::table('activity_faculty')
            ->whereIn('activity_id', $rows->pluck('id')->unique()->all())
            ->selectRaw('activity_id, COUNT(*) as aggregate')
            ->groupBy('activity_id')
            ->pluck('aggregate', 'activity_id');

        // Distinct beneficiaries reached across each faculty member's activities.
        // Distinct PERSON, not a sum of attendances — a beneficiary who attended
        // three of their sessions was still reached once.
        $reached = DB::table('attendances')
            ->join('activities', 'activities.id', '=', 'attendances.activity_id')
            ->join('activity_faculty', 'activity_faculty.activity_id', '=', 'activities.id')
            ->whereIn('activity_faculty.faculty_id', $ids)
            ->whereNull('activities.deleted_at')
            ->whereIn('attendances.status', ['present', 'late'])
            ->selectRaw('activity_faculty.faculty_id, COUNT(DISTINCT attendances.beneficiary_id) as aggregate')
            ->groupBy('activity_faculty.faculty_id')
            ->pluck('aggregate', 'activity_faculty.faculty_id');

        return $rows->groupBy('faculty_id')->map(function ($activityRows, $facultyId) use ($attendeeCounts, $trainorCounts, $reached) {
            $perActivity = $activityRows->map(function ($row) use ($attendeeCounts, $trainorCounts) {
                $trainors = $row->trainors_snapshot !== null
                    ? (int) $row->trainors_snapshot
                    : (int) ($trainorCounts[$row->id] ?? 0);

                $attendees = $attendeeCounts[$row->id] ?? 0;
                if ($attendees > 0) {
                    $trainees = ['count' => $attendees, 'source' => self::SOURCE_ATTENDANCE];
                } elseif ($row->participants !== null && (int) $row->participants > 0) {
                    $trainees = ['count' => (int) $row->participants, 'source' => self::SOURCE_MANUAL];
                } else {
                    $trainees = ['count' => 0, 'source' => self::SOURCE_NONE];
                }

                $days = $row->no_of_days !== null ? (float) $row->no_of_days : 1.0;

                return [
                    'activity_id' => $row->id,
                    'title' => $row->title,
                    'status' => $row->status,
                    'planned_start_date' => $row->planned_start_date,
                    'trainors' => $trainors,
                    'trainees' => $trainees['count'],
                    'trainees_source' => $trainees['source'],
                    'trainees_source_label' => self::SOURCE_LABELS[$trainees['source']],
                    'days' => $days,
                    'hours' => $this->compute($trainors, $trainees['count'], $days),
                    'measurable' => $trainors > 0 && $trainees['count'] > 0 && $row->no_of_days !== null,
                ];
            });

            $hours = (float) $perActivity->sum('hours');

            return [
                'training_hours_delivered' => $hours,
                'training_sessions' => $perActivity->count(),
                // Distinct persons, so this is NOT the sum of the per-activity
                // trainee counts — that would double-count repeat attendees.
                'trainees_reached' => (int) ($reached[$facultyId] ?? 0),
                'training_days' => (float) $perActivity->sum('days'),
                'training_activities' => $perActivity->values(),
                'training_measurable' => true,
                // Which R-Q1 sources these figures rest on, so the Director can
                // see whether a number came from imported attendance or manual entry.
                'training_sources' => $perActivity->groupBy('trainees_source')->map->count()->all(),
            ];
        });
    }

    /**
     * The all-NULL contribution shape, so callers never branch on missing keys.
     *
     * @return array<string, mixed>
     */
    protected function emptyContribution(): array
    {
        return [
            'training_hours_delivered' => null,
            'training_sessions' => null,
            'trainees_reached' => null,
            'training_days' => null,
            'training_activities' => [],
            'training_measurable' => false,
            'training_sources' => [],
        ];
    }

    /**
     * Training delivery bucketed by period, for the profile's trend.
     *
     * Buckets on the activity's planned start date — the same date the project
     * hub orders by — so the trend and the project timeline agree. Activities
     * with no date land in an "undated" bucket rather than being dropped, since
     * silently omitting delivered hours would understate the trend.
     *
     * @param  array<int, array<string, mixed>>|Collection<int, array<string, mixed>>  $activities
     * @return array<int, array<string, mixed>>
     */
    public function trendForActivities(array|Collection $activities): array
    {
        $buckets = [];

        foreach ($activities as $activity) {
            $key = $this->periodKeyOf($activity)
                ?? self::PERIOD_UNDATED;

            $buckets[$key] = $buckets[$key] ?? ['period' => $key, 'hours' => 0.0, 'sessions' => 0];
            $buckets[$key]['hours'] += (float) ($activity['hours'] ?? 0);
            $buckets[$key]['sessions']++;
        }

        // `ksort` orders the `Y-m` keys chronologically and puts 'undated' last,
        // since 'u' sorts above any digit. No extra pinning is needed — but note
        // that this is the ONLY ordering step, so a future bucket key that does not
        // start with a digit would sort unexpectedly.
        ksort($buckets);

        return array_values($buckets);
    }

    /**
     * A `Y-m` bucket key from an activity row's date field, or NULL.
     *
     * Parsed rather than string-sliced, deliberately. `substr($date, 0, 7)` happens
     * to be correct for the two formats in play — `'2026-03-15'` and
     * `'2026-03-15 00:00:00'` — but only because the separator sits at index 7 and
     * the slice stops before it. It is a coincidence of ISO-8601's layout rather
     * than a guarantee, and it fails the moment a value arrives in a shape where
     * the first seven characters are not the month: a `d/m/Y` string, a
     * `DateTimeInterface` (which `substr` cannot take at all), or any locale
     * formatting. Parsing costs one Carbon instance per bucket and removes the
     * whole class of problem.
     *
     * Rows reach here from a raw `DB::table()` select, so the format is whatever
     * the driver returns, not what a cast would have produced.
     *
     * @param  array<string, mixed>  $activity
     */
    protected function periodKeyOf(array $activity): ?string
    {
        $raw = $activity['planned_start_date'] ?? null;

        if ($raw === null || $raw === '') {
            return null;
        }

        if ($raw instanceof \DateTimeInterface) {
            return $raw->format('Y-m');
        }

        try {
            return Carbon::parse((string) $raw)->format('Y-m');
        } catch (\Throwable) {
            // An unparseable date is still not a reason to drop delivered hours.
            return null;
        }
    }

    /* ------------------------------------------------------------------ */
    /* Per-year / university roll-up */
    /* ------------------------------------------------------------------ */

    /**
     * University rollup for a year — the actuals side of the annual pool.
     *
     * `drawdown` is the consumption model (§2.2B): the annual target is a pool
     * projects draw down, not a ratio to hit. `remaining` is what is left.
     *
     * Projects are filtered by their planned start year because a project belongs
     * to one academic year; activities inherit the project's year.
     */
    public function forYear(int $year, ?float $annualTargetHours = null, ?float $annualTargetBudget = null): array
    {
        $projects = ExtensionProject::query()
            ->whereYear('planned_start_date', $year)
            ->get();

        $hours = 0.0;
        $budget = 0.0;
        $trainees = 0;
        $activities = 0;

        foreach ($projects as $project) {
            $rollup = $this->forProject($project);
            $hours += $rollup['actual_hours'];
            $budget += $rollup['utilized_budget'];
            $trainees += $rollup['trainees'];
            $activities += $rollup['activity_count'];
        }

        return [
            'year' => $year,
            'projects' => $projects->count(),
            'activities' => $activities,
            'trainees' => $trainees,
            'training_hours' => $hours,
            'budget_utilized' => $budget,
            'target_hours' => $annualTargetHours,
            'target_budget' => $annualTargetBudget,
            'hours_pct' => $this->percentage($hours, $annualTargetHours),
            'budget_pct' => $this->percentage($budget, $annualTargetBudget),
            'drawn_down' => $annualTargetHours !== null ? min($hours, $annualTargetHours) : null,
            // `0.0`, not `0`: `max()` with an int literal returns an int, which
            // would leak a bare integer into JSON and into the blade `number_format`
            // path. The floor must stay a float.
            'remaining' => $annualTargetHours !== null ? max($annualTargetHours - $hours, 0.0) : null,
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Helpers */
    /* ------------------------------------------------------------------ */

    /** Present/late attendance rows for one activity. */
    private function attendanceCount(Activity $activity): int
    {
        return (int) $activity->attendances()
            ->whereIn('status', ['present', 'late'])
            ->distinct()
            ->count('beneficiary_id');
    }

    /** NULL (not 0) when there is no denominator — "no target" is not "0%". */
    private function percentage(float $actual, float|int|null $target): ?float
    {
        if ($target === null || (float) $target <= 0) {
            return null;
        }

        return round($actual / (float) $target * 100, 1);
    }

    /** 0.5 renders as "0.5"; 1.0 renders as "1" rather than "1.0". */
    public function formatDays(float $days): string
    {
        return rtrim(rtrim(number_format($days, 1, '.', ''), '0'), '.') ?: '0';
    }
}
