<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained()->cascadeOnDelete();
            $table->foreignId('extension_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('proposed_start_date');
            $table->date('proposed_end_date');
            $table->decimal('budget_estimate', 12, 2)->nullable();
            $table->string('special_order_path')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('submitted_at')->nullable();
            $table->text('admin_remarks')->nullable();
            $table->timestamp('admin_approved_at')->nullable();
            $table->foreignId('admin_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assessment_deadline')->nullable();
            $table->text('secretary_remarks')->nullable();
            $table->timestamp('secretary_approved_at')->nullable();
            $table->foreignId('secretary_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_proposals');
    }
};
