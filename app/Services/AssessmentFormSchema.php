<?php

namespace App\Services;

/**
 * Everything the public needs-assessment form needs to render itself from the
 * canonical instrument (after-defense revision, Item 1).
 *
 * The public form is deliberately NOT a Livewire component: it holds all
 * answers in the browser so a respondent on mobile data does not trigger a
 * server round trip per tap (the staff wizard does one per chip). That means
 * the browser needs the field types, the multi-select limits and the
 * conditional-visibility rules as data, which this class supplies.
 *
 * `AssessmentTemplate::COLUMNS` stays the single source of truth for fields and
 * vocabularies; this only adds presentation metadata and the five display
 * steps, so a field added to the template appears here with no extra work.
 */
class AssessmentFormSchema
{
    /**
     * The five display steps. Same grouping the staff wizard uses, so the two
     * instruments are recognisably the same questionnaire.
     */
    public const STEPS = [
        ['n' => 1, 'title' => 'Profile', 'sections' => ['I', 'II'], 'blurb' => 'You and your household'],
        ['n' => 2, 'title' => 'Livelihood & Education', 'sections' => ['III', 'IV'], 'blurb' => 'Work, training and schooling'],
        ['n' => 3, 'title' => 'Health & Housing', 'sections' => ['V', 'VI'], 'blurb' => 'Health, water, sanitation and home'],
        ['n' => 4, 'title' => 'Community Life', 'sections' => ['VII', 'VIII'], 'blurb' => 'Organizations, free time and problems'],
        ['n' => 5, 'title' => 'Service Ratings', 'sections' => ['IX'], 'blurb' => 'Final questions and consent'],
    ];

    /**
     * Public-facing wording for labels that read like internal shorthand in the
     * template ("Organization Usual"). Field keys and vocabularies are
     * untouched — this is presentation only.
     */
    public const LABEL_OVERRIDES = [
        'barangay_educational_facilities' => 'Educational facilities in the barangay',
        'barangay_medical_supplies_available' => 'Medical supplies available in the barangay',
        'barangay_recreational_facilities' => 'Recreational facilities in the barangay',
        'household_member_currently_studying' => 'Is a household member currently studying?',
        'household_members_in_organization' => 'Household members who belong to an organization',
        'has_barangay_health_programs' => 'Does the barangay have health programs?',
        'benefits_from_barangay_programs' => 'Has the household benefited from these programs?',
        'has_own_toilet' => 'Does the household have its own toilet?',
        'keeps_animals' => 'Does the household keep animals?',
        'has_electricity' => 'Does the household have electricity?',
        'member_of_organization' => 'Is a household member part of an organization?',
        'available_for_training' => 'Is a household member available for training?',
        'organization_usual_activities' => 'Usual organization activities',
        'reason_not_available' => 'Reason not available for training',
    ];

    /**
     * Empty answer state for the instrument.
     *
     * @return array<string, mixed>
     */
    public static function initialState(): array
    {
        $form = [];

        foreach (AssessmentTemplate::COLUMNS as $field => $col) {
            if ($col['type'] === 'context') {
                continue;
            }

            $form[$field] = $col['type'] === 'multi' ? [] : '';
        }

        // Present in the table and the wizard, but not in the template column
        // list (it is free text, so the XLSX sheet has no cell for it).
        $form['general_feedback'] = '';

        // Stored as ['overall' => 'Fair'] rather than a bare string.
        $form['barangay_service_ratings'] = ['overall' => ''];

        return $form;
    }

    /**
     * Field => answer shape, for the browser's chip toggling.
     *
     * @return array<string, string>
     */
    public static function types(): array
    {
        $types = [];

        foreach (AssessmentTemplate::COLUMNS as $field => $col) {
            if ($col['type'] === 'context') {
                continue;
            }

            $types[$field] = $field === 'barangay_service_ratings' ? 'rating' : $col['type'];
        }

        $types['general_feedback'] = 'text';

        return $types;
    }

    /**
     * Fields whose vocabulary contains "Other" and therefore need a
     * specify-yourself input.
     *
     * @return list<string>
     */
    public static function otherFields(): array
    {
        $fields = [];

        foreach (AssessmentTemplate::COLUMNS as $field => $col) {
            if (in_array($col['type'], ['context', 'yesno', 'text', 'number'], true)) {
                continue;
            }

            $vocab = config('smartcemes.vocab.'.$col['vocab']) ?? [];

            if (in_array('Other', $vocab, true)) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Multi-select caps (problems: up to 3).
     *
     * @return array<string, int>
     */
    public static function maxSelect(): array
    {
        $max = [];

        foreach (AssessmentTemplate::COLUMNS as $field => $col) {
            if (isset($col['max'])) {
                $max[$field] = (int) $col['max'];
            }
        }

        return $max;
    }

    /**
     * Options that clear the rest of their group when picked ("Flexible",
     * "No supplies").
     *
     * @return array<string, string>
     */
    public static function exclusiveOptions(): array
    {
        $exclusive = [];

        foreach (AssessmentTemplate::COLUMNS as $field => $col) {
            if (isset($col['exclusive'])) {
                $exclusive[$field] = $col['exclusive'];
            }
        }

        return $exclusive;
    }

    /**
     * Conditional visibility, mirroring the `$show` map in the staff wizard and
     * the clearing rules in `Wizard::updated()`. Keys are field names; values
     * are Alpine expressions evaluated against the form state.
     *
     * @return array<string, string>
     */
    public static function visibility(): array
    {
        return [
            'areas_of_educational_interest' => 'show().areas',
            'benefits_from_barangay_programs' => 'show().benefits',
            'programs_benefited_from' => 'show().programs',
            'toilet_type' => 'show().toilet',
            'animals_kept' => 'show().animals',
            'appliances_owned' => 'show().appliances',
            'light_source_without_power' => 'show().lighting',
            'member_of_organization' => 'show().member',
            'organization_types' => 'show().org',
            'organization_meeting_frequency' => 'show().org',
            'organization_usual_activities' => 'show().org',
            'position_in_organization' => 'show().org',
            'reason_not_available' => 'show().reason',
        ];
    }

    /** Which display step a section belongs to. */
    public static function stepForSection(string $key): int
    {
        foreach (self::STEPS as $step) {
            if (in_array($key, $step['sections'], true)) {
                return $step['n'];
            }
        }

        return 1;
    }

    /** Public label for a field, falling back to the template label. */
    public static function labelFor(string $field): string
    {
        return self::LABEL_OVERRIDES[$field]
            ?? AssessmentTemplate::COLUMNS[$field]['label']
            ?? AssessmentTemplate::labelFor($field);
    }

    /**
     * Short hint under the field label.
     */
    public static function hintFor(string $field): ?string
    {
        $col = AssessmentTemplate::COLUMNS[$field] ?? null;

        if ($col === null) {
            return null;
        }

        if ($col['type'] === 'multi') {
            return isset($col['max'])
                ? 'choose up to '.$col['max']
                : 'choose all that apply';
        }

        return null;
    }
}
