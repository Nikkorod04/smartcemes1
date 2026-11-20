<?php

namespace App\Http\Controllers;

use App\Models\ExtensionProject;
use App\Services\ReportPdfService;
use Illuminate\Http\Response;

/**
 * Per-project beneficiary roster as an A4 portrait PDF (2026-11-20).
 *
 * Replaces the old XLSX export. Uses the same PDF engine as the institutional
 * reports so the seal, page numbers, and signature lines are consistent.
 */
class BeneficiaryExportController extends Controller
{
    public function __invoke(ExtensionProject $project): Response
    {
        $beneficiaries = $project->beneficiaries()
            ->with('community')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return app(ReportPdfService::class)->stream(
            'reports.beneficiaries-pdf',
            [
                'project' => $project,
                'beneficiaries' => $beneficiaries,
                'scopeLabel' => $beneficiaries->count().' active beneficiaries',
            ],
            'Beneficiary List',
            $project->code.' · '.$project->title,
            'beneficiaries-'.strtolower($project->code).'.pdf',
            false,
            'A4',
            'beneficiary',
        );
    }
}
