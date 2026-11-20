<?php

namespace App\Console\Commands;

use App\Models\Beneficiary;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Remove beneficiaries that have no project (2026-11-20).
 *
 * The one-project migration leaves these rows NULL so the database change never
 * silently deletes a person. This command is the explicit cleanup:
 *
 *   php artisan beneficiaries:prune-unassigned           # dry run + JSON backup
 *   php artisan beneficiaries:prune-unassigned --force   # hard delete
 *
 * Only rows with `extension_project_id IS NULL` are touched.
 */
class PruneUnassignedBeneficiaries extends Command
{
    protected $signature = 'beneficiaries:prune-unassigned
                            {--force : Hard-delete the backed-up rows}';

    protected $description = 'Back up and remove beneficiaries that have no project';

    public function handle(): int
    {
        $beneficiaries = Beneficiary::withTrashed()
            ->whereNull('extension_project_id')
            ->with('attendances')
            ->get();

        if ($beneficiaries->isEmpty()) {
            $this->info('No unassigned beneficiaries found.');

            return self::SUCCESS;
        }

        $disk = Storage::disk('local');
        $disk->makeDirectory('backups');

        $path = 'backups/beneficiaries-unassigned-'.now()->format('Ymd-His').'.json';

        $payload = $beneficiaries->map(fn (Beneficiary $b) => [
            'id' => $b->id,
            'first_name' => $b->first_name,
            'middle_name' => $b->middle_name,
            'last_name' => $b->last_name,
            'age' => $b->age,
            'gender' => $b->gender,
            'phone' => $b->phone,
            'barangay' => $b->barangay,
            'municipality' => $b->municipality,
            'province' => $b->province,
            'community_id' => $b->community_id,
            'extension_project_id' => $b->extension_project_id,
            'beneficiary_category' => $b->beneficiary_category,
            'created_at' => $b->created_at?->toIso8601String(),
            'updated_at' => $b->updated_at?->toIso8601String(),
            'deleted_at' => $b->deleted_at?->toIso8601String(),
            'attendance_rows' => $b->attendances->count(),
            // Attendance cascades on a hard delete, so keep it in the safety
            // backup too. The intended live set has none; a stray row must still
            // be recoverable from this file.
            'attendances' => $b->attendances->map(fn ($a) => [
                'id' => $a->id,
                'activity_id' => $a->activity_id,
                'attendance_date' => $a->attendance_date?->toDateString(),
                'status' => $a->status,
                'remarks' => $a->remarks ?? null,
                'created_at' => $a->created_at?->toIso8601String(),
                'updated_at' => $a->updated_at?->toIso8601String(),
            ])->all(),
        ])->all();

        $disk->put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info($beneficiaries->count().' unassigned beneficiary record(s) backed up to:');
        $this->line($disk->path($path));

        if (! $this->option('force')) {
            $this->warn('Dry run only. Re-run with --force to hard-delete these records.');

            return self::SUCCESS;
        }

        foreach ($beneficiaries as $beneficiary) {
            $beneficiary->forceDelete();
        }

        $this->info($beneficiaries->count().' unassigned record(s) deleted.');

        return self::SUCCESS;
    }
}
