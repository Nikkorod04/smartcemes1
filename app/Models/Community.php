<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Community extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const TYPE_COMMUNITY = 'community';

    public const TYPE_SCHOOL = 'school';

    public const SCHOOL_LEVELS = ['elementary', 'secondary', 'higher_ed'];

    protected $fillable = [
        'name',
        'municipality',
        'province',
        'type',
        'school_level',
        'description',
        'contact_person',
        'contact_number',
        'email',
        'address',
        'status',
        'notes',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function isSchool(): bool
    {
        return $this->type === self::TYPE_SCHOOL;
    }

    public function needsAssessments()
    {
        return $this->hasMany(NeedsAssessment::class);
    }

    public function assessmentSummaries()
    {
        return $this->hasMany(AssessmentSummary::class);
    }

    public function extensionPrograms()
    {
        return $this->belongsToMany(ExtensionProgram::class, 'community_extension_program');
    }

    public function beneficiaries()
    {
        return $this->hasMany(Beneficiary::class);
    }
}
