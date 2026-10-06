<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    public function community(): ?Community
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
}
