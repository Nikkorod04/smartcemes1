<?php

namespace App\Console\Commands;

use App\Models\ExtensionProgram;
use App\Models\ProgramObjective;
use App\Models\User;
use App\Notifications\SmartCemesNotification;
use App\Services\KpiService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * DEADLINE SCHEDULER (5.14): notifies Admin of programs ending within 14
 * days and on-track objectives approaching their target dates, with
 * 7-day de-duplication.
 */
class NotifyDeadlines extends Command
{
    protected $signature = 'smartcemes:notify-deadlines';

    protected $description = 'Notify the Admin of programs ending within 14 days and objectives approaching target dates (7-day dedup)';

    public function handle(): int
    {
        $admins = User::where('role', User::ROLE_ADMIN)->get();
        if ($admins->isEmpty()) {
            return self::SUCCESS;
        }

        $sent = 0;
        $now = now();

        $programs = ExtensionProgram::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereBetween('planned_end_date', [$now->copy()->startOfDay(), $now->copy()->addDays(14)->endOfDay()])
            ->get();

        foreach ($programs as $program) {
            $key = 'program_deadline_'.$program->id;

            if ($this->wasNotifiedWithin7Days($admins->first(), $key)) {
                continue; // 7-day dedup
            }

            $days = (int) $now->copy()->startOfDay()->diffInDays($program->planned_end_date, false);
            $admins->each(fn ($admin) => $admin->notify(new SmartCemesNotification(
                'Program ending soon',
                "{$program->code} · {$program->title} ends on ".$program->planned_end_date->format('M j, Y')." ({$days} days).",
                'calendar', 'yellow', $key
            )));

            $sent++;
        }

        // 8.6: on_track ALWAYS derives live from the effective actual —
        // the stored status column is never authoritative.
        $objectives = ProgramObjective::query()
            ->with('program')
            ->whereNotNull('target_date')
            ->whereBetween('target_date', [$now->copy()->startOfDay(), $now->copy()->addDays(14)->endOfDay()])
            ->get()
            ->filter(fn ($o) => app(KpiService::class)->statusFor($o) === 'on_track');

        foreach ($objectives as $objective) {
            $key = 'objective_deadline_'.$objective->id;

            if ($this->wasNotifiedWithin7Days($admins->first(), $key)) {
                continue; // 7-day dedup
            }

            $days = (int) $now->copy()->startOfDay()->diffInDays($objective->target_date, false);
            $admins->each(fn ($admin) => $admin->notify(new SmartCemesNotification(
                'Objective target date approaching',
                "'{$objective->program->code}' objective \"".Str::limit($objective->objective, 60).'" is due '.$objective->target_date->format('M j, Y')." ({$days} days).",
                'chart', 'yellow', $key
            )));

            $sent++;
        }

        $this->info("Deadline notifications sent: {$sent}.");

        return self::SUCCESS;
    }

    /** 7-day dedup (5.14): skip when the same dedup key fired within the last 7 days. */
    protected function wasNotifiedWithin7Days(User $user, string $dedupKey): bool
    {
        return $user->notifications()
            ->where('created_at', '>=', now()->subDays(7))
            ->where('data->dedup_key', $dedupKey)
            ->exists();
    }
}
