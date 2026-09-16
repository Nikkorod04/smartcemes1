<?php

namespace App\Services;

use App\Jobs\GenerateProgramNarrative;
use App\Models\ExtensionProgram;
use App\Models\ProgramNarrative;

/**
 * Output type 2 — program narratives (5.15): Admin-only, NO approval gate
 * (internal decision support); every generation creates a new row; history
 * kept per program.
 */
class ProgramNarrativeService
{
    public function generateFor(ExtensionProgram $program): ProgramNarrative
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Admin-only AI access (D4).');

        $narrative = ProgramNarrative::create([
            'extension_program_id' => $program->id,
            'generated_by' => auth()->id(),
            'status' => ProgramNarrative::STATUS_PENDING,
            'metadata' => [
                'model' => config('smartcemes.ai.model'),
                'prompt_version' => config('smartcemes.ai.prompt_version'),
                'generated_by' => auth()->id(),
            ],
        ]);

        activity()->performedOn($program)->event('narrative_started')
            ->log('Program narrative generation started (Director-only, aggregates only)');

        // Immediate generation (owner decision, v4.4): run in-request so the
        // narrative appears right away — no queue worker dependency. On
        // failure, persist the first-class failed state (failed() is
        // worker-only).
        try {
            GenerateProgramNarrative::dispatchSync($narrative->id);
        } catch (\Throwable $e) {
            $narrative->refresh();
            if ($narrative->status !== ProgramNarrative::STATUS_FAILED) {
                $narrative->update([
                    'status' => ProgramNarrative::STATUS_FAILED,
                    'error_message' => 'Narrative unavailable — '.$e->getMessage(),
                    'generated_at' => now(),
                ]);
            }
        }

        return $narrative;
    }
}
