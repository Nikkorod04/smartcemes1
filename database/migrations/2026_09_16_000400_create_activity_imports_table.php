<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-activity XLSX import provenance (blueprint v4.13, §6.19).
     *
     * One row per confirmed import: the archived source file, who imported
     * it, when, and the processed/applied/skipped counts. Attendance and
     * evaluation imports share this table (`type`).
     */
    public function up(): void
    {
        Schema::create('activity_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('imported_at')->nullable();
            $table->unsignedInteger('rows_processed')->default(0);
            $table->unsignedInteger('rows_applied')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->json('summary')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['activity_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_imports');
    }
};
