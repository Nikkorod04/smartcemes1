<?php

namespace App\Livewire\AuditLogs;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity as ActivityLog;

/**
 * Audit Logs (owner request 2026-09-25).
 *
 * The Director's record of everything the system logged — approvals, rejections,
 * deletions, status transitions and the XLSX imports (D8: the Spatie Activity Log
 * is REQUIRED, not optional). It replaces the four-row "Recent Activity" panel
 * that used to sit on the dashboard, which could never answer "who changed this,
 * and when" beyond the last handful of events.
 *
 * ADMIN-ONLY, and deliberately READ-ONLY. Nothing here writes, edits or deletes a
 * log row — an audit trail a Director can amend is not an audit trail.
 *
 * A reverse-chronological, read-only list with lightweight filters and search.
 * The dashboard panel showed four rows; this shows all of them, 25 at a time.
 */
#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    /** Entries per page. */
    private const PER_PAGE = 25;

    public string $search = '';

    public string $event = 'all';

    public string $subject = 'all';

    public string $sort = 'created_at';

    public string $direction = 'desc';

    /**
     * Event → badge colour. Deliberately its OWN map rather than
     * `config('smartcemes.status_colors')`: that vocabulary is keyed on record
     * STATUS (ongoing, pending, rejected…), not on log events, so an event name
     * would never match and every badge would fall through to gray.
     */
    private const EVENT_COLORS = [
        'created' => 'green',
        'restored' => 'green',
        'updated' => 'blue',
        'status_transition' => 'yellow',
        'deleted' => 'red',
    ];

    /**
     * Morph type → the noun a Director reads. Falls back to the class basename, so
     * a newly logged subject still renders rather than showing nothing.
     */
    private const SUBJECT_LABELS = [
        'Activity' => 'Activity',
        'ActivityImport' => 'Activity import',
        'ActivityProposal' => 'Proposal',
        'AssessmentAnalysis' => 'AI analysis',
        'AssessmentSummary' => 'Assessment summary',
        'AvailabilityRequest' => 'Availability request',
        'Beneficiary' => 'Beneficiary',
        'BudgetUtilization' => 'Budget entry',
        'College' => 'College',
        'Community' => 'Community',
        'ExtensionProject' => 'Project',
        'Faculty' => 'Faculty',
        'InteragencyAgency' => 'Interagency agency',
        'NeedsAssessment' => 'Needs assessment',
        'Program' => 'Program',
        'ProgramNarrative' => 'Project narrative',
        'RenderedHours' => 'Rendered hours',
        'UniversityTarget' => 'University target',
        'User' => 'User',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function setEvent(string $event): void
    {
        abort_unless(in_array($event, ['all', 'created', 'updated', 'status_transition', 'deleted', 'restored'], true), 422);

        $this->event = $event;
        $this->resetPage();
    }

    public function sortBy(string $column): void
    {
        if (! in_array($column, ['created_at', 'event', 'description'], true)) {
            return;
        }

        if ($this->sort === $column) {
            $this->direction = $this->direction === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sort = $column;
            $this->direction = $column === 'created_at' ? 'desc' : 'asc';
        }

        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSubject(): void
    {
        $this->resetPage();
    }

    /** The shared design-system paginator, not Livewire's Tailwind default (§14). */
    public function paginationView(): string
    {
        return 'livewire.partials.pagination';
    }

    public function eventColor(?string $event): string
    {
        return self::EVENT_COLORS[$event] ?? 'gray';
    }

    public function subjectLabel(?string $subjectType): string
    {
        if ($subjectType === null) {
            return '—';
        }

        $base = class_basename($subjectType);

        return self::SUBJECT_LABELS[$base] ?? $base;
    }

    public function render()
    {
        /* `causer` and `subject` are morphTo — eager-loaded so a 25-row page does
           not fire 50 extra queries. A deleted subject resolves to NULL, which the
           view renders as a dash rather than erroring. */
        $query = ActivityLog::query()
            ->with(['causer', 'subject'])
            ->when($this->event !== 'all', fn ($query) => $query->where('event', $this->event))
            ->when($this->subject !== 'all', fn ($query) => $query->where('subject_type', 'like', "%{$this->subject}"));

        if ($this->search !== '') {
            $search = trim($this->search);
            $query->where(function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%")
                    ->orWhereHasMorph('causer', [User::class], function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $rows = $query
            ->orderBy($this->sort, $this->direction)
            ->paginate(self::PER_PAGE);

        return view('livewire.audit-logs.index', [
            'rows' => $rows,
            'total' => $rows->total(),
            'subjectOptions' => self::SUBJECT_LABELS,
        ]);
    }
}
