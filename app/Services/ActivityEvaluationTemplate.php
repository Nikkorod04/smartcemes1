<?php

namespace App\Services;

use App\Models\Activity;
use App\Services\Concerns\StylesTemplateSheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Official per-activity evaluation template (blueprint v4.13, 5.6 / D13).
 *
 * Generated on demand for ONE activity: the enrolled beneficiary roster is
 * pre-filled with blank Pre-Test / Post-Test / Satisfaction columns. On
 * import the per-row values are aggregated (mean) into the activity's
 * single aggregate columns — no per-beneficiary evaluation table exists
 * (D13). Blank cells leave the existing aggregate untouched.
 */
class ActivityEvaluationTemplate
{
    use StylesTemplateSheet;

    /** Import accepts at most this many beneficiary rows per file. */
    public const MAX_ROWS = 500;

    public const TITLE = 'SmartCEMES — Official Activity Evaluation Template · Leyte Normal University CESO';

    /** @var list<string> */
    public const HEADERS = [
        'Beneficiary ID', 'Last Name', 'First Name', 'Barangay',
        'Pre-Test Score (0-100)', 'Post-Test Score (0-100)', 'Satisfaction (1-5)',
    ];

    public const PRE_MIN = 0.0;

    public const PRE_MAX = 100.0;

    public const POST_MIN = 0.0;

    public const POST_MAX = 100.0;

    public const SATISFACTION_MIN = 1.0;

    public const SATISFACTION_MAX = 5.0;

    public static function filename(Activity $activity): string
    {
        return 'activity-evaluation-'.($activity->program?->code ?? 'program').'.xlsx';
    }

    public function build(Activity $activity): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Evaluation');

        $sheet->setCellValue('A1', self::TITLE);
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $sheet->setCellValue('A2', sprintf(
            'Program: %s (%s) · Activity: %s · Date: %s · Venue: %s',
            $activity->program?->title ?? '—',
            $activity->program?->code ?? '—',
            $activity->title,
            $activity->planned_start_date?->format('M j, Y') ?? '—',
            $activity->venue ?? '—',
        ));
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getFont()->setSize(10);

        $sheet->setCellValue('A3', 'One row = one enrolled beneficiary. Enter numbers only — Pre-Test and Post-Test 0–100, Satisfaction 1–5. Blank cells leave the existing score untouched on import. Do not rename or reorder the columns.');
        $sheet->mergeCells('A3:G3');
        $sheet->getStyle('A3')->getFont()->setSize(9)->setItalic(true);
        $sheet->getStyle('A3')->getAlignment()->setWrapText(true)->setVertical('top');
        $sheet->getRowDimension(3)->setRowHeight(28);

        $sheet->fromArray(self::HEADERS, null, 'A5');
        $sheet->getStyle('A5:G5')->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle('A5:G5')->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFEEF4FF'));
        $sheet->freezePane('A6');
        $sheet->getPageSetup()->setFitToWidth(1);

        foreach (['A' => 14, 'B' => 20, 'C' => 20, 'D' => 18, 'E' => 18, 'F' => 18, 'G' => 16] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $beneficiaries = $activity->program
            ? $activity->program->beneficiaries()
                ->whereNull('beneficiaries.deleted_at')
                ->orderBy('last_name')
                ->limit(self::MAX_ROWS)
                ->get()
            : collect();

        $row = 6;
        foreach ($beneficiaries as $beneficiary) {
            $sheet->setCellValue("A{$row}", $beneficiary->id);
            $sheet->setCellValue("B{$row}", $beneficiary->last_name);
            $sheet->setCellValue("C{$row}", $beneficiary->first_name);
            $sheet->setCellValue("D{$row}", $beneficiary->barangay);
            $this->addDecimalRange($sheet, "E{$row}", (string) self::PRE_MIN, (string) self::PRE_MAX);
            $this->addDecimalRange($sheet, "F{$row}", (string) self::POST_MIN, (string) self::POST_MAX);
            $this->addDecimalRange($sheet, "G{$row}", (string) self::SATISFACTION_MIN, (string) self::SATISFACTION_MAX);
            $row++;
        }

        return $spreadsheet;
    }
}
