<?php

namespace Primalmaxor\LaravelEnvDoctor;

use Illuminate\Support\ServiceProvider;
use Primalmaxor\LaravelEnvDoctor\Console\Commands\CompareEnvCommand;
use Primalmaxor\LaravelEnvDoctor\Console\Commands\AuditEnvUsageCommand;
use Primalmaxor\LaravelEnvDoctor\Console\Commands\FixEnvCommand;
use Primalmaxor\LaravelEnvDoctor\Console\Commands\LintEnvCommand;
use Primalmaxor\LaravelEnvDoctor\Console\Commands\SecurityEnvCommand;

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
                FixEnvCommand::class,
                LintEnvCommand::class,
                SecurityEnvCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/laravel-env-doctor.php' => config_path('laravel-env-doctor.php'),
            ], 'laravel-env-doctor-config');
        }
    }
} 