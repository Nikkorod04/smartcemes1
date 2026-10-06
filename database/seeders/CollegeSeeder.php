<?php

namespace Database\Seeders;

use App\Models\College;
use App\Models\Faculty;
use Illuminate\Database\Seeder;

/**
 * The college set (revision §1.1 / Phase R1; four rows since 2026-09-25).
 *
 * THE SET IS FIXED — CAS, COE, CME and the Graduate School — and this seeder is
 * its ONLY owner. The UI has no college CRUD (owner request 2026-09-25), so a
 * name, description or coordinator is corrected HERE, not in the app.
 * `updateOrCreate` re-asserts the set on every seed.
 *
 * Each college has a named Extension Coordinator — the natural owner of a
 * college's extension work and a candidate for the Project Lead role. The
 * coordinator is resolved from the faculty roster by matching the seeded
 * `faculty.department`, which already holds the full official college name,
 * so the mapping is deterministic. The Graduate School's coordinator stays NULL
 * until its faculty are seeded.
 *
 * Idempotent: safe to re-run (`migrate:fresh --seed` or `db:seed`).
 */
class CollegeSeeder extends Seeder
{
    /**
     * code => [name, short_name, description, coordinator email — NULL when unassigned]
     */
    private const COLLEGES = [
        'CAS' => [
            'name' => 'College of Arts and Sciences',
            'short_name' => 'Arts and Sciences',
            'description' => 'Houses Information Technology, Social Work and Communication programmes; leads the literacy, numeracy and language thrust.',
            'coordinator' => 'faculty1@lnu.com',
        ],
        'COE' => [
            'name' => 'College of Education',
            'short_name' => 'Education',
            'description' => 'Houses the teacher-education programmes; leads the information, communication and education thrust.',
            'coordinator' => 'faculty2@lnu.com',
        ],
        'CME' => [
            'name' => 'College of Management and Entrepreneurship',
            'short_name' => 'Management & Entrepreneurship',
            'description' => 'Houses Hospitality, Tourism and Entrepreneurship programmes; leads the livelihood, technical and business management thrust.',
            'coordinator' => 'faculty4@lnu.com',
        ],
        /* The Graduate School (owner request 2026-09-25). It is not a college in
           the institutional sense — it is the unit that runs the master's and
           doctoral programmes — but it delivers extension work, so it sits at the
           same level in the hierarchy. Its coordinator is Dr. Ramon L. Villamor
           (faculty5), seeded with the graduate starter set. Its official seal
           landed 2026-09-27 (`public/gs.png` → `img/colleges/grad.png`), so all
           four colleges now render a seal and the code crest is only a fallback. */
        'GRAD' => [
            'name' => 'Graduate School',
            'short_name' => 'Graduate School',
            'description' => 'Runs the master\'s and doctoral programmes; leads research-based extension work and graduate capability building.',
            'coordinator' => 'faculty5@lnu.com',
        ],
    ];

    public function run(): void
    {
        foreach (self::COLLEGES as $code => $data) {
            // GRAD ships with a NULL coordinator until its faculty are seeded.
            $coordinator = $data['coordinator'] === null
                ? null
                : Faculty::whereHas('user', fn ($q) => $q->where('email', $data['coordinator']))->first();

            College::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $data['name'],
                    'short_name' => $data['short_name'],
                    'description' => $data['description'],
                    'extension_coordinator_id' => $coordinator?->id,
                    'status' => 'active',
                ]
            );
        }

        $this->command?->info('Colleges seeded: '.implode(', ', array_keys(self::COLLEGES)));
    }
}
