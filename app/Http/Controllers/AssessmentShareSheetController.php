<?php

namespace App\Http\Controllers;

use App\Models\AssessmentLink;
use Illuminate\Contracts\View\View;

/**
 * The printable A4 sheet for a share link (after-defense revision, Step 4b).
 *
 * The QR is drawn in the browser, like everywhere else: a print service would
 * receive the token, which is the only credential the public form has.
 */
class AssessmentShareSheetController extends Controller
{
    public function __invoke(AssessmentLink $link): View
    {
        $link->loadMissing('community');

        return view('assessments.share-sheet', [
            'link' => $link,
            'url' => route('assessment.public', ['token' => $link->token]),
        ]);
    }
}
