# After-Defense Revision Plan

**Created:** 2026-11-18
**Trigger:** Capstone panel defense — six required changes to the system and the manuscript.
**Window:** 6 working days.
**Deployment:** the system is live on an Azure VM. `APP_URL` there is HTTPS (confirmed by the project owner). Updates are pulled from this repository.

Status legend: **LOCKED** = agreed, safe to build against. **PROPOSED** = recommended, still needs an owner decision before the dependent code is written.

---

## 0. Ground rules

1. **The running system is the source of truth for the manuscript.** The paper describes it; the system is not changed to match stale paper text.
2. **No regressions.** Every existing test that passes today must still pass. The full suite is 637 test methods (~18–20 min); targeted suites run after each item, full runs twice at most (Day 5, Day 6).
3. **Every shipped item gets a `revisions.md` entry.** This plan is the intent; `revisions.md` remains the authoritative record of what actually changed.
4. **Docs stay honest.** `AI_HANDOFF.md`, `README.md`, and `SYSTEM_BLUEPRINT_V4.txt` are already behind six commits (through 2026-11-18). Anything built here is documented in the same pass.
5. **No new PHP dependencies.** `composer install` on the VM is avoided; the only new package is client-side (`qrcode`) and ships through the existing asset build.

---

## 1. Locked decisions

| # | Decision | Value | Status |
|---|---|---|---|
| D1 | Item 1 approach | Public web form (Option A). OCR is rejected, documented as future work. | LOCKED |
| D2 | Offline capture | Deferred. Online-first build; offline is a Day 6 stretch behind a go/no-go gate. | LOCKED |
| D3 | Public form architecture | Client-side (Alpine) form, one signed JSON `POST`. **Not** the Livewire wizard. | LOCKED |
| D4 | Deployment | Azure VM, HTTPS `APP_URL`, deployed by pulling this repo. | LOCKED |
| D5 | Share-link lifetime | **1–5 days**, chosen at generation (default 5), expiring end-of-day. A stored token, not a signed URL, so the link can be **extended/reopened** without changing the URL or printed QR, and **revoked** immediately. | LOCKED |
| D6 | Who can generate share links | **Secretary + Faculty**, any community (parity with the encode wizard). Admin not included. Every generation is logged. | LOCKED |
| D7 | AI/summary input | Summaries aggregate **validated** assessments only (implemented 2026-11-19; live totals 663 → 151). | LOCKED |
| D8 | Beneficiary edit scope | Secretary only; Admin keeps create/enroll/import. | LOCKED (implemented 2026-11-19) |
| D9 | Project-creation required set | ≥1 community, target beneficiaries, allocated budget, project lead, description (min ~20 chars). | LOCKED (implemented 2026-11-19) |
| D10 | Manuscript scope | Restructure Results & Discussion per Specific Objective **and** correct the factual contradictions (stack, hierarchy, import scope, test counts). | LOCKED (implemented 2026-11-19) |

### Why D3 matters

The existing staff wizard answers every chip with a server round trip (`resources/views/livewire/assessments/partials/field.blade.php:13-17`) and renders conditional fields server-side (`@if ($show[...])` in `wizard.blade.php`). That is fine in the office, but on mobile data it is ~60 requests per respondent and it cannot work offline. The public form therefore holds its own state in the browser and submits once. The staff wizard is left untouched.

---

## 2. Item 1 — Streamline needs-assessment collection

### The problem the panel saw

The instrument is ~60 questions across 9 sections. Today the answers reach the database only through a human: either the encode wizard or the XLSX template. Both are **transcription** — paper → eyes → keyboard → database. The system digitises the *result*, not the *collection*; the slow, error-prone step happens before the system is involved. Mistakes made during transcription are invisible because the import can only check that a value is an allowed option, not that it is true.

### Decision

Build an unauthenticated public form reachable by a shareable link and QR code. Data is born structured; no transcription step exists.

OCR (Option B) is rejected for this cycle: it still starts with paper, adds a guessing step for handwriting and scan quality, and a misread answer looks valid. Estimated 5+ days with real accuracy risk versus ~2 days for the form.

### Design

```
QR / link → public form (Alpine state) → one signed POST → needs_assessments (pending)
                                                              → Review queue (Public badge)
                                                              → Secretary validates
                                                              → summary (validated only) → AI → project
```

