<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ActivityProposal extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'faculty_id',
        'extension_program_id',
        'community_id',
        'created_activity_id',
        'title',
        'description',
        'proposed_start_date',
        'proposed_end_date',
        'budget_estimate',
        'special_order_path',
        'status',
        'submitted_at',
        'admin_remarks',
        'admin_approved_at',
        'admin_approved_by',
        'assessment_deadline',
        'secretary_remarks',
        'secretary_approved_at',
        'secretary_approved_by',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'proposed_start_date' => 'date',
        'proposed_end_date' => 'date',
        'budget_estimate' => 'decimal:2',
        'submitted_at' => 'datetime',
        'admin_approved_at' => 'datetime',
        'assessment_deadline' => 'date',
        'secretary_approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Proposal {$eventName}");
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function program()
    {
        return $this->belongsTo(ExtensionProgram::class, 'extension_program_id');
    }

    public function community()
    {
        return $this->belongsTo(Community::class);
    }

    public function createdActivity()
    {
        return $this->belongsTo(Activity::class, 'created_activity_id');
    }

    public function activity()
    {
        return $this->hasOne(Activity::class);
    }

    public function documents()
    {
        return $this->hasMany(ProposalDocument::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'admin_approved_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * 5.12 / 8.8: proposed dates must fall within the target program's
     * range BEFORE approval — approval is blocked otherwise.
     */
    public function violatesProgramRange(): bool
    {
        return $this->proposed_start_date < $this->program->planned_start_date
            || $this->proposed_end_date > $this->program->planned_end_date;
    }
}
