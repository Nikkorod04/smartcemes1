<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('quarter');
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('total_responses')->default(0);
            $table->json('gender_distribution')->default('{}');
            $table->json('religion_distribution')->default('{}');
            $table->json('education_distribution')->default('{}');
            $table->json('civil_status_distribution')->default('{}');
            $table->json('livelihood_interests')->default('{}');
            $table->json('educational_interests')->default('{}');
            $table->json('health_problems')->default('{}');
            $table->json('family_problems')->default('{}');
            $table->json('employment_problems')->default('{}');
            $table->json('infrastructure_problems')->default('{}');
            $table->json('economic_problems')->default('{}');
            $table->json('security_problems')->default('{}');
            $table->json('water_sources')->default('{}');
            $table->json('house_types')->default('{}');
            $table->decimal('electricity_access_percentage', 5, 2)->nullable();
            $table->decimal('organization_membership_percentage', 5, 2)->nullable();
            $table->decimal('training_availability_percentage', 5, 2)->nullable();
            $table->decimal('avg_service_satisfaction', 4, 2)->nullable();
            $table->decimal('baseline_satisfaction_score', 4, 2)->nullable();
            $table->text('ai_analysis')->nullable();
            $table->json('ai_interventions')->nullable();
            $table->json('ai_analysis_sections')->nullable();
            $table->timestamp('ai_analysis_generated_at')->nullable();
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['community_id', 'quarter', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_summaries');
    }
};
