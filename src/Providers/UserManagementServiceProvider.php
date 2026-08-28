<?php

namespace Kreetancraft\UserManagement\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Kreetancraft\UserManagement\Console\InstallCommand;
use Kreetancraft\UserManagement\Console\SuperAdminCommand;
use Kreetancraft\UserManagement\Console\SyncPermissionsCommand;
use Kreetancraft\UserManagement\Contracts\ManagesUsers;
use Kreetancraft\UserManagement\Contracts\QueriesUsers;
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

        // One implementation, three views of it: consumers depend on the
        // narrowest interface that covers what they actually do.
        $this->app->singleton(UserRepository::class);
        $this->app->bind(UserContract::class, UserRepository::class);
        $this->app->bind(ManagesUsers::class, UserRepository::class);
        $this->app->bind(QueriesUsers::class, UserRepository::class);
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
        Blade::anonymousComponentPath(__DIR__.'/../../resources/views/components', 'user-management');
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
     * Works for both package User and host App\User (when it uses HasRoles).
     * Last-super-admin guard lives in DeleteUserAction, not the policy,
     * because Gate::before would otherwise short-circuit it.
     */
    /**
     * Grant super admins everything.
     *
     * The interception point is configurable, as Filament Shield's is. `before`
     * short-circuits policies entirely — which is why the self-delete and
     * last-super-admin guards live in DeleteUserAction, where a short-circuit
     * cannot skip them. `after` lets policies answer first and only then grants,
     * which some applications will prefer.
     */
    private function registerSuperAdminBypass(): void
    {
        if (! config('user-management.super_admin.enabled', true)) {
            return;
        }

        $intercept = config('user-management.super_admin.intercept_gate', 'before') === 'after'
            ? 'after'
            : 'before';

        Gate::{$intercept}(function ($user, $ability) use ($intercept) {
            if (! is_object($user)) {
                return $intercept === 'after' ? false : null;
            }

            // Host App\User or package User both may have isSuperAdmin()
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }

            // Fallback: check role directly via HasRoles
            $superRole = (string) config('user-management.super_admin.role', 'super-admin');
            if (method_exists($user, 'hasRole') && $user->hasRole($superRole)) {
                return true;
            }

            // `before` must return null to let the policy speak; `after` is only
            // consulted once it already has, so false there means "still no".
            return $intercept === 'after' ? false : null;
        });
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                SuperAdminCommand::class,
                SyncPermissionsCommand::class,
                InstallCommand::class,
            ]);
        }
    }
}
