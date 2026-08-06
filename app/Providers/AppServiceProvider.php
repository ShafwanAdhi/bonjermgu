<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\ApplicationTracking;
use App\Observers\ApplicationTrackingObserver;
use App\Policies\ApplicationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Go Live date follows stage 11 — AD-12.
        ApplicationTracking::observe(ApplicationTrackingObserver::class);

        // The global scope decides what is visible; the policy decides what is
        // changeable. Referral passes the scope and is refused here on update.
        Gate::policy(Application::class, ApplicationPolicy::class);
    }
}
