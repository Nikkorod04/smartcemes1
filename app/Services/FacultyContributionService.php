<?php

namespace App\Services;

use App\Models\ActivityProposal;
use App\Models\College;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\RenderedHours;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Faculty contribution metrics (revision §6 / D-R9 / Phase R3).
 *
 * ONE source of truth for the Faculty Engagement board, the leaderboard, the
 * faculty profile drawer and the Faculty Directory's derived columns. The
 * prototype computed these inline in JS; here they are computed once, from the
 * database, so the board and the detail page can never disagree.
 *
 * WHAT THIS SERVICE DOES NOT DO YET — and why that is honest:
 * "Training contribution" (§6: training hours DELIVERED, sessions, trainees) is
 * R4's deliverable, because it needs `TrainingHoursService`
 * (`trainors × trainees × days`) and the `activities.no_of_days` /
 * `participants` columns that R4 adds. Until R4 lands, this service reports the
 * metrics that ARE computable today — rendered hours, projects led/involved,
 * proposals — and returns NULL (not zero) for delivered training hours, so the
 * UI can say "not yet measurable" rather than print a fabricated 0.
 *
 * Deliberate distinction the UI must preserve (§9 risk register):
 *   RENDERED HOURS   = service credit a faculty member claims for taking part.
 *   TRAINING HOURS   = hours of training the faculty member DELIVERED.
 * They are different numbers and must never be labelled the same way.
 */
class FacultyContributionService
{
    /**
     * R3c: training DELIVERY is delegated here so `trainors x trainees x days`
     * has exactly one implementation. Resolved from the container, so callers
     * keep constructing this service with no arguments.
     */
    public function __construct(
        protected TrainingHoursService $trainingHours,
    ) {}

