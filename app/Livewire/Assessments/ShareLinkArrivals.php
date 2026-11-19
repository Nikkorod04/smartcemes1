<?php

namespace App\Livewire\Assessments;

use App\Models\AssessmentLink;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The live strip under a share link: how many answers have arrived, and when
 * the last one did (Step 4b).
 *
 * This is a child component on purpose. `wire:poll` on the share page itself
 * would re-render the whole page every cycle, including the QR canvas that
 * somebody may be pointing a phone at. The parent owns the QR; this strip
 * refreshes alone.
 *
 * It shows counts and timestamps only. Respondent names belong in the review
 * queue, which is behind the secretary's role — not on a screen that gets held
 * up in front of a barangay.
 */
class ShareLinkArrivals extends Component
{
    #[Locked]
    public int $linkId;

    public function mount(int $linkId): void
    {
        $this->guard();

        $this->linkId = $linkId;
    }

    public function render()
    {
        $this->guard();

        $link = AssessmentLink::query()->whereKey($this->linkId)->first();

        $stats = $link
            ? $link->assessments()
                ->selectRaw('count(*) as total, max(coalesce(captured_at, created_at)) as last_at')
                ->first()
            : null;

        return view('livewire.assessments.share-link-arrivals', [
            'count' => (int) ($stats->total ?? 0),
            'lastAt' => $stats?->last_at ? Carbon::parse($stats->last_at) : null,
            'isSecretary' => auth()->user()?->isSecretary() ?? false,
        ]);
    }

    /** Livewire updates bypass the route middleware, so re-check the role. */
    private function guard(): void
    {
        abort_unless(
            in_array(auth()->user()?->role, [User::ROLE_FACULTY, User::ROLE_SECRETARY], true),
            403,
        );
    }
}
