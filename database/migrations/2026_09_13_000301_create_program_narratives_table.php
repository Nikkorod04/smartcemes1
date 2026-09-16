<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_narratives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extension_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('summary')->nullable();
            $table->string('health_label')->nullable();
            $table->json('risks')->nullable();
            $table->json('recommendations')->nullable();
            $table->json('raw_extracted_data')->nullable();
            $table->decimal('confidence_score', 3, 2)->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_narratives');
    }
};