- **Share links are stored, not signed.** A new `assessment_links` table holds `community_id`, `quarter`, `year`, a random `token`, `expires_at`, `created_by`, `revoked_at`. The public URL is `GET /a/{token}`. This gives short URLs, instant revocation, an "Active links" list, and the ability to **extend/reopen** a link without changing the URL or reprinting a QR.
- **Lifetime:** the creator picks **1–5 days** (default 5); expiry is end-of-day. After expiry the page shows a friendly "link expired" screen, not a 403.
- **Route:** `GET /a/{token}` and `POST /a/{token}`, outside `auth`, `throttle` + honeypot. The token is the authorization; the endpoint is CSRF-exempt because there is no user session to protect.
- **Layout:** new mobile-first `resources/views/layouts/public.blade.php`, `noindex`.
- **Instrument:** rendered from `App\Services\AssessmentTemplate::COLUMNS` grouped by `config('smartcemes.assessment_sections')` — the same source of truth the wizard and the XLSX template use, so the instrument cannot drift.
- **Validation:** the rule builder currently inside `Wizard::rules()` is extracted to a shared service (e.g. `App\Services\AssessmentRules`) used by both the staff wizard and the public endpoint.
- **Context is locked** by the link: community, quarter, year. Respondents cannot change them, which keeps summaries comparable.
- **Consent** required before submit; thank-you screen; "Submit another response" resets the form for the next household.
- **Provenance:** one migration adds `source` (`encoded|import|public`), `client_uuid` (unique, nullable) and `captured_at` to `needs_assessments`.
- **Draft safety:** every answer is autosaved to `localStorage` (debounced) and restored on reload; cleared only after a confirmed submit. See the subsection below.


### Build steps and acceptance criteria

| Step | Work | Acceptance |
|---|---|---|
| 1 | Walking skeleton: link table + migration, route, public layout, stub page | Opens on a phone over mobile data with no login; unknown/expired token → friendly expired page |
| 2 | Form: renderer, Alpine state, conditional rules, progress, consent, draft autosave | All 9 sections complete with zero network calls until submit; reload restores answers |
| 3 | Submit: endpoint, provenance migration, idempotency, review badge, source filter, duplicate warning | Row appears as Public/Pending; identical `client_uuid` twice → one row; pending row does not move the summary |
| 4 | Share sheet: community + 1–5 day validity, link, QR, printable A4, Active links / extend / revoke | QR scanned from paper opens the right community; expired or revoked token is refused |
| 5 | **Deferred** — offline queue | See gate below |

### Draft safety and dropped connections

The form is client-side, so **nothing is sent until Submit**. That changes the failure model:

| Scenario | Behaviour |
|---|---|
| Connection drops while answering | No effect — the form never needed the server. Answers stay in the browser. |
| Respondent closes the tab / phone dies / accidental back | Answers are restored from `localStorage` on reopening the same link. A banner offers *Continue* or *Start over*. |
| Page reload or crash mid-form | Same restore path; the step and every answered field come back. |
| Submit fails (timeout, signal lost) | Nothing is cleared. The page shows *"Couldn't send — your answers are saved on this device"* with a **Retry** button; it also retries automatically when the browser reports it is back online. |
| Submit actually reached the server but the reply was lost | Retry is safe: the endpoint treats the same `client_uuid` as the same submission and returns success instead of inserting a duplicate. |
| Double-tap Submit | The button disables on first press; the unique `client_uuid` is the backstop. |
| Browser data cleared / private mode / different device | **Lost.** There is no account to recover from. Stated as a limitation; a "Clear saved answers" control and a short privacy note cover shared devices. |

Implementation: draft key `sc.assess.<token>`, value `{version, client_uuid, step, answers, saved_at}`, written debounced (~400 ms) and on step change / `visibilitychange` / `pagehide`. A 60-field draft is roughly 3–6 KB. It is removed only after a 2xx response.

Server side, `POST /a/{token}` accepts JSON, validates with the shared rules, and is **idempotent on `client_uuid`**: a repeat returns the existing record's id with 200 rather than creating a second row.

**What the office sees:** nothing until Submit succeeds. Drafts never create half-finished rows in the review queue.

### Offline (deferred) — go/no-go gate

At the end of Day 5, **all four must be true** to spend Day 6 on offline:

