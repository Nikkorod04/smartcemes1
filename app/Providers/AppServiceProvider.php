<?php

namespace App\Providers;

use App\Models\College;
use App\Models\ExtensionProject;
use App\Models\Faculty;
use App\Models\InteragencyAgency;
use App\Models\Program;
use App\Policies\BroadProgramPolicy;
use App\Policies\CollegePolicy;
use App\Policies\FacultyPolicy;
use App\Policies\InteragencyAgencyPolicy;
use App\Policies\ProgramPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(ExtensionProject::class, ProgramPolicy::class);
        Gate::policy(College::class, CollegePolicy::class);
        Gate::policy(Program::class, BroadProgramPolicy::class);
        Gate::policy(Faculty::class, FacultyPolicy::class);
        Gate::policy(InteragencyAgency::class, InteragencyAgencyPolicy::class);
    }
}
