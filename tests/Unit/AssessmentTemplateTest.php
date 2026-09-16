<?php

namespace Tests\Unit;

use App\Services\AssessmentTemplate;
use Tests\TestCase;

class AssessmentTemplateTest extends TestCase
{
    public function test_exact_vocab_match_is_kept(): void
    {
        $result = AssessmentTemplate::normalize('respondent_religion', 'Roman Catholic', []);

        $this->assertSame('Roman Catholic', $result['value']);
        $this->assertNull($result['other']);
        $this->assertNull($result['error']);
    }

    public function test_case_insensitive_match_maps_to_canonical_case(): void
    {
        $result = AssessmentTemplate::normalize('respondent_religion', 'iglesia ni cristo', []);

        $this->assertSame('Iglesia ni Cristo', $result['value']);
    }

    public function test_single_field_misspelled_value_auto_maps_to_other_with_raw_text(): void
    {
        // v4.9: single-select fields whose vocabulary keeps "Other" (e.g.
        // Main Source of Household Livelihood) still auto-map misspelled
        // values with the raw text preserved (D9).
        $result = AssessmentTemplate::normalize('livelihood_options', 'sari-sari store', []);

        $this->assertSame('Other', $result['value']);
        $this->assertSame(['livelihood_options' => ['sari-sari store']], $result['other']);
        $this->assertNull($result['error']);
    }

    public function test_religion_is_a_closed_list_without_other(): void
    {
        // v4.9: religion (and other closed lists) no longer carry "Other" —
        // unknown values become per-field errors instead of auto-mapping.
        $this->assertNotContains('Other', config('smartcemes.vocab.respondent_religion'));

        $result = AssessmentTemplate::normalize('respondent_religion', 'Catholic', []);

        $this->assertNull($result['value']);
        $this->assertNull($result['other']);
        $this->assertNotNull($result['error']);
    }

    public function test_multi_values_split_and_unknown_maps_to_other(): void
    {
        $result = AssessmentTemplate::normalize('animals_kept', 'Dog, Iguana', []);

        $this->assertSame(['Dog', 'Other'], $result['value']);
        $this->assertSame(['animals_kept' => ['Iguana']], $result['other']);
    }

    public function test_legacy_single_values_outside_vocabulary_map_to_other(): void
    {
        // Single-select fields that keep "Other" (D9 convention).
        $this->assertSame('Other', AssessmentTemplate::normalize('water_source', 'NAWASA', [])['value']);
        $this->assertSame('Other', AssessmentTemplate::normalize('light_source_without_power', 'Oil lamp', [])['value']);
        $this->assertSame('Other', AssessmentTemplate::normalize('house_type', 'Half concrete/wood', [])['value']);
        $this->assertSame('Other', AssessmentTemplate::normalize('toilet_type', 'Antipolo style', [])['value']);
    }

    public function test_closed_multi_lists_reject_unknown_values(): void
    {
        // v4.9: problems fields have no "Other" — unknown values error.
        $result = AssessmentTemplate::normalize('family_problems', 'Low income, Something else', []);

        $this->assertSame(['Low income'], $result['value']);
        $this->assertSame([], $result['other']);

        $all = AssessmentTemplate::normalize('family_problems', 'Something else', []);
        $this->assertNull($all['value']);
        $this->assertNotNull($all['error']);
    }

    public function test_problem_fields_are_capped_at_three_values(): void
    {
        $result = AssessmentTemplate::normalize('family_problems', 'Low income, Insufficient food, Poor housing, Family conflict', []);

        $this->assertNull($result['value']);
        $this->assertNotNull($result['error']);
        $this->assertStringContainsString('up to 3', $result['error']);
    }

    public function test_yes_no_strict_with_error_for_garbage(): void
    {
        $yes = AssessmentTemplate::normalize('has_electricity', 'YES', []);
        $this->assertSame('Yes', $yes['value']);

        $bad = AssessmentTemplate::normalize('has_electricity', 'maybe', []);
        $this->assertNull($bad['value']);
        $this->assertNotNull($bad['error']);
    }

    public function test_service_rating_stores_overall_key(): void
    {
        $result = AssessmentTemplate::normalize('barangay_service_ratings', 'Very good', []);

        $this->assertSame(['overall' => 'Very good'], $result['value']);
    }

    public function test_blank_input_is_no_error(): void
    {
        $result = AssessmentTemplate::normalize('respondent_sex', '', []);

        $this->assertNull($result['value']);
        $this->assertNull($result['error']);
    }

    public function test_multi_vocabulary_integrity(): void
    {
        // v4.9: the true multi-select fields and their vocabularies.
        $multiFields = [
            'barangay_educational_facilities', 'preferred_training_days', 'barangay_medical_supplies_available',
            'programs_benefited_from', 'animals_kept', 'appliances_owned', 'barangay_recreational_facilities',
            'use_of_free_time', 'family_problems', 'health_problems', 'educational_problems',
            'employment_problems', 'infrastructure_problems', 'economic_problems', 'security_problems',
        ];

        $closedLists = [
            'preferred_training_days', 'appliances_owned', 'barangay_recreational_facilities',
            'use_of_free_time', 'family_problems', 'health_problems', 'educational_problems',
            'employment_problems', 'infrastructure_problems', 'economic_problems', 'security_problems',
        ];

        foreach (AssessmentTemplate::COLUMNS as $field => $col) {
            if (in_array($field, $multiFields, true)) {
                $this->assertSame('multi', $col['type'], "$field must stay multi");
                $this->assertIsArray(config('smartcemes.vocab.'.$col['vocab']), "vocab missing for $field");
            }
        }

        foreach ($closedLists as $field) {
            $this->assertNotContains('Other', config('smartcemes.vocab.'.AssessmentTemplate::COLUMNS[$field]['vocab']), "$field must not offer Other");
        }

        // Exclusive options exist in their vocabularies.
        $this->assertSame('Flexible', AssessmentTemplate::COLUMNS['preferred_training_days']['exclusive']);
        $this->assertSame('No supplies', AssessmentTemplate::COLUMNS['barangay_medical_supplies_available']['exclusive']);

        // Problems are capped at 3.
        foreach ($closedLists as $field) {
            if (str_ends_with($field, '_problems')) {
                $this->assertSame(3, AssessmentTemplate::COLUMNS[$field]['max']);
            }
        }
    }
}