1. Full suite green.
2. Deployed and smoke-tested on Azure.
3. Chapter IV restructured with screenshots in place.
4. No open blocker from Items 2–5.

If any is red, ship online-only and state the limitation in the manuscript: *"Field capture requires connectivity; the official XLSX template remains available for offline batches, and device-side draft sync is identified as future work."*

If green, offline is additive: service worker for the public shell, pending counter, replay queue. The form, the token endpoint and `client_uuid` are already in place, so nothing is rewritten.

### Files

- `routes/web.php` — `GET|POST /a/{token}`
- `app/Http/Controllers/PublicAssessmentController.php` (new) — show, submit
- `app/Models/AssessmentLink.php` (new) — token, expiry, revocation, scope
- `database/migrations/*_create_assessment_links_table.php` (new)
- `database/migrations/*_add_provenance_to_needs_assessments.php` (new)
- `app/Services/AssessmentRules.php` (new) — rules extracted from the wizard
- `app/Livewire/Assessments/Wizard.php` — use the shared rules; no behaviour change
- `resources/views/layouts/public.blade.php` (new)
- `resources/views/assessments/public-form.blade.php` (new)
- `resources/views/livewire/assessments/share-links.blade.php` (new) — generate, list, extend, revoke
- `resources/views/livewire/assessments/review.blade.php` — Public badge + source filter
- `app/Livewire/Assessments/Review.php` — source filter, share entry point
- `resources/views/livewire/assessments/wizard.blade.php` — "Share form" button for faculty
- `resources/js/public.js` + `package.json` — Alpine entry + QR
- `tests/Feature/PublicAssessmentFormTest.php` (new)
- `tests/Feature/AssessmentLinkTest.php` (new) — expiry, revoke, extend, faculty access

### Paper wording

> Respondents may answer the needs assessment directly through a public, per-community link distributed as a QR code. Responses enter the same validation queue as encoded and imported records and are marked by collection source. Manual encoding and spreadsheet import remain available for offline batches.

---

## 3. Item 2 — Community needs-assessment directory (implemented 2026-11-19 — `revisions.md` §41)

**Goal:** inspect every submitted assessment for one community.

**Approach (PROPOSED):** extend the existing `Assessments\Review` component rather than building a second table.

- New route `GET /communities/{community}/assessments`, `auth` + `role:admin,secretary`, bound to `Review`.
- `mount(?Community $community)` sets a fixed community scope and a `readOnly` flag (Admin sees the list and dossier, not the Validate/Return actions).
- Filters: quarter, year, status, source; reuse the existing search and dossier drawer.
- Community detail drawer (`resources/views/livewire/communities/index.blade.php:297+`) gains an assessment count and a deep link.

**Why not a new component:** it would duplicate ~400 lines of dossier rendering and pagination for a different column set. One implementation, and the existing 10 `AssessmentReviewTest` cases keep passing if defaults are unchanged.

**Acceptance:** `/communities/{id}/assessments` lists only that community's rows; Admin cannot validate; Secretary can; the drawer links to it.

**Files:** `routes/web.php`, `app/Livewire/Assessments/Review.php`, `resources/views/livewire/assessments/review.blade.php`, `resources/views/livewire/communities/index.blade.php`, tests.

---

## 4. Item 3 — Edit beneficiary information (Secretary) (implemented 2026-11-19 — `revisions.md` §42)

**Goal:** correct a beneficiary profile after registration.

**Approach (PROPOSED):** edit the global registry record from the project-hub roster.

- New ability `editBeneficiary(User, ExtensionProject)` in `app/Policies/ProgramPolicy.php`, returning `isSecretary()`. This is deliberately narrower than `manageBeneficiaries` (Admin + Secretary).
- `Programs\Hub`: `openBeneficiaryEdit()` / `saveBeneficiaryEdit()`, reusing `Beneficiary::duplicatesFor()` (excluding self) and the existing trim/validation patterns.
- New edit modal in `resources/views/livewire/programs/partials/hub-modals.blade.php`; Edit action in the roster visible only to Secretaries.
- Edits are audited automatically (`Beneficiary` uses `LogsActivity` + `logFillable()`); add a project-level `activity()` entry for parity with registration.

**Acceptance:** Secretary edits and the change is visible everywhere the profile is used; Admin and Faculty get 403; renaming into an existing first+last+barangay raises the duplicate warning; validation rejects empty required fields.

