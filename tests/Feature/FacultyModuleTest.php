<?php

namespace Tests\Feature;

use App\Livewire\Faculty\Directory;
use App\Livewire\Faculty\EngagementBoard;
use App\Livewire\Faculty\Profile;
use App\Models\Activity;
use App\Models\Attendance;
use App\Models\Beneficiary;
use App\Models\College;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\RenderedHours;
use App\Models\User;
use App\Services\FacultyContributionService;
use App\Services\TrainingHoursService;
use Database\Seeders\CollegeSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase R3b — the Faculty Management module (revision §5 R3 steps 2–4).
 *
 * Two things matter most here:
 *
 * 1. THE ROUTE NOW EXISTS. `faculty.index` was a phantom nav entry before R3 —
 *    config pointed at a route that did not exist, so the sidebar item was
 *    silently hidden. These tests pin that the route resolves, because a
 *    regression would hide the module again without any visible error.
 *
 * 2. ACCESS. The module is Director-facing (§6), so Admin-only for the board
 *    and directory; a faculty member may reach their OWN profile and nobody
 *    else's. That boundary is the one worth testing.
 */
class FacultyModuleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function facultyUser(Faculty $faculty): User
    {
        return $faculty->user;
    }

    /* ---------------------------------------------------------------------
     | Routes & access
     | ------------------------------------------------------------------ */

    public function test_faculty_routes_now_exist(): void
    {
        // The phantom-entry regression guard.
        $this->assertNotNull(route('faculty.index'));
        $this->assertNotNull(route('faculty.directory'));

        $this->assertSame(url('/faculty'), route('faculty.index'));
        $this->assertSame(url('/faculty/directory'), route('faculty.directory'));
    }

    /**
     * D-R7: the faculty's OWN rendered-hours page must not name the retired 8.6 KPI
     * dictionary, and must not imply a per-professor target.
     *
     * Both leaked here until 2026-09-28 and nothing caught them: every D-R7 guard was
     * scoped to PROJECT-level surfaces (the project hub, the ranking service), and this
     * page had NO test at all. The tile read "count toward the 8.6 KPI" — a metric D-R7
     * retired — and the prototype's copy of the page still carried a hardcoded
     * "Target 40 hrs / semester" attainment bar that P0h had removed everywhere else.
     *
     * Faculty figures are CONTRIBUTION, never attainment: there is no per-professor
     * target, so a percentage here would be invented.
     */
    public function test_the_faculty_rendered_hours_page_names_no_retired_kpi_or_target(): void
    {
        $faculty = Faculty::factory()->create();

        $this->actingAs($faculty->user)
            ->get(route('rendered-hours.my'))
            ->assertOk()
            ->assertSee('count toward your faculty contribution')
            ->assertDontSee('8.6 KPI')
            ->assertDontSee('KPI dictionary')
            ->assertDontSee('attainment')
            ->assertDontSee('Target 40');
    }

    public function test_admin_can_open_the_engagement_board(): void
    {
        $this->actingAs($this->admin())
            ->get(route('faculty.index'))
            ->assertOk()
            ->assertSee('Faculty Engagement');
    }

    public function test_admin_can_open_the_directory(): void
    {
        $this->actingAs($this->admin())
            ->get(route('faculty.directory'))
            ->assertOk()
            ->assertSee('Faculty Directory');
    }

    public function test_the_route_order_keeps_directory_out_of_the_wildcard(): void
    {
        // `/faculty/directory` must not be captured by `/faculty/{faculty}` —
        // a route-model-binding 404 would be the failure mode.
        $this->actingAs($this->admin())
            ->get('/faculty/directory')
            ->assertOk();
    }

    public function test_faculty_cannot_open_the_board(): void
    {
        $faculty = Faculty::factory()->create();

        $this->actingAs($this->facultyUser($faculty))
            ->get(route('faculty.index'))
            ->assertForbidden();
    }

    public function test_faculty_cannot_open_the_directory(): void
    {
        $faculty = Faculty::factory()->create();

        $this->actingAs($this->facultyUser($faculty))
            ->get(route('faculty.directory'))
            ->assertForbidden();
    }

    public function test_secretary_has_no_faculty_management_access(): void
    {
        // Separation of duties: the Secretary validates assessments and manages
        // beneficiaries, but does not touch the faculty roster.
        $secretary = User::factory()->create(['role' => User::ROLE_SECRETARY]);

        $this->actingAs($secretary)->get(route('faculty.index'))->assertForbidden();
        $this->actingAs($secretary)->get(route('faculty.directory'))->assertForbidden();
    }

    public function test_faculty_can_view_their_own_profile(): void
    {
        $faculty = Faculty::factory()->create();

        $this->actingAs($this->facultyUser($faculty))
            ->get(route('faculty.show', ['faculty' => $faculty]))
            ->assertOk()
            ->assertSee($faculty->user->name);
    }

    public function test_faculty_cannot_view_a_colleagues_profile(): void
    {
        $mine = Faculty::factory()->create();
        $theirs = Faculty::factory()->create();

        $this->actingAs($this->facultyUser($mine))
            ->get(route('faculty.show', ['faculty' => $theirs]))
            ->assertForbidden();
    }

    public function test_admin_can_view_any_profile(): void
    {
        $faculty = Faculty::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('faculty.show', ['faculty' => $faculty]))
            ->assertOk();
    }

    /* ---------------------------------------------------------------------
     | The nav entry is no longer phantom
     | ------------------------------------------------------------------ */

    public function test_the_faculty_management_nav_entry_renders_for_admin(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(CollegeSeeder::class);

        $this->actingAs(User::where('email', 'admin@lnu.com')->firstOrFail())
            ->get(route('faculty.index'))
            ->assertOk()
            ->assertSee('Faculty Management');
    }

    /* ---------------------------------------------------------------------
     | Board behaviour
     | ------------------------------------------------------------------ */

    public function test_the_board_defaults_to_the_hours_metric(): void
    {
        Livewire::actingAs($this->admin())
            ->test(EngagementBoard::class)
            ->assertSet('metric', 'hours');
    }

    public function test_the_board_switches_metric(): void
    {
        Livewire::actingAs($this->admin())
            ->test(EngagementBoard::class)
            ->call('setMetric', 'leads')
            ->assertSet('metric', 'leads');
    }

    public function test_the_board_rejects_an_unknown_metric(): void
    {
        Livewire::actingAs($this->admin())
            ->test(EngagementBoard::class)
            ->call('setMetric', 'nonsense')
            ->assertSet('metric', 'hours');
    }

    public function test_the_board_accepts_a_valid_college_deep_link(): void
    {
        $this->seed(CollegeSeeder::class);

        Livewire::actingAs($this->admin())
            ->test(EngagementBoard::class, ['collegeScope' => 'CAS'])
            ->assertSet('collegeScope', 'CAS');
    }

    public function test_the_board_ignores_an_invalid_college_deep_link(): void
    {
        $this->seed(CollegeSeeder::class);

        // An unknown code must not produce a silently-empty board.
        Livewire::actingAs($this->admin())
            ->test(EngagementBoard::class, ['collegeScope' => 'XXX'])
            ->assertSet('collegeScope', null);
    }

    public function test_opening_a_faculty_drawer_sets_the_selection(): void
    {
        $faculty = Faculty::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(EngagementBoard::class)
            ->call('openFaculty', $faculty->id)
            ->assertSet('openFacultyId', $faculty->id);
    }

    /**
     * The leaderboard value carries its UNIT (owner request 2026-10-07).
     *
     * Before this the row printed a bare number on every tab, and the unit
     * appeared only in the CAPTION of the other two metrics — so the hours tab
     * was the one tab whose number said nothing about what it counted.
     */
    public function test_the_leaderboard_value_carries_its_unit(): void
    {
        $component = Livewire::actingAs($this->admin())->test(EngagementBoard::class)->instance();
        $definitions = app(FacultyContributionService::class)->metricDefinitions();

        // Hours keep a FIXED label: "hrs rendered" is the house term used by the
        // hub tile, the caption and the chart tooltip, so it must not
        // singularise to "1 hr rendered" here.
        $this->assertSame('hrs rendered', $component->headlineUnit(['rendered_hours' => 21.0], $definitions['hours']));
        $this->assertSame(
            'hrs rendered',
            $component->headlineUnit(['rendered_hours' => 1.0], $definitions['hours']),
            'The hours label is fixed and must not singularise.'
        );

        // The countable metrics do singularise.
        $this->assertSame('Project', $component->headlineUnit(['projects_involved' => 1], $definitions['projects']));
        $this->assertSame('Projects', $component->headlineUnit(['projects_involved' => 4], $definitions['projects']));
        $this->assertSame('Project led', $component->headlineUnit(['projects_led' => 1], $definitions['leads']));
        $this->assertSame('Projects led', $component->headlineUnit(['projects_led' => 4], $definitions['leads']));
    }

    /**
     * The sub-line under the name carries the ACTIVITY count.
     *
     * It used to carry the project count, which duplicated the caption on the
     * hours tab and the value itself on the projects tab. A row now reads
     * hours / projects / activities with no figure printed twice.
     */
    public function test_the_row_subline_carries_the_activity_count_not_the_project_count(): void
    {
        $component = Livewire::actingAs($this->admin())->test(EngagementBoard::class)->instance();

        $row = [
            'college' => 'CAS',
            'status' => 'active',
            'activities_handled' => 4,
            'projects_involved' => 2,
            'projects_led' => 1,
        ];

        $this->assertSame('CAS · 4 activities', $component->rowSubline($row));
        $this->assertSame(
            'COE · 1 activity',
            $component->rowSubline(['college' => 'COE', 'status' => 'active', 'activities_handled' => 1])
        );

        // The whole point of the trim: the project count must NOT reappear.
        $this->assertStringNotContainsString(
            'project',
            strtolower($component->rowSubline($row)),
            'The project count is already the caption (hours tab) or the value (projects tab).'
        );

        // On leave still replaces the figure, as it did before.
        $this->assertSame(
            'GRAD · On leave',
            $component->rowSubline(['college' => 'GRAD', 'status' => 'on_leave', 'activities_handled' => 4])
        );
    }

    /**
     * The rendered row, not just the helpers — and the drawer badges, which
     * used to print "2 lead" and "1 activities".
     *
     * `assertSeeHtml` is deliberate: "hrs rendered" also appears in the chart
     * bootstrap script, so a plain assertSee would pass even with the unit
     * removed from the row (a test passing for the wrong reason).
     */
    public function test_the_rendered_board_labels_the_value_and_pluralises_its_badges(): void
    {
        $project = $this->project();
        $faculty = Faculty::factory()->create();

        // Exactly one activity, so both the sub-line and the drawer badge are in
        // their SINGULAR form — where the old copy read "1 activities".
        $this->attachActivity($faculty, $project, 'Unit Label Session', '2026-03-01', 1.0, 10, 1);

        Livewire::actingAs($this->admin())
            ->test(EngagementBoard::class)
            ->assertSeeHtml('<small>hrs rendered</small>')
            ->assertSee('· 1 activity')
            ->assertDontSee('1 activities')
            ->call('openFaculty', $faculty->id)
            ->assertSee('1 activity')
            ->assertSee('0 Projects led');
    }

    public function test_a_faculty_member_cannot_reach_the_board_at_all(): void
    {
        // Defence in depth. Two layers are refused here:
        //   1. The HTTP route (role:admin) -- asserted below.
        //   2. The mount() Gate::authorize, which runs BEFORE a Livewire
        //      snapshot can be taken. That is why this is an HTTP assertion and
        //      not `Livewire::test(...)->call(...)`: the component never mounts
        //      for a non-admin, so there is no snapshot to act on. (An earlier
        //      version of this test used Livewire::test and failed with
        //      "Invalid Livewire snapshot structure" -- the mount had already
        //      403'd.)
        $mine = Faculty::factory()->create();
        $theirs = Faculty::factory()->create();

        $this->actingAs($this->facultyUser($mine))
            ->get(route('faculty.index'))
            ->assertForbidden();

        // And the action that would open a colleague is unreachable for the
        // same reason -- there is no admin session to invoke it from.
        $this->actingAs($this->facultyUser($mine))
            ->get(route('faculty.show', ['faculty' => $theirs]))
            ->assertForbidden();
    }

    /* ---------------------------------------------------------------------
     | Directory behaviour
     | ------------------------------------------------------------------ */

    public function test_the_directory_lists_faculty(): void
    {
        $faculty = Faculty::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(Directory::class)
            ->assertSee($faculty->user->name);
    }

    public function test_the_directory_filters_by_college(): void
    {
        $cas = College::factory()->create(['code' => 'CAS']);
        $coe = College::factory()->create(['code' => 'COE']);

        $inCas = Faculty::factory()->create(['college_id' => $cas->id]);
        $inCoe = Faculty::factory()->create(['college_id' => $coe->id]);

        Livewire::actingAs($this->admin())
            ->test(Directory::class)
            ->set('collegeFilter', 'CAS')
            ->assertSee($inCas->user->name)
            ->assertDontSee($inCoe->user->name);
    }

    public function test_the_directory_filters_by_expertise(): void
    {
        $literacy = Faculty::factory()->create();
        $literacy->syncExpertise(['Literacy & Reading']);

        $sports = Faculty::factory()->create();
        $sports->syncExpertise(['Sports Coaching']);

        Livewire::actingAs($this->admin())
            ->test(Directory::class)
            ->set('expertiseFilter', 'Literacy & Reading')
            ->assertSee($literacy->user->name)
            ->assertDontSee($sports->user->name);
    }

    public function test_the_directory_filters_by_status(): void
    {
        $active = Faculty::factory()->create();
        $onLeave = Faculty::factory()->onLeave()->create();

        Livewire::actingAs($this->admin())
            ->test(Directory::class)
            ->set('statusFilter', Faculty::STATUS_ON_LEAVE)
            ->assertSee($onLeave->user->name)
            ->assertDontSee($active->user->name);
    }

    public function test_the_directory_searches_by_name_and_employee_id(): void
    {
        $target = Faculty::factory()->create([
            'employee_id' => 'LNU-2026-9999',
        ]);

        $other = Faculty::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(Directory::class)
            ->set('search', 'LNU-2026-9999')
            ->assertSee($target->user->name)
            ->assertDontSee($other->user->name);
    }

    public function test_the_directory_clears_filters(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Directory::class)
            ->set('search', 'zzz')
            ->set('collegeFilter', 'CAS')
            ->set('statusFilter', Faculty::STATUS_ON_LEAVE)
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('collegeFilter', '')
            ->assertSet('statusFilter', '');
    }

    /* ---------------------------------------------------------------------
     | Profile create / edit
     | ------------------------------------------------------------------ */

    public function test_admin_creates_a_faculty_profile_with_expertise(): void
    {
        $college = College::factory()->create(['code' => 'CAS']);

        Livewire::actingAs($this->admin())
            ->test(Directory::class)
            ->call('create')
            ->set('form.name', 'New Instructor')
            ->set('form.email', 'new.instructor@lnu.com')
            ->set('form.college_id', (string) $college->id)
            ->set('form.department', 'College of Arts and Sciences')
            ->set('form.specialization', 'Biology')
            ->set('form.position', 'Instructor I')
            ->set('form.status', Faculty::STATUS_ACTIVE)
            ->call('toggleExpertise', 'faculty-expertise', 'Health Literacy')
            ->call('save')
            ->assertHasNoErrors();

        $faculty = Faculty::whereHas('user', fn ($q) => $q->where('email', 'new.instructor@lnu.com'))
            ->firstOrFail();

        $this->assertSame($college->id, $faculty->college_id);
        $this->assertSame(['Health Literacy'], $faculty->expertise->pluck('area')->all());

        // Employee ID is auto-generated when left blank.
        $this->assertMatchesRegularExpression('/^LNU-\d{4}-\d{4}$/', $faculty->employee_id);
    }

    public function test_the_profile_form_requires_a_name_and_email(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Directory::class)
            ->call('create')
            ->set('form.name', '')
            ->set('form.email', '')
            ->call('save')
            ->assertHasErrors(['form.name' => 'required', 'form.email' => 'required']);
    }

    public function test_the_profile_form_rejects_a_duplicate_email(): void
    {
        $existing = Faculty::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(Directory::class)
            ->call('create')
            ->set('form.name', 'Duplicate')
            ->set('form.email', $existing->user->email)
            ->call('save')
            ->assertHasErrors(['form.email' => 'unique']);
    }

    public function test_editing_a_profile_updates_it(): void
    {
        $faculty = Faculty::factory()->create(['position' => 'Instructor I']);

        Livewire::actingAs($this->admin())
            ->test(Directory::class)
            ->call('edit', $faculty->id)
            ->set('form.position', 'Associate Professor II')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Associate Professor II', $faculty->fresh()->position);
    }

    public function test_editing_keeps_the_same_user_row_and_its_own_email(): void
    {
        // A unique-rule that forgot `ignore()` would reject the user's own
        // unchanged email — a classic save-blocker.
        $faculty = Faculty::factory()->create();
        $userId = $faculty->user_id;

        Livewire::actingAs($this->admin())
            ->test(Directory::class)
            ->call('edit', $faculty->id)
            ->set('form.specialization', 'Updated Specialization')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($userId, $faculty->fresh()->user_id);
    }

    public function test_admin_deactivates_a_profile_softly(): void
    {
        $faculty = Faculty::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(Directory::class)
            ->call('deactivate', $faculty->id);

        // Soft, so historical contribution survives for reporting.
        $this->assertSoftDeleted('faculties', ['id' => $faculty->id]);
    }

    /* ---------------------------------------------------------------------
     | The self-service profile edit boundary
     |
     | WIDENED 2026-09-27 (owner decision): a faculty member now maintains their
     | own academic + contact details and their expertise areas, where before it
     | was contact-only. Employee ID, college, position, status and the login
     | account stay Director-only.
     |
     | The writable set is enforced SERVER-SIDE, so these tests POST the
     | institutional keys and prove they are ignored — rather than trusting that
     | the form happens to omit them. That is the difference between a rule and
     | a hidden input.
     | ------------------------------------------------------------------ */

    public function test_faculty_can_edit_their_own_academic_and_contact_details(): void
    {
        $faculty = Faculty::factory()->create([
            'phone' => '0900 000 0000',
            'specialization' => 'Old specialization',
        ]);

        Livewire::actingAs($this->facultyUser($faculty))
            ->test(Profile::class, ['faculty' => $faculty])
            ->call('editProfile')
            ->set('editForm.phone', '0917 111 2222')
            ->set('editForm.address', 'Brgy. San Jose, Tacloban City')
            ->set('editForm.specialization', 'Reading Education')
            ->set('editForm.department', 'College of Education')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $fresh = $faculty->fresh();

        $this->assertSame('0917 111 2222', $fresh->phone);
        $this->assertSame('Brgy. San Jose, Tacloban City', $fresh->address);
        $this->assertSame('Reading Education', $fresh->specialization);
        $this->assertSame('College of Education', $fresh->department);
    }

    public function test_faculty_can_edit_their_own_expertise(): void
    {
        $faculty = Faculty::factory()->create();

        // The (key, area) signature is the multi-select component's contract —
        // see Profile::toggleExpertise() for why the key must be declared.
        Livewire::actingAs($this->facultyUser($faculty))
            ->test(Profile::class, ['faculty' => $faculty])
            ->call('editProfile')
            ->call('toggleExpertise', 'profile-expertise', 'Literacy & Reading')
            ->call('toggleExpertise', 'profile-expertise', 'Geriatric Care')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $expertise = $faculty->fresh()->expertise;

        $this->assertSame(
            ['Geriatric Care', 'Literacy & Reading'],
            $expertise->pluck('area')->all(),
            'The expertise relation is ordered by area.'
        );

        // The category comes from the SHARED config map, so a self-tagged area
        // groups the same way the Directory's expertise filter expects.
        $this->assertSame(
            'Health & Wellness',
            $expertise->firstWhere('area', 'Geriatric Care')->category
        );
    }

    public function test_toggling_an_expertise_area_off_removes_it(): void
    {
        $faculty = Faculty::factory()->create();
        $faculty->syncExpertise(['Geriatric Care', 'Health Literacy']);

        Livewire::actingAs($this->facultyUser($faculty))
            ->test(Profile::class, ['faculty' => $faculty])
            ->call('editProfile')
            ->call('toggleExpertise', 'profile-expertise', 'Geriatric Care')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $this->assertSame(['Health Literacy'], $faculty->fresh()->expertise->pluck('area')->all());
    }

    public function test_the_self_edit_cannot_touch_institutional_fields(): void
    {
        // The rule is enforced server-side, not by hiding inputs. Posting the
        // whole institutional set is the strongest available probe: none of
        // these keys has an entry in `$editForm`, so `saveProfile()` has no
        // writable path to them.
        $faculty = Faculty::factory()->create([
            'employee_id' => 'LNU-2026-0001',
            'position' => 'Instructor I',
            'status' => Faculty::STATUS_ACTIVE,
        ]);

        $college = College::factory()->create();

        $before = [
            'employee_id' => $faculty->employee_id,
            'position' => $faculty->position,
            'status' => $faculty->status,
            'college_id' => $faculty->college_id,
            'name' => $faculty->user->name,
            'email' => $faculty->user->email,
        ];

        Livewire::actingAs($this->facultyUser($faculty))
            ->test(Profile::class, ['faculty' => $faculty])
            ->call('editProfile')
            ->set('editForm', [
                // The five writable fields, so the save really runs…
                'specialization' => 'Kept',
                'department' => 'Kept',
                'phone' => '0917 000 0000',
                'address' => 'Kept',
                'expertise' => [],
                // …alongside every institutional key.
                'employee_id' => 'LNU-2026-9999',
                'position' => 'Professor I',
                'status' => Faculty::STATUS_INACTIVE,
                'college_id' => $college->id,
                'name' => 'Impostor',
                'email' => 'impostor@lnu.com',
            ])
            ->call('saveProfile')
            ->assertHasNoErrors();

        $fresh = $faculty->fresh();

        $this->assertSame($before['employee_id'], $fresh->employee_id);
        $this->assertSame($before['position'], $fresh->position);
        $this->assertSame($before['status'], $fresh->status);
        $this->assertSame($before['college_id'], $fresh->college_id);
        $this->assertSame($before['name'], $fresh->user->fresh()->name);
        $this->assertSame($before['email'], $fresh->user->fresh()->email);

        // …and the writable field DID save, so the assertions above are not
        // passing merely because nothing ran.
        $this->assertSame('Kept', $fresh->specialization);
    }

    public function test_the_self_edit_writes_an_activity_log_entry(): void
    {
        $faculty = Faculty::factory()->create();

        Livewire::actingAs($this->facultyUser($faculty))
            ->test(Profile::class, ['faculty' => $faculty])
            ->call('editProfile')
            ->set('editForm.specialization', 'Environmental Science')
            ->call('saveProfile');

        $this->assertSame(
            1,
            DB::table('activity_log')
                ->where('event', 'faculty_self_update')
                ->where('subject_type', Faculty::class)
                ->where('subject_id', $faculty->id)
                ->count(),
            'A self-edit is logged so the Director can see it without gating it.'
        );
    }

    public function test_saving_an_unchanged_profile_writes_no_activity_log_entry(): void
    {
        // The other half of the pair: the log records CHANGES, not clicks. A
        // save that alters nothing must stay silent or the audit trail fills
        // with noise.
        $faculty = Faculty::factory()->create();

        Livewire::actingAs($this->facultyUser($faculty))
            ->test(Profile::class, ['faculty' => $faculty])
            ->call('editProfile')   // seeds the form from the current values
            ->call('saveProfile')
            ->assertHasNoErrors();

        $this->assertSame(
            0,
            DB::table('activity_log')->where('event', 'faculty_self_update')->count()
        );
    }

    /* ---------------------------------------------------------------------
     | /my-profile — the faculty sidebar's entry point
     ---------------------------------------------------------------------- */

    public function test_my_profile_renders_the_authenticated_users_own_record(): void
    {
        // The gap this closes: before 2026-09-27 the page existed and was
        // correctly scoped, but NOTHING linked to it — the faculty sidebar had
        // no item and the only way in was to type the URL.
        $faculty = Faculty::factory()->create(['employee_id' => 'LNU-2026-0042']);
        $user = $this->facultyUser($faculty);

        $this->actingAs($user)
            ->get(route('faculty.me'))
            ->assertOk()
            ->assertSee('LNU-2026-0042')
            ->assertSee($user->name);
    }

    /**
     * A role that cannot reach a route must not be shown a link to it.
     *
     * The profile view is served by BOTH `faculty.show` (`auth` only — the Director's
     * route) and `faculty.me` (faculty). Its breadcrumb pointed at `faculty.index` and
     * `faculty.directory`, both admin-only — so a faculty member on `/my-profile` got
     * two breadcrumb links that 403'd. The trail exists to orient the Director, so it
     * is now admin-only.
     */
    public function test_the_profile_breadcrumb_is_admin_only(): void
    {
        $faculty = Faculty::factory()->create();
        $directory = route('faculty.directory');

        // The Director arrives from the Directory and keeps the trail.
        $this->actingAs($this->admin())
            ->get(route('faculty.show', ['faculty' => $faculty]))
            ->assertOk()
            ->assertSee($directory);

        // A faculty member's own profile must not offer it.
        $this->actingAs($this->facultyUser($faculty))
            ->get(route('faculty.me'))
            ->assertOk()
            ->assertDontSee($directory);
    }

    public function test_my_profile_resolves_the_right_faculty_member(): void
    {
        // It must resolve the AUTHENTICATED user's row, not the first one — the
        // classic "wrong member" failure this codebase has been bitten by.
        $first = Faculty::factory()->create(['employee_id' => 'LNU-2026-0001']);
        $second = Faculty::factory()->create(['employee_id' => 'LNU-2026-0002']);

        $this->actingAs($this->facultyUser($second))
            ->get(route('faculty.me'))
            ->assertOk()
            ->assertSee('LNU-2026-0002')
            ->assertDontSee('LNU-2026-0001');

        $this->assertNotSame($first->id, $second->id);
    }

    public function test_my_profile_is_faculty_only(): void
    {
        // A guest never reaches it. Asserted FIRST on purpose: `actingAs()`
        // persists for the rest of the test, so a guest check placed after one
        // is silently still authenticated.
        $this->get(route('faculty.me'))->assertRedirect(route('login'));

        // An Admin has no `faculties` row, so the page is meaningless for them;
        // they reach profiles through the Directory instead.
        $this->actingAs($this->admin())->get(route('faculty.me'))->assertForbidden();

        $secretary = User::factory()->create(['role' => User::ROLE_SECRETARY]);
        $this->actingAs($secretary)->get(route('faculty.me'))->assertForbidden();
    }

    public function test_the_my_profile_nav_item_is_registered(): void
    {
        // The nav silently hides an item whose route does not exist, so a
        // rename here would make the entry vanish with no visible error.
        // Label, icon and section all mirror the prototype's faculty nav
        // (`layout.js`, section "My Profile").
        $sections = collect(config('smartcemes.nav')['faculty']);

        $section = $sections->firstWhere('section', 'My Profile');
        $this->assertNotNull($section, 'The faculty nav must carry a "My Profile" section.');

        $entry = collect($section['items'])->firstWhere('key', 'my-faculty-profile');

        $this->assertNotNull($entry, 'The section must carry the My Faculty Profile item.');
        $this->assertSame('My Faculty Profile', $entry['label']);
        $this->assertSame('faculty.me', $entry['route']);
        $this->assertSame('users', $entry['icon']);
        $this->assertNotNull(route($entry['route']), 'The nav item must point at a real route.');
    }

    public function test_a_faculty_member_cannot_edit_a_colleagues_profile(): void
    {
        $mine = Faculty::factory()->create();
        $theirs = Faculty::factory()->create([
            'phone' => '0900 000 0000',
            'specialization' => 'Not mine',
        ]);

        // mount() authorization refuses before any Livewire snapshot exists, so
        // this is asserted over HTTP rather than via Livewire::test().
        $this->actingAs($this->facultyUser($mine))
            ->get(route('faculty.show', ['faculty' => $theirs]))
            ->assertForbidden();

        // The colleague's data is untouched.
        $fresh = $theirs->fresh();
        $this->assertSame('0900 000 0000', $fresh->phone);
        $this->assertSame('Not mine', $fresh->specialization);
    }

    /* ---------------------------------------------------------------------
     | The R4 dependency — now SATISFIED (§14.7)
     ---------------------------------------------------------------------- */

    public function test_the_profile_states_the_training_hours_formula_after_r4(): void
    {
        // R4 shipped `activities.no_of_days` and TrainingHoursService, so the
        // page no longer says "not yet measurable" — it states the formula the
        // numbers come from. The "say so rather than print 0" principle is
        // unchanged: with no activities carrying days, the figure is still NULL
        // (see the null-not-zero test below), never a fabricated 0.
        $faculty = Faculty::factory()->create();

        Livewire::actingAs($this->admin())
            ->test(Profile::class, ['faculty' => $faculty])
            ->assertSee('Training contribution')
            ->assertDontSee('Not yet measurable');
    }

    public function test_the_service_reports_training_as_measurable_after_r4(): void
    {
        $service = app(FacultyContributionService::class);

        // `activities.no_of_days` arrived in R4 (§4.4). The column is the
        // trigger, so this assertion is the switch that R3c was waiting on.
        $this->assertTrue(
            $service->trainingIsMeasurable(),
            'trainingIsMeasurable() should be true now that R4 added activities.no_of_days.'
        );
    }

    public function test_the_service_reports_null_not_zero_for_training(): void
    {
        $faculty = Faculty::factory()->create();
        $record = app(FacultyContributionService::class)
            ->forFaculty(collect([$faculty]))
            ->first();

        // NULL = "not measured". Zero would be a claim the system cannot support.
        $this->assertNull($record['training_hours_delivered']);
        $this->assertNull($record['training_sessions']);
        $this->assertNull($record['trainees_reached']);
    }

    /* ---------------------------------------------------------------------
     | The prototype's two-hours distinction
     | ------------------------------------------------------------------ */

    public function test_rendered_hours_counts_only_approved_entries(): void
    {
        $faculty = Faculty::factory()->create();

        $project = ExtensionProject::create([
            'code' => 'CAS-2026-900',
            'title' => 'Faculty Hours Test Project',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 10000,
        ]);

        $approvedActivity = Activity::create([
            'extension_project_id' => $project->id,
            'title' => 'Approved Activity',
            'planned_start_date' => '2026-07-01',
            'planned_end_date' => '2026-07-01',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'status' => 'completed',
        ]);

        $pendingActivity = Activity::create([
            'extension_project_id' => $project->id,
            'title' => 'Pending Activity',
            'planned_start_date' => '2026-08-01',
            'planned_end_date' => '2026-08-01',
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'status' => 'completed',
        ]);

        RenderedHours::create([
            'faculty_id' => $faculty->id,
            'activity_id' => $approvedActivity->id,
            'date' => '2026-07-01',
            'hours' => 8.0,
            'source' => 'auto',
            'status' => RenderedHours::STATUS_APPROVED,
        ]);

        RenderedHours::create([
            'faculty_id' => $faculty->id,
            'activity_id' => $pendingActivity->id,
            'date' => '2026-08-01',
            'hours' => 4.0,
            'source' => 'auto',
            'status' => RenderedHours::STATUS_PENDING,
        ]);

        $record = app(FacultyContributionService::class)
            ->forFaculty(collect([$faculty]))
            ->first();

        $this->assertSame(8.0, $record['rendered_hours']);
        $this->assertSame(4.0, $record['pending_hours']);
    }

    /* ---------------------------------------------------------------------
     | Phase R3c — the two training blocks
     ----------------------------------------------------------------------
     | R3a/R3b left "Training contribution" and "Performance trend" as honest
     | "not yet measurable" panels because the model did not exist. R4 then built
     | it, which silently flipped both blocks onto a live-but-unfinished branch:
     | one kept copy claiming R4 had not landed, the other rendered the literal
     | placeholder "Trend chart available.". Nothing asserted the measurable
     | branch, so the suite stayed green over it.
     |
     | R3c finishes both. These tests pin the branch that was previously
     | unasserted — including the trap that caused the placeholder in the first
     | place: `training_activities` is a Collection after the round trip, NOT the
     | array `TrainingHoursService::forFaculty()` builds internally.
     ---------------------------------------------------------------------- */

    public function test_the_training_contribution_block_shows_delivered_figures(): void
    {
        // 2 trainors x 30 trainees x 0.5 day = 30 hrs. Deliberately the prototype's
        // worked example (§2.2A) — if an `x 8` ever reappears, this reads 240.
        $faculty = $this->facultyDelivering(days: 0.5, participants: 30, trainors: 2);

        Livewire::actingAs($this->admin())
            ->test(Profile::class, ['faculty' => $faculty])
            ->assertSee('Training contribution')
            ->assertSee('30')                          // delivered hours
            ->assertSee('training hours delivered')
            ->assertSee('trainees reached')
            ->assertSee('training days')
            ->assertSee('trainors × trainees × days')  // the formula is stated
            ->assertDontSee('Not yet measurable');
    }

    public function test_the_training_contribution_block_names_its_data_source(): void
    {
        // R-Q1: a Director must be able to see whether a headline figure rests on
        // imported attendance or on someone's manual entry. Both are legitimate;
        // conflating them is not.
        $faculty = $this->facultyDelivering(days: 1.0, participants: 12, trainors: 1);

        $record = app(FacultyContributionService::class)
            ->forFaculty(collect([$faculty]))
            ->first();

        $this->assertSame(TrainingHoursService::SOURCE_MANUAL, $record['training_activities']->first()['trainees_source']);
        $this->assertSame([TrainingHoursService::SOURCE_MANUAL => 1], $record['training_sources']);

        Livewire::actingAs($this->admin())
            ->test(Profile::class, ['faculty' => $faculty])
            ->assertSee('Manual entry');
    }

    public function test_the_training_contribution_block_flags_an_unrecorded_trainee_count(): void
    {
        // `no_of_days` present but no attendance and no `participants` means the
        // session contributes 0 hours. That is a data gap, and the page has to say
        // so — otherwise a 0 sits next to a real session and reads as a claim.
        $faculty = $this->facultyDelivering(days: 1.0, participants: null, trainors: 1);

        $record = app(FacultyContributionService::class)
            ->forFaculty(collect([$faculty]))
            ->first();

        $this->assertSame(0.0, (float) $record['training_hours_delivered']);
        $this->assertSame(1, $record['training_sources'][TrainingHoursService::SOURCE_NONE]);

        Livewire::actingAs($this->admin())
            ->test(Profile::class, ['faculty' => $faculty])
            ->assertSee('Not recorded')
            ->assertSee('data gap, not a claim that nothing was delivered');
    }

    public function test_the_performance_trend_draws_a_chart_from_real_periods(): void
    {
        // Two dated activities in two different months, so there is a shape to draw.
        // The old placeholder string must be gone.
        $faculty = Faculty::factory()->create();
        $project = $this->project();

        $this->attachActivity($faculty, $project, 'March Session', '2026-03-10', 1.0, 10, 1);
        $this->attachActivity($faculty, $project, 'May Session', '2026-05-04', 1.0, 20, 1);

        Livewire::actingAs($this->admin())
            ->test(Profile::class, ['faculty' => $faculty])
            ->assertSee('Performance trend')
            ->assertSee('Training hours delivered per period')
            ->assertSee('Mar 2026')
            ->assertSee('May 2026')
            ->assertDontSee('Trend chart available.');
    }

    public function test_the_performance_trend_withholds_the_chart_for_a_single_point(): void
    {
        // A one-point line is not a trend. The chart is withheld and the block
        // explains why, rather than drawing a flat line that implies steady
        // delivery. This is the NULL-over-0 principle applied to a chart.
        $faculty = $this->facultyDelivering(days: 1.0, participants: 10, trainors: 1);

        Livewire::actingAs($this->admin())
            ->test(Profile::class, ['faculty' => $faculty])
            ->assertSee('Performance trend')
            ->assertSee('No trend to draw yet')
            ->assertSee('is not a trend')
            ->assertDontSee('Trend chart available.');
    }

    public function test_the_performance_trend_withholds_the_chart_when_every_period_is_zero(): void
    {
        // Two dated sessions, neither with a trainee count: a multi-point series
        // where every value is 0. Technically a shape, but it would draw a flat
        // line at zero and read as "delivered nothing throughout".
        $faculty = Faculty::factory()->create();
        $project = $this->project();

        $this->attachActivity($faculty, $project, 'Blind Session A', '2026-03-10', 1.0, null, 1);
        $this->attachActivity($faculty, $project, 'Blind Session B', '2026-05-04', 1.0, null, 1);

        Livewire::actingAs($this->admin())
            ->test(Profile::class, ['faculty' => $faculty])
            ->assertSee('No trend to draw yet')
            ->assertDontSee('Trend chart available.');
    }

    public function test_an_undated_activity_is_bucketed_rather_than_dropped(): void
    {
        // Silently omitting delivered hours would understate the trend, so an
        // activity with no planned date lands in an explicit "undated" bucket. It
        // also has to SORT last: the chart plots buckets in order, so an undated
        // point wedged between two months would misrepresent when work happened.
        // ('u' sorts above digits, so `ksort` happens to give this for free — this
        // test is what stops that becoming an accident.)
        $activities = [
            ['planned_start_date' => '2026-03-10', 'hours' => 5.0],
            ['planned_start_date' => null, 'hours' => 7.0],
            ['planned_start_date' => '2026-05-04', 'hours' => 3.0],
            ['planned_start_date' => '', 'hours' => 1.0],
        ];

        $trend = app(TrainingHoursService::class)->trendForActivities($activities);

        $this->assertSame(['2026-03', '2026-05', 'undated'], array_column($trend, 'period'));
        $this->assertSame(8.0, $trend[2]['hours'], 'The undated bucket must keep its hours.');
        $this->assertSame(2, $trend[2]['sessions'], 'NULL and empty-string dates both land here.');
    }

    public function test_the_trend_buckets_every_date_format_the_same_way(): void
    {
        // The per-activity rows come from a raw `DB::table()` select, so the date
        // arrives as whatever the driver returns rather than what a cast would
        // produce. All three string shapes must land in ONE bucket — a value that
        // silently produced its own key would split a month into several points and
        // draw a sawtooth that never happened.
        $trend = app(TrainingHoursService::class)->trendForActivities([
            ['planned_start_date' => '2026-03-15', 'hours' => 2.0],
            ['planned_start_date' => '2026-03-20 00:00:00', 'hours' => 3.0],
            ['planned_start_date' => '2026-03-28T00:00:00.000000Z', 'hours' => 4.0],
        ]);

        $this->assertCount(1, $trend, 'All three dates are March — one bucket, not three.');
        $this->assertSame('2026-03', $trend[0]['period']);
        $this->assertSame(9.0, $trend[0]['hours']);
        $this->assertSame(3, $trend[0]['sessions']);
    }

    public function test_the_trend_accepts_a_datetime_object_as_well_as_a_string(): void
    {
        // This is the case a `substr()` could never handle: a DateTimeInterface is
        // not a string, and `$activity['planned_start_date']` is only a string when
        // the caller happened to hydrate it from a raw select. Anything that
        // hydrates through a cast hands over an object instead, so the bucket key
        // must be derived by formatting rather than slicing.
        $trend = app(TrainingHoursService::class)->trendForActivities([
            ['planned_start_date' => new \DateTimeImmutable('2026-04-05 09:30:00'), 'hours' => 6.0],
            ['planned_start_date' => Carbon::parse('2026-04-20'), 'hours' => 1.0],
        ]);

        $this->assertSame('2026-04', $trend[0]['period']);
        $this->assertSame(7.0, $trend[0]['hours']);
    }

    public function test_the_trend_accepts_the_collection_the_contribution_service_hands_over(): void
    {
        // THE BUG THIS EXISTS FOR. `forFaculty()` builds `training_activities` with
        // `->values()`, and its return is a Collection — so what reaches the profile
        // component is a Collection, not the array the service builds internally.
        // A `array` type declaration on `trendForActivities()` therefore throws a
        // TypeError on every real page load while every unit test that hand-builds
        // an array still passes. Pinned here so the signature cannot narrow again.
        $faculty = $this->facultyDelivering(days: 1.0, participants: 10, trainors: 1);

        $record = app(FacultyContributionService::class)
            ->forFaculty(collect([$faculty]))
            ->first();

        $this->assertInstanceOf(Collection::class, $record['training_activities']);

        $trend = app(TrainingHoursService::class)->trendForActivities($record['training_activities']);

        $this->assertIsArray($trend);
        $this->assertSame('2026-01', $trend[0]['period']);
    }

    public function test_the_faculty_side_agrees_with_the_project_hub(): void
    {
        // THE DELEGATION CONTRACT. `FacultyContributionService` must not re-derive
        // `trainors x trainees x days`; if it ever does, this page and the project
        // hub start disagreeing about the same activity. With none of the seeded
        // activities assigned to more than one faculty member, the two roll-ups
        // must be exactly equal.
        $faculty = Faculty::factory()->create();
        $project = $this->project();

        $this->attachActivity($faculty, $project, 'Shared Session', '2026-04-02', 0.5, 30, 3);

        $fromFaculty = app(FacultyContributionService::class)
            ->forFaculty(collect([$faculty]))
            ->first()['training_hours_delivered'];

        $fromHub = app(TrainingHoursService::class)->forProject($project)['actual_hours'];

        $this->assertSame(45.0, (float) $fromFaculty, '3 x 30 x 0.5 = 45.');
        $this->assertSame((float) $fromHub, (float) $fromFaculty, 'Both views must read the same activity identically.');
    }

    public function test_trainees_reached_counts_distinct_people_not_attendances(): void
    {
        // A beneficiary who attended three of a faculty member's sessions was still
        // reached once. Summing per-activity counts would report 3.
        $faculty = Faculty::factory()->create();
        $project = $this->project();
        $beneficiary = Beneficiary::factory()->create();

        foreach ([['2026-04-02', 1.0], ['2026-04-09', 1.0], ['2026-04-16', 1.0]] as $i => [$date, $days]) {
            $activity = $this->attachActivity($faculty, $project, "Session {$i}", $date, $days, null, 1);

            Attendance::create([
                'activity_id' => $activity->id,
                'beneficiary_id' => $beneficiary->id,
                'status' => 'present',
                'attendance_date' => $date,
            ]);
        }

        $record = app(FacultyContributionService::class)
            ->forFaculty(collect([$faculty]))
            ->first();

        $this->assertSame(3, $record['training_sessions'], 'Three sessions were held.');
        $this->assertSame(1, $record['trainees_reached'], 'But only one distinct person was reached.');
    }

    public function test_each_faculty_member_gets_their_own_figures_in_a_batch(): void
    {
        // THE MISATTRIBUTION TEST. Every other test in this file calls
        // `forFaculty(collect([$oneFaculty]))`, where a sequentially-keyed
        // collection would still resolve correctly because the single row sits at
        // offset 0 and the faculty id happens to be looked up as key 0 or 1. Batch
        // more than one member and that luck runs out: the ids (1, 2, 3…) overlap
        // the offsets (0, 1, 2…), so a wrong keying silently shows one member's
        // hours on another's profile.
        //
        // Two members with deliberately DIFFERENT figures, so a swap is visible.
        $project = $this->project();

        $first = Faculty::factory()->create();
        $second = Faculty::factory()->create();

        // 1 x 10 x 1 = 10 hrs for the first, 2 x 30 x 0.5 = 30 hrs for the second.
        $this->attachActivity($first, $project, 'First Session', '2026-02-01', 1.0, 10, 1);
        $this->attachActivity($second, $project, 'Second Session', '2026-06-01', 0.5, 30, 2);

        $records = app(FacultyContributionService::class)
            ->forFaculty(collect([$first, $second]));

        $this->assertCount(2, $records);

        // Look each member up BY ID — the same lookup the service itself performs.
        $firstRow = $records->firstWhere('id', $first->id);
        $secondRow = $records->firstWhere('id', $second->id);

        $this->assertNotNull($firstRow);
        $this->assertNotNull($secondRow);
        $this->assertSame(10.0, (float) $firstRow['training_hours_delivered'], 'The first member must get their own 10 hrs.');
        $this->assertSame(30.0, (float) $secondRow['training_hours_delivered'], 'The second member must get their own 30 hrs.');
        $this->assertSame(1, $firstRow['training_sessions']);
        $this->assertSame(1, $secondRow['training_sessions']);

        // And a member with no activities must degrade to NULL, never to a
        // neighbour's figures.
        $third = Faculty::factory()->create();
        $withThird = app(FacultyContributionService::class)->forFaculty(collect([$first, $third]));

        $this->assertNull(
            $withThird->firstWhere('id', $third->id)['training_hours_delivered'],
            'A member with no activities must be NULL, not the other member\'s total.'
        );
    }

    public function test_the_roll_up_is_keyed_by_faculty_id(): void
    {
        // The contract `FacultyContributionService` relies on when it calls
        // `$training->get($member->id)`. Asserted directly because a violation is
        // silent: `Collection::get()` returns a different member's row rather than
        // failing, and the service's guard turns that into a LogicException.
        //
        // Note what is NOT asserted: a key for every requested id. A member with no
        // activities has no row and must fall through to the service's NULL default
        // — requiring full coverage would force a fabricated entry instead.
        $withActivities = Faculty::factory()->create();
        $withoutActivities = Faculty::factory()->create();
        $project = $this->project();

        $this->attachActivity($withActivities, $project, 'Keyed Session', '2026-03-01', 1.0, 5, 1);

        $rollUp = app(TrainingHoursService::class)
            ->forFaculty(collect([$withActivities, $withoutActivities]));

        $this->assertSame(
            [$withActivities->id],
            $rollUp->keys()->values()->all(),
            'Keys must be faculty ids — never sequential offsets.'
        );
        $this->assertArrayNotHasKey(0, $rollUp->all(), 'A key of 0 would mean the collection is offset-keyed.');
        $this->assertFalse(
            $rollUp->has($withoutActivities->id),
            'A member with no activities has no row; the caller supplies the NULL default.'
        );
    }

    /* ---------------------------------------------------------------------
     | R3c fixtures
     ---------------------------------------------------------------------- */

    private function project(): ExtensionProject
    {
        return ExtensionProject::create([
            'code' => 'CAS-2026-'.fake()->unique()->numerify('8##'),
            'title' => 'R3c Training Delivery Project',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 50000,
        ]);
    }

    /**
     * One faculty member with one dated activity carrying the training fields.
     *
     * @param  int|null  $participants  NULL exercises the R-Q1 "not recorded" branch
     */
    private function facultyDelivering(float $days, ?int $participants, int $trainors): Faculty
    {
        $faculty = Faculty::factory()->create();

        $this->attachActivity($faculty, $this->project(), 'Delivery Session', '2026-01-15', $days, $participants, $trainors);

        return $faculty;
    }

    private function attachActivity(
        Faculty $faculty,
        ExtensionProject $project,
        string $title,
        string $date,
        float $days,
        ?int $participants,
        int $trainors,
    ): Activity {
        $activity = Activity::create([
            'extension_project_id' => $project->id,
            'title' => $title,
            'planned_start_date' => $date,
            'planned_end_date' => $date,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'status' => 'completed',
            'no_of_days' => $days,
            'participants' => $participants,
            'trainors_snapshot' => $trainors,
        ]);

        $activity->faculty()->attach($faculty->id);

        return $activity;
    }
}
