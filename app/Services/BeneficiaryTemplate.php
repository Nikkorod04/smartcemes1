<?php

namespace App\Services;

use App\Models\Beneficiary;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Official beneficiary XLSX import template (blueprint v4.7, 5.4; v2 2026-11-20).
 *
 * v2 replaces the free-text Barangay/Municipality pair with one
 * **Community / Partner School** name. The value must match a community that is
 * already linked to the target project; unknown names are row errors, never
 * guessed. A blank Contact Number stays blank - the old demo number is gone.
 */
class BeneficiaryTemplate
{
    /** @var array<string, array{header:string, required?:bool, vocab?:string}> */
    public const COLUMNS = [
        'first_name' => ['header' => 'First Name', 'required' => true],
        'middle_name' => ['header' => 'Middle Name'],
        'last_name' => ['header' => 'Last Name', 'required' => true],
        'age' => ['header' => 'Age'],
        'gender' => ['header' => 'Sex', 'vocab' => 'beneficiary_genders'],
        'phone' => ['header' => 'Contact Number'],
        'community' => ['header' => 'Community / Partner School', 'required' => true],
        'beneficiary_category' => ['header' => 'Beneficiary Category', 'required' => true, 'vocab' => 'beneficiary_categories'],
    ];

    public const TEMPLATE_FILENAME = 'beneficiary-import-template-v2.xlsx';

    public const MAX_ROWS = 500;

    /** Header row values in order. */
    public static function headers(): array
    {
        return array_map(fn ($col) => $col['header'], self::COLUMNS);
    }

    /** One beneficiary as a row in COLUMNS order (used by tests/tools only). */
    public static function rowFor(Beneficiary $beneficiary): array
    {
        return array_map(
            fn (string $field) => $field === 'community'
                ? $beneficiary->community?->name
                : $beneficiary->{$field},
            array_keys(self::COLUMNS)
        );
    }

    /**
     * @param  iterable<Beneficiary>  $beneficiaries
     * @return array<int, array<int, mixed>>
     */
    public static function rowsFor(iterable $beneficiaries): array
    {
        $rows = [];

        foreach ($beneficiaries as $beneficiary) {
            $rows[] = self::rowFor($beneficiary);
        }

        return $rows;
    }

    /**
     * Build the workbook. `$rows === null` produces the blank template; passing
     * rows produces an export-shaped sheet (kept for compatibility).
     *
     * @param  array<int, array<int, mixed>>|null  $rows
     */
    public static function build(?array $rows = null, ?string $contextLabel = null): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Beneficiaries');

        $isExport = $rows !== null;

        $sheet->setCellValue('A1', $isExport
            ? 'SmartCEMES - Beneficiary List - '.($contextLabel ?? 'Project').' - Leyte Normal University CESO'
            : 'SmartCEMES - Official Beneficiary Import Template v2 - Leyte Normal University CESO');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $sheet->setCellValue('A2', 'One row = one beneficiary. Do not change the column headers. First name, last name, Community / Partner School and Beneficiary Category are required.');
        $sheet->getStyle('A2')->getFont()->setSize(10)->setItalic(true);

        $sheet->setCellValue('A3', $isExport
            ? 'This is the project roster in the import format. Existing people in this project are updated; new rows are added.'
            : 'Community / Partner School must already exist and be linked to the target project. Contact Number is optional; leave it blank if unknown.');
        $sheet->getStyle('A3')->getFont()->setSize(10)->setItalic(true);

        $headers = self::headers();
        $sheet->fromArray($headers, null, 'A5');
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle('A5:'.$lastCol.'5')->getFont()->setBold(true)->setSize(9);
        $sheet->freezePane('A6');
        $sheet->getRowDimension(5)->setRowHeight(28);
        $sheet->getStyle('A5:'.$lastCol.'5')->getAlignment()->setWrapText(true)->setVertical('top');

        foreach (range(1, count($headers)) as $colIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setWidth(24);
        }

        if ($isExport) {
            if ($rows !== []) {
                $sheet->fromArray($rows, null, 'A6');
            }
        } else {
            // Grey example row: delete it before importing.
            $sheet->fromArray([
                'Juan', 'Reyes', 'Dela Cruz', '42', 'Male', '0917 123 4567', 'Brgy. San Jose', 'Farmer',
            ], null, 'A6');
            $sheet->getStyle('A6:'.$lastCol.'6')->getFont()->setItalic(true)->getColor()->setRGB('808080');
        }

        return $spreadsheet;
    }

    /**
     * Normalize one parsed spreadsheet row (field => raw string).
     *
     * Name and contact rules match the form (BeneficiaryRules). The community is
     * a name here; the caller resolves it against the target project.
     *
     * @return array{data: array<string, ?string>, errors: array<string, string>, category_other: ?string}
     */
    public static function normalizeRow(array $raw): array
    {
        $data = [];
        $errors = [];
        $categoryOther = null;

        foreach (self::COLUMNS as $field => $col) {
            $value = trim((string) ($raw[$field] ?? ''));

            if ($value === '') {
                if (($col['required'] ?? false) === true) {
                    $errors[$field] = 'Required - row will be skipped.';
                }
                $data[$field] = null;

                continue;
            }

            switch ($field) {
                case 'first_name':
                case 'middle_name':
                case 'last_name':
                    if (! preg_match(BeneficiaryRules::NAME_REGEX, $value)) {
                        $errors[$field] = 'Use letters, spaces, periods, hyphens and apostrophes only.';
                        $data[$field] = null;
                    } else {
                        $data[$field] = mb_substr($value, 0, 255);
                    }
                    break;

                case 'age':
                    if (! ctype_digit($value) || (int) $value < 1 || (int) $value > 120) {
                        $errors[$field] = "Expected a whole number 1-120, got \"{$value}\".";
                        $data[$field] = null;
                    } else {
                        $data[$field] = (string) (int) $value;
                    }
                    break;

                case 'phone':
                    if (! preg_match(BeneficiaryRules::CONTACT_REGEX, $value)) {
                        $errors[$field] = 'Use digits, spaces, +, hyphens or parentheses, with at least 7 digits.';
                        $data[$field] = null;
                    } else {
                        $data[$field] = mb_substr($value, 0, 20);
                    }
                    break;

                case 'community':
                    $data[$field] = mb_substr($value, 0, 150);
                    break;

                case 'gender':
                case 'beneficiary_category':
                    $vocab = config('smartcemes.'.$col['vocab']);
                    $matched = null;
                    foreach ($vocab as $option) {
                        if (strcasecmp($option, $value) === 0) {
                            $matched = $option;
                            break;
                        }
                    }
                    if ($matched === null && $field === 'beneficiary_category' && in_array('Other', $vocab, true)) {
                        $matched = 'Other';
                        $categoryOther = $value;
                    }
                    if ($matched === null) {
                        $errors[$field] = "Value \"{$value}\" is not one of: ".implode(', ', $vocab).'.';
                        $data[$field] = null;
                    } else {
                        $data[$field] = $matched;
                    }
                    break;

                default:
                    $data[$field] = mb_substr($value, 0, 255);
            }
        }

        return ['data' => $data, 'errors' => $errors, 'category_other' => $categoryOther];
    }
}
