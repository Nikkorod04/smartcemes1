<?php

namespace App\Services\Ai\Prompts;

use App\Models\InteragencyAgency;

/**
 * Prompt set v2 (Phase R6, feedback #8 and #9).
 *
 * WHY V2 EXISTS AND V1 IS KEPT
 * ----------------------------
 * V1 let the model recommend anything that sounded like community work. The
 * adviser's objection (feedback #8) was that this produced recommendations CESO
 * cannot deliver — feeding programmes, medical missions, construction — because
 * the model had no statement of CESO's actual mandate to reason against.
 *
 * V2 fixes that by telling the model three things it was never told:
 *
 *   1. **The CESO scope** — the six thrusts (§3) are the ONLY interventions the
 *      model may propose as CESO work.
 *   2. **The prohibition list** — needs that are real but are NOT CESO's to
 *      deliver must never be presented as CESO interventions.
 *   3. **The agency catalogue** — the closed vocabulary for handing those needs
 *      off. The model may cite ONLY these agencies, by code.
 *
 * V1 is deliberately left in place and untouched. `metadata.prompt_version`
 * records which set produced each analysis, so a historical analysis remains
 * reproducible and a reviewer can see what the model was actually asked. Deleting
 * V1 would make every pre-R6 analysis un-auditable.
 *
 * THE THREE-TIER RULE (§7.1) IS THE WHOLE DESIGN
 * ----------------------------------------------
 *   Tier 1 — direct CESO intervention  -> `recommendations[]`
 *   Tier 2 — interagency referral      -> `interagency_referrals[]`
 *   Tier 3 — prohibited as CESO work   -> may appear ONLY as Tier 2
 *
 * The schema enforces the split structurally: the two tiers are separate arrays,
 * so a prohibited need cannot occupy a CESO recommendation slot without the
 * model violating an explicit instruction. `AssessmentAnalysisService` then
 * validates the referrals against the live catalogue, so a hallucinated agency
 * cannot survive into the UI.
 *
 * D3 (aggregation-before-send) IS UNCHANGED: only aggregate statistics are
 * transmitted. The catalogue is public institutional information, not personal
 * data, so appending it does not weaken the privacy posture.
 */
class PromptV2
{
    /**
     * The six CESO training thrusts (§3).
     *
     * Verbatim thrust names, each tagged with its pillar. This is the model's
     * definition of "what CESO does" — the single most important addition in V2,
     * because without it the model reasonably treats every community need as
     * CESO's responsibility.
     */
    public const CESO_PROGRAMS = [
        ['program' => 'Literacy, Numeracy & Language', 'pillar' => 'Social'],
        ['program' => 'Information, Communication & Education', 'pillar' => 'Social'],
        ['program' => 'Cultural Development', 'pillar' => 'Social'],
        ['program' => 'Physical Fitness & Sports Development', 'pillar' => 'Social'],
        ['program' => 'Livelihood, Technical & Business Management', 'pillar' => 'Economic'],
        ['program' => 'Environmental Conservation & Disaster Preparedness', 'pillar' => 'Environmental'],
    ];

    /** The three pillars the six programs are distributed across. */
    public const CESO_PILLARS = ['Social', 'Economic', 'Environmental'];

    /**
     * Tier 3 — needs that must NEVER be presented as CESO interventions (§7.1).
     *
     * Each entry pairs the thing with why it is out of scope, because a bare word
     * ("feeding") invites the model to reinterpret it ("feeding *training*"), and
     * that reinterpretation is how the adviser's objection would reappear.
     */
    public const PROHIBITED = [
        ['item' => 'Supplementary or school feeding programmes', 'why' => 'service delivery, not training — belongs to DSWD'],
        ['item' => 'Medical, dental or optical missions', 'why' => 'clinical service, not training — belongs to DOH'],
        ['item' => 'Construction or repair of facilities, roads, drainage or water systems', 'why' => 'infrastructure works, not training — belongs to DPWH or the LGU'],
        ['item' => 'Water potability testing or water system operation', 'why' => 'regulatory/technical service — belongs to the LGU or DOH'],
        ['item' => 'Direct cash, food or material assistance', 'why' => 'relief distribution, not training — belongs to DSWD'],
        ['item' => 'Free medicines, vaccines or clinical treatment', 'why' => 'health service, not training — belongs to DOH'],
        ['item' => 'Coastal or river clean-up as a CESO activity', 'why' => 'one-off service delivery, not training — belongs to DENR with the LGU'],
        ['item' => 'Employment or job placement', 'why' => 'labour service, not training — belongs to TESDA or DOLE'],
    ];

