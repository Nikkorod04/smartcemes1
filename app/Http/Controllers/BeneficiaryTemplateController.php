<?php

namespace App\Http\Controllers;

use App\Services\BeneficiaryTemplate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The BLANK beneficiary import template (blueprint v4.7, 5.4).
 *
 * The workbook layout lives in `BeneficiaryTemplate::build()` so this and
 * `BeneficiaryExportController` cannot drift — the export is the same sheet with
 * the project's enrolled list where the grey example row would be.
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
