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
}
