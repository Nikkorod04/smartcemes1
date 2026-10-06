<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\College;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\RenderedHours;
use App\Models\UniversityTarget;
use App\Services\TrainingHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Proves `migrate:fresh --seed` converges on a complete hierarchy.
 *
 * This is the companion to ExtensionProjectRenameTest: that one guards the
 * MIGRATION path (existing rows get linked by the backfill), this one guards the
 * SEED path (a brand-new database gets linked by the seeders). Both orders must
 * converge, and the R2b migration is a deliberate no-op when the table is empty,
 * so the seeder is the only thing that can link rows on a fresh database.
 */
class FreshSeedHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_seed_produces_a_complete_hierarchy(): void
    {
        $this->seed(); // DatabaseSeeder

        $this->assertSame(4, College::count(), 'CAS, COE, CME + the Graduate School');
        $this->assertSame(6, Program::count());

        /* THE LIVE SET IS THE SAFE-ZONE FIVE (owner decision 2026-10-05).
           `SafeZoneProjectSeeder` runs LAST and ARCHIVES the eight the earlier
           seeders created, so both halves are asserted: five live, eight
           archived. The archived ones are kept rather than hard-deleted so a
           Director can restore any of them from the hub's Archived filter. */
        $this->assertSame(5, ExtensionProject::count(), 'the five safe-zone projects');
        $this->assertSame(8, ExtensionProject::onlyTrashed()->count(), '6 migrated + BUSOG + PANDAY, archived');

        $this->assertSame(0, ExtensionProject::whereNull('college_id')->count(), 'no orphan projects');
        $this->assertSame(0, ExtensionProject::whereNull('program_id')->count(), 'no orphan projects');

        /* The archived set still carries both code schemes — six migrated EXT-
           rows plus BUSOG and PANDAY, which were created after R2 and therefore
           carry a college prefix. The live five are all college-prefixed. */
        $archivedCodes = ExtensionProject::onlyTrashed()->orderBy('code')->pluck('code')->all();

        $this->assertCount(6, array_filter($archivedCodes, fn ($c) => str_starts_with($c, 'EXT-')));
        $this->assertCount(1, array_filter($archivedCodes, fn ($c) => str_starts_with($c, 'CAS-')));
        $this->assertCount(1, array_filter($archivedCodes, fn ($c) => str_starts_with($c, 'GRAD-')));

        foreach (ExtensionProject::pluck('code') as $code) {
            $this->assertMatchesRegularExpression(
                '/^(CAS|COE|CME|GRAD)-\d{4}-\d{3}$/',
                $code,
                'Every live project carries a college-prefixed code (R-Q4).'
            );
        }
    }

    /**
     * The safe-zone set must cover every thrust but one.
     *
     * Cultural Development was the empty thrust this set was built to fill. It
     * does — but archiving BATANG MATINIK (the only sports project) moved the gap
     * to Physical Fitness & Sports Development, which is asserted here rather
     * than hidden: a thrust with no projects is the most visible defect on the
     * college hub.
     */
    public function test_the_safe_zone_set_covers_five_of_the_six_thrusts(): void
    {
        $this->seed();

        $covered = Program::query()
            ->whereHas('projects')
            ->pluck('title')
            ->all();

        $this->assertContains('Cultural Development', $covered, 'The empty thrust must now be filled.');

        $uncovered = Program::query()
            ->whereDoesntHave('projects')
            ->pluck('title')
            ->all();

        $this->assertSame(
            ['Physical Fitness & Sports Development'],
            $uncovered,
            'Sports is the ONE thrust the five deliberately leave empty.'
        );
    }

    /**
     * Phase R4: the demo database must come up with a working target model.
     *
     * This is the acceptance check for R4TargetsSeeder — without it a fresh
     * seed would render "no target set" everywhere and the Director would have
     * nothing to demo against.
     */
    public function test_fresh_seed_produces_the_r4_target_model(): void
    {
        $this->seed();

        $target = UniversityTarget::forYear(2026);

        $this->assertNotNull($target, 'The demo AY must carry a university target.');
        $this->assertSame('2500.00', $target->annual_target_hours);
        $this->assertSame('668000.00', $target->annual_target_budget);

        // Every demo activity must carry days, or training hours stay 0 and the
        // whole R4 surface is dead on a fresh seed.
        $withDays = Activity::whereNotNull('no_of_days')->count();
        $this->assertGreaterThan(0, $withDays, 'R4TargetsSeeder must back-fill no_of_days.');

        // A half day must survive the round trip — decimal(4,1), not an integer.
        $this->assertTrue(
            Activity::where('no_of_days', 0.5)->exists(),
            'The demo data must include a half day, since 0.5 is a first-class duration.'
        );

        // BUSOG carries an annual HOURS target so its attainment bars have a
        // denominator. Budget carries none: a project has no annual budget
        // target (owner decision 2026-09-26) — the allocation is the basis.
        //
        // BUSOG is ARCHIVED by the safe-zone seeder, so it is resolved through
        // `withTrashed()`. That is the point of archiving rather than deleting:
        // its figures survive, so restoring it brings back a project with data.
        $busog = ExtensionProject::withTrashed()->where('title', 'like', '%BUSOG%')->firstOrFail();
        $this->assertNotNull($busog->annual_target_hours);
        $this->assertNull(
            $busog->annual_target_budget,
            'The seeder must not write an inert budget-target column.'
        );

        // EVERY LIVE project must carry an hours target, or its page reads "no
        // annual hours target set" and the Director has no ratio to demo. The
        // safe-zone seeder sets all five; R4TargetsSeeder still fills the legacy
        // eight (which are archived afterwards, keeping their figures).
        $untargeted = ExtensionProject::whereNull('annual_target_hours')->pluck('code');
        $this->assertTrue(
            $untargeted->isEmpty(),
            'Every live project needs an annual hours target; missing: '.$untargeted->implode(', ')
        );

        // The legacy figures still mirror seed-data.js on the archived rows.
        $litrawiya = ExtensionProject::withTrashed()->where('title', 'like', '%LITRAWIYA%')->firstOrFail();
        $this->assertSame(640.0, (float) $litrawiya->annual_target_hours, 'archived targets mirror seed-data.js');

        // …and the live set carries its own, non-zero targets.
        $this->assertSame(0, ExtensionProject::where('annual_target_hours', 0)->count());
    }

    public function test_the_demo_seed_renders_measurable_training_hours(): void
    {
        $this->seed();

        // Every LIVE project must render a real figure — that is the whole
        // reason the safe-zone seeder ships cohorts with attendance rather than
        // just project rows (§26.4: a 0 reads as broken, not thin).
        foreach (ExtensionProject::with('college')->get() as $project) {
            $perf = app(TrainingHoursService::class)->forProject($project);

            $this->assertGreaterThan(0, $perf['actual_hours'], $project->code.' must render hours.');
            $this->assertGreaterThan(0, $perf['trainors'], $project->code.' must report a trainor.');
            $this->assertGreaterThan(0, $perf['trainees'], $project->code.' must report a trainee count.');
            $this->assertNotNull($perf['hours_pct'], $project->code.' has a target, so attainment is computable.');
            $this->assertLessThan(
                100,
                $perf['hours_pct'],
                $project->code.' must sit UNDER its target, so the D7 warning stays quiet.'
            );
        }
    }

    /**
     * Every faculty member must carry a real contribution record.
     *
     * The faculty board's headline is `SUM(rendered_hours.hours) WHERE status =
     * approved`, and `rendered_hours` rows are only produced by the activity
     * lifecycle — so a seeder that assigns faculty to activities but writes no
     * hours leaves every professor reading "0.0 hours rendered" no matter how
     * many projects they lead. That is what this pins.
     *
     * It also guards a subtler trap found while building it: leaving EVERY
     * co-lead's entries pending starved the one faculty member who only ever
     * co-leads (Bianca Oledan) of any approved hours at all.
     */
    public function test_every_faculty_member_carries_a_contribution_record(): void
    {
        $this->seed();

        $this->assertGreaterThan(0, Faculty::count());

        foreach (Faculty::with('user')->get() as $faculty) {
            $approved = RenderedHours::where('faculty_id', $faculty->id)
                ->where('status', RenderedHours::STATUS_APPROVED)
                ->sum('hours');

            $this->assertGreaterThan(
                0,
                (float) $approved,
                $faculty->user->name.' has no APPROVED rendered hours — the board would read 0.0.'
            );

            $handled = Activity::whereHas('faculty', fn ($q) => $q->where('faculty_id', $faculty->id))->count();

            $this->assertGreaterThan(
                0,
                $handled,
                $faculty->user->name.' is not assigned to any activity.'
            );
        }

        // At least one entry is left awaiting approval, so the board's "hrs
        // pending" badge is exercised rather than always reading zero.
        $this->assertGreaterThan(
            0,
            RenderedHours::where('status', RenderedHours::STATUS_PENDING)->count(),
            'The demo must include a pending submission for the approval queue.'
        );
    }
}
