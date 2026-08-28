<?php

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Navigation;
use Kreetancraft\UserManagement\Tests\Fixtures\Models\Widget;
use Kreetancraft\UserManagement\Tests\Fixtures\Policies\WidgetPolicy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * The sidebar registry.
 *
 * A package cannot add a link by depending on this one, so the seam is a
 * container tag: bind an item, tag it `admin.navigation`, and it appears. These
 * tests are written from the contributing package's side, because that is the
 * side that must keep working when this package is absent.
 */
function nav(): Navigation
{
    return app(Navigation::class);
}

/** Register a route so items have something real to point at. */
function fakeRoute(string $name, string $uri = '/widgets'): void
{
    Route::get($uri, fn () => '')->name($name);

    // The name is set after the route joins the collection, so the lookup the
    // registry's Route::has() consults is stale until it is rebuilt.
    Route::getRoutes()->refreshNameLookups();
}

test('a package contributes a link through the container tag', function () {
    fakeRoute('admin.widgets');

    app()->bind('widgets.nav', fn () => [
        'label' => 'Widgets',
        'icon' => 'cube',
        'route' => 'admin.widgets',
        'sort' => 15,
    ]);
    app()->tag('widgets.nav', Navigation::TAG);

    expect(collect(nav()->items())->pluck('label'))->toContain('Widgets');
});

test('a contributed link is resolved lazily, so provider order does not matter', function () {
    fakeRoute('admin.widgets');

    // Resolve the registry FIRST, then contribute — a package booting after
    // user-management must still be picked up.
    $navigation = nav();

    app()->bind('widgets.nav', fn () => [
        'label' => 'Widgets',
        'route' => 'admin.widgets',
    ]);
    app()->tag('widgets.nav', Navigation::TAG);

    expect(collect($navigation->items())->pluck('label'))->toContain('Widgets');
});

test('one binding may contribute several links', function () {
    fakeRoute('admin.widgets');
    fakeRoute('admin.gadgets', '/gadgets');

    app()->bind('widgets.nav', fn () => [
        ['label' => 'Widgets', 'route' => 'admin.widgets'],
        ['label' => 'Gadgets', 'route' => 'admin.gadgets'],
    ]);
    app()->tag('widgets.nav', Navigation::TAG);

    expect(collect(nav()->items())->pluck('label'))
        ->toContain('Widgets')
        ->toContain('Gadgets');
});

test('a link whose route does not exist is skipped rather than throwing', function () {
    // Exactly what happens when a package ships the link but its admin routes
    // are switched off in config.
    app()->bind('widgets.nav', fn () => [
        'label' => 'Widgets',
        'route' => 'admin.widgets.disabled',
    ]);
    app()->tag('widgets.nav', Navigation::TAG);

    expect(collect(nav()->items())->pluck('label'))->not->toContain('Widgets');
});

test('a link is hidden when the user lacks its ability', function () {
    fakeRoute('admin.widgets');

    $user = User::factory()->create();
    $this->actingAs($user);

    app()->bind('widgets.nav', fn () => [
        'label' => 'Widgets',
        'route' => 'admin.widgets',
        'ability' => 'view-widgets',
    ]);
    app()->tag('widgets.nav', Navigation::TAG);

    expect(collect(nav()->items())->pluck('label'))->not->toContain('Widgets');
});

test('a link may be gated by a policy rather than a permission name', function () {
    // How the media package does it: the same question its route middleware
    // asks, so the link appears exactly when the page behind it is reachable.
    fakeRoute('admin.widgets');
    Gate::policy(Widget::class, WidgetPolicy::class);

    Permission::findOrCreate('view-widgets', 'web');

    $user = User::factory()->create();
    $this->actingAs($user);

    app()->bind('widgets.nav', fn () => [
        'label' => 'Widgets',
        'route' => 'admin.widgets',
        'ability' => 'viewAny',
        'model' => Widget::class,
    ]);
    app()->tag('widgets.nav', Navigation::TAG);

    // WidgetPolicy::viewAny() asks for view-widgets, which this user lacks.
    expect(collect(nav()->items())->pluck('label'))->not->toContain('Widgets');

    $user->givePermissionTo('view-widgets');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect(collect(nav()->items())->pluck('label'))->toContain('Widgets');
});

test('links are ordered by sort', function () {
    fakeRoute('admin.widgets');
    fakeRoute('admin.gadgets', '/gadgets');

    app()->bind('widgets.nav', fn () => [
        ['label' => 'Last', 'route' => 'admin.widgets', 'sort' => 90],
        ['label' => 'First', 'route' => 'admin.gadgets', 'sort' => 1],
    ]);
    app()->tag('widgets.nav', Navigation::TAG);

    $labels = collect(nav()->items())->pluck('label')->all();

    expect(array_search('First', $labels, true))
        ->toBeLessThan(array_search('Last', $labels, true));
});

test('a host may add links directly', function () {
    fakeRoute('admin.widgets');

    nav()->add(['label' => 'Dashboard', 'route' => 'admin.widgets']);

    expect(collect(nav()->items())->pluck('label'))->toContain('Dashboard');
});

test('this package contributes its own links through the same tag', function () {
    // No privileged path for our own links that a third party cannot take.
    collect(packagePermissions())->each(fn ($p) => Permission::findOrCreate($p, 'web'));

    $user = User::factory()->create();
    $user->givePermissionTo('view-users');
    $this->actingAs($user);

    expect(collect(nav()->items())->pluck('label'))->toContain('Users');
});

test('an item carries an href and an active flag ready for rendering', function () {
    fakeRoute('admin.widgets');

    nav()->add(['label' => 'Widgets', 'route' => 'admin.widgets']);

    expect(nav()->items()[0])
        ->toHaveKeys(['label', 'icon', 'href', 'active'])
        ->and(nav()->items()[0]['href'])->toContain('/widgets')
        ->and(nav()->items()[0]['active'])->toBeFalse();
});
