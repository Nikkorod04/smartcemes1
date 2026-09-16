<?php

namespace App\Jobs;

use App\Models\ProgramNarrative;
use App\Services\Ai\ConfidenceScore;
use App\Services\Ai\GeminiClient;
use App\Services\Ai\ProgramAggregates;
use App\Services\Ai\Prompts\PromptV1;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class GenerateProgramNarrative implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 3;

    /** 8.5: retry with backoff. */
    public array $backoff = [60, 180];

    public function __construct(public int $narrativeId) {}

    public function handle(GeminiClient $client): void
    {
        $narrative = ProgramNarrative::with('program')->findOrFail($this->narrativeId);
        $program = $narrative->program;

        // D3: aggregate program-level data only (no PII).
        $aggregates = app(ProgramAggregates::class)->build($program);

        $narrative->update(['raw_extracted_data' => $aggregates]);

        $result = $client->generate(PromptV1::programNarrative($aggregates));

        $narrative->update([
            'summary' => $result['data']['summary'] ?? null,
            'health_label' => $result['data']['health_label'] ?? null,
            'risks' => array_values($result['data']['risks'] ?? []),
            'recommendations' => $result['data']['recommendations'] ?? null,
            // Gemini reports no confidence — derived data-confidence heuristic (guidance only).
            'confidence_score' => ConfidenceScore::forNarrative($aggregates, $result),
            'status' => ProgramNarrative::STATUS_COMPLETED,
            'error_message' => null,
            'metadata' => $result['metadata'] + [
                'confidence_basis' => 'derived: KPI coverage + program richness + output completeness',
            ],
            'generated_at' => now(),
        ]);
    }

    /** 8.5: terminal failed state ("narrative unavailable"). */
    public function failed(\Throwable $e): void
    {
        $narrative = ProgramNarrative::find($this->narrativeId);
        if ($narrative) {
            $narrative->update([
                'status' => ProgramNarrative::STATUS_FAILED,
                'error_message' => 'Narrative unavailable — '.$e->getMessage(),
                'generated_at' => now(),
            ]);
        }

        Log::error('Program narrative generation failed', ['narrative_id' => $this->narrativeId, 'error' => $e->getMessage()]);
    }
}
