<?php

namespace Tests\Feature;

use App\Console\Commands\NotifyDeadlines;
use App\Models\ExtensionProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeadlineSchedulerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_notified_for_program_ending_within_14_days(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ExtensionProject::create([
            'code' => 'EXT-2026-020',
            'title' => 'Ending Soon Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => now()->addDays(7),
            'status' => 'ongoing',
            'allocated_budget' => 0,
        ]);

        $this->artisan(NotifyDeadlines::class)->expectsOutput('Deadline notifications sent: 1.')->assertSuccessful();

        $this->assertSame(1, $admin->unreadNotifications->count());
    }

    public function test_seven_day_dedup_prevents_duplicates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ExtensionProject::create([
            'code' => 'EXT-2026-011',
            'title' => 'Ending Soon Program 2',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => now()->addDays(7),
            'status' => 'ongoing',
            'allocated_budget' => 0,
        ]);

        $this->artisan(NotifyDeadlines::class);
        $this->artisan(NotifyDeadlines::class); // second run within 7 days

        $this->assertSame(1, $admin->unreadNotifications->count());
    }

    public function test_completed_programs_are_not_notified(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        ExtensionProject::create([
            'code' => 'EXT-2026-012',
            'title' => 'Completed Program',
            'planned_start_date' => '2026-01-01',
            'planned_end_date' => now()->addDays(3),
            'status' => 'completed',
            'allocated_budget' => 0,
        ]);

        $this->artisan(NotifyDeadlines::class)->expectsOutput('Deadline notifications sent: 0.')->assertSuccessful();

        $this->assertSame(0, $admin->unreadNotifications->count());
    }
}
