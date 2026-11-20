<?php

namespace App\Http\Controllers;

use App\Services\BeneficiaryTemplate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The BLANK beneficiary import template, v2 (2026-11-20).
 *
 * The workbook layout lives in `BeneficiaryTemplate::build()`, so the blank file
 * and any future export-shaped sheet share one definition. The per-project
 * roster itself is now a PDF (`BeneficiaryExportController`), not a workbook.
 */
class BeneficiaryTemplateController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'sc_beneficiary_template_').'.xlsx';

        (new XlsxWriter(BeneficiaryTemplate::build()))->save($path);

        return response()->download($path, BeneficiaryTemplate::TEMPLATE_FILENAME)->deleteFileAfterSend();
    }
}
