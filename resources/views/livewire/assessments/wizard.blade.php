<div>
@php
    $communityOptions = $communities->mapWithKeys(fn ($c) => [$c->id => $c->name.' · '.$c->municipality])->all();
    $quarterOptions = [1 => 'Q1 · Jan–Mar', 2 => 'Q2 · Apr–Jun', 3 => 'Q3 · Jul–Sep', 4 => 'Q4 · Oct–Dec'];
    $years = range(now()->year - 5, now()->year + 5);
    $yearOptions = array_combine($years, $years);
    $vocabOptions = fn (string $key) => array_combine($vocab[$key], $vocab[$key]);

    // Conditional-rule helpers (blueprint v4.9 §7).
    $show = [
        'areas' => ($form['interested_in_continuing_studies'] ?? '') === 'Yes',
        'benefits' => ($form['has_barangay_health_programs'] ?? '') === 'Yes',
        'programs_benefited' => ($form['has_barangay_health_programs'] ?? '') === 'Yes'
            && ($form['benefits_from_barangay_programs'] ?? '') === 'Yes',
        'toilet_type' => ($form['has_own_toilet'] ?? '') === 'Yes',
        'animals' => ($form['keeps_animals'] ?? '') === 'Yes',
        'appliances' => ($form['has_electricity'] ?? '') === 'Yes',
        'lighting' => ($form['has_electricity'] ?? '') === 'No',
        'org' => ($form['household_members_in_organization'] ?? '') !== 'None'
            && ($form['member_of_organization'] ?? '') === 'Yes',
        'member' => ($form['household_members_in_organization'] ?? '') !== 'None',
        'reason' => ($form['available_for_training'] ?? '') === 'No',
    ];
@endphp
<section class="pt-6">
    <p class="text-[13px] text-gray-400 font-medium mb-3">Encode needs-assessment responses · Sections I–IX · routed to the CESO Secretary for validation</p>
    <div class="reveal-item flex flex-wrap items-center gap-3">
        <a href="{{ route('assessments.template') }}" class="btn btn-outline !py-2 !px-3 text-[12px]"><x-sc.icon name="download" class="w-4 h-4" /> Download official template</a>
        <a href="{{ route('assessments.import') }}" class="btn btn-outline !py-2 !px-3 text-[12px]"><x-sc.icon name="doc" class="w-4 h-4" /> Import from template</a>
    </div>
</section>

