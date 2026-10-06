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
use App\Models\ProgramObjective;
use App\Models\RenderedHours;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Presentation data for the defense/demo: EXT-2026-007 "BUSOG" — a clean,
 * ongoing school-based supplementary feeding program with a 3-objective x
 * 3-activity results framework, 30 enrolled pupils, attendance on every
 * feeding cycle, six budget entries and rendered-hours rows.
 *
 * Results framework (all numeric, live-derived per 8.6):
 *   community_reach 30 / attendance_consistency 85% / budget_utilization 90%
 * → reach 30/30, participation 100%, consistency ~99%, utilization ~92%,
 *   PHP 2,600 cost per beneficiary. Activities carry satisfaction ratings only
 *   — no pre/post assessment scores, since knowledge gain does not apply to a
 *   feeding program (nutritional status is tracked via weighing/MUAC records).
 *
 * Idempotent: skips entirely when a BUSOG program already exists. The code
 * is allocated through the race-safe nextCode() sequence so the seeder also
 * works on databases where manual programs already hold earlier codes (on a
 * fresh seed it lands on EXT-2026-007).
 */
class FeedingProgramSeeder extends Seeder
{
    public const PROGRAM_TITLE = 'BUSOG: School-Based Supplementary Feeding & Nutrition Program';

    /**
     * 30 Grades 1-3 pupils of Brgy. Nula-tula enrolled in the feeding cycles.
     * Middle names follow the Filipino convention (mother's maiden surname);
     * surnames are common Tacloban/Leyte family names; contact numbers are
     * guardian-style PH mobile numbers.
     *
     * @var array<int, array{first: string, middle: string, last: string, age: int, sex: string, phone: string}>
     */
    private array $pupils = [
        ['first' => 'Althea Nicole', 'middle' => 'Cariaga', 'last' => 'Codilla', 'age' => 7, 'sex' => 'Female', 'phone' => '09175324861'],
        ['first' => 'Jhon Kenneth', 'middle' => 'Duran', 'last' => 'Bagunas', 'age' => 8, 'sex' => 'Male', 'phone' => '09218643759'],
        ['first' => 'Princess Marie', 'middle' => 'Devanadera', 'last' => 'Elizaga', 'age' => 6, 'sex' => 'Female', 'phone' => '09274815903'],
        ['first' => 'Christian Jay', 'middle' => 'Aniel', 'last' => 'Lucero', 'age' => 9, 'sex' => 'Male', 'phone' => '09396718245'],
        ['first' => 'Angelica Mae', 'middle' => 'Holganza', 'last' => 'Bachiller', 'age' => 7, 'sex' => 'Female', 'phone' => '09186273409'],
        ['first' => 'John Mark', 'middle' => 'Baybay', 'last' => 'Petinglay', 'age' => 8, 'sex' => 'Male', 'phone' => '09475820316'],
        ['first' => 'Kyle Andrea', 'middle' => 'Dacles', 'last' => 'Rosal', 'age' => 6, 'sex' => 'Female', 'phone' => '09358461927'],
        ['first' => 'Mark Anthony', 'middle' => 'Pepito', 'last' => 'Tangpuz', 'age' => 9, 'sex' => 'Male', 'phone' => '09263178450'],
        ['first' => 'Sophia Irene', 'middle' => 'Lipang', 'last' => 'Bathan', 'age' => 7, 'sex' => 'Female', 'phone' => '09482736105'],
        ['first' => 'Jomari', 'middle' => 'Catura', 'last' => 'Tampus', 'age' => 8, 'sex' => 'Male', 'phone' => '09751964382'],
        ['first' => 'Ashley Grace', 'middle' => 'Atuel', 'last' => 'Abenoja', 'age' => 7, 'sex' => 'Female', 'phone' => '09174658293'],
        ['first' => 'Kevin Paul', 'middle' => 'Mahinay', 'last' => 'Duran', 'age' => 9, 'sex' => 'Male', 'phone' => '09456231870'],
        ['first' => 'Mary Joy', 'middle' => 'Piamonte', 'last' => 'Capoquian', 'age' => 6, 'sex' => 'Female', 'phone' => '09397524816'],
        ['first' => 'Jericho', 'middle' => 'Luces', 'last' => 'Alcober', 'age' => 8, 'sex' => 'Male', 'phone' => '09216349785'],
        ['first' => 'Micah Ella', 'middle' => 'Petilla', 'last' => 'Montalban', 'age' => 7, 'sex' => 'Female', 'phone' => '09678513204'],
        ['first' => 'Jaymie Ann', 'middle' => 'Ocenar', 'last' => 'Piamonte', 'age' => 8, 'sex' => 'Female', 'phone' => '09203746851'],
        ['first' => 'Trisha Anne', 'middle' => 'Baguna', 'last' => 'Viste', 'age' => 8, 'sex' => 'Female', 'phone' => '09496825173'],
        ['first' => 'Kent Adrian', 'middle' => 'Meniano', 'last' => 'Hinlo', 'age' => 7, 'sex' => 'Male', 'phone' => '09193648275'],
        ['first' => 'Bea Angelica', 'middle' => 'Salazar', 'last' => 'Catura', 'age' => 9, 'sex' => 'Female', 'phone' => '09327618540'],
        ['first' => 'Wency James', 'middle' => 'Galvez', 'last' => 'Balbuena', 'age' => 6, 'sex' => 'Male', 'phone' => '09774539628'],
        ['first' => 'Erika Ysabelle', 'middle' => 'Ybañez', 'last' => 'Larrazabal', 'age' => 6, 'sex' => 'Female', 'phone' => '09175482639'],
        ['first' => 'Niño Angelo', 'middle' => 'Cabahug', 'last' => 'Sumaylo', 'age' => 8, 'sex' => 'Male', 'phone' => '09266384719'],
        ['first' => 'Lovelyn Grace', 'middle' => 'Tan', 'last' => 'Dagohoy', 'age' => 7, 'sex' => 'Female', 'phone' => '09471826539'],
        ['first' => 'Mark Dave', 'middle' => 'Eronico', 'last' => 'Gadin', 'age' => 9, 'sex' => 'Male', 'phone' => '09356482710'],
        ['first' => 'Cyrell Ann', 'middle' => 'Lim', 'last' => 'Tecson', 'age' => 8, 'sex' => 'Female', 'phone' => '09384756192'],
        ['first' => 'Ruel Francis', 'middle' => 'Uy', 'last' => 'Lago', 'age' => 7, 'sex' => 'Male', 'phone' => '09214587630'],
        ['first' => 'Zandra Mae', 'middle' => 'Alviz', 'last' => 'Palcon', 'age' => 7, 'sex' => 'Female', 'phone' => '09954376821'],
        ['first' => 'Vince Oliver', 'middle' => 'Elumba', 'last' => 'Lago', 'age' => 8, 'sex' => 'Male', 'phone' => '09184736205'],
        ['first' => 'Charlene Joy', 'middle' => 'Rosal', 'last' => 'Cagande', 'age' => 9, 'sex' => 'Female', 'phone' => '09273651894'],
        ['first' => 'Adrian Paul', 'middle' => 'Abellar', 'last' => 'Salazar', 'age' => 6, 'sex' => 'Male', 'phone' => '09466231857'],
    ];

