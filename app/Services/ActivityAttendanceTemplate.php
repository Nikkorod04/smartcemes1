<?php

namespace App\Services;

use App\Models\Activity;
use App\Services\Concerns\StylesTemplateSheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Official per-activity attendance template (blueprint v4.13, 5.5).
 *
 * Generated on demand for ONE activity: the enrolled beneficiary roster is
 * pre-filled and the Status column carries a non-strict dropdown
 * (present | absent | excused | late). One sheet = one activity date —
 * the payload is imported back through the Records modal.
 */
class ActivityAttendanceTemplate
{
    use StylesTemplateSheet;

    /** Import accepts at most this many beneficiary rows per file. */
    public const MAX_ROWS = 500;

    public const TITLE = 'SmartCEMES — Official Activity Attendance Template · Leyte Normal University CESO';

    /** @var list<string> */
    public const HEADERS = ['Beneficiary ID', 'Last Name', 'First Name', 'Barangay', 'Status'];

    /** Human labels in canonical status order (smartcemes.statuses.attendance). */
    public static function statusLabels(): array
    {
        return array_map(fn ($s) => ucfirst($s), config('smartcemes.statuses.attendance'));
    }

    public static function filename(Activity $activity): string
    {
        return 'activity-attendance-'.($activity->program?->code ?? 'program').'.xlsx';
    }

    public function build(Activity $activity): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Attendance');

        $lists = $spreadsheet->createSheet();
        $lists->setTitle('Lists');
        $lists->setSheetState('hidden');
        $listsColumn = 1;

        $sheet->setCellValue('A1', self::TITLE);
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);

        $sheet->setCellValue('A2', sprintf(
            'Project: %s (%s) · Activity: %s · Date: %s · Venue: %s',
            $activity->program?->title ?? '—',
            $activity->program?->code ?? '—',
            $activity->title,
            $activity->planned_start_date?->format('M j, Y') ?? '—',
            $activity->venue ?? '—',
        ));
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A2')->getFont()->setSize(10);

        $sheet->setCellValue('A3', 'One row = one enrolled beneficiary. Fill only the Status column — '.implode(', ', self::statusLabels()).'. Leave a cell blank to leave that beneficiary unrecorded. Do not rename or reorder the columns.');
        $sheet->mergeCells('A3:E3');
        $sheet->getStyle('A3')->getFont()->setSize(9)->setItalic(true);
        $sheet->getStyle('A3')->getAlignment()->setWrapText(true)->setVertical('top');
        $sheet->getRowDimension(3)->setRowHeight(28);

        $sheet->fromArray(self::HEADERS, null, 'A5');
        $sheet->getStyle('A5:E5')->getFont()->setBold(true)->setSize(9);
        $sheet->getStyle('A5:E5')->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('FFEEF4FF'));
        $sheet->freezePane('A6');
        $sheet->getPageSetup()->setFitToWidth(1);

        foreach (['A' => 14, 'B' => 22, 'C' => 22, 'D' => 20, 'E' => 16] as $col => $width) {
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
            $this->addListDropdown($sheet, "E{$row}", self::statusLabels(), $lists, $listsColumn);
            $row++;
        }

        return $spreadsheet;
    }
}
