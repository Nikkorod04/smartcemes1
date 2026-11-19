<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anonymous per-activity evaluation responses (Phase 2 of the
 * attendance/evaluation streamlining).
 *
 * These rows are deliberately NOT linked to a beneficiary. They are the public
 * feedback an attendee leaves by scanning the session QR: a satisfaction score
 * and an optional comment. Per-beneficiary pre/post scores stay in the XLSX
 * import and the activity's aggregate columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_evaluations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();

            // Nullable so a link can be removed without losing the responses
            // already collected through it.
            $table->foreignId('activity_evaluation_link_id')
                ->nullable()
                ->constrained('activity_evaluation_links')
                ->nullOnDelete();

            // One id per browser draft: a retried POST never writes twice.
            $table->uuid('client_uuid')->nullable()->unique();

            $table->unsignedTinyInteger('satisfaction');
            $table->text('comments')->nullable();

            // The respondent's clock, trusted loosely (see the controller).
            $table->timestamp('captured_at')->nullable();

            $table->timestamps();

            $table->index(['activity_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_evaluations');
    }
};
