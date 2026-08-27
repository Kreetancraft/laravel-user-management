<?php

namespace Kreetancraft\UserManagement\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Kreetancraft\UserManagement\Contracts\RoleContract;
use Kreetancraft\UserManagement\Contracts\UserContract;
use Kreetancraft\UserManagement\Http\Middleware\EnsureTwoFactorEnforced;
use Kreetancraft\UserManagement\Http\Middleware\EnsureUserIsActive;
use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Observers\UserObserver;
use Kreetancraft\UserManagement\Policies\UserPolicy;
use Kreetancraft\UserManagement\Repositories\RoleRepository;
use Kreetancraft\UserManagement\Repositories\UserRepository;

class UserManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/user-management.php', 'user-management');

        $this->app->bind(UserContract::class, UserRepository::class);
        $this->app->bind(RoleContract::class, RoleRepository::class);

        $this->app->register(EventServiceProvider::class);
        $this->app->register(FortifyServiceProvider::class);
    }

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerMigrations();
        $this->registerRoutes();
        $this->registerPolicies();
        $this->registerSuperAdminBypass();
        $this->registerMiddleware();
        $this->registerCommands();

        User::observe(UserObserver::class);

        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'user-management');
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__.'/../../config/user-management.php' => config_path('user-management.php'),
        ], 'user-management-config');

        $this->publishes([
            __DIR__.'/../../config/user-management.php' => config_path('user-management.php'),
        ], 'config');
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'user-management');

        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/user-management'),
        ], 'user-management-views');

        Blade::componentNamespace('Kreetancraft\UserManagement\\View\\Components', 'user-management');
    }

    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->publishesMigrations([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'user-management-migrations');
    }

    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../../routes/web.php');
    }

    private function registerMiddleware(): void
    {
        Route::aliasMiddleware('ensure.2fa.enforced', EnsureTwoFactorEnforced::class);
        Route::pushMiddlewareToGroup('web', EnsureUserIsActive::class);
    }

    private function registerPolicies(): void
    {
        Gate::policy(User::class, UserPolicy::class);
    }

    /**
     * Super-admins bypass all permission checks.
     * Last-super-admin guard lives in DeleteUserAction, not the policy,
     * because Gate::before would otherwise short-circuit it.
     */
    private function registerSuperAdminBypass(): void
    {
        Gate::before(function ($user, $ability) {
            if (! $user instanceof User || ! $user->isSuperAdmin()) {
                return null;
            }

            return true;
        });
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Kreetancraft\UserManagement\Console\SuperAdminCommand::class,
                \Kreetancraft\UserManagement\Console\SyncPermissionsCommand::class,
            ]);
        }
    }
}
