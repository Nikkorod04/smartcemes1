<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Where a needs-assessment row came from (after-defense revision, Item 1, Step 3).
 *
 * Before this, every row came from a signed-in encoder: either the five-step
 * wizard or an XLSX import. The public form is the first instrument that writes
 * to the same table with no user attached, so the queue needs to be able to say
 * so — a secretary validating a row should know whether a person filled it in
 * themselves or a faculty member encoded it on their behalf.
 *
 * `client_uuid` is the draft id the browser generates. It makes the POST
 * idempotent: a respondent whose signal drops mid-submit can tap again and get
 * the same row instead of a duplicate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('needs_assessments', function (Blueprint $table) {
            // staff | import | public
            $table->string('source', 16)->default('staff')->after('review_status')->index();
            $table->uuid('client_uuid')->nullable()->unique()->after('source');
            // When the respondent actually filled it in, which can differ from
            // created_at once offline capture exists.
            $table->timestamp('captured_at')->nullable()->after('client_uuid');
        });

        // Existing rows keep the default, except imports, which are
        // identifiable by their file.
        DB::table('needs_assessments')
            ->whereNotNull('file_path')
            ->update(['source' => 'import']);
    }

    public function down(): void
    {
        Schema::table('needs_assessments', function (Blueprint $table) {
            $table->dropUnique(['client_uuid']);
            $table->dropIndex(['source']);
            $table->dropColumn(['source', 'client_uuid', 'captured_at']);
        });
    }
};
