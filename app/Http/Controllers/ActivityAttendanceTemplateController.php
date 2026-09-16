<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Services\ActivityAttendanceTemplate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves the generated per-activity attendance template (v4.13, 5.5).
 * Admin and Secretary only (route middleware).
 */
class ActivityAttendanceTemplateController extends Controller
{
    public function __invoke(Activity $activity): BinaryFileResponse
    {
        abort_unless(
            $activity->program?->beneficiaries()->whereNull('beneficiaries.deleted_at')->exists(),
            422,
            'Enroll at least one beneficiary before downloading this template.'
        );

        $template = app(ActivityAttendanceTemplate::class);
        $path = tempnam(sys_get_temp_dir(), 'sc_attendance_tpl_').'.xlsx';

        (new XlsxWriter($template->build($activity)))->save($path);

        return response()->download($path, ActivityAttendanceTemplate::filename($activity))->deleteFileAfterSend();
    }
}
