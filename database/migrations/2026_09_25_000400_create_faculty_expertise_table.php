<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * New: `faculty_expertise` (revision §4.5 / Phase R3 step 1).
 *
 *   id, faculty_id, area (string; e.g. "Reading Education"), category, timestamps
 *
 * Implemented as a one-row-per-area table rather than a JSON column on
 * `faculties` because the module filters and counts by area ("show me everyone
 * who does Reading Education") — a JSON blob cannot be indexed or aggregated
 * cleanly. The prototype's multi-select writes one row per chosen area.
 *
 * `category` groups areas for the filters (e.g. "Literacy", "Environment");
 * it is nullable so an area can exist before it is classified.
 *
 * A unique index on (faculty_id, area) stops the same area being added twice
 * for one person — the multi-select could otherwise re-insert on edit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faculty_expertise', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_id')->constrained()->cascadeOnDelete();
            $table->string('area');
            $table->string('category')->nullable();
            $table->timestamps();

            $table->unique(['faculty_id', 'area']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faculty_expertise');
    }
};
