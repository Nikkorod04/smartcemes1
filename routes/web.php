<?php

use App\Http\Controllers\ActivityAttendanceTemplateController;
use App\Http\Controllers\ActivityEvaluationTemplateController;
use App\Http\Controllers\AssessmentTemplateController;
use App\Http\Controllers\BeneficiaryExportController;
use App\Http\Controllers\BeneficiaryTemplateController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Livewire\AiAnalysis;
use App\Livewire\Assessments\Import;
use App\Livewire\Assessments\Review;
use App\Livewire\Assessments\Wizard;
use App\Livewire\AuditLogs\Index as AuditLogsIndex;
use App\Livewire\Availability\Index;
use App\Livewire\Calendar;
use App\Livewire\Colleges\Index as CollegesIndex;
use App\Livewire\Communities\Index as CommunitiesIndex;
use App\Livewire\Dashboard;
use App\Livewire\Faculty\Directory;
use App\Livewire\Faculty\EngagementBoard;
use App\Livewire\Faculty\Profile;
use App\Livewire\Interagency\Index as InteragencyIndex;
use App\Livewire\ProgramNarratives;
use App\Livewire\Programs\BroadPrograms;
use App\Livewire\Programs\Hub;
use App\Livewire\Programs\Index as ProgramsIndex;
use App\Livewire\Programs\MyPrograms;
use App\Livewire\Proposals\Create;
use App\Livewire\RenderedHours\My;
use App\Livewire\Targets;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Role dashboards (12.1) — one component, three role views
Route::get('/dashboard', Dashboard::class)
    ->middleware(['auth'])->name('dashboard');

/*
| The Admin Analytics page (`/analytics`, route `analytics.index`) was REMOVED
| by owner decision 2026-09-27 — see revisions.md. It was blueprint 5.13's "six
| dashboards in six tabs", but four tabs duplicated the admin dashboard, the
| Faculty tab was superseded by the R3 Faculty module, and the two unique parts
| (the aggregate Pending Actions list and the Community Reach chart) were
| accepted as losses. The individual queues keep their own pages below.
| Do not restore the nav entry without also restoring the route.
*/

// AI — Admin-only surfaces (D4): insights workspace + program narratives
Route::get('/ai-analysis', AiAnalysis::class)
    ->middleware(['auth', 'role:admin'])->name('ai-analysis.index');

Route::get('/program-narratives', ProgramNarratives::class)
    ->middleware(['auth', 'role:admin'])->name('program-narratives.index');

// Shared calendar (5.9)
Route::get('/calendar', Calendar::class)
    ->middleware(['auth'])->name('calendar.index');

// Institutional print reports (12.2) — Admin only
Route::middleware(['auth', 'role:admin'])->prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/annual', [ReportController::class, 'annual'])->name('annual');
    Route::get('/rendered-hours', [ReportController::class, 'renderedHours'])->name('rendered-hours');
    Route::get('/community-impact', [ReportController::class, 'communityImpact'])->name('community-impact');
    Route::get('/results-framework/{program}', [ReportController::class, 'resultsFramework'])->name('results-framework');
});

// Communities — Admin only (5.3)
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/communities', CommunitiesIndex::class)->name('communities.index');
});

/*
|--------------------------------------------------------------------------
| Revised hierarchy (v4.15 / Phase R1, names finalised in R2)
|--------------------------------------------------------------------------
| College → Program → Project → Activity.
|
| Route names now match the entities exactly:
|   programs.index → the BROAD level  (CESO thrusts, `programs` table)
|   projects.index → the PROJECT list (was `programs.index` before R2)
|   projects.show  → a single project hub   (was `projects.show`)
|   projects.my    → "my projects" for faculty (was `projects.my`)
|
| The `/extension-programs` path redirect is kept so bookmarks from the
| pre-R2 UI still resolve.
*/
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/colleges', CollegesIndex::class)->name('colleges.index');
    Route::get('/programs', BroadPrograms::class)->name('programs.index');
    Route::get('/projects', ProgramsIndex::class)->name('projects.index');

    // University Targets (Phase R4b, §4.7 / R-Q3) — the Director's annual pool.
    // Admin-only: it is the institutional commitment, not a project setting.
    Route::get('/targets', Targets::class)->name('targets.index');

    // Interagency Catalogue (Phase R6, §4.6 / D-R10) — the closed vocabulary
    // the AI may cite when it refers an out-of-scope need to another agency.
    // Admin-only: editing it changes what the model is permitted to say.
    Route::get('/interagency', InteragencyIndex::class)->name('interagency.index');

    // Audit Logs (owner request 2026-09-25) — the Director's record of every
    // approval, rejection, deletion, status change and import (D8). Admin-only,
    // and READ-ONLY: the component has no write action at all.
    Route::get('/audit-logs', AuditLogsIndex::class)->name('audit-logs.index');

    // Legacy path → new home (bookmark/compat bridge).
    Route::redirect('/extension-programs', '/projects');
});

