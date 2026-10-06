<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * New: `faculties.college_id` (revision §5 Phase R3 step 1 / §4.5).
 *
 * Puts each faculty member under a college so the Faculty Management module can
 * filter and rank by college, and so the faculty profile can show the
 * "load by college" split.
 *
 * BACKFILL RULE (revision §5 R3 step 1): derived from `department`. The seeded
 * `department` values are already full college names, so the mapping is
 * deterministic rather than a guess:
 *
 *   "College of Arts and Sciences"                  -> CAS
 *   "College of Education"                          -> COE
 *   "College of Management and Entrepreneurship"    -> CME
 *
 * The Phase2/User seeders spell the third one "College of Business
 * Administration" for faculty4 (Kent Naputo, Entrepreneurship) — that is a
 * legacy label for CME, not a fourth college, so it is mapped explicitly below.
 * See the R3a note in revisions.md / the memory log for the evidence.
 *
 * The column stays NULLABLE: faculty without a recognisable department keep
 * NULL rather than being forced into a wrong college, and admins can set it by
 * hand on the Faculty Directory page.
 */
return new class extends Migration
{
    /**
     * department string (normalised, lowercased) => college code.
     */
    private const DEPARTMENT_TO_COLLEGE = [
        'college of arts and sciences' => 'CAS',
        'arts and sciences' => 'CAS',
        'cas' => 'CAS',

        'college of education' => 'COE',
        'education' => 'COE',
        'coe' => 'COE',

        'college of management and entrepreneurship' => 'CME',
        'management and entrepreneurship' => 'CME',
        'cme' => 'CME',

        // The Graduate School (added 2026-09-25) — a fourth unit at the same
        // level. Without these entries its faculty link to NO college, and the
        // backfill silently leaves them NULL.
        'graduate school' => 'GRAD',
        'graduate studies' => 'GRAD',
        'grad' => 'GRAD',

        // Legacy label used by the seeders for faculty4 — a synonym for CME,
        // NOT a distinct college. Kept explicit so the reason is visible.
        'college of business administration' => 'CME',
        'business administration' => 'CME',
    ];

    public function up(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            // `after('employee_id')` keeps the column adjacent to the other
            // identity fields — cosmetic, but it keeps the table readable.
            $table->foreignId('college_id')
                ->nullable()
                ->after('employee_id')
                ->constrained('colleges')
                ->nullOnDelete();

            // Faculty status (revision §5 R3 step 3: "filters (college,
            // expertise, active/inactive)"). Vocabulary: active | on_leave |
            // inactive — see config('smartcemes.faculty_statuses').
            $table->string('status', 16)->default('active')->after('position');
        });

        $this->backfill();
    }

    /**
     * Map every faculty row to its college by department. Safe to run when the
     * colleges table is empty or missing (the FK simply stays NULL).
     *
     * PUBLIC and idempotent on purpose: `migrate:fresh --seed` runs migrations
     * against an empty database, so when the seeders create the faculty rows
     * afterwards there is nothing left to link them. The `FacultyCollegeSeeder`
     * (and tests) call this again once the roster exists.
     */
    public function backfill(): void
    {
        if (! Schema::hasTable('colleges') || ! Schema::hasTable('faculties')) {
            return;
        }

        $collegeIds = DB::table('colleges')->pluck('id', 'code');

        if ($collegeIds->isEmpty()) {
            return;
        }

        DB::table('faculties')
            ->select('id', 'department', 'college_id')
            ->orderBy('id')
            ->get()
            ->each(function ($faculty) use ($collegeIds) {
                // Never overwrite a link someone already set.
                if ($faculty->college_id !== null) {
                    return;
                }

                $code = $this->collegeCodeFor($faculty->department);

                if ($code === null || ! isset($collegeIds[$code])) {
                    return;
                }

                DB::table('faculties')
                    ->where('id', $faculty->id)
                    ->update(['college_id' => $collegeIds[$code]]);
            });
    }

    /**
     * Resolve a department string to a college code.
     *
     * Matching is deliberately conservative: exact normalised match first, then
     * a contains-check so "College of Arts and Sciences (CAS)" or a department
     * with a trailing suffix still resolves. Anything ambiguous returns null —
     * a NULL college is honest; a wrong college is not.
     */
    private function collegeCodeFor(?string $department): ?string
    {
        if ($department === null || trim($department) === '') {
            return null;
        }

        $normalised = strtolower(trim($department));

        if (isset(self::DEPARTMENT_TO_COLLEGE[$normalised])) {
            return self::DEPARTMENT_TO_COLLEGE[$normalised];
        }

        foreach (self::DEPARTMENT_TO_COLLEGE as $needle => $code) {
            if (str_contains($normalised, $needle)) {
                return $code;
            }
        }

        return null;
    }

    public function down(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            $table->dropConstrainedForeignId('college_id');
            $table->dropColumn('status');
        });
    }
};
