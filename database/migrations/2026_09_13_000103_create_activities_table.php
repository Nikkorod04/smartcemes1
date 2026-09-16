<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extension_program_id')->constrained()->cascadeOnDelete();
            // activity_proposal_id is added by the Phase 3 proposals migration
            // (6.5: stamped when an activity is auto-created from an approval).
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('planned_start_date');
            $table->date('planned_end_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->string('venue')->nullable();
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->decimal('allocated_budget', 12, 2)->nullable();
            $table->decimal('pre_assessment_score', 6, 2)->nullable();
            $table->decimal('post_assessment_score', 6, 2)->nullable();
            $table->decimal('satisfaction_rating', 4, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
