<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
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

        // Forçar HTTPS apenas quando usar ngrok ou domínio real (não localhost)
        $appUrl = config('app.url', '');
        if (str_contains($appUrl, 'ngrok') ||
            (config('app.env') === 'production' && !str_contains($appUrl, 'localhost'))) {
            URL::forceScheme('https');
        }

        // View::composer('*', function ($view) {
        //     $blingAuthService = app(BlingAuthService::class);
        //     $view->with('blingToken', $blingAuthService->getAccessToken());
        // });
    }
}
