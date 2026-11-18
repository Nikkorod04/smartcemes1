<?php

namespace App\Http\Controllers;

use App\Models\AssessmentLink;
use App\Services\AssessmentFormSchema;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * The unauthenticated public needs-assessment form (after-defense revision,
 * Item 1). The token in the URL is the authorization; there is no user session,
 * so the eventual POST endpoint is CSRF-exempt by design.
 *
 * The form itself is rendered from `AssessmentFormSchema`, which is built on
 * `AssessmentTemplate::COLUMNS` — the same source of truth the staff wizard and
 * the XLSX template use. Nothing about the instrument is duplicated here.
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

        return view('assessments.public-form', [
            'link' => $link,
            'sections' => config('smartcemes.assessment_sections'),
            'visibility' => AssessmentFormSchema::visibility(),
            'config' => [
                'token' => $link->token,
                // One-time id for this respondent; a retry of the same draft
                // must not create a second row (wired up in the submit step).
                'clientUuid' => (string) Str::uuid(),
                'form' => AssessmentFormSchema::initialState(),
                'types' => AssessmentFormSchema::types(),
                'otherFields' => AssessmentFormSchema::otherFields(),
                'max' => AssessmentFormSchema::maxSelect(),
                'exclusive' => AssessmentFormSchema::exclusiveOptions(),
                'steps' => AssessmentFormSchema::STEPS,
            ],
        ]);
    }
}
