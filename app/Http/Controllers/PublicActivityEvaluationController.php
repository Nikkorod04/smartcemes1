<?php

namespace App\Http\Controllers;

use App\Models\ActivityEvaluation;
use App\Models\ActivityEvaluationLink;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The unauthenticated public evaluation form for one activity (Phase 2).
 *
 * The token in the URL is the authorization, so the POST is CSRF-exempt by
 * design (see bootstrap/app.php) and rate-limited instead. The form is a short
 * Alpine page with one POST: satisfaction 1–5 and an optional comment.
 *
 * It is deliberately anonymous. A response cannot be tied back to a beneficiary,
 * so it never feeds the activity's per-beneficiary pre/post means — those remain
 * the XLSX import's job. The office sees the public aggregate and comments only.
 */
class PublicActivityEvaluationController extends Controller
{
    public function show(string $token): View|Response
    {
        $link = ActivityEvaluationLink::with('activity.program')->where('token', $token)->first();

        if ($link === null) {
            return response()->view('activities.public-evaluation-inactive', ['reason' => 'unknown'], 404);
        }

        if (! $link->isActive()) {
            return response()->view('activities.public-evaluation-inactive', [
                'reason' => $link->isRevoked() ? 'revoked' : 'expired',
            ], 410);
        }

        return view('activities.public-evaluation', [
            'link' => $link,
            'activity' => $link->activity,
            'project' => $link->activity->program,
            'config' => [
                'token' => $link->token,
                'submitUrl' => route('activity.evaluation.submit', ['token' => $link->token]),
                // One id per page load: a retried POST cannot write twice.
                'clientUuid' => (string) Str::uuid(),
                'maxComments' => 1000,
                'labels' => self::labels(),
            ],
        ]);
    }

    public function store(Request $request, string $token): JsonResponse
    {
        $link = ActivityEvaluationLink::where('token', $token)->first();

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
            'satisfaction' => ['required', 'integer', 'between:1,5'],
            'comments' => ['nullable', 'string', 'max:1000'],
            'client_uuid' => ['nullable', 'uuid'],
            'captured_at' => ['nullable', 'date'],
        ]);

        $clientUuid = $data['client_uuid'] ?? null;

        // Tap "send" twice on a flaky signal, or retry after a timeout the
        // browser never showed: same draft, same row.
        if ($clientUuid !== null) {
            $existing = ActivityEvaluation::where('client_uuid', $clientUuid)->first();

            if ($existing !== null) {
                return response()->json([
                    'status' => 'already-recorded',
                    'id' => $existing->id,
                ]);
            }
        }

        $evaluation = ActivityEvaluation::create([
            'activity_id' => $link->activity_id,
            'activity_evaluation_link_id' => $link->id,
            'client_uuid' => $clientUuid,
            'satisfaction' => (int) $data['satisfaction'],
            'comments' => isset($data['comments']) && trim($data['comments']) !== ''
                ? trim($data['comments'])
                : null,
            'captured_at' => $this->capturedAt($data['captured_at'] ?? null, $link),
        ]);

        activity()->event('activity_evaluation_public_submit')
            ->performedOn($link->activity)
            ->log('Anonymous activity evaluation submitted through a public link');

        return response()->json([
            'status' => 'created',
            'id' => $evaluation->id,
            'submitted_at' => $evaluation->created_at->toIso8601String(),
        ], 201);
    }

    /**
     * Trust the browser's clock only loosely. A wrong device date must not put
     * a response in 2038 or before the link existed.
     */
    private function capturedAt(?string $value, ActivityEvaluationLink $link): Carbon
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

    /** @return array<int, string> */
    private static function labels(): array
    {
        return [
            1 => 'Very dissatisfied',
            2 => 'Dissatisfied',
            3 => 'Neutral',
            4 => 'Satisfied',
            5 => 'Very satisfied',
        ];
    }
}
