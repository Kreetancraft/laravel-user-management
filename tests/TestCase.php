<?php

namespace Kreetancraft\UserManagement\Tests;

use Flux\FluxServiceProvider;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Providers\UserManagementServiceProvider;
use Lab404\Impersonate\ImpersonateServiceProvider;
use Laravel\Fortify\FortifyServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\LaravelData\LaravelDataServiceProvider;
use Spatie\Permission\PermissionServiceProvider;
use Torann\GeoIP\GeoIPServiceProvider;

abstract class TestCase extends BaseTestCase
{
    use LazilyRefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            FluxServiceProvider::class,
            PermissionServiceProvider::class,
            LaravelDataServiceProvider::class,
            GeoIPServiceProvider::class,
            ImpersonateServiceProvider::class,
            FortifyServiceProvider::class,
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
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('permission.testing', true);
        // Fortify redirects here after login; the host normally owns this route.
        $app['config']->set('fortify.home', '/admin');
        $app['config']->set('user-management.routes.names.security_edit', 'security.edit');
        $app['config']->set('user-management.routes.home', '/');
        // tests/fixtures/views stands in for the host application: this package
        // ships no layouts, so the suite has to provide the ones it renders into.
        $app['config']->set('view.paths', [
            __DIR__.'/fixtures/views',
            __DIR__.'/../resources/views',
            resource_path('views'),
        ]);
    }

    /**
     * Skip a test unless the given Fortify feature is enabled.
     *
     * Features are configurable, so a test for (say) registration must not fail
     * simply because this package ships with registration off by default.
     */
    protected function skipUnlessFortifyHas(string $feature): void
    {
        if (! in_array($feature, (array) config('fortify.features', []), true)) {
            $this->markTestSkipped("Fortify feature [{$feature}] is not enabled.");
        }
    }

    /**
     * Stand-ins for routes the HOST application owns.
     *
     * The package deliberately references these by configurable name rather
     * than defining them, so the suite has to play the host here too.
     */
    protected function defineRoutes($router): void
    {
        $router->middleware('web')->group(function ($router) {
            $router->get('/', fn () => 'home')->name('home');
            $router->get('/admin', fn () => 'admin')->name('admin');
            $router->get('/settings/security', fn () => 'security')->name('security.edit');
        });
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