    /**
     * Build the contribution row for a set of faculty members.
     *
     * @param  Collection<int, Faculty>  $faculty
     * @return Collection<int, array<string, mixed>>
     */
    public function forFaculty(Collection $faculty): Collection
    {
        $ids = $faculty->pluck('id')->all();

        // R3c: training DELIVERY (§6 "Training contribution"). Delegated to
        // TrainingHoursService — the one implementation of `trainors x trainees
        // x days` — so the profile, the board and the project hub can never
        // disagree about the same activity. NULL when the model is not live.
        //
        // KEYED BY FACULTY ID. This contract is load-bearing and easy to lose:
        // `$training->get($member->id)` does not throw when the key is absent or
        // when the collection is keyed sequentially — it silently returns a
        // DIFFERENT faculty member's figures, because the ids overlap the
        // offsets. `assertTrainingKeyedByFacultyId()` pins the shape, so a
        // regression fails loudly instead of misattributing delivery.
        $training = $this->trainingHours->forFaculty($faculty);
        $this->assertTrainingKeyedByFacultyId($training, $ids);

        // ---- Projects led / involved -------------------------------------
        // One query each, grouped in PHP, rather than N queries per row.
        $ledCounts = ExtensionProject::query()
            ->whereIn('program_lead_id', $ids)
            ->selectRaw('program_lead_id, COUNT(*) as aggregate')
            ->groupBy('program_lead_id')
            ->pluck('aggregate', 'program_lead_id');

        // Projects a faculty member is "involved" in = projects they lead PLUS
        // projects whose activities they are assigned to (the activity_faculty
        // pivot). The prototype's `projectCount` means exactly this.
        $viaActivities = DB::table('activity_faculty')
            ->join('activities', 'activities.id', '=', 'activity_faculty.activity_id')
            ->whereIn('activity_faculty.faculty_id', $ids)
            ->whereNull('activities.deleted_at')
            ->whereNotNull('activities.extension_project_id')
            ->select('activity_faculty.faculty_id', 'activities.extension_project_id')
            ->distinct()
            ->get()
            ->groupBy('faculty_id')
            ->map(fn ($rows) => $rows->pluck('extension_project_id')->unique()->values());

        $involvedCounts = collect($ids)->mapWithKeys(function ($id) use ($viaActivities) {
            $viaActivitiesIds = $viaActivities->get($id, collect());
            $ledProjectIds = ExtensionProject::where('program_lead_id', $id)->pluck('id');

            return [$id => $viaActivitiesIds->merge($ledProjectIds)->unique()->count()];
        });

        // ---- Activities handled ------------------------------------------
        $activityCounts = DB::table('activity_faculty')
            ->join('activities', 'activities.id', '=', 'activity_faculty.activity_id')
            ->whereIn('activity_faculty.faculty_id', $ids)
            ->whereNull('activities.deleted_at')
            ->selectRaw('activity_faculty.faculty_id, COUNT(*) as aggregate')
            ->groupBy('activity_faculty.faculty_id')
            ->pluck('aggregate', 'activity_faculty.faculty_id');

        // ---- Rendered hours by status ------------------------------------
        $hoursRows = RenderedHours::query()
            ->whereIn('faculty_id', $ids)
            ->selectRaw('faculty_id, status, SUM(hours) as total')
            ->groupBy('faculty_id', 'status')
            ->get()
            ->groupBy('faculty_id');

        // ---- Proposals ----------------------------------------------------
        $proposalRows = ActivityProposal::query()
            ->whereIn('faculty_id', $ids)
            ->selectRaw('faculty_id, status, COUNT(*) as aggregate')
            ->groupBy('faculty_id', 'status')
            ->get()
            ->groupBy('faculty_id');

        return $faculty->map(function (Faculty $member) use (
            $ledCounts, $involvedCounts, $activityCounts, $hoursRows, $proposalRows, $training
        ) {
            $hours = $hoursRows->get($member->id, collect())->pluck('total', 'status');
            $proposalCounts = $proposalRows->get($member->id, collect())->pluck('aggregate', 'status');

            $approvedHours = (float) ($hours[RenderedHours::STATUS_APPROVED] ?? 0);
            $pendingHours = (float) ($hours[RenderedHours::STATUS_PENDING] ?? 0);
            $rejectedHours = (float) ($hours[RenderedHours::STATUS_REJECTED] ?? 0);

            $proposalsSubmitted = (int) $proposalCounts->sum();
            $proposalsApproved = (int) ($proposalCounts[ActivityProposal::STATUS_APPROVED] ?? 0);

            $delivery = $training->get($member->id) ?? [
                'training_hours_delivered' => null,
                'training_sessions' => null,
                'trainees_reached' => null,
                'training_days' => null,
                'training_activities' => [],
                'training_measurable' => $this->trainingIsMeasurable(),
                'training_sources' => [],
            ];

            return [
                'id' => $member->id,
                'name' => $member->user?->name ?? '—',
                'employee_id' => $member->employee_id,
                'college' => $member->college?->code,
                'college_name' => $member->college?->name,
                'department' => $member->department,
                'position' => $member->position,
                'specialization' => $member->specialization,
                'status' => $member->status ?? 'active',
                'expertise' => $member->expertise->pluck('area')->all(),

                // Contribution metrics that ARE computable today.
                'rendered_hours' => $approvedHours,
                'pending_hours' => $pendingHours,
                'rejected_hours' => $rejectedHours,
                'projects_led' => (int) ($ledCounts[$member->id] ?? 0),
                'projects_involved' => (int) ($involvedCounts[$member->id] ?? 0),
                'activities_handled' => (int) ($activityCounts[$member->id] ?? 0),

                // Proposals
                'proposals_submitted' => $proposalsSubmitted,
                'proposals_approved' => $proposalsApproved,
                'proposal_approval_rate' => $proposalsSubmitted > 0
                    ? round($proposalsApproved / $proposalsSubmitted * 100)
                    : null,

                // R3c: training hours DELIVERED, from TrainingHoursService.
                // NULL (not 0) whenever the model is not live or they have no
                // activities — see the class docblock.
                'training_hours_delivered' => $delivery['training_hours_delivered'],
                'training_sessions' => $delivery['training_sessions'],
                'trainees_reached' => $delivery['trainees_reached'],
                'training_days' => $delivery['training_days'],
                'training_activities' => $delivery['training_activities'],
                'training_sources' => $delivery['training_sources'],
                'training_measurable' => $delivery['training_measurable'],
            ];
        });
    }

    /**
     * Fail loudly if the training roll-up is not keyed by faculty id.
     *
     * WHY THIS EXISTS
     * ---------------
     * `$training->get($member->id)` is a lookup that cannot fail safely. Given a
     * sequentially-keyed collection — the natural shape to reach for, and what
     * `Collection::map()` returns — `get(1)`, `get(2)`, `get(3)` all resolve, and
     * resolve to the WRONG people, because faculty ids start at 1 and array
     * offsets start at 0. The symptom is not an exception: it is one faculty
     * member's profile showing another's delivered hours.
     *
     * A missing key degrades to the honest all-NULL default; a wrong key invents
     * a number. Only the first is acceptable, so the shape is asserted.
     *
     * Note the direction: this checks that every key in the roll-up is an id we
     * asked for. It deliberately does NOT require a key for every id — a faculty
     * member with no activities legitimately has no row, and must fall through to
     * the NULL default rather than being treated as an error.
     *
     * @param  Collection<int|string, array<string, mixed>>  $training
     * @param  array<int, int>  $ids
     */
    private function assertTrainingKeyedByFacultyId(Collection $training, array $ids): void
    {
        if ($training->isEmpty()) {
            return;
        }

        $stray = array_diff($training->keys()->all(), $ids);

        if ($stray !== []) {
            throw new \LogicException(
                'TrainingHoursService::forFaculty() returned keys ['.implode(', ', $stray).'] that are not '
                .'faculty ids from the requested set. The `->get($member->id)` lookup below would silently '
                .'attribute one faculty member\'s delivered hours to another.'
            );
        }
    }

