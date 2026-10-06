<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase R4 — `university_targets` (revision §4.7, R-Q3).
 *
 * The single annual training-hours target for the whole of SmartCEMES, plus the
 * university-wide budget figure, held as a DB row rather than a config value.
 *
 * WHY A ROW AND NOT CONFIG (R-Q3): the Director persona works in the UI, not in
 * code. Config would require a deploy to change a target, and one row per year
 * lets AY 2026-27 and AY 2027-28 hold different targets — which is exactly what
 * demoing a second academic year needs.
 *
 * THE MODEL IS CONSUMPTION, NOT A RATIO (§2.2B). This annual target sits ABOVE
 * the projects as a pool; each project's actual training hours are SUBTRACTED
 * from it:
 *
 *     annual_target_remaining = annual_target - SUM(project.actual_training_hours)
 *
 * Per-project targets live on `extension_projects.annual_target_hours` /
 * `.annual_target_budget` and are deliberately NOT summed to produce this
 * number — they are planning figures for their own project. Broad programs
 * carry NO target of any kind, so there is no program-level column anywhere.
 *
 * `year` is the AY start year (2026 = AY 2026-2027), unique so a year cannot be
 * double-entered. `label` is stored because "AY 2026-2027" is a rendering
 * decision the Director may want to control, and deriving it from `year` would
 * hard-code one convention.
 *
 * Actuals are ALWAYS computed and never stored — this table holds targets only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('university_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->string('label')->nullable();
            $table->decimal('annual_target_hours', 12, 2)->nullable();
            $table->decimal('annual_target_budget', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('university_targets');
    }
};
