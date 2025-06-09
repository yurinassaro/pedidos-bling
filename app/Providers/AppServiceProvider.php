<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Services\BlingAuthService;

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
        Schema::defaultStringLength(191);

        // View::composer('*', function ($view) {
        //     $blingAuthService = app(BlingAuthService::class);
        //     $view->with('blingToken', $blingAuthService->getAccessToken());
        // });
    }
}
