<?php

namespace App\Http\Controllers;

use App\Services\AssessmentTemplate;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssessmentTemplateController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'sc_template_').'.xlsx';

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Needs Assessment');

        // Hidden sheet holding long vocabularies for range-based dropdowns
        // (inline list formulas are capped at 255 characters by Excel).
        $lists = $spreadsheet->createSheet();
        $lists->setTitle('Lists');
        $lists->setSheetState('hidden');
        $listsColumn = 1;

        // Title + instructions (rows 1-2).
        $sheet->setCellValue('A1', 'SmartCEMES — Official Needs-Assessment Import Template v3 · Leyte Normal University CESO');
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $sheet->setCellValue('A2', 'One file = one respondent. Fill the shaded answer cells in column B only — do not rename or reorder the labels in column A. Choose one value for single-choice fields; comma-separated lists are only for fields whose guide says so.');
        $sheet->mergeCells('A2:C2');
        $sheet->getStyle('A2')->getFont()->setSize(10)->setItalic(true);
        $sheet->getStyle('A2')->getAlignment()->setWrapText(true)->setVertical('top');
        $sheet->getRowDimension(2)->setRowHeight(30);

        $sheet->getColumnDimension('A')->setWidth(34);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(52);

        $sheet->freezePane('A4');
        $sheet->getPageSetup()->setFitToWidth(1);

        $row = 4;
        foreach (AssessmentTemplate::COLUMNS as $field => $col) {
            // Section separator row (merged, LNU blue).
            if (isset(AssessmentTemplate::SECTION_BREAKS[$field])) {
                $sheet->setCellValue("A{$row}", AssessmentTemplate::SECTION_BREAKS[$field]);
                $sheet->mergeCells("A{$row}:C{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10)->setColor(new Color('FFFFFFFF'));
                $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FF003599'));
                $sheet->getRowDimension($row)->setRowHeight(20);
                $row++;
            }

            // Label (A) · answer (B) · fill-up guide (C).
            $sheet->setCellValue("A{$row}", $col['label']);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(9)->setColor(new Color('FF374151'));
            $sheet->getStyle("A{$row}")->getAlignment()->setVertical('top');

            $sheet->getStyle("B{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFFDF3CE'));
            $sheet->getStyle("B{$row}")->getAlignment()->setWrapText(true)->setVertical('top');

            $sheet->setCellValue("C{$row}", AssessmentTemplate::guide($field));
            $sheet->getStyle("C{$row}")->getFont()->setSize(8)->setItalic(true)->setColor(new Color('FF6B7280'));
            $sheet->getStyle("C{$row}")->getAlignment()->setWrapText(true)->setVertical('top');

            $sheet->getStyle("A{$row}:C{$row}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE5E7EB']],
                ],
            ]);

            // Non-strict dropdown suggestions on single-choice / yes-no
            // answer cells — typed free text is still accepted, so the
            // D9 auto-map-to-Other flow keeps working on import.
            $vocab = match (true) {
                $col['type'] === 'yesno' => ['Yes', 'No'],
                in_array($col['type'], ['single'], true) && isset($col['vocab']) => config('smartcemes.vocab.'.$col['vocab']),
                default => null,
            };
            if ($vocab !== null) {
                $this->addDropdown($sheet, "B{$row}", $vocab, $lists, $listsColumn);
            }

            $row++;
        }

        (new XlsxWriter($spreadsheet))->save($path);

        return response()->download($path, AssessmentTemplate::TEMPLATE_FILENAME)->deleteFileAfterSend();
    }

    /**
     * Attach a non-strict LIST validation to a cell. Short vocabularies use
     * an inline "a,b,c" formula; longer ones are written to the hidden
     * Lists sheet and referenced by range (Excel caps inline lists at 255
     * characters).
     */
    protected function addDropdown(Worksheet $sheet, string $cell, array $vocab, Worksheet $lists, int &$listsColumn): void
    {
        $inline = '"'.implode(',', $vocab).'"';

        if (strlen($inline) > 255) {
            $letter = Coordinate::stringFromColumnIndex($listsColumn++);
            $lists->fromArray($vocab, null, $letter.'1');
            $formula = "'Lists'!\${$letter}\$1:\${$letter}\$".count($vocab);
        } else {
            $formula = $inline;
        }

        $validation = $sheet->getCell($cell)->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setFormula1($formula);
        // PhpSpreadsheet inverts this vs the OOXML attribute:
        // the property must be TRUE for the arrow to render.
        $validation->setShowDropDown(true);
        $validation->setShowErrorMessage(false);
        $validation->setAllowBlank(true);
    }
}
