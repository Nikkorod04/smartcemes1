# Guide 05 — Budget Utilization & the Over-Allocation Warning (with real examples)

**Role: Admin** · Path: project hub → **Budget** tab
· Prerequisites: guides 01–04 (SIKAD-DIGITAL with activities + beneficiaries)

You will record expenses, watch utilization climb 70% → 85% (flipping an
objective to Achieved live), then deliberately over-allocate to see the D7
warning.

## 1. Add these 3 budget entries

On the **Budget** tab, click **Add entry** and fill each row:

| Item name | Amount | Date used | Charge against (activity) | Receipt reference |
|---|---|---|---|---|
| `Internet & venue allowance` | 3500 | 2026-09-05 | Digital Skills Pre-Assessment & Orientation (A1) | *(blank)* |
| `Training kits & handouts` | 8500 | 2026-09-12 | Hands-on Workshop: e-Gov Services & Online Safety (A2) | *(blank)* |
| `Facilitator meals & transport` | 2000 | 2026-09-12 | Hands-on Workshop: e-Gov Services & Online Safety (A2) | `OR-2026-114` |

Notes on the form:
- Entries attach to the **project** (required) and optionally to an
  **activity** — pick from the dropdown of this project's activities.
- Leave **Receipt reference** blank and the system auto-generates one like
  `REC-2026-0004`; or type your own (e.g. `OR-2026-114`).

**Expected:** after the three saves, the tab shows **Utilized ₱14,000** of
the ₱20,000 allocation — a **70% progress bar** — with **no warnings**
(still under allocation). Entries are listed newest-first with their
activity links.

## 2. Check the attainment move (live derivation payoff)

1. Go to the hub **Overview** tab. **Budget vs Allocated Budget** reads
   **₱14,000 of ₱20,000 → 70%**.
2. Back on **Budget**, add a 4th entry:

| Item name | Amount | Date used | Charge against | Receipt |
|---|---|---|---|---|
| `Printing of certificates` | 3000 | 2026-10-03 | Digital Bayanihan: Community Tech Helpdesk (A3) | *(blank)* |

3. Refresh the **Overview** tab.

**Expected:** utilization jumps to **₱17,000 / 85%** and the Overview's
**Budget vs Allocated Budget** doughnut follows it immediately — nothing is stored,
the ring is recomputed from the entries on every render. The project's attainment on
the hub, on `/projects`, and in the university pool's consumption all move to
match, because they read the same service.

## 3. Trigger the over-allocation warning (D7)

Add one more entry to deliberately exceed the ₱20,000 allocation:

| Item name | Amount | Date used | Charge against | Receipt |
|---|---|---|---|---|
| `Additional gadgets & headsets` | 10000 | 2026-10-03 | *(none — project only)* | *(blank)* |

**Expected:**
- The entry **SAVES** — over-allocation never blocks (D7) — but the toast
  warns: *"Warning: allocation exceeded — entry saved with over-allocation
  flag (D7)"*.
- A persistent **over-allocation warning banner** shows on the project
  (utilized ₱27,000 = 135% > ₱20,000 allocated), and the project list card
  carries an over-budget badge.
- An **audit-log entry** records the overrun (visible in the admin
  dashboard's recent activity feed).

### Seeded example to compare

Open **EXT-2026-002 HANDA** from the Projects list — it is seeded
**over-allocated by exactly ₱2,000** (utilized ₱64,500 vs allocated
₱62,500) so you can see the persistent warning state without creating one.

## 4. Clean up (optional)

Delete the ₱10,000 overrun entry (**Remove** on the row) if you want the
project back under allocation.

**Expected:** toast "Entry removed"; the banner disappears once utilization
is back under the allocation.

## Report back

| Check | Pass? |
|---|---|
| 3 entries save; utilized ₱14,000 / 70% | ☐ |
| Blank receipt reference auto-generates REC-2026-NNNN | ☐ |
| 4th entry → 85% → O5 flips to Achieved live | ☐ |
| 5th entry saves WITH over-allocation warning (never blocks) | ☐ |
| Persistent banner + project-list badge appear | ☐ |
| HANDA seeded overrun shows the same state | ☐ |
