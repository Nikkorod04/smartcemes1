<?php

namespace App\Livewire\Activities;

use App\Models\Activity;
use App\Models\ActivityEvaluationLink;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Generate and manage the public evaluation QR for one activity (Phase 2).
 *
 * One link per session is the norm: the QR is shown at the end of the activity,
 * respondents answer anonymously on their own phone, and the office watches the
 * count arrive without re-rendering the QR canvas (see EvaluationArrivals).
 *
 * The token never changes on extend, so a card already printed for the session
 * still works. Revoking takes effect on the next request.
 */
#[Layout('layouts.app')]
class EvaluationLinks extends Component
{
    public int $activityId;

    public int $days = ActivityEvaluationLink::DEFAULT_DAYS;

    /** The link whose card (QR + URL) is open below the form. */
    public ?int $selectedId = null;

    public bool $showRevoke = false;

    public ?int $revokingId = null;

    /** Extend ("reopen") dialog. */
    public bool $showExtend = false;

    public ?int $extendingId = null;

    public int $extendDays = ActivityEvaluationLink::DEFAULT_DAYS;

    public function mount(Activity $activity): void
    {
        $this->guard();

        $this->activityId = $activity->id;

        // Land on the newest live link: on a field day this page is opened to
        // show the QR, not to read a form.
        $this->selectedId = $activity->evaluationLinks()
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->value('id');
    }

    public function generate(): void
    {
        $this->guard();

        $this->validate([
            'days' => ['required', 'integer', 'between:'.ActivityEvaluationLink::MIN_DAYS.','.ActivityEvaluationLink::MAX_DAYS],
        ], [
            'days.between' => 'A link can stay open for 1 to 7 days.',
        ]);

        $activity = $this->activity();

        $link = $activity->evaluationLinks()->create([
            'token' => ActivityEvaluationLink::newToken(),
            'expires_at' => ActivityEvaluationLink::expiryFor($this->days, $activity->planned_start_date),
            'created_by' => auth()->id(),
        ]);

        // The audit trail records what the link is for, never the token itself:
        // the token is the only thing standing between a stranger and the form.
        activity()->event('activity_evaluation_link_created')
            ->performedOn($activity)
            ->causedBy(auth()->user())
            ->withProperties([
                'activity' => $activity->title,
                'days' => $this->days,
                'expires_at' => $link->expires_at->toIso8601String(),
            ])
            ->log('Activity evaluation link created');

        $this->selectedId = $link->id;

        $this->dispatch(
            'sc-toast',
            message: 'Evaluation link ready for '.$activity->title,
            type: 'success',
        );
    }

    public function select(int $id): void
    {
        $this->guard();

        $this->selectedId = $this->activity()->evaluationLinks()->whereKey($id)->exists() ? $id : null;
    }

    public function extend(): void
    {
        $this->guard();

        $this->validate([
            'extendDays' => ['required', 'integer', 'between:'.ActivityEvaluationLink::MIN_DAYS.','.ActivityEvaluationLink::MAX_DAYS],
        ], [
            'extendDays.between' => 'A link can stay open for 1 to 7 days.',
        ]);

        $link = $this->activity()->evaluationLinks()->whereKey($this->extendingId)->first();

        if ($link === null) {
            $this->closeExtend();

            return;
        }

        if ($link->isRevoked()) {
            $this->closeExtend();
            $this->dispatch('sc-toast', message: 'A revoked link cannot be reopened. Generate a new one.', type: 'warn');

            return;
        }

        // Same token, new expiry: a printed card keeps working.
        $link->update([
            'expires_at' => ActivityEvaluationLink::expiryFor(
                $this->extendDays,
                $link->activity->planned_start_date,
            ),
        ]);

        activity()->event('activity_evaluation_link_extended')
            ->performedOn($link->activity)
            ->causedBy(auth()->user())
            ->withProperties([
                'activity' => $link->activity->title,
                'expires_at' => $link->expires_at->toIso8601String(),
            ])
            ->log('Activity evaluation link extended');

        $this->selectedId = $link->id;
        $this->closeExtend();

        $this->dispatch(
            'sc-toast',
            message: 'Link now open until '.$link->expires_at->format('M j, g:i A'),
            type: 'success',
        );
    }

