<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Beneficiary extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'age',
        'gender',
        'email',
        'phone',
        'address',
        'barangay',
        'municipality',
        'province',
        'community_id',
        'extension_project_id',
        'beneficiary_category',
        'monthly_income',
        'occupation',
        'educational_attainment',
        'marital_status',
        'number_of_dependents',
        'status',
        'notes',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->setDescriptionForEvent(fn (string $eventName) => "Beneficiary {$eventName}");
    }

    public function community()
    {
        return $this->belongsTo(Community::class);
    }

    /** The one project this beneficiary belongs to (2026-11-20). */
    public function extensionProject()
    {
        return $this->belongsTo(ExtensionProject::class, 'extension_project_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function fullName(): string
    {
        return trim(implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name])));
    }

    /**
     * De-duplication check per 5.4: match on first + last name + community.
     * Never silently merges — callers show a warning and require confirmation.
     *
     * With one project per beneficiary, `$projectId` scopes the check to the
     * roster the caller is working in; omit it for a registry-wide check.
     */
    public static function duplicatesFor(
        string $firstName,
        string $lastName,
        ?int $communityId,
        ?int $projectId = null,
    ): Collection {
        return static::query()
            ->when($projectId !== null, fn ($q) => $q->where('extension_project_id', $projectId))
            ->when($communityId !== null, fn ($q) => $q->where('community_id', $communityId))
            ->where('first_name', 'like', trim($firstName))
            ->where('last_name', 'like', trim($lastName))
            ->with('extensionProject')
            ->get();
    }
}
