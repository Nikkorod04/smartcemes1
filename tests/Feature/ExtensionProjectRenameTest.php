<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\ExtensionProject;
use App\Models\Program;
use Database\Seeders\CollegeSeeder;
use Database\Seeders\Phase2Seeder;
use Database\Seeders\ProgramSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * PHASE R2 — the rename, verified.
 *
 * These assertions are the durable guard on the highest-risk migration in the
 * plan: they run it against a POPULATED database (the only case that matters,
 * since the rename must preserve the six inherited rows and their children),
 * prove `down()` restores every name, and prove the backfill links each row to
 * the right college and broad program.
 *
 * If a future migration renames these tables again, THIS is the file that will
 * fail first — deliberately.
 */
class ExtensionProjectRenameTest extends TestCase
{
    use RefreshDatabase;

    public function test_rename_ran_and_preserved_rows(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(CollegeSeeder::class);
        $this->seed(ProgramSeeder::class);
        $this->seed(Phase2Seeder::class);

        /* The new table exists, the old one does not. */
        $this->assertTrue(Schema::hasTable('extension_projects'));
        $this->assertFalse(Schema::hasTable('extension_programs'));

        /* All six rows survived. */
        $this->assertSame(6, DB::table('extension_projects')->count());

        /* The FK column was renamed on every dependent table. */
        foreach (['activities', 'budget_utilizations', 'program_objectives', 'activity_proposals', 'program_narratives'] as $table) {
            $this->assertTrue(Schema::hasColumn($table, 'extension_project_id'), "$table missing extension_project_id");
            $this->assertFalse(Schema::hasColumn($table, 'extension_program_id'), "$table still has the pre-R2 column");
        }

        /* Both pivots were renamed, table and column. */
        $this->assertTrue(Schema::hasTable('extension_project_beneficiary'));
        $this->assertTrue(Schema::hasTable('community_extension_project'));
        $this->assertTrue(Schema::hasColumn('extension_project_beneficiary', 'extension_project_id'));

        /* The new columns exist. */
        $this->assertTrue(Schema::hasColumn('extension_projects', 'college_id'));
        $this->assertTrue(Schema::hasColumn('extension_projects', 'program_id'));
        $this->assertTrue(Schema::hasColumn('extension_projects', 'annual_target_hours'));
        $this->assertTrue(Schema::hasColumn('extension_projects', 'annual_target_budget'));

        /* Codes are untouched (R-Q4). */
        $codes = DB::table('extension_projects')->orderBy('code')->pluck('code')->all();
        $this->assertSame([
            'EXT-2026-001', 'EXT-2026-002', 'EXT-2026-003',
            'EXT-2026-004', 'EXT-2026-005', 'EXT-2026-006',
        ], $codes);
    }

    public function test_backfill_links_every_row_via_specialization_mapping(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(CollegeSeeder::class);
        $this->seed(ProgramSeeder::class);
        $this->seed(Phase2Seeder::class);

        $expected = [
            'EXT-2026-001' => ['COE', 'Literacy, Numeracy & Language'],
            'EXT-2026-002' => ['CAS', 'Environmental Conservation & Disaster Preparedness'],
            'EXT-2026-003' => ['CME', 'Livelihood, Technical & Business Management'],
            'EXT-2026-004' => ['CAS', 'Information, Communication & Education'],
            'EXT-2026-005' => ['CAS', 'Information, Communication & Education'],
            'EXT-2026-006' => ['COE', 'Physical Fitness & Sports Development'],
        ];

        foreach ($expected as $code => [$collegeCode, $programTitle]) {
            $row = DB::table('extension_projects')->where('code', $code)->first();

            $this->assertNotNull($row->college_id, "$code has no college_id");
            $this->assertNotNull($row->program_id, "$code has no program_id");

            $this->assertSame(
                $collegeCode,
                DB::table('colleges')->where('id', $row->college_id)->value('code'),
                "$code linked to the wrong college"
            );
            $this->assertSame(
                $programTitle,
                DB::table('programs')->where('id', $row->program_id)->value('title'),
                "$code linked to the wrong program"
            );
        }
    }

