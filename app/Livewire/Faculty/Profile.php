<?php

namespace App\Livewire\Faculty;

use App\Models\ActivityProposal;
use App\Models\ExtensionProject;
use App\Models\Faculty as FacultyModel;
use App\Models\RenderedHours;
use App\Services\FacultyContributionService;
use App\Services\TrainingHoursService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * One faculty member's full extension contribution (revision §5 R3 step 4 /
 * §6 D-R9).
 *
 * This is the page the Director opens from the board or the directory, and the
 * page a faculty member reaches to see their own record. It carries the eight
 * blocks §6 specifies:
 *
 *   Profile & identity · Expertise · Involvement · Training contribution ·
 *   Rendered hours · Proposals · Performance trend · Flags
 *
 * THE TWO TRAINING BLOCKS LANDED IN R3c.
 * "Training contribution" and "Performance trend" depend on
 * `TrainingHoursService`. R4 built that service and added
 * `activities.no_of_days` / `participants`; R3c then wired those two blocks to
 * the real figures. `FacultyContributionService` delegates the delivery maths to
 * `TrainingHoursService::forFaculty()` so this page and the project hub can
 * never disagree about the same activity, and still reports
 * `training_measurable = false` — with NULLs, never zeros — if the model is ever
 * absent, so the page degrades to an honest "not measurable" panel rather than a
 * fabricated 0.
 *
 * ACCESS: the FacultyPolicy decides. Admin sees anyone; a faculty member sees
 * themselves. `Gate::authorize` runs in mount() AND is re-checked on every
 * sensitive action.
 *
 * SELF-EDIT SCOPE (widened 2026-09-27, owner decision): a faculty member edits
 * their own specialization, department, contact number, address and expertise
 * areas. Employee ID, college, position, status and the login account stay
 * Director-only. This page is also the destination of the faculty sidebar's
 * "My Profile" item, which is why `mount()` takes an OPTIONAL faculty id — see
 * its docblock.
 */
#[Layout('layouts.app')]
class Profile extends Component
{
    public FacultyModel $faculty;

    public bool $showEdit = false;

    /**
     * The self-edit form — what a faculty member maintains about THEMSELVES.
     *
     * WIDENED 2026-09-27 (owner decision). It was contact-only; it now also
     * carries the academic fields and the expertise set.
     *
     *   WRITABLE       specialization · department · phone · address ·
     *                  expertise[]
     *   NOT WRITABLE   employee_id · college_id · position · status, and the
     *                  `users` row (full name, email)
     *
     * The institutional keys are simply ABSENT from this array, and
     * `saveProfile()` writes nothing else — so a posted `editForm.employee_id`
     * has nowhere to land. The rule is enforced server-side, not by hiding
     * inputs; `test_the_self_edit_cannot_touch_institutional_fields` proves it.
     */
    public array $editForm = [
        'specialization' => '',
        'department' => '',
        'phone' => '',
        'address' => '',
        'expertise' => [],
    ];

    /**
     * The `{faculty}` route parameter is OPTIONAL so this one component serves
     * both routes:
     *
     *   faculty.show  `/faculty/{faculty}`  — the Director opening any profile
     *   faculty.me    `/my-profile`         — a faculty member opening their own
     *
     * `faculty.me` carries no id (the nav config resolves route names with no
     * parameters), so it falls back to the authenticated user's own record.
     * `role:faculty` on that route guarantees the row exists; the `firstOrFail`
     * covers a mis-provisioned faculty login — a user with the faculty role but
     * no `faculties` row — which would otherwise fatal on a null property.
     */
    public function mount(?FacultyModel $faculty = null): void
    {
        $faculty ??= auth()->user()->faculty()->firstOrFail();

        Gate::authorize('view', $faculty);

        $this->faculty = $faculty->load(['user', 'college', 'expertise']);
    }

    public function editProfile(): void
    {
        Gate::authorize('updateOwnProfile', $this->faculty);

        $this->editForm = [
            'specialization' => $this->faculty->specialization ?? '',
            'department' => $this->faculty->department ?? '',
            'phone' => $this->faculty->phone ?? '',
            'address' => $this->faculty->address ?? '',
            'expertise' => $this->faculty->expertise->pluck('area')->all(),
        ];
        $this->showEdit = true;
    }