    /**
     * The board's metric switch (prototype: hours / projects / leads).
     *
     * Kept here so the blade and the Livewire component share the definitions
     * and the ordering rule, exactly like the prototype's METRICS object.
     *
     * @return array<string, array<string, mixed>>
     */
    public function metricDefinitions(): array
    {
        return [
            // `unit` labels the leaderboard row's value; `unit_one` is the
            // singular form. Both were previously written as bare
            // abbreviations ('hrs' / 'proj' / 'lead') and read by NOTHING —
            // the row printed a naked number and the unit only ever appeared
            // in the caption of the OTHER two metrics (owner request
            // 2026-10-07). `unit` is now the plural/default wording.
            //
            // Hours keep a fixed label: "hrs rendered" is the house term used
            // by the hub tile, the caption and the chart tooltip, so the row
            // must not be the one place that says "1 hr rendered".
            'hours' => [
                'label' => 'Training hours rendered',
                'short' => 'Hours rendered',
                'sub' => 'Total hours each faculty member has rendered this academic year',
                'key' => 'rendered_hours',
                'unit' => 'hrs rendered',
            ],
            'projects' => [
                'label' => 'Project involvement',
                'short' => 'Projects',
                'sub' => 'Number of extension projects led or co-led this academic year',
                'key' => 'projects_involved',
                'unit' => 'Projects',
                'unit_one' => 'Project',
            ],
            'leads' => [
                'label' => 'Projects led',
                'short' => 'Leads',
                'sub' => 'Projects where the faculty member carries the lead role',
                'key' => 'projects_led',
                'unit' => 'Projects led',
                'unit_one' => 'Project led',
            ],
        ];
    }

    /**
     * Rank rows by the chosen metric, descending (prototype `ranked()`).
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    public function rank(Collection $rows, string $metric): Collection
    {
        $definitions = $this->metricDefinitions();
        $key = $definitions[$metric]['key'] ?? 'rendered_hours';

        return $rows->sortByDesc(fn (array $row) => $row[$key] ?? 0)->values();
    }

    /**
     * The "load by college" split (prototype `drawSplit()`), always in
     * CAS → COE → CME order so the legend is stable.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function collegeSplit(Collection $rows, string $metric = 'hours'): array
    {
        $definitions = $this->metricDefinitions();
        $key = $definitions[$metric]['key'] ?? 'rendered_hours';

        $total = (float) $rows->sum(fn (array $row) => $row[$key] ?? 0);
        $order = College::ordered()->pluck('code')->all();

        return collect($order)
            ->map(function (string $code) use ($rows, $key, $total) {
                $subset = $rows->filter(fn (array $row) => ($row['college'] ?? null) === $code);
                $value = (float) $subset->sum(fn (array $row) => $row[$key] ?? 0);

                return [
                    'code' => $code,
                    'value' => $value,
                    'share' => $total > 0 ? round($value / $total * 100) : 0,
                    'count' => $subset->count(),
                ];
            })
            ->all();
    }

    /**
     * Is training-hours delivery computable yet?
     *
     * True only once R4 has added `activities.no_of_days` — the cheapest
     * reliable probe for "the training-hours model has landed". Until then the
     * board hides/suppresses the training-delivery figures instead of inventing
     * them.
     */
    public function trainingIsMeasurable(): bool
    {
        return Schema::hasColumn('activities', 'no_of_days')
            && class_exists(TrainingHoursService::class);
    }

