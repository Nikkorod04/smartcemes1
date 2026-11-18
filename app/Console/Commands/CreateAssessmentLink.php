<?php

namespace App\Console\Commands;

use App\Models\AssessmentLink;
use App\Models\Community;
use Illuminate\Console\Command;

/**
 * Creates a public needs-assessment share link from the console.
 *
 * The staff-facing generator arrives with the share sheet (Item 1, Step 4).
 * Until then this command is how a link is minted for the walking-skeleton
 * deploy check on the live VM.
 */
class CreateAssessmentLink extends Command
{
    protected $signature = 'assessment:link
                            {community : Community ID, or part of its name}
                            {--days=5 : Validity in days (1-5)}
                            {--quarter= : Quarter 1-4 (defaults to the current quarter)}';

    protected $description = 'Create a public needs-assessment share link for a community (1-5 days)';

    public function handle(): int
    {
        $needle = trim((string) $this->argument('community'));

        $matches = Community::query()
            ->where(function ($query) use ($needle) {
                $query->where('id', $needle)
                    ->orWhere('name', 'like', '%'.$needle.'%');
            })
            ->orderBy('name')
            ->get();

        if ($matches->isEmpty()) {
            $this->error('No community matches "'.$needle.'".');

            return self::FAILURE;
        }

        if ($matches->count() > 1) {
            $this->error('Several communities match. Re-run with the ID:');
            $this->table(
                ['ID', 'Name', 'Municipality'],
                $matches->map(fn (Community $c) => [$c->id, $c->name, $c->municipality])->all()
            );

            return self::FAILURE;
        }

        $days = (int) $this->option('days');

        if ($days < AssessmentLink::MIN_DAYS || $days > AssessmentLink::MAX_DAYS) {
            $this->error('Days must be between '.AssessmentLink::MIN_DAYS.' and '.AssessmentLink::MAX_DAYS.'.');

            return self::FAILURE;
        }

        $quarter = $this->option('quarter') !== null
            ? (int) $this->option('quarter')
            : (int) ceil(now()->month / 3);

        if ($quarter < 1 || $quarter > 4) {
            $this->error('Quarter must be 1, 2, 3 or 4.');

            return self::FAILURE;
        }

        $community = $matches->first();

        $link = AssessmentLink::create([
            'community_id' => $community->id,
            'quarter' => $quarter,
            'year' => (int) now()->format('Y'),
            'token' => AssessmentLink::newToken(),
            'expires_at' => AssessmentLink::expiryFor($days),
            'created_by' => null,
        ]);

        $this->info('Link created: '.$community->name.' · '.$link->periodLabel());
        $this->line(route('assessment.public', ['token' => $link->token]));
        $this->line('Valid until '.$link->expires_at->format('M j, Y g:i A'));

        return self::SUCCESS;
    }
}
