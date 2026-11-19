<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityEvaluationLink;
use Illuminate\Contracts\View\View;

/**
 * The printable session card for an activity evaluation link (Phase 2).
 *
 * The QR is drawn in the browser, like everywhere else: a print service would
 * receive the token, which is the only credential protecting the public form.
 */
class ActivityEvaluationSheetController extends Controller
{
    public function __invoke(Activity $activity, ActivityEvaluationLink $link): View
    {
        // The URL carries both ids; a link from another activity must not
        // render under this one.
        abort_unless($link->activity_id === $activity->id, 404);

        $link->loadMissing('activity.program');

        return view('activities.evaluation-sheet', [
            'link' => $link,
            'activity' => $link->activity,
            'project' => $link->activity->program,
            'url' => route('activity.evaluation.public', ['token' => $link->token]),
        ]);
    }
}
