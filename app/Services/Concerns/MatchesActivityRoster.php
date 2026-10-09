<?php

namespace App\Services\Concerns;

use App\Models\Activity;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Shared parsing helpers for the per-activity XLSX imports (v4.13):
 * header-label matching, roster lookup, cross-row duplicate detection and
 * the ID + name identity check. Errors are screen-only per D10.
 */
trait MatchesActivityRoster
{
    /**
     * Load the workbook's active sheet as rows keyed 1..n with column
     * letters A.. as keys. Throws when the file is not a readable XLSX.
     */
    protected function loadRows(string $path): array
    {
        try {
            $spreadsheet = IOFactory::load($path);
        } catch (\Throwable) {
            throw new \RuntimeException('Could not read the file as an XLSX workbook.');
        }

        return $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
    }

    /**
     * Locate the fixed header row and map column letters to field names.
     * Returns null when fewer than $minMatches official labels are found.
     *
     * @param  array<string, string>  $fieldByHeader  header label => field
     * @return array{row:int, columns:array<string,string>}|null
     */
    protected function mapHeaderRow(array $rows, array $fieldByHeader, int $minMatches): ?array
    {
        foreach ($rows as $rowNumber => $row) {
            $mapped = [];
            foreach ($row as $colLetter => $label) {
                $label = trim((string) $label);
                if ($label !== '' && isset($fieldByHeader[$label])) {
                    $mapped[$colLetter] = $fieldByHeader[$label];
                }
            }

            if (count($mapped) >= $minMatches) {
                return ['row' => (int) $rowNumber, 'columns' => $mapped];
            }
        }

        return null;
    }

    /** Program-enrolled beneficiaries keyed by registry id. */
    protected function roster(Activity $activity): Collection
    {
        if ($activity->program === null) {
            return collect();
        }

        return $activity->program->beneficiaries()
            ->whereNull('beneficiaries.deleted_at')
            ->get()
            ->keyBy('id');
    }

    /**
     * Pull the mapped field values out of one sheet row.
     *
     * @param  array<string,string>  $columnField  column letter => field
     * @return array<string,string>
     */
    protected function rawFields(array $row, array $columnField): array
    {
        $raw = [];
        foreach ($columnField as $colLetter => $field) {
            $raw[$field] = trim((string) ($row[$colLetter] ?? ''));
        }

        return $raw;
    }

    protected function rowIsEmpty(array $raw): bool
    {
        foreach ($raw as $value) {
            if ($value !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Identity check shared by both imports (D10): the registry ID must be
     * enrolled in the program and appear once per file; any provided name
     * must match the registry so wrong-row IDs cannot silently overwrite.
     *
     * @param  array<int,bool>  $seenIds  cross-row duplicate tracker (by ref)
     * @return list<string> row errors
     */
    protected function matchIdentity(int $id, string $lastName, string $firstName, Collection $roster, array &$seenIds): array
    {
        if ($id <= 0) {
            return ['Missing Beneficiary ID.'];
        }

        if (isset($seenIds[$id])) {
            return ["Beneficiary ID {$id} appears more than once in this file."];
        }

        $beneficiary = $roster->get($id);

        if ($beneficiary === null) {
            return ["Beneficiary ID {$id} is not enrolled in this project (unknown or unenrolled)."];
        }

        if ($lastName !== '' && mb_strtolower($lastName) !== mb_strtolower($beneficiary->last_name)) {
            return ["Name does not match the registry for ID {$id} ({$beneficiary->last_name}, {$beneficiary->first_name})."];
        }

        if ($firstName !== '' && mb_strtolower($firstName) !== mb_strtolower($beneficiary->first_name)) {
            return ["Name does not match the registry for ID {$id} ({$beneficiary->last_name}, {$beneficiary->first_name})."];
        }

        $seenIds[$id] = true;

        return [];
    }

    protected function displayName(Collection $roster, int $id): string
    {
        $beneficiary = $roster->get($id);

        return $beneficiary ? $beneficiary->last_name.', '.$beneficiary->first_name : '—';
    }
}
