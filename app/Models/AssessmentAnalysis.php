<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AssessmentAnalysis extends Model
{
    use HasFactory, LogsActivity;

    public const APPROVAL_DRAFT = 'draft';

    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_DISCARDED = 'discarded';

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'needs_assessment_id',
        'assessment_summary_id',
        'raw_extracted_data',
        'extracted_fields',
        'problems_identified',
        'recommendations',
        // R6: Tier-2 referrals to agencies outside CESO's training mandate.
        'interagency_referrals',
        'summary',
        'confidence_score',
        'approval_status',
        'approved_by',
        'approved_at',
        'status',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'raw_extracted_data' => 'array',
        'extracted_fields' => 'array',
        'problems_identified' => 'array',
        'recommendations' => 'array',
        'interagency_referrals' => 'array',
        'metadata' => 'array',
        'confidence_score' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Assessment analysis {$eventName}");
    }

    public function needsAssessment()
    {
        return $this->belongsTo(NeedsAssessment::class);
    }

    /**
     * 6.10 aggregates actually sent to the LLM. Named deliberately to avoid
     * shadowing by the `summary` TEXT column (6.11).
     */
    public function assessmentSummary()
    {
        return $this->belongsTo(AssessmentSummary::class, 'assessment_summary_id');
    }

    /**
     * Resolve the community through the linked assessment summary.
     *
     * This is intentionally not named `community()`: Eloquent reserves that
     * convention for relationship methods that return a Relation instance.
     */
    public function resolvedCommunity(): ?Community
    {
        return $this->assessmentSummary?->community;
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** D4: Admin approves/discards drafts. */
    public function isDraft(): bool
    {
        return $this->approval_status === self::APPROVAL_DRAFT;
    }

    /* ------------------------------------------------------------------ */
    /* R6 — interagency referrals */
    /* ------------------------------------------------------------------ */

    /**
     * The Tier-1 CESO interventions, split out from the Tier-2 referrals.
     *
     * The two live in separate columns (see the R6 migration docblock), so the
     * UI groups them without re-deriving anything: this returns exactly what the
     * model put in `recommendations[]`.
     */
    public function cesoInterventions(): array
    {
        return $this->recommendations ?? [];
    }

    /**
     * The Tier-2 referrals, each resolved against the live catalogue.
     *
     * Returns a list of `['need', 'rationale', 'agency' => ?InteragencyAgency,
     * 'as_cited' => [...]]`. `agency` is NULL when the cited code is not in the
     * catalogue — the UI shows those as unverified rather than hiding them, so a
     * hallucinated agency is visible to the reviewer instead of silently dropped.
     *
     * Resolution is against the CURRENT catalogue, not the snapshot: if the
     * Director retires an agency, an old referral should show as unresolved
     * rather than continue to look legitimate.
     */
    public function resolvedReferrals(): array
    {
        return collect($this->interagency_referrals ?? [])
            ->map(function (array $referral) {
                $agency = InteragencyAgency::resolve($referral['agency_code'] ?? null);

                return [
                    'need' => $referral['need'] ?? '—',
                    'rationale' => $referral['rationale'] ?? '',
                    'agency' => $agency,
                    // What the model actually returned, kept for the reviewer
                    // when it does not match the catalogue.
                    'as_cited' => [
                        'agency_code' => $referral['agency_code'] ?? null,
                        'agency_name' => $referral['agency_name'] ?? null,
                    ],
                ];
            })
            ->all();
    }

    /** Are there any Tier-2 referrals at all? (An empty array is a valid result.) */
    public function hasReferrals(): bool
    {
        return ! empty($this->interagency_referrals);
    }

    /**
     * How many referrals cite an agency that is NOT in the catalogue.
     *
     * Surfaced on the review screen: a non-zero count means the model produced an
     * unverifiable citation, which the reviewer should see rather than the system
     * quietly correcting.
     */
    public function unverifiedReferralCount(): int
    {
        return collect($this->resolvedReferrals())
            ->filter(fn (array $r) => $r['agency'] === null)
            ->count();
    }

    /* ------------------------------------------------------------------ */
    /* Generation lineage (2026-10-07 redesign) */
    /* ------------------------------------------------------------------ */

    /**
     * Memoised generation list — see `siblings()`.
     *
     * Private, so it is never serialised by Livewire and never confused with a
     * database attribute.
     */
    private ?Collection $siblingsCache = null;

    /**
     * Every analysis for the SAME summary, oldest first.
     *
     * A summary can legitimately have several generations — regenerating creates
     * a new row and leaves the old one intact (the live DB holds 4 for one
     * summary). Ordered by **id, not `created_at`**, because generations created
     * in the same second would otherwise sort arbitrarily; the id is monotonic.
     *
     * ⚠️ **Memoised per instance, and that matters.** A row reads up to four
     * lineage values (`generationCount`, `generationIndex`, `isCurrent`,
     * `isSuperseded`) and each one asks this question. Un-memoised that measured
     * **4 queries per row — 24 of the queue's 36 queries** for six generations.
     * `refresh()` drops the cache, so a state change is never read back through a
     * stale list.
     *
     * @return Collection<int, self>
     */
    public function siblings(): Collection
    {
        if ($this->siblingsCache !== null) {
            return $this->siblingsCache;
        }

        if ($this->assessment_summary_id === null) {
            return $this->siblingsCache = collect([$this]);
        }

        return $this->siblingsCache = static::query()
            ->where('assessment_summary_id', $this->assessment_summary_id)
            ->orderBy('id')
            ->get();
    }

    /** Drop the memoised lineage — see `siblings()`. */
    public function refresh(): static
    {
        $this->siblingsCache = null;

        return parent::refresh();
    }

    /** 1-based position of this analysis within its summary. */
    public function generationIndex(): int
    {
        $index = $this->siblings()->search(fn (self $a) => $a->id === $this->id);

        return $index === false ? 1 : $index + 1;
    }

    /** How many generations exist for this summary. */
    public function generationCount(): int
    {
        return $this->siblings()->count();
    }

    /**
     * The generation the Director should treat as authoritative: the NEWEST
     * **completed** generation for the summary that has not been discarded.
     *
     * Derived, never stored — so it cannot drift from the rows, and it needs no
     * `superseded_by` column.
     *
     * ⚠️ A FAILED newer attempt must NOT become "current": a regeneration that
     * fails leaves the previous usable draft as the best thing available, so
     * demoting it would be wrong. (Caught on the real page — a failed row was
     * rendering as `current`.)
     */
    public function isCurrent(): bool
    {
        $newest = $this->siblings()
            ->reject(fn (self $a) => $a->approval_status === self::APPROVAL_DISCARDED)
            ->filter(fn (self $a) => $a->status === self::STATUS_COMPLETED)
            ->last();

        return $newest?->id === $this->id;
    }

    /** A completed generation that a newer usable one has replaced. */
    public function isSuperseded(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && $this->approval_status !== self::APPROVAL_DISCARDED
            && ! $this->isCurrent();
    }

    /**
     * The single state the queue filters on — the two axes (`status`, the
     * pipeline; `approval_status`, the human gate) collapsed for display.
     */
    public function queueState(): string
    {
        return match (true) {
            $this->status === self::STATUS_FAILED => 'failed',
            $this->status !== self::STATUS_COMPLETED => 'pending',
            $this->approval_status === self::APPROVAL_APPROVED => 'approved',
            $this->approval_status === self::APPROVAL_DISCARDED => 'discarded',
            default => 'awaiting_review',
        };
    }

    /**
     * Can this generation be deleted?
     *
     * Two generations must NEVER be deletable:
     *  - an **approved** one — it is citable in reports and its content is mirrored
     *    onto the summary (`ai_analysis*`), so deleting it would orphan a citation;
     *  - the **current draft** — it is the queue's only actionable row, and Discard
     *    is the way to retire it (discard, then delete, if you really mean it).
     *
     * Everything else (failed, pending, discarded, superseded) is a past attempt.
     *
     * A HARD delete is safe here, which is unusual: nothing carries a foreign key
     * to `assessment_analyses`, and `AuditLogs\Index` already renders a NULL
     * subject for a row that no longer exists — so the audit trail keeps the
     * description text and degrades gracefully. The deletion is itself logged.
     */
    public function isDeletable(): bool
    {
        if ($this->approval_status === self::APPROVAL_APPROVED) {
            return false;
        }

        return ! ($this->queueState() === 'awaiting_review' && $this->isCurrent());
    }

    /**
     * A short, human reason for a failed generation.
     *
     * `error_message` stores the provider's RAW body (a full JSON error
     * document), which is unreadable on a row and unreachable by keyboard when
     * hidden in a `title=`. The raw text stays stored for forensics; this only
     * renders a summary of it.
     */
    public function failureSummary(): string
    {
        $raw = trim((string) $this->error_message);

        if ($raw === '') {
            return 'Analysis unavailable.';
        }

        return match (true) {
            str_contains($raw, '503') => 'AI request failed — HTTP 503, the provider was overloaded.',
            str_contains($raw, '429') => 'AI request failed — HTTP 429, rate limited.',
            str_contains($raw, 'quota') => 'AI request failed — quota exceeded.',
            str_contains($raw, '401'), str_contains($raw, '403') => 'AI request failed — the API key was rejected.',
            str_contains($raw, 'timed out'), str_contains($raw, 'timeout') => 'AI request failed — the request timed out.',
            default => Str::limit(preg_replace('/\s+/', ' ', $raw), 90),
        };
    }
}
