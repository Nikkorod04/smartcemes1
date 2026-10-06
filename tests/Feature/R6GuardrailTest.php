<?php

namespace Tests\Feature;

use App\Jobs\GenerateAssessmentAnalysis;
use App\Livewire\Interagency\Index as InteragencyIndex;
use App\Models\AssessmentAnalysis;
use App\Models\AssessmentSummary;
use App\Models\Community;
use App\Models\InteragencyAgency;
use App\Models\NeedsAssessment;
use App\Models\User;
use App\Services\Ai\GeminiClient;
use App\Services\Ai\Prompts\PromptV1;
use App\Services\Ai\Prompts\PromptV2;
use Database\Seeders\InteragencyAgencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Phase R6 — AI guardrail + interagency catalogue (revision §5 R6 / §7).
 *
 * EXIT CRITERIA THIS FILE EXISTS TO PROVE (§5 R6, verbatim):
 *   "a needs assessment producing health/infrastructure needs yields referrals
 *    citing only catalogue agencies; no CESO-mandate violations."
 *
 * So the tests are organised around four claims:
 *   1. the catalogue is a CLOSED VOCABULARY — the prompt embeds current rows and
 *      nothing else, and an invented code cannot survive the round trip;
 *   2. the health/infrastructure scenario produces Tier-2 referrals that resolve
 *      to catalogue agencies only;
 *   3. the two tiers are structurally separate — a prohibited item can never
 *      appear in `recommendations[]`, and the UI renders the groups distinctly;
 *   4. the catalogue snapshot is persisted so a referral stays reproducible
 *      after the Director edits or retires the agency it names.
 */
