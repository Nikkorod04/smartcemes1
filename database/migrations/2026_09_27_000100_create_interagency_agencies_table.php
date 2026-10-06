<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase R6 / D-R10 — the interagency referral catalogue.
 *
 * WHY THIS IS A TABLE AND NOT A CONFIG ARRAY
 * ------------------------------------------
 * The AI may cite an agency in a recommendation, and every citation has to be
 * defensible to the adviser. A hardcoded list inside the prompt is not
 * auditable: nobody can tell which version of the list produced which referral.
 * Holding the catalogue in the database means (a) the Director edits it without
 * a deploy, and (b) the exact rows used for a given analysis can be snapshotted
 * into `assessment_analyses.metadata` and re-read later.
 *
 * THE GUARDRAIL THIS ENABLES
 * --------------------------
 * CESO's mandate is TRAINING. Food programmes, medical missions, roads and water
 * systems are real community needs but are NOT CESO's to deliver (§3, §7.1
 * Tier 2/3). This table is the closed vocabulary the model may cite for those
 * needs — so a referral names DSWD or DPWH, never an invented agency, and never
 * CESO itself.
 *
 * `agency_code` is unique because the prompt asks the model to return a CODE;
 * `agency_name` is a display convenience the model echoes back, but the code is
 * the key the system resolves against so a paraphrased name cannot smuggle in an
 * agency that is not in the catalogue.
 *
 * `active` (rather than soft-delete alone) exists so an agency can be retired
 * from the prompt's citable set while historical analyses keep resolving its
 * name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interagency_agencies', function (Blueprint $table) {
            $table->id();
            $table->string('agency_code')->unique();
            $table->string('agency_name');
            $table->text('mandate')->nullable();
            // The need bucket this agency answers, e.g. "Food / nutrition / welfare".
            $table->string('need_category');
            // Comma-separated sample referral services, shown to the Director.
            $table->text('sample_service')->nullable();
            $table->string('contact_info')->nullable();
            // Retirable without deleting history (see the class docblock).
            $table->boolean('active')->default(true);
            // Presentation order on the catalogue screen and in the prompt.
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interagency_agencies');
    }
};
