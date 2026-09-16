<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AvailabilityRequest extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    protected $fillable = [
        'activity_id',
        'faculty_id',
        'date',
        'start_time',
        'end_time',
        'status',
        'requested_by',
        'requested_at',
        'remarks',
        'responded_by',
        'responded_at',
        'decline_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Availability request {$eventName}");
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function responder()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    /**
     * 8.8: a faculty member cannot ACCEPT a request whose date/time overlaps
     * their accepted requests or existing activity assignments.
     */
    public function overlapsFacultySchedule(): bool
    {
        $faculty = $this->faculty;

        $overlaps = function ($start, $end, $dateStart, $dateEnd) {
            return $this->date <= $dateEnd && $start <= $dateEnd && $end >= $dateStart
                && $dateStart <= $this->date;
        };

        // Overlap with accepted availability requests.
        $accepted = $faculty->availabilityRequests()
            ->where('status', self::STATUS_ACCEPTED)
            ->whereKeyNot($this->getKey())
            ->get();

        foreach ($accepted as $req) {
            if ($req->date->equalTo($this->date)
                && $this->start_time < $req->end_time
                && $req->start_time < $this->end_time) {
                return true;
            }
        }

        // Overlap with assigned activities.
        $assigned = $faculty->activities()
            ->whereNotIn('status', ['cancelled'])
            ->get();

        foreach ($assigned as $activity) {
            if ($activity->planned_start_date <= $this->date
                && $activity->planned_end_date >= $this->date
                && $this->start_time < $activity->end_time
                && $activity->start_time < $this->end_time) {
                return true;
            }
        }

        return false;
    }
}
