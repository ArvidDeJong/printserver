<?php

namespace Darvis\Printserver;

use Darvis\Printserver\Console\Commands\CleanupCommand;
use Darvis\Printserver\Console\Commands\TestPageCommand;
use Darvis\Printserver\Console\Commands\TokenCommand;
use Darvis\Printserver\Support\PrintserverConfig;
use Illuminate\Support\ServiceProvider;

class PrintserverServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/printserver.php', 'printserver');
    }

    /**
     * Register the migration, the endpoints, the commands and the publishable config.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (PrintserverConfig::routesEnabled()) {
            $this->loadRoutesFrom(__DIR__.'/../routes/printserver.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanupCommand::class,
                TestPageCommand::class,
                TokenCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/printserver.php' => config_path('printserver.php'),
            ], 'printserver-config');
        }
    }
}
