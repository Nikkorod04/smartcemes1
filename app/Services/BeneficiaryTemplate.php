<?php

namespace App\Services;

use App\Models\Beneficiary;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Official beneficiary XLSX import template (blueprint v4.7, 5.4).
 *
 * Fixed column headers, one row = one beneficiary. Users do not map columns
 * to fields. Unknown/misspelled beneficiary categories auto-map to "Other"
 * (D9 convention); per-row errors are shown on screen only and never
 * persisted. Rows with errors or duplicates are skipped on confirm — the
 * import never rejects the whole file.
 *
 * SINCE 2026-10-05 this class also OWNS THE WORKBOOK LAYOUT (`build()`), so the
 * blank template the Director downloads and the export of a project's enrolled
 * list are produced by one implementation and cannot drift apart. The export is
 * deliberately the SAME sheet shape as the import template: it round-trips, so a
 * Director can download the list, edit it, and import it straight back.
 */
class BeneficiaryTemplate
{
    /** Demo default applied when the Contact Number column is left blank. */
    public const DEFAULT_CONTACT_NUMBER = '09123456789';

    /** @var array<string, array{header:string, required?:bool, vocab?:string}> */
    public const COLUMNS = [
        'first_name' => ['header' => 'First Name', 'required' => true],
        'middle_name' => ['header' => 'Middle Name'],
        'last_name' => ['header' => 'Last Name', 'required' => true],
        'age' => ['header' => 'Age'],
        'gender' => ['header' => 'Sex', 'vocab' => 'beneficiary_genders'],
        'phone' => ['header' => 'Contact Number'],
        'barangay' => ['header' => 'Barangay', 'required' => true],
        'municipality' => ['header' => 'Municipality / City'],
        'beneficiary_category' => ['header' => 'Beneficiary Category', 'required' => true, 'vocab' => 'beneficiary_categories'],
    ];

    public const TEMPLATE_FILENAME = 'beneficiary-import-template-v1.xlsx';

    public const MAX_ROWS = 500;

    /** Header row values in order. */
    public static function headers(): array
    {
        return array_map(fn ($col) => $col['header'], self::COLUMNS);
    }

    /**
     * One beneficiary as a row in COLUMNS order.
     *
     * Keyed off `array_keys(self::COLUMNS)` rather than a hand-written list, so
     * adding a column to the template cannot silently desynchronise the export.
     */
    public static function rowFor(Beneficiary $beneficiary): array
    {
        return array_map(
            fn (string $field) => $beneficiary->{$field},
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
     * Build the workbook.
     *
     * `$rows === null` produces the BLANK template — the grey example row is
     * written so encoders can see the expected shape. Passing rows produces the
     * EXPORT instead: same headers, same widths, same freeze pane, the project's
     * own data where the example row would be.
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
            ? 'SmartCEMES — Beneficiary List · '.($contextLabel ?? 'Program').' · Leyte Normal University CESO'
            : 'SmartCEMES — Official Beneficiary Import Template v1 · Leyte Normal University CESO');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $sheet->setCellValue('A2', 'One row = one beneficiary. Do not change the column headers. First name, last name, barangay and beneficiary category are required.');
        $sheet->getStyle('A2')->getFont()->setSize(10)->setItalic(true);

        $sheet->setCellValue('A3', $isExport
            ? 'This is the project\'s enrolled list in the import format — edit it and import it straight back.'
            : 'Blank Contact Number defaults to '.self::DEFAULT_CONTACT_NUMBER.'. Unknown beneficiary categories auto-map to "Other".');
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
            // Example row so encoders see the expected format (delete before importing).
            $sheet->fromArray([
                'Juan', 'Reyes', 'Dela Cruz', '42', 'Male', self::DEFAULT_CONTACT_NUMBER,
                'San Jose', 'Tacloban City', 'Farmer',
            ], null, 'A6');
            $sheet->getStyle('A6:'.$lastCol.'6')->getFont()->setItalic(true)->getColor()->setRGB('808080');
        }

        return $spreadsheet;
    }

    /**
     * Normalize one parsed spreadsheet row (field => raw string) into
     * importable beneficiary data. Returns the cleaned values, per-field
     * errors (screen-only), and whether the category was auto-mapped to
     * "Other" with the raw label kept for the preview.
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
                    $errors[$field] = 'Required — row will be skipped.';
                }
                $data[$field] = null;

                continue;
            }

            switch ($field) {
                case 'age':
                    if (! ctype_digit($value) || (int) $value < 1 || (int) $value > 120) {
                        $errors[$field] = "Expected a whole number 1–120, got \"{$value}\".";
                        $data[$field] = null;
                    } else {
                        $data[$field] = (string) (int) $value;
                    }
                    break;

                case 'phone':
                    $data[$field] = mb_substr(preg_replace('/[\s\-()]/', '', $value) ?: $value, 0, 20);
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

        // Demo default: every beneficiary gets a contact number.
        if (($data['phone'] ?? null) === null && ! isset($errors['phone'])) {
            $data['phone'] = self::DEFAULT_CONTACT_NUMBER;
        }

        return ['data' => $data, 'errors' => $errors, 'category_other' => $categoryOther];
    }
}
