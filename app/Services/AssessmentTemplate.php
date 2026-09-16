<?php

namespace App\Services;

/**
 * Official needs-assessment XLSX import template (D11).
 *
 * The template is AUTHORED by this project as a VERTICAL form-style sheet:
 * column A carries the fixed field labels grouped under section separators,
 * column B is the answer cell, and column C carries fill-up guides (full
 * §7 option lists). One file = one respondent. Users do not map fields.
 *
 * v3 semantics (blueprint v4.9): most former multi-select fields are now
 * SINGLE-select; problems fields are multi-select capped at 3; preferred
 * training days ("Flexible") and medical supplies ("No supplies") are
 * exclusive options; religion and several closed lists carry no "Other"
 * (unknown XLSX values become per-field errors instead of auto-mapping).
 * Dropdown validation on answer cells is NON-STRICT — suggestions only.
 */
class AssessmentTemplate
{
    /**
     * @var array<string, array{
     *     label: string,
     *     type: string,
     *     vocab?: string,
     *     exclusive?: string,
     *     max?: int
     * }>
     */
    public const COLUMNS = [
        // Import context block
        'community_name' => ['label' => 'Community', 'type' => 'context'],
        'quarter' => ['label' => 'Quarter', 'type' => 'context'],
        'year' => ['label' => 'Year', 'type' => 'context'],
        'proposal_reference' => ['label' => 'Proposal Reference (optional)', 'type' => 'context'],

        // Section I
        'respondent_first_name' => ['label' => 'First Name', 'type' => 'text'],
        'respondent_middle_name' => ['label' => 'Middle Name', 'type' => 'text'],
        'respondent_last_name' => ['label' => 'Last Name', 'type' => 'text'],
        'respondent_age' => ['label' => 'Age', 'type' => 'number'],
        'respondent_civil_status' => ['label' => 'Civil Status', 'type' => 'single', 'vocab' => 'respondent_civil_status'],
        'respondent_sex' => ['label' => 'Sex', 'type' => 'single', 'vocab' => 'respondent_sex'],
        'respondent_religion' => ['label' => 'Religion', 'type' => 'single', 'vocab' => 'respondent_religion'],
        'respondent_educational_attainment' => ['label' => 'Highest Educational Attainment', 'type' => 'single', 'vocab' => 'respondent_educational_attainment'],

        // Section II
        'family_composition' => ['label' => 'Family Composition', 'type' => 'single', 'vocab' => 'family_composition'],
        'household_member_currently_studying' => ['label' => 'Household Member Currently Studying', 'type' => 'yesno'],
        'household_members_in_organization' => ['label' => 'Household Members in Organization', 'type' => 'single', 'vocab' => 'household_members_in_organization'],

        // Section III
        'livelihood_options' => ['label' => 'Main Source of Household Livelihood', 'type' => 'single', 'vocab' => 'livelihood_options'],
        'desired_training' => ['label' => 'Desired Livelihood Training', 'type' => 'single', 'vocab' => 'desired_training'],

        // Section IV
        'barangay_educational_facilities' => ['label' => 'Barangay Educational Facilities', 'type' => 'multi', 'vocab' => 'barangay_educational_facilities'],
        'interested_in_continuing_studies' => ['label' => 'Interested in Continuing Studies', 'type' => 'yesno'],
        'areas_of_educational_interest' => ['label' => 'Area of Educational Interest', 'type' => 'single', 'vocab' => 'areas_of_educational_interest'],
        'preferred_training_time' => ['label' => 'Preferred Training Time', 'type' => 'single', 'vocab' => 'preferred_training_time'],
        'preferred_training_days' => ['label' => 'Preferred Training Days', 'type' => 'multi', 'vocab' => 'preferred_training_days', 'exclusive' => 'Flexible'],

        // Section V
        'common_illnesses' => ['label' => 'Most Common Illness', 'type' => 'single', 'vocab' => 'common_illnesses'],
        'action_when_sick' => ['label' => 'Action When Sick', 'type' => 'single', 'vocab' => 'action_when_sick'],
        'barangay_medical_supplies_available' => ['label' => 'Barangay Medical Supplies Available', 'type' => 'multi', 'vocab' => 'barangay_medical_supplies_available', 'exclusive' => 'No supplies'],
        'has_barangay_health_programs' => ['label' => 'Has Barangay Health Programs', 'type' => 'yesno'],
        'benefits_from_barangay_programs' => ['label' => 'Benefits from Barangay Programs', 'type' => 'yesno'],
        'programs_benefited_from' => ['label' => 'Programs Benefited From', 'type' => 'multi', 'vocab' => 'programs_benefited_from'],
        'water_source' => ['label' => 'Water Source', 'type' => 'single', 'vocab' => 'water_source'],
        'water_source_distance' => ['label' => 'Water Source Distance', 'type' => 'single', 'vocab' => 'water_source_distance'],
        'garbage_disposal_method' => ['label' => 'Garbage Disposal Method', 'type' => 'single', 'vocab' => 'garbage_disposal_method'],
        'has_own_toilet' => ['label' => 'Has Own Toilet', 'type' => 'yesno'],
        'toilet_type' => ['label' => 'Toilet Type', 'type' => 'single', 'vocab' => 'toilet_type'],
        'keeps_animals' => ['label' => 'Keeps Animals', 'type' => 'yesno'],
        'animals_kept' => ['label' => 'Animals Kept', 'type' => 'multi', 'vocab' => 'animals_kept'],

        // Section VI
        'house_type' => ['label' => 'House Type', 'type' => 'single', 'vocab' => 'house_type'],
        'tenure_status' => ['label' => 'Tenure Status', 'type' => 'single', 'vocab' => 'tenure_status'],
        'has_electricity' => ['label' => 'Has Electricity', 'type' => 'yesno'],
        'light_source_without_power' => ['label' => 'Light Source Without Power', 'type' => 'single', 'vocab' => 'light_source_without_power'],
        'appliances_owned' => ['label' => 'Appliances Owned', 'type' => 'multi', 'vocab' => 'appliances_owned'],

        // Section VII
        'barangay_recreational_facilities' => ['label' => 'Barangay Recreational Facilities', 'type' => 'multi', 'vocab' => 'barangay_recreational_facilities'],
        'use_of_free_time' => ['label' => 'Use of Free Time', 'type' => 'multi', 'vocab' => 'use_of_free_time'],
        'member_of_organization' => ['label' => 'Member of Organization', 'type' => 'yesno'],
        'organization_types' => ['label' => 'Organization Type', 'type' => 'single', 'vocab' => 'organization_types'],
        'organization_meeting_frequency' => ['label' => 'Organization Meeting Frequency', 'type' => 'single', 'vocab' => 'organization_meeting_frequency'],
        'organization_usual_activities' => ['label' => 'Organization Usual', 'type' => 'single', 'vocab' => 'organization_usual_activities'],
        'position_in_organization' => ['label' => 'Position in Organization', 'type' => 'single', 'vocab' => 'position_in_organization'],

        // Section VIII
        'family_problems' => ['label' => 'Family Problems', 'type' => 'multi', 'vocab' => 'family_problems', 'max' => 3],
        'health_problems' => ['label' => 'Health Problems', 'type' => 'multi', 'vocab' => 'health_problems', 'max' => 3],
        'educational_problems' => ['label' => 'Educational Problems', 'type' => 'multi', 'vocab' => 'educational_problems', 'max' => 3],
        'employment_problems' => ['label' => 'Employment Problems', 'type' => 'multi', 'vocab' => 'employment_problems', 'max' => 3],
        'infrastructure_problems' => ['label' => 'Infrastructure Problems', 'type' => 'multi', 'vocab' => 'infrastructure_problems', 'max' => 3],
        'economic_problems' => ['label' => 'Economic Problems', 'type' => 'multi', 'vocab' => 'economic_problems', 'max' => 3],
        'security_problems' => ['label' => 'Security Problems', 'type' => 'multi', 'vocab' => 'security_problems', 'max' => 3],

        // Section IX
        'barangay_service_ratings' => ['label' => 'Barangay Service Rating', 'type' => 'single', 'vocab' => 'barangay_service_ratings'],
        'available_for_training' => ['label' => 'Available for Training', 'type' => 'yesno'],
        'reason_not_available' => ['label' => 'Reason Not Available (if not available)', 'type' => 'single', 'vocab' => 'reason_not_available'],
    ];

