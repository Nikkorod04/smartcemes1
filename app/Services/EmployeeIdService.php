<?php

namespace App\Services;

use App\Models\Faculty;

class EmployeeIdService
{
    public function __construct(protected SequenceService $sequences) {}

    /**
     * Atomically reserve the next employee ID for the given year.
     * Format: LNU-{year}-{4-digit zero-padded sequence} (blueprint 5.1).
     * Existing IDs (seeded or manually set, soft-deleted included) are
     * passed as a floor so the sequence can never issue a duplicate.
     */
    public function next(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');

        $prefix = "LNU-{$year}-";

        $floor = Faculty::withTrashed()
            ->where('employee_id', 'like', $prefix.'%')
            ->pluck('employee_id')
            ->map(fn (string $id) => (int) substr($id, strlen($prefix)))
            ->max();

        $seq = $this->sequences->next('employee_id_'.$year, $floor);

        return sprintf('LNU-%d-%04d', $year, $seq);
    }
}
