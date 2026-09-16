<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Activity extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'extension_program_id',
        'title',
        'description',
        'planned_start_date',
        'planned_end_date',
        'start_time',
        'end_time',
        'actual_start_date',
        'actual_end_date',
        'venue',
        'status',
        'notes',
        'allocated_budget',
        'pre_assessment_score',
        'post_assessment_score',
        'satisfaction_rating',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'allocated_budget' => 'decimal:2',
        'pre_assessment_score' => 'decimal:2',
        'post_assessment_score' => 'decimal:2',
        'satisfaction_rating' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Activity {$eventName}");
    }

    public function program()
    {
        return $this->belongsTo(ExtensionProgram::class, 'extension_program_id');
    }

    public function faculty()
    {
        return $this->belongsToMany(Faculty::class, 'activity_faculty');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function proposal()
    {
        return $this->belongsTo(ActivityProposal::class, 'activity_proposal_id');
    }

    public function renderedHours()
    {
        return $this->hasMany(RenderedHours::class);
    }

    public function availabilityRequests()
    {
        return $this->hasMany(AvailabilityRequest::class);
    }

    public function budgetUtilizations()
    {
        return $this->hasMany(BudgetUtilization::class);
    }

    /** XLSX import provenance — attendance and evaluation (6.19, v4.13). */
    public function activityImports()
    {
        return $this->hasMany(ActivityImport::class);
    }

    /** 8.8: two activities overlap when date ranges AND times overlap. */
    public function overlaps(self $other): bool
    {
        $dateOverlap = $this->planned_start_date <= $other->planned_end_date
            && $other->planned_start_date <= $this->planned_end_date;

        if (! $dateOverlap) {
            return false;
        }

        return $this->start_time < $other->end_time
            && $other->start_time < $this->end_time;
    }
}
