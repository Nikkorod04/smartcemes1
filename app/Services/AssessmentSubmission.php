<?php

namespace App\Services;

use App\Models\NeedsAssessment;

/**
 * Turns an answered instrument into a `needs_assessments` row.
 *
 * There are now three instruments writing to that table — the staff wizard, the
 * XLSX import and the public form — and only the first two were written by the
 * same person. Rather than let the public endpoint grow its own copy of "strip
 * the `_other` keys, fold the free text into `other_text`, drop empty service
 * ratings", both entry points call this. A stored public submission and a stored
 * wizard submission are then the same shape by construction, which is what lets
 * the review drawer render either one without special cases.
 */
class AssessmentSubmission
{
    /**
     * @param  array<string, mixed>  $form  Answers keyed by template field.
     * @param  array{community_id: int, quarter: int, year: int, source: string, uploaded_by?: int|null, client_uuid?: string|null, captured_at?: mixed}  $context
     */
    public static function create(array $form, array $context): NeedsAssessment
    {
        $data = self::normalize($form);

        return NeedsAssessment::create([
            ...$data,
            'community_id' => $context['community_id'],
            'quarter' => $context['quarter'],
            'year' => $context['year'],
            'source' => $context['source'],
            'uploaded_by' => $context['uploaded_by'] ?? null,
            'client_uuid' => $context['client_uuid'] ?? null,
            'captured_at' => $context['captured_at'] ?? now(),
            'review_status' => 'pending',
        ]);
    }

    /**
     * The parts of the form that are not stored as-is.
     *
     * - the service rating is stored as an object with empty keys stripped
     * - every `<field>_other` key is folded into `other_text[<field>]`, and only
     *   kept when "Other" is actually one of the chosen answers
     *
     * @param  array<string, mixed>  $form
     * @return array<string, mixed>
     */
    public static function normalize(array $form): array
    {
        $data = $form;

        $data['barangay_service_ratings'] = array_filter(
            $data['barangay_service_ratings'] ?? [],
            fn ($v) => $v !== null && $v !== ''
        );

        $otherText = [];

        foreach ($data as $field => $value) {
            if (! str_ends_with($field, '_other')) {
                continue;
            }

            unset($data[$field]);

            $base = substr($field, 0, -strlen('_other'));
            $text = trim((string) $value);
            $baseValue = $data[$base] ?? '';
            $selected = is_array($baseValue)
                ? in_array('Other', $baseValue, true)
                : $baseValue === 'Other';

            if ($text !== '' && $selected) {
                $otherText[$base] = [$text];
            }
        }

        if ($otherText !== []) {
            $data['other_text'] = $otherText;
        }

        return $data;
    }
}
