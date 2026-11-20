<?php

namespace Tests\Feature;

use App\Models\Beneficiary;
use App\Models\Community;
use App\Models\ExtensionProject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The explicit one-project cleanup (2026-11-20).
 *
 * The migration leaves unlinked beneficiaries with a NULL project. This command
 * backs them up as JSON, then (only with --force) hard-deletes exactly those
 * rows. Assigned beneficiaries must never be touched.
 */
class BeneficiaryPruneTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function fixture(): array
    {
        $community = Community::factory()->create(['name' => 'Brgy. San Jose']);

        $project = ExtensionProject::create([
            'code' => 'EXT-2026-900',
            'title' => 'Prune Test Project',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 10000,
        ]);
        $project->communities()->attach($community->id);

        $keep = Beneficiary::create([
            'first_name' => 'Keep', 'last_name' => 'Me',
            'barangay' => 'San Jose', 'municipality' => 'Tacloban City', 'province' => 'Leyte',
            'community_id' => $community->id,
            'extension_project_id' => $project->id,
            'beneficiary_category' => 'Farmer',
        ]);

        $orphanA = Beneficiary::create([
            'first_name' => 'Orphan', 'last_name' => 'One',
            'barangay' => 'San Jose', 'municipality' => 'Tacloban City', 'province' => 'Leyte',
            'community_id' => $community->id,
            'beneficiary_category' => 'Farmer',
        ]);

        $orphanB = Beneficiary::create([
            'first_name' => 'Orphan', 'last_name' => 'Two',
            'barangay' => 'San Jose', 'municipality' => 'Tacloban City', 'province' => 'Leyte',
            'community_id' => $community->id,
            'beneficiary_category' => 'Vendor',
        ]);
        $orphanB->delete(); // archived orphans are pruned too

        return compact('project', 'keep', 'orphanA', 'orphanB');
    }

    public function test_dry_run_backs_up_without_deleting(): void
    {
        $data = $this->fixture();

        $this->artisan('beneficiaries:prune-unassigned')->assertSuccessful();

        $this->assertSame(3, Beneficiary::withTrashed()->count());
        $this->assertNotSoftDeleted($data['orphanA']);
        $this->assertSoftDeleted($data['orphanB']);

        $files = Storage::disk('local')->files('backups');
        $this->assertCount(1, $files);

        $payload = json_decode(Storage::disk('local')->get($files[0]), true);
        $this->assertCount(2, $payload);
        $this->assertSame(['Orphan One', 'Orphan Two'], collect($payload)
            ->map(fn ($row) => $row['first_name'].' '.$row['last_name'])
            ->all());
        $this->assertNull($payload[0]['extension_project_id']);
    }

    public function test_force_deletes_only_unassigned_beneficiaries(): void
    {
        $data = $this->fixture();

        $this->artisan('beneficiaries:prune-unassigned', ['--force' => true])->assertSuccessful();

        $this->assertSame(1, Beneficiary::withTrashed()->count());
        $this->assertTrue(Beneficiary::withTrashed()->whereKey($data['keep']->id)->exists());
        $this->assertFalse(Beneficiary::withTrashed()->whereKey($data['orphanA']->id)->exists());
        $this->assertFalse(Beneficiary::withTrashed()->whereKey($data['orphanB']->id)->exists());
    }

    public function test_command_is_a_no_op_when_everyone_belongs_to_a_project(): void
    {
        $data = $this->fixture();
        Beneficiary::withTrashed()->whereNull('extension_project_id')->forceDelete();

        $this->artisan('beneficiaries:prune-unassigned', ['--force' => true])->assertSuccessful();

        $this->assertSame(1, Beneficiary::withTrashed()->count());
        $this->assertSame(0, count(Storage::disk('local')->files('backups')));
    }
}
