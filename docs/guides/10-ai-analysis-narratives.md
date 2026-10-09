# Guide 10 — AI Analysis (Community Insights) & Project Narratives (worked example)

**Role: Admin ONLY** (D4 — Secretary and Faculty have no AI access; visiting
`/ai-analysis` as them yields 403) · Paths: **AI Analysis Review** and
**Project Narratives**

One governed pipeline, two outputs: **community assessment insights**
(approval-gated, institutional use) and **project narratives** (no gate,
Director-internal decision support). Everything sent to Google Gemini is
**aggregates only** — counts, percentages, distributions. Never respondent
names/PII (D3, DPA).

**Best precondition:** a Secretary friend has validated a few assessments
first (each validation recomputes that community's quarterly summary — the
summaries are what the AI consumes). `GEMINI_API_KEY` must be set.

---

## Part A — Community insights (/ai-analysis)

### A0. The page is a QUEUE, not a form

`/ai-analysis` lists **every analysis grouped by community**, plus every validated summary
still awaiting one. There is **no summary picker** — generation starts from the row that
says a summary is waiting.

- **Search** a barangay in the box (`?q=` — partial and case-insensitive, so `san jo`
  finds *Brgy. San Jose*), or narrow with the chips: *Awaiting review · Approved · Failed ·
  Generating · Discarded · Awaiting analysis*.
- **Generating** is the chip for a row left `pending`. Generation is synchronous, so a row
  still pending was **interrupted** (a timeout against the client's 120s budget, a fatal, an
  aborted request) and will never finish on its own. Such a row offers **Start a new
  generation** — it is the only way out, because `retry()` accepts only a `failed` generation.
- Each **community name is a link** → its own history page
  (`/ai-analysis/community/{id}`): every period, every generation, with Generate, Retry,
  **Delete** and a bulk **Clear failed**. Deleting is **scoped** — an approved analysis
  (citable in reports) and the live draft cannot be deleted.
- Any row opens in **any** state, including approved (read-only) and discarded.

### A1. Generate

1. Log in as **admin@lnu.com** → **AI Analysis Review**.
2. Find the community — e.g. a **Brgy. San Jose** row (seeded assessments exist), or
   search for the barangay whose quarter your friends just validated.
3. Click **Generate** on the row marked **awaiting analysis** — the button swaps to a
   spinner ("Generating…"); generation is **synchronous**, so results appear in-request
   (a few seconds) and you are taken to that analysis's own page.

**Expected:** a **draft** analysis appears with:
- **Situation summary** of the community.
- **Priority needs** identified from the aggregates.
- **Top recommended interventions** with brief rationale — collapsible rows, with the
  **High**-priority ones left open.
- A **confidence chip** — a derived *data*-confidence (sample size +
  coverage), never the model bragging about itself.

**If the AI service is down:** the row turns **failed** with a readable reason
("AI request failed — HTTP 503…") and a **Retry** button, and the error is persisted.
That is designed behavior (D12 — live API only, no mock mode), not a broken page.
**Regenerate** on an analysis page creates a NEW generation and keeps the previous one, so
a regeneration is auditable rather than destructive.

### A2. The three-tier scope guardrail — the important part

This is what stops the AI recommending work CESO cannot do. Every
recommendation is classified **before it is shown**:

| Tier | Meaning | How it appears |
|---|---|---|
| **1** | CESO can deliver this itself | A normal **recommended intervention** |
| **2** | A real need, but **not CESO's work** | An **interagency referral** card, naming an agency |
| **3** | Prohibited as CESO work | **Suppressed** — audit note only, never a recommendation |

**Expected on a good run:** most cards are Tier 1; a few are Tier 2 and carry a
*"Refer to &lt;agency&gt;"* note. A need CESO cannot serve is **reclassified as
a referral, never silently dropped** — that is the whole point of the guardrail.

**Check the agency names.** A Tier-2 card may only cite an agency that exists in
the **Interagency Catalogue** (sidebar → *Interagency Catalogue*, in
Intelligence & Reports). The model may not invent one. Try it:

1. Sidebar → **Interagency Catalogue**. Eight agencies are seeded.
2. Note one you would expect to see, e.g. a health or agriculture agency.
3. Back on the analysis, confirm the referral names a real catalogue entry.

### A3. The DPA audit view

Open the **Community response data** accordion on the draft.

**Expected:** the EXACT aggregate payload that was sent to the API — key
indicators (electricity %, training availability %, avg satisfaction) and
distribution lists with counts/percentages. **No respondent names anywhere**
— this is the privacy audit view: aggregation-before-send, verifiable.

### A4. Approve or discard (the gate)

