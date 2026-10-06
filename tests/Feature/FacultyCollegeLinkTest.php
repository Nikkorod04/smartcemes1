<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\Faculty;
use App\Models\FacultyExpertise;
use Database\Seeders\CollegeSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase R3a — the faculty college link + the expertise table (revision §5 R3
 * step 1 / §4.5).
 *
 * The load-bearing claim here is the BACKFILL: `faculties.college_id` is
 * derived from `department`, and the mapping must be deterministic. These tests
 * pin the mapping (including the legacy "College of Business Administration"
 * label) so a future edit to either the seeder or the migration cannot quietly
 * mis-file a faculty member into the wrong college.
 */
class FacultyCollegeLinkTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------------
     | Schema
     | ------------------------------------------------------------------ */

    public function test_faculties_gain_a_nullable_college_id(): void
    {
        $faculty = Faculty::factory()->create(['college_id' => null]);

        $this->assertDatabaseHas('faculties', [
            'id' => $faculty->id,
            'college_id' => null,
        ]);
    }

    public function test_faculties_table_has_the_expected_shape(): void
    {
        $college = College::factory()->create();
        $faculty = Faculty::factory()->create(['college_id' => $college->id]);

        $this->assertTrue($faculty->college->is($college));
        $this->assertSame($college->id, $faculty->fresh()->college_id);
    }

    public function test_a_faculty_member_without_a_college_still_exists(): void
    {
        // Nullable by design — a factory row with no college must not blow up,
        // because the UI has to render that case.
        $faculty = Faculty::factory()->create(['college_id' => null]);

        $this->assertNull($faculty->fresh()->college);
    }

    public function test_deleting_a_college_nulls_the_link_rather_than_the_faculty(): void
    {
        $college = College::factory()->create();
        $faculty = Faculty::factory()->create(['college_id' => $college->id]);

        $college->forceDelete();

        $this->assertDatabaseHas('faculties', ['id' => $faculty->id]);
        $this->assertNull($faculty->fresh()->college_id);
    }

    /* ---------------------------------------------------------------------
     | Backfill — the deterministic department mapping
     | ------------------------------------------------------------------ */

    public function test_seeded_faculty_are_linked_to_their_college_by_department(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(CollegeSeeder::class);

        // The R3a migration already ran during RefreshDatabase; re-run its
        // backfill against the freshly seeded rows.
        $this->runBackfill();

        $expected = [
            'faculty1@lnu.com' => 'CAS', // Carlo Sumile — Arts and Sciences
            'faculty2@lnu.com' => 'COE', // Bianca Oledan — Education
            'faculty3@lnu.com' => 'CAS', // Nikko Villas — Arts and Sciences
            'faculty4@lnu.com' => 'CME', // Kent Naputo — Management and Entrepreneurship
            'faculty5@lnu.com' => 'GRAD', // Dr. Ramon L. Villamor — Graduate School
            'faculty6@lnu.com' => 'GRAD', // Dr. Cristina P. Manalo — Graduate School
        ];

        foreach ($expected as $email => $code) {
            $faculty = Faculty::whereHas('user', fn ($q) => $q->where('email', $email))->firstOrFail();

            $this->assertNotNull($faculty->college, "{$email} was not linked to a college.");
            $this->assertSame(
                $code,
                $faculty->college->code,
                "{$email} was linked to the wrong college."
            );
        }
    }

    public function test_the_legacy_business_administration_label_maps_to_cme(): void
    {
        // The pre-R3 UserSeeder spelled CME's department "College of Business
        // Administration" for faculty4. That is a synonym for CME, NOT a fourth
        // college — this is the assertion that keeps it from silently becoming
        // an unlinked faculty member (or worse, a wrong college).
        $this->seed(UserSeeder::class);
        $this->seed(CollegeSeeder::class);

        $faculty = Faculty::whereHas('user', fn ($q) => $q->where('email', 'faculty4@lnu.com'))
            ->firstOrFail();

        $faculty->update([
            'department' => 'College of Business Administration',
            'college_id' => null,
        ]);

        $this->runBackfill();

        $this->assertSame('CME', $faculty->fresh()->college->code);
    }

    public function test_backfill_never_overwrites_an_existing_link(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(CollegeSeeder::class);

        $coordinator = College::where('code', 'COE')->firstOrFail();
        $faculty = Faculty::whereHas('user', fn ($q) => $q->where('email', 'faculty1@lnu.com'))
            ->firstOrFail();

        // faculty1's department says CAS; deliberately pin them to COE and prove
        // the backfill respects it.
        $faculty->update(['college_id' => $coordinator->id]);

        $this->runBackfill();

        $this->assertSame($coordinator->id, $faculty->fresh()->college_id);
    }

    public function test_an_unrecognised_department_stays_unlinked(): void
    {
        $this->seed(CollegeSeeder::class);

        $faculty = Faculty::factory()->create([
            'department' => 'Institute of Something Else',
            'college_id' => null,
        ]);

        $this->runBackfill();

        // NULL is honest; a guessed college is not.
        $this->assertNull($faculty->fresh()->college_id);
    }

    /* ---------------------------------------------------------------------
     | Expertise
     | ------------------------------------------------------------------ */

    public function test_expertise_area_is_unique_per_faculty(): void
    {
        $faculty = Faculty::factory()->create();

        FacultyExpertise::create(['faculty_id' => $faculty->id, 'area' => 'Literacy & Reading']);

        $this->expectException(QueryException::class);

        FacultyExpertise::create(['faculty_id' => $faculty->id, 'area' => 'Literacy & Reading']);
    }

    public function test_two_faculty_may_share_the_same_area(): void
    {
        $a = Faculty::factory()->create();
        $b = Faculty::factory()->create();

        FacultyExpertise::create(['faculty_id' => $a->id, 'area' => 'Literacy & Reading']);
        FacultyExpertise::create(['faculty_id' => $b->id, 'area' => 'Literacy & Reading']);

        $this->assertSame(2, FacultyExpertise::where('area', 'Literacy & Reading')->count());
    }

    public function test_sync_expertise_replaces_the_set(): void
    {
        $faculty = Faculty::factory()->create();

        $faculty->syncExpertise(['Literacy & Reading', 'Remedial Instruction']);
        $this->assertEqualsCanonicalizing(
            ['Literacy & Reading', 'Remedial Instruction'],
            $faculty->expertise()->pluck('area')->all()
        );

        // Removing one and adding another in the same call is the multi-select's
        // normal edit behaviour.
        $faculty->syncExpertise(['Remedial Instruction', 'Sports Coaching']);
        $this->assertEqualsCanonicalizing(
            ['Remedial Instruction', 'Sports Coaching'],
            $faculty->expertise()->pluck('area')->all()
        );
    }

    public function test_sync_expertise_deduplicates_and_trims(): void
    {
        $faculty = Faculty::factory()->create();

        $faculty->syncExpertise(['  Literacy & Reading  ', 'Literacy & Reading', '', 'Sports Coaching']);

        $this->assertEqualsCanonicalizing(
            ['Literacy & Reading', 'Sports Coaching'],
            $faculty->expertise()->pluck('area')->all()
        );
        $this->assertSame(2, $faculty->expertise()->count());
    }

    public function test_sync_expertise_records_the_category(): void
    {
        $faculty = Faculty::factory()->create();

        $faculty->syncExpertise(['Literacy & Reading'], ['Literacy & Reading' => 'Literacy']);

        $this->assertSame('Literacy', $faculty->expertise()->first()->category);
    }

    public function test_sync_expertise_to_empty_clears_the_set(): void
    {
        $faculty = Faculty::factory()->create();

        $faculty->syncExpertise(['Literacy & Reading', 'Sports Coaching']);
        $faculty->syncExpertise([]);

        $this->assertSame(0, $faculty->expertise()->count());
    }

    public function test_deleting_a_faculty_removes_their_expertise(): void
    {
        $faculty = Faculty::factory()->create();
        $faculty->syncExpertise(['Literacy & Reading']);

        $faculty->forceDelete();

        $this->assertSame(0, FacultyExpertise::where('faculty_id', $faculty->id)->count());
    }

    public function test_the_canonical_vocabulary_is_available(): void
    {
        $options = config('smartcemes.expertise_options');

        $this->assertIsArray($options);
        $this->assertNotEmpty($options);
        $this->assertContains('Literacy & Reading', $options);
        $this->assertContains('Sports Coaching', $options);
    }

    /**
     * Run the R3a backfill directly.
     *
     * `RefreshDatabase` migrates an EMPTY database, so the migration's backfill
     * legitimately found nothing to do. Seeding afterwards therefore needs the
     * backfill re-run — which is exactly what `migrate:fresh --seed` needs too,
     * and is why the migration exposes this as a public, idempotent method
     * (rather than doing the work only inside `up()`).
     */
    private function runBackfill(): void
    {
        $migration = require database_path(
            'migrations/2026_09_25_000300_add_college_id_to_faculties_table.php'
        );

        $migration->backfill();
    }
}
