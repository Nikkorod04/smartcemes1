<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Services\ActivityEvaluationTemplate;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves the generated per-activity evaluation template (v4.13, 5.6).
 * Admin and Secretary only (route middleware).
 */
class ActivityEvaluationTemplateController extends Controller
{
    public function __invoke(Activity $activity): BinaryFileResponse
    {
        abort_unless(
            $activity->program?->beneficiaries()->whereNull('beneficiaries.deleted_at')->exists(),
            422,
            'Enroll at least one beneficiary before downloading this template.'
        );

        $template = app(ActivityEvaluationTemplate::class);
        $path = tempnam(sys_get_temp_dir(), 'sc_evaluation_tpl_').'.xlsx';

        (new XlsxWriter($template->build($activity)))->save($path);

        return response()->download($path, ActivityEvaluationTemplate::filename($activity))->deleteFileAfterSend();
    }
}
