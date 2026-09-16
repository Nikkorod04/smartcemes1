<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RenderedHours extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const SOURCE_AUTO = 'auto';

    public const SOURCE_MANUAL = 'manual';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'faculty_id',
        'activity_id',
        'date',
        'hours',
        'source',
        'status',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'remarks',
    ];

    protected $casts = [
        'date' => 'date',
        'hours' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Rendered hours {$eventName}");
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** 8.9: approved entries are LOCKED and immutable. */
    public function isLocked(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /** Auto-draft hours = activity duration; null when end <= start (8.9 guard). */
    public static function autoHoursFor(Activity $activity): ?float
    {
        if ($activity->end_time <= $activity->start_time) {
            return null; // overnight/invalid schedule — faculty records manually
        }

        $minutes = $activity->start_time->diffInMinutes($activity->end_time);

        return round($minutes / 60, 2);
    }
}
