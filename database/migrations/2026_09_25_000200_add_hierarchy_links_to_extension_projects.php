<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE R2b + R2c — attach projects to the new hierarchy (revision §4.3, §3.1).
 *
 * Adds the two parent FKs and the two per-project targets, then backfills the
 * six inherited rows.
 *
 * Why nullable-then-backfill rather than `->constrained()` inline: the table is
 * already populated when this runs, so a NOT NULL FK would be unsatisfiable.
 * Nullable also survives re-running and leaves a clear signal if a future row
 * somehow escapes the backfill.
 *
 * The backfill map is derived, not hand-typed, from §3.1 and each project's
 * SUBJECT DOMAIN — the college whose degree programmes actually cover the work.
 *
 * CORRECTED 2026-09-25. The map was originally derived from each row's
 * `program_lead_id`, i.e. the college of the lead's specialization (matched
 * against §1.1 rather than the literal `faculty.department` string, because
 * `faculty4` carries the legacy department "College of Business
 * Administration"). That produced two rows the institution does not recognise:
 * SENIOR CARE sat under COE and BATANG MATINIK under CME purely because their
 * leads teach Reading Education and Entrepreneurship — nothing to do with
 * health or sport. LNU houses the Bachelor of Physical Education inside the
 * COLLEGE OF EDUCATION (it has its own BPEd Unit), so sport is COE; health and
 * wellness have no college of their own and sit in CAS beside BS Biology and
 * BS Social Work.
 *
 * A project's college is therefore its DOMAIN, and the lead may differ from it —
 * nothing validates the pairing. Two rows still do:
 *
 *   EXT-2026-005 SENIOR CARE    -> CAS  (lead faculty2, Reading Education — COE)
 *   EXT-2026-006 BATANG MATINIK -> COE  (lead faculty4, Entrepreneurship — CME)
 *
 * Derivation:
 *
 *   EXT-2026-001 LITRAWIYA      -> COE  (remedial reading is teacher education)
 *   EXT-2026-002 HANDA          -> CAS  (DRR / environmental science)
 *   EXT-2026-003 KABUHIAN       -> CME  (enterprise / livelihood)
 *   EXT-2026-004 e-LITERACY     -> CAS  (information technology)
 *   EXT-2026-005 SENIOR CARE    -> CAS  (health & wellness — no health college)
 *   EXT-2026-006 BATANG MATINIK -> COE  (BPEd lives in the College of Education)
 *
 * Broad-program assignment comes from §3.1 and is matched by TITLE (the broad
 * programs carry deterministic PROG-{year}-{seq} codes, so title is the stable
 * join key across re-seeds).
 *
 * Codes are NOT rewritten — the six rows keep `EXT-2026-00{n}` (R-Q4).
 */
return new class extends Migration
{
    /**
     * code => [college code, broad program title (matched against §3 / ProgramSeeder)]
     */
    private const BACKFILL = [
        'EXT-2026-001' => ['COE', 'Literacy, Numeracy & Language'],
        'EXT-2026-002' => ['CAS', 'Environmental Conservation & Disaster Preparedness'],
        'EXT-2026-003' => ['CME', 'Livelihood, Technical & Business Management'],
        'EXT-2026-004' => ['CAS', 'Information, Communication & Education'],
        'EXT-2026-005' => ['CAS', 'Information, Communication & Education'],
        'EXT-2026-006' => ['COE', 'Physical Fitness & Sports Development'],
    ];

    public function up(): void
    {
        Schema::table('extension_projects', function (Blueprint $t) {
            /* Parent links. Nullable so the populated table can accept them. */
            $t->foreignId('college_id')->nullable()->after('id')
                ->constrained('colleges')->nullOnDelete();
            $t->foreignId('program_id')->nullable()->after('college_id')
                ->constrained('programs')->nullOnDelete();

            /* Per-project annual targets (§4.3 / D-R5). Distinct from the
               university-wide pool on `university_targets` (§4.7, Phase R4). */
            $t->decimal('annual_target_hours', 10, 2)->nullable()->after('allocated_budget');
            $t->decimal('annual_target_budget', 12, 2)->nullable()->after('annual_target_hours');
        });

        $this->backfill();

        /* Now that every row is linked, a project without a college or program
           is a data error rather than a normal state. Applied only where the
           tables are non-empty so a bare `migrate` on a fresh DB still works. */
        $this->tightenIfPopulated();
    }

    /**
     * Link the six inherited rows to their college and broad program.
     */
    private function backfill(): void
    {
        /* Guard: this migration must be a no-op on a database where the R1
           seeders have not run (e.g. a bare `migrate` before `db:seed`). */
        if (! Schema::hasTable('colleges') || ! Schema::hasTable('programs')) {
            return;
        }

        foreach (self::BACKFILL as $code => [$collegeCode, $programTitle]) {
            $row = DB::table('extension_projects')->where('code', $code)->first();

            if (! $row) {
                continue; // not seeded in this environment — nothing to link
            }

            $collegeId = DB::table('colleges')->where('code', $collegeCode)->value('id');
            $programId = DB::table('programs')->where('title', $programTitle)->value('id');

            DB::table('extension_projects')->where('id', $row->id)->update([
                'college_id' => $collegeId,
                'program_id' => $programId,
            ]);
        }
    }

    /**
     * Only enforce NOT NULL when rows actually exist and all of them are linked.
     * A partially-linked table is left nullable rather than failing the migrate.
     */
    private function tightenIfPopulated(): void
    {
        if (DB::table('extension_projects')->count() === 0) {
            return;
        }

        $orphans = DB::table('extension_projects')
            ->whereNull('college_id')
            ->orWhereNull('program_id')
            ->count();

        if ($orphans > 0) {
            /* Leave nullable and stay silent — `db:seed` may not have run yet.
               A later re-run of this migration will tighten it. */
            return;
        }

        /* SQLite cannot alter a column to NOT NULL without a table rebuild, so
           the constraint is applied only where the driver supports it. The
           application layer enforces it everywhere via validation + the
           relations (see ExtensionProject::$fillable and the R2f CRUD). */
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('extension_projects', function (Blueprint $t) {
            $t->foreignId('college_id')->nullable(false)->change();
            $t->foreignId('program_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('extension_projects', function (Blueprint $t) {
            $t->dropConstrainedForeignId('program_id');
            $t->dropConstrainedForeignId('college_id');
            $t->dropColumn(['annual_target_hours', 'annual_target_budget']);
        });
    }
};
