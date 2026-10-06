<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\BudgetUtilization;
use App\Models\College;
use App\Models\Community;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The Graduate School's flagship extension project (owner request 2026-09-25).
 *
 * One project so the Graduate School is not an empty card. It is deliberately
 * filed under the **Information, Communication & Education** thrust rather than
 * a new one: a thrust is university-wide and SPANS colleges, so a graduate-led
 * project sitting beside the CAS ones is the model working as designed — and it
 * restores the only visible example of that span in the demo data.
 *
 * Created AFTER R2, so the code is college-prefixed (R-Q4): `GRAD-2026-001`.
 *
 * WHY IT SEEDS A FULL COHORT, NOT JUST A PROJECT
 * ---------------------------------------------
 * A project with no activities reads 0/0, and — less obviously — a project with
 * activities but no ATTENDANCE still reads **0 trainees**, because the Trainees
 * tile is a DISTINCT BENEFICIARY count from attendance, not the `participants`
 * fallback (R-Q1: `attendance → participants → 0`). `participants` only feeds the
 * HOURS formula. So the cohort below exists to make the tile honest.
 *
 * `participants` is kept EQUAL to the number of attendees on each activity, so
 * the hours do not shift when attendance lands — imported attendance REPLACES
 * the fallback, and a mismatch would make the computed hours drop.
 *
 * Both Graduate School faculty are assigned to both activities, so the project's
 * distinct-trainor count (2) agrees with each activity's `trainors_snapshot`.
 *
 * MUST RUN AFTER `UserSeeder`, `CollegeSeeder`, `ProgramSeeder` and
 * `Phase2Seeder` (it resolves a college, a thrust, a lead and a community).
 *
 * Idempotent: skips when the project already exists.
 */
class GraduateProgramSeeder extends Seeder
{
    private const PROGRAM_TITLE = 'PANDAY: Community Research & Documentation Capability Building';

    private const LEAD_EMAIL = 'faculty5@lnu.com';

    private const CO_LEAD_EMAIL = 'faculty6@lnu.com';

    private const COMMUNITY = 'Brgy. San Jose';

    private const VENUE = 'Brgy. San Jose Multi-Purpose Hall';

    private const BARANGAY = 'San Jose';

    /**
     * 15 trainees × 2 trainors × 1.0 day per session, two sessions = 60 h against
     * a 120 h annual target (50%, so the bar reads as in-progress rather than a
     * suspiciously exact 100%).
     */
    private const ACTIVITIES = [
        [
            'title' => 'Community Research Design & Data Gathering Workshop',
            'description' => 'Two-track training for barangay officials and volunteers on research design, survey instruments and ethical data gathering.',
            'date' => '2026-08-10',
            'start_time' => '08:00:00', 'end_time' => '17:00:00',
            'days' => 1.0, 'trainors' => 2,
            'status' => 'completed', 'pre' => 52.0, 'post' => 78.0, 'satisfaction' => 4.6,
            /* One late, one absent — so the attendance screen is not uniformly green. */
            'late' => 1, 'absent' => 1,
        ],
        [
            'title' => 'Barangay Profile Documentation Clinic',
            'description' => 'Hands-on clinic producing the barangay community profile: tabulation, mapping and report writing with graduate-supervisor review.',
            'date' => '2026-09-21',
            'start_time' => '08:00:00', 'end_time' => '17:00:00',
            'days' => 1.0, 'trainors' => 2,
            'status' => 'completed', 'pre' => 58.0, 'post' => 81.0, 'satisfaction' => 4.7,
            'late' => 0, 'absent' => 1,
        ],
    ];

    /**
     * The budget ledger — ₱18,900 of the ₱42,000 allocation (45%), so the D7
     * over-allocation warning stays clear and the utilization bar is meaningful.
     *
     * `activity` indexes ACTIVITIES, or NULL for an expense that belongs to the
     * project rather than to one session (§6.12 allows both).
     */
    private const BUDGET = [
        ['item' => 'Workshop materials, modules & printing', 'amount' => 9400.0, 'date' => '2026-08-10', 'activity' => 0],
        ['item' => 'Documentation clinic supplies & venue snacks', 'amount' => 5900.0, 'date' => '2026-09-21', 'activity' => 1],
        ['item' => 'Community profile printing & binding', 'amount' => 3600.0, 'date' => '2026-09-30', 'activity' => null],
    ];

    /** [first, middle initial, last, age, sex, category] */
    private const COHORT = [
        ['Rosalinda', 'M.', 'Abella', 46, 'Female', 'Barangay Worker'],
        ['Edgardo', 'P.', 'Bacsain', 52, 'Male', 'Barangay Worker'],
        ['Marites', 'L.', 'Cabarles', 39, 'Female', 'Parent'],
        ['Joel', 'R.', 'Dacillo', 44, 'Male', 'Barangay Worker'],
        ['Analiza', 'T.', 'Ecleo', 35, 'Female', 'Parent'],
        ['Rodolfo', 'S.', 'Fabregas', 57, 'Male', 'Barangay Worker'],
        ['Mylene', 'B.', 'Gaditano', 31, 'Female', 'Parent'],
        ['Arnel', 'C.', 'Hinlo', 42, 'Male', 'Barangay Worker'],
        ['Jenny Rose', 'V.', 'Ibarra', 28, 'Female', 'Parent'],
        ['Wilfredo', 'A.', 'Jarabejo', 49, 'Male', 'Barangay Worker'],
        ['Lorna', 'D.', 'Kalaw', 37, 'Female', 'Parent'],
        ['Bernardo', 'G.', 'Lagumbay', 55, 'Male', 'Barangay Worker'],
        ['Sheila', 'N.', 'Magbanua', 33, 'Female', 'Parent'],
        ['Reynaldo', 'H.', 'Nacario', 47, 'Male', 'Barangay Worker'],
        ['Cristina', 'E.', 'Obenza', 40, 'Female', 'Parent'],
    ];

