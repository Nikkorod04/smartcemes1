<?php

namespace App\Livewire\Assessments;

use App\Models\AssessmentLink;
use App\Models\Community;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Generate and manage public needs-assessment share links (Item 1, Step 4).
 *
 * The link is the whole credential: whoever holds it can answer for one
 * community and period. Everything on this screen exists to make that
 * credential easy to hand out and easy to take back — a QR to photograph, a URL
 * to paste, an expiry that is short by design (1–5 days), and a revoke that
 * takes effect on the next request.
 *
 * The token deliberately never changes on extend: a sheet printed on Monday is
 * still the right sheet on Thursday. Generating again mints a *new* token, so a
 * sheet per purok can each report its own arrivals (Step 4b).
 */
#[Layout('layouts.app')]
class ShareLinks extends Component
{
    /** Generate panel. */
    public string $communityId = '';

    public string $quarter = '';

    public string $year = '';

    public int $days = AssessmentLink::DEFAULT_DAYS;

    /** The link whose card (QR + URL) is open below the form. */
    public ?int $selectedId = null;

    /** Extend ("reopen") dialog. */
    public bool $showExtend = false;

    public ?int $extendingId = null;

    public int $extendDays = AssessmentLink::DEFAULT_DAYS;

    /** Revoke dialog. */
    public bool $showRevoke = false;

    public ?int $revokingId = null;

    public function mount(): void
    {
        $this->guard();

        $this->quarter = (string) (int) ceil(now()->month / 3);
        $this->year = now()->format('Y');

        // Land on the newest live link instead of an empty card: on a field day
        // this page is opened to show the QR, not to read a form.
        $this->selectedId = AssessmentLink::query()->active()->latest('id')->value('id');
    }

    public function generate(): void
    {
        $this->guard();

        $this->validate([
            'communityId' => ['required', 'integer', 'exists:communities,id'],
            'quarter' => ['required', 'integer', 'in:1,2,3,4'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'days' => ['required', 'integer', 'between:'.AssessmentLink::MIN_DAYS.','.AssessmentLink::MAX_DAYS],
        ], [
            'days.between' => 'A link can stay open for 1 to 5 days.',
        ]);

        $link = AssessmentLink::create([
            'community_id' => (int) $this->communityId,
            'quarter' => (int) $this->quarter,
            'year' => (int) $this->year,
            'token' => AssessmentLink::newToken(),
            'expires_at' => AssessmentLink::expiryFor($this->days),
            'created_by' => auth()->id(),
        ]);

        // The audit trail records what the link is for, never the token itself:
        // an audit log is readable by more people than the field team, and the
        // token is the only thing standing between a stranger and the form.
        activity()->event('assessment_link_created')
            ->performedOn($link)
            ->causedBy(auth()->user())
            ->withProperties([
                'community' => $link->community->name,
                'period' => $link->periodLabel(),
                'days' => $this->days,
                'expires_at' => $link->expires_at->toIso8601String(),
            ])
            ->log('Public assessment link created');

        $this->selectedId = $link->id;
        $this->showExtend = false;

        $this->dispatch(
            'sc-toast',
            message: 'Link ready for '.$link->community->name.' · '.$link->periodLabel(),
            type: 'success',
        );
    }

    public function select(int $id): void
    {
        $this->guard();

        $this->selectedId = AssessmentLink::whereKey($id)->exists() ? $id : null;
    }

    public function openExtend(int $id): void
    {
        $this->guard();

        $link = AssessmentLink::findOrFail($id);

        if ($link->isRevoked()) {
            $this->dispatch('sc-toast', message: 'A revoked link cannot be reopened. Generate a new one.', type: 'warn');

            return;
        }

        $this->extendingId = $link->id;
        $this->extendDays = AssessmentLink::DEFAULT_DAYS;
        $this->showExtend = true;
    }

    public function extend(): void
    {
        $this->guard();

        $this->validate([
            'extendDays' => ['required', 'integer', 'between:'.AssessmentLink::MIN_DAYS.','.AssessmentLink::MAX_DAYS],
        ], [
            'extendDays.between' => 'A link can stay open for 1 to 5 days.',
        ]);

        $link = AssessmentLink::findOrFail($this->extendingId);

        if ($link->isRevoked()) {
            $this->closeExtend();
            $this->dispatch('sc-toast', message: 'That link was revoked and cannot be reopened.', type: 'warn');

            return;
        }

        // Same token, new expiry: the printed QR keeps working.
        $link->update(['expires_at' => AssessmentLink::expiryFor($this->extendDays)]);

        activity()->event('assessment_link_extended')
            ->performedOn($link)
            ->causedBy(auth()->user())
            ->withProperties([
                'community' => $link->community->name,
                'period' => $link->periodLabel(),
                'expires_at' => $link->expires_at->toIso8601String(),
            ])
            ->log('Public assessment link extended');

        $this->closeExtend();
        $this->selectedId = $link->id;

        $this->dispatch(
            'sc-toast',
            message: 'Link now open until '.$link->expires_at->format('M j, g:i A'),
            type: 'success',
        );
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

        $this->revokingId = AssessmentLink::findOrFail($id)->id;
        $this->showRevoke = true;
    }

    public function revoke(): void
    {
        $this->guard();

        $link = AssessmentLink::findOrFail($this->revokingId);

        if (! $link->isRevoked()) {
            $link->update(['revoked_at' => now()]);

            activity()->event('assessment_link_revoked')
                ->performedOn($link)
                ->causedBy(auth()->user())
                ->withProperties([
                    'community' => $link->community->name,
                    'period' => $link->periodLabel(),
                    'received' => $link->assessments()->count(),
                ])
                ->log('Public assessment link revoked');
        }

        $this->closeRevoke();

        $this->dispatch(
            'sc-toast',
            message: 'Link closed. Answers already received stay in the review queue.',
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
        // Newest first, but everything still open sits above everything ended:
        // on arrival the next action is always "show me the live one".
        $links = AssessmentLink::query()
            ->with(['community', 'creator'])
            ->latest('id')
            ->get()
            ->sortByDesc(fn (AssessmentLink $link) => $link->isActive() ? 1 : 0)
            ->values();

        return view('livewire.assessments.share-links', [
            'links' => $links,
            'selected' => $links->firstWhere('id', $this->selectedId),
            'communities' => Community::query()->orderBy('name')->get(['id', 'name', 'municipality']),
            'activeCount' => $links->filter->isActive()->count(),
        ]);
    }

    /**
     * Livewire update requests do not pass the page's route middleware, so the
     * component re-checks the role before it mints, extends or revokes anything.
     */
    private function guard(): void
    {
        abort_unless(
            in_array(auth()->user()?->role, [User::ROLE_FACULTY, User::ROLE_SECRETARY], true),
            403,
        );
    }
}
