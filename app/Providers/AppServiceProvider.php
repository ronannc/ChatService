<?php

namespace App\Providers;

use App\Support\AtendenteContext;
use App\Support\SistemaContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SistemaContext::class);
        $this->app->singleton(AtendenteContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