    /**
     * Toggle one expertise area in the self-edit form.
     *
     * The `(key, area)` signature is the shared `x-sc.multi-select` component's
     * contract — its `toggle()` JS calls `$wire.call(method, key, id)`. The key
     * is ignored here (one multi-select on this page), but it MUST be declared:
     * PHP silently ignores surplus arguments, so a one-parameter
     * `toggleExpertise(string $area)` would bind `$area` to the literal key
     * `"profile-expertise"` and toggle a non-existent area instead of failing.
     *
     * Server-side on purpose: Livewire 3.8's `$toggle` is broken for ARRAY
     * properties — its client implementation is `set(name, !get(name))` and
     * ignores the value argument, so `$toggle('editForm.expertise', 'X')`
     * coerces the property to a bool and the next render dies on
     * `in_array(): bool given`. `Faculty\Directory::toggleExpertise()` exists
     * for the same reason; this mirrors it.
     */
    public function toggleExpertise(string $key, string $area): void
    {
        Gate::authorize('updateOwnProfile', $this->faculty);

        $current = $this->editForm['expertise'] ?? [];

        $this->editForm['expertise'] = in_array($area, $current, true)
            ? array_values(array_diff($current, [$area]))
            : array_values(array_merge($current, [$area]));
    }

    /**
     * Save the self-editable fields.
     *
     * Restricted deliberately: employee ID, college, position, status and the
     * login account are INSTITUTIONAL records — what the institution decides
     * (appointment, rank, employment status) or issues (the employee ID) — so a
     * faculty member may not change them from here even though they own the
     * profile. Only the five fields in `$editForm` are writable, and the rule is
     * enforced server-side, never by hiding inputs.
     *
     * ACTIVITY-LOGGED (D8). That is what makes "save immediately" acceptable
     * here instead of needing a Director approval queue: an expertise or
     * academic change lands in the audit trail, so the Director can see it
     * without having to gate it.
     */
    public function saveProfile(): void
    {
        Gate::authorize('updateOwnProfile', $this->faculty);

        $this->validate([
            'editForm.specialization' => ['nullable', 'string', 'max:255'],
            'editForm.department' => ['nullable', 'string', 'max:255'],
            'editForm.phone' => ['nullable', 'string', 'max:32'],
            'editForm.address' => ['nullable', 'string', 'max:255'],
            'editForm.expertise' => ['array'],
            'editForm.expertise.*' => ['string', 'max:120'],
        ]);

        // Captured before the write: expertise is a RELATION, so `wasChanged()`
        // cannot see it, and the audit entry should name what actually changed
        // rather than just "profile updated".
        $before = $this->faculty->expertise->pluck('area')->all();

        $this->faculty->update([
            'specialization' => $this->editForm['specialization'] ?: null,
            'department' => $this->editForm['department'] ?: null,
            'phone' => $this->editForm['phone'] ?: null,
            'address' => $this->editForm['address'] ?: null,
        ]);

        $this->faculty->syncExpertise(
            $this->editForm['expertise'],
            FacultyModel::expertiseCategoryMap()
        );

        $touched = collect(['specialization', 'department', 'phone', 'address'])
            ->filter(fn (string $field) => $this->faculty->wasChanged($field))
            ->all();

        // Sorted, because the multi-select's order is not meaningful and a
        // reorder must not read as a change.
        $expertiseChanged = $this->sorted($before) !== $this->sorted($this->editForm['expertise']);

        if ($touched !== [] || $expertiseChanged) {
            if ($expertiseChanged) {
                $touched[] = 'expertise';
            }

            activity()->performedOn($this->faculty)->event('faculty_self_update')
                ->log('Faculty member updated their own profile: '.implode(', ', $touched));
        }

        $this->showEdit = false;

        // Re-read so the page behind the modal shows the saved values rather
        // than the pre-edit ones.
        $this->faculty->refresh()->load('expertise');

        $this->dispatch('sc-toast', message: 'Profile updated', type: 'success');
    }

