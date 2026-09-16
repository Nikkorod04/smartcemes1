<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // activity_proposal_id is added by the Phase 3 proposals migration.
        Schema::create('needs_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('quarter');
            $table->unsignedSmallInteger('year');
            $table->string('file_path')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('review_status')->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_remarks')->nullable();

            // Section I: Respondent Information
            $table->string('respondent_first_name')->nullable();
            $table->string('respondent_middle_name')->nullable();
            $table->string('respondent_last_name')->nullable();
            $table->unsignedSmallInteger('respondent_age')->nullable();
            $table->string('respondent_civil_status')->nullable();
            $table->string('respondent_sex')->nullable();
            $table->string('respondent_religion')->nullable();
            $table->json('respondent_educational_attainment')->default('[]');

            // Section II: Family Composition
            $table->json('family_composition')->default('[]');
            $table->string('household_member_currently_studying')->nullable();
            $table->json('household_members_in_organization')->default('[]');

            // Section III: Economic / Livelihood
            $table->json('livelihood_options')->default('[]');
            $table->string('interested_in_livelihood_training')->nullable();
            $table->json('desired_training')->default('[]');

            // Section IV: Education
            $table->json('barangay_educational_facilities')->default('[]');
            $table->string('interested_in_continuing_studies')->nullable();
            $table->json('areas_of_educational_interest')->default('[]');
            $table->string('preferred_training_time')->nullable();
            $table->json('preferred_training_days')->default('[]');

            // Section V: Health and Sanitation
            $table->json('common_illnesses')->default('[]');
            $table->json('action_when_sick')->default('[]');
            $table->json('barangay_medical_supplies_available')->default('[]');
            $table->string('has_barangay_health_programs')->nullable();
            $table->string('benefits_from_barangay_programs')->nullable();
            $table->json('programs_benefited_from')->default('[]');
            $table->json('water_source')->default('[]');
            $table->string('water_source_distance')->nullable();
            $table->json('garbage_disposal_method')->default('[]');
            $table->string('has_own_toilet')->nullable();
            $table->json('toilet_type')->default('[]');
            $table->string('keeps_animals')->nullable();
            $table->json('animals_kept')->default('[]');

            // Section VI: Housing and Basic Amenities
            $table->json('house_type')->default('[]');
            $table->json('tenure_status')->default('[]');
            $table->string('has_electricity')->nullable();
            $table->json('light_source_without_power')->default('[]');
            $table->json('appliances_owned')->default('[]');

            // Section VII: Recreation, Organization, and Social Participation
            $table->json('barangay_recreational_facilities')->default('[]');
            $table->json('use_of_free_time')->default('[]');
            $table->string('member_of_organization')->nullable();
            $table->json('organization_types')->default('[]');
            $table->string('organization_meeting_frequency')->nullable();
            $table->json('organization_usual_activities')->default('[]');
            $table->string('position_in_organization')->nullable();

            // Section VIII: Problems and Priorities
            $table->json('family_problems')->default('[]');
            $table->json('health_problems')->default('[]');
            $table->json('educational_problems')->default('[]');
            $table->json('employment_problems')->default('[]');
            $table->json('infrastructure_problems')->default('[]');
            $table->json('economic_problems')->default('[]');
            $table->json('security_problems')->default('[]');

            // Section IX: Service Ratings and Summary
            $table->json('barangay_service_ratings')->default('{}');
            $table->text('general_feedback')->nullable();
            $table->string('available_for_training')->nullable();
            $table->text('reason_not_available')->nullable();

            // Raw text preserved for "Other" selections (7 rules / D9)
            $table->json('other_text')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('needs_assessments');
    }
};