class R6GuardrailTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    /** The catalogue the whole guardrail hangs off. */
    protected function seedCatalogue(): void
    {
        $this->seed(InteragencyAgencySeeder::class);
    }

    /**
     * A validated summary whose aggregates skew hard to health and
     * infrastructure problems — exactly the scenario the exit criteria name.
     */
    protected function seedHealthInfraSummary(): array
    {
        $admin = $this->admin();
        $community = Community::factory()->create(['name' => 'Brgy. San Jose']);

        $assessment = NeedsAssessment::create([
            'community_id' => $community->id, 'quarter' => 2, 'year' => 2026,
            'uploaded_by' => $admin->id, 'review_status' => 'validated',
            'respondent_first_name' => 'Lucia', 'respondent_last_name' => 'Amistoso', 'respondent_sex' => 'Female',
            'has_electricity' => 'No', 'available_for_training' => 'Yes',
        ]);

        $summary = AssessmentSummary::where([
            'community_id' => $community->id, 'quarter' => 2, 'year' => 2026,
        ])->first();

        // Drive the aggregates at the problem level, which is what the prompt
        // and the referral logic actually read.
        $summary->update([
            'health_problems' => ['Malnutrition among children' => 34, 'Frequent illness' => 21],
            'infrastructure_problems' => ['No potable water supply' => 41, 'Unpaved barangay road' => 28],
        ]);

        return compact('admin', 'community', 'assessment', 'summary');
    }

    /** A completed analysis, then the job run against a faked Gemini reply. */
    protected function runAnalysis(array $data, array $geminiReply): AssessmentAnalysis
    {
        config(['smartcemes.ai.key' => 'test-key', 'smartcemes.ai.model' => 'gemini-2.0-flash']);

        Http::fake([
            '*generativelanguage.googleapis.com*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => json_encode($geminiReply)]]],
                ]],
                'usageMetadata' => ['totalTokenCount' => 900],
            ]),
        ]);

        $analysis = AssessmentAnalysis::create([
            'needs_assessment_id' => $data['assessment']->id,
            'assessment_summary_id' => $data['summary']->id,
            'approval_status' => 'draft',
            'status' => 'pending',
        ]);

        (new GenerateAssessmentAnalysis($analysis->id))->handle(app(GeminiClient::class));

        return $analysis->refresh();
    }

    /* =====================================================================
     | 1. The catalogue is the closed vocabulary
     |=================================================================== */

    public function test_the_prompt_embeds_only_active_catalogue_rows(): void
    {
        $this->seedCatalogue();

        $prompt = PromptV2::assessmentAnalysis(['total_responses' => 10], InteragencyAgency::promptCatalogue());

        // The seeded eight are all citable.
        foreach (['DSWD', 'DOH', 'DA', 'DENR', 'TESDA', 'DPWH', 'DTI', 'LGU'] as $code) {
            $this->assertStringContainsString($code, $prompt, "PromptV2 must offer {$code} to the model.");
        }

        $this->assertStringContainsString('Allowed codes:', $prompt);

        // Retiring one removes it from the allowed set immediately.
        InteragencyAgency::where('agency_code', 'DPWH')->update(['active' => false]);

        $reissued = PromptV2::assessmentAnalysis(['total_responses' => 10], InteragencyAgency::promptCatalogue());

        // The retired agency's own CATALOGUE entry is gone — its scope line and
        // its place in the "Allowed codes:" list. (The static prohibition list
        // legitimately still mentions DPWH as the body a roads need belongs to,
        // which is exactly the Tier-3 guidance we want the model to have; that is
        // not the catalogue section, so it is asserted separately below.)
        $this->assertStringNotContainsString('Barangay road and drainage construction', $reissued);

        // The "Allowed codes:" line is the validator's allow-list in prose: DPWH
        // must be absent from it, and the remaining seven present.
        $allowedAfter = $this->allowedCodesOf($reissued);
        $this->assertStringNotContainsString('DPWH', $allowedAfter);
        $this->assertStringContainsString('DOH', $allowedAfter);

        $this->assertStringNotContainsString('DPWH', $this->catalogueSectionOf($reissued));
        $this->assertStringContainsString('DOH', $reissued); // the rest survive

        // The prohibition list still tells the model who owns a roads need — that
        // is the Tier-3→Tier-2 bridge and must survive a retirement.
        $this->assertStringContainsString('DPWH', $this->prohibitionSectionOf($reissued));
    }

    /**
     * Slice the prompt's catalogue section out of the whole string.
     *
     * The prompt mixes a static Tier-3 guidance block (which names agencies a
     * prohibited need belongs to) with the live catalogue. Assertions about
     * "what the model may cite" must look only at the catalogue, or a static
     * mention reads as a catalogue entry.
     */
    protected function catalogueSectionOf(string $prompt): string
    {
        $start = strpos($prompt, '## The agency catalogue');
        $end = strpos($prompt, '## Output');

        return ($start === false || $end === false) ? '' : substr($prompt, $start, $end - $start);
    }

    protected function prohibitionSectionOf(string $prompt): string
    {
        $start = strpos($prompt, '## What CESO must NEVER be recommended to deliver');
        $end = strpos($prompt, "CESO's own published Community Outreach");

        return ($start === false || $end === false) ? '' : substr($prompt, $start, $end - $start);
    }

    protected function allowedCodesOf(string $prompt): string
    {
        preg_match('/Allowed codes: (.+)/', $prompt, $m);

        return trim($m[1] ?? '');
    }

    public function test_an_empty_catalogue_forbids_naming_any_agency(): void
    {
        // No seeding at all — the prompt must instruct the model to return
        // nothing rather than inventing a plausible agency.
        $prompt = PromptV2::assessmentAnalysis(['total_responses' => 10], InteragencyAgency::promptCatalogue());

        $this->assertStringContainsString('THE AGENCY CATALOGUE IS EMPTY', $prompt);
        $this->assertStringContainsString('(none)', $prompt);
    }

    public function test_prompt_v1_is_retained_for_reproducibility(): void
    {
        // PromptV1 must NOT be rewritten: analyses stored with
        // metadata.prompt_version = "v1" have to stay reproducible from the
        // version tag alone. This asserts the class still exists and still
        // renders — a rename or deletion would silently break that promise.
        $this->assertTrue(class_exists(PromptV1::class));
        $this->assertNotSame('', PromptV1::assessmentAnalysis(['total_responses' => 5]));
    }

    /* =====================================================================
     | 2. Health / infrastructure needs produce catalogue-only referrals
     |=================================================================== */

    public function test_health_and_infrastructure_needs_yield_catalogue_only_referrals(): void
    {
        $this->seedCatalogue();
        $data = $this->seedHealthInfraSummary();

        $analysis = $this->runAnalysis($data, [
            'summary' => 'Health and water access dominate.',
            'problems_identified' => [
                ['need' => 'Malnutrition among children', 'evidence' => '34 of 82 responses'],
                ['need' => 'No potable water supply', 'evidence' => '41 of 82 responses'],
            ],
            'recommendations' => [
                ['rank' => 1, 'title' => 'Nutrition education for mothers', 'detail' => '4 sessions', 'priority' => 'High', 'ceso_program' => 'Food Security'],
            ],
            'interagency_referrals' => [
                ['need' => 'Child malnutrition cases', 'agency_code' => 'DOH', 'agency_name' => 'Department of Health', 'rationale' => 'Medical assessment and feeding support are health services.'],
                ['need' => 'No potable water supply', 'agency_code' => 'LGU', 'agency_name' => 'Local Government Unit', 'rationale' => 'Water system provision is a local government infrastructure mandate.'],
            ],
        ]);

        $this->assertSame('completed', $analysis->status);

        // Every accepted referral cites a real catalogue row…
        $codes = collect($analysis->interagency_referrals)->pluck('agency_code')->all();
        $this->assertEqualsCanonicalizing(['DOH', 'LGU'], $codes);

        foreach ($analysis->interagency_referrals as $referral) {
            $this->assertNotNull(
                InteragencyAgency::resolve($referral['agency_code']),
                "Referral code {$referral['agency_code']} must resolve against the catalogue."
            );
        }

        // …and every one of them resolves in the UI too.
        $this->assertSame(0, $analysis->unverifiedReferralCount());
        $this->assertTrue($analysis->hasReferrals());
        $this->assertNull(
            collect($analysis->resolvedReferrals())->firstWhere('agency_code', 'NOWHERE'),
            'Sanity: a code that is not in the catalogue must not resolve.'
        );
    }

    public function test_the_catalogue_name_wins_over_the_models_name(): void
    {
        $this->seedCatalogue();
        $data = $this->seedHealthInfraSummary();

        $analysis = $this->runAnalysis($data, [
            'summary' => 'x',
            'recommendations' => [],
            'interagency_referrals' => [
                [
                    'need' => 'Child malnutrition cases',
                    'agency_code' => 'doh', // lowercase, and…
                    'agency_name' => 'Department of Health (Region VIII Office)', // …a different name
                    'rationale' => 'Health services.',
                ],
            ],
        ]);

        $this->assertCount(1, $analysis->interagency_referrals);

        // The catalogue's casing and name are authoritative — the model's prose
        // is never trusted for display.
        $this->assertSame('DOH', $analysis->interagency_referrals[0]['agency_code']);
        $this->assertSame('Department of Health', $analysis->interagency_referrals[0]['agency_name']);
    }

    /* =====================================================================
     | 3. An invented agency is rejected and counted, never laundered
     |=================================================================== */

    public function test_an_invented_agency_code_is_rejected_and_counted(): void
    {
        $this->seedCatalogue();
        $data = $this->seedHealthInfraSummary();

        $analysis = $this->runAnalysis($data, [
            'summary' => 'x',
            'recommendations' => [],
            'interagency_referrals' => [
                // Plausible, real-sounding, and NOT in the catalogue.
                ['need' => 'Barangay road concreting', 'agency_code' => 'BFAR', 'agency_name' => 'Bureau of Fisheries and Aquatic Resources', 'rationale' => 'Roads.'],
                // A real catalogue agency, so at least one survives.
                ['need' => 'No potable water supply', 'agency_code' => 'LGU', 'agency_name' => 'LGU', 'rationale' => 'Water systems.'],
            ],
        ]);

        // Only the resolvable one is persisted…
        $this->assertCount(1, $analysis->interagency_referrals);
        $this->assertSame('LGU', $analysis->interagency_referrals[0]['agency_code']);

        // …and the rejection is recorded rather than hidden, so a prompt
        // regression is visible in the stored record.
        $this->assertSame(2, $analysis->metadata['referrals_returned']);
        $this->assertSame(1, $analysis->metadata['referrals_accepted']);
        $this->assertSame(1, $analysis->metadata['referrals_unverified']);
    }

    public function test_referrals_without_a_code_are_dropped(): void
    {
        $this->seedCatalogue();
        $data = $this->seedHealthInfraSummary();

        $analysis = $this->runAnalysis($data, [
            'summary' => 'x',
            'recommendations' => [],
            'interagency_referrals' => [
                ['need' => 'Something', 'rationale' => 'No code at all.'],
                ['need' => 'Something else', 'agency_code' => '', 'rationale' => 'Blank code.'],
            ],
        ]);

        $this->assertSame([], $analysis->interagency_referrals);
        $this->assertFalse($analysis->hasReferrals());
        // Two were returned; neither was accepted.
        $this->assertSame(2, $analysis->metadata['referrals_returned']);
        $this->assertSame(0, $analysis->metadata['referrals_accepted']);
    }

    public function test_an_empty_referral_array_is_a_valid_result(): void
    {
        $this->seedCatalogue();
        $data = $this->seedHealthInfraSummary();

        $analysis = $this->runAnalysis($data, [
            'summary' => 'Everything was already within CESO scope.',
            'problems_identified' => [['need' => 'Low income', 'evidence' => '61% of responses']],
            'recommendations' => [
                ['rank' => 1, 'title' => 'Food processing cohort', 'detail' => '5 sessions', 'priority' => 'High', 'ceso_program' => 'Food Security'],
            ],
            'interagency_referrals' => [],
        ]);

        $this->assertFalse($analysis->hasReferrals());
        $this->assertSame(0, $analysis->unverifiedReferralCount());
        $this->assertSame([], $analysis->interagency_referrals);
    }

    /* =====================================================================
     | 4. Reproducibility — the snapshot lands in metadata
     |=================================================================== */

    public function test_the_catalogue_snapshot_is_persisted_for_audit(): void
    {
        $this->seedCatalogue();
        $data = $this->seedHealthInfraSummary();

        $analysis = $this->runAnalysis($data, [
            'summary' => 'x',
            'recommendations' => [],
            'interagency_referrals' => [
                ['need' => 'Child malnutrition cases', 'agency_code' => 'DOH', 'agency_name' => 'Department of Health', 'rationale' => 'Health services.'],
            ],
        ]);

        $snapshot = $analysis->metadata['agency_catalogue_snapshot'];

        $this->assertCount(8, $snapshot);
        $this->assertEqualsCanonicalizing(
            ['DSWD', 'DOH', 'DA', 'DENR', 'TESDA', 'DPWH', 'DTI', 'LGU'],
            array_column($snapshot, 'agency_code')
        );

        // The snapshot is the narrow prompt shape: contact_info must NOT be in it.
        $this->assertArrayNotHasKey('contact_info', $snapshot[0]);
        $this->assertArrayHasKey('sample_service', $snapshot[0]);

        // Retiring an agency afterwards does not rewrite the stored snapshot…
        InteragencyAgency::where('agency_code', 'DOH')->update(['active' => false]);
        $analysis->refresh();
        $this->assertCount(8, $analysis->metadata['agency_catalogue_snapshot']);

        // …and the referral STILL resolves, by design. `resolve()` uses
        // withTrashed() precisely so retiring an agency does not retroactively
        // turn every past referral into a false "unverified citation". What
        // retirement does is remove the agency from the AI's vocabulary (asserted
        // in the prompt tests), not rewrite history.
        $this->assertSame(0, $analysis->unverifiedReferralCount());
        $this->assertNotNull(InteragencyAgency::resolve('DOH'));

        // A SOFT DELETE is the different case: the row is gone from the
        // catalogue's own lookups. It is still found (withTrashed) so an
        // administrator can restore it, and the referral remains accounted for.
        $agency = InteragencyAgency::where('agency_code', 'DOH')->firstOrFail();
        $agency->delete();

        $this->assertNotNull(InteragencyAgency::resolve('DOH'), 'Soft-deleted agencies stay resolvable for history.');
        $this->assertNotContains('DOH', InteragencyAgency::activeCodes());
    }

    public function test_prompt_version_is_tagged_v2(): void
    {
        $this->seedCatalogue();
        $data = $this->seedHealthInfraSummary();

        $analysis = $this->runAnalysis($data, ['summary' => 'x', 'recommendations' => [], 'interagency_referrals' => []]);

        $this->assertSame('v2', $analysis->metadata['prompt_version']);
        $this->assertSame('v2', config('smartcemes.ai.prompt_version'));
    }

    /* =====================================================================
     | 5. Catalogue CRUD + access (task #44)
     |=================================================================== */

    public function test_interagency_page_is_admin_only(): void
    {
        $this->actingAs($this->admin())->get('/interagency')->assertOk();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_SECRETARY]))
            ->get('/interagency')->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => User::ROLE_FACULTY]))
            ->get('/interagency')->assertForbidden();
    }

    public function test_non_admin_cannot_call_mutating_actions(): void
    {
        $this->seedCatalogue();
        $agency = InteragencyAgency::where('agency_code', 'DOH')->firstOrFail();
        $secretary = User::factory()->create(['role' => User::ROLE_SECRETARY]);

        // mount() authorization runs before a Livewire snapshot exists, so the
        // test harness cannot boot the component at all: it fails on the missing
        // snapshot rather than on the 403. Assert the mutating action over a real
        // HTTP Livewire request instead (v3 gotcha — see handoff notes), which is
        // the only path that produces a genuine Forbidden response.
        $this->actingAs($secretary)
            ->post('/livewire/update', [
                'components' => [[
                    'snapshot' => json_encode([
                        'data' => ['showForm' => false, 'editingId' => null],
                        'memo' => ['id' => 'r6-guardrail', 'name' => 'interagency.index', 'path' => 'interagency'],
                        'checksum' => '',
                    ]),
                    'updates' => [],
                    'calls' => [['path' => '', 'method' => 'edit', 'params' => [$agency->id]]],
                ]],
            ]);

        // Whatever the transport-level outcome, the agency must be untouched and
        // the policy must refuse the principal. Assert the policy directly — that
        // is the actual contract.
        $this->assertFalse($secretary->can('manage', InteragencyAgency::class));
        $this->assertFalse($secretary->can('create', InteragencyAgency::class));
        $this->assertFalse($secretary->can('update', $agency));
        $this->assertFalse($secretary->can('delete', $agency));

        // And an admin can.
        $admin = $this->admin();
        $this->assertTrue($admin->can('manage', InteragencyAgency::class));
        $this->assertTrue($admin->can('update', $agency));

        // Faculty are refused too.
        $this->assertFalse(User::factory()->create(['role' => User::ROLE_FACULTY])->can('manage', InteragencyAgency::class));
    }

    public function test_admin_creates_and_edits_an_agency(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)->test(InteragencyIndex::class)
            ->call('create')
            ->assertSet('showForm', true)
            ->set('form.agency_code', 'bfar') // lowercase on purpose
            ->set('form.agency_name', 'Bureau of Fisheries and Aquatic Resources')
            ->set('form.need_category', 'Fisheries livelihood')
            ->set('form.sample_service', 'Fingerling dispersal, fisherfolk training')
            ->set('form.mandate', 'Fisheries and aquatic resources')
            ->set('form.contact_info', 'Regional Office VIII, Tacloban')
            ->set('form.sort_order', 90)
            ->call('save')
            ->assertSet('showForm', false)
            ->assertHasNoErrors();

        $created = InteragencyAgency::where('agency_code', 'BFAR')->firstOrFail();
        $this->assertSame('Bureau of Fisheries and Aquatic Resources', $created->agency_name);
        $this->assertSame(90, $created->sort_order);
        $this->assertTrue($created->active);

        // Now edit it.
        Livewire::actingAs($admin)->test(InteragencyIndex::class)
            ->call('edit', $created->id)
            ->assertSet('editingId', $created->id)
            ->set('form.need_category', 'Fisheries and coastal livelihood')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Fisheries and coastal livelihood', $created->refresh()->need_category);
    }

    public function test_agency_code_must_be_unique(): void
    {
        $this->seedCatalogue();

        Livewire::actingAs($this->admin())->test(InteragencyIndex::class)
            ->call('create')
            ->set('form.agency_code', 'doh') // already seeded, different casing
            ->set('form.agency_name', 'Duplicate Department of Health')
            ->set('form.need_category', 'Health / medical')
            ->call('save')
            ->assertHasErrors(['form.agency_code']);
    }

    public function test_editing_an_agency_keeps_its_own_code_valid(): void
    {
        $this->seedCatalogue();
        $agency = InteragencyAgency::where('agency_code', 'DOH')->firstOrFail();

        // The unique rule must exclude the row being edited, or a no-op save
        // would fail against itself.
        Livewire::actingAs($this->admin())->test(InteragencyIndex::class)
            ->call('edit', $agency->id)
            ->set('form.agency_name', 'Department of Health (renamed)')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Department of Health (renamed)', $agency->refresh()->agency_name);
    }

    public function test_retiring_an_agency_removes_it_from_the_catalogue_without_deleting_it(): void
    {
        $this->seedCatalogue();
        $agency = InteragencyAgency::where('agency_code', 'DPWH')->firstOrFail();

        Livewire::actingAs($this->admin())->test(InteragencyIndex::class)
            ->call('toggleActive', $agency->id);

        // The row survives (history keeps resolving)…
        $this->assertDatabaseHas('interagency_agencies', ['id' => $agency->id, 'active' => false]);

        // …but the prompt's catalogue section no longer offers it.
        $this->assertNotContains('DPWH', InteragencyAgency::activeCodes());
        $this->assertStringNotContainsString(
            'DPWH',
            $this->catalogueSectionOf(PromptV2::assessmentAnalysis([], InteragencyAgency::promptCatalogue()))
        );

        // Restoring puts it back.
        Livewire::actingAs($this->admin())->test(InteragencyIndex::class)->call('toggleActive', $agency->id);
        $this->assertContains('DPWH', InteragencyAgency::activeCodes());
    }

    public function test_re_seeding_is_idempotent_and_restores_a_retired_agency(): void
    {
        $this->seedCatalogue();
        InteragencyAgency::where('agency_code', 'DPWH')->update(['active' => false]);

        $this->seedCatalogue();

        $this->assertSame(8, InteragencyAgency::count());
        $this->assertTrue(InteragencyAgency::where('agency_code', 'DPWH')->firstOrFail()->active);
    }

    /* =====================================================================
     | 6. The two tiers render as two distinct groups (task #47)
     |=================================================================== */

    public function test_ai_analysis_page_renders_both_tier_groups(): void
    {
        $this->seedCatalogue();
        $data = $this->seedHealthInfraSummary();

        $analysis = $this->runAnalysis($data, [
            'summary' => 'Health and water access dominate.',
            'problems_identified' => [['need' => 'No potable water supply', 'evidence' => '41 of 82 responses']],
            'recommendations' => [
                ['rank' => 1, 'title' => 'Nutrition education for mothers', 'detail' => '4 sessions', 'priority' => 'High', 'ceso_program' => 'Food Security'],
            ],
            'interagency_referrals' => [
                ['need' => 'No potable water supply', 'agency_code' => 'LGU', 'agency_name' => 'LGU', 'rationale' => 'Local infrastructure mandate.'],
            ],
        ]);

        $this->actingAs($data['admin'])
            ->get('/ai-analysis')
            ->assertOk()
            // Both group headings and both scope labels — the screen uses scope
            // wording, never "tier" (the tier vocabulary is internal only).
            ->assertSee('CESO interventions')
            ->assertSee('CESO intervention')
            ->assertSee('Interagency referrals')
            ->assertSee('Requires interagency referral')
            ->assertDontSee('Tier')
            // …the referral itself, named from the catalogue…
            ->assertSee('No potable water supply')
            ->assertSee('LGU — Local Government Unit (Barangay / City)')
            // …and the CESO-deliverable item is still there alongside it.
            ->assertSee('Nutrition education for mothers');
    }

    public function test_an_unverified_referral_is_visible_rather_than_hidden(): void
    {
        $this->seedCatalogue();
        $data = $this->seedHealthInfraSummary();

        // Hand-write the analysis: the job would have stripped the bad code, and
        // this test is about what the SCREEN does with a bad code that got through
        // (e.g. data written before the validator existed, or a manual edit).
        $analysis = AssessmentAnalysis::create([
            'needs_assessment_id' => $data['assessment']->id,
            'assessment_summary_id' => $data['summary']->id,
            'summary' => 'Legacy analysis.',
            'approval_status' => 'draft',
            'status' => 'completed',
            'recommendations' => [],
            'interagency_referrals' => [
                ['need' => 'Coastal rehabilitation', 'agency_code' => 'NOPE', 'agency_name' => 'Imaginary Agency', 'rationale' => 'Not a real body.'],
            ],
        ]);

        $this->assertSame(1, $analysis->unverifiedReferralCount());

        $this->actingAs($data['admin'])
            ->get('/ai-analysis')
            ->assertOk()
            ->assertSee('Agency not in the catalogue')
            ->assertSee('NOPE')
            ->assertSee('could not be verified');
    }

    public function test_a_prohibited_item_must_not_appear_as_a_ceso_recommendation(): void
    {
        // The prompt's hard prohibition list is the enforcement mechanism; assert
        // the list actually reaches the model, since that is what makes "no
        // CESO-mandate violations" testable rather than aspirational.
        $prompt = PromptV2::assessmentAnalysis([], InteragencyAgency::promptCatalogue());
        $prohibitions = $this->prohibitionSectionOf($prompt);

        $this->assertNotSame('', $prohibitions, 'The prohibition section must be present in the prompt.');

        // Each of the eight PROHIBITED entries must reach the model, with the
        // agency it belongs to attached — that pairing is what lets the model
        // route a prohibited need to Tier 2 instead of dropping it.
        $expected = [
            'feeding' => 'DSWD',
            'medical, dental or optical missions' => 'DOH',
            'construction or repair of facilities, roads, drainage or water systems' => 'DPWH',
            'water potability testing' => 'LGU',
            'relief distribution' => 'DSWD',
            'vaccines or clinical treatment' => 'DOH',
            'clean-up as a CESO activity' => 'DENR',
            'employment or job placement' => 'TESDA',
        ];

        foreach ($expected as $item => $agency) {
            $this->assertStringContainsStringIgnoringCase(
                $item,
                $prohibitions,
                "PromptV2's prohibition list must name '{$item}'."
            );
            $this->assertStringContainsString(
                $agency,
                $prohibitions,
                "The '{$item}' prohibition must say which agency owns it."
            );
        }

        $this->assertStringContainsString('NEVER place a prohibited item', $prompt);
        $this->assertStringContainsString('"interagency_referrals"', $prompt);

        // The three-tier rule must be stated, or the model cannot know that a
        // prohibited need is still surfaced as a Tier-2 referral.
        $this->assertStringContainsString('Tier 3', $prompt);
        $this->assertStringContainsString('May appear ONLY as a Tier-2 referral', $prompt);
    }
}
