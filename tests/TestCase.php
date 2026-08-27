<?php

namespace Kreetancraft\UserManagement\Tests;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Kreetancraft\UserManagement\Providers\UserManagementServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            \Flux\FluxServiceProvider::class,
            PermissionServiceProvider::class,
            \Torann\GeoIP\GeoIPServiceProvider::class,
            \Lab404\Impersonate\ImpersonateServiceProvider::class,
            \Laravel\Fortify\FortifyServiceProvider::class,
            UserManagementServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('auth.providers.users.model', \Kreetancraft\UserManagement\Models\User::class);
        $app['config']->set('permission.testing', true);
        $app['config']->set('view.paths', [__DIR__.'/../resources/views', resource_path('views')]);
        // Flux stub - provide minimal view namespace if not installed in testbench
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
