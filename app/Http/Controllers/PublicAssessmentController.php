<?php

namespace App\Http\Controllers;

use App\Models\AssessmentLink;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

/**
 * The unauthenticated public needs-assessment form (after-defense revision,
 * Item 1). The token in the URL is the authorization; there is no user
 * session, so the eventual POST endpoint is CSRF-exempt by design.
 *
 * Step 1 of the build is the entry surface only: the questionnaire arrives in
 * Step 2. A link that does not exist, has expired, or was revoked must fail
 * with a courteous page rather than a stack trace or a bare 403.
 */
class PublicAssessmentController extends Controller
{
    public function show(string $token): View|Response
    {
        $link = AssessmentLink::with('community')->where('token', $token)->first();

        if ($link === null) {
            return response()->view('assessments.public-inactive', ['reason' => 'unknown'], 404);
        }

        if (! $link->isActive()) {
            return response()->view('assessments.public-inactive', [
                'reason' => $link->isRevoked() ? 'revoked' : 'expired',
            ], 410);
        }

        return view('assessments.public-intro', ['link' => $link]);
    }
}