    public function run(): void
    {
        if (ExtensionProject::withTrashed()->where('title', self::PROGRAM_TITLE)->exists()) {
            $this->command?->warn('  PANDAY already seeded — skipping.');

            return;
        }

        $college = College::where('code', 'GRAD')->first();
        $broadProgram = Program::where('title', 'Information, Communication & Education')->first();
        $lead = Faculty::whereHas('user', fn ($q) => $q->where('email', self::LEAD_EMAIL))->first();
        $coLead = Faculty::whereHas('user', fn ($q) => $q->where('email', self::CO_LEAD_EMAIL))->first();
        $community = Community::where('name', self::COMMUNITY)->first();
        $admin = User::where('email', 'admin@lnu.com')->first();

        /* Fail soft: a standalone `db:seed --class=GraduateProgramSeeder` against
           a bare database must warn, not throw. */
        if ($college === null || $broadProgram === null || $lead === null) {
            $this->command?->warn('  PANDAY skipped — the GRAD college, the ICE thrust or its lead is missing.');

            return;
        }

        DB::transaction(function () use ($college, $broadProgram, $lead, $coLead, $community, $admin): void {
            $project = ExtensionProject::create([
                'code' => ExtensionProject::nextCode($college->code, 2026),
                'college_id' => $college->id,
                'program_id' => $broadProgram->id,
                'title' => self::PROGRAM_TITLE,
                'description' => 'Graduate-supervised training in community research, data gathering and documentation for barangay officials and volunteers.',
                'goals' => 'Leave the barangay able to plan, document and report its own development work without outside help.',
                'objectives' => 'Train barangay officials and volunteers in research design, data gathering and documentation, and produce one community profile.',
                'planned_start_date' => '2026-08-03',
                'planned_end_date' => '2026-12-18',
                'target_beneficiaries' => count(self::COHORT),
                'beneficiary_categories' => ['Barangay Worker', 'Parent'],
                'allocated_budget' => 42000,
                'annual_target_hours' => 120.0,
                'annual_target_budget' => 42000.0,
                'program_lead_id' => $lead->id,
                'partners' => ['Barangay Council of San Jose'],
                'status' => 'ongoing',
                'created_by' => $admin?->id,
                'updated_by' => $admin?->id,
            ]);

            if ($community !== null) {
                $project->communities()->sync([$community->id]);
            }

            // ---- The cohort -------------------------------------------------
            $beneficiaryIds = [];

            foreach (self::COHORT as [$first, $middle, $last, $age, $sex, $category]) {
                $beneficiaryIds[] = Beneficiary::create([
                    'first_name' => $first,
                    'middle_name' => $middle,
                    'last_name' => $last,
                    'age' => $age,
                    'gender' => $sex,
                    'phone' => '09123456789',
                    'barangay' => self::BARANGAY,
                    'municipality' => 'Tacloban City',
                    'province' => 'Leyte',
                    'community_id' => $community?->id,
                    'beneficiary_category' => $category,
                    'occupation' => $category,
                ])->id;
            }

            $project->beneficiaries()->syncWithoutDetaching($beneficiaryIds);

            // ---- Activities + attendance ------------------------------------
            $trainorIds = array_values(array_filter([$lead?->id, $coLead?->id]));
            $activityIds = [];

            foreach (self::ACTIVITIES as $index => $row) {
                $attendees = count($beneficiaryIds) - $row['absent'];

                $activity = Activity::create([
                    'extension_project_id' => $project->id,
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'planned_start_date' => $row['date'],
                    'planned_end_date' => $row['date'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'venue' => self::VENUE,
                    'status' => $row['status'],
                    'no_of_days' => $row['days'],
                    /* Equal to the attendee count ON PURPOSE: imported attendance
                       replaces this fallback, so a mismatch would move the hours. */
                    'participants' => $attendees,
                    'trainors_snapshot' => $row['trainors'],
                    'pre_assessment_score' => $row['pre'],
                    'post_assessment_score' => $row['post'],
                    'satisfaction_rating' => $row['satisfaction'],
                ]);

                if ($trainorIds !== []) {
                    $activity->faculty()->syncWithoutDetaching($trainorIds);
                }

                /* One absentee per session, at a position that SHIFTS per session.
                   Marking the same person absent twice would make the project's
                   distinct-trainee count read one below the cohort — and using a
                   range instead of a single slot would silently drop a second
                   attendee, which also moves the computed hours. */
                $absentAt = count($beneficiaryIds) - 1 - $index;

                foreach ($beneficiaryIds as $position => $beneficiaryId) {
                    $status = match (true) {
                        $position < $row['late'] => 'late',
                        $position === $absentAt => 'absent',
                        default => 'present',
                    };

                    Attendance::create([
                        'activity_id' => $activity->id,
                        'beneficiary_id' => $beneficiaryId,
                        'attendance_date' => $row['date'],
                        'status' => $status,
                    ]);
                }

                $activityIds[$index] = $activity->id;
            }

            // ---- Budget ledger ----------------------------------------------
            foreach (self::BUDGET as $entry) {
                BudgetUtilization::create([
                    'extension_project_id' => $project->id,
                    'activity_id' => $entry['activity'] === null ? null : $activityIds[$entry['activity']],
                    'item_name' => $entry['item'],
                    'amount' => $entry['amount'],
                    'date_used' => $entry['date'],
                ]);
            }
        });

        $this->command?->info(sprintf(
            '  PANDAY seeded (GRAD-2026-001: %d activities, %d trainees, PHP 18,900 utilized).',
            count(self::ACTIVITIES),
            count(self::COHORT)
        ));
    }
}
