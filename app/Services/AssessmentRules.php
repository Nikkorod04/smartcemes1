<?php

namespace App\Services;

/**
 * Validation rules for the needs-assessment instrument, in one place.
 *
 * Extracted from `Assessments\Wizard::rules()` when the public form was added
 * (after-defense revision, Item 1). Two instruments now write to the same
 * `needs_assessments` table, so they must not disagree about what counts as an
 * acceptable answer. The staff wizard and the public submission endpoint both
 * call this; `AssessmentTemplate::COLUMNS` remains the field/vocabulary source
 * of truth, so adding a field is still a one-place change.
 */
class AssessmentRules
{
    /**
     * Rules for the instrument fields, keyed with a prefix.
     *
     * Mirrors the wizard exactly: only the multi-select fields are `array`,
     * everything else is a nullable scalar apart from the respondent identity
     * fields, which the caller adds (they are deliberately stricter).
     *
     * @return array<string, string>
     */
    public static function forForm(string $prefix = 'form'): array
    {
        $rules = [];

        foreach (AssessmentTemplate::COLUMNS as $field => $col) {
            if (in_array($col['type'], ['context', 'text', 'number'], true)) {
                continue;
            }

            if ($col['type'] === 'yesno') {
                $rules["$prefix.$field"] = 'nullable|in:Yes,No';

                continue;
            }

            $vocab = config('smartcemes.vocab.'.$col['vocab']);

            if ($col['type'] === 'single') {
                // The service rating is stored as ['overall' => '…'], so its
                // vocabulary is checked here but the shape is free.
                $rules["$prefix.$field"] = $field === 'barangay_service_ratings'
                    ? 'nullable'
                    : 'nullable|in:'.implode(',', $vocab);
            } else {
                $rules["$prefix.$field"] = 'array'.(isset($col['max']) ? '|max:'.$col['max'] : '');
                $rules["$prefix.$field".'.*'] = 'in:'.implode(',', $vocab);
            }

            if ($col['type'] !== 'yesno' && in_array('Other', $vocab ?? [], true)) {
                $rules["$prefix.$field".'_other'] = 'nullable|string|max:500';
            }
        }

        return $rules;
    }

    /**
     * The respondent identity block. Unprefixed so the public endpoint can
     * validate the payload it receives directly, while the wizard maps the
     * form array onto it.
     *
     * @return array<string, string>
     */
    public static function respondent(string $prefix = 'form'): array
    {
        return [
            "$prefix.respondent_first_name" => 'required|string|max:255',
            "$prefix.respondent_last_name" => 'required|string|max:255',
            "$prefix.respondent_age" => 'nullable|integer|between:15,120',
        ];
    }
}
