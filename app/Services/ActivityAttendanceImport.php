<?php

namespace App\Services;

use App\Models\Activity;
use App\Services\Concerns\MatchesActivityRoster;

/**
 * Parses the official activity attendance XLSX (v4.13, 5.5).
 *
 * The file is screen-only until the uploader confirms (D10). Rows are
 * matched by Beneficiary ID + name against the program roster; unknown,
 * duplicated, mismatched or invalid-status rows are reported and skipped —
 * the file is never whole-file rejected. A blank Status leaves the
 * beneficiary unrecorded (no attendance row is written or cleared).
 */
class ActivityAttendanceImport
{
    use MatchesActivityRoster;

    /** header label => field */
    protected const FIELD_BY_HEADER = [
        'Beneficiary ID' => 'beneficiary_id',
        'Last Name' => 'last_name',
        'First Name' => 'first_name',
        'Barangay' => 'barangay',
        'Status' => 'status',
    ];

    /**
     * @return array{errors:list<string>, rows:list<array>, summary:array}
     */
    public function parse(Activity $activity, string $path): array
    {
        $sheetRows = $this->loadRows($path);

        $header = $this->mapHeaderRow($sheetRows, static::FIELD_BY_HEADER, 4);
        if ($header === null) {
            return $this->failure('This file does not use the official activity attendance template headers. Download the template and do not rename columns.');
        }

        $roster = $this->roster($activity);
        if ($roster->isEmpty()) {
            return $this->failure('No beneficiaries are enrolled in this program yet — enroll them before importing attendance.');
        }

        $statuses = array_map('strtolower', config('smartcemes.statuses.attendance'));
        $seenIds = [];
        $rows = [];
        $processed = 0;
        $applied = 0;
        $skipped = 0;
        $invalid = 0;
        $statusCounts = array_fill_keys($statuses, 0);

        foreach ($sheetRows as $rowNumber => $sheetRow) {
            if ($rowNumber <= $header['row']) {
                continue;
            }

            $raw = $this->rawFields($sheetRow, $header['columns']);
            if ($this->rowIsEmpty($raw)) {
                continue;
            }

            $processed++;
            $errors = [];
            $id = (int) ($raw['beneficiary_id'] ?? '');

            $errors = array_merge($errors, $this->matchIdentity(
                $id,
                $raw['last_name'] ?? '',
                $raw['first_name'] ?? '',
                $roster,
                $seenIds
            ));

            $rawStatus = $raw['status'] ?? '';
            $status = $rawStatus === '' ? null : strtolower($rawStatus);
            if ($status !== null && ! in_array($status, $statuses, true)) {
                $errors[] = "Status '{$rawStatus}' is not one of ".implode(', ', ActivityAttendanceTemplate::statusLabels()).'.';
                $status = null;
            }

            $state = 'applied';
            if ($errors !== []) {
                $state = 'error';
                $invalid++;
            } elseif ($status === null) {
                $state = 'skipped';
                $skipped++;
            } else {
                $applied++;
                $statusCounts[$status]++;
            }

            $rows[] = [
                'row' => (int) $rowNumber,
                'beneficiary_id' => $id,
                'name' => $this->displayName($roster, $id),
                'status' => $state === 'error' ? null : $status,
                'state' => $state,
                'errors' => $errors,
            ];
        }

        if ($processed === 0) {
            return $this->failure('No data rows found under the template headers.');
        }

        if ($processed > ActivityAttendanceTemplate::MAX_ROWS) {
            return $this->failure('Too many rows — the import accepts at most '.ActivityAttendanceTemplate::MAX_ROWS.' beneficiaries per file.');
        }

        return [
            'errors' => [],
            'rows' => $rows,
            'summary' => [
                'processed' => $processed,
                'applied' => $applied,
                'skipped' => $skipped,
                'invalid' => $invalid,
                'statuses' => $statusCounts,
            ],
        ];
    }

    /**
     * @return array{errors:list<string>, rows:list<array>, summary:array}
     */
    protected function failure(string $message): array
    {
        return [
            'errors' => [$message],
            'rows' => [],
            'summary' => ['processed' => 0, 'applied' => 0, 'skipped' => 0, 'invalid' => 0, 'statuses' => []],
        ];
    }
}
