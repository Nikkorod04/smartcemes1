<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * New: the BROAD Program level (revision §4.2 / Phase R1).
 *
 * Revised hierarchy: College → Program → Project → Activity.
 * Only six rows in practice — one per CESO thrust (§3). Admin-managed.
 *
 * TABLE NAMING (Phase R1 decision, option 1)
 * ------------------------------------------
 * The model is `App\Models\ExtensionProgram` but the physical table is
 * `programs`, NOT `extension_programs`. Reason: the LEGACY table still owns
 * the name `extension_programs` until Phase R2 renames it to
 * `extension_projects`. Two tables cannot share a name, and doing R2's
 * 7-column + 1-pivot-table + 126-reference rename inside R1 would import the
 * riskiest phase into the foundation phase.
 *
 * Phase R2 renames the legacy table out of the way; at that point this table
 * may be renamed to `extension_programs` if the canonical name is wanted, or
 * left as `programs` (which reads better in code). Either way the end state
 * matches §4.2 — this is a naming deferral, not a model change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('title');
            $table->string('pillar', 32);
            $table->string('ceso_thrust');
            $table->text('description')->nullable();
            $table->text('goals')->nullable();
            $table->decimal('annual_target_hours', 10, 2)->nullable();
            $table->decimal('annual_target_budget', 12, 2)->nullable();
            $table->string('status', 16)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
