<?php

namespace App\Services;

use App\Models\Community;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Parses one official needs-assessment workbook.
 *
 * The parser deliberately does not persist anything. This keeps single-file
 * and batch imports on the same rules and lets the UI show a complete review
 * before a record is created.
 */
class AssessmentImportParser
{
    public function parse(UploadedFile $file, int $fallbackCommunityId = 0, int $fallbackQuarter = 1, int $fallbackYear = 0): array
    {
        $result = [
            'filename' => $file->getClientOriginalName(),
            'size' => (int) ($file->getSize() ?: 0),
            'hash' => $this->hash($file),
            'status' => 'failed',
            'record' => [],
            'other_mapped' => [],
            'field_errors' => [],
            'file_errors' => [],
            'community_id' => $fallbackCommunityId,
            'quarter' => $fallbackQuarter,
            'year' => $fallbackYear,
            'community_name' => null,
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'sc_assessment_');
        $tmpXlsx = $tmp ? $tmp.'.xlsx' : null;
        $spreadsheet = null;

        try {
            if (! $tmp || ! $tmpXlsx || ! copy($file->getRealPath(), $tmpXlsx)) {
                throw new \RuntimeException('The uploaded file could not be prepared for reading.');
            }

            $spreadsheet = IOFactory::load($tmpXlsx);
        } catch (\Throwable $e) {
            Log::warning('Assessment XLSX could not be parsed', [
                'filename' => $result['filename'],
                'exception' => $e->getMessage(),
            ]);
            $result['file_errors'][] = 'Could not read the file as an XLSX workbook.';
        } finally {
            if ($tmpXlsx) {
                @unlink($tmpXlsx);
            }
            if ($tmp) {
                @unlink($tmp);
            }
        }

        if ($spreadsheet === null) {
            return $result;
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
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

            // First occurrence wins, matching the original single-file flow.
            $rowByField[$fieldByLabel[$label]] ??= trim((string) ($row['B'] ?? ''));
        }

        if (count($rowByField) < 20) {
            $result['file_errors'][] = 'This file does not use the official template labels. Download the current template (vertical form) and fill column B — older horizontal templates are no longer supported.';

            return $result;
        }

        if (trim(implode('', $rowByField)) === '') {
            $result['file_errors'][] = 'No answers found — fill the shaded answer cells in column B of the template.';

            return $result;
        }

        foreach (AssessmentTemplate::COLUMNS as $field => $col) {
            $raw = $rowByField[$field] ?? '';

            if ($col['type'] === 'context') {
                if ($field === 'community_name' && $raw !== '') {
                    $match = Community::whereRaw('lower(name) = ?', [mb_strtolower($raw)])->first()
                        ?? Community::where('name', 'like', "%{$raw}%")->orderBy('name')->first();

                    if ($match) {
                        $result['community_id'] = $match->id;
                        $result['community_name'] = $match->name;
                        $result['record']['community_name'] = $match->name;
                    }
                }

                if ($field === 'quarter' && $raw !== '') {
                    $result['quarter'] = max(1, min(4, (int) $raw));
                }

                if ($field === 'year' && $raw !== '') {
                    $result['year'] = (int) $raw;
                }

                // Proposal Reference is currently informational only and is
                // intentionally not persisted by the existing schema.
                continue;
            }

            $normalized = AssessmentTemplate::normalize($field, $raw, []);

            if ($normalized['error'] !== null) {
                $result['field_errors'][$field] = $normalized['error'];
            }

            if ($normalized['other'] !== null) {
                foreach ($normalized['other'] as $otherField => $texts) {
                    $result['other_mapped'][$otherField] = array_merge(
                        $result['other_mapped'][$otherField] ?? [],
                        $texts
                    );
                }
            }

            if ($normalized['value'] === null) {
                if ($col['type'] === 'yesno' || $normalized['error'] !== null) {
                    $result['field_errors'][$field] = $result['field_errors'][$field]
                        ?? $normalized['error']
                        ?? 'Missing value (shown on screen only — not persisted)';
                }

                continue;
            }

            $result['record'][$field] = $normalized['value'];
        }

        if ($result['community_id'] === 0 || $result['year'] === 0) {
            $result['file_errors'][] = 'Community and Year are required — select them above or fill the template context block.';

            return $result;
        }

        $result['status'] = $result['field_errors'] === [] ? 'ready' : 'warning';

        return $result;
    }

    protected function hash(UploadedFile $file): ?string
    {
        $path = $file->getRealPath();

        return $path && is_file($path) ? hash_file('sha256', $path) : null;
    }
}