**Note for the manuscript:** create/enroll remains Admin + Secretary, edit is Secretary-only. State this asymmetry explicitly so it does not read as an inconsistency.

**Files:** policy, `Programs\Hub`, hub modals + roster view, tests.

---

## 5. Item 4 — Clearer workflow and step progression

**Goal:** make the end-to-end sequence visually obvious.

**Approach:** a config-driven stepper, reused everywhere.

- `config/smartcemes.php` gains a `workflow` map (role → ordered steps with route + count key), matching how navigation is already config-driven.
- New `resources/views/components/sc/process-steps.blade.php` (compact and full variants).
- Role-aware sequences:
  - Faculty: Submit Proposal → (SO issued) → Encode/Share Assessment → Activities & Attendance → Rendered Hours
  - Secretary: Validate Assessments → Beneficiaries → Attendance/Evaluation → Budget entries
  - Director: Proposals & SO → Generate AI Insights → Create Project → Monitor → Reports
- Dashboards (`dashboard/admin|secretary|faculty.blade.php`) gain the stepper plus a "next action" card driven by counts already computed in `Dashboard.php`.
- In-page micro-steppers in Review, AI analysis, and the project hub.

**Acceptance:** each role sees its own sequence, the current step is visually distinct, and the next action links to a working route.

**Files:** config, new Blade component, three dashboards, `Dashboard.php`, the three in-page views.

---

## 6. Item 5 - System-wide form validation audit (implemented 2026-11-19 — `docs/VALIDATION-AUDIT.md`, `revisions.md` §45)

**Goal:** essential fields are marked and enforced on the client and the server.

**Deliverable:** `docs/VALIDATION-AUDIT.md` — form → field → DB nullability → backend rule → UI marker → covering test — plus fixes and a `ValidationAuditTest`.

### Known gaps (verified)

| Form | Location | Gap |
|---|---|---|
| Project creation | `Colleges\Index::saveProject` (376–392) | target beneficiaries, allocated budget, project lead, communities all `nullable`/`array`; description optional |
| Project creation UI | `colleges/index.blade.php:818` | Status is required server-side but its label has no `*` |
| Activity | `Programs\Hub` (771–785) | venue, participants, trainers, budget optional |
| Proposal | `Proposals\Create` (37–45) | budget estimate and description optional |
| Beneficiary register | `Programs\Hub` (1302–1308) | age, municipality, phone optional |
| Assessment wizard | `Assessments\Wizard` (121–128) | only names required; age/sex/civil status optional |
| Targets | `Targets` (176–179) | both figures optional — **intentional** ("no target" must remain representable) |

**Required set for project creation (PROPOSED, D9):** ≥1 community, target beneficiaries, allocated budget, project lead, description (min ~20 chars).

**Risk:** tightening rules breaks fixtures in `ExtensionHubTest` (23), `CollegeProgramCrudTest` (18), `ProgramHubTest` (28) and the seeders. That is where most of the 1–1.5 days goes.

---

## 7. Item 6 - Manuscript: Results & Discussion per objective (implemented 2026-11-19 — `revisions.md` §43)

**Goal:** the chapter answers each Specific Objective explicitly (Section 1.3 lists six).

### Structure

One subsection per objective, each with the same five parts:

1. **Requirement** — restate the objective in one sentence.
2. **Implementation** — what was built, screens, files.
3. **Evidence** — tests, counts, dates, screenshots.
4. **Discussion** — why this satisfies the objective; what changed from the proposal.
5. **Limitation** — what it does not do.

Objectives: (1) centralized MySQL extension database; (2) spreadsheet import and field mapping; (3) workflow, monitoring and scheduling modules; (4) LLM-assisted analytics; (5) AI-assisted summarisation and reports; (6) ISO/IEC 25010 evaluation (functional suitability, performance efficiency, usability, reliability, security).

### Factual corrections to make in the same pass

- Vue.js → **Livewire 3 + Blade + Alpine**.
- Google Cloud Vertex AI / OpenAI → **Google Gemini (flash-class)** via `GeminiClient`.
- "PDF and image multi-format handling / OCR" → **XLSX template only**; state it as a limitation.
- "Extension program" as the narrow entity → **College → Program → Project → Activity**; use *project* where the narrow entity is meant.
- "28 module-based tests" → 28 representative cases selected from the **637-method** suite (state the date).
- Black-box total says 29 but the narrative says 28 — reconcile.
- Remove SDG-metric claims (no SDG feature exists).
- Delete the duplicated Chapter 4.
- Reclassify recommendations into "implemented" vs "future work".
- Add this revision's features to the implementation discussion: public form, community directory, beneficiary edit, workflow stepper, validation audit.

