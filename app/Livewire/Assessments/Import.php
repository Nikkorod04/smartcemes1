<?php

namespace App\Livewire\Assessments;

use App\Models\Community;
use App\Models\NeedsAssessment;
use App\Services\AssessmentImportParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Import extends Component
{
    use WithFileUploads;

    public string $step = 'upload'; // upload | preview

    /** Legacy single-file property kept for the existing import path/tests. */
    public $file;

    /** Multiple official templates uploaded for one batch. */
    public array $files = [];

    public bool $batchMode = false;

    public int $communityId = 0;

    public int $quarter = 1;

    public int $year = 0;

    /** Parsed single-record values keyed by field name. */
    public array $record = [];

    /** field => [raw strings auto-mapped to "Other"] */
    public array $otherMapped = [];

    /** field => error string (on-screen only, never persisted) */
    public array $fieldErrors = [];

    /** @var array<int, array<string, mixed>> */
    public array $batchResults = [];

    /** Uploaded file indexes selected for confirmation. */
    public array $selectedBatchFiles = [];

    public bool $showBatchConfirm = false;

    public bool $showBatchResult = false;

    public string $batchResultType = '';

    /** @var array<string, int> */
    public array $batchResultSummary = [];

    public const MAX_BATCH_FILES = 20;

    public const MAX_BATCH_KB = 51200;

    protected function singleFileRules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx|max:'.config('smartcemes.uploads.max_kb'),
        ];
    }

    protected function batchFileRules(): array
    {
        return [
            'files' => 'required|array|min:1|max:'.self::MAX_BATCH_FILES,
        ];
    }

    public function updatedFile(): void
    {
        $this->resetErrorBag('file');
    }

    public function updatedFiles(): void
    {
        $this->resetErrorBag('files');
        $this->resetErrorBag('batch');
    }

    public function enableBatchMode(): void
    {
        $this->resetErrorBag();
        $this->batchMode = true;
        $this->step = 'upload';
        $this->file = null;
        $this->resetBatchState();
    }

    public function enableSingleMode(): void
    {
        $this->resetErrorBag();
        $this->batchMode = false;
        $this->step = 'upload';
        $this->files = [];
        $this->resetBatchState();
    }

    public function parse(): void
    {
        if ($this->batchMode) {
            $this->parseBatch();

            return;
        }

        $this->parseSingle();
    }

    protected function parseSingle(): void
    {
        $this->validate($this->singleFileRules());

        $result = app(AssessmentImportParser::class)->parse(
            $this->file,
            $this->communityId,
            $this->quarter,
            $this->year,
        );

        if ($result['file_errors'] !== []) {
            $this->record = [];
            $this->otherMapped = [];
            $this->fieldErrors = $result['field_errors'];

            if ($this->missingContext($result)) {
                $this->addError('communityId', 'Community and Year are required — pick them above (the template context header was blank).');
            } else {
                $this->addError('file', implode(' ', $result['file_errors']));
            }

            return;
        }

        $this->communityId = (int) $result['community_id'];
        $this->quarter = (int) $result['quarter'];
        $this->year = (int) $result['year'];
        $this->record = $result['record'];
        $this->otherMapped = $result['other_mapped'];
        $this->fieldErrors = $result['field_errors'];
        $this->step = 'preview';
    }

    protected function parseBatch(): void
    {
        $this->validate($this->batchFileRules());

        $totalKb = collect($this->files)->sum(fn ($file) => (int) ceil(($file->getSize() ?: 0) / 1024));
        if ($totalKb > self::MAX_BATCH_KB) {
            $this->addError('files', 'This batch is too large. Select files totalling no more than 50 MB.');

            return;
        }

        $this->resetErrorBag('batch');
        $this->batchResults = [];
        $this->selectedBatchFiles = [];
        $seenHashes = [];
        $parser = app(AssessmentImportParser::class);

        foreach (array_values($this->files) as $index => $file) {
            $fileValidation = Validator::make(
                ['file' => $file],
                ['file' => 'required|file|mimes:xlsx|max:'.config('smartcemes.uploads.max_kb')],
            );

            if ($fileValidation->fails()) {
                $this->batchResults[] = $this->invalidBatchFileResult($file, $index, $fileValidation->errors()->first('file'));

                continue;
            }

            $result = $parser->parse($file, $this->communityId, $this->quarter, $this->year);
            $result['index'] = $index;

            if ($result['hash'] !== null && isset($seenHashes[$result['hash']])) {
                $result['status'] = 'failed';
                $result['file_errors'] = ['This file is a duplicate of another file in this batch.'];
            } elseif ($result['hash'] !== null) {
                $seenHashes[$result['hash']] = $index;
            }

            $this->batchResults[] = $result;

            if (in_array($result['status'], ['ready', 'warning'], true)) {
                $this->selectedBatchFiles[] = (string) $index;
            }
        }

        $this->step = 'preview';

        if ($this->selectedBatchFiles === []) {
            $this->batchResultType = 'error';
            $this->batchResultSummary = [
                'total' => count($this->batchResults),
                'selected' => 0,
                'imported' => 0,
                'failed' => count($this->batchResults),
                'skipped' => count($this->batchResults),
            ];
            $this->showBatchResult = true;
        }
    }

    protected function invalidBatchFileResult(mixed $file, int $index, string $message): array
    {
        return [
            'filename' => $file instanceof UploadedFile ? $file->getClientOriginalName() : 'Unknown file',
            'size' => $file instanceof UploadedFile ? (int) ($file->getSize() ?: 0) : 0,
            'hash' => null,
            'status' => 'failed',
            'record' => [],
            'other_mapped' => [],
            'field_errors' => [],
            'file_errors' => [$message],
            'community_id' => $this->communityId,
            'quarter' => $this->quarter,
            'year' => $this->year,
            'community_name' => null,
            'index' => $index,
        ];
    }

    protected function missingContext(array $result): bool
    {
        return collect($result['file_errors'])
            ->contains(fn (string $error) => str_starts_with($error, 'Community and Year are required'));
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

        try {
            $this->persistAssessment($this->file, [
                'record' => $this->record,
                'other_mapped' => $this->otherMapped,
                'community_id' => $this->communityId,
                'quarter' => $this->quarter,
                'year' => $this->year,
            ]);
        } catch (\Throwable $e) {
            Log::error('Assessment XLSX import could not be saved', ['exception' => $e]);
            $this->addError('file', 'The assessment could not be saved. Please try again.');

            return;
        }

        activity()->event('assessment_import')
            ->log('Needs assessment imported from XLSX template (pending validation)');

        $this->dispatch('sc-toast', message: 'Assessment created (pending) from import', type: 'success');

        $this->step = 'upload';
        $this->record = [];
        $this->otherMapped = [];
        $this->fieldErrors = [];
    }

    public function openBatchConfirm(): void
    {
        $selected = $this->selectedIndexes();

        if ($selected === []) {
            $this->addError('batch', 'Select at least one valid file to import.');

            return;
        }

        $this->showBatchConfirm = true;
    }

    public function cancelBatchConfirm(): void
    {
        $this->showBatchConfirm = false;
    }

    public function confirmBatch(): void
    {
        $this->showBatchConfirm = false;
        $selected = $this->selectedIndexes();
        $imported = 0;

        foreach ($this->batchResults as $resultIndex => &$result) {
            $fileIndex = (int) ($result['index'] ?? $resultIndex);

            if (! in_array($fileIndex, $selected, true)) {
                continue;
            }

            $file = $this->files[$fileIndex] ?? null;
            if (! $file instanceof UploadedFile || ! in_array($result['status'], ['ready', 'warning'], true)) {
                continue;
            }

            try {
                $this->persistAssessment($file, [
                    'record' => $result['record'],
                    'other_mapped' => $result['other_mapped'],
                    'community_id' => $result['community_id'],
                    'quarter' => $result['quarter'],
                    'year' => $result['year'],
                ]);
                $result['status'] = 'imported';
                $imported++;
            } catch (\Throwable $e) {
                Log::error('Assessment batch item could not be saved', [
                    'filename' => $result['filename'],
                    'exception' => $e,
                ]);
                $result['status'] = 'failed';
                $result['file_errors'][] = 'The record could not be saved. Please retry this file.';
            }
        }
        unset($result);

        $failed = collect($this->batchResults)->where('status', 'failed')->count();
        $selectedCount = count($selected);
        $this->batchResultType = $imported === $selectedCount
            ? 'success'
            : ($imported > 0 ? 'partial' : 'error');
        $this->batchResultSummary = [
            'total' => count($this->batchResults),
            'selected' => $selectedCount,
            'imported' => $imported,
            'failed' => $failed,
            'skipped' => max(0, count($this->batchResults) - $selectedCount),
        ];
        $this->showBatchResult = true;

        if ($imported > 0) {
            activity()->event('assessment_import')
                ->log("Assessment batch imported: {$imported} record(s) created as pending");
            $this->dispatch('sc-toast', message: "{$imported} assessment record(s) imported", type: 'success');
        }
    }

    public function backToBatchUpload(): void
    {
        $this->step = 'upload';
        $this->batchResults = [];
        $this->selectedBatchFiles = [];
        $this->resetErrorBag();
    }

    public function closeBatchResult(): void
    {
        $this->showBatchResult = false;
    }

    public function resetBatch(): void
    {
        $this->files = [];
        $this->batchResults = [];
        $this->selectedBatchFiles = [];
        $this->batchMode = true;
        $this->step = 'upload';
        $this->showBatchConfirm = false;
        $this->showBatchResult = false;
        $this->batchResultType = '';
        $this->batchResultSummary = [];
        $this->resetErrorBag();
    }

    public function downloadBatchErrors()
    {
        $errors = collect($this->batchResults)
            ->filter(fn (array $result) => $result['status'] === 'failed')
            ->values();

        if ($errors->isEmpty()) {
            return null;
        }

        return response()->streamDownload(function () use ($errors): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['File', 'Status', 'Errors']);

            foreach ($errors as $result) {
                fputcsv($handle, [
                    $result['filename'],
                    'Failed',
                    implode(' | ', $result['file_errors'] ?? []),
                ]);
            }

            fclose($handle);
        }, 'assessment-batch-errors-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /** @return array<int, int> */
    protected function selectedIndexes(): array
    {
        return collect($this->selectedBatchFiles)
            ->map(fn ($index) => (int) $index)
            ->filter(fn (int $index) => isset($this->batchResults[$index]) && in_array($this->batchResults[$index]['status'], ['ready', 'warning'], true))
            ->values()
            ->all();
    }

    protected function persistAssessment(UploadedFile $file, array $parsed): NeedsAssessment
    {
        $path = $file->store('needs-assessments/'.now()->format('Y/m'), 'public');

        try {
            return NeedsAssessment::create([
                ...$parsed['record'],
                'barangay_service_ratings' => $parsed['record']['barangay_service_ratings'] ?? ['overall' => null],
                'other_text' => $parsed['other_mapped'] ?: null,
                'community_id' => $parsed['community_id'],
                'quarter' => $parsed['quarter'],
                'year' => $parsed['year'],
                'file_path' => $path,
                'uploaded_by' => auth()->id(),
                'review_status' => 'pending',
            ]);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);

            throw $e;
        }
    }

    protected function resetBatchState(): void
    {
        $this->batchResults = [];
        $this->selectedBatchFiles = [];
        $this->showBatchConfirm = false;
        $this->showBatchResult = false;
        $this->batchResultType = '';
        $this->batchResultSummary = [];
    }

    public function render()
    {
        return view('livewire.assessments.import', [
            'communities' => Community::orderBy('name')->get(),
        ]);
    }
}