    /**
     * @param  array<int, string>  $values
     * @return array<int, string>
     */
    private function sorted(array $values): array
    {
        $values = array_values(array_unique($values));
        sort($values);

        return $values;
    }

    public function render(FacultyContributionService $contribution, TrainingHoursService $trainingHours)
    {
        $record = $contribution->forFaculty(collect([$this->faculty]))->first();

        // R3c: §6's "Performance trend" — training hours delivered over time.
        // Bucketed by the same activity dates the project hub orders by, so the
        // trend and the project timeline tell the same story. Built from the
        // per-activity rows `FacultyContributionService` already fetched, so this
        // costs no extra query.
        $trend = $record['training_measurable']
            ? $trainingHours->trendForActivities($record['training_activities'] ?? [])
            : [];
        $trendPeak = empty($trend) ? 0.0 : (float) max(array_column($trend, 'hours'));

        // A chart of one point is not a trend, and a chart of all-zero points is
        // not either — both would draw a flat line implying "steady delivery"
        // where the honest answer is "not enough recorded yet". So the chart only
        // renders past that bar; below it the block says what is missing.
        $trendChart = $trendPeak > 0 && count($trend) > 1
            ? $this->trendChart($trend)
            : null;

        // R-Q1: which source each activity's trainee count came from. Surfaced so
        // the Director can see whether a headline figure rests on imported
        // attendance or on someone's manual entry.
        $trendSources = collect($record['training_sources'] ?? [])
            ->map(fn (int $count, string $source) => [
                'count' => $count,
                'label' => TrainingHoursService::SOURCE_LABELS[$source] ?? $source,
                'source' => $source,
            ])
            ->sortByDesc('count')
            ->values();

        // Rendered hours broken out by semester — §6's "approved / pending /
        // rejected hours per semester". Grouped on the activity's planned dates,
        // which is what the semester is derived from.
        $hoursBySemester = RenderedHours::query()
            ->where('faculty_id', $this->faculty->id)
            ->with('activity')
            ->get()
            ->groupBy(function (RenderedHours $entry) {
                $date = $entry->activity?->planned_start_date;

                if ($date === null) {
                    return 'Unscheduled';
                }

                // June–November is the first semester, December–May the second.
                $month = (int) $date->format('n');

                return $month >= 6 && $month <= 11
                    ? $date->format('Y').' · 1st Semester'
                    : $date->copy()->subMonths($month <= 5 ? 6 : 0)->format('Y').' · 2nd Semester';
            })
            ->map(fn ($entries) => [
                'approved' => round($entries->where('status', RenderedHours::STATUS_APPROVED)->sum('hours'), 1),
                'pending' => round($entries->where('status', RenderedHours::STATUS_PENDING)->sum('hours'), 1),
                'rejected' => round($entries->where('status', RenderedHours::STATUS_REJECTED)->sum('hours'), 1),
                'count' => $entries->count(),
            ]);

        $proposals = ActivityProposal::query()
            ->where('faculty_id', $this->faculty->id)
            ->with('activity')
            ->latest()
            ->limit(10)
            ->get();

        $projects = $this->faculty->ledPrograms()->with('college')->get()
            ->map(fn ($project) => ['project' => $project, 'role' => 'Lead']);

        $assigned = ExtensionProject::query()
            ->whereIn('id', function ($query) {
                $query->select('activities.extension_project_id')
                    ->from('activities')
                    ->join('activity_faculty', 'activity_faculty.activity_id', '=', 'activities.id')
                    ->where('activity_faculty.faculty_id', $this->faculty->id)
                    ->whereNull('activities.deleted_at')
                    ->whereNotNull('activities.extension_project_id');
            })
            ->where('program_lead_id', '!=', $this->faculty->id)
            ->with('college')
            ->get()
            ->map(fn ($project) => ['project' => $project, 'role' => 'Co-Lead']);

        return view('livewire.faculty.profile', [
            'record' => $record,
            'projects' => $projects->concat($assigned)->values(),
            'hoursBySemester' => $hoursBySemester,
            'proposals' => $proposals,
            'flags' => $this->flags($record),
            'contribution' => $contribution,
            'trend' => $trend,
            'trendPeak' => $trendPeak,
            'trendChart' => $trendChart,
            'trendSources' => $trendSources,
        ]);
    }

