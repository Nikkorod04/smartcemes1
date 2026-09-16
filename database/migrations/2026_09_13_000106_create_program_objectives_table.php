<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extension_program_id')->constrained()->cascadeOnDelete();
            $table->text('objective');
            $table->string('kpi_metric')->nullable();
            $table->decimal('baseline_value', 12, 4)->nullable();
            $table->decimal('target_value', 12, 4)->nullable();
            $table->decimal('actual_value', 12, 4)->nullable();
            $table->string('unit')->nullable();
            $table->date('target_date')->nullable();
            $table->string('status')->default('not_started');
            $table->text('evidence_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_objectives');
    }
};
