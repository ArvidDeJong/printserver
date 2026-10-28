<?php

namespace Darvis\Printserver\Tests;

use Darvis\Printserver\PrintserverServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Config a test needs before the application is built, for instance because the routes
     * read it when they are registered. Set it through rebuildWith().
     *
     * @var array<string, mixed>
     */
    protected array $extraConfig = [];

    /**
     * @param  Application  $app
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app): array
    {
        return [
            PrintserverServiceProvider::class,
        ];
    }

    /**
     * An in-memory database and one token, so a test only sets what it needs itself.
     * Everything else uses the package defaults.
     *
     * @param  Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('printserver.tokens', 'amsterdam:amsterdam:token-amsterdam,rotterdam:rotterdam:token-rotterdam');

        foreach ($this->extraConfig as $key => $value) {
            $app['config']->set($key, $value);
        }
    }

    /**
     * Builds the application again with this config and migrates the fresh in-memory database.
     *
     * @param  array<string, mixed>  $config
     */
    protected function rebuildWith(array $config): void
    {
        $this->extraConfig = $config;
        $this->refreshApplication();
        $this->artisan('migrate')->run();
    }
}
