<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * College (revision §4.1 / Phase R1) — top of the revised hierarchy:
 * College → Program → Project → Activity.
 */
class College extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'short_name',
        'description',
        'extension_coordinator_id',
        'status',
        'created_by',
        'updated_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "College {$eventName}");
    }

    public function extensionCoordinator()
    {
        return $this->belongsTo(Faculty::class, 'extension_coordinator_id');
    }

    /**
     * The projects delivered by this college.
     *
     * WIRED IN R2 — `extension_projects.college_id` is the direct FK, so a
     * college reaches its projects without going through programs. Both paths
     * are legitimate: `projects()` for a direct roll-up (the common case), and
     * `programs()->projects()` when the grouping matters.
     */
    public function projects()
    {
        return $this->hasMany(ExtensionProject::class);
    }

    /**
     * The broad programs associated with this college.
     *
     * NOTE: the `programs` table has NO `college_id`. This is deliberate, not
     * an omission. The six broad programs are university-wide CESO thrusts
     * (§3) — a thrust is not owned by a college; colleges deliver projects
     * *under* thrusts. A program therefore spans colleges, and its college
     * membership is DERIVED from the projects beneath it.
     *
     * Rather than declare a relation that cannot exist, use:
     *
     *     $college->projects()->distinct()->pluck('program_id')
     *
     * or the scope below. Adding `programs.college_id` would contradict §3 and
     * the University-tiles model in §2.2B.
     */
    public function scopeWithPrograms($query)
    {
        return $query->with(['projects.program']);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Colleges are a fixed, small set. Ordered by code so the UI is stable.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('code');
    }
}
