<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

/**
 * The six broad extension programs (revision §3 / Phase R1).
 *
 * Each program is a VERBATIM CESO thrust, tagged with its pillar. These are
 * the six rows the whole revised hierarchy hangs from:
 *
 *     College → Program → Project → Activity
 *
 * Two CESO thrusts are deliberately EXCLUDED (§3) — documented here so the
 * exclusion reads as a decision, not an oversight:
 *   - Management and Leadership Management — internal/staff capability.
 *   - Special Institute and Teacher Training Program — professional
 *     development, not community extension.
 *
 * The three Community Outreach categories (food/health, medical missions,
 * clean-and-green) are NOT programs: they are reclassified as interagency
 * referral categories (§3 / §7.2) and are seeded in Phase R6.
 *
 * No program-level training-hours target exists (§2.2B / D-C5) — the annual
 * target belongs to the university pool and to individual projects. The
 * `annual_target_*` columns are present for admin planning only and are left
 * NULL here.
 *
 * Idempotent: safe to re-run.
 */
class ProgramSeeder extends Seeder
{
    /**
     * Verbatim CESO thrusts, tagged with pillar (§3).
     */
    private const PROGRAMS = [
        [
            'title' => 'Literacy, Numeracy & Language',
            'pillar' => 'social',
            'ceso_thrust' => 'Literacy, Numeracy & Language Enhancement',
            'description' => 'Reading, numeracy and language enhancement delivered with partner schools and community learning centres.',
        ],
        [
            'title' => 'Information, Communication & Education',
            'pillar' => 'social',
            'ceso_thrust' => 'Information, Communication & Education',
            'description' => 'Digital literacy, information access and continuing-education support for community members.',
        ],
        [
            'title' => 'Cultural Development',
            'pillar' => 'social',
            'ceso_thrust' => 'Cultural Development',
            'description' => 'Preservation and promotion of local culture, arts and heritage within partner communities.',
        ],
        [
            'title' => 'Physical Fitness & Sports Development',
            'pillar' => 'social',
            'ceso_thrust' => 'Physical Fitness & Sports Development',
            'description' => 'Community sports, physical fitness and youth development activities.',
        ],
        [
            'title' => 'Livelihood, Technical & Business Management',
            'pillar' => 'economic',
            'ceso_thrust' => 'Livelihood, Technical and Business Management',
            'description' => 'Livelihood skills, technical training and enterprise management for community households.',
        ],
        [
            'title' => 'Environmental Conservation & Disaster Preparedness',
            'pillar' => 'environmental',
            'ceso_thrust' => 'Environmental Conservation and Disaster Preparedness',
            'description' => 'Environmental protection, disaster risk reduction and community resilience training.',
        ],
    ];

    public function run(): void
    {
        $year = (int) now()->format('Y');
        $seq = 0;

        foreach (self::PROGRAMS as $row) {
            $seq++;

            /* Code is deterministic so re-seeding is idempotent: PROG-{year}-{seq}
               follows the declaration order above rather than the sequence table.
               New programs created through the UI use Program::nextCode(). */
            Program::updateOrCreate(
                ['code' => sprintf('PROG-%d-%03d', $year, $seq)],
                [
                    'title' => $row['title'],
                    'pillar' => $row['pillar'],
                    'ceso_thrust' => $row['ceso_thrust'],
                    'description' => $row['description'],
                    'status' => 'active',
                ]
            );
        }

        $this->command?->info('Programs seeded: '.count(self::PROGRAMS).' CESO thrusts');
    }
}
