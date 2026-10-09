<?php

namespace App\Http\Controllers;

use App\Models\ExtensionProject;
use App\Services\BeneficiaryTemplate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Export a project's ENROLLED beneficiaries as XLSX (owner request 2026-10-05).
 *
 * Deliberately the SAME workbook as the import template — headers, widths,
 * freeze pane and all — with the project's list where the grey example row would
 * be. That makes it round-trip: download the list, edit it in Excel, import it
 * straight back. Both workbooks come from `BeneficiaryTemplate::build()`, so the
 * two can never drift.
 *
 * The ORDER matches the hub's Enrolled Beneficiaries table (last name, then
 * first name) so the export and the screen agree row for row.
 */
class BeneficiaryExportController extends Controller
{
    public function __invoke(ExtensionProject $project): BinaryFileResponse
    {
        $beneficiaries = $project->beneficiaries()
            ->whereNull('beneficiaries.deleted_at')
            ->orderBy('beneficiaries.last_name')
            ->orderBy('beneficiaries.first_name')
            ->get();

        $spreadsheet = BeneficiaryTemplate::build(
            BeneficiaryTemplate::rowsFor($beneficiaries),
            $project->code,
        );

        $path = tempnam(sys_get_temp_dir(), 'sc_beneficiary_export_').'.xlsx';

        (new XlsxWriter($spreadsheet))->save($path);

        return response()
            ->download($path, 'beneficiaries-'.strtolower($project->code).'.xlsx')
            ->deleteFileAfterSend();
    }
}