/*
|--------------------------------------------------------------------------
| Faculty Management (revision §5 Phase R3 / D-R9)
|--------------------------------------------------------------------------
| The module the adviser highlighted. `faculty.index` was a PHANTOM nav entry
| before R3 — config/smartcemes.php pointed at a route that did not exist, so
| the sidebar item was silently hidden. Registering these two routes makes it
| appear.
|
|   faculty.index     → the Faculty Engagement board (the nav destination)
|   faculty.directory → the roster: filters, search, New/Edit profile modal
|   faculty.show      → one faculty member's full contribution (§6's 8 blocks)
|
| Admin-only: §6 frames the whole module as "what the Director sees". The
| FacultyPolicy additionally lets a faculty member view their OWN profile,
| which is why faculty.show sits in its own group below.
*/
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/faculty', EngagementBoard::class)->name('faculty.index');
    Route::get('/faculty/directory', Directory::class)->name('faculty.directory');
});

// A faculty member's own profile — policy-scoped, not role-scoped: the
// FacultyPolicy lets them see themselves and nobody else. Declaration is
// ordered after faculty.directory so `/faculty/directory` is not captured by
// this wildcard.
Route::middleware(['auth'])->group(function () {
    Route::get('/faculty/{faculty}', Profile::class)->name('faculty.show');
});

/*
| A faculty member's OWN profile — the sidebar's "My Profile" destination
| (owner decision 2026-09-27).
|
| WHY A SEPARATE, PARAMETERLESS ROUTE: `App\Support\Navigation` stores route
| NAMES and resolves them with NO parameters (`Route::has($name)` then
| `route($name)`), so a nav item cannot point at `faculty.show` — that needs a
| `{faculty}` id the config has no way to supply. `Profile::mount()` therefore
| takes the id as optional and falls back to the authenticated user's own
| record, so both routes render the same component.
|
| Role-gated to faculty: an Admin has no `faculties` row, so `/my-profile` is
| meaningless for them — they reach profiles through the Directory.
*/
Route::get('/my-profile', Profile::class)
    ->middleware(['auth', 'role:faculty'])->name('faculty.me');

// Project hub — all roles; access scoped by ProgramPolicy (5.2)
// The {project} parameter binds the ExtensionProject model.
Route::middleware(['auth'])->group(function () {
    Route::get('/projects/{project}', Hub::class)->name('projects.show');
    Route::get('/my-projects', MyPrograms::class)->name('projects.my');

    // Availability — admin creates; faculty responds (5.8)
    Route::get('/availability', Index::class)->name('availability.index');
});

// Proposals — faculty submit; admin reviews (5.12)
Route::get('/proposals', App\Livewire\Proposals\Index::class)
    ->middleware(['auth', 'role:admin,faculty'])->name('proposals.index');

Route::get('/proposals/create', Create::class)
    ->middleware(['auth', 'role:faculty'])->name('proposals.create');

// Rendered hours — admin approval queue; faculty my-hours (8.9)
Route::get('/rendered-hours', App\Livewire\RenderedHours\Index::class)
    ->middleware(['auth', 'role:admin'])->name('rendered-hours.index');

Route::get('/rendered-hours/my', My::class)
    ->middleware(['auth', 'role:faculty'])->name('rendered-hours.my');

// Needs assessments — Faculty encodes, Secretary imports/encodes (2.2, 2.3)
Route::get('/assessments/create', Wizard::class)
    ->middleware(['auth', 'role:faculty,secretary'])->name('assessments.create');

Route::get('/assessments/import', Import::class)
    ->middleware(['auth', 'role:faculty,secretary'])->name('assessments.import');

Route::get('/assessments/review', Review::class)
    ->middleware(['auth', 'role:secretary'])->name('assessments.review');

Route::get('/assessments/template', AssessmentTemplateController::class)
    ->middleware(['auth'])->name('assessments.template');

// Beneficiary management — Secretary entry point to the program hubs (5.4, v4.12)
Route::get('/beneficiaries', App\Livewire\Beneficiaries\Index::class)
    ->middleware(['auth', 'role:secretary'])->name('beneficiaries.index');

// Beneficiary import template — Admin AND Secretary (import lives in the program
// hub, 5.4). The Secretary's documented scope includes importing from this
// template (secretaryguide.md), and the hub renders the download link INSIDE the
// import modal, which is gated on $canManageBeneficiaries (admin + secretary).
// Admin-only here therefore showed the Secretary a button that 403'd. Now
// matches its two siblings, activities.attendance-template and
// activities.evaluation-template.
Route::get('/beneficiaries/template', BeneficiaryTemplateController::class)
    ->middleware(['auth', 'role:admin,secretary'])->name('beneficiaries.template');

// Beneficiary EXPORT — the same workbook, pre-filled with a program's enrolled
// list (owner request 2026-10-05). Gated exactly like the button that links to
// it: the hub renders the export link under $canManageBeneficiaries (admin +
// secretary), so any narrower role here would show a role a link it cannot
// reach — the §29 class of bug.
Route::get('/projects/{project}/beneficiaries/export', BeneficiaryExportController::class)
    ->middleware(['auth', 'role:admin,secretary'])->name('beneficiaries.export');

// Activity record templates (v4.13) — Admin and Secretary (5.4/5.5/5.6)
Route::get('/activities/{activity}/attendance-template', ActivityAttendanceTemplateController::class)
    ->middleware(['auth', 'role:admin,secretary'])->name('activities.attendance-template');

Route::get('/activities/{activity}/evaluation-template', ActivityEvaluationTemplateController::class)
    ->middleware(['auth', 'role:admin,secretary'])->name('activities.evaluation-template');

Route::middleware('auth')->group(function () {
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