---

## 7a. Item 7 - Attendance roster and anonymous evaluation QR (implemented 2026-11-20 - `revisions.md` 49-50)

**Why:** attendance and evaluation were both XLSX round-trips for a session that is happening now.

**Phase 1 - attendance roster.** `/activities/{activity}/attendance` for Admin/Secretary: the enrolled
roster, preloaded statuses, tap Present/Late/Absent/Excused, mark all present, search, sticky save. It
writes the same `Attendance` rows and date as the import, so trainee counts and training hours stay
consistent. The import remains the bulk/legacy path.

**Phase 2 - evaluation QR.** `/activities/{activity}/evaluation` for Admin/Secretary: a 1-7 day session
link, QR and printable card, live count, extend, revoke, and the public aggregate. `/e/{token}` is the
anonymous public form: satisfaction 1-5 plus an optional comment, one POST, honeypot, `client_uuid`
idempotency, rate limits, 410 on expiry/revoke.

**Boundary:** public feedback never writes the activity's imported satisfaction or pre/post means. The
XLSX evaluation import stays the only per-beneficiary path. Phase 3 (one combined session screen and an
explicit reporting rule for the two satisfaction sources) is still open; the working rule is keep them
separate.

---

## 7b. Reports workspace refresh (implemented 2026-11-20 - `revisions.md` 52)

`/reports` is now a workspace: report catalog + shared scope (year, college, program, community) + an A4
landscape paper preview. The Annual report is restored as the flagship and the annual 500 (a summary
pointing at a deleted community) is fixed. Empty communities, zero-participation faculty, developer notes,
the placeholder narratives section, and the technical trainee-source column are removed; summaries and
signatures are trimmed to what a reader needs. Signature lines stay blank and read "Name and Signature".
The real PDF is rendered by **mPDF** (pure PHP): the workspace embeds it, page numbers and the letterhead
are repeated, and **Download PDF** returns the attachment. Browser printing of the HTML report remains
available.

---

## 8. Six-day schedule

| Day | Track A — system | Track B — manuscript |
|---|---|---|
| 1 | Item 1: skeleton + form | Chapter IV outline by objective |
| 2 | Item 1: submit, provenance, share; Item 2 start | Objectives 1–3 |
| 3 | Item 2 finish; Item 3 | Objectives 4–5 |
| 4 | Item 4; Item 5 required fields | Objective 6 + limitations |
| 5 | Item 5 finish; full suite; screenshots | Insert evidence; correct stack/hierarchy claims |
| 6 | Buffer; **offline go/no-go**; defense rehearsal | Final proofread |

**Feature freeze:** Day 5 noon.

---

## 9. Risks

| Risk | Mitigation |
|---|---|
| Required-field changes break fixtures/seeders | Budget 1.5 days; update tests and seeders in the same commit as the rule change |
| Generated QR points at the wrong host | `APP_URL` must be the public HTTPS domain; prove with the walking-skeleton deploy on a real phone before printing anything |
| A printed poster outlives its link | Links can be extended/reopened without changing the URL; the expired page tells respondents to contact the office |
| `public/build` is gitignored | Confirm whether `npm run build` runs on the VM or assets are copied; the QR feature needs a rebuild either way |
| Public endpoint abuse | Signed link + throttle + honeypot + human validation before anything counts |
| Long form on a phone | 5-step progress, large tap targets, draft autosave, works on low-end Android |
| Duplicate submissions | Soft warning to Secretary (name + barangay + quarter); never auto-merge |
| Paper and build drift again | Update `revisions.md`, `AI_HANDOFF.md`, `README.md` in the same pass as the features |

---

## 10. Out of scope

- OCR / scanned-form ingestion.
- PDF or image assessment uploads.
- Full offline sync (unless the Day 5 gate passes).
- Offline validation (validation is a server-side state change with an audit trail; two validators offline would need conflict resolution).
- Vue.js or any frontend rewrite.
- SDG metrics.
- Production infrastructure changes beyond the normal pull-and-deploy.
