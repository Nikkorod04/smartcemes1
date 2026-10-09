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
    | Revised hierarchy (v4.15 / Phase R1)
    |----------------------------------------------------------------------
    | College → Program → Project → Activity. Programs are the six verbatim
    | CESO thrusts (§3), each tagged with one of the three pillars.
    */
    'pillars' => [
        'social' => 'Social',
        'economic' => 'Economic',
        'environmental' => 'Environmental',
    ],

    'pillar_colors' => [
        'social' => 'blue',
        'economic' => 'gold',
        'environmental' => 'green',
    ],

    'college_statuses' => ['active', 'inactive'],
    'program_statuses' => ['active', 'inactive'],

    /*
    |----------------------------------------------------------------------
    | College seals
    |----------------------------------------------------------------------
    | The official seal a college renders on its card and in its hero. Keys
    | are `colleges.code`; values are paths relative to `public/`.
    |
    | These are 256px derivatives of the authored originals in `public/`
    | (CAS/COE/CME at 2560-3000px, 2.3-3.0 MB each; the Graduate School's
    | `gs.png` at 611px, 300 KB), downscaled and cropped to their alpha
    | bounding box so all four carry the same visual weight — each seal fills
    | 98% of its 256px frame, and the set is ~285 KB instead of ~8.0 MB.
    |
    | The originals' filenames are NOT uniform: the three colleges use
    | `{CODE}.png` while the Graduate School's source is `gs.png`. The
    | derivatives all follow the folder's lowercase-code convention, so this
    | map is keyed by `colleges.code` and never derives a path from a filename.
    |
    | A code with no entry falls back to the code crest in the UI, so a
    | college without a seal still renders.
    */
    'college_logos' => [
        'CAS' => 'img/colleges/cas.png',
        'COE' => 'img/colleges/coe.png',
        'CME' => 'img/colleges/cme.png',
        'GRAD' => 'img/colleges/grad.png',
    ],

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
    | Faculty Management (revision §4.5 / Phase R3)
    |
    | `expertise_options` and `position_ladder` are ported VERBATIM from
    | docs/prototype/assets/js/seed-data.js (expertiseOptions / positionLadder)
    | so the Laravel module and the prototype cannot drift apart.
    |----------------------------------------------------------------------
    */
    'faculty_statuses' => ['active', 'on_leave', 'inactive'],

    /*
    | Canonical expertise vocabulary — powers the profile multi-select and the
    | Faculty Directory expertise filter. Sorted alphabetically (the prototype
    | list is already in this order). New areas are free text at the UI level:
    | the picker is type-to-filter, so this list is a floor, not a ceiling.
    */
    'expertise_options' => [
        'Assessment Design', 'Business Planning', 'Community Organizing', 'Community Wellness',
        'Cultural Heritage', 'Digital Literacy', 'Disaster Preparedness', 'Early Childhood Education',
        'Entrepreneurship', 'Environmental Conservation', 'Financial Literacy', 'Geriatric Care',
        'Health Literacy', 'ICT Training', 'Literacy & Reading', 'Local Governance',
        'Media & Information Literacy', 'Mother-Tongue Pedagogy', 'Numeracy & Math Instruction',
        'Peace & Conflict Resolution', 'Remedial Instruction', 'Solid Waste Management',
        'Special & Inclusive Education', 'Sports Coaching',
    ],

    /*
    | Optional grouping for expertise areas (feeds `faculty_expertise.category`).
    | Areas absent from this map keep a NULL category — which is honest, and the
    | UI shows them under "Other".
    */
    'expertise_categories' => [
        'Literacy' => [
            'Literacy & Reading', 'Remedial Instruction', 'Mother-Tongue Pedagogy',
            'Media & Information Literacy',
        ],
        'Numeracy' => [
            'Numeracy & Math Instruction', 'Assessment Design',
        ],
        'Livelihood' => [
            'Entrepreneurship', 'Business Planning', 'Financial Literacy',
        ],
        'Environment' => [
            'Environmental Conservation', 'Solid Waste Management', 'Disaster Preparedness',
        ],
        'Health & Wellness' => [
            'Health Literacy', 'Community Wellness', 'Geriatric Care',
        ],
        'Technology' => [
            'Digital Literacy', 'ICT Training',
        ],
        'Governance & Community' => [
            'Community Organizing', 'Local Governance', 'Peace & Conflict Resolution',
            'Cultural Heritage',
        ],
        'Education & Sports' => [
            'Early Childhood Education', 'Special & Inclusive Education', 'Sports Coaching',
        ],
    ],

    /*
    | Faculty position ladder — INSTITUTIONAL ORDER, most junior first.
    | `rank` sorts, `label` displays, `group` buckets for the filters.
    */
    'position_ladder' => [
        ['rank' => 1,  'group' => 'Instructor',           'label' => 'Instructor I'],
        ['rank' => 2,  'group' => 'Instructor',           'label' => 'Instructor II'],
        ['rank' => 3,  'group' => 'Instructor',           'label' => 'Instructor III'],
        ['rank' => 4,  'group' => 'Assistant Professor',  'label' => 'Assistant Professor I'],
        ['rank' => 5,  'group' => 'Assistant Professor',  'label' => 'Assistant Professor II'],
        ['rank' => 6,  'group' => 'Assistant Professor',  'label' => 'Assistant Professor III'],
        ['rank' => 7,  'group' => 'Assistant Professor',  'label' => 'Assistant Professor IV'],
        ['rank' => 8,  'group' => 'Associate Professor',  'label' => 'Associate Professor I'],
        ['rank' => 9,  'group' => 'Associate Professor',  'label' => 'Associate Professor II'],
        ['rank' => 10, 'group' => 'Associate Professor',  'label' => 'Associate Professor III'],
        ['rank' => 11, 'group' => 'Associate Professor',  'label' => 'Associate Professor IV'],
        ['rank' => 12, 'group' => 'Associate Professor',  'label' => 'Associate Professor V'],
        ['rank' => 13, 'group' => 'Professor',            'label' => 'Professor I'],
        ['rank' => 14, 'group' => 'Professor',            'label' => 'Professor II'],
        ['rank' => 15, 'group' => 'Professor',            'label' => 'Professor III'],
        ['rank' => 16, 'group' => 'Professor',            'label' => 'Professor IV'],
        ['rank' => 17, 'group' => 'Professor',            'label' => 'Professor V'],
        ['rank' => 18, 'group' => 'Professor',            'label' => 'Professor VI'],
    ],

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
        /*
        | The API key MUST be read through config, never via a runtime `env()`
        | call. `php artisan config:cache` (deploy checklist item 10) stops
        | Laravel loading `.env`, so `env('GEMINI_API_KEY')` evaluated at request
        | time returns null and every AI surface falls into its "unavailable"
        | state with "No API key configured" — while the model id and endpoint,
        | which ARE read through config, keep working. GeminiClient reads
        | `config('smartcemes.ai.key')` first for exactly this reason.
        */
        'key' => env('GEMINI_API_KEY'),
        'endpoint' => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models'),
        'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),
        // v2 (R6, feedback #8/#9): embeds the CESO scope, the Tier-3 prohibition
        // list and the interagency catalogue. v1 is RETAINED in the codebase
        // (`PromptV1`) so pre-R6 analyses stay reproducible from their recorded
        // `metadata.prompt_version`.
        'prompt_version' => 'v2',

        /*
        | Transient-failure retry (2026-10-02).
        |
        | Gemini returns 503 UNAVAILABLE under peak load ("temporarily
        | restricting capacity for preview or flash models") and 429
        | RESOURCE_EXHAUSTED when a quota is hit. Google's documented remedy is
        | exponential backoff with jitter on 408/429/5xx — and explicitly NOT on
        | 4xx, since a bad key, a depleted prepay balance (402) or a retired
        | model id cannot be fixed by asking again.
        |
        | The budget is deliberately small because the whole generation runs
        | inside the web request (dispatchSync), so the Director is waiting.
        | A failure that survives these attempts still lands in the same
        | first-class "Analysis unavailable" state with the same manual Retry.
        */
        'retry' => [
            'max_attempts' => (int) env('GEMINI_RETRY_MAX_ATTEMPTS', 3),
            'base_delay_ms' => (int) env('GEMINI_RETRY_BASE_MS', 1000),
            'max_delay_ms' => (int) env('GEMINI_RETRY_MAX_MS', 8000),
            // Hard wall-clock ceiling for the whole call, retries included.
            'total_budget_ms' => (int) env('GEMINI_RETRY_BUDGET_MS', 120000),
        ],
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
    | Nightly database backup (R7 — absorbed from the retired "Phase 6")
    |----------------------------------------------------------------------
    | `dump_binary` is the mysqldump executable. It is configurable because a
    | shared host or a Windows box (XAMPP/Laragon/WAMP) very often has it
    | OUTSIDE the PATH — the default `mysqldump` then fails at 02:00 with
    | "'mysqldump' is not recognized", and a backup that silently stops
    | happening is worse than none. Override with DB_DUMP_BINARY in `.env`.
    |
    | `directory` defaults to storage/app/backups. `keep` is the retention
    | count; the scheduled run uses it unless --keep is passed.
    */
    'backup' => [
        'dump_binary' => env('DB_DUMP_BINARY', 'mysqldump'),
        'directory' => env('DB_BACKUP_DIR'),
        'keep' => (int) env('DB_BACKUP_KEEP', 14),
    ],

    /*
    |----------------------------------------------------------------------
    | Role navigation (ported from docs/prototype/assets/js/layout.js).
    |
    | Items whose route does not exist yet are hidden automatically — later
    | phases add routes and the nav fills in.
    |
    | COLLAPSED HIERARCHY (prototype PATTERNS v4.3 / revisions.md §11.4):
    | the admin sidebar exposes exactly ONE extension-structure entry,
    | "Manage Extension Programs". `Colleges`, `Extension Programs` and
    | `Extension Projects` must NOT appear as separate items — the whole
    | College → Program → Project → Activity chain is navigated from inside
    | the hub. `subs[]` lists the route names that keep that single entry
    | highlighted while the user is anywhere in the chain, and that
    | `RouteSurfaceTest` still walks for a 5xx (they are no longer nav items,
    | so nothing else would cover them).
    | `tests/Feature/CollegeProgramCrudTest` asserts this rule, so it cannot
    | silently regress (the prototype asserts the same in `_check.cjs:196-210`).
    |----------------------------------------------------------------------
    */
    'nav' => [
        'admin' => [
            [
                'section' => 'Overview',
                'items' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'grid'],
                    ['key' => 'targets', 'label' => 'University Targets', 'route' => 'targets.index', 'icon' => 'shield'],
                    ['key' => 'calendar', 'label' => 'Calendar', 'route' => 'calendar.index', 'icon' => 'calendar'],
                ],
            ],
            [
                'section' => 'Extension Programs',
                'items' => [
                    [
                        'key' => 'manage-programs',
                        'label' => 'Manage Extension Programs',
                        'route' => 'colleges.index',
                        'icon' => 'folder',
                        /* `subs` is a HIGHLIGHT list, not a navigation list — it
                           decides which sidebar entry stays lit, and creates no
                           link. It deliberately still names all four routes even
                           though /programs and /projects are no longer promoted
                           anywhere in the UI (§23): the New project action lands
                           the Director on /projects, and without `projects.index`
                           here the sidebar would show nothing highlighted on the
                           very page they just arrived at. */
                        'subs' => ['colleges.index', 'programs.index', 'projects.index', 'projects.show'],
                    ],
                ],
            ],
            [
                'section' => 'Management',
                'items' => [
                    [
                        'key' => 'faculty-management',
                        'label' => 'Faculty Management',
                        'route' => 'faculty.index',
                        'icon' => 'users',
                        'subs' => ['faculty.directory'],
                    ],
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
                    ['key' => 'program-narratives', 'label' => 'Project Narratives', 'route' => 'program-narratives.index', 'icon' => 'sparkles'],
                    ['key' => 'interagency', 'label' => 'Interagency Catalogue', 'route' => 'interagency.index', 'icon' => 'shield'],
                    ['key' => 'reports', 'label' => 'Reports', 'route' => 'reports.index', 'icon' => 'doc'],
                    // Audit Logs (owner request 2026-09-25) — moved off the dashboard,
                    // where it was a four-row panel, into a page of its own.
                    ['key' => 'audit-logs', 'label' => 'Audit Logs', 'route' => 'audit-logs.index', 'icon' => 'list'],
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
                    ['key' => 'my-projects', 'label' => 'My Projects', 'route' => 'projects.my', 'icon' => 'folder'],
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
            [
                /* Its own section, mirroring the prototype's faculty nav
                   (`layout.js`, section "My Profile"). Faculty maintain their
                   own contact details, academic details and expertise here;
                   assignment fields (position, college, status) and the login
                   account stay admin-controlled.

                   The route is `faculty.me` — a PARAMETERLESS route, because
                   `App\Support\Navigation` resolves nav route names with no
                   parameters and `faculty.show` needs a `{faculty}` id. */
                'section' => 'My Profile',
                'items' => [
                    ['key' => 'my-faculty-profile', 'label' => 'My Faculty Profile', 'route' => 'faculty.me', 'icon' => 'users'],
                ],
            ],
        ],
    ],
];