    /**
     * Section separators for the vertical template: the first field of each
     * block (in COLUMNS order) => separator label rendered on its own row.
     */
    public const SECTION_BREAKS = [
        'community_name' => 'Import Context',
        'respondent_first_name' => 'Section I — Respondent Information',
        'family_composition' => 'Section II — Family Composition',
        'livelihood_options' => 'Section III — Economic / Livelihood',
        'barangay_educational_facilities' => 'Section IV — Education',
        'common_illnesses' => 'Section V — Health and Sanitation',
        'house_type' => 'Section VI — Housing and Basic Amenities',
        'barangay_recreational_facilities' => 'Section VII — Recreation, Organization, and Social Participation',
        'family_problems' => 'Section VIII — Problems and Priorities',
        'barangay_service_ratings' => 'Section IX — Service Ratings and Summary',
    ];

    public const TEMPLATE_FILENAME = 'assessment-template-v3.xlsx';

    /** Field labels in template order. */
    public static function labels(): array
    {
        return array_map(fn ($col) => $col['label'], self::COLUMNS);
    }

    /**
     * Fill-up guide (column C of the vertical template): how to answer the
     * field, with the FULL §7 option list so the sheet is self-contained
     * for field encoding.
     */
    public static function guide(string $field): ?string
    {
        $col = self::COLUMNS[$field] ?? null;

        if ($col === null) {
            return null;
        }

        $vocab = isset($col['vocab']) ? config('smartcemes.vocab.'.$col['vocab']) : null;
        $hasOther = in_array('Other', $vocab ?? [], true);

        return match ($col['type']) {
            'context' => match ($field) {
                'community_name' => 'Type the community name (e.g. Brgy. San Jose) — picked up automatically on import.',
                'quarter' => 'Calendar quarter: 1 = Jan–Mar, 2 = Apr–Jun, 3 = Jul–Sep, 4 = Oct–Dec.',
                'year' => 'Four-digit year, e.g. 2026.',
                default => 'Optional. Proposal code, e.g. EXT-2026-003.',
            },
            'text' => 'Type the answer. Letters only — leave blank if none.',
            'number' => 'Whole number between 15 and 120.',
            'yesno' => 'Choose or type Yes or No.',
            'multi' => sprintf(
                'Comma-separated — choose %s: %s.%s%s',
                isset($col['max']) ? 'up to '.$col['max'] : 'any',
                implode(', ', $vocab ?? []),
                $hasOther ? ' Values not on the list are kept as "Other".' : ' Use only the listed values.',
                isset($col['exclusive']) ? sprintf(' "%s" clears the other choices.', $col['exclusive']) : ''
            ),
            default => 'Choose one: '.implode(' / ', $vocab ?? []).($hasOther ? '. Anything else is kept as "Other".' : '. Use only the listed values — anything else is reported as an error.'),
        };
    }