    /**
     * Chart.js payload for §6's "Performance trend".
     *
     * A line chart, not the bar chart used on the admin dashboard: the question
     * here is a single faculty member's trajectory, and a line is what carries
     * "over time". Reuses `config('smartcemes.chart_palette')` (its only
     * remaining reader since the Analytics page was removed 2026-09-27) and the
     * same `x-data x-init` + `Chart.getChart(...).destroy()` guard the dashboard
     * charts use, so a Livewire re-render cannot stack a second canvas.
     *
     * @param  array<int, array<string, mixed>>  $trend
     * @return array<string, mixed>
     */
    private function trendChart(array $trend): array
    {
        $palette = config('smartcemes.chart_palette');

        return [
            'type' => 'line',
            'data' => [
                'labels' => array_map(
                    fn (array $bucket) => $this->periodLabel($bucket['period']),
                    $trend
                ),
                'datasets' => [[
                    'label' => 'Training hours delivered',
                    'data' => array_map(fn (array $bucket) => round((float) $bucket['hours'], 2), $trend),
                    'borderColor' => $palette[0],
                    'backgroundColor' => 'rgba(0,53,153,.08)',
                    'pointBackgroundColor' => $palette[0],
                    'pointRadius' => 3,
                    'borderWidth' => 2,
                    'tension' => 0.32,
                    'fill' => true,
                ]],
            ],
            'options' => [
                'maintainAspectRatio' => false,
                'plugins' => ['legend' => ['display' => false]],
                'scales' => ['y' => ['beginAtZero' => true]],
            ],
        ];
    }

    /**
     * `2026-03` -> `Mar 2026`. The undated bucket stays words, not a date, so a
     * missing date is visibly a missing date rather than a plausible month.
     */
    private function periodLabel(string $period): string
    {
        if ($period === TrainingHoursService::PERIOD_UNDATED) {
            return 'Undated';
        }

        try {
            return Carbon::parse($period.'-01')->format('M Y');
        } catch (\Throwable) {
            return $period;
        }
    }

    /**
     * §6's "Flags" block — surfaced as computed observations, not judgements.
     *
     * @param  array<string, mixed>  $record
     * @return array<int, array<string, string>>
     */
    private function flags(array $record): array
    {
        $flags = [];

        if (($record['projects_involved'] ?? 0) === 0) {
            $flags[] = [
                'tone' => 'gray',
                'text' => 'No project assignment yet.',
            ];
        }

        if (($record['pending_hours'] ?? 0) > 0) {
            $flags[] = [
                'tone' => 'yellow',
                'text' => $this->formatHours($record['pending_hours']).' rendered hours await approval.',
            ];
        }

        if (($record['pending_hours'] ?? 0) > 40) {
            $flags[] = [
                'tone' => 'red',
                'text' => 'Possibly over-loaded — more than 40 hours awaiting approval.',
            ];
        }

        if ($this->faculty->isOnLeave()) {
            $flags[] = [
                'tone' => 'gray',
                'text' => 'Currently on leave — contribution reflects work before leave.',
            ];
        }

        if (($record['proposals_submitted'] ?? 0) > 0 && ($record['proposal_approval_rate'] ?? 0) < 50) {
            $flags[] = [
                'tone' => 'yellow',
                'text' => 'Proposal approval rate below 50%.',
            ];
        }

        return $flags;
    }

    private function formatHours(float|int|string|null $hours): string
    {
        return rtrim(rtrim(number_format((float) $hours, 1), '0'), '.');
    }

    /**
     * Blade-visible hours formatter (no trailing ".0").
     */
    public function fmt(float|int|string|null $hours): string
    {
        return $this->formatHours($hours);
    }
}
