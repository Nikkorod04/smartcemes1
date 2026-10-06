<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase R6 — the Tier-2 referral column on the assessment analysis.
 *
 * WHY A SEPARATE COLUMN RATHER THAN A KEY INSIDE `recommendations`
 * ---------------------------------------------------------------
 * The two tiers are not the same kind of output and must not be ranked against
 * each other. `recommendations[]` is "what CESO should deliver", ordered 1-5;
 * `interagency_referrals[]` is "what CESO should HAND OFF, and to whom" — it has
 * no rank, because CESO is not competing for the slot. Putting them in one array
 * would force a shared `rank` and imply CESO could choose to deliver a medical
 * mission, which is exactly the Tier-3 confusion §7.1 exists to prevent.
 *
 * NULLABLE, and NULL means "not yet analysed" — the same NULL-over-0 discipline
 * used throughout R4/R5. An empty array `[]` means "analysed, no referrals
 * needed", which is a materially different and legitimate result.
 *
 * Each element: {need, agency_code, agency_name, rationale}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_analyses', function (Blueprint $table) {
            $table->json('interagency_referrals')->nullable()->after('recommendations');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_analyses', function (Blueprint $table) {
            $table->dropColumn('interagency_referrals');
        });
    }
};