    /**
     * CESO's own published Community Outreach categories (§3), reclassified as
     * referral buckets rather than CESO programmes.
     *
     * Included in the prompt so the model understands WHY the needs below are
     * Tier 2 and not an omission from the CESO list — CESO does prioritise them,
     * it simply does not deliver them as training.
     */
    public const RECLASSIFIED_OUTREACH = [
        'Food and Nutrition / Health and Sanitation / Maternal and child-care' => 'DSWD, DOH, LGU',
        'Medical / Dental / Optical Missions' => 'DOH, LGU',
        'Clean and Green Community / Coastal Clean-up' => 'DENR, LGU, DPWH',
    ];

    /**
     * The community needs analysis prompt.
     *
     * @param  array  $aggregates  aggregate statistics only (D3)
     * @param  array|null  $catalogue  rows from `InteragencyAgency::promptCatalogue()`;
     *                                 null/empty falls back to reading the table
     */
    public static function assessmentAnalysis(array $aggregates, ?array $catalogue = null): string
    {
        $json = json_encode($aggregates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $catalogue = $catalogue ?? InteragencyAgency::promptCatalogue();

        $programs = collect(self::CESO_PROGRAMS)
            ->map(fn ($p) => "- {$p['program']} ({$p['pillar']})")
            ->implode("\n");

        $prohibited = collect(self::PROHIBITED)
            ->map(fn ($p) => "- {$p['item']} — {$p['why']}")
            ->implode("\n");

        $outreach = collect(self::RECLASSIFIED_OUTREACH)
            ->map(fn ($agencies, $category) => "- {$category} -> {$agencies}")
            ->implode("\n");

        $catalogueLines = collect($catalogue)
            ->map(fn ($a) => "- {$a['agency_code']} ({$a['agency_name']}) — handles: {$a['need_category']}. Services: {$a['sample_service']}")
            ->implode("\n");

        // An empty catalogue is a deploy/seed error, not a silent condition: with
        // no citable agencies the model would either invent one or downgrade a
        // Tier-2 need to a CESO recommendation. Both are unacceptable, so say so
        // explicitly and forbid referrals rather than leaving a hole.
        $catalogueSection = $catalogueLines !== ''
            ? $catalogueLines
            : '(THE AGENCY CATALOGUE IS EMPTY — return an empty "interagency_referrals" array and do not name any agency.)';

        // The allow-list is restated as a literal so the model sees the exact
        // strings it must return; the validator enforces the same set server-side.
        $allowedCodes = $catalogueLines !== ''
            ? implode(', ', array_column($catalogue, 'agency_code'))
            : '(none)';

        return <<<PROMPT
You are an extension-services analyst for Leyte Normal University's Community Extension Services Office (CESO). You receive AGGREGATE household-survey statistics for one barangay community for one quarter — never individual records, never personal information. Use the aggregates only.

## What CESO actually delivers

CESO delivers COMMUNITY TRAINING, organised under six thrusts. These are the ONLY interventions you may propose as CESO work:

{$programs}

The three pillars are: Social, Economic, Environmental.

## What CESO must NEVER be recommended to deliver

The community needs below are REAL and must not be ignored — but CESO must never be presented as the body that delivers them. They are service delivery or infrastructure, not training, and they sit outside CESO's mandate:

{$prohibited}

CESO's own published Community Outreach categories cover some of these needs. They are not omitted from CESO's priorities; they are delivered through other agencies:

{$outreach}

## How to classify every need — exactly one tier

- Tier 1: a CESO training intervention under one of the six thrusts. Goes in "recommendations".
- Tier 2: a real need outside CESO's training mandate. Goes in "interagency_referrals", citing an agency from the catalogue below. A Tier-2 need may ALSO have a Tier-1 training component — for example, a malnutrition finding can yield (a) a CESO nutrition-and-parenting training recommendation AND (b) a DSWD feeding referral. Both, separately.
- Tier 3: never a CESO intervention. May appear ONLY as a Tier-2 referral.

## The agency catalogue — cite ONLY from this list

{$catalogueSection}

You may reference ONLY the agencies above, and you must return the exact agency_code. Allowed codes: {$allowedCodes}
If a need has no matching agency in this catalogue, do NOT invent an agency and do NOT convert it into a CESO recommendation. Leave it out of interagency_referrals.

## Output

Return strict JSON with exactly these keys:

{
  "summary": "3-6 sentence situation assessment of the community",
  "problems_identified": [{"need": "...", "evidence": "which aggregate supports this", "tier": "1|2|3"}],
  "recommendations": [{"rank": 1, "title": "...", "detail": "...", "priority": "High|Medium|Low", "ceso_program": "one of the six thrusts above"}],
  "interagency_referrals": [{"need": "...", "agency_code": "...", "agency_name": "...", "rationale": "one sentence grounded in the aggregates"}]
}

Rules:
- "problems_identified": 3-6 priority needs ranked by prevalence, each tagged with the tier you assigned it.
- "recommendations": EXACTLY 5 CESO interventions, rank 1-5, each grounded in the aggregates and each naming the CESO thrust it falls under. Every one must be TRAINING that CESO could deliver.
- "interagency_referrals": 0-5 entries. Use an EMPTY ARRAY when no need falls outside CESO's mandate — a community with only training-shaped needs legitimately produces none. "agency_code" must be one of the allowed codes, and "agency_name" must match that code's name in the catalogue.
- NEVER place a prohibited item above in "recommendations". If you find yourself wanting to, it is a Tier-2 referral instead.
- Do not invent numbers not present in the aggregates; do not mention individuals.
- Keep a professional, concise institutional tone.

AGGREGATES:
{$json}
PROMPT;
    }

    /**
     * The programme narrative prompt — carried forward from V1 unchanged.
     *
     * R6 did not revise this surface: project narratives describe training-hours
     * delivery, which the R4/R5 dictionary already covers correctly. It lives
     * here rather than in V1 so a v2 analysis is fully reproducible from one
     * class, and `GenerateProgramNarrative` is pointed at this copy.
     */
    public static function programNarrative(array $aggregates): string
    {
        $json = json_encode($aggregates, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<PROMPT
You are an executive analyst for Leyte Normal University's Community Extension Services Office. You receive AGGREGATE project-level data for one extension project (training hours, trainors, trainees, activity completion, budget against the project's allocation) — never personal data.

About the metrics you are given:
- Training hours are computed as trainors x trainees x days. There is NO hourly multiplication factor; `days` already carries the duration, so a half day is 0.5.
- `hours_attainment_pct` is against the project's ANNUAL HOURS TARGET. A null means no target has been set — in that case say so and do NOT treat it as zero.
- `budget.utilization_pct` is against the project's ALLOCATED BUDGET. A project has NO annual budget target — the allocation IS the budget figure. Never call the allocation a "target" or a "budget target", and never say budget attainment.
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
- Every recommendation must be a CESO TRAINING intervention under one of CESO's six thrusts. Never recommend feeding programmes, medical missions, construction, clean-ups, relief distribution or clinical services — CESO does not deliver those; refer them instead.
- health_label: on-track when training hours are progressing toward the annual hours target, activities are completing, and budget is within its allocation; needs-attention when hours are well short of the target at this point in the period, activities are overdue, or budget is over its allocation; at-risk in between. Where no annual hours target is set, grade on activity delivery and budget only, and say the target is unset.
- risks: exactly 3 items unless the data clearly supports fewer.

AGGREGATES:
{$json}
PROMPT;
    }
}
