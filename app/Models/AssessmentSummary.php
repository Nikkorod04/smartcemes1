<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentSummary extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'community_id',
        'quarter',
        'year',
        'total_responses',
        'gender_distribution',
        'religion_distribution',
        'education_distribution',
        'civil_status_distribution',
        'livelihood_interests',
        'educational_interests',
        'health_problems',
        'family_problems',
        'employment_problems',
        'infrastructure_problems',
        'economic_problems',
        'security_problems',
        'water_sources',
        'house_types',
        'electricity_access_percentage',
        'organization_membership_percentage',
        'training_availability_percentage',
        'avg_service_satisfaction',
        'baseline_satisfaction_score',
        'ai_analysis',
        'ai_interventions',
        'ai_analysis_sections',
        'ai_analysis_generated_at',
        'last_calculated_at',
    ];

    protected $casts = [
        'gender_distribution' => 'array',
        'religion_distribution' => 'array',
        'education_distribution' => 'array',
        'civil_status_distribution' => 'array',
        'livelihood_interests' => 'array',
        'educational_interests' => 'array',
        'health_problems' => 'array',
        'family_problems' => 'array',
        'employment_problems' => 'array',
        'infrastructure_problems' => 'array',
        'economic_problems' => 'array',
        'security_problems' => 'array',
        'water_sources' => 'array',
        'house_types' => 'array',
        'ai_interventions' => 'array',
        'ai_analysis_sections' => 'array',
        'ai_analysis_generated_at' => 'datetime',
        'last_calculated_at' => 'datetime',
    ];

    public function community()
    {
        return $this->belongsTo(Community::class);
    }
}
