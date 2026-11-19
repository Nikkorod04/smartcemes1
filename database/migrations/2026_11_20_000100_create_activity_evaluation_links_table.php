<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-activity evaluation links (Phase 2 of the attendance/evaluation
 * streamlining). Like the assessment links, the token is the credential: it is
 * short-lived, revocable, and never changes the public URL on close.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_evaluation_links', function (Blueprint $table) {
            $table->id();

            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();

            // Nullable so MySQL cannot attach the first-TIMESTAMP-column
            // DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP quirk.
            $table->timestamp('expires_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            $table->index(['activity_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_evaluation_links');
    }
};
