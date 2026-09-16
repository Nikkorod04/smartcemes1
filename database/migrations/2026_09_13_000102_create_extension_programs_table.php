<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extension_programs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('goals')->nullable();
            $table->text('objectives')->nullable();
            $table->date('planned_start_date');
            $table->date('planned_end_date');
            $table->unsignedInteger('target_beneficiaries')->nullable();
            $table->json('beneficiary_categories')->default('[]');
            $table->decimal('allocated_budget', 12, 2)->default(0);
            $table->foreignId('program_lead_id')->nullable()->constrained('faculties')->nullOnDelete();
            $table->json('partners')->default('[]');
            $table->string('cover_image')->nullable();
            $table->json('gallery_images')->default('[]');
            $table->json('attachments')->default('[]');
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extension_programs');
    }
};
