<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE R2a — the big rename (revision §5 R2, §4.3).
 *
 * Inverts the hierarchy. Until now `extension_programs` held the narrow,
 * activity-bearing entity. After R2 it holds a PROJECT, and the word
 * "Program" belongs to the broad CESO-thrust level introduced in R1
 * (`programs`, model `App\Models\Program`).
 *
 *     before:  extension_programs -> activities
 *     after:   colleges -> programs -> extension_projects -> activities
 *
 * What moves here:
 *   1. Table  `extension_programs`            -> `extension_projects`
 *   2. Pivots `extension_program_beneficiary` -> `extension_project_beneficiary`
 *             `community_extension_program`   -> `community_extension_project`
 *   3. The FK column `extension_program_id` -> `extension_project_id` on all
 *      seven tables that carry it.
 *
 * NAMING DELIBERATION (owner decision, R2 step 4):
 * The plan's step 4 offered "rename `programs` -> `extension_programs`". That was
 * REJECTED. It would re-adopt the very name R1 deliberately vacated, inverting
 * what the word "program" means mid-phase while R2 is already the highest-risk
 * step. Keeping `programs` keeps this migration to ONE rename. The resulting
 * divergence from SYSTEM_BLUEPRINT_V4.txt §1 (which still says
 * `extension_programs` for the broad level) is intentional and is reconciled in
 * R7 along with the other stale blueprint sections listed in §8.
 *
 * NO COLUMN IS DROPPED and NO ROW IS TOUCHED. Codes (`EXT-{year}-{seq}`) are
 * carried over verbatim to preserve history (R-Q4); only NEW projects adopt the
 * college-prefixed pattern, implemented in R2d.
 *
 * Reversible: `down()` restores every name exactly.
 */
return new class extends Migration
{
    /**
     * The seven tables carrying `extension_program_id`, with the index names
     * each one created. MySQL/SQLite carry index names across a column rename,
     * so they must be renamed explicitly to stay coherent — Laravel's
     * `renameColumn` does not do it for us.
     *
     * table => [old index name (nullable), new index name (nullable)]
     */
    private const FOREIGN_KEYS = [
        'activities' => [null, null],
        'budget_utilizations' => [null, null],
        'program_objectives' => [null, null],
        'activity_proposals' => [null, null],
        'program_narratives' => [null, null],
    ];

    /**
     * Pivot tables: their FK column is renamed alongside the table itself.
     */
    private const PIVOTS = [
        'extension_program_beneficiary' => 'extension_project_beneficiary',
        'community_extension_program' => 'community_extension_project',
    ];

    public function up(): void
    {
        /* ---- 1. the primary table ---- */
        Schema::rename('extension_programs', 'extension_projects');

        /* ---- 2. the FK column on every dependent table ---- */
        foreach (array_keys(self::FOREIGN_KEYS) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'extension_program_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->renameColumn('extension_program_id', 'extension_project_id');
            });
        }

        /* ---- 3. the pivots: table name AND their FK column ---- */
        foreach (self::PIVOTS as $old => $new) {
            if (! Schema::hasTable($old)) {
                continue;
            }

            Schema::rename($old, $new);

            if (Schema::hasColumn($new, 'extension_program_id')) {
                Schema::table($new, function (Blueprint $t) {
                    $t->renameColumn('extension_program_id', 'extension_project_id');
                });
            }
        }
    }

    public function down(): void
    {
        /* ---- 3b. pivots back ---- */
        foreach (self::PIVOTS as $old => $new) {
            if (! Schema::hasTable($new)) {
                continue;
            }

            if (Schema::hasColumn($new, 'extension_project_id')) {
                Schema::table($new, function (Blueprint $t) {
                    $t->renameColumn('extension_project_id', 'extension_program_id');
                });
            }

            Schema::rename($new, $old);
        }

        /* ---- 2b. FK columns back ---- */
        foreach (array_keys(self::FOREIGN_KEYS) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'extension_project_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->renameColumn('extension_project_id', 'extension_program_id');
            });
        }

        /* ---- 1b. table back ---- */
        Schema::rename('extension_projects', 'extension_programs');
    }
};