    /**
     * Normalize a parsed raw value for a field per §7/D9:
     * - text fields: kept as-is (length-capped to the 255-char column).
     * - number fields: validated as an integer age 15-120 (wizard parity);
     *   anything else is an error shown on screen only.
     * - multi fields: split on commas; unknown/misspelled items auto-map to
     *   "Other" keeping raw text in other_text — but only when the field's
     *   vocabulary still contains "Other" (v4.9 closed lists error instead).
     * - single fields: case-insensitive canonical match, else "Other" + raw
     *   when the vocabulary has "Other", otherwise an on-screen error.
     * - yes/no: strict Yes/No (case-insensitive input); anything else is an
     *   error shown on screen only.
     */
    public static function normalize(string $field, ?string $raw, array $otherText): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return ['value' => $field === 'barangay_service_ratings' ? ['overall' => null] : null, 'other' => null, 'error' => null];
        }

        $col = self::COLUMNS[$field] ?? ['type' => 'text'];
        $type = $col['type'];
        $vocab = isset($col['vocab']) ? config('smartcemes.vocab.'.$col['vocab']) : null;

        switch ($type) {
            case 'text':
                if (mb_strlen($raw) > 255) {
                    return ['value' => null, 'other' => null, 'error' => 'Value is longer than 255 characters'];
                }

                return ['value' => $raw, 'other' => null, 'error' => null];

            case 'number':
                $int = filter_var($raw, FILTER_VALIDATE_INT);
                if ($int === false || $int < 15 || $int > 120) {
                    return ['value' => null, 'other' => null, 'error' => "Expected an age between 15 and 120, got \"$raw\""];
                }

                return ['value' => $int, 'other' => null, 'error' => null];

            case 'yesno':
                $v = ucfirst(strtolower($raw));
                if (in_array($v, ['Yes', 'No'], true)) {
                    return ['value' => $v, 'other' => null, 'error' => null];
                }

                return ['value' => null, 'other' => null, 'error' => "Expected Yes/No, got \"$raw\""];

            case 'multi':
                $otherText = [];
                $out = [];
                foreach (preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $item) {
                    $item = trim($item);
                    if ($item === '') {
                        continue;
                    }
                    $matched = null;
                    foreach ($vocab as $option) {
                        if (strcasecmp($option, $item) === 0) {
                            $matched = $option;
                            break;
                        }
                    }
                    if ($matched === null && in_array('Other', $vocab, true)) {
                        $matched = 'Other';
                        $otherText[$field][] = $item;
                    }
                    if ($matched !== null && ! in_array($matched, $out, true)) {
                        $out[] = $matched;
                    }
                }
                if (count($out) > ($col['max'] ?? PHP_INT_MAX)) {
                    return ['value' => null, 'other' => null, 'error' => 'Too many values — choose up to '.($col['max'] ?? 'the limit').", got \"$raw\""];
                }
                if ($out === [] && trim($raw) !== '') {
                    return ['value' => null, 'other' => null, 'error' => "No mappable values in \"$raw\""];
                }

                return ['value' => $out, 'other' => $otherText, 'error' => null];

            default: // single
                foreach ($vocab ?? [] as $option) {
                    if (strcasecmp($option, $raw) === 0) {
                        return ['value' => $field === 'barangay_service_ratings' ? ['overall' => $option] : $option, 'other' => null, 'error' => null];
                    }
                }
                if (in_array('Other', $vocab ?? [], true)) {
                    return [
                        'value' => $field === 'barangay_service_ratings' ? ['overall' => 'Other'] : 'Other',
                        'other' => [$field => [$raw]],
                        'error' => null,
                    ];
                }

                return ['value' => null, 'other' => null, 'error' => "Value \"$raw\" is not in the standard list"];
        }
    }
}
