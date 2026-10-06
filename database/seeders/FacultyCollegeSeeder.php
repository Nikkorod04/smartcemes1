<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\Faculty;
use Illuminate\Database\Seeder;

/**
 * Link the faculty roster to colleges, and tag each member's expertise
 * (revision §4.5 / Phase R3a).
 *
 * WHY THIS IS A SEEDER AND NOT ONLY A MIGRATION:
 * the R3a migration backfills `faculties.college_id` from `department`, but
 * `migrate:fresh --seed` runs migrations against an EMPTY database — the roster
 * does not exist yet, so the migration has nothing to link. This seeder closes
 * that gap by re-running the (public, idempotent) backfill once `UserSeeder`
 * has created the faculty rows, then seeding the expertise the Faculty
 * Engagement board and Directory filters need.
 *
 * MUST RUN AFTER `UserSeeder` and `CollegeSeeder`.
 *
 * Idempotent: `syncExpertise()` replaces the set rather than appending, and the
 * backfill never overwrites an existing link.
 */
class FacultyCollegeSeeder extends Seeder
{
    /**
     * Seeded expertise per faculty email.
     *
     * Derived from each member's `specialization` (set in UserSeeder) so the demo
     * data is internally consistent — an Entrepreneurship specialist is tagged
     * with the livelihood areas, not literacy ones. Values are drawn from
     * config('smartcemes.expertise_options') so the filters have something real
     * to match; areas outside the canonical list are permitted (the vocabulary
     * is a floor, not a ceiling) but are avoided here.
     */
    private const EXPERTISE = [
        'faculty1@lnu.com' => [ // Carlo Sumile — Information Technology
            'Information Technology' => ['Digital Literacy', 'ICT Training'],
        ],
        'faculty2@lnu.com' => [ // Bianca Oledan — Reading Education
            'Literacy' => ['Literacy & Reading', 'Remedial Instruction', 'Mother-Tongue Pedagogy'],
        ],
        'faculty3@lnu.com' => [ // Nikko Villas — Environmental Science
            'Environment' => ['Environmental Conservation', 'Solid Waste Management', 'Disaster Preparedness'],
        ],
        'faculty4@lnu.com' => [ // Kent Naputo — Entrepreneurship
            'Livelihood' => ['Entrepreneurship', 'Business Planning', 'Financial Literacy'],
        ],
        'faculty5@lnu.com' => [ // Dr. Ramon L. Villamor — Research & Extension Management
            'Numeracy' => ['Assessment Design'],
            'Governance & Community' => ['Local Governance'],
        ],
        'faculty6@lnu.com' => [ // Dr. Cristina P. Manalo — Community Development
            'Governance & Community' => ['Community Organizing', 'Peace & Conflict Resolution'],
        ],
    ];

    /**
     * Status per faculty email.
     *
     * Mirrors the prototype's roster, which intentionally includes one member
     * On Leave so the board demonstrates the muted-outline treatment and the
     * "active load" reading. Everyone else is Active.
     */
    private const STATUSES = [
        'faculty3@lnu.com' => Faculty::STATUS_ON_LEAVE, // Nikko Villas
    ];

    public function run(): void
    {
        // 1. Re-run the migration's backfill now that the roster exists.
        $this->runCollegeBackfill();

        // 2. Statuses.
        foreach (self::STATUSES as $email => $status) {
            Faculty::whereHas('user', fn ($q) => $q->where('email', $email))
                ->update(['status' => $status]);
        }

        // 3. Seed expertise.
        foreach (self::EXPERTISE as $email => $categoryMap) {
            $faculty = Faculty::whereHas('user', fn ($q) => $q->where('email', $email))->first();

            if ($faculty === null) {
                continue;
            }

            $areas = [];
            $categories = [];

            foreach ($categoryMap as $category => $list) {
                foreach ($list as $area) {
                    $areas[] = $area;
                    $categories[$area] = $category;
                }
            }

            $faculty->syncExpertise($areas, $categories);
        }

        $linked = Faculty::whereNotNull('college_id')->count();
        $this->command?->info("Faculty linked to colleges: {$linked}");
    }

    /**
     * Re-run the R3a migration's backfill.
     *
     * The migration exposes `backfill()` publicly for exactly this reason — see
     * its docblock. Guarded so a missing college table cannot fatal the seed.
     */
    private function runCollegeBackfill(): void
    {
        if (College::count() === 0) {
            return;
        }

        $path = database_path('migrations/2026_09_25_000300_add_college_id_to_faculties_table.php');

        if (! file_exists($path)) {
            return;
        }

        require $path;

        // A migration file returns its anonymous class instance, so requiring
        // it yields a fresh instance whose backfill() is safe to call.
        $migration = require $path;

        if (method_exists($migration, 'backfill')) {
            $migration->backfill();
        }
    }
}
