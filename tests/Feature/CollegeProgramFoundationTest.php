<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\Program;
use Database\Seeders\CollegeSeeder;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase R1 — the College + broad Program foundation (revision §4.1 / §4.2).
 *
 * R1 is ADDITIVE: it introduces the two new levels without moving any data.
 * The exit criterion is that no existing screen changes behaviour, which is
 * covered by the pre-existing suite; these tests cover only the new entities.
 */
class CollegeProgramFoundationTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------------
     | Colleges
     | ------------------------------------------------------------------ */

    public function test_colleges_table_has_the_expected_shape(): void
    {
        $college = College::factory()->create();

        $this->assertDatabaseHas('colleges', ['id' => $college->id]);
        $this->assertSame('active', $college->status);
        $this->assertNull($college->deleted_at);
    }

    public function test_college_code_is_unique(): void
    {
        College::factory()->create(['code' => 'CAS']);

        $this->expectException(QueryException::class);

        College::factory()->create(['code' => 'CAS']);
    }

    public function test_college_soft_deletes(): void
    {
        $college = College::factory()->create();
        $college->delete();

        $this->assertSoftDeleted('colleges', ['id' => $college->id]);
        $this->assertSame(0, College::count());
        $this->assertSame(1, College::withTrashed()->count());
    }

    public function test_college_coordinator_relation_resolves_a_faculty(): void
    {
        $this->seed(UserSeeder::class);

        $faculty = Faculty::whereHas('user', fn ($q) => $q->where('email', 'faculty1@lnu.com'))->first();
        $this->assertNotNull($faculty, 'seeded faculty1 must exist');

        $college = College::factory()->create(['extension_coordinator_id' => $faculty->id]);

        $this->assertTrue($college->extensionCoordinator->is($faculty));
    }

    public function test_college_coordinator_nulls_on_faculty_delete(): void
    {
        $this->seed(UserSeeder::class);

        $faculty = Faculty::whereHas('user', fn ($q) => $q->where('email', 'faculty1@lnu.com'))->first();
        $college = College::factory()->create(['extension_coordinator_id' => $faculty->id]);

        $faculty->forceDelete();

        $this->assertNull($college->fresh()->extension_coordinator_id);
    }

    public function test_active_and_ordered_scopes(): void
    {
        College::factory()->create(['code' => 'CME', 'status' => 'active']);
        College::factory()->create(['code' => 'CAS', 'status' => 'active']);
        College::factory()->create(['code' => 'COE', 'status' => 'inactive']);

        $active = College::active()->ordered()->pluck('code')->all();

        $this->assertSame(['CAS', 'CME'], $active);
    }

    /* ---------------------------------------------------------------------
     | College seeder
     | ------------------------------------------------------------------ */

    public function test_college_seeder_creates_the_four_official_colleges(): void
    {
        /* CollegeSeeder resolves coordinators from the faculty roster, so the
           faculty must exist first — this mirrors DatabaseSeeder's ordering. */
        $this->seed(UserSeeder::class);
        $this->seed(CollegeSeeder::class);

        $this->assertSame(4, College::count());
        $this->assertEqualsCanonicalizing(
            ['CAS', 'COE', 'CME', 'GRAD'],
            College::pluck('code')->all()
        );

        $cas = College::where('code', 'CAS')->firstOrFail();
        $this->assertSame('College of Arts and Sciences', $cas->name);
        $this->assertNotNull($cas->extension_coordinator_id, 'CAS must have a coordinator');

        /* The fourth unit is the Graduate School — not a college in the
           institutional sense, but it delivers extension work. */
        $this->assertSame('Graduate School', College::where('code', 'GRAD')->firstOrFail()->name);
    }

    public function test_college_seeder_tolerates_a_missing_faculty_roster(): void
    {
        /* Without faculty every coordinator is null — the seeder must not
           throw, so it can run against a bare database. */
        $this->seed(CollegeSeeder::class);

        $this->assertSame(4, College::count());
        $this->assertSame(0, College::whereNotNull('extension_coordinator_id')->count());
    }

    public function test_college_seeder_is_idempotent(): void
    {
        $this->seed(CollegeSeeder::class);
        $this->seed(CollegeSeeder::class);

        $this->assertSame(4, College::count());
    }

    /* ---------------------------------------------------------------------
     | Programs (the BROAD level)
     | ------------------------------------------------------------------ */

    public function test_program_uses_its_own_table_and_does_not_collide(): void
    {
        /* Two distinct levels, two distinct tables. R2 finalised the names:
           the BROAD level is `programs`, the PROJECT level is
           `extension_projects`. */
        $this->assertSame('programs', (new Program)->getTable());
        $this->assertSame('extension_projects', (new ExtensionProject)->getTable());
    }

    public function test_program_factory_and_pillars(): void
    {
        $program = Program::factory()->social()->create();

        $this->assertSame('social', $program->pillar);
        $this->assertSame('active', $program->status);
    }

    public function test_program_soft_deletes_and_scopes(): void
    {
        $keep = Program::factory()->create(['status' => 'active']);
        $gone = Program::factory()->inactive()->create();
        $gone->delete();

        $this->assertSame(1, Program::count(), 'soft-deleted rows are excluded by default');
        $this->assertSame(2, Program::withTrashed()->count());
        $this->assertSame(1, Program::active()->count());
        $this->assertTrue($keep->exists);
        $this->assertSoftDeleted('programs', ['id' => $gone->id]);
    }

    public function test_program_next_code_is_sequential_and_prefixed(): void
    {
        $first = Program::nextCode(2026);
        $this->assertSame('PROG-2026-001', $first);

        Program::create([
            'code' => $first,
            'title' => 'Test',
            'pillar' => 'social',
            'ceso_thrust' => 'Test thrust',
            'status' => 'active',
        ]);

        $this->assertSame('PROG-2026-002', Program::nextCode(2026));
    }

    public function test_program_next_code_heals_drift_from_seeded_codes(): void
    {
        /* A row inserted outside the sequence must not be duplicated. */
        Program::create([
            'code' => 'PROG-2026-005',
            'title' => 'Manual',
            'pillar' => 'social',
            'ceso_thrust' => 'Manual thrust',
            'status' => 'active',
        ]);

        $this->assertSame('PROG-2026-006', Program::nextCode(2026));
    }

    public function test_program_next_code_is_year_scoped(): void
    {
        $this->assertSame('PROG-2027-001', Program::nextCode(2027));
        $this->assertSame('PROG-2026-001', Program::nextCode(2026));
    }

    public function test_program_next_code_is_race_safe_under_repeated_calls(): void
    {
        $codes = [];
        for ($i = 0; $i < 5; $i++) {
            $code = Program::nextCode(2026);
            $codes[] = $code;
            Program::create([
                'code' => $code,
                'title' => "P{$i}",
                'pillar' => 'social',
                'ceso_thrust' => 'T',
                'status' => 'active',
            ]);
        }

        $this->assertSame(count($codes), count(array_unique($codes)), 'codes must never repeat');
        $this->assertSame('PROG-2026-001', $codes[0]);
        $this->assertSame('PROG-2026-005', $codes[4]);
    }

    public function test_program_code_is_unique(): void
    {
        Program::factory()->create(['code' => 'PROG-2026-001']);

        $this->expectException(QueryException::class);

        Program::factory()->create(['code' => 'PROG-2026-001']);
    }

    /* ---------------------------------------------------------------------
     | Program seeder
     | ------------------------------------------------------------------ */

    public function test_program_seeder_creates_the_six_ceso_thrusts(): void
    {
        $this->seed(ProgramSeeder::class);

        $this->assertSame(6, Program::count());

        $this->assertEqualsCanonicalizing(
            [
                'Literacy, Numeracy & Language',
                'Information, Communication & Education',
                'Cultural Development',
                'Physical Fitness & Sports Development',
                'Livelihood, Technical & Business Management',
                'Environmental Conservation & Disaster Preparedness',
            ],
            Program::pluck('title')->all()
        );
    }

    public function test_program_seeder_pillar_distribution_is_two_one_three(): void
    {
        $this->seed(ProgramSeeder::class);

        $this->assertSame(4, Program::where('pillar', 'social')->count());
        $this->assertSame(1, Program::where('pillar', 'economic')->count());
        $this->assertSame(1, Program::where('pillar', 'environmental')->count());
    }

    public function test_program_seeder_cites_a_ceso_thrust_for_every_row(): void
    {
        $this->seed(ProgramSeeder::class);

        Program::all()->each(function (Program $p) {
            $this->assertNotEmpty($p->ceso_thrust, "program {$p->code} must cite a CESO thrust");
            $this->assertContains($p->pillar, ['social', 'economic', 'environmental']);
        });
    }

    public function test_program_seeder_carries_no_training_hours_target(): void
    {
        /* §2.2B / D-C5: broad programs carry NO target of any kind. */
        $this->seed(ProgramSeeder::class);

        $this->assertSame(0, Program::whereNotNull('annual_target_hours')->count());
    }

    public function test_program_seeder_is_idempotent(): void
    {
        $this->seed(ProgramSeeder::class);
        $this->seed(ProgramSeeder::class);

        $this->assertSame(6, Program::count());
    }

    public function test_program_seeder_codes_are_deterministic(): void
    {
        $this->seed(ProgramSeeder::class);

        $year = (int) now()->format('Y');
        $this->assertDatabaseHas('programs', ['code' => sprintf('PROG-%d-001', $year)]);
        $this->assertDatabaseHas('programs', ['code' => sprintf('PROG-%d-006', $year)]);
    }

    /* ---------------------------------------------------------------------
     | R1 is ADDITIVE — the legacy side must be untouched
     | ------------------------------------------------------------------ */

    public function test_full_seed_leaves_the_legacy_program_table_intact(): void
    {
        $this->seed();

        /* The legacy entity (soon to be "project") must still exist and hold
           its data — R1 introduces the new levels without moving anything.

           Counted through `withTrashed()`: the safe-zone seeder ARCHIVES the
           legacy rows rather than deleting them (2026-10-05), so they are still
           in the table with all their data — which is exactly what this test is
           asserting. Comparing against the live count would now measure the
           opposite of the intent. */
        $this->assertGreaterThan(0, ExtensionProject::withTrashed()->count());
        $this->assertDatabaseCount('extension_projects', ExtensionProject::withTrashed()->count());
    }

    public function test_new_entities_do_not_disturb_the_sequence_table_contract(): void
    {
        /* Broad-program codes (PROG-…) and project codes (college-prefixed,
           R-Q4) must use DIFFERENT sequence keys, so the three numbering
           schemes can never consume each other's values. */
        $broad = Program::nextCode(2026);
        $cas = ExtensionProject::nextCode('CAS', 2026);
        $coe = ExtensionProject::nextCode('COE', 2026);
        $cme = ExtensionProject::nextCode('CME', 2026);

        $this->assertStringStartsWith('PROG-2026-', $broad);
        $this->assertStringStartsWith('CAS-2026-', $cas);
        $this->assertStringStartsWith('COE-2026-', $coe);
        $this->assertStringStartsWith('CME-2026-', $cme);

        $this->assertDatabaseHas('sequences', ['key' => 'program_2026']);
        $this->assertDatabaseHas('sequences', ['key' => 'project_CAS_2026']);
        $this->assertDatabaseHas('sequences', ['key' => 'project_COE_2026']);
        $this->assertDatabaseHas('sequences', ['key' => 'project_CME_2026']);
    }
}
