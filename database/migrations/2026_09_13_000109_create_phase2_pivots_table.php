<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_extension_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('extension_program_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['community_id', 'extension_program_id'], 'community_program_unique');
        });

        Schema::create('extension_program_beneficiary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extension_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['extension_program_id', 'beneficiary_id'], 'program_beneficiary_unique');
        });

        Schema::create('activity_faculty', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('faculty_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['activity_id', 'faculty_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_faculty');
        Schema::dropIfExists('extension_program_beneficiary');
        Schema::dropIfExists('community_extension_program');
    }
};
