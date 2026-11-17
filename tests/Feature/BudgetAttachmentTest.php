<?php

namespace Tests\Feature;

use App\Livewire\Programs\Hub;
use App\Models\BudgetUtilization;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class BudgetAttachmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The expense entry's proof of expense is now a set of ATTACHED FILES
     * (up to 5, 10 MB each, force-downloaded — never rendered in the browser).
     * It replaces the receipt_reference string, which had no input anywhere
     * in the UI and was always auto-generated.
     */
    protected function program(array $overrides = []): ExtensionProject
    {
        return ExtensionProject::create(array_merge([
            'code' => 'EXT-2026-080',
            'title' => 'Budget Attachment Test Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => '2026-12-31',
            'status' => 'ongoing',
            'allocated_budget' => 100000,
            'target_beneficiaries' => 100,
        ], $overrides));
    }

    public function test_an_expense_entry_saves_up_to_five_attachments(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();

        $files = [
            UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->image('venue-photo.jpg'),
        ];

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->call('openBudgetForm')
            ->set('budgetForm.item_name', 'Training materials')
            ->set('budgetForm.amount', '1500')
            ->set('budgetForm.attachments', $files)
            ->call('saveBudgetEntry')
            ->assertHasNoErrors();

        $entry = BudgetUtilization::firstOrFail();
        $this->assertCount(2, $entry->attachments);
        $this->assertSame('receipt.pdf', $entry->attachments[0]['name']);
        foreach ($entry->attachments as $attachment) {
            Storage::disk('public')->assertExists($attachment['path']);
            $this->assertStringContainsString('budget-attachments/', $attachment['path']);
        }
    }

    public function test_the_budget_tab_lists_attachments_as_force_download_links(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();

        $path = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf')
            ->store('budget-attachments/test', 'public');
        BudgetUtilization::create([
            'extension_project_id' => $program->id,
            'item_name' => 'Materials',
            'amount' => 500,
            'date_used' => '2026-03-01',
            'attachments' => [['path' => $path, 'name' => 'receipt.pdf', 'size' => 102400]],
        ]);

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->call('setTab', 'budget')
            ->assertSee('receipt.pdf')
            ->assertSee(Storage::disk('public')->url($path))
            ->assertSeeHtml('download');
    }

    public function test_an_entry_without_attachments_renders_an_em_dash(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();

        BudgetUtilization::create([
            'extension_project_id' => $program->id,
            'item_name' => 'Materials',
            'amount' => 500,
            'date_used' => '2026-03-01',
        ]);

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->call('setTab', 'budget')
            ->assertSee('Materials')
            ->assertSeeHtml('<span class="text-[11.5px] text-gray-300">—</span>');
    }

    public function test_more_than_five_attachments_are_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();

        $files = array_map(
            fn ($i) => UploadedFile::fake()->create("receipt-{$i}.pdf", 10, 'application/pdf'),
            range(1, 6)
        );

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->call('openBudgetForm')
            ->set('budgetForm.item_name', 'Training materials')
            ->set('budgetForm.amount', '1500')
            ->set('budgetForm.attachments', $files)
            ->call('saveBudgetEntry')
            ->assertHasErrors('budgetForm.attachments');

        $this->assertDatabaseCount('budget_utilizations', 0);
    }

    public function test_an_attachment_over_twenty_mb_is_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();

        // fake()->create() only REPORTS a size, and Livewire's test pipeline
        // re-synthesizes uploads onto an empty temp file — so a 10 MB upload
        // cannot be driven through ->set(). Instead the component's extracted
        // ruleset is validated directly against a genuinely oversized file,
        // which is exactly what saveBudgetEntry() runs.
        // budgetFormRules() reads no instance state — a bare instance is enough.
        $hub = new Hub;
        $rules = (new \ReflectionMethod(Hub::class, 'budgetFormRules'))->invoke($hub);
        $messages = (new \ReflectionMethod(Hub::class, 'budgetFormMessages'))->invoke($hub);

        $oversized = UploadedFile::fake()->createWithContent(
            'oversized.pdf',
            str_repeat('a', 10241 * 1024),
        );

        $validator = \Illuminate\Support\Facades\Validator::make(
            ['budgetForm' => ['attachments' => [$oversized]]],
            array_intersect_key($rules, ['budgetForm.attachments' => '', 'budgetForm.attachments.*' => '']),
            array_intersect_key($messages, ['budgetForm.attachments.*.max' => '']),
        );

        $this->assertTrue($validator->fails());
        $this->assertSame('Each attachment may not exceed 10 MB.', $validator->errors()->first('budgetForm.attachments.0'));
    }

    public function test_a_non_whitelisted_file_type_is_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->call('openBudgetForm')
            ->set('budgetForm.item_name', 'Training materials')
            ->set('budgetForm.amount', '1500')
            ->set('budgetForm.attachments', [UploadedFile::fake()->create('notes.txt', 10)])
            ->call('saveBudgetEntry')
            ->assertHasErrors('budgetForm.attachments.0');

        $this->assertDatabaseCount('budget_utilizations', 0);
    }

    public function test_deleting_an_entry_deletes_its_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $program = $this->program();

        $path = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf')
            ->store('budget-attachments/test', 'public');
        $entry = BudgetUtilization::create([
            'extension_project_id' => $program->id,
            'item_name' => 'Materials',
            'amount' => 500,
            'date_used' => '2026-03-01',
            'attachments' => [['path' => $path, 'name' => 'receipt.pdf', 'size' => 102400]],
        ]);

        Livewire::actingAs($admin)->test(Hub::class, ['project' => $program])
            ->call('deleteBudgetEntry', $entry->id)
            ->assertOk();

        Storage::disk('public')->assertMissing($path);
        // BudgetUtilization soft-deletes: the row survives, the FILE does not.
        $this->assertSoftDeleted('budget_utilizations', ['id' => $entry->id]);
    }

    /**
     * One Testable per forbidden action: an aborted Livewire action
     * invalidates the instance for any subsequent call.
     */
    public function test_faculty_cannot_record_expenses(): void
    {
        $lead = Faculty::factory()->create();
        $program = $this->program(['program_lead_id' => $lead->id]);

        Livewire::actingAs($lead->user)->test(Hub::class, ['project' => $program])
            ->call('openBudgetForm')
            ->assertForbidden();
    }
}
