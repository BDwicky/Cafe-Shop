<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
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
        if (config('app.env') === 'production' || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        if (str_contains(database_path('migrations'), '[') || str_contains(database_path('migrations'), ']')) {
            foreach (File::files(database_path('migrations')) as $file) {
                $this->loadMigrationsFrom($file->getPathname());
            }
        }
    }
}
