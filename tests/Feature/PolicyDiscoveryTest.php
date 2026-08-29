<?php

use Illuminate\Support\Facades\Gate;
use Kreetancraft\UserManagement\Tests\Fixtures\Models\Doohickey;
use Kreetancraft\UserManagement\Tests\Fixtures\Models\Gadget;
use Kreetancraft\UserManagement\Tests\Fixtures\Models\ThirdPartyThing;
use Kreetancraft\UserManagement\Tests\Fixtures\Models\Widget;
use Kreetancraft\UserManagement\Tests\Fixtures\Policies\DoohickeyPolicy;
use Kreetancraft\UserManagement\Tests\Fixtures\Policies\GadgetPolicy;
use Kreetancraft\UserManagement\Tests\Fixtures\Policies\ThirdPartyPolicy;
use Kreetancraft\UserManagement\Tests\Fixtures\Policies\WidgetPolicy;
use Spatie\Permission\Models\Permission;

/**
 * Permissions are discovered, not declared.
 *
 * Modelled on Filament Shield, which derives names from subjects registered in
 * the Filament panel. We have no panel, so the equivalent registry is Laravel's
 * own Gate::policies() — which is the only thing that makes a policy shipped
 * INSIDE a package visible. Scanning directories finds this application's
 * policies and nothing else.
 */
beforeEach(function () {
    Permission::query()->delete();
    config()->set('user-management.permissions.custom', []);
    config()->set('user-management.policies.paths', []);
    config()->set('user-management.policies.exclude', []);
});

test('a policy registered by a package is discovered with nothing declared', function () {
    // Exactly what a package does in its service provider.
    Gate::policy(Widget::class, WidgetPolicy::class);

    $this->artisan('user-management:sync-permissions')->assertSuccessful();

    expect(Permission::pluck('name')->all())
        ->toContain('view-widgets')
        ->toContain('create-widgets')
        ->toContain('delete-widgets');
});

test('only the methods a policy actually declares produce permissions', function () {
    Gate::policy(Widget::class, WidgetPolicy::class);

    $this->artisan('user-management:sync-permissions')->assertSuccessful();

    // WidgetPolicy has no update() method.
    expect(Permission::pluck('name')->all())->not->toContain('update-widgets');
});

test('viewAny collapses to view rather than view-any', function () {
    Gate::policy(Widget::class, WidgetPolicy::class);

    $this->artisan('user-management:sync-permissions')->assertSuccessful();

    expect(Permission::pluck('name')->all())
        ->toContain('view-widgets')
        ->not->toContain('view-any-widgets');
});

test('a policy names its own subject', function () {
    // The single source of truth. The policy that checks the ability and the
    // command that creates it read the same constant, so there is no pair of
    // config keys anyone has to keep in step — which is exactly the drift this
    // replaced. Without it the subject falls back to the model name.
    Gate::policy(Gadget::class, GadgetPolicy::class);

    $this->artisan('user-management:sync-permissions')->assertSuccessful();

    expect(Permission::pluck('name')->all())
        ->toContain('view-thingamies')   // GadgetPolicy::PERMISSION_SUBJECT = 'thingamy'
        ->not->toContain('view-gadgets');
});
test('a model can be excluded from discovery', function () {
    config()->set('user-management.policies.exclude', [Widget::class]);

    Gate::policy(Widget::class, WidgetPolicy::class);

    $this->artisan('user-management:sync-permissions')->assertSuccessful();

    expect(Permission::pluck('name')->all())->not->toContain('view-widgets');
});

test('registered discovery can be turned off without disabling path scanning', function () {
    config()->set('user-management.policies.discover_registered', false);

    Gate::policy(Widget::class, WidgetPolicy::class);

    $this->artisan('user-management:sync-permissions')->assertSuccessful();

    expect(Permission::pluck('name')->all())->not->toContain('view-widgets');
});

test('the run reports which subject each permission came from', function () {
    Gate::policy(Widget::class, WidgetPolicy::class);

    $this->artisan('user-management:sync-permissions')
        ->expectsOutputToContain('widget')
        ->assertSuccessful();
});

test('dry-run reports without writing anything', function () {
    Gate::policy(Widget::class, WidgetPolicy::class);

    $this->artisan('user-management:sync-permissions', ['--dry-run' => true])
        ->assertSuccessful();

    expect(Permission::count())->toBe(0);
});

test('syncing twice creates nothing new', function () {
    Gate::policy(Widget::class, WidgetPolicy::class);

    $this->artisan('user-management:sync-permissions')->assertSuccessful();
    $first = Permission::count();

    $this->artisan('user-management:sync-permissions')->assertSuccessful();

    expect(Permission::count())->toBe($first);
});

test('a policy from an unrelated dependency is left alone', function () {
    // livewire-filemanager registers its own MediaPolicy and FolderPolicy.
    // Generating permissions for them would expose a dependency nobody meant to,
    // and collide with subjects this application actually owns.
    Gate::policy(ThirdPartyThing::class, ThirdPartyPolicy::class);

    $this->artisan('user-management:sync-permissions')->assertSuccessful();

    expect(Permission::pluck('name')->all())
        ->not->toContain('view-third-party-things')
        ->not->toContain('restore-third-party-things');
});

test('a policy may declare its own plural when deriving one reads wrong', function () {
    // SEO is a mass noun: `view-seo` is the phrase, `view-seos` is not. Without
    // this the only options were an awkward permission name or an awkward
    // subject, and both leak into every app that installs the package.
    Gate::policy(Doohickey::class, DoohickeyPolicy::class);

    $this->artisan('user-management:sync-permissions')->assertSuccessful();

    expect(Permission::pluck('name')->all())
        ->toContain('view-kit')
        ->not->toContain('view-kits');
});
