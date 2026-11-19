<?php

namespace App\Livewire\Assessments;

use App\Models\Community;
use App\Models\NeedsAssessment;
use App\Services\AssessmentRules;
use App\Services\AssessmentSubmission;
use App\Services\AssessmentTemplate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Wizard extends Component
{
    public string $step = '1'; // 1..5 wizard steps (client-side display only)

    public int $communityId = 0;

    public int $quarter = 1;

    public int $year = 0;

    public array $form = [];

    public function mount(): void
    {
        $this->year = (int) now()->format('Y');

        $this->form = [];
        foreach (AssessmentTemplate::COLUMNS as $field => $col) {
            if (in_array($col['type'], ['context'], true)) {
                continue;
            }

            $this->form[$field] = match ($col['type']) {
                'multi' => [],
                'number' => '',
                default => '',
            };
        }
        $this->form['barangay_service_ratings'] = ['overall' => ''];
    }

    public function toggleOption(string $field, string $option): void
    {
        $col = AssessmentTemplate::COLUMNS[$field] ?? null;

        if ($col === null || $col['type'] !== 'multi') {
            return;
        }

        $exclusive = $col['exclusive'] ?? null;
        $values = is_array($this->form[$field] ?? null) ? $this->form[$field] : [];

        // Exclusive options (Flexible / No supplies) replace the selection;
        // picking a normal option drops any exclusive one first.
        if ($exclusive !== null && $option === $exclusive) {
            $this->form[$field] = [$option];

            return;
        }

        if (in_array($option, $values, true)) {
            $this->form[$field] = array_values(array_diff($values, [$option]));

            return;
        }

        if ($exclusive !== null) {
            $values = array_values(array_diff($values, [$exclusive]));
        }

        // Capped selections (problems: up to 3) — ignore the click at the cap.
        $max = $col['max'] ?? null;
        if ($max !== null && count($values) >= $max) {
            return;
        }

        $this->form[$field] = [...$values, $option];
    }

    /**
     * Conditional rules (blueprint v4.9 §7): when a parent answer flips to a
     * hiding value, clear the hidden children so saved records stay
     * internally consistent. Rule 9: Household Members in Organization =
     * None derives Member of Organization = No and hides the whole org block.
     */
    public function updated(string $name, mixed $value): void
    {
        $clear = function (array $fields): void {
            foreach ($fields as $field) {
                $this->form[$field] = is_array($this->form[$field] ?? null) ? [] : '';
                unset($this->form[$field.'_other']);
            }
        };

        $orgFields = ['organization_types', 'organization_meeting_frequency', 'organization_usual_activities', 'position_in_organization'];

        match ($name) {
            'form.interested_in_continuing_studies' => $value === 'Yes' ? null : $clear(['areas_of_educational_interest']),
            'form.has_barangay_health_programs' => $value === 'Yes' ? null : $clear(['benefits_from_barangay_programs', 'programs_benefited_from']),
            'form.benefits_from_barangay_programs' => $value === 'Yes' ? null : $clear(['programs_benefited_from']),
            'form.has_own_toilet' => $value === 'Yes' ? null : $clear(['toilet_type']),
            'form.keeps_animals' => $value === 'Yes' ? null : $clear(['animals_kept']),
            'form.has_electricity' => match ($value) {
                'Yes' => $clear(['light_source_without_power']),
                'No' => $clear(['appliances_owned']),
                default => $clear(['appliances_owned', 'light_source_without_power']),
            },
            'form.member_of_organization' => $value === 'Yes' ? null : $clear($orgFields),
            'form.available_for_training' => $value === 'No' ? null : $clear(['reason_not_available']),
            'form.household_members_in_organization' => match ($value) {
                'None' => tap($clear($orgFields), fn () => $this->form['member_of_organization'] = 'No'),
                default => $this->form['member_of_organization'] = '',
            },
            default => null,
        };
    }

    protected function rules(): array
    {
        // The instrument rules live in one service because the public form
        // validates the same fields (after-defense revision, Item 1). Keeping a
        // second copy here is what would let the two instruments drift.
        return [
            'communityId' => 'required|exists:communities,id',
            'quarter' => 'required|integer|between:1,4',
            'year' => 'required|integer|min:2020|max:2100',
        ] + AssessmentRules::respondent('form')
          + AssessmentRules::forForm('form');
    }

    public function save(): void
    {
        $this->validate();

        AssessmentSubmission::create($this->form, [
            'community_id' => $this->communityId,
            'quarter' => $this->quarter,
            'year' => $this->year,
            'source' => NeedsAssessment::SOURCE_STAFF,
            'uploaded_by' => auth()->id(),
        ]);

        activity()->event('assessment_submit')
            ->log('Needs assessment submitted (pending validation)');

        $this->dispatch('sc-toast', message: 'Assessment submitted — pending secretary validation', type: 'success');

        $this->mount();
        $this->step = '1';
    }

    public function render()
    {
        return view('livewire.assessments.wizard', [
            'communities' => Community::orderBy('name')->get(),
            'quarters' => config('smartcemes.quarters'),
            'sections' => config('smartcemes.assessment_sections'),
            'vocab' => config('smartcemes.vocab'),
        ]);
    }
}
