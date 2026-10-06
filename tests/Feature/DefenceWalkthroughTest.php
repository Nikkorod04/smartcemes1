<?php

namespace Tests\Feature;

use App\Livewire\Colleges\Index as CollegesIndex;
use App\Livewire\Programs\Hub;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\College;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\RenderedHours;
use App\Models\User;
use App\Services\TrainingHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Executes the DEFENCE WALKTHROUGH in `docs/TEST-SCRIPT.md` and asserts the
 * figures the script tells the presenter to expect.
 *
 * WHY THIS EXISTS
 * ---------------
 * A manual script that states the wrong expected number is worse than no script:
 * the presenter reads it out and is contradicted by the screen. Every headline
 * figure in the walkthrough is therefore pinned here — if the model changes, the
 * script's numbers break a test instead of embarrassing someone at the defence.
 *
 * It also documents the ONE subtlety worth knowing: the per-activity HOURS use
 * the `participants` fallback when no attendance exists, while the project's
 * "Trainees" figure is a DISTINCT beneficiary count and stays 0 until attendance
 * is imported. Those two are different numbers on purpose.
 */
class DefenceWalkthroughTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@lnu.com')->firstOrFail();
    }

    private function hours(): TrainingHoursService
    {
        return app(TrainingHoursService::class);
    }

    /**
     * The full chain: create the project → two activities → budget → complete.
     */
    public function test_the_walkthrough_pipeline_produces_the_documented_figures(): void
    {
        $admin = $this->admin();
        $college = College::where('code', 'CME')->firstOrFail();
        $program = Program::where('title', 'like', 'Livelihood%')->firstOrFail();

        $kent = Faculty::whereHas('user', fn ($q) => $q->where('name', 'like', '%Naputo%'))->firstOrFail();
        $nikko = Faculty::whereHas('user', fn ($q) => $q->where('name', 'like', '%Villas%'))->firstOrFail();

        /* ---------------- Step 2: create the project ----------------
           Creation moved to the hub (§25) — `/projects` is a read-only list now,
           so the walkthrough creates the project where a Director would. */

        Livewire::actingAs($admin)->test(CollegesIndex::class)
            ->call('openProjectCreate')
            ->set('projectForm.college_id', $college->id)
            ->set('projectForm.program_id', $program->id)
            ->set('projectForm.title', 'PANADERO: Barangay Bread & Pastry Livelihood Training')
            ->set('projectForm.description', 'Hands-on bakery training for out-of-work mothers in Sagkahan.')
            ->set('projectForm.planned_start_date', '2026-09-01')
            ->set('projectForm.planned_end_date', '2026-12-31')
            ->set('projectForm.target_beneficiaries', 5)
            ->set('projectForm.allocated_budget', 30000)
            ->set('projectForm.annual_target_hours', 40)
            ->set('projectForm.program_lead_id', $kent->id)
            ->set('projectForm.status', 'ongoing')
            ->call('saveProject')
            ->assertHasNoErrors();

        $project = ExtensionProject::where('title', 'PANADERO: Barangay Bread & Pastry Livelihood Training')->firstOrFail();

        $this->assertMatchesRegularExpression(
            '/^CME-2026-\d{3}$/',
            $project->code,
            'Step 2 of the walkthrough promises a college-prefixed code (R-Q4).'
        );

        // `CME-2026-001` is now the SEEDED PAGKAON project (safe-zone set,
        // 2026-10-05), so the walkthrough's own project takes the next code.
        // Asserted as "the pattern, and not the one already used" rather than a
        // hardcoded literal, so adding a CME project to the seeder does not
        // break the walkthrough for the wrong reason.
        $this->assertNotSame(
            'CME-2026-001',
            $project->code,
            'CME-2026-001 belongs to the seeded PAGKAON project.'
        );

        /* ---------------- Step 3: two activities ---------------- */

        $addActivity = function (array $activity) use ($admin, $project) {
            $component = Livewire::actingAs($admin)
                ->test(Hub::class, ['project' => $project])
                ->call('openActivityForm')
                ->set('activityForm.title', $activity['title'])
                ->set('activityForm.venue', 'Sagkahan Barangay Hall')
                ->set('activityForm.planned_start_date', $activity['date'])
                ->set('activityForm.planned_end_date', $activity['date'])
                ->set('activityForm.start_time', '08:00')
                ->set('activityForm.end_time', '12:00')
                ->set('activityForm.no_of_days', $activity['days'])
                ->set('activityForm.participants', '5')
                ->set('activityForm.faculty_ids', $activity['faculty'])
                ->set('activityForm.allocated_budget', $activity['budget'])
                ->set('activityForm.status', 'ongoing')
                ->call('saveActivity')
                ->assertHasNoErrors();

            // A silent failure here would make the arithmetic assertions below
            // look like a formula bug. Pin the reason first, then the row.
            $this->assertSame(
                '',
                (string) $component->get('facultyConflict'),
                "Saving \"{$activity['title']}\" was refused by the schedule guard."
            );

            $this->assertDatabaseHas('activities', ['title' => $activity['title']]);
        };

        $addActivity([
            'title' => 'A1 · Dough Basics & Food Safety Orientation',
            'date' => '2026-10-20',
            'days' => '1',
            'faculty' => [$kent->id, $nikko->id],
            'budget' => 6000,
        ]);

        $addActivity([
            'title' => 'A2 · Baking Practicum: Pan de Sal & Ensaymada',
            'date' => '2026-10-27',
            'days' => '0.5',
            'faculty' => [$kent->id, $nikko->id],
            'budget' => 9000,
        ]);

        /* ---------------- Step 3's arithmetic ---------------- */

        $rollup = $this->hours()->forProject($project->fresh());

        $this->assertSame(
            2,
            $rollup['activity_count'],
            'Both activities must have been created before the arithmetic is checked.'
        );

        // 2 × 5 × 1 = 10  ·  2 × 5 × 0.5 = 5  →  15
        $this->assertSame(15.0, $rollup['actual_hours'], 'Step 3 promises 15 training hours (10 + 5).');
        $this->assertSame(40.0, $rollup['target_hours']);
        $this->assertSame(37.5, $rollup['hours_pct'], 'Step 3 promises 37.5 % of the 40-hour target.');
        $this->assertSame(2, $rollup['trainors'], 'Two DISTINCT faculty across the project.');
        $this->assertSame(2, $rollup['activity_count']);

        // The project's "Trainees" tile is a distinct BENEFICIARY count — it is
        // 0 until attendance is imported (step 5). The hours above used the
        // `participants` fallback, which is a different number by design.
        $this->assertSame(
            0,
            $rollup['trainees'],
            'Without imported attendance the distinct reach is 0 — the walkthrough must not claim 25 here.'
        );

        /* ---------------- Step 6: three budget entries ---------------- */

        foreach ([
            ['Ingredients & baking supplies', 6000, '2026-10-20'],
            ['Oven rental & utilities', 5000, '2026-10-27'],
            ['Packaging & labelling materials', 2500, '2026-10-27'],
        ] as [$item, $amount, $date]) {
            Livewire::actingAs($admin)
                ->test(Hub::class, ['project' => $project])
                ->call('openBudgetForm')
                ->set('budgetForm.item_name', $item)
                ->set('budgetForm.amount', (string) $amount)
                ->set('budgetForm.date_used', $date)
                ->call('saveBudgetEntry')
                ->assertHasNoErrors();
        }

        $rollup = $this->hours()->forProject($project->fresh());

        $this->assertSame(13500.0, $rollup['utilized_budget'], 'Step 6 promises ₱13,500 utilized.');
        $this->assertSame(30000.0, $rollup['allocated_budget']);
        $this->assertSame(45.0, $rollup['budget_pct'], 'Step 6 promises 45 % of the ₱30,000 allocation.');

        /* ---------------- Step 7: completing drafts rendered hours ---------------- */

        $draftsBefore = RenderedHours::count();

        foreach (Activity::where('extension_project_id', $project->id)->get() as $activity) {
            Livewire::actingAs($admin)
                ->test(Hub::class, ['project' => $project])
                ->call('completeActivity', $activity->id);
        }

        $this->assertGreaterThan(
            $draftsBefore,
            RenderedHours::count(),
            'Step 7 promises that completing an activity drafts rendered hours for each assigned faculty member.'
        );

        // A1 and A2 are both 08:00–12:00 → 4 hrs each, one draft per faculty member.
        $fourHourDrafts = RenderedHours::where('hours', 4)->count();

        $this->assertGreaterThan(
            0,
            $fourHourDrafts,
            'Step 7 promises 4-hour drafts (end_time − start_time) for the assigned faculty.'
        );

        /* ---------------- Step 8: the project target view ---------------- */

        $rollup = $this->hours()->forProject($project->fresh());

        $this->assertSame(15.0, $rollup['actual_hours']);
        $this->assertSame(37.5, $rollup['hours_pct']);
        $this->assertSame(45.0, $rollup['budget_pct']);
        $this->assertSame(0, $rollup['trainees'], 'Still 0 — no attendance has been imported yet.');

        /* ---------------- Steps 4–5: beneficiaries + attendance ----------------
           The IMPORT UI is covered by `ActivityAttendanceImportTest`. What is
           pinned here is the consequence the walkthrough depends on: imported
           attendance becomes the trainee source, and the reach becomes a
           DISTINCT person count. */

        $beneficiaries = Beneficiary::factory()->count(5)->create([
            'barangay' => 'Sagkahan',
            'municipality' => 'Tacloban City',
            'beneficiary_category' => 'Parent',
        ]);

        $project->beneficiaries()->sync($beneficiaries->pluck('id')->all());

        foreach (Activity::where('extension_project_id', $project->id)->get() as $activity) {
            foreach ($beneficiaries->values() as $index => $beneficiary) {
                Attendance::create([
                    'activity_id' => $activity->id,
                    'beneficiary_id' => $beneficiary->id,
                    'attendance_date' => $activity->planned_start_date,
                    // Four present, one late — late still counts as served.
                    'status' => $index === 4 ? 'late' : 'present',
                ]);
            }
        }

        $rollup = $this->hours()->forProject($project->fresh());

        $this->assertSame(5, $rollup['trainees'], 'The reach is a DISTINCT person count (present + late).');
        $this->assertSame(
            15.0,
            $rollup['actual_hours'],
            'Attendance (5 people) replaces the participants fallback (5) — the hours must not move.'
        );
    }

    /**
     * Step 8b's rule: the university pool is a CONSUMPTION pool, and the new
     * project's hours must show up in it.
     */
    public function test_the_university_pool_is_a_consumption_pool(): void
    {
        $pool = $this->hours()->forYear(2026, 2500.0, 668000.0);

        // Seeded values, quoted in step 8b of the walkthrough.
        $this->assertSame(2500.0, $pool['target_hours'], 'The seeded university pool is 2,500 hours.');
        $this->assertSame(668000.0, $pool['target_budget'], 'The seeded university budget pool is ₱668,000.');

        // The seeded CAS project already delivers hours, so the pool shows real
        // consumption rather than an empty ratio.
        $this->assertGreaterThan(0.0, (float) ($pool['hours_pct'] ?? 0.0), 'The pool must show real consumption.');
        $this->assertGreaterThan(0.0, (float) ($pool['budget_pct'] ?? 0.0));

        // A LIVE project that carries an hours target and real delivery. (This
        // used to be `CAS-2026-001` — the seeded BUSOG — which the safe-zone
        // seeder now archives, 2026-10-05.)
        $live = ExtensionProject::whereNotNull('annual_target_hours')->firstOrFail();

        $this->assertGreaterThan(
            0.0,
            $this->hours()->forProject($live)['actual_hours'],
            $live->code.' is a seeded project carrying a target — the kind step 1 tells you to open.'
        );
    }

    /**
     * Step 12's conflict demo — and the regression test for TWO bugs that made
     * the 8.8 hard-block useless:
     *
     *  1. the conflict query filtered a bare `id` on a relation joining
     *     `activities` to `activity_faculty`; the pivot has its own `id`, so the
     *     column was ambiguous and the query threw on every driver;
     *  2. the refusal message had four `%s` placeholders and three arguments, so
     *     the guard raised ArgumentCountError instead of refusing.
     *
     * Neither was reachable from the existing tests, because they all used
     * faculty with no prior activities. This one deliberately books a faculty
     * member who is already committed.
     */
    public function test_the_schedule_guard_refuses_a_clashing_assignment_with_a_message(): void
    {
        $admin = $this->admin();

        /* The LINIS safe-zone project (CAS) books Nikko Villas on 2026-08-22.
           This used to be the seeded BUSOG and its "Feeding Cycle 2", which the
           safe-zone seeder now archives (2026-10-05) — archiving takes its
           activities with it, so the conflict demo has to run against a LIVE
           booking. LINIS is led by Nikko, which keeps the demo's premise: a
           faculty member who is already committed. */
        $project = ExtensionProject::where('title', 'like', 'LINIS%')->firstOrFail();

        $nikko = Faculty::whereHas('user', fn ($q) => $q->where('name', 'like', '%Villas%'))->firstOrFail();

        $this->assertDatabaseHas('activities', [
            'title' => 'Composting & Materials Recovery Training',
        ]);

        $component = Livewire::actingAs($admin)
            ->test(Hub::class, ['project' => $project])
            ->call('openActivityForm')
            ->set('activityForm.title', 'A clashing session')
            ->set('activityForm.planned_start_date', '2026-08-22')
            ->set('activityForm.planned_end_date', '2026-08-22')
            ->set('activityForm.start_time', '08:00')
            ->set('activityForm.end_time', '12:00')
            ->set('activityForm.no_of_days', '1')
            ->set('activityForm.faculty_ids', [$nikko->id])
            ->call('saveActivity');

        // The guard must REFUSE, with a readable message — not crash, not save.
        $this->assertDatabaseMissing('activities', ['title' => 'A clashing session']);

        $conflict = (string) $component->get('facultyConflict');

        $this->assertStringContainsString('Assignment refused', $conflict);
        $this->assertStringContainsString('Nikko Villas', $conflict);
        $this->assertStringContainsString('Composting', $conflict, 'The message must name the clash.');
    }

    /**
     * Step 1's negative claim: the project hub must carry NO VISIBLE 8.6 KPI
     * surface.
     *
     * Two traps here, both found by running it:
     *  - "Baseline" legitimately appears inside an activity TITLE ("Nutrition
     *    Baseline Assessment"), so a naive substring check false-positives.
     *  - `kpi_metric` legitimately appears in the **Livewire snapshot** — the
     *    Hub still holds the retained-but-unread `objForm` state (R-Q2, pinned by
     *    `ObjectiveStatusTest`). That is inert payload, not a rendered surface.
     *
     * So this asserts the absence of the REMOVED UI's labels, which is what the
     * walkthrough actually claims.
     */
    public function test_the_project_hub_renders_no_kpi_dictionary_surface(): void
    {
        $admin = $this->admin();
        $project = ExtensionProject::firstOrFail();

        $html = $this->actingAs($admin)->get(route('projects.show', $project))->assertOk()->getContent();

        foreach (['Results Framework', 'Add objective', 'Objective statement', 'KPI Dictionary', 'Manage objectives'] as $retired) {
            $this->assertStringNotContainsString(
                $retired,
                $html,
                "The project hub still renders the retired 8.6 KPI surface: {$retired}"
            );
        }
    }
}
