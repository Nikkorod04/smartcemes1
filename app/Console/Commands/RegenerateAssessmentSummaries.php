<?php

namespace App\Console\Commands;

use App\Models\AssessmentSummary;
use App\Models\NeedsAssessment;
use App\Services\AssessmentSummaryService;
use Illuminate\Console\Command;

/**
 * Recomputes every community/quarter/year summary in place.
 *
 * Summaries are recalculated automatically when a row is created or validated,
 * which is invisible right up to the moment the rule changes — then every stored
 * figure is stale until its batch happens to be touched again. That is exactly
 * what D7 did to the live database: summaries that had been counting every row
 * had to become validated-only, for all 58 communities at once.
 */
class RegenerateAssessmentSummaries extends Command
{
    protected $signature = 'assessment:summaries
                            {--dry-run : List the batches that would be recomputed}';

    protected $description = 'Recompute every assessment summary from validated rows (D7)';

    public function handle(AssessmentSummaryService $summaries): int
    {
        // Plain arrays, not Eloquent models: `Eloquent\Collection::merge()`
        // keys by primary key, and these rows are projected without an `id`,
        // so merging models silently collapses the whole list.
        $batches = collect()
            ->concat(AssessmentSummary::query()->select('community_id', 'quarter', 'year')->get()
                ->map(fn ($row) => ['community_id' => (int) $row->community_id, 'quarter' => (int) $row->quarter, 'year' => (int) $row->year]))
            ->concat(NeedsAssessment::query()->select('community_id', 'quarter', 'year')->distinct()->get()
                ->map(fn ($row) => ['community_id' => (int) $row->community_id, 'quarter' => (int) $row->quarter, 'year' => (int) $row->year]))
            ->unique(fn (array $row) => $row['community_id'].'-'.$row['quarter'].'-'.$row['year'])
            ->sortBy([['community_id', 'asc'], ['year', 'asc'], ['quarter', 'asc']])
            ->values();

        if ($batches->isEmpty()) {
            $this->info('No community/period batches found.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->table(
                ['Community ID', 'Period'],
                $batches->map(fn (array $b) => [$b['community_id'], 'Q'.$b['quarter'].' '.$b['year']])->all()
            );

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($batches->count());
        $bar->start();

        foreach ($batches as $batch) {
            $summaries->recomputeFor($batch['community_id'], $batch['quarter'], $batch['year']);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Recomputed '.$batches->count().' summaries from validated rows.');

        return self::SUCCESS;
    }
}
