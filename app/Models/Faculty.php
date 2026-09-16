<?php

namespace App\Models;

use Database\Factories\FacultyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faculty extends Model
{
    /** @use HasFactory<FacultyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'employee_id',
        'department',
        'specialization',
        'position',
        'avatar',
        'phone',
        'address',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activities()
    {
        return $this->belongsToMany(Activity::class, 'activity_faculty');
    }

    public function availabilityRequests()
    {
        return $this->hasMany(AvailabilityRequest::class);
    }

    public function activityProposals()
    {
        return $this->hasMany(ActivityProposal::class);
    }

    public function renderedHours()
    {
        return $this->hasMany(RenderedHours::class);
    }

    public function ledPrograms()
    {
        return $this->hasMany(ExtensionProgram::class, 'program_lead_id');
    }
}
