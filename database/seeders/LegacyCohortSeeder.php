<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\ExtensionProject;
use Illuminate\Database\Seeder;

/**
 * Gives the six legacy projects a real demo cohort.
 *
 * WHY THIS EXISTS
 * ---------------
 * Those projects carried **two beneficiaries each**, so `trainors x trainees x days`
 * rendered 0-4 hours against targets of 200-640 — **0.4 % to 0.7 % attainment**, which
 * reads as a broken figure rather than a thin one. `AI_HANDOFF.md` §16 G names the
 * remedy explicitly: ENROL COHORTS, do not shrink the targets. This seeder does that.
 *
 * WHERE THE NUMBERS COME FROM — `docs/prototype/assets/js/seed-data.js`
 * --------------------------------------------------------------------
 * That file is the authority (AI_HANDOFF §11 rule 3), and it already carries the
 * demo's intended cohort per project:
 *
 *   - `projects[].trainees`  -> the ENROLLED count for that project.
 *   - `activities[]`         -> per-activity `attendees` / `trainors` / `noOfDays`,
 *                               and those three satisfy the D-R3 formula EXACTLY
 *                               (e.g. 2 x 112 x 0.5 = 112). The prototype's own
 *                               `_check.cjs` [FORMULA] block asserts that sum.
 *
 * So this seeder mirrors both, and Laravel's rendered hours land where the prototype's
 * already do. The resulting attainment is deliberately UNEVEN (31 %, 149 %, 13 %, 12 %,
 * 0 %, 0 %) because that is the prototype's own story — see §16 G, "the demo ranking is
 * lopsided BY DECISION". Do not "even it out" here; that would contradict the contract.
 *
 * WHAT IS DELIBERATELY UNCHANGED
 * ------------------------------
 * - **The targets.** `annual_target_hours` is R4TargetsSeeder's business, not this one's.
 * - **BATANG MATINIK (EXT-2026-006)** — the prototype gives it no activities and 0
 *   attendees, so it stays at 0 hours. Its 0 % is correct, not missing data.
 * - **The 2 pre-existing beneficiaries per project** — they are kept (existing
 *   attendance references them) and the cohort is topped up around them.
 *
 * IDEMPOTENT: a project already at or above its target cohort is skipped, so a re-seed
 * never doubles a roster.
 */
class LegacyCohortSeeder extends Seeder
{
    /**
     * project code => [enrolled total, [[attendees, trainors, days], ...] per activity]
     *
     * Both columns are copied from seed-data.js: the first from `projects[].trainees`,
     * the second from that project's rows in the `activities` array, in date order.
     */
    private const PLAN = [
        'EXT-2026-001' => [186, [[112, 2, 0.5], [87, 2, 0.5], [0, 1, 0.5]]],
        'EXT-2026-002' => [171, [[154, 3, 0.5], [141, 3, 1.0]]],
        'EXT-2026-003' => [126, [[126, 1, 0.5]]],
        'EXT-2026-004' => [74, [[52, 2, 0.5]]],
        'EXT-2026-005' => [96, [[0, 2, 0.5], [0, 1, 0.5]]],
    ];

    /** 40 first names — enough, with 60 surnames, for 2,400 unique people. */
    private const FIRST = [
        'Jose', 'Maria', 'Juan', 'Ana', 'Pedro', 'Rosa', 'Antonio', 'Luz', 'Ramon', 'Elena',
        'Carlos', 'Teresa', 'Miguel', 'Carmen', 'Ricardo', 'Gloria', 'Eduardo', 'Lourdes', 'Fernando', 'Norma',
        'Roberto', 'Estrella', 'Alfredo', 'Corazon', 'Rogelio', 'Milagros', 'Ernesto', 'Remedios', 'Arturo', 'Perla',
        'Danilo', 'Amparo', 'Nestor', 'Leticia', 'Reynaldo', 'Aurora', 'Benigno', 'Consuelo', 'Marcelino', 'Rosario',
    ];

    /** 60 Tacloban / Leyte family names. */
    private const LAST = [
        'Abella', 'Alegre', 'Almario', 'Ampong', 'Anota', 'Arado', 'Astilla', 'Bacsal', 'Baclayon', 'Bagares',
        'Balderama', 'Bantilo', 'Barredo', 'Bascug', 'Bataller', 'Bautista', 'Bayona', 'Bernales', 'Boco', 'Borja',
        'Cabahug', 'Cabarles', 'Cajipo', 'Calderon', 'Caneda', 'Caparro', 'Caspe', 'Castillo', 'Castro', 'Cayanong',
        'Cernal', 'Colasito', 'Cuadra', 'Dacillo', 'Dagami', 'Daganta', 'Dala', 'Dela Cruz', 'Delantar', 'Diaz',
        'Dionaldo', 'Domingo', 'Ecleo', 'Enage', 'Enerlan', 'Espino', 'Fabian', 'Fermin', 'Flores', 'Gacosta',
        'Gadicho', 'Galapon', 'Garcia', 'Garrido', 'Gonzales', 'Guarin', 'Hilario', 'Ibañez', 'Jacinto', 'Javier',
    ];

