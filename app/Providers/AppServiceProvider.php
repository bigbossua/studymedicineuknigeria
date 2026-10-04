<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
        Paginator::defaultView('pagination.site');
        Paginator::defaultSimpleView('pagination.site-simple');
        // Console and queued work (mail sent from jobs, scheduled commands) has no request: build links from APP_URL there too.
        if ($this->app->runningInConsole() && ! $this->app->environment('local', 'testing')) {
            URL::forceRootUrl(rtrim((string) config('app.url'), '/'));
        }
    }
}
