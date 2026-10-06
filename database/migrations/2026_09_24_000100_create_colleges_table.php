<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * New: `colleges` (revision §4.1 / Phase R1).
 *
 * Top of the revised hierarchy: College → Program → Project → Activity.
 * Seeded with the three official colleges (CAS / COE / CME); admin-managed
 * but expected to stay static.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colleges', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name');
            $table->string('short_name', 32);
            $table->text('description')->nullable();
            $table->foreignId('extension_coordinator_id')
                ->nullable()
                ->constrained('faculties')
                ->nullOnDelete();
            $table->string('status', 16)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colleges');
    }
};
