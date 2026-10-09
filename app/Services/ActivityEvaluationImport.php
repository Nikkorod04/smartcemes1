<?php

namespace App\Services;

use App\Models\Activity;
use App\Services\Concerns\MatchesActivityRoster;

/**
 * Parses the official activity evaluation XLSX (v4.13, 5.6 / D13).
 *
 * Per-beneficiary Pre-Test / Post-Test / Satisfaction values are validated
 * (row-level errors are screen-only per D10) and aggregated on confirm
 * into the activity's single aggregate columns — no per-beneficiary
 * evaluation table exists (D13). Blank cells are ignored; a metric with no
 * values in the file leaves the existing aggregate untouched (per-metric
 * merge).
 */
class ActivityEvaluationImport
{
    use MatchesActivityRoster;

    /** header label => field */
    protected const FIELD_BY_HEADER = [
        'Beneficiary ID' => 'beneficiary_id',
        'Last Name' => 'last_name',
        'First Name' => 'first_name',
        'Barangay' => 'barangay',
        'Pre-Test Score (0-100)' => 'pre',
        'Post-Test Score (0-100)' => 'post',
        'Satisfaction (1-5)' => 'satisfaction',
    ];

    /**
     * @return array{errors:list<string>, rows:list<array>, summary:array}
     */
    public function parse(Activity $activity, string $path): array
    {
        $sheetRows = $this->loadRows($path);

        $header = $this->mapHeaderRow($sheetRows, static::FIELD_BY_HEADER, 6);
        if ($header === null) {
            return $this->failure('This file does not use the official activity evaluation template headers. Download the template and do not rename columns.');
        }

        $roster = $this->roster($activity);
        if ($roster->isEmpty()) {
            return $this->failure('No beneficiaries are enrolled in this project yet — enroll them before importing evaluation scores.');
        }

        $seenIds = [];
        $rows = [];
        $processed = 0;
        $applied = 0;
        $skipped = 0;
        $invalid = 0;
        $metricCounts = ['pre' => 0, 'post' => 0, 'satisfaction' => 0];

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

            $values = [
                'pre' => $this->number($raw['pre'] ?? '', ActivityEvaluationTemplate::PRE_MIN, ActivityEvaluationTemplate::PRE_MAX, 'Pre-Test Score', $errors),
                'post' => $this->number($raw['post'] ?? '', ActivityEvaluationTemplate::POST_MIN, ActivityEvaluationTemplate::POST_MAX, 'Post-Test Score', $errors),
                'satisfaction' => $this->number($raw['satisfaction'] ?? '', ActivityEvaluationTemplate::SATISFACTION_MIN, ActivityEvaluationTemplate::SATISFACTION_MAX, 'Satisfaction', $errors),
            ];

            $hasValue = $values['pre'] !== null || $values['post'] !== null || $values['satisfaction'] !== null;

            $state = 'applied';
            if ($errors !== []) {
                $state = 'error';
                $invalid++;
            } elseif (! $hasValue) {
                $state = 'skipped';
                $skipped++;
            } else {
                $applied++;
                foreach ($values as $metric => $value) {
                    if ($value !== null) {
                        $metricCounts[$metric]++;
                    }
                }
            }

            $rows[] = [
                'row' => (int) $rowNumber,
                'beneficiary_id' => $id,
                'name' => $this->displayName($roster, $id),
                'values' => $state === 'error' ? ['pre' => null, 'post' => null, 'satisfaction' => null] : $values,
                'state' => $state,
                'errors' => $errors,
            ];
        }

        if ($processed === 0) {
            return $this->failure('No data rows found under the template headers.');
        }

        if ($processed > ActivityEvaluationTemplate::MAX_ROWS) {
            return $this->failure('Too many rows — the import accepts at most '.ActivityEvaluationTemplate::MAX_ROWS.' beneficiaries per file.');
        }

        return [
            'errors' => [],
            'rows' => $rows,
            'summary' => [
                'processed' => $processed,
                'applied' => $applied,
                'skipped' => $skipped,
                'invalid' => $invalid,
                'metrics' => $metricCounts,
            ],
        ];
    }

    /**
     * Per-metric means over applied rows — null for metrics with no values
     * (the caller must leave those existing aggregates untouched).
     *
     * @param  list<array>  $rows  parse() rows
     * @return array{pre:?float, post:?float, satisfaction:?float, counts:array<string,int>}
     */
    public function means(array $rows): array
    {
        $values = ['pre' => [], 'post' => [], 'satisfaction' => []];

        foreach ($rows as $row) {
            if (($row['state'] ?? '') !== 'applied') {
                continue;
            }

            foreach (array_keys($values) as $metric) {
                $value = $row['values'][$metric] ?? null;
                if ($value !== null) {
                    $values[$metric][] = (float) $value;
                }
            }
        }

        $means = [];
        foreach ($values as $metric => $list) {
            $means[$metric] = $list === [] ? null : round(array_sum($list) / count($list), 2);
        }

        return [
            'pre' => $means['pre'],
            'post' => $means['post'],
            'satisfaction' => $means['satisfaction'],
            'counts' => array_map('count', $values),
        ];
    }

    /**
     * Validate one numeric cell against its range; appends a screen-only
     * error and returns null when blank or invalid.
     *
     * @param  list<string>  $errors
     */
    protected function number(string $raw, float $min, float $max, string $label, array &$errors): ?float
    {
        if ($raw === '') {
            return null;
        }

        if (! is_numeric($raw)) {
            $errors[] = "{$label} '{$raw}' is not a number.";

            return null;
        }

        $value = (float) $raw;
        if ($value < $min || $value > $max) {
            $errors[] = "{$label} {$raw} is outside the allowed range {$min}–{$max}.";

            return null;
        }

        return $value;
    }

    /**
     * @return array{errors:list<string>, rows:list<array>, summary:array}
     */
    protected function failure(string $message): array
    {
        return [
            'errors' => [$message],
            'rows' => [],
            'summary' => ['processed' => 0, 'applied' => 0, 'skipped' => 0, 'invalid' => 0, 'metrics' => ['pre' => 0, 'post' => 0, 'satisfaction' => 0]],
        ];
    }
}
