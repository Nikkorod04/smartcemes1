<?php

namespace App\Services;

use App\Models\Beneficiary;
use App\Models\Community;
use App\Models\ExtensionProject;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * The v2 beneficiary XLSX import (2026-11-20).
 *
 * Shared by the project hub and the standalone registry page so both entry
 * points parse, preview and write exactly the same way. The caller owns the
 * upload validation (required/mimes/max); this service owns the workbook shape,
 * row states and the database write.
 */
class BeneficiaryImporter
{
    /**
     * Parse a workbook against one project.
     *
     * @return array{rows: array<int, array<string, mixed>>, error: ?string}
     */
    public function parse(UploadedFile $file, ExtensionProject $project): array
    {
        // Livewire tmp paths can exceed Windows MAX_PATH (uploads encode
        // metadata in the filename) and PhpSpreadsheet's ZipArchive cannot
        // read those — copy to a short temp path first (§14 gotcha).
        $tmp = tempnam(sys_get_temp_dir(), 'sc_ben_').'.xlsx';
        $spreadsheet = null;

        try {
            copy($file->getRealPath(), $tmp);
            $spreadsheet = IOFactory::load($tmp);
        } catch (\Throwable) {
            $spreadsheet = null;
        } finally {
            @unlink($tmp);
        }

        if ($spreadsheet === null) {
            return ['rows' => [], 'error' => 'Could not read the file as an XLSX workbook.'];
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $fieldByHeader = [];
        foreach (BeneficiaryTemplate::COLUMNS as $field => $col) {
            $fieldByHeader[$col['header']] = $field;
        }

        // Locate the fixed header row and map column letter -> field.
        $columnField = null;
        $dataRows = [];
        foreach ($rows as $row) {
            if ($columnField === null) {
                $mapped = [];
                foreach ($row as $colLetter => $header) {
                    $header = trim((string) $header);
                    if (isset($fieldByHeader[$header])) {
                        $mapped[$colLetter] = $fieldByHeader[$header];
                    }
                }
                if (count($mapped) >= 6) {
                    $columnField = $mapped;
                }

                continue;
            }

            $isEmpty = true;
            foreach ($row as $value) {
                if (trim((string) $value) !== '') {
                    $isEmpty = false;
                    break;
                }
            }
            if (! $isEmpty) {
                $dataRows[] = $row;
            }
        }

        // v2 needs both the community and category headers. Older templates fail
        // here rather than importing rows with a guessed community.
        if ($columnField === null
            || ! in_array('community', $columnField, true)
            || ! in_array('beneficiary_category', $columnField, true)) {
            return [
                'rows' => [],
                'error' => 'This file does not use the v2 beneficiary template. Download the new template and do not rename columns.',
            ];
        }

        if ($dataRows === []) {
            return ['rows' => [], 'error' => 'No data rows found under the template headers.'];
        }

        if (count($dataRows) > BeneficiaryTemplate::MAX_ROWS) {
            return [
                'rows' => [],
                'error' => 'Too many rows — the import accepts at most '.BeneficiaryTemplate::MAX_ROWS.' beneficiaries per file.',
            ];
        }

        $communityByName = [];
        foreach ($project->communities()->get() as $community) {
            $communityByName[$this->communityKey($community->name)] = $community;
        }

        $existing = $project->beneficiaries()->get()
            ->keyBy(fn (Beneficiary $b) => $this->beneficiaryKey($b->first_name, $b->last_name, $b->community_id));

        $otherProjects = Beneficiary::query()
            ->where(function ($query) use ($project) {
                $query->whereNull('extension_project_id')
                    ->orWhere('extension_project_id', '!=', $project->id);
            })
            ->with('extensionProject')
            ->get()
            ->keyBy(fn (Beneficiary $b) => $this->beneficiaryKey($b->first_name, $b->last_name, $b->community_id));

        $seen = [];
        $parsed = [];

        foreach ($dataRows as $row) {
            $raw = [];
            foreach ($columnField as $colLetter => $field) {
                $raw[$field] = trim((string) ($row[$colLetter] ?? ''));
            }

            $result = BeneficiaryTemplate::normalizeRow($raw);
            $data = $result['data'];
            $errors = $result['errors'];
            $community = null;
            $status = 'new';
            $existingId = null;
            $otherProject = null;

            if ($errors === []) {
                $community = $communityByName[$this->communityKey((string) $data['community'])] ?? null;

                if ($community === null) {
                    $errors['community'] = 'This community/partner school is not linked to the project. Add the link first.';
                }
            }

            if ($errors === []) {
                $key = $this->beneficiaryKey($data['first_name'], $data['last_name'], $community->id);

                if (isset($seen[$key])) {
                    $errors['first_name'] = 'Duplicate person in this file — only the first row will be used.';
                } else {
                    $seen[$key] = true;

                    if ($existing->has($key)) {
                        $status = 'update';
                        $existingId = $existing->get($key)->id;
                    } elseif ($otherProjects->has($key)) {
                        $candidate = $otherProjects->get($key);

                        if ($candidate->extension_project_id === null) {
                            // A legacy registry-only record: claim it into this
                            // project instead of creating a duplicate.
                            $status = 'unassigned';
                            $existingId = $candidate->id;
                        } else {
                            $status = 'other_project';
                            $otherProject = $candidate->extensionProject?->code ?? 'another project';
                        }
                    }
                }
            }

            if ($errors !== []) {
                $status = 'error';
            }

            $parsed[] = [
                'data' => $data,
                'errors' => $errors,
                'category_other' => $result['category_other'],
                'status' => $status,
                'existing_id' => $existingId,
                'community_id' => $community?->id,
                'community_name' => $community?->name ?? ($data['community'] ?? null),
                'other_project' => $otherProject,
            ];
        }

        return ['rows' => $parsed, 'error' => null];
    }

    /**
     * Write the confirmed rows. Errors and other-project rows are skipped;
     * unassigned legacy rows are claimed into the project.
     *
     * @param  array<int, array<string, mixed>>  $importRows
     * @return array{created:int, updated:int, claimed:int, otherProject:int, failed:int}
     */
    public function confirm(array $importRows, ExtensionProject $project): array
    {
        $created = 0;
        $updated = 0;
        $claimed = 0;
        $otherProject = 0;
        $failed = 0;

        DB::transaction(function () use ($importRows, $project, &$created, &$updated, &$claimed, &$otherProject, &$failed) {
            foreach ($importRows as $row) {
                if ($row['status'] === 'error') {
                    $failed++;

                    continue;
                }

                if ($row['status'] === 'other_project') {
                    $otherProject++;

                    continue;
                }

                $data = $row['data'];
                $community = Community::find($row['community_id']);

                if ($community === null) {
                    $failed++;

                    continue;
                }

                if (in_array($row['status'], ['update', 'unassigned'], true) && $row['existing_id'] !== null) {
                    $beneficiary = $row['status'] === 'update'
                        ? Beneficiary::query()
                            ->where('extension_project_id', $project->id)
                            ->find($row['existing_id'])
                        : Beneficiary::find($row['existing_id']);

                    // The person may have been assigned elsewhere while the
                    // preview was open; never move them silently.
                    if ($beneficiary === null
                        || ($row['status'] === 'unassigned' && $beneficiary->extension_project_id !== null)) {
                        $failed++;

                        continue;
                    }

                    $extra = $row['status'] === 'unassigned'
                        ? ['extension_project_id' => $project->id]
                        : [];

                    $beneficiary->update($this->updatesFor($data, $community, $extra));

                    if ($row['status'] === 'unassigned') {
                        $claimed++;
                    } else {
                        $updated++;
                    }

                    continue;
                }

                Beneficiary::create([
                    'first_name' => $data['first_name'],
                    'middle_name' => $data['middle_name'] ?: null,
                    'last_name' => $data['last_name'],
                    'age' => $data['age'] !== null ? (int) $data['age'] : null,
                    'gender' => $data['gender'],
                    'phone' => BeneficiaryRules::normaliseContact($data['phone'] ?? null),
                    'barangay' => $community->name,
                    'municipality' => $community->municipality,
                    'province' => $community->province,
                    'community_id' => $community->id,
                    'extension_project_id' => $project->id,
                    'beneficiary_category' => $data['beneficiary_category'],
                ]);

                $created++;
            }
        });

        if ($created > 0 || $updated > 0 || $claimed > 0) {
            activity()->performedOn($project)->event('beneficiary_import')
                ->withProperties(['created' => $created, 'updated' => $updated, 'claimed' => $claimed])
                ->log("{$created} beneficiaries created, {$updated} updated and {$claimed} unassigned records assigned from the XLSX template");
        }

        return compact('created', 'updated', 'claimed', 'otherProject', 'failed');
    }

    /** Human summary for the toast. */
    public function summary(array $counts): string
    {
        $summary = "{$counts['created']} created · {$counts['updated']} updated";
        $summary .= ($counts['claimed'] ?? 0) > 0 ? " · {$counts['claimed']} assigned from unassigned" : '';
        $summary .= $counts['otherProject'] > 0 ? " · {$counts['otherProject']} skipped (exists in another project)" : '';
        $summary .= $counts['failed'] > 0 ? " · {$counts['failed']} skipped (errors)" : '';

        return $summary;
    }

    /**
     * Only filled cells update; a blank cell never erases saved data.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function updatesFor(array $data, Community $community, array $extra = []): array
    {
        $updates = array_merge([
            'community_id' => $community->id,
            'barangay' => $community->name,
            'municipality' => $community->municipality,
            'province' => $community->province,
        ], $extra);

        foreach (['first_name', 'middle_name', 'last_name', 'gender', 'beneficiary_category'] as $field) {
            if (($data[$field] ?? null) !== null) {
                $updates[$field] = $data[$field];
            }
        }

        if (($data['age'] ?? null) !== null) {
            $updates['age'] = (int) $data['age'];
        }

        if (($data['phone'] ?? null) !== null) {
            $updates['phone'] = BeneficiaryRules::normaliseContact($data['phone']);
        }

        return $updates;
    }

    protected function communityKey(?string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $name)) ?? '');
    }

    protected function beneficiaryKey(?string $firstName, ?string $lastName, ?int $communityId): string
    {
        return mb_strtolower(trim((string) $firstName).'|'.trim((string) $lastName).'|'.(int) $communityId);
    }
}