    public function run(): void
    {
        foreach (self::PLAN as $code => [$cohortTarget, $activitySpecs]) {
            $project = ExtensionProject::where('code', $code)->first();

            if ($project === null) {
                continue;
            }

            $enrolled = $this->topUpCohort($project, $cohortTarget);
            $this->alignActivities($project, $activitySpecs);
            $this->seedAttendance($project, $enrolled, $activitySpecs);
        }
    }

    /**
     * Bring the project's roster up to the prototype's enrolled count, and return it.
     *
     * @return array<int, int> every enrolled beneficiary id, oldest first
     */
    private function topUpCohort(ExtensionProject $project, int $cohortTarget): array
    {
        $existing = $project->beneficiaries()->orderBy('beneficiaries.id')->pluck('beneficiaries.id')->all();
        $missing = $cohortTarget - count($existing);

        if ($missing <= 0) {
            return $existing;
        }

        $community = $project->communities->first();
        $categories = $project->beneficiary_categories ?: ['Other'];
        $created = [];

        for ($i = 0; $i < $missing; $i++) {
            // intdiv-based indexing keeps (first, last) unique for the first 2,400
            // people, which is what the dedup rule keys on (first + last + barangay).
            $first = self::FIRST[$i % count(self::FIRST)];
            $last = self::LAST[intdiv($i, count(self::FIRST)) % count(self::LAST)];

            $created[] = Beneficiary::create([
                'first_name' => $first,
                'middle_name' => null,
                'last_name' => $last,
                'age' => 18 + ($i % 47),
                'gender' => $i % 2 === 0 ? 'Female' : 'Male',
                'phone' => '0917'.str_pad((string) (2000000 + $i), 7, '0', STR_PAD_LEFT),
                'barangay' => $community?->name ?? 'Poblacion',
                'municipality' => $community?->municipality ?? 'Tacloban City',
                'province' => $community?->province ?? 'Leyte',
                'community_id' => $community?->id,
                'beneficiary_category' => $categories[$i % count($categories)],
                'occupation' => null,
            ])->id;
        }

        $project->beneficiaries()->syncWithoutDetaching($created);

        return array_merge($existing, $created);
    }

    /**
     * Set each activity's trainor count and duration to the prototype's figures.
     *
     * These two fields ARE the D-R3 formula's inputs, so they must match the prototype
     * or the hours will not. Activities are matched in planned-date order; if the
     * project has more activities than the prototype lists, the LAST spec repeats, and
     * any extra activity gets no attendance (so it adds no hours).
     *
     * @param  array<int, array{0:int,1:int,2:float}>  $specs
     */
    private function alignActivities(ExtensionProject $project, array $specs): void
    {
        $activities = $project->activities()->orderBy('planned_start_date')->orderBy('id')->get();

        foreach ($activities as $index => $activity) {
            $spec = $specs[$index] ?? $specs[count($specs) - 1];

            $activity->update([
                'trainors_snapshot' => $spec[1],
                'no_of_days' => $spec[2],
            ]);
        }
    }

    /**
     * Record attendance for each activity, hitting the prototype's attendee count exactly.
     *
     * The roster is walked with a rotating start offset, so a cohort of 186 spread over
     * two sessions of 112 and 87 COVERS all 186 rather than re-serving the same 112 —
     * which is also what makes the project's distinct-trainee count realistic.
     *
     * Counts must be hit exactly: one attendee fewer and the rendered hours move off the
     * prototype's figure. So nobody is skipped; variety comes from marking some 'late',
     * which still counts as served (`whereIn('status', ['present', 'late'])`).
     *
     * @param  array<int, int>  $enrolled
     * @param  array<int, array{0:int,1:int,2:float}>  $specs
     */
    private function seedAttendance(ExtensionProject $project, array $enrolled, array $specs): void
    {
        $activities = $project->activities()->orderBy('planned_start_date')->orderBy('id')->get();
        $cohort = count($enrolled);

        if ($cohort === 0 || $activities->isEmpty()) {
            return;
        }

        // This seeder OWNS these projects' attendance, so it CLEARS what the earlier
        // demo seeders left behind (2-4 rows each) before writing the prototype's
        // counts. Skipping activities that already had a row — the obvious guard —
        // would leave every project at ~0.5 % attainment, i.e. the exact problem this
        // seeder exists to fix. Clearing is safe because it runs on `migrate:fresh
        // --seed`, and it makes the seeder idempotent: the same rows every time.
        Attendance::whereIn('activity_id', $activities->pluck('id'))->delete();

        $offset = 0;

        foreach ($activities as $index => $activity) {
            $spec = $specs[$index] ?? null;

            // An activity beyond the prototype's list is left unrecorded.
            if ($spec === null || $spec[0] === 0) {
                continue;
            }

            $attendees = min($spec[0], $cohort);

            for ($n = 0; $n < $attendees; $n++) {
                Attendance::create([
                    'activity_id' => $activity->id,
                    'beneficiary_id' => $enrolled[($offset + $n) % $cohort],
                    'attendance_date' => $activity->planned_start_date,
                    'status' => $n % 12 === 11 ? 'late' : 'present',
                ]);
            }

            $offset += $attendees;
        }
    }
}
