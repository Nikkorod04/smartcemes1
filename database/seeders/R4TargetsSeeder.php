<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\ExtensionProject;
use App\Models\UniversityTarget;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Phase R4 demo data (revision §4.4 / §4.7, D-R3 / D-R4 / D-R5).
 *
 * The prototype's numbers are the acceptance target:
 *
 *     2 trainors x 112 trainees x 0.5 day =  112 hrs
 *     3 trainors x 141 trainees x 1   day =  423 hrs
 *     1 trainor  x 126 trainees x 0.5 day =   63 hrs
 *
 * so this seeder makes the demo database produce exactly that shape: every
 * activity carries `no_of_days` (half days included) and `participants`, the
 * per-project annual targets exist, and ONE university pool sits above them.
 *
 * Why a SEPARATE seeder rather than editing FeedingProgramSeeder:
 *  - FeedingProgramSeeder is already-shipped seed history. Re-running it on an
 *    existing database is a no-op by design (it skips when BUSOG exists), so
 *    adding columns there would silently leave existing demo data untouched.
 *    This seeder is idempotent and back-fills instead.
 *  - Historical migrations are immutable; the same discipline applies to seed
 *    history. The R4 columns arrive here as a forward change.
 *
 * Idempotent on two axes: the university target is upserted by year, and each
 * activity is only filled where `no_of_days` is still NULL, so re-running never
 * overwrites a figure the Director has since edited by hand.
 */
class R4TargetsSeeder extends Seeder
{
    /**
     * The demo AY and its university pool, mirroring the prototype seed
     * (`universityTargets[2026]` = 2 500 hrs / ₱668 000 / AY 2026-2027).
     *
     * These are DEMO figures, not invented requirements: they exist so the
     * attainment bars have a denominator on a fresh database. The Director
     * replaces them on the University Targets page.
     */
    private const TARGET_YEAR = 2026;

    private const TARGET_HOURS = 2500;

    private const TARGET_BUDGET = 668000;

