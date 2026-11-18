<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public needs-assessment share links (after-defense revision, Item 1).
 *
 * A link is a TOKEN, not a signed URL: that is what makes it revocable,
 * extendable ("reopen") and short enough to print under a QR code. The
 * token, not the URL signature, is the authorization for the public form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_links', function (Blueprint $table) {
            $table->id();

            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('quarter');
            $table->unsignedSmallInteger('year');

            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();

            // "Which links were opened for this community this quarter?"
            $table->index(['community_id', 'quarter', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_links');
    }
};
