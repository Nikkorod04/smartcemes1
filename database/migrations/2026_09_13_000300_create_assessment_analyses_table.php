<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('needs_assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_summary_id')->constrained()->cascadeOnDelete();
            $table->json('raw_extracted_data')->nullable();
            $table->json('extracted_fields')->nullable();
            $table->json('problems_identified')->nullable();
            $table->json('recommendations')->nullable();
            $table->text('summary')->nullable();
            $table->decimal('confidence_score', 3, 2)->nullable();
            $table->string('approval_status')->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_analyses');
    }
};