    /**
     * Aggregate notes for the board's "What this says" strip (prototype
     * `drawInsights()`), returned as structured data so the blade owns the
     * markup.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function insights(Collection $rows, string $metric, ?string $collegeScope = null): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        $definitions = $this->metricDefinitions();
        $definition = $definitions[$metric] ?? $definitions['hours'];
        $key = $definition['key'];

        $ranked = $this->rank($rows, $metric);
        $top = $ranked->first();

        $totalHours = (float) $rows->sum('rendered_hours');
        $withHours = $rows->filter(fn (array $row) => ($row['rendered_hours'] ?? 0) > 0)->count();
        $unassigned = $rows->filter(fn (array $row) => ($row['projects_involved'] ?? 0) === 0);
        $other = $rows->filter(fn (array $row) => ($row['id'] ?? null) !== ($top['id'] ?? null))
            ->sortByDesc('projects_led')
            ->first();

        $who = $collegeScope ?: 'The roster';
        $notes = [];

        if ($top) {
            $notes[] = [
                'icon' => 'sparkles',
                'tone' => 'gold',
                'text' => sprintf(
                    '%s leads the board on %s at %s, across %d project%s.',
                    $this->lastName($top['name']),
                    strtolower($definition['label']),
                    $this->formatMetric($top[$key] ?? 0, $metric),
                    $top['projects_involved'] ?? 0,
                    ($top['projects_involved'] ?? 0) === 1 ? '' : 's'
                ),
            ];
        }

        $notes[] = [
            'icon' => 'chart',
            'tone' => 'lnu',
            'text' => sprintf(
                '%s has rendered %s training hours — a mean of %s hrs per faculty member, carried by %d of %d.',
                $who,
                $this->formatHours($totalHours),
                (string) round($totalHours / max($rows->count(), 1)),
                $withHours,
                $rows->count()
            ),
        ];

        if ($unassigned->isNotEmpty()) {
            $notes[] = [
                'icon' => 'users',
                'tone' => 'gray',
                'text' => sprintf(
                    '%d faculty %s no project assignment yet%s.',
                    $unassigned->count(),
                    $unassigned->count() === 1 ? 'has' : 'have',
                    $unassigned->count() === 1 ? ' — '.$this->lastName($unassigned->first()['name']) : ''
                ),
            ];
        }

        if ($other && ($other['projects_led'] ?? 0) > 0) {
            $notes[] = [
                'icon' => 'clipboard',
                'tone' => 'emerald',
                'text' => sprintf(
                    '%s also carries the most lead roles (%d).',
                    $this->lastName($other['name']),
                    $other['projects_led']
                ),
            ];
        }

        return $notes;
    }

    /**
     * The sort position for a faculty position label (config ladder).
     * Mirrors the prototype's `positionRank`.
     */
    public function positionRank(?string $position): ?int
    {
        if ($position === null) {
            return null;
        }

        foreach (config('smartcemes.position_ladder', []) as $rung) {
            if ($rung['label'] === $position) {
                return $rung['rank'];
            }
        }

        return null;
    }

    /**
     * "Bautista J." — the compact chart-axis label from the prototype.
     */
    public function shortName(?string $name): string
    {
        $name = preg_replace('/^(Prof\.|Dr\.)\s+/', '', (string) $name);
        $parts = array_values(array_filter(explode(' ', $name)));

        if (count($parts) < 2) {
            return $parts[0] ?? '—';
        }

        return end($parts).' '.strtoupper(substr($parts[0], 0, 1)).'.';
    }

    /**
     * "R. Bautista" style surname-first label used by the insight notes.
     */
    public function lastName(?string $name): string
    {
        $name = preg_replace('/^(Prof\.|Dr\.)\s+/', '', (string) $name);
        $parts = array_values(array_filter(explode(' ', $name)));

        return end($parts) ?: '—';
    }

    /**
     * Initials for the avatar chip, mirroring the prototype.
     */
    public function initials(?string $name): string
    {
        $name = preg_replace('/^(Prof\.|Dr\.)\s+/', '', (string) $name);
        $parts = array_values(array_filter(explode(' ', $name)));

        return strtoupper(implode('', array_map(
            fn (string $word) => substr($word, 0, 1),
            array_slice($parts, 0, 2)
        )));
    }

    private function formatHours(float $hours): string
    {
        return rtrim(rtrim(number_format($hours, 1), '0'), '.');
    }

    /**
     * Public hours formatter for blades that hold a computed row (not the model)
     * and therefore cannot call the private helper.
     */
    public function formatHoursValue(float|int|string|null $hours): string
    {
        return $this->formatHours((float) $hours);
    }

    private function formatMetric(float|int $value, string $metric): string
    {
        if ($metric === 'hours') {
            return $this->formatHours((float) $value).' hrs';
        }

        $unit = $metric === 'leads' ? 'lead role' : 'project';

        return $value.' '.$unit.($value == 1 ? '' : 's');
    }
}
