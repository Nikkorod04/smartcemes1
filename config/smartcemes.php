<?php

/*
|--------------------------------------------------------------------------
| SmartCEMES system configuration
|--------------------------------------------------------------------------
| Single source of truth for the controlled vocabularies (Blueprint §7),
| status color mapping (§9 design system), KPI keys (§8.6) and role nav.
| Server-side validation MUST reference these values — never hardcode
| option lists in views or controllers.
*/

return [

    /*
    |----------------------------------------------------------------------
    | Roles (6.18)
    |----------------------------------------------------------------------
    */
    'roles' => ['admin', 'secretary', 'faculty'],

    /*
    |----------------------------------------------------------------------
    | Status enumerations (6.18)
    |----------------------------------------------------------------------
    */
    'statuses' => [
        'community' => ['active', 'prospecting'],
        'program' => ['draft', 'ongoing', 'completed', 'cancelled'],
        'activity' => ['draft', 'ongoing', 'completed', 'cancelled'],
        'attendance' => ['present', 'absent', 'excused', 'late'],
        'review' => ['pending', 'validated', 'returned'],
    ],

    /*
    |----------------------------------------------------------------------
    | Status → badge color map (§9 design system)
    |----------------------------------------------------------------------
    */
    'status_colors' => [
        'ongoing' => 'green', 'approved' => 'green', 'validated' => 'green',
        'active' => 'green', 'completed' => 'green', 'present' => 'green',
        'pending' => 'yellow', 'draft' => 'yellow', 'absent' => 'yellow',
        'rejected' => 'red', 'returned' => 'red', 'cancelled' => 'red',
        'failed' => 'red', 'late' => 'red', 'excused' => 'gray',
        'upcoming' => 'blue',
        'prospecting' => 'gray', 'on_track' => 'blue', 'achieved' => 'green',
        'not_met' => 'red', 'not_started' => 'gray',
    ],

    /*
    |----------------------------------------------------------------------
    | Chart palette (§9)
    |----------------------------------------------------------------------
    */
    'chart_palette' => ['#003599', '#F6B800', '#2547eb', '#93b4fd', '#fdd24a'],

    /*
    |----------------------------------------------------------------------
    | KPI dictionary — locked keys (8.6)
    |----------------------------------------------------------------------
    */
    'kpi_metrics' => [
        'participation_rate' => 'Participation Rate (%)',
        'activity_completion_rate' => 'Activity Completion Rate (%)',
        'attendance_consistency' => 'Attendance Consistency (%)',
        'budget_utilization' => 'Budget Utilization (%)',
        'knowledge_gain' => 'Knowledge Gain (points)',
        'cost_per_beneficiary' => 'Cost per Beneficiary (currency)',
        'community_reach' => 'Community Reach (count)',
    ],

    /*
    |----------------------------------------------------------------------
    | Communities & Partner Schools (6.3) — record types + school levels
    |----------------------------------------------------------------------
    */
    'community_types' => ['community', 'school'],

    'school_levels' => ['elementary', 'secondary', 'higher_ed'],

    /*
    |----------------------------------------------------------------------
    | Beneficiary categories (5.4 controlled vocabulary)
    |----------------------------------------------------------------------
    */
    'beneficiary_categories' => [
        'Farmer', 'Fisherfolk', 'Fisherman', 'Housewife', 'Parent',
        'Senior Citizen', 'Out-of-School Youth', 'Student', 'Vendor',
        'Tricycle Driver', 'Construction Worker', 'Barangay Worker',
        'Unemployed', 'Other',
    ],

    /*
    |----------------------------------------------------------------------
    | Beneficiary genders (5.4 / XLSX import template normalization)
    |----------------------------------------------------------------------
    */
    'beneficiary_genders' => ['Female', 'Male', 'Prefer not to say'],

    /*
    |----------------------------------------------------------------------
    | Quarters (D1 — calendar quarters, integers)
    |----------------------------------------------------------------------
    */
    'quarters' => [1, 2, 3, 4],

    /*
    |----------------------------------------------------------------------
    | Uploads (§9: 10 MB; pdf/jpg/png/docx/xlsx; MIME + extension validated)
    |----------------------------------------------------------------------
    */
    'uploads' => [
        'max_kb' => 10240,
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'docx', 'xlsx'],
    ],

    /*
    |----------------------------------------------------------------------
    | Needs-assessment form vocabularies (Section 7) — authoritative lists
    |----------------------------------------------------------------------
    */
    'vocab' => [
        'respondent_sex' => ['Male', 'Female', 'Prefer not to say', 'Other'],
        'respondent_civil_status' => ['Single', 'Married', 'Widowed', 'Separated', 'Divorced'],
        'respondent_religion' => ['Roman Catholic', 'Islam', 'Iglesia ni Cristo', 'Evangelical Christianity', 'United Church of Christ in the Philippines', 'Assemblies of God', 'Seventh-day Adventist Church', 'Philippine Independent Church (Aglipayan)', 'Baptist', 'Jehovah\'s Witnesses', 'Church of Jesus Christ of Latter-day Saints (Mormon)', 'Methodist', 'Church of Christ', 'Buddhism', 'Indigenous / Tribal Religions', 'Prefer not to say'],
        'respondent_educational_attainment' => ['Elementary Undergraduate', 'Elementary Graduate', 'Junior High School Undergraduate', 'Junior High School Graduate', 'Senior High School Undergraduate', 'Senior High School Graduate', 'College Undergraduate', 'College Graduate', 'Vocational / Technical', 'Postgraduate', 'Other'],

        'family_composition' => ['2 members', '3 members', '4 members', '5 members', '6 members', '7 members', '8+ members'],
        'yes_no' => ['Yes', 'No'],
        'household_members_in_organization' => ['1 member', '2 members', '3 members', '4 members', '5 members', '6+ members', 'None'],

        'livelihood_options' => ['Agriculture', 'Farming', 'Fishing', 'Retail / sari-sari store', 'Food vending', 'Construction work', 'Transportation', 'Home-based income', 'Skilled labor', 'OFW family remittance', 'Unemployed', 'Other'],
        'desired_training' => ['Food processing', 'Rice/corn farming', 'Livestock raising', 'Vegetable production', 'Dressmaking / sewing', 'Soap making', 'Fish processing', 'Handicraft making', 'Beauty care', 'Household enterprise', 'Other'],

        'barangay_educational_facilities' => ['Elementary school', 'High school', 'Day care center', 'Library', 'Computer lab', 'Adult learning center', 'Scholarship support', 'Other'],
        'areas_of_educational_interest' => ['Literacy', 'Basic education', 'Computer literacy', 'TESDA skills', 'Agriculture training', 'Livelihood entrepreneurship', 'Food processing', 'Beauty care', 'Tailoring / sewing', 'Other'],
        'preferred_training_time' => ['Morning 8:00-12:00', 'Afternoon 1:00-5:00', 'Evening', 'Flexible'],
        'preferred_training_days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday', 'Flexible'],

        'common_illnesses' => ['Fever', 'Cough / colds', 'Diarrhea', 'Hypertension', 'Diabetes', 'Asthma', 'Skin infection', 'Headache', 'Toothache', 'Respiratory illness', 'Other'],
        'action_when_sick' => ['Self-medication', 'Consult barangay health worker', 'Visit clinic', 'Go to hospital', 'Traditional healer', 'Ask family members', 'No action', 'Other'],
        'barangay_medical_supplies_available' => ['First aid kit', 'Paracetamol', 'Antibiotics', 'Vitamins', 'Bandages', 'Basic wound care', 'No supplies', 'Other'],
        'programs_benefited_from' => ['Vaccination', 'Maternal and child health', 'Nutrition program', 'Health education', 'Disease prevention', 'Sanitation drive', 'None', 'Other'],
        'water_source' => ['Deep well', 'Level II / piped', 'Community water system', 'Spring', 'Rainwater', 'River / stream', 'Other'],
        'water_source_distance' => ['Just outside', '250 meters away', '500 meters away', 'More than 500 meters', 'No idea'],
        'garbage_disposal_method' => ['Burning', 'Open dumping', 'Burial', 'Segregation / composting', 'Collected by barangay', 'Burying', 'Other'],
        'toilet_type' => ['Flush toilet', 'Pour flush', 'Ventilated improved pit', 'Shared toilet', 'Open pit', 'No toilet', 'Other'],
        'animals_kept' => ['Chicken', 'Duck', 'Pig', 'Goat', 'Cow', 'Carabao', 'Dog', 'Cat', 'Other'],

        'house_type' => ['Concrete / solid house', 'Semi-concrete', 'Wooden house', 'Bamboo / nipa', 'Makeshift / improvised', 'Other'],
        'tenure_status' => ['Owner', 'Renter', 'Living with relatives', 'Caretaker', 'Informal occupancy', 'Other'],
        'light_source_without_power' => ['Kerosene lamp', 'Solar lamp', 'Candles', 'Battery lantern', 'Generator', 'None', 'Other'],
        'appliances_owned' => ['Television', 'Radio', 'Cellphone', 'Electric fan', 'Refrigerator', 'Rice cooker', 'Gas stove', 'Washing machine'],

        'barangay_recreational_facilities' => ['Playground', 'Basketball court', 'Community center', 'Park', 'Sports field', 'Function hall'],
        'use_of_free_time' => ['Household chores', 'Resting', 'Watching TV', 'Socializing', 'Community meeting', 'Sports / recreation', 'Helping in livelihood work'],
        'organization_types' => ['Farmers group', 'Women organization', 'Youth group', 'Fisherfolk group', 'Cooperative', 'Religious organization', 'Barangay council'],
        'organization_meeting_frequency' => ['Weekly', 'Monthly', 'Twice a month', 'Quarterly', 'Yearly', 'Not regular'],
        'organization_usual_activities' => ['Meetings', 'Training', 'Community clean-up', 'Livelihood activities', 'Disaster response', 'Planning sessions'],
        'position_in_organization' => ['Officer', 'Member', 'Volunteer', 'Leader', 'Treasurer', 'Secretary', 'President'],

        'family_problems' => ['Low income', 'Insufficient food', 'Lack of employment', 'Large family size', 'Poor housing', 'Education expenses', 'Medical expenses', 'Family conflict'],
        'health_problems' => ['Malnutrition', 'High blood pressure', 'Diabetes', 'Respiratory illness', 'Skin disease', 'Infectious disease', 'Poor sanitation', 'Limited medical access'],
        'educational_problems' => ['Lack of school supplies', 'Distance to school', 'No available scholarship', 'Poor reading skills', 'Low school attendance', 'No internet access'],
        'employment_problems' => ['Lack of jobs', 'Low wages', 'No skills training', 'Seasonal employment', 'Underemployment', 'Transport to work'],
        'infrastructure_problems' => ['Poor roads', 'No drainage', 'No electricity', 'No potable water', 'Poor sanitation', 'No flood control', 'No community facilities'],
        'economic_problems' => ['Low income', 'Price inflation', 'Debt', 'Lack of capital', 'Market access', 'No savings'],
        'security_problems' => ['Illegal drugs', 'Crime', 'Traffic issues', 'Land dispute', 'Domestic conflict', 'Fire hazard', 'No street lights'],

        'barangay_service_ratings' => ['Very poor', 'Poor', 'Fair', 'Good', 'Very good'],
        'reason_not_available' => ['Work schedule conflict', 'Health reasons', 'Family obligation', 'Distance / transportation', 'No interest'],
    ],

    /*
    |----------------------------------------------------------------------
    | AI pipeline (8.5 / D3 / D4 / D12 / v4.2)
    |----------------------------------------------------------------------
    | Google Gemini (flash class, free tier) — live API ONLY, no mock mode.
    | Aggregation-before-send: only aggregate statistics leave the system.
    */
    'ai' => [
        'endpoint' => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models'),
        'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),
        'prompt_version' => 'v1',
    ],

    /*
    |----------------------------------------------------------------------
    | Multi-select field groups for the assessment form wizard (Sections I-IX)
    |----------------------------------------------------------------------
    */
    'assessment_sections' => [
        [
            'key' => 'I', 'title' => 'Respondent Information', 'icon' => 'doc',
            'fields' => [
                'respondent_sex' => 'single', 'respondent_civil_status' => 'single',
                'respondent_religion' => 'single', 'respondent_educational_attainment' => 'single',
            ],
        ],
        [
            'key' => 'II', 'title' => 'Family Composition', 'icon' => 'users',
            'fields' => [
                'family_composition' => 'single', 'household_member_currently_studying' => 'yesno',
                'household_members_in_organization' => 'single',
            ],
        ],
        [
            'key' => 'III', 'title' => 'Economic / Livelihood', 'icon' => 'wallet',
            'fields' => [
                'livelihood_options' => 'single', 'desired_training' => 'single',
            ],
        ],
        [
            'key' => 'IV', 'title' => 'Education', 'icon' => 'doc',
            'fields' => [
                'barangay_educational_facilities' => 'multi', 'interested_in_continuing_studies' => 'yesno',
                'areas_of_educational_interest' => 'single', 'preferred_training_time' => 'single',
                'preferred_training_days' => 'multi',
            ],
        ],
        [
            'key' => 'V', 'title' => 'Health and Sanitation', 'icon' => 'check',
            'fields' => [
                'common_illnesses' => 'single', 'action_when_sick' => 'single',
                'barangay_medical_supplies_available' => 'multi', 'has_barangay_health_programs' => 'yesno',
                'benefits_from_barangay_programs' => 'yesno', 'programs_benefited_from' => 'multi',
                'water_source' => 'single', 'water_source_distance' => 'single',
                'garbage_disposal_method' => 'single', 'has_own_toilet' => 'yesno',
                'toilet_type' => 'single', 'keeps_animals' => 'yesno', 'animals_kept' => 'multi',
            ],
        ],
        [
            'key' => 'VI', 'title' => 'Housing and Basic Amenities', 'icon' => 'pin',
            'fields' => [
                'house_type' => 'single', 'tenure_status' => 'single',
                'has_electricity' => 'yesno', 'light_source_without_power' => 'single',
                'appliances_owned' => 'multi',
            ],
        ],
        [
            'key' => 'VII', 'title' => 'Recreation, Organization, and Social Participation', 'icon' => 'people',
            'fields' => [
                'barangay_recreational_facilities' => 'multi', 'use_of_free_time' => 'multi',
                'member_of_organization' => 'yesno', 'organization_types' => 'single',
                'organization_meeting_frequency' => 'single', 'organization_usual_activities' => 'single',
                'position_in_organization' => 'single',
            ],
        ],
        [
            'key' => 'VIII', 'title' => 'Problems and Priorities', 'icon' => 'clipboard',
            'fields' => [
                'family_problems' => 'multi', 'health_problems' => 'multi',
                'educational_problems' => 'multi', 'employment_problems' => 'multi',
                'infrastructure_problems' => 'multi', 'economic_problems' => 'multi',
                'security_problems' => 'multi',
            ],
        ],
        [
            'key' => 'IX', 'title' => 'Service Ratings and Summary', 'icon' => 'chart',
            'fields' => [
                'barangay_service_ratings' => 'rating', 'available_for_training' => 'yesno',
            ],
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Role navigation (ported from docs/prototype/assets/js/layout.js).
    | Items whose route does not exist yet are hidden automatically —
    | later phases add routes and the nav fills in.
    |----------------------------------------------------------------------
    */
    'nav' => [
        'admin' => [
            [
                'section' => 'Overview',
                'items' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'grid'],
                    ['key' => 'analytics', 'label' => 'Analytics', 'route' => 'analytics.index', 'icon' => 'chart'],
                    ['key' => 'calendar', 'label' => 'Calendar', 'route' => 'calendar.index', 'icon' => 'calendar'],
                ],
            ],
            [
                'section' => 'Management',
                'items' => [
                    ['key' => 'faculty-management', 'label' => 'Faculty Management', 'route' => 'faculty.index', 'icon' => 'users'],
                    ['key' => 'programs', 'label' => 'Extension Programs', 'route' => 'programs.index', 'icon' => 'folder'],
                    ['key' => 'communities', 'label' => 'Communities & Partner Schools', 'route' => 'communities.index', 'icon' => 'pin'],
                ],
            ],
            [
                'section' => 'Approvals',
                'items' => [
                    ['key' => 'proposals', 'label' => 'Proposals', 'route' => 'proposals.index', 'icon' => 'doc'],
                    ['key' => 'availability', 'label' => 'Availability Requests', 'route' => 'availability.index', 'icon' => 'clock'],
                    ['key' => 'rendered-hours', 'label' => 'Rendered Hours', 'route' => 'rendered-hours.index', 'icon' => 'clipboard'],
                ],
            ],
            [
                'section' => 'Intelligence & Reports',
                'items' => [
                    ['key' => 'ai-analysis', 'label' => 'AI Analysis Review', 'route' => 'ai-analysis.index', 'icon' => 'sparkles'],
                    ['key' => 'program-narratives', 'label' => 'Program Narratives', 'route' => 'program-narratives.index', 'icon' => 'sparkles'],
                    ['key' => 'reports', 'label' => 'Reports', 'route' => 'reports.index', 'icon' => 'doc'],
                ],
            ],
        ],
        'secretary' => [
            [
                'section' => 'Overview',
                'items' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'grid'],
                    ['key' => 'calendar', 'label' => 'Calendar', 'route' => 'calendar.index', 'icon' => 'calendar'],
                ],
            ],
            [
                'section' => 'Validation',
                'items' => [
                    ['key' => 'assessment-review', 'label' => 'Assessment Review', 'route' => 'assessments.review', 'icon' => 'doc'],
                ],
            ],
            [
                'section' => 'Management',
                'items' => [
                    ['key' => 'beneficiaries', 'label' => 'Manage Beneficiaries', 'route' => 'beneficiaries.index', 'icon' => 'people'],
                ],
            ],
        ],
        'faculty' => [
            [
                'section' => 'Overview',
                'items' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'grid'],
                    ['key' => 'my-programs', 'label' => 'My Programs', 'route' => 'programs.my', 'icon' => 'folder'],
                    ['key' => 'calendar', 'label' => 'Calendar', 'route' => 'calendar.index', 'icon' => 'calendar'],
                ],
            ],
            [
                'section' => 'My Extension Work',
                'items' => [
                    ['key' => 'proposal-new', 'label' => 'Submit Proposal', 'route' => 'proposals.create', 'icon' => 'doc'],
                    ['key' => 'proposals', 'label' => 'My Proposals', 'route' => 'proposals.index', 'icon' => 'folder'],
                    ['key' => 'assessment-form', 'label' => 'Encode Assessment', 'route' => 'assessments.create', 'icon' => 'clipboard'],
                    ['key' => 'availability', 'label' => 'Availability Requests', 'route' => 'availability.index', 'icon' => 'clock'],
                    ['key' => 'rendered-hours', 'label' => 'Rendered Hours', 'route' => 'rendered-hours.my', 'icon' => 'clock'],
                ],
            ],
        ],
    ],
];
