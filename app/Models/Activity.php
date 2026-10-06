<?php

namespace App\Models;

use App\Services\TrainingHoursService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Activity extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'extension_project_id',
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
        // Phase R4 training-hours model (revision §4.4).
        'no_of_days',
        'participants',
        'trainors_snapshot',
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
        // decimal(4,1) so a half day survives as 0.5 (D-R4). Cast as a float
        // string would corrupt `0.5` arithmetic, hence 'float'.
        'no_of_days' => 'float',
        'participants' => 'integer',
        'trainors_snapshot' => 'integer',
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
        return $this->belongsTo(ExtensionProject::class, 'extension_project_id');
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

    /**
     * 8.8: two activities overlap when date ranges AND times overlap.
     */
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

    /* ------------------------------------------------------------------ */
    /* Training hours (Phase R4, revision §4.4) */
    /* ------------------------------------------------------------------ */

    /**
     * The training-hours breakdown for this activity.
     *
     * Delegates to TrainingHoursService so there is exactly one implementation
     * of `trainors x trainees x days` in the codebase — no `x 8`, and the
     * trainee source tag travels with the number (R-Q1).
     *
     * Returns a plain array rather than an accessor attribute because the result
     * carries several fields (trainees, source, days, formula) that an attribute
     * string could not express.
     */
    public function trainingBreakdown(): array
    {
        return app(TrainingHoursService::class)->forActivity($this);
    }

    /** Training hours contributed by this activity. */
    public function trainingHours(): float
    {
        return $this->trainingBreakdown()['hours'];
    }

    /**
     * Days as displayed — NULL renders as a full day (1), matching the
     * prototype's activity table. Use `no_of_days === null` directly when the
     * question is "was a duration actually recorded?".
     */
    public function getDaysDisplayAttribute(): string
    {
        return app(TrainingHoursService::class)
            ->formatDays($this->no_of_days !== null ? (float) $this->no_of_days : 1.0);
    }

    /**
     * Trainors = snapshot override, else assigned faculty count (§4.4).
     */
    public function getTrainorsCountAttribute(): int
    {
        if ($this->trainors_snapshot !== null) {
            return (int) $this->trainors_snapshot;
        }

        return (int) ($this->faculty_count ?? $this->faculty()->count());
    }

    /** Where the trainee figure came from: attendance | manual | none (R-Q1). */
    public function getTraineesSourceAttribute(): string
    {
        return $this->trainingBreakdown()['trainees_source'];
    }
}
