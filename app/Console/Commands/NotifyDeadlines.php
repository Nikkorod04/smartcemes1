<?php

namespace App\Console\Commands;

use App\Models\ExtensionProject;
use App\Models\User;
use App\Notifications\SmartCemesNotification;
use App\Services\TrainingHoursService;
use Illuminate\Console\Command;

/**
 * DEADLINE SCHEDULER (5.14): notifies Admin of projects ending within 14 days
 * and projects whose training-hours delivery is behind schedule, with 7-day
 * de-duplication.
 *
 * R5 / D-R7: the second alert used to be an OBJECTIVE target-date warning built
 * on the 8.6 KPI status. Objectives are retained unread (R-Q2), so the alert was
 * rebuilt on the R4 target model — see the block comment in `handle()`.
 */
class NotifyDeadlines extends Command
{
    protected $signature = 'smartcemes:notify-deadlines';

    protected $description = 'Notify the Admin of projects ending within 14 days and training hours behind schedule (7-day dedup)';

    public function handle(): int
    {
        $admins = User::where('role', User::ROLE_ADMIN)->get();
        if ($admins->isEmpty()) {
            return self::SUCCESS;
        }

        $sent = 0;
        $now = now();

        $programs = ExtensionProject::query()
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

        // R5 / D-R7: this block used to alert the Admin about *objective* target
        // dates whose 8.6 status derived as `on_track`. Objectives are retained
        // unread (R-Q2), so notifying about them would push a retired metric into
        // the Director's inbox. It now alerts on a **training-hours shortfall**:
        // a project past its midpoint that has rendered less than half its annual
        // target. That is the actionable signal under the R4 target model.
        $hours = app(TrainingHoursService::class);

        $projects = ExtensionProject::query()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNotNull('annual_target_hours')
            ->where('annual_target_hours', '>', 0)
            ->whereNotNull('planned_start_date')
            ->whereNotNull('planned_end_date')
            ->get()
            ->filter(fn ($p) => $p->planned_end_date->isFuture()
                && $p->planned_start_date->isPast());

        foreach ($projects as $project) {
            $total = $project->planned_start_date->diffInDays($project->planned_end_date);
            if ($total <= 0) {
                continue;
            }

            $elapsed = $project->planned_start_date->diffInDays($now);
            $elapsedPct = $elapsed / $total * 100;

            // Only warn once the project is at least halfway through its window.
            if ($elapsedPct < 50) {
                continue;
            }

            $rollup = $hours->forProject($project);
            $attainment = $rollup['hours_pct'];

            // No target or no hours recorded is not a shortfall — it is an
            // absence of data, and the UI already says "no target set".
            if ($attainment === null || $attainment >= $elapsedPct) {
                continue;
            }

            $key = 'project_hours_shortfall_'.$project->id;

            if ($this->wasNotifiedWithin7Days($admins->first(), $key)) {
                continue; // 7-day dedup
            }

            $admins->each(fn ($admin) => $admin->notify(new SmartCemesNotification(
                'Training hours behind schedule',
                "{$project->code} · {$project->title} is ".round($elapsedPct).'% through its window but has rendered only '
                    .round($attainment).'% of its annual target ('
                    .number_format($rollup['actual_hours'], 1).' of '.number_format((float) $project->annual_target_hours).' hrs).',
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
