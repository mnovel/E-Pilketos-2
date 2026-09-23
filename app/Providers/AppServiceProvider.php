<?php

namespace App\Providers;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\ElectionSession;
use App\Models\User;
use App\Observers\CandidateObserver;
use App\Observers\ElectionObserver;
use App\Observers\ElectionSessionObserver;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\URL;
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
        // ✅ Force HTTPS di production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // ✅ Daftarkan Model Observers untuk auto-clear cache
        Election::observe(ElectionObserver::class);
        Candidate::observe(CandidateObserver::class);
        User::observe(UserObserver::class);
        ElectionSession::observe(ElectionSessionObserver::class);
    }
}
