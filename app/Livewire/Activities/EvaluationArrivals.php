<?php

namespace App\Livewire\Activities;

use App\Models\ActivityEvaluationLink;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The live strip under an evaluation link: how many anonymous responses have
 * arrived and their running average (Phase 2).
 *
 * A child component on purpose. Polling the parent would re-render the QR
 * canvas somebody may be pointing a phone at; this strip refreshes alone.
 */
class EvaluationArrivals extends Component
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

        $link = ActivityEvaluationLink::query()->whereKey($this->linkId)->first();

        $stats = $link
            ? $link->evaluations()
                ->selectRaw('count(*) as total, avg(satisfaction) as average, max(coalesce(captured_at, created_at)) as last_at')
                ->first()
            : null;

        return view('livewire.activities.evaluation-arrivals', [
            'count' => (int) ($stats->total ?? 0),
            'average' => $stats?->average !== null ? round((float) $stats->average, 2) : null,
            'lastAt' => $stats?->last_at ? Carbon::parse($stats->last_at) : null,
        ]);
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
