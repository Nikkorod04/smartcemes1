<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase R4 — the training-hours model (revision §4.4, D-R3/D-R4, R-Q1).
 *
 * Training hours are COMPUTED, never stored:
 *
 *     trainors = trainors_snapshot ?? activity.faculty()->count()
 *     trainees = DISTINCT beneficiaries with present|late attendance
 *                ?? participants                       (manual fallback, R-Q1)
 *                ?? 0
 *     days     = no_of_days (0 contributes 0)
 *     TRAINING_HOURS = trainors x trainees x days        # NOTE: no x8
 *
 * The `x 8` hourly factor was REMOVED by §2.2A — `days` already carries the
 * duration, so multiplying by 8 double-counted it. The half-day option is
 * unaffected: it lives here in `no_of_days` as 0.5.
 *
 * `no_of_days` is decimal(4,1) rather than an integer precisely because 0.5 is
 * a first-class value (D-R4: "0.5 increments, 0.5 / 1 / 1.5 / 2 ..."). Storing
 * a half day as an integer would force a separate flag column.
 *
 * All three columns are NULLABLE on purpose. A pre-R4 activity genuinely has no
 * recorded duration, and NULL says that honestly — 0 would claim "this activity
 * delivered nothing", which is a different and unverifiable statement. The
 * service treats NULL days as a contributing 0 but the UI distinguishes the two.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // 0.5 = half day. Nullable so historical rows stay honest.
            $table->decimal('no_of_days', 4, 1)->nullable()->after('end_time');
            // Manual trainee count — the R-Q1 fallback when no attendance exists.
            $table->unsignedInteger('participants')->nullable()->after('no_of_days');
            // Optional override; default is the count of assigned faculty.
            $table->unsignedInteger('trainors_snapshot')->nullable()->after('participants');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['no_of_days', 'participants', 'trainors_snapshot']);
        });
    }
};
