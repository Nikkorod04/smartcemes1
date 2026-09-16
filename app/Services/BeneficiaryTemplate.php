<?php

namespace App\Services;

/**
 * Official beneficiary XLSX import template (blueprint v4.7, 5.4).
 *
 * Fixed column headers, one row = one beneficiary. Users do not map columns
 * to fields. Unknown/misspelled beneficiary categories auto-map to "Other"
 * (D9 convention); per-row errors are shown on screen only and never
 * persisted. Rows with errors or duplicates are skipped on confirm — the
 * import never rejects the whole file.
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