1. Click **Approve**.

**Expected:** the draft is stamped **Approved** with you as the approving
officer; approved analyses carry the "Approved" provenance label (this is
what institutional/community-facing use requires).

2. Generate another and click **Discard** on that one.

**Expected:** the draft is discarded; it remains in history with its state.

### A5. History & provenance

Look at the history table.

**Expected:** every generation lists the **model**, **prompt version**,
generated-at/by, status, and (when approved) the **approving officer** —
full provenance for audit.

---

## Part B — Project narratives (executive summaries)

### B0. The page is a searchable index

**Project Narratives** lists **one card per project** — including projects with no narrative
yet, which is a first-class state rather than an omission.

- **Search** (`?q=`) matches the project's **title, code, lead or community** — so `kultura`,
  `CAS-2026-002`, a lead's name or a barangay all find the same project.
- **Chips** filter by the project's *latest* narrative: *Not generated · Generating · Failed ·
  Needs attention · At risk · On track*, each with a live count. The counts describe **what is
  on screen**, so a search narrows them too.
- Cards are ordered **attention-first** — whatever needs you is at the top — and there are
  **8 projects a page**.
- A card's **body collapses** (click the title). The header and the metric strip — *trainors ·
  trainees · training hrs vs the annual target · activities* — always show. **"Not generated",
  "Generating" and "Failed" never collapse**, so a failure reason is never behind a click.

### B1. Generate

1. Go to **Project Narratives** (or open a project hub and click
   **Generate narrative** — e.g. on **CAS-2026-002 KULTURA**).
2. Click **Generate** on the card.

**Expected:** an executive summary card with:
- A **health label** — On track / At risk / Needs attention (derived by the
  model from the **target-model** inputs).
- Summary text, **top risks**, and **next actions** with priority.
- Delivery figures for the project: **training hours rendered vs the annual HOURS target**,
  **budget utilized vs the project's ALLOCATION**, and **trainees**. A project has **no annual
  budget target** (v4.19) — the allocation is the denominator, and no surface may call it a target.
- A provenance footer (model · prompt version · generated by/at).

> **The old "Objectives met: X/Y" chip is gone.** It derived from the 8.6
> objective machinery, which was removed from the UI (D-R7). What you see
> instead is training-hours attainment against the project's **annual HOURS
> target** — the same number as the hub's Overview tab.

> **If it fails** you get a red **"Narrative unavailable"** card carrying the reason, and the
> toast reports an *error* — never a green "generated" over a failed card. Click **Generate**
> again to retry; each attempt is a new row, so the failed one stays in the version history.

### B2. Version history (no gate)

1. Click **Generate** again for the same project.

**Expected:** a **new version** is created — nothing is overwritten; open **Version history**
on the card to compare. Narratives have **no approval
gate**: they are Director-internal decision support (contrast Part A's
gate), but provenance is still recorded.

### B3. Cross-check the inputs

Open the **KULTURA** hub → Overview while reading the narrative.

**Expected:** the narrative's budget / hours / trainee statements match the hub's
live numbers — both come from `TrainingHoursService`, so they cannot disagree.

---

## Talking points if anyone asks

| Question | Answer |
|---|---|
| What data leaves the system? | Aggregates only — the accordion shows the exact payload (D3). |
| What if the AI suggests something CESO cannot do? | It is **reclassified** as a Tier-2 interagency referral, with a named agency from the catalogue. It is never dropped and never presented as CESO work (D-R8). |
| Can the AI invent an agency? | No — the catalogue is the closed vocabulary; an unresolvable code is dropped and counted, never laundered. |
| Why approve analyses but not narratives? | Analyses feed institutional/community-facing use (gate, D4); narratives are Director-internal (5.15). |
| What if Gemini fails? | First-class "unavailable" state + Retry + persisted error — no mock mode (D12). |
| Is the confidence the AI's? | No — a deterministic data-confidence (sample size + coverage), clamped, guidance-only. |

---

## Report back

| Check | Pass? |
|---|---|
| Generate (spinner) → draft with needs + interventions | ☐ |
| Confidence chip present (data-derived) | ☐ |
| **Tier-2 referrals name a real Interagency Catalogue agency** | ☐ |
| **No Tier-3 item appears as a recommendation** | ☐ |
| DPA accordion shows aggregates only, no names | ☐ |
| Approve stamps approver; Discard works | ☐ |
| History shows model + prompt version + approver | ☐ |
| Narrative: health label + attainment vs annual targets | ☐ |
| Regenerate → new version, nothing overwritten | ☐ |
| (If API failed) clean "unavailable" state + Retry | ☐ |
