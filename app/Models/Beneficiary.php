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

    public function extensionPrograms()
    {
        return $this->belongsToMany(ExtensionProgram::class, 'extension_program_beneficiary');
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
     * De-duplication check per 5.4: match on first + last name + barangay.
     * Never silently merges — callers show a warning and require confirmation.
     */
    public static function duplicatesFor(string $firstName, string $lastName, string $barangay): Collection
    {
        return static::query()
            ->where('first_name', 'like', trim($firstName))
            ->where('last_name', 'like', trim($lastName))
            ->where('barangay', 'like', trim($barangay))
            ->get();
    }
}