    /**
     * Per-activity `no_of_days` / `participants` / `trainors_snapshot`, keyed by
     * project title fragment and activity title fragment.
     *
     * `participants` is only meaningful as the R-Q1 MANUAL fallback — where an
     * activity already has imported attendance, the service reads that instead
     * and the manual figure is never used. Values are chosen so the rendered
     * hours land on the prototype's worked example.
     *
     * @var array<int, array{project: string, activity: string, days: float, participants: int, trainors: ?int}>
     */
    private array $trainingRows = [
        [
            'project' => 'BUSOG',
            'activity' => 'Nutrition Baseline',
            'days' => 0.5,
            'participants' => 30,
            'trainors' => 2,
        ],
        [
            'project' => 'BUSOG',
            'activity' => 'Feeding Cycle 1',
            'days' => 1.0,
            'participants' => 30,
            'trainors' => 3,
        ],
        [
            'project' => 'BUSOG',
            'activity' => 'Feeding Cycle 2',
            'days' => 0.5,
            'participants' => 30,
            'trainors' => 1,
        ],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $admin = User::where('email', 'admin@lnu.com')->first();

            $this->seedUniversityTarget($admin);
            $this->backfillActivityTrainingFields();
            $this->seedProjectTargets();
        });
    }

    /** ONE annual pool per AY — the SOLE input to the annual target (§2.2B). */
    private function seedUniversityTarget(?User $admin): void
    {
        $target = UniversityTarget::forYear(self::TARGET_YEAR);

        if ($target !== null) {
            $this->command?->warn(sprintf(
                '  University target for AY %d-%d already set (%s hrs) — leaving it untouched.',
                self::TARGET_YEAR, self::TARGET_YEAR + 1,
                $target->annual_target_hours === null ? 'no' : number_format((float) $target->annual_target_hours)
            ));

            return;
        }

        UniversityTarget::create([
            'year' => self::TARGET_YEAR,
            'annual_target_hours' => self::TARGET_HOURS,
            'annual_target_budget' => self::TARGET_BUDGET,
            'notes' => 'Demo baseline for AY '.self::TARGET_YEAR.'-'.(self::TARGET_YEAR + 1)
                .'. Replace with the CESO-approved commitment on the University Targets page.',
            'updated_by' => $admin?->id,
        ]);

        $this->command?->info(sprintf(
            '  University target seeded: AY %d-%d = %s hrs / PHP %s.',
            self::TARGET_YEAR, self::TARGET_YEAR + 1,
            number_format(self::TARGET_HOURS), number_format(self::TARGET_BUDGET)
        ));
    }

    /**
     * Fill `no_of_days` / `participants` / `trainors_snapshot` on the demo
     * activities that still have them NULL.
     *
     * `no_of_days` is the trigger that flips
     * `FacultyContributionService::trainingIsMeasurable()` on (§14.7), so this
     * method is what makes R3c's two dormant blocks light up.
     */
    private function backfillActivityTrainingFields(): void
    {
        $filled = 0;
        $skipped = 0;

        foreach ($this->trainingRows as $row) {
            $project = ExtensionProject::where('title', 'like', '%'.$row['project'].'%')->first();

            if ($project === null) {
                continue;
            }

            $activity = $project->activities()
                ->where('title', 'like', '%'.$row['activity'].'%')
                ->first();

            if ($activity === null) {
                continue;
            }

            // Never overwrite a hand-entered figure: only NULLs are back-filled.
            if ($activity->no_of_days !== null) {
                $skipped++;

                continue;
            }

            // `participants` is the manual FALLBACK (R-Q1). Where attendance was
            // already imported for this activity we deliberately leave it NULL:
            // writing a manual figure beside real attendance would create two
            // sources for one number, and the service would silently prefer the
            // import — leaving a stale figure in the DB that looks authoritative.
            $hasAttendance = Attendance::where('activity_id', $activity->id)->exists();

            $activity->update([
                'no_of_days' => $row['days'],
                'participants' => $hasAttendance ? null : $row['participants'],
                'trainors_snapshot' => $row['trainors'],
            ]);

            $filled++;
        }

        if ($filled > 0 || $skipped > 0) {
            $this->command?->info("  Training-hours fields back-filled: {$filled} activit".($filled === 1 ? 'y' : 'ies')." ({$skipped} already set).");
        }
    }

    /**
     * Per-project annual HOURS targets (planning figures for their own project —
     * never summed to produce the university pool).
     *
     * Budget is deliberately NOT seeded. A project has no annual budget target
     * (owner decision 2026-09-26) — its allocation is the only budget figure, so
     * `annual_target_budget` is retained but unread. Writing one here would make
     * an inert column look like a live target and invite someone to read it.
     *
     * Only set where absent, so a Director-entered target survives a re-seed.
     */
    private function seedProjectTargets(): void
    {
        // Hours mirror what the activities actually render, rounded UP to a
        // plausible planning figure, so the demo bars sit below 100% rather
        // than exactly on it (an exactly-100% demo hides the at-risk tone).
        //
        // The six legacy projects take their targets VERBATIM from the
        // prototype's `seed-data.js` (`trainingHoursTarget`, matched by its
        // `legacyCode`). Inventing different numbers here would contradict the
        // visual contract, which AI_HANDOFF §11 rule 3 forbids.
        //
        // NOTE THE CONSEQUENCE, and do not "fix" it by lowering these: those six
        // projects render only 0–4 hours in the seeded demo, because their
        // beneficiary enrolment is deliberately thin (§16 G — five projects have
        // 2 beneficiaries each and BATANG MATINIK has none). Their pages will
        // therefore read ~0–1% of target. That is the honest figure. The real
        // remedy is enrolling cohorts, not shrinking the target.
        $plan = [
            'LITRAWIYA' => ['hours' => 640.0],       // seed-data.js EXT-2026-001
            'HANDA' => ['hours' => 440.0],           // seed-data.js EXT-2026-002
            'KABUHIAN' => ['hours' => 480.0],        // seed-data.js EXT-2026-003
            'e-LITERACY' => ['hours' => 420.0],      // seed-data.js EXT-2026-004
            'SENIOR CARE' => ['hours' => 300.0],     // seed-data.js EXT-2026-005
            'BATANG MATINIK' => ['hours' => 200.0],  // seed-data.js EXT-2026-006
            'BUSOG' => ['hours' => 150.0],           // Laravel-only project, no prototype counterpart
        ];

        $set = 0;

        foreach ($plan as $fragment => $figures) {
            $project = ExtensionProject::where('title', 'like', '%'.$fragment.'%')->first();

            if ($project === null) {
                continue;
            }

            if ($project->annual_target_hours === null) {
                $project->annual_target_hours = $figures['hours'];
                $project->save();
                $set++;
            }
        }

        if ($set > 0) {
            $this->command?->info("  Project annual targets seeded: {$set} project".($set === 1 ? '' : 's').'.');
        }
    }
}