<section class="mt-4 reveal-item" x-data="{ s: 1 }">
    {{-- Stepper header --}}
    <div class="sc-card px-6 py-4 flex items-center">
        @foreach ([1 => 'Profile', 2 => 'Livelihood & Education', 3 => 'Health & Housing', 4 => 'Community Life', 5 => 'Review'] as $n => $label)
            @if ($n > 1)
                <div class="step-line mt-[17px]" :class="s > {{ $n - 1 }} ? 'done' : ''"></div>
            @endif
            <div class="flex flex-col items-center gap-1.5 shrink-0 w-[104px]">
                <button type="button" class="step-dot" :class="s > {{ $n }} ? 'done' : (s === {{ $n }} ? 'on' : '')" @click="s = {{ $n }}">
                    <span x-text="s > {{ $n }} ? '✓' : {{ $n }}">{{ $n }}</span>
                </button>
                <span class="text-[10px] font-bold tracking-wide text-center leading-tight" :class="s === {{ $n }} ? 'text-lnu-800' : 'text-gray-400'">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    <form wire:submit="save" class="contents">
        {{-- STEP 1 · PROFILE (Sections I-II) + context --}}
        <div x-cloak x-show="s === 1" class="sc-card p-6 mt-4">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-extrabold text-[15px] tracking-tight">Respondent Profile</h3>
                <span class="badge badge-blue">Instrument Sections I–II</span>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-5">
                <div>
                    <label class="label">Community *</label>
                    <x-sc.select model="communityId" :value="$communityId" :options="$communityOptions" placeholder="— select community —" />
                    @error('communityId') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Quarter</label>
                    <x-sc.select model="quarter" :value="$quarter" :options="$quarterOptions" />
                </div>
                <div>
                    <label class="label">Year</label>
                    <x-sc.select model="year" :value="$year" :options="$yearOptions" placeholder="— year —" />
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="label">First Name *</label>
                    <input class="input" wire:model="form.respondent_first_name"
                           x-on:input="$el.value = $el.value.replace(/[^a-zA-ZÑñ\s\.\-']/g, '')">
                    @error('form.respondent_first_name') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Middle Name</label>
                    <input class="input" wire:model="form.respondent_middle_name"
                           x-on:input="$el.value = $el.value.replace(/[^a-zA-ZÑñ\s\.\-']/g, '')">
                </div>
                <div>
                    <label class="label">Last Name *</label>
                    <input class="input" wire:model="form.respondent_last_name"
                           x-on:input="$el.value = $el.value.replace(/[^a-zA-ZÑñ\s\.\-']/g, '')">
                    @error('form.respondent_last_name') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="label">Age</label>
                    <input type="number" min="15" max="120" class="input" wire:model="form.respondent_age">
                </div>
                <div>
                    <label class="label">Civil Status</label>
                    <x-sc.select model="form.respondent_civil_status" :value="$form['respondent_civil_status'] ?? ''" :options="['' => '—'] + $vocabOptions('respondent_civil_status')" placeholder="—" :search="false" />
                </div>
                <div>
                    <label class="label">Religion</label>
                    <x-sc.select model="form.respondent_religion" :value="$form['respondent_religion'] ?? ''" :options="['' => '—'] + $vocabOptions('respondent_religion')" placeholder="—" />
                </div>
            </div>

            @include('livewire.assessments.partials.field', [
                'field' => 'respondent_sex', 'type' => 'single', 'label' => 'Sex', 'options' => $vocab['respondent_sex'], 'class' => 'mt-5',
            ])
            @include('livewire.assessments.partials.field', [
                'field' => 'respondent_educational_attainment', 'type' => 'single', 'label' => 'Highest Educational Attainment', 'options' => $vocab['respondent_educational_attainment'], 'class' => 'mt-5',
            ])
            @include('livewire.assessments.partials.field', [
                'field' => 'family_composition', 'type' => 'single', 'label' => 'Family Composition', 'options' => $vocab['family_composition'], 'class' => 'mt-5',
            ])
            @include('livewire.assessments.partials.field', [
                'field' => 'household_member_currently_studying', 'type' => 'yesno', 'label' => 'Household Member Currently Studying', 'options' => $vocab['yes_no'], 'class' => 'mt-5',
            ])
            @include('livewire.assessments.partials.field', [
                'field' => 'household_members_in_organization', 'type' => 'single', 'label' => 'Household Members in Organization', 'options' => $vocab['household_members_in_organization'], 'class' => 'mt-5',
            ])
        </div>

        {{-- STEP 2 · LIVELIHOOD & EDUCATION (Sections III-IV) --}}
        <div x-cloak x-show="s === 2" class="sc-card p-6 mt-4">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-extrabold text-[15px] tracking-tight">Livelihood & Education</h3>
                <span class="badge badge-blue">Instrument Sections III–IV</span>
            </div>

            @include('livewire.assessments.partials.field', [
                'field' => 'livelihood_options', 'type' => 'single', 'label' => 'Main Source of Household Livelihood', 'options' => $vocab['livelihood_options'],
            ])
            @include('livewire.assessments.partials.field', [
                'field' => 'desired_training', 'type' => 'single', 'label' => 'Desired Livelihood Training', 'options' => $vocab['desired_training'], 'class' => 'mt-5',
            ])
            @include('livewire.assessments.partials.field', [
                'field' => 'barangay_educational_facilities', 'type' => 'multi', 'label' => 'Barangay Educational Facilities', 'options' => $vocab['barangay_educational_facilities'], 'class' => 'mt-5',
            ])
            @include('livewire.assessments.partials.field', [
                'field' => 'interested_in_continuing_studies', 'type' => 'yesno', 'label' => 'Interested in Continuing Studies?', 'options' => $vocab['yes_no'], 'class' => 'mt-5',
            ])
            @if ($show['areas'])
                @include('livewire.assessments.partials.field', [
                    'field' => 'areas_of_educational_interest', 'type' => 'single', 'label' => 'Area of Educational Interest', 'options' => $vocab['areas_of_educational_interest'], 'class' => 'mt-5',
                ])
            @endif
            @include('livewire.assessments.partials.field', [
                'field' => 'preferred_training_time', 'type' => 'single', 'label' => 'Preferred Training Time', 'options' => $vocab['preferred_training_time'], 'class' => 'mt-5',
            ])
            @include('livewire.assessments.partials.field', [
                'field' => 'preferred_training_days', 'type' => 'multi', 'label' => 'Preferred Training Days', 'options' => $vocab['preferred_training_days'], 'hint' => 'select all that apply — “Flexible” clears the other choices', 'class' => 'mt-5',
            ])
        </div>

        {{-- STEP 3 · HEALTH & HOUSING (Sections V-VI) --}}
        <div x-cloak x-show="s === 3" class="sc-card p-6 mt-4">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-extrabold text-[15px] tracking-tight">Health & Housing</h3>
                <span class="badge badge-blue">Instrument Sections V–VI</span>
            </div>

            @include('livewire.assessments.partials.field', ['field' => 'common_illnesses', 'type' => 'single', 'label' => 'Most Common Illness', 'options' => $vocab['common_illnesses']])
            @include('livewire.assessments.partials.field', ['field' => 'action_when_sick', 'type' => 'single', 'label' => 'Action When Sick', 'options' => $vocab['action_when_sick'], 'class' => 'mt-5'])
            @include('livewire.assessments.partials.field', ['field' => 'barangay_medical_supplies_available', 'type' => 'multi', 'label' => 'Barangay Medical Supplies Available', 'options' => $vocab['barangay_medical_supplies_available'], 'hint' => 'select all that apply — “No supplies” clears the other choices', 'class' => 'mt-5'])
            @include('livewire.assessments.partials.field', ['field' => 'has_barangay_health_programs', 'type' => 'yesno', 'label' => 'Has Barangay Health Programs?', 'options' => $vocab['yes_no'], 'class' => 'mt-5'])
            @if ($show['benefits'])
                @include('livewire.assessments.partials.field', ['field' => 'benefits_from_barangay_programs', 'type' => 'yesno', 'label' => 'Benefits from Barangay Programs?', 'options' => $vocab['yes_no'], 'class' => 'mt-5'])
            @endif
            @if ($show['programs_benefited'])
                @include('livewire.assessments.partials.field', ['field' => 'programs_benefited_from', 'type' => 'multi', 'label' => 'Programs Benefited From', 'options' => $vocab['programs_benefited_from'], 'class' => 'mt-5'])
            @endif
            @include('livewire.assessments.partials.field', ['field' => 'water_source', 'type' => 'single', 'label' => 'Water Source', 'options' => $vocab['water_source'], 'class' => 'mt-5'])
            @include('livewire.assessments.partials.field', ['field' => 'water_source_distance', 'type' => 'single', 'label' => 'Water Source Distance', 'options' => $vocab['water_source_distance'], 'class' => 'mt-5'])
            @include('livewire.assessments.partials.field', ['field' => 'garbage_disposal_method', 'type' => 'single', 'label' => 'Garbage Disposal Method', 'options' => $vocab['garbage_disposal_method'], 'class' => 'mt-5'])
            @include('livewire.assessments.partials.field', ['field' => 'has_own_toilet', 'type' => 'yesno', 'label' => 'Has Own Toilet?', 'options' => $vocab['yes_no'], 'class' => 'mt-5'])
            @if ($show['toilet_type'])
                @include('livewire.assessments.partials.field', ['field' => 'toilet_type', 'type' => 'single', 'label' => 'Toilet Type', 'options' => $vocab['toilet_type'], 'class' => 'mt-5'])
            @endif
            @include('livewire.assessments.partials.field', ['field' => 'keeps_animals', 'type' => 'yesno', 'label' => 'Keeps Animals?', 'options' => $vocab['yes_no'], 'class' => 'mt-5'])
            @if ($show['animals'])
                @include('livewire.assessments.partials.field', ['field' => 'animals_kept', 'type' => 'multi', 'label' => 'Animals Kept', 'options' => $vocab['animals_kept'], 'class' => 'mt-5'])
            @endif
            @include('livewire.assessments.partials.field', ['field' => 'house_type', 'type' => 'single', 'label' => 'House Type', 'options' => $vocab['house_type'], 'class' => 'mt-5'])
            @include('livewire.assessments.partials.field', ['field' => 'tenure_status', 'type' => 'single', 'label' => 'Tenure Status', 'options' => $vocab['tenure_status'], 'class' => 'mt-5'])
            @include('livewire.assessments.partials.field', ['field' => 'has_electricity', 'type' => 'yesno', 'label' => 'Has Electricity?', 'options' => $vocab['yes_no'], 'class' => 'mt-5'])
            @if ($show['appliances'])
                @include('livewire.assessments.partials.field', ['field' => 'appliances_owned', 'type' => 'multi', 'label' => 'Appliances Owned', 'options' => $vocab['appliances_owned'], 'class' => 'mt-5'])
            @endif
            @if ($show['lighting'])
                @include('livewire.assessments.partials.field', ['field' => 'light_source_without_power', 'type' => 'single', 'label' => 'Light Source Without Power', 'options' => $vocab['light_source_without_power'], 'class' => 'mt-5'])
            @endif
        </div>

        {{-- STEP 4 · COMMUNITY LIFE (Sections VII-VIII) --}}
        <div x-cloak x-show="s === 4" class="sc-card p-6 mt-4">
            <div class="flex items-center justify-between mb-5">
                <h3 class="font-extrabold text-[15px] tracking-tight">Community Life</h3>
                <span class="badge badge-blue">Instrument Sections VII–VIII</span>
            </div>

            @include('livewire.assessments.partials.field', ['field' => 'barangay_recreational_facilities', 'type' => 'multi', 'label' => 'Barangay Recreational Facilities', 'options' => $vocab['barangay_recreational_facilities']])
            @include('livewire.assessments.partials.field', ['field' => 'use_of_free_time', 'type' => 'multi', 'label' => 'Use of Free Time', 'options' => $vocab['use_of_free_time'], 'class' => 'mt-5'])

            @if ($show['member'])
                @include('livewire.assessments.partials.field', ['field' => 'member_of_organization', 'type' => 'yesno', 'label' => 'Member of Organization?', 'options' => $vocab['yes_no'], 'class' => 'mt-5'])
            @endif
            @if ($show['org'])
                @include('livewire.assessments.partials.field', ['field' => 'organization_types', 'type' => 'single', 'label' => 'Organization Type', 'options' => $vocab['organization_types'], 'class' => 'mt-5'])
                @include('livewire.assessments.partials.field', ['field' => 'organization_meeting_frequency', 'type' => 'single', 'label' => 'Organization Meeting Frequency', 'options' => $vocab['organization_meeting_frequency'], 'class' => 'mt-5'])
                @include('livewire.assessments.partials.field', ['field' => 'organization_usual_activities', 'type' => 'single', 'label' => 'Organization Usual', 'options' => $vocab['organization_usual_activities'], 'class' => 'mt-5'])
                @include('livewire.assessments.partials.field', ['field' => 'position_in_organization', 'type' => 'single', 'label' => 'Position in Organization', 'options' => $vocab['position_in_organization'], 'class' => 'mt-5'])
            @endif

            <div class="mt-6 pt-4 border-t border-gray-100">
                <p class="font-extrabold text-[14px] mb-4">Problems and Priorities <span class="text-[11px] text-gray-400 font-normal">· select up to 3 per category</span></p>
                @include('livewire.assessments.partials.field', ['field' => 'family_problems', 'type' => 'multi', 'label' => 'Family Problems', 'options' => $vocab['family_problems'], 'hint' => 'select up to 3'])
                @include('livewire.assessments.partials.field', ['field' => 'health_problems', 'type' => 'multi', 'label' => 'Health Problems', 'options' => $vocab['health_problems'], 'hint' => 'select up to 3', 'class' => 'mt-5'])
                @include('livewire.assessments.partials.field', ['field' => 'educational_problems', 'type' => 'multi', 'label' => 'Educational Problems', 'options' => $vocab['educational_problems'], 'hint' => 'select up to 3', 'class' => 'mt-5'])
                @include('livewire.assessments.partials.field', ['field' => 'employment_problems', 'type' => 'multi', 'label' => 'Employment Problems', 'options' => $vocab['employment_problems'], 'hint' => 'select up to 3', 'class' => 'mt-5'])
                @include('livewire.assessments.partials.field', ['field' => 'infrastructure_problems', 'type' => 'multi', 'label' => 'Infrastructure Problems', 'options' => $vocab['infrastructure_problems'], 'hint' => 'select up to 3', 'class' => 'mt-5'])
                @include('livewire.assessments.partials.field', ['field' => 'economic_problems', 'type' => 'multi', 'label' => 'Economic Problems', 'options' => $vocab['economic_problems'], 'hint' => 'select up to 3', 'class' => 'mt-5'])
                @include('livewire.assessments.partials.field', ['field' => 'security_problems', 'type' => 'multi', 'label' => 'Security Problems', 'options' => $vocab['security_problems'], 'hint' => 'select up to 3', 'class' => 'mt-5'])
            </div>
        </div>

        {{-- STEP 5 · REVIEW (Section IX) --}}
        <div x-cloak x-show="s === 5" class="mt-4 grid grid-cols-3 gap-4">
            <div class="sc-card p-6 col-span-2">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-extrabold text-[15px] tracking-tight">Service Ratings & Summary</h3>
                    <span class="badge badge-gold">Instrument Section IX</span>
                </div>

                <div>
                    <p class="text-[12px] font-semibold mb-2">Barangay Service Rating</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($vocab['barangay_service_ratings'] as $rating)
                            <button type="button" wire:click="$set('form.barangay_service_ratings.overall', '{{ $rating }}')"
                                    @class(['chip', 'on' => ($form['barangay_service_ratings']['overall'] ?? '') === $rating])>{{ $rating }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5">
                    <label class="label">General Feedback</label>
                    <textarea rows="3" class="input" wire:model="form.general_feedback" placeholder="Anything else the barangay should know?"></textarea>
                </div>
                @include('livewire.assessments.partials.field', ['field' => 'available_for_training', 'type' => 'yesno', 'label' => 'Available for Training?', 'options' => $vocab['yes_no'], 'class' => 'mt-5'])
                @if ($show['reason'])
                    <div class="mt-5">
                        <p class="text-[12px] font-semibold mb-2">Reason Not Available</p>
                        <div class="chip-group flex flex-wrap gap-2">
                            @foreach ($vocab['reason_not_available'] as $reason)
                                <button type="button" wire:click="$set('form.reason_not_available', '{{ $reason }}')"
                                        @class(['chip', 'on' => ($form['reason_not_available'] ?? '') === $reason])>{{ $reason }}</button>
                            @endforeach
                        </div>
                        @error('form.reason_not_available') <p class="text-[11px] text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>
            <div class="sc-card p-5 space-y-4">
                <div class="border-l-4 border-gold-500 bg-gold-50 rounded-r-xl p-3.5">
                    <p class="text-[12px] font-extrabold text-gold-800">Before you submit</p>
                    <p class="text-[11px] text-gold-700/80 font-medium mt-1">Entries route to the CESO Secretary for validation, then feed the community needs summaries used in reports.</p>
                </div>
                <button type="submit" class="btn btn-primary w-full !justify-center">Submit Assessment</button>
            </div>
        </div>

        {{-- Wizard nav --}}
        <div class="mt-4 flex items-center justify-between no-print">
            <p class="text-[11.5px] text-gray-400 font-medium">Step <span x-text="s">1</span> of 5</p>
            <div class="flex gap-2">
                <button type="button" class="btn btn-outline" :class="s === 1 ? 'opacity-40 pointer-events-none' : ''" @click="s = Math.max(1, s - 1)">← Back</button>
                <button type="button" x-show="s < 5" class="btn btn-primary" @click="s = Math.min(5, s + 1)">Next Step →</button>
                <button type="submit" x-show="s === 5" class="btn btn-primary">Submit Assessment</button>
            </div>
        </div>
    </form>
</section>

<footer class="mt-8 text-center text-[11px] text-gray-300 font-medium no-print">
    SmartCEMES · Community Extension Services Office · Leyte Normal University
</footer>
</div>
