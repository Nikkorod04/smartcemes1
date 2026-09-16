<?php

namespace App\Models;

use App\Services\AssessmentSummaryService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NeedsAssessment extends Model
{
    use HasFactory, SoftDeletes;

    /** Single-select yes/no fields per Section 7. */
    public const YES_NO_FIELDS = [
        'household_member_currently_studying',
        'interested_in_continuing_studies',
        'has_barangay_health_programs',
        'benefits_from_barangay_programs',
        'has_own_toilet',
        'keeps_animals',
        'has_electricity',
        'member_of_organization',
        'available_for_training',
    ];

    protected $fillable = [
        'community_id',
        'quarter',
        'year',
        'file_path',
        'uploaded_by',
        'review_status',
        'reviewed_by',
        'reviewed_at',
        'review_remarks',
        'respondent_first_name',
        'respondent_middle_name',
        'respondent_last_name',
        'respondent_age',
        'respondent_civil_status',
        'respondent_sex',
        'respondent_religion',
        'respondent_educational_attainment',
        'family_composition',
        'livelihood_options',
        'desired_training',
        'barangay_educational_facilities',
        'household_member_currently_studying',
        'interested_in_continuing_studies',
        'areas_of_educational_interest',
        'preferred_training_time',
        'preferred_training_days',
        'common_illnesses',
        'action_when_sick',
        'barangay_medical_supplies_available',
        'has_barangay_health_programs',
        'benefits_from_barangay_programs',
        'programs_benefited_from',
        'water_source',
        'water_source_distance',
        'garbage_disposal_method',
        'has_own_toilet',
        'toilet_type',
        'keeps_animals',
        'animals_kept',
        'house_type',
        'tenure_status',
        'has_electricity',
        'light_source_without_power',
        'appliances_owned',
        'barangay_recreational_facilities',
        'use_of_free_time',
        'member_of_organization',
        'organization_types',
        'organization_meeting_frequency',
        'organization_usual_activities',
        'household_members_in_organization',
        'position_in_organization',
        'family_problems',
        'health_problems',
        'educational_problems',
        'employment_problems',
        'infrastructure_problems',
        'economic_problems',
        'security_problems',
        'barangay_service_ratings',
        'general_feedback',
        'available_for_training',
        'reason_not_available',
        'other_text',
    ];

    protected $casts = [
        // v4.9: single-select fields store plain strings (no cast); only the
        // true multi-select fields keep the array cast.
        'barangay_educational_facilities' => 'array',
        'preferred_training_days' => 'array',
        'barangay_medical_supplies_available' => 'array',
        'programs_benefited_from' => 'array',
        'animals_kept' => 'array',
        'appliances_owned' => 'array',
        'barangay_recreational_facilities' => 'array',
        'use_of_free_time' => 'array',
        'family_problems' => 'array',
        'health_problems' => 'array',
        'educational_problems' => 'array',
        'employment_problems' => 'array',
        'infrastructure_problems' => 'array',
        'economic_problems' => 'array',
        'security_problems' => 'array',
        'barangay_service_ratings' => 'array',
        'other_text' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function community()
    {
        return $this->belongsTo(Community::class);
    }

    protected static function booted(): void
    {
        // 6.10: recompute the (community, quarter, year) summary whenever a
        // NeedsAssessment is created or its review_status changes.
        static::created(fn (self $assessment) => self::recompute($assessment));
        static::updated(function (self $assessment) {
            if ($assessment->wasChanged('review_status')) {
                self::recompute($assessment);
            }
        });
    }

    protected static function recompute(self $assessment): void
    {
        app(AssessmentSummaryService::class)->recomputeFor(
            $assessment->community_id,
            (int) $assessment->quarter,
            (int) $assessment->year
        );
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public static function labelFor(string $field): string
    {
        return ucwords(str_replace('_', ' ', $field));
    }
}