    public function openExtend(int $id): void
    {
        $this->guard();

        $link = $this->activity()->evaluationLinks()->whereKey($id)->firstOrFail();

        if ($link->isRevoked()) {
            $this->dispatch('sc-toast', message: 'A revoked link cannot be reopened. Generate a new one.', type: 'warn');

            return;
        }

        $this->extendingId = $link->id;
        $this->extendDays = ActivityEvaluationLink::DEFAULT_DAYS;
        $this->showExtend = true;
    }

    public function closeExtend(): void
    {
        $this->showExtend = false;
        $this->extendingId = null;
        $this->resetValidation('extendDays');
    }

    public function openRevoke(int $id): void
    {
        $this->guard();

        $this->revokingId = $this->activity()->evaluationLinks()->whereKey($id)->value('id');
        $this->showRevoke = $this->revokingId !== null;
    }

    public function revoke(): void
    {
        $this->guard();

        $link = $this->activity()->evaluationLinks()->whereKey($this->revokingId)->first();

        if ($link !== null && ! $link->isRevoked()) {
            $link->update(['revoked_at' => now()]);

            activity()->event('activity_evaluation_link_revoked')
                ->performedOn($link->activity)
                ->causedBy(auth()->user())
                ->withProperties([
                    'activity' => $link->activity->title,
                    'received' => $link->evaluations()->count(),
                ])
                ->log('Activity evaluation link revoked');
        }

        $this->closeRevoke();

        $this->dispatch(
            'sc-toast',
            message: 'Link closed. Answers already received stay on the activity.',
            type: 'warn',
        );
    }

    public function closeRevoke(): void
    {
        $this->showRevoke = false;
        $this->revokingId = null;
    }

    public function render()
    {
        $activity = $this->activity();

        $links = $activity->evaluationLinks()
            ->with('creator')
            ->withCount('evaluations')
            ->latest('id')
            ->get()
            ->sortByDesc(fn (ActivityEvaluationLink $link) => $link->isActive() ? 1 : 0)
            ->values();

        $selected = $links->firstWhere('id', $this->selectedId);

        return view('livewire.activities.evaluation-links', [
            'activity' => $activity,
            'project' => $activity->program,
            'links' => $links,
            'selected' => $selected,
            'selectedUrl' => $selected
                ? route('activity.evaluation.public', ['token' => $selected->token])
                : null,
            'revoking' => $this->revokingId ? $links->firstWhere('id', $this->revokingId) : null,
            'extending' => $this->extendingId ? $links->firstWhere('id', $this->extendingId) : null,
            'total' => $activity->evaluations()->count(),
            'average' => $this->average($activity),
            'distribution' => $this->distribution($activity),
            'recent' => $activity->evaluations()
                ->whereNotNull('comments')
                ->latest('id')
                ->limit(6)
                ->get(['id', 'satisfaction', 'comments', 'created_at']),
            'activeCount' => $links->filter->isActive()->count(),
        ]);
    }

    /** @return array<int, int> satisfaction score => count */
    private function distribution(Activity $activity): array
    {
        $counts = $activity->evaluations()
            ->selectRaw('satisfaction, count(*) as total')
            ->groupBy('satisfaction')
            ->pluck('total', 'satisfaction')
            ->all();

        return collect(range(1, 5))
            ->mapWithKeys(fn (int $score) => [$score => (int) ($counts[$score] ?? 0)])
            ->all();
    }

    private function average(Activity $activity): ?float
    {
        $value = $activity->evaluations()->avg('satisfaction');

        return $value === null ? null : round((float) $value, 2);
    }

    private function activity(): Activity
    {
        return Activity::with('program')->findOrFail($this->activityId);
    }

    /** Livewire updates bypass route middleware, so re-check the role. */
    private function guard(): void
    {
        abort_unless(
            in_array(auth()->user()?->role, [User::ROLE_ADMIN, User::ROLE_SECRETARY], true),
            403,
        );
    }
}
