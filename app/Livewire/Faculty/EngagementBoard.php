<?php

namespace App\Livewire\Faculty;

use App\Models\College;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\Faculty as FacultyModel;
use App\Services\FacultyContributionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Faculty Management — the Faculty Engagement board (revision §5 R3 step 2/3,
 * D-R9).
 *
 * This is the sidebar module the adviser highlighted. It replaces the nav entry
 * that was previously PHANTOM: `config/smartcemes.php` pointed at
 * route `faculty.index`, which did not exist, so the item was silently hidden
 * (the nav hides items whose route is missing). Registering this route makes it
 * appear.
 *
 * The page IS the board — following the prototype (faculty-management.html),
 * which deliberately has NO page heading, KPI cards or roster table: the roster
 * lives on the separate Faculty Directory page, reached from the board's header
 * action. Do not add a page title here; that was an explicit P0 decision
 * (P0i/P0j/P0k stripped those surfaces back out).
 *
 * Admin-only: the whole module is "what the Director sees" (§6).
 */
#[Layout('layouts.app')]
class EngagementBoard extends Component
{
    /**
     * The board's metric switch — hours rendered / projects / leads.
     * Matches the prototype's engSwitch.
     */
    #[Url(as: 'metric', except: 'hours')]
    public string $metric = 'hours';

    /**
     * ?college=CAS narrows the chart, leaderboard AND insights together, so the
     * three can never disagree (prototype SCOPE).
     */
    #[Url(as: 'college', except: null)]
    public ?string $collegeScope = null;

    /**
     * Drawer state — the selected faculty member's id.
     */
    public ?int $openFacultyId = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', FacultyModel::class);

        if (! array_key_exists($this->metric, $this->metricDefinitions())) {
            $this->metric = 'hours';
        }

        $valid = College::ordered()->pluck('code')->all();

