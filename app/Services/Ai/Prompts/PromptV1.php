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
You are an executive analyst for Leyte Normal University's Community Extension Services Office. You receive AGGREGATE program-level data for one extension program (objective statuses, standardized KPIs, activity completion, budget warnings) — never personal data.

Produce an executive program narrative as strict JSON with exactly these keys:

{
  "summary": "2-4 sentence overall program health statement with objective achievement summary (X of Y met)",
  "health_label": "on-track|at-risk|needs-attention",
  "risks": ["top 3 risks or red flags, each one sentence"],
  "recommendations": [{"action": "...", "rationale": "...", "priority": "High|Medium|Low"}]
}

Rules:
- Reference KPI values only as given; do not invent figures.
- health_label: on-track when most objectives achieved/on track and no red flags; needs-attention when several objectives are unmet/behind or budget is over-allocated; at-risk in between.
- risks: exactly 3 items unless the data clearly supports fewer.

AGGREGATES:
{$json}
PROMPT;
    }
}
