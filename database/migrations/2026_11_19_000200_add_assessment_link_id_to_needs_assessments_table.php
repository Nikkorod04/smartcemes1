<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which public link a submission came through.
     *
     * Without this, "how many answered through this QR code" can only be
     * guessed from community + period + a timestamp, which stops being true as
     * soon as two links exist for the same barangay — and two links for the same
     * barangay is a normal field day (one sheet per purok).
     *
     * Nullable because the wizard and the XLSX import have no link, and
     * `nullOnDelete` because answers outlive the credential that collected them:
     * revoking or pruning a link must never remove a respondent's data.
     */
    public function up(): void
    {
        Schema::table('needs_assessments', function (Blueprint $table) {
            $table->foreignId('assessment_link_id')
                ->nullable()
                ->after('id')
                ->constrained('assessment_links')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('needs_assessments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assessment_link_id');
        });
    }
};
