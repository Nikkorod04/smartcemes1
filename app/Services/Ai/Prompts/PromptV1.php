<?php

namespace App\Services\Ai\Prompts;

/**
 * Prompt set v1 (recorded in provenance metadata). Inputs are aggregate
 * statistics ONLY — never raw respondent data (D3).
 */
class PromptV1
{
    public static function assessmentAnalysis(array $aggregates): string
    {
        $json = json_encode($aggregates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
You are an extension-services analyst for Leyte Normal University's Community Extension Services Office (CESO). You receive AGGREGATE household-survey statistics for one barangay community for one quarter — never individual records, never personal information. Use the aggregates only.

Given the aggregate statistics below, produce a community needs analysis as strict JSON with exactly these keys:

{
  "summary": "3-6 sentence situation assessment of the community",
  "problems_identified": [{"need": "...", "evidence": "which aggregate supports this"}],
  "recommendations": [{"rank": 1, "title": "...", "detail": "...", "priority": "High|Medium|Low"}]
}

Rules:
- "problems_identified": 3-6 priority needs ranked by prevalence.
- "recommendations": EXACTLY 5 interventions, rank 1-5, each with one-sentence rationale grounded in the aggregates.
- Do not invent numbers not present in the aggregates; do not mention individuals.
- Keep a professional, concise institutional tone.

AGGREGATES:
{$json}
PROMPT;
    }

    public static function programNarrative(array $aggregates): string
    {
        $json = json_encode($aggregates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
You are an executive analyst for Leyte Normal University's Community Extension Services Office. You receive AGGREGATE project-level data for one extension project (training hours, trainors, trainees, activity completion, budget against the annual target) — never personal data.

About the metrics you are given:
- Training hours are computed as trainors x trainees x days. There is NO hourly multiplication factor; `days` already carries the duration, so a half day is 0.5.
- `hours_attainment_pct` and `attainment_pct` are against the project's ANNUAL TARGET. A null means no target has been set — in that case say so and do NOT treat it as zero.
- `trainees` may rest on imported attendance or on a manual count; `trainee_sources` tells you which. If it rests on manual entry, note that the figure is provisional.

Produce an executive project narrative as strict JSON with exactly these keys:

{
  "summary": "2-4 sentence overall project health statement referencing training hours against the annual target and activities completed",
  "health_label": "on-track|at-risk|needs-attention",
  "risks": ["top 3 risks or red flags, each one sentence"],
  "recommendations": [{"action": "...", "rationale": "...", "priority": "High|Medium|Low"}]
}

Rules:
- Reference only values present in the aggregates; do not invent figures.
- Never introduce an hourly factor of 8, and never mention objectives, KPI dictionaries, knowledge gain, cost per beneficiary or community reach — those metrics are retired and are not part of this project's data.
- health_label: on-track when training hours are progressing toward the annual target, activities are completing, and budget is within target; needs-attention when hours are well short of the target at this point in the period, activities are overdue, or budget is over target; at-risk in between. Where no annual target is set, grade on activity delivery and budget only, and say the target is unset.
- risks: exactly 3 items unless the data clearly supports fewer.

AGGREGATES:
{$json}
PROMPT;
    }
}
