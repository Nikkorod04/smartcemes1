<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One project per beneficiary (2026-11-20).
 *
 * The old model was a global registry plus a project link table. The office
 * never used the many-to-many part (live data: 0 beneficiaries in more than one
 * project), so the link moves onto the beneficiary itself.
 *
 * Backfill takes the FIRST project link where a legacy beneficiary somehow has
 * more than one. The live data has none, but the migration must not fail on a
 * stray test or restored row.
 *
 * Rows with no project link are left with a NULL project. They are removed by
 * `php artisan beneficiaries:prune-unassigned --force`, which writes a JSON
 * backup first. A migration must not silently delete data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->foreignId('extension_project_id')
                ->nullable()
                ->after('community_id')
                ->constrained('extension_projects')
                ->cascadeOnDelete();
        });

        if (Schema::hasTable('extension_project_beneficiary')) {
            DB::table('extension_project_beneficiary')
                ->orderBy('id')
                ->get()
                ->groupBy('beneficiary_id')
                ->each(function ($rows, $beneficiaryId) {
                    DB::table('beneficiaries')
                        ->where('id', $beneficiaryId)
                        ->update(['extension_project_id' => $rows->first()->extension_project_id]);
                });

            Schema::drop('extension_project_beneficiary');
        }
    }

    public function down(): void
    {
        Schema::create('extension_project_beneficiary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extension_project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['extension_project_id', 'beneficiary_id'], 'program_beneficiary_unique');
        });

        DB::table('beneficiaries')
            ->whereNotNull('extension_project_id')
            ->orderBy('id')
            ->each(function ($beneficiary) {
                DB::table('extension_project_beneficiary')->insert([
                    'extension_project_id' => $beneficiary->extension_project_id,
                    'beneficiary_id' => $beneficiary->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('beneficiaries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('extension_project_id');
        });
    }
};