    public function run(): void
    {
        if (ExtensionProject::withTrashed()->where('title', self::PROGRAM_TITLE)->exists()) {
            $this->command?->warn('  BUSOG feeding program already seeded — skipping.');

            return;
        }

        DB::transaction(function (): void {
            $admin = User::where('email', 'admin@lnu.com')->firstOrFail();
            $lead = Faculty::whereHas('user', fn ($q) => $q->where('email', 'faculty3@lnu.com'))->firstOrFail();
            $community = Community::where('name', 'Brgy. Nula-tula')->firstOrFail();
            $school = Community::where('name', 'Nula-Tula Elementary School')->firstOrFail();

            /* Phase R2 hierarchy. BUSOG is a school-based nutrition and health
               education intervention led by faculty3 (Nikko Villas, Environmental
               Science) — a CAS member, matching this project's CAS college. It
               sits under the Information, Communication & Education thrust, the
               same thrust as SENIOR CARE.
               Code is college-prefixed because this row is created after R2
               (R-Q4): migrated history keeps `EXT-…`, new rows get `CAS-…`. */
            $college = College::where('code', 'CAS')->first();
            $broadProgram = Program::where('title', 'Information, Communication & Education')->first();

            $program = ExtensionProject::create([
                'code' => ExtensionProject::nextCode($college?->code ?? 'CAS', 2026),
                'college_id' => $college?->id,
                'program_id' => $broadProgram?->id,
                'title' => self::PROGRAM_TITLE,
                'description' => 'Daily hot-meal feeding and nutrition education for undernourished Grades 1-3 pupils, paired with parent nutrition orientation.',
                'goals' => 'Reduce undernutrition among Grades 1-3 learners of Nula-Tula Elementary School and sustain healthy feeding habits at home.',
                'objectives' => 'Serve 30 undernourished pupils through the feeding cycles while sustaining session attendance and on-budget delivery.',
                'planned_start_date' => '2026-06-15',
                'planned_end_date' => '2026-12-18',
                'target_beneficiaries' => 30,
                'beneficiary_categories' => ['Student', 'Parent'],
                'allocated_budget' => 85000,
                'program_lead_id' => $lead->id,
                'partners' => ['Nula-Tula Elementary School', 'Barangay Council of Nula-tula'],
                'status' => 'ongoing',
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]);
            $program->communities()->sync([$community->id, $school->id]);

            $beneficiaryIds = [];
            foreach ($this->pupils as $pupil) {
                $beneficiaryIds[] = Beneficiary::create([
                    'first_name' => $pupil['first'],
                    'middle_name' => $pupil['middle'],
                    'last_name' => $pupil['last'],
                    'age' => $pupil['age'],
                    'gender' => $pupil['sex'],
                    'phone' => $pupil['phone'],
                    'barangay' => 'Nula-tula',
                    'municipality' => 'Tacloban City',
                    'province' => 'Leyte',
                    'community_id' => $community->id,
                    'beneficiary_category' => 'Student',
                    'occupation' => 'Student',
                ])->id;
            }
            $program->beneficiaries()->syncWithoutDetaching($beneficiaryIds);

            $activityRows = [
                [
                    'key' => 'kickoff',
                    'title' => 'Nutrition Baseline Assessment & Feeding Kick-off',
                    'description' => 'Weighing and MUAC screening of the 30 enrolled pupils to record baseline nutritional status, followed by the first hot-meal service.',
                    'start' => '2026-06-15', 'end' => '2026-06-15',
                    'start_time' => '08:00:00', 'end_time' => '12:00:00',
                    'status' => 'completed', 'pre' => null, 'post' => null, 'satisfaction' => 4.4,
                ],
                [
                    'key' => 'cycle1',
                    'title' => 'Feeding Cycle 1 — Daily Hot Meals & Nutrition Sessions',
                    'description' => 'Six weeks of daily hot meals with weekly nutrition education sessions and mid-cycle growth monitoring.',
                    'start' => '2026-07-06', 'end' => '2026-08-07',
                    'start_time' => '07:30:00', 'end_time' => '09:30:00',
                    'status' => 'completed', 'pre' => null, 'post' => null, 'satisfaction' => 4.6,
                ],
                [
                    'key' => 'cycle2',
                    'title' => 'Feeding Cycle 2 — Daily Hot Meals & Parent Nutrition Orientation',
                    'description' => 'Second six-week feeding cycle paired with a parent nutrition orientation; ongoing as of the current term.',
                    'start' => '2026-09-08', 'end' => '2026-10-16',
                    'start_time' => '07:30:00', 'end_time' => '09:30:00',
                    'status' => 'ongoing', 'pre' => null, 'post' => null, 'satisfaction' => 4.7,
                ],
            ];

            $activities = [];
            foreach ($activityRows as $row) {
                $activity = Activity::create([
                    'extension_project_id' => $program->id,
                    'title' => $row['title'],
                    'description' => $row['description'],
                    'planned_start_date' => $row['start'],
                    'planned_end_date' => $row['end'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'venue' => 'Nula-Tula Elementary School',
                    'status' => $row['status'],
                    'pre_assessment_score' => $row['pre'],
                    'post_assessment_score' => $row['post'],
                    'satisfaction_rating' => $row['satisfaction'],
                ]);
                $activity->faculty()->syncWithoutDetaching([$lead->id]);
                $activities[$row['key']] = $activity;
            }

            $this->seedAttendance($activities, $beneficiaryIds);

            $objectiveRows = [
                ['metric' => 'community_reach', 'target' => 30, 'unit' => 'pupils',
                    'objective' => 'Serve at least 30 undernourished Grades 1-3 pupils through the supplementary feeding cycles',
                    'evidence' => 'Weighing records and per-cycle feeding attendance sheets.'],
                ['metric' => 'attendance_consistency', 'target' => 85, 'unit' => '%',
                    'objective' => 'Maintain at least 85% attendance consistency across all feeding sessions',
                    'evidence' => 'Attendance imports per feeding cycle (hub Records modal).'],
                ['metric' => 'budget_utilization', 'target' => 90, 'unit' => '%',
                    'objective' => 'Achieve at least 90% budget utilization without over-allocation',
                    'evidence' => 'Itemized budget ledger per cycle; D7 over-allocation warning clear.'],
            ];

            foreach ($objectiveRows as $row) {
                ProgramObjective::create([
                    'extension_project_id' => $program->id,
                    'objective' => $row['objective'],
                    'kpi_metric' => $row['metric'],
                    'baseline_value' => 0,
                    'target_value' => $row['target'],
                    'actual_value' => null,
                    'unit' => $row['unit'],
                    'target_date' => '2026-12-18',
                    'status' => 'not_started',
                    'evidence_notes' => $row['evidence'],
                ]);
            }

            $budgetRows = [
                ['activity' => 'kickoff', 'item' => 'Weighing scale, MUAC tapes & screening supplies', 'amount' => 8800, 'date' => '2026-06-15'],
                ['activity' => 'cycle1', 'item' => 'Cycle 1 food supplies — rice (200 kg), viand & vegetables', 'amount' => 22400, 'date' => '2026-07-06'],
                ['activity' => 'cycle1', 'item' => 'Cooking fuel (LPG refills) & kitchen consumables — Cycle 1', 'amount' => 5600, 'date' => '2026-08-07'],
                ['activity' => 'cycle2', 'item' => 'Cycle 2 food supplies — rice (210 kg), viand & vegetables', 'amount' => 23500, 'date' => '2026-09-08'],
                ['activity' => 'cycle2', 'item' => 'Nutrition education materials, parent orientation kits & growth charts', 'amount' => 9400, 'date' => '2026-09-08'],
                ['activity' => null, 'item' => 'Transportation & logistics', 'amount' => 8300, 'date' => '2026-09-12'],
            ];

            foreach ($budgetRows as $index => $row) {
                BudgetUtilization::create([
                    'extension_project_id' => $program->id,
                    'activity_id' => $row['activity'] ? $activities[$row['activity']]->id : null,
                    'item_name' => $row['item'],
                    'amount' => $row['amount'],
                    'date_used' => $row['date'],
                    'receipt_reference' => 'REC-2026-'.str_pad((string) (301 + $index), 4, '0', STR_PAD_LEFT),
                ]);
            }

            RenderedHours::create([
                'faculty_id' => $lead->id,
                'activity_id' => $activities['kickoff']->id,
                'date' => '2026-06-15',
                'hours' => 4.0,
                'source' => 'auto',
                'status' => 'approved',
                'submitted_by' => $lead->user_id,
                'submitted_at' => '2026-06-16 09:00:00',
                'approved_by' => $admin->id,
                'approved_at' => '2026-06-17 10:00:00',
            ]);
            RenderedHours::create([
                'faculty_id' => $lead->id,
                'activity_id' => $activities['cycle1']->id,
                'date' => '2026-07-06',
                'hours' => 2.0,
                'source' => 'auto',
                'status' => 'pending',
                'submitted_by' => $lead->user_id,
                'submitted_at' => '2026-08-08 09:00:00',
                'remarks' => 'Auto-drafted 2.00 hrs — submitted for approval.',
            ]);

            $this->command?->info("  BUSOG feeding program seeded ({$program->code}: 3 objectives, 3 activities, 30 pupils, attendance, budget, rendered hours).");
        });
    }

    /**
     * One attendance date per activity (6.7/D13) with a deterministic plan:
     * kick-off all present; cycle 1 two late; cycle 2 two late and one
     * unrecorded (absent by omission, blank-cell semantics).
     *
     * @param  array<string, Activity>  $activities
     * @param  array<int, int>  $beneficiaryIds
     */
    private function seedAttendance(array $activities, array $beneficiaryIds): void
    {
        $plan = [
            'kickoff' => ['late' => [], 'skip' => []],
            'cycle1' => ['late' => [1, 16], 'skip' => []],
            'cycle2' => ['late' => [4, 20], 'skip' => [29]],
        ];

        foreach ($plan as $key => $spec) {
            $activity = $activities[$key];

            foreach ($beneficiaryIds as $index => $beneficiaryId) {
                if (in_array($index, $spec['skip'], true)) {
                    continue;
                }

                Attendance::create([
                    'activity_id' => $activity->id,
                    'beneficiary_id' => $beneficiaryId,
                    'attendance_date' => $activity->planned_start_date,
                    'status' => in_array($index, $spec['late'], true) ? 'late' : 'present',
                ]);
            }
        }
    }
}
