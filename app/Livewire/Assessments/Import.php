<?php

namespace App\Livewire\Assessments;

use App\Models\Community;
use App\Models\NeedsAssessment;
use App\Services\AssessmentTemplate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;

#[Layout('layouts.app')]
class Import extends Component
{
    use WithFileUploads;

    public string $step = 'upload'; // upload | preview

    public $file;

    public int $communityId = 0;

    public int $quarter = 1;

    public int $year = 0;

    /** Parsed record values keyed by field name. */
    public array $record = [];

    /** field => [raw strings auto-mapped to "Other"] */
    public array $otherMapped = [];

    /** field => error string (on-screen only, never persisted) */
    public array $fieldErrors = [];

    protected function rules(): array
    {
        return [
            'file' => 'required|file|mimes:'.implode(',', config('smartcemes.uploads.mimes')).'|max:'.config('smartcemes.uploads.max_kb'),
            'communityId' => 'required|exists:communities,id',
            'quarter' => 'required|integer|between:1,4',
            'year' => 'required|integer|min:2020|max:2100',
        ];
    }

    public function updatedFile(): void
    {
        $this->resetErrorBag('file');
    }

    public function parse(): void
    {
        // Validate the file only — the template's context header (D11) may
        // supply community/quarter/year; the form fields remain the fallback
        // and are checked after parsing below.
        $this->validateOnly('file');

        // Livewire tmp paths can exceed Windows MAX_PATH (uploads encode
        // metadata in the filename) and PhpSpreadsheet's ZipArchive cannot
        // read those — copy to a short temp path first (§14 gotcha).
        $tmp = tempnam(sys_get_temp_dir(), 'sc_assessment_').'.xlsx';
        $spreadsheet = null;
        try {
            copy($this->file->getRealPath(), $tmp);
            $spreadsheet = IOFactory::load($tmp);
        } catch (\Throwable) {
            $spreadsheet = null;
        } finally {
            @unlink($tmp);
        }

        if ($spreadsheet === null) {
            $this->addError('file', 'Could not read the file as an XLSX workbook.');

            return;
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        // Vertical template (v2): column A carries the fixed field labels,
        // column B the answers. Section separators, title rows, and the
        // guide column (C) are ignored — only exact label matches map to
        // fields. First occurrence of a label wins.
        $fieldByLabel = [];
        foreach (AssessmentTemplate::COLUMNS as $field => $col) {
            $fieldByLabel[$col['label']] = $field;
        }

        $rowByField = [];
        foreach ($rows as $row) {
            $label = trim((string) ($row['A'] ?? ''));

            if ($label === '' || ! isset($fieldByLabel[$label])) {
                continue;
            }

            $rowByField[$fieldByLabel[$label]] ??= trim((string) ($row['B'] ?? ''));
        }

        if (count($rowByField) < 20) {
            $this->addError('file', 'This file does not use the official template labels. Download the current template (vertical form) and fill column B — older horizontal templates are no longer supported.');

            return;
        }

        if (trim(implode('', $rowByField)) === '') {
            $this->addError('file', 'No answers found — fill the shaded answer cells in column B of the template.');

            return;
        }

        $this->record = [];
        $this->otherMapped = [];
        $this->fieldErrors = [];

        foreach (AssessmentTemplate::COLUMNS as $field => $col) {
            $raw = $rowByField[$field] ?? '';

            if ($col['type'] === 'context') {
                if ($field === 'community_name' && $raw !== '') {
                    $match = Community::whereRaw('lower(name) = ?', [mb_strtolower($raw)])->first()
                        ?? Community::where('name', 'like', "%{$raw}%")->orderBy('name')->first();
                    if ($match) {
                        $this->communityId = $match->id;
                        $this->record['community_name'] = $match->name;
                    }
                }
                if ($field === 'quarter' && $raw !== '') {
                    $this->quarter = max(1, min(4, (int) $raw));
                }
                if ($field === 'year' && $raw !== '') {
                    $this->year = (int) $raw;
                }

                continue;
            }

            $result = AssessmentTemplate::normalize($field, $raw, []);

            if ($result['error'] !== null) {
                $this->fieldErrors[$field] = $result['error'];
            }

            if ($result['other'] !== null) {
                foreach ($result['other'] as $otherField => $texts) {
                    $this->otherMapped[$otherField] = array_merge($this->otherMapped[$otherField] ?? [], $texts);
                }
            }

            if ($result['value'] === null) {
                if ($col['type'] === 'yesno' || $result['error'] !== null) {
                    $this->fieldErrors[$field] = $this->fieldErrors[$field]
                        ?? $result['error']
                        ?? 'Missing value (shown on screen only — not persisted)';
                }

                continue;
            }

            $this->record[$field] = $result['value'];
        }

        // Context fallbacks when the template's context header was left blank:
        // stay on the upload step so the selects (which render the errors)
        // stay visible — the uploader fixes them and re-parses.
        if ($this->communityId === 0 || $this->year === 0) {
            $this->addError('communityId', 'Community and Year are required — pick them above (the template context header was blank).');

            return;
        }

        $this->step = 'preview';
    }

    /** D10: record is only created when the uploader confirms the preview. */
    public function confirm(): void
    {
        $this->validate([
            'communityId' => 'required|exists:communities,id',
            'quarter' => 'required|integer|between:1,4',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        if ($this->record === []) {
            $this->addError('file', 'Nothing parsed to import.');

            return;
        }

        // Archive the source file (audit only — never machine-read, 6.9).
        $path = $this->file->store('needs-assessments/'.now()->format('Y/m'), 'public');

        NeedsAssessment::create([
            ...$this->record,
            'barangay_service_ratings' => $this->record['barangay_service_ratings'] ?? ['overall' => null],
            'other_text' => $this->otherMapped ?: null,
            'community_id' => $this->communityId,
            'quarter' => $this->quarter,
            'year' => $this->year,
            'file_path' => $path,
            'uploaded_by' => auth()->id(),
            'review_status' => 'pending',
        ]);

        activity()->event('assessment_import')
            ->log('Needs assessment imported from XLSX template (pending validation)');

        $this->dispatch('sc-toast', message: 'Assessment created (pending) from import', type: 'success');

        $this->step = 'upload';
        $this->record = [];
        $this->otherMapped = [];
        $this->fieldErrors = [];
    }

    public function render()
    {
        return view('livewire.assessments.import', [
            'communities' => Community::orderBy('name')->get(),
        ]);
    }
}
