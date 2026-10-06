<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CollegeSeeder::class,
            // Must follow UserSeeder + CollegeSeeder: links the roster to
            // colleges (re-running the R3a backfill) and tags expertise.
            FacultyCollegeSeeder::class,
            ProgramSeeder::class,
            Phase2Seeder::class,
            Phase3Seeder::class,
            FeedingProgramSeeder::class,
            // The Graduate School's flagship project (owner request 2026-09-25).
            // Must follow ProgramSeeder + Phase2Seeder: it resolves the ICE thrust
            // and a seeded community.
            GraduateProgramSeeder::class,
            // Demo cohorts for the six legacy projects (2026-09-28). Those projects
            // carried 2 beneficiaries each, so training hours rendered 0-4 against
            // targets of 200-640 (0.4 %-0.7 % attainment) — §16 G's remedy is to
            // ENROL COHORTS, not shrink the targets. Figures mirror seed-data.js.
            // Must run BEFORE R4TargetsSeeder: it sets the activities' trainors and
            // durations, and R4 only fills NULLs — and R4 must see this seeder's
            // attendance so it leaves the manual `participants` fallback NULL rather
            // than creating a second source for the same number.
            LegacyCohortSeeder::class,
            // Phase R4 (§4.4 / §4.7): back-fills the training-hours columns on
            // the demo activities and sets the annual targets. Deliberately LAST
            // — it needs the projects and activities the seeders above create,
            // and it only fills NULLs, so it never overrides anything they or
            // the Director have already set.
            R4TargetsSeeder::class,
            // Phase R6 / D-R10 (§4.6 / §7.2): the interagency referral catalogue.
            // Independent of every other seeder — it names external agencies, not
            // internal rows — but it must run BEFORE any AI analysis is
            // generated, because `PromptV2` injects it and can cite only from it.
            InteragencyAgencySeeder::class,
            // THE SAFE-ZONE PROJECT SET (owner decision 2026-10-05). Deliberately
            // LAST: it ARCHIVES every project the seeders above created and
            // replaces them with five simpler, plainly LNU-plausible ones. Every
            // earlier project seeder therefore still runs (so a restore from the
            // hub's Archived filter brings back a complete project, not a shell),
            // and `LegacyCohortSeeder` / `R4TargetsSeeder` become no-ops against
            // the live set by design.
            SafeZoneProjectSeeder::class,
        ]);
    }
}
