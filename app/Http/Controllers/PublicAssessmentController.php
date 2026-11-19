<?php

namespace App\Http\Controllers;

use App\Models\AssessmentLink;
use App\Models\NeedsAssessment;
use App\Services\AssessmentFormSchema;
use App\Services\AssessmentRules;
use App\Services\AssessmentSubmission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The unauthenticated public needs-assessment form (after-defense revision,
 * Item 1). The token in the URL is the authorization; there is no user session,
 * so the POST is CSRF-exempt by design (see bootstrap/app.php) and rate-limited
 * instead.
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
                'submitUrl' => route('assessment.public.submit', ['token' => $link->token]),
                // One-time id for this respondent; a retry of the same draft
                // cannot create a second row.
                'clientUuid' => (string) Str::uuid(),
                'form' => AssessmentFormSchema::initialState(),
                'types' => AssessmentFormSchema::types(),
                'otherFields' => AssessmentFormSchema::otherFields(),
                'max' => AssessmentFormSchema::maxSelect(),
                'exclusive' => AssessmentFormSchema::exclusiveOptions(),
                'steps' => AssessmentFormSchema::STEPS,
                'fieldSteps' => AssessmentFormSchema::fieldSteps(),
            ],
        ]);
    }

    /**
     * Accept one completed questionnaire.
     *
     * Returns JSON because the browser sends it with `fetch` and stays on the
     * page: a respondent who submitted from a barangay hall should see the
     * confirmation immediately, not a redirect to a page they cannot go back
     * from on a shared phone.
     */
    public function store(Request $request, string $token): JsonResponse
    {
        $link = AssessmentLink::where('token', $token)->first();

        if ($link === null) {
            return response()->json(['message' => 'This link is not valid.'], 404);
        }

        if (! $link->isActive()) {
            return response()->json([
                'message' => $link->isRevoked()
                    ? 'This link has been closed.'
                    : 'This link has expired.',
                'reason' => $link->isRevoked() ? 'revoked' : 'expired',
            ], 410);
        }

        // A field no respondent can see and no keyboard reaches. Bots fill it;
        // answering 201 without storing anything tells them nothing.
        if ($request->filled('website')) {
            return response()->json(['status' => 'created'], 201);
        }

        $data = $request->validate([
            'form' => 'required|array',
            'consent' => 'accepted',
            'client_uuid' => 'nullable|uuid',
            'captured_at' => 'nullable|date',
            // Free text that has no vocabulary to check against. Listed here so
            // that `validate()` returns it: it only returns keys that have a
            // rule, and an unlisted field would be silently dropped.
            'form.respondent_middle_name' => 'nullable|string|max:255',
            'form.general_feedback' => 'nullable|string|max:2000',
        ] + AssessmentRules::respondent('form')
          + AssessmentRules::forForm('form'), [
              'consent.accepted' => 'Please confirm that your answers may be recorded.',
          ]);

        $clientUuid = $data['client_uuid'] ?? null;

        // Tap "send" twice on a flaky signal, or retry after a timeout that the
        // browser never showed: same draft, same row.
        if ($clientUuid !== null) {
            $existing = NeedsAssessment::where('client_uuid', $clientUuid)->first();

            if ($existing !== null) {
                return response()->json([
                    'status' => 'already-recorded',
                    'id' => $existing->id,
                ]);
            }
        }

        $assessment = AssessmentSubmission::create($data['form'], [
            'community_id' => $link->community_id,
            'quarter' => $link->quarter,
            'year' => $link->year,
            'source' => NeedsAssessment::SOURCE_PUBLIC,
            'uploaded_by' => null,
            'client_uuid' => $clientUuid,
            'captured_at' => $this->capturedAt($data['captured_at'] ?? null, $link),
        ]);

        activity()->event('assessment_public_submit')
            ->log('Needs assessment submitted through a public link');

        return response()->json([
            'status' => 'created',
            'id' => $assessment->id,
            'submitted_at' => $assessment->created_at->toIso8601String(),
        ], 201);
    }

    /**
     * Trust the browser's clock only loosely. `captured_at` exists to record
     * when the answers were written, which can precede the request — but a
     * device with the wrong date must not put a submission in 2038 or in the
     * quarter before the link was even printed.
     */
    private function capturedAt(?string $value, AssessmentLink $link): Carbon
    {
        if ($value === null || trim($value) === '') {
            return now();
        }

        try {
            $captured = Carbon::parse($value);
        } catch (\Throwable) {
            return now();
        }

        if ($captured->isFuture() || $captured->lt($link->created_at->copy()->subDay())) {
            return now();
        }

        return $captured;
    }
}