        if ($this->collegeScope !== null && ! in_array(strtoupper($this->collegeScope), $valid, true)) {
            $this->collegeScope = null;
        }
    }

    public function setMetric(string $metric): void
    {
        if (array_key_exists($metric, $this->metricDefinitions())) {
            $this->metric = $metric;
        }
    }

    public function clearCollegeScope(): void
    {
        $this->collegeScope = null;
    }

    public function openFaculty(int $id): void
    {
        $faculty = FacultyModel::with(['user', 'college', 'expertise'])->findOrFail($id);

        // Defence in depth: a faculty member must not be able to open a
        // colleague's drawer by crafting a Livewire call.
        Gate::authorize('view', $faculty);

        $this->openFacultyId = $id;
    }

    public function closeFaculty(): void
    {
        $this->openFacultyId = null;
    }

    public function render(FacultyContributionService $contribution)
    {
        $faculty = FacultyModel::query()
            ->with(['user', 'college', 'expertise'])
            ->forCollegeCode($this->collegeScope)
            ->get()
            // A faculty row without a user is a data fault, not a person —
            // skip it rather than render an empty row.
            ->filter(fn (FacultyModel $f) => $f->user !== null)
            ->values();

        $rows = $contribution->forFaculty($faculty);
        $ranked = $contribution->rank($rows, $this->metric);
        $definitions = $contribution->metricDefinitions();

        $selected = $this->openFacultyId
            ? $faculty->firstWhere('id', $this->openFacultyId)
            : null;

        return view('livewire.faculty.engagement-board', [
            'rows' => $rows,
            'ranked' => $ranked,
            'definitions' => $definitions,
            // Named `activeMetric`, NOT `metric`: `$metric` is a public Livewire
            // property (a string) in this component, and Livewire injects public
            // properties into the view scope, so a same-named array here would be
            // shadowed by the property and every $metric['key'] would fatal.
            'activeMetric' => $definitions[$this->metric],
            'split' => $contribution->collegeSplit($rows, $this->metric),
            'insights' => $contribution->insights($rows, $this->metric, $this->collegeScope),
            'colleges' => College::ordered()->get(),
            'selected' => $selected,
            'selectedProjects' => $selected ? $this->projectsFor($selected) : collect(),
            'contribution' => $contribution,

            // Chart payload — plain arrays so @js() emits clean JSON. Sending the
            // rows raw would drag Eloquent internals into the page.
            'chartRows' => $ranked->map(function (array $row) use ($definitions) {
                $key = $definitions[$this->metric]['key'];

                return [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'college' => $row['college'],
                    'position' => $row['position'],
                    'status' => $row['status'],
                    'statusLabel' => Faculty::STATUS_LABELS[$row['status']] ?? ucfirst((string) $row['status']),
                    'value' => $row[$key] ?? 0,
                    'hours' => $row['rendered_hours'] ?? 0,
                    'projects' => $row['projects_involved'] ?? 0,
                ];
            })->values(),
        ]);
    }

    /* ---------------------------------------------------------------------
     | Blade helpers — small formatting shims so the view stays declarative.
     | ------------------------------------------------------------------ */

    /**
     * The CAS/COE/CME/GRAD colour used by the chart, the split bar and the pills.
     */
    public function collegeColor(?string $code): string
    {
        return match (strtoupper((string) $code)) {
            'CAS' => '#003599',
            'COE' => '#F6B800',
            'CME' => '#10b981',
            default => '#93b4fd',
        };
    }

    /**
     * The leaderboard row's big number, per metric (prototype `headline`).
     *
     * The unit is rendered SEPARATELY (see `headlineUnit`) so the view can set
     * it in the muted `.eng-val small` style without the number losing weight.
     */
    public function headlineValue(array $row, array $metric): string
    {
        $value = $row[$metric['key']] ?? 0;

        return $metric['key'] === 'rendered_hours'
            ? $this->fmtHours($value)
            : (string) $value;
    }

    /**
     * The unit that sits beside the value — 'hrs rendered' / 'Project(s)' /
     * 'Project(s) led'. Sourced from the metric definition so the wording has
     * one owner (owner request 2026-10-07).
     *
     * Before this the row printed a bare number and the unit appeared only in
     * the caption of the OTHER two metrics, so the hours tab was the one tab
     * whose number carried no unit at all.
     */
    public function headlineUnit(array $row, array $metric): string
    {
        $unit = $metric['unit'] ?? '';

        if ($unit === '') {
            return '';
        }

        // 'hrs rendered' is a fixed label — never "1 hr rendered" — because
        // that is the house term used by the hub tile, the caption and the
        // chart tooltip. Only the countable metrics carry a singular form.
        return (int) ($row[$metric['key']] ?? 0) === 1
            ? ($metric['unit_one'] ?? $unit)
            : $unit;
    }

    /**
     * The caption under the leaderboard row's number — the context that makes
     * the number mean something, per metric.
     */
    public function headlineCaption(array $row, array $metric): string
    {
        $projects = $row['projects_involved'] ?? 0;
        $hours = $this->fmtHours($row['rendered_hours'] ?? 0);

        return match ($metric['key']) {
            'rendered_hours' => $projects.' project'.($projects === 1 ? '' : 's'),
            default => $hours.' hrs rendered',
        };
    }

    /**
     * The identity line under the name: the college, then the ACTIVITY count.
     *
     * It used to carry the project count, which duplicated the caption on the
     * hours tab and the value itself on the projects tab. The activity count is
     * the one contribution figure neither the value nor the caption ever shows,
     * so a row now reads hours / projects / activities without repeating itself
     * (owner request 2026-10-07).
     */
    public function rowSubline(array $row): string
    {
        $college = $row['college'] ?? '—';

        if (($row['status'] ?? 'active') === 'on_leave') {
            return $college.' · On leave';
        }

        return $college.' · '.$this->countLabel((int) ($row['activities_handled'] ?? 0), 'activity', 'activities');
    }

    /**
     * "1 activity" / "4 activities" — the shared pluralisation idiom. Public so
     * the drawer badges use the same rule as the row (they used to print
     * "2 lead" and "1 activities").
     */
    public function countLabel(int $count, string $singular, string $plural): string
    {
        return $count.' '.($count === 1 ? $singular : $plural);
    }

    /**
     * Icon tone classes for the insight notes.
     */
    public function toneClass(string $tone): string
    {
        return match ($tone) {
            'gold' => 'text-gold-700',
            'lnu' => 'text-lnu-700',
            'emerald' => 'text-emerald-600',
            default => 'text-gray-400',
        };
    }

    /**
     * Hours without a trailing ".0" — matches the prototype's SC.hours().
     */
    public function fmtHours(float|int|string|null $hours): string
    {
        $hours = (float) $hours;

        return rtrim(rtrim(number_format($hours, 1), '0'), '.');
    }

    /**
     * The projects a faculty member leads or is assigned to, with their role —
     * the drawer's project list (prototype `projectList`).
     */
    private function projectsFor(FacultyModel $faculty): Collection
    {
        $led = ExtensionProject::query()
            ->where('program_lead_id', $faculty->id)
            ->with('college')
            ->get()
            ->map(fn ($project) => ['project' => $project, 'role' => 'Lead']);

        $assigned = ExtensionProject::query()
            ->whereIn('id', function ($query) use ($faculty) {
                $query->select('activities.extension_project_id')
                    ->from('activities')
                    ->join('activity_faculty', 'activity_faculty.activity_id', '=', 'activities.id')
                    ->where('activity_faculty.faculty_id', $faculty->id)
                    ->whereNull('activities.deleted_at')
                    ->whereNotNull('activities.extension_project_id');
            })
            ->where('program_lead_id', '!=', $faculty->id)
            ->with('college')
            ->get()
            ->map(fn ($project) => ['project' => $project, 'role' => 'Co-Lead']);

        return $led->concat($assigned)->values();
    }

    private function metricDefinitions(): array
    {
        return app(FacultyContributionService::class)->metricDefinitions();
    }
}
