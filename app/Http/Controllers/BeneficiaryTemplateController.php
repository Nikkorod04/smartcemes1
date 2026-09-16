<?php

namespace App\Http\Controllers;

use App\Services\BeneficiaryTemplate;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BeneficiaryTemplateController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'sc_beneficiary_template_').'.xlsx';

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Beneficiaries');

        $sheet->setCellValue('A1', 'SmartCEMES — Official Beneficiary Import Template v1 · Leyte Normal University CESO');
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $sheet->setCellValue('A2', 'One row = one beneficiary. Do not change the column headers. First name, last name, barangay and beneficiary category are required.');
        $sheet->getStyle('A2')->getFont()->setSize(10)->setItalic(true);

        $sheet->setCellValue('A3', 'Blank Contact Number defaults to '.BeneficiaryTemplate::DEFAULT_CONTACT_NUMBER.'. Unknown beneficiary categories auto-map to "Other".');
        $sheet->getStyle('A3')->getFont()->setSize(10)->setItalic(true);

        $headers = BeneficiaryTemplate::headers();
        $sheet->fromArray($headers, null, 'A5');
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle('A5:'.$lastCol.'5')->getFont()->setBold(true)->setSize(9);
        $sheet->freezePane('A6');
        $sheet->getRowDimension(5)->setRowHeight(28);
        $sheet->getStyle('A5:'.$lastCol.'5')->getAlignment()->setWrapText(true)->setVertical('top');

        foreach (range(1, count($headers)) as $colIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($colIndex))->setWidth(24);
        }

        // Example row so encoders see the expected format (delete before importing).
        $sheet->fromArray([
            'Juan', 'Reyes', 'Dela Cruz', '42', 'Male', '09123456789',
            'San Jose', 'Tacloban City', 'Farmer',
        ], null, 'A6');
        $sheet->getStyle('A6:'.$lastCol.'6')->getFont()->setItalic(true)->getColor()->setRGB('808080');

        (new XlsxWriter($spreadsheet))->save($path);

        return response()->download($path, BeneficiaryTemplate::TEMPLATE_FILENAME)->deleteFileAfterSend();
    }
}
