# Guide 02 — Targets: the Two Levels (with real examples)

**Role: Admin (Director)** · Path: **University Targets** (sidebar) and the project hub's **Overview** tab
· Prerequisite: guide 01 (SIKAD-DIGITAL exists)

> **This guide replaces the old "Objectives & the Results Framework" guide.** The
> objective manager and the results framework were removed from the UI (D-R7).
> What replaced them is the **target model**: targets exist at exactly **two**
> levels — the university pool and each project — and everything else is a
> derived roll-up.

## The rule, in one table

| Level | Carries a target? | What you see |
|---|---|---|
| **University** | ✅ yes — an annual **pool** of training hours and budget | Targets page: pool vs actuals, consumption, progress across the year |
| **Project** | ✅ yes — annual target hours and budget | Hub Overview: rendered vs target, utilized vs target, attainment % |
| College | ❌ no | Derived roll-ups only (projects, hours delivered, reach, budget used) |
| Broad program | ❌ no | Derived roll-ups only (project count, hours, reach, budget used) |
| Faculty | ❌ no | Contribution figures only (hours rendered, projects led) — no attainment % |

**Why the pool is a pool.** The university target is *consumed*, not averaged:
each project's **actual** hours are subtracted from it. Project-level targets are
planning figures and are deliberately **not** summed to produce the annual
target — otherwise the two numbers would double-count each other.

---

## Part A — Set the university pool

1. Log in as **admin@lnu.com**.
2. Sidebar → **University Targets** (in the **Overview** section).
3. Pick the academic year from the dropdown at the top-right.
4. Click **Set targets** (or **Edit targets** if a pool already exists).

| Field | Value to type |
|---|---|
| Annual training hours * | `2500` |
| Annual budget target (₱) * | `668000` |
| Notes | `CESO annual commitment, AY 2026-2027` |

5. Click **Save target**.

**Expected:**
- The header now reads "The institutional commitments CESO reports against —
  training hours and budget for **AY 2026-2027**".
- **University targets vs actuals** shows the pool, the summed actuals across
  projects, and the remaining balance. On the seeded data the pool is
  **2,500 hours / ₱668,000** with a small share consumed.
- **Progress Across the Year** charts how much of the pool has been consumed.
- The **Training Hours Formula** panel states `trainors × trainees × days` and
  says explicitly that there is **no × 8** factor.
- An activity-log entry records the change with your name and timestamp (8.1).

**Things to try:**
- Leave a field blank and save → that target is **cleared**, not zeroed. A blank
  is "not set"; 0 would be a claim that the university intends to deliver
  nothing.
- Edit the same year again → it **updates** the row rather than creating a
  second one (one pool per academic year).
- Re-open the page → **Project targets** lists every project with its own
  attainment, so you can see the pool being consumed by name.

---

## Part B — See a project's own target

1. Sidebar → **Manage Extension Programs** → click the **CAS** card.
2. In that college's project grid, open **BUSOG: School-Based Supplementary
   Feeding & Nutrition Program** (`CAS-2026-001` — the seeded project that
   carries targets).
3. Read the **Overview** tab.

**Expected:**

| Figure | Value |
|---|---|
| Training hours rendered | **134.5** of **150** → **89.7 %** |
| Budget utilized | **₱78,000** of **₱85,000** |
| Trainors / Trainees / Activities | 1 / 30 / 3 |

- The **Training Hours vs Annual Target** bar fills to 89.7 %.
- The **Budget vs Allocated Budget** doughnut's centre reads the utilized share of the
  allocation (91.8 %). Budget has no annual *target* — the allocation is the basis.
- The project's targets came from the create/edit form (guide 01), and they are
  editable there — there is no separate "objectives" screen.

**Also check:** the project hub has **no objectives list, no KPI dictionary and
no results framework**. If you see one, that is a regression.

---

## Part C — What is deliberately absent

- **No per-program target.** `/programs` shows roll-ups (project count, hours
  delivered, reach, budget consumed) and carries a D-R5 notice saying so. The
  prototype's `programs.html` still renders per-program targets and attainment —
  the prototype is **stale** there, and Laravel is right.
- **No per-college target.** The hub's college cards show roll-ups only.
- **No per-faculty target.** A professor's page shows contribution, never an
  attainment percentage — there is no denominator, so any % would be invented.
- **No objective manager.** `ProgramObjective` and `KpiService` still exist in
  the codebase (retained unread, R-Q2) so historical rows stay inspectable, but
  no screen reads them.

## Report back

| Check | Pass? |
|---|---|
| University pool saved (2,500 h / ₱668,000) and shown vs actuals | ☐ |
| A blank field clears a target rather than zeroing it | ☐ |
| Editing the same AY updates rather than duplicates | ☐ |
| BUSOG shows 134.5 / 150 hrs → 89.7 % | ☐ |
| `/programs` shows roll-ups but **no** target column | ☐ |
| No objectives/KPI surface anywhere on the project hub | ☐ |
