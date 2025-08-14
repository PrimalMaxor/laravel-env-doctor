<?php

namespace Primalmaxor\LaravelEnvDoctor;

use Illuminate\Support\ServiceProvider;
use Primalmaxor\LaravelEnvDoctor\Console\Commands\CompareEnvCommand;
use Primalmaxor\LaravelEnvDoctor\Console\Commands\AuditEnvUsageCommand;

class LaravelEnvDoctorServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CompareEnvCommand::class,
                AuditEnvUsageCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/laravel-env-doctor.php' => config_path('laravel-env-doctor.php'),
            ], 'laravel-env-doctor-config');
        }
    }
} 