    public function test_rename_is_reversible(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(CollegeSeeder::class);
        $this->seed(ProgramSeeder::class);
        $this->seed(Phase2Seeder::class);

        $migrations = app('migrator');
        $migrations->rollback([base_path('database/migrations/2026_09_25_000200_add_hierarchy_links_to_extension_projects.php')]);
        $migrations->rollback([base_path('database/migrations/2026_09_25_000100_rename_extension_programs_to_extension_projects.php')]);

        $this->assertTrue(Schema::hasTable('extension_programs'), 'down() did not restore the table');
        $this->assertFalse(Schema::hasTable('extension_projects'));
        $this->assertTrue(Schema::hasColumn('activities', 'extension_program_id'), 'down() did not restore the FK');
        $this->assertTrue(Schema::hasTable('extension_program_beneficiary'), 'down() did not restore the pivot');
        $this->assertTrue(Schema::hasTable('community_extension_program'), 'down() did not restore the community pivot');
        $this->assertSame(6, DB::table('extension_programs')->count(), 'down() lost rows');
    }

    /* ------------------------------------------------------------------ */
    /* The hierarchy itself — the point of the whole phase */
    /* ------------------------------------------------------------------ */

    /**
     * Exit criterion: the hierarchy renders Program → Project → Activity.
     */
    public function test_hierarchy_walks_college_program_project_activity(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(CollegeSeeder::class);
        $this->seed(ProgramSeeder::class);
        $this->seed(Phase2Seeder::class);

        $college = College::where('code', 'COE')->firstOrFail();
        $project = ExtensionProject::where('code', 'EXT-2026-001')->firstOrFail();

        /* upward */
        $this->assertSame($college->id, $project->college->id);
        $this->assertSame('Literacy, Numeracy & Language', $project->program->title);

        /* downward */
        $this->assertContains($project->id, $college->projects->pluck('id')->all());
        $this->assertContains(
            $project->id,
            $project->program->projects->pluck('id')->all(),
            'the broad program reaches its projects'
        );
        $this->assertGreaterThan(0, $project->activities->count(), 'activities hang off the project');
    }

    /**
     * A broad program spans colleges — that is why `programs` has NO
     * `college_id` (see College::projects() docblock). This test pins that
     * design so a future migration cannot quietly add one.
     */
    public function test_a_broad_program_spans_colleges_and_carries_no_college_fk(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(CollegeSeeder::class);
        $this->seed(ProgramSeeder::class);
        $this->seed(Phase2Seeder::class);

        $this->assertFalse(
            Schema::hasColumn('programs', 'college_id'),
            'broad programs are university-wide CESO thrusts; they must not own a college'
        );

        /* Prove the MODEL spans colleges — not that the demo seed happens to.
           (The seed does demonstrate it: ICE is delivered by CAS and, since
           2026-09-25, the Graduate School. But the assertions below do not lean
           on that, so a future seed change cannot silently weaken the test.)
           Arrange the condition explicitly: move one project to a THIRD college
           and assert the thrust reports all of them. */
        $ice = Program::where('title', 'Information, Communication & Education')->firstOrFail();
        $coe = College::where('code', 'COE')->firstOrFail();

        $before = $ice->projects->pluck('college.code')->unique()->values();
        $this->assertGreaterThanOrEqual(1, $before->count(), 'the ICE thrust has at least one project');

        $ice->projects->first()->update(['college_id' => $coe->id]);

        $colleges = $ice->fresh()->projects->pluck('college.code')->unique()->values()->all();

        $this->assertContains('COE', $colleges, 'the moved project reports its new college');
        $this->assertGreaterThanOrEqual(
            2,
            count($colleges),
            'one thrust is delivered by more than one college'
        );
    }

    /**
     * Migrated rows keep their legacy codes (R-Q4) while new rows adopt the
     * college prefix — both schemes coexist without collision.
     */
    public function test_legacy_and_college_prefixed_codes_coexist(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(CollegeSeeder::class);
        $this->seed(ProgramSeeder::class);
        $this->seed(Phase2Seeder::class);

        $legacy = DB::table('extension_projects')->pluck('code')->all();
        $this->assertCount(6, $legacy);
        foreach ($legacy as $code) {
            $this->assertStringStartsWith('EXT-2026-', $code, 'migrated history is preserved verbatim');
        }

        /* A new project gets the college prefix and cannot collide. */
        $new = ExtensionProject::nextCode('COE', 2026);
        $this->assertSame('COE-2026-001', $new);
        $this->assertNotContains($new, $legacy);
    }
}
