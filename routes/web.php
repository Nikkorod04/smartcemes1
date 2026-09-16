<?php

use App\Http\Controllers\ActivityAttendanceTemplateController;
use App\Http\Controllers\ActivityEvaluationTemplateController;
use App\Http\Controllers\AssessmentTemplateController;
use App\Http\Controllers\BeneficiaryTemplateController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Livewire\AiAnalysis;
use App\Livewire\Analytics;
use App\Livewire\Assessments\Import;
use App\Livewire\Assessments\Review;
use App\Livewire\Assessments\Wizard;
use App\Livewire\Availability\Index;
use App\Livewire\Calendar;
use App\Livewire\Communities\Index as CommunitiesIndex;
use App\Livewire\Dashboard;
use App\Livewire\ProgramNarratives;
use App\Livewire\Programs\Hub;
use App\Livewire\Programs\Index as ProgramsIndex;
use App\Livewire\Programs\MyPrograms;
use App\Livewire\Proposals\Create;
use App\Livewire\RenderedHours\My;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Role dashboards (12.1) — one component, three role views
Route::get('/dashboard', Dashboard::class)
    ->middleware(['auth'])->name('dashboard');

// Analytics — ONE Admin page, six tabs (5.13 / 12.1)
Route::get('/analytics', Analytics::class)
    ->middleware(['auth', 'role:admin'])->name('analytics.index');

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

    Route::get('/programs', ProgramsIndex::class)->name('programs.index');
});

// Program hub — all roles; access scoped by ProgramPolicy (5.2)
Route::middleware(['auth'])->group(function () {
    Route::get('/programs/{program}', Hub::class)->name('programs.show');
    Route::get('/my-programs', MyPrograms::class)->name('programs.my');

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

// Beneficiary import template — Admin only (import lives in the program hub, 5.4)
Route::get('/beneficiaries/template', BeneficiaryTemplateController::class)
    ->middleware(['auth', 'role:admin'])->name('beneficiaries.template');

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
