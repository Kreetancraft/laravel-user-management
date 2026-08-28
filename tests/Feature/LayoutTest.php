<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Kreetancraft\UserManagement\Layout;

/**
 * This package ships no layout — its screens render into the host's. Getting
 * that wrong used to surface as Livewire's MissingLayoutException, which names a
 * view but neither the config key that chose it nor the layouts you have.
 */
test('the configured layout is used when it exists', function () {
    // tests/fixtures/views stands in for the host application.
    config()->set('user-management.layouts.admin', 'fixtures-layout');

    expect(Layout::admin())->toBe('fixtures-layout');
});

test('a missing configured layout falls back to a convention', function () {
    // An app on the older `layouts.app` convention should not have to configure
    // anything just because the default names the newer one.
    config()->set('user-management.layouts.admin', 'does.not.exist');

    expect(Layout::CONVENTIONS)->toContain('components.layouts.app')
        ->and(Layout::CONVENTIONS)->toContain('layouts.app');

    expect(Layout::admin())->toBeIn(Layout::CONVENTIONS);
});

test('it fails with the config key and what it tried, not just a view name', function () {
    config()->set('user-management.layouts.admin', 'nope');

    // Hide every convention so nothing resolves.
    View::getFinder()->setPaths([__DIR__.'/../fixtures/empty']);
    View::flushFinderCache();

    expect(fn () => Layout::admin())
        ->toThrow(RuntimeException::class, 'user-management.layouts.admin');
});

test('home accepts a route name', function () {
    Route::get('/admin', fn () => '')->name('dashboard');
    Route::getRoutes()->refreshNameLookups();

    config()->set('user-management.routes.home', 'dashboard');

    expect(Layout::home())->toContain('/admin');
});

test('home still accepts a plain URL', function () {
    config()->set('user-management.routes.home', '/admin');

    expect(Layout::home())->toBe('/admin');
});

test('home falls back to the site root rather than erroring', function () {
    config()->set('user-management.routes.home', '');

    expect(Layout::home())->toBe('/');
});

test('a route name that does not exist does not become a relative link', function () {
    // The default is the `dashboard` route name. An app without one must get the
    // site root, not href="dashboard" pointing back at the current directory.
    config()->set('user-management.routes.home', 'dashboard');

    expect(Layout::home())->toBe('/');
});
