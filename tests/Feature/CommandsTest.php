<?php

use Kreetancraft\UserManagement\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// -------------------------------------------------------------------
// user-management:super-admin
// -------------------------------------------------------------------

test('super-admin creates a user non-interactively', function () {
    $this->artisan('user-management:super-admin', [
        '--name' => 'Root',
        '--email' => 'root@example.com',
        '--password' => 'secret-password',
    ])->assertSuccessful();

    $user = User::where('email', 'root@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->isSuperAdmin())->toBeTrue()
        ->and($user->is_active)->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();
});

test('super-admin promotes an existing user by email', function () {
    $user = User::factory()->create(['email' => 'existing@example.com']);

    $this->artisan('user-management:super-admin', ['--user' => 'existing@example.com'])
        ->assertSuccessful();

    expect($user->fresh()->isSuperAdmin())->toBeTrue();
});

test('super-admin promotes an existing user by id', function () {
    $user = User::factory()->create();

    $this->artisan('user-management:super-admin', ['--user' => (string) $user->id])
        ->assertSuccessful();

    expect($user->fresh()->isSuperAdmin())->toBeTrue();
});

test('super-admin fails clearly when the user does not exist', function () {
    $this->artisan('user-management:super-admin', ['--user' => 'nobody@example.com'])
        ->expectsOutputToContain('not found')
        ->assertFailed();
});

test('super-admin creates the role if it is missing', function () {
    Role::query()->delete();

    $this->artisan('user-management:super-admin', [
        '--name' => 'Root',
        '--email' => 'root2@example.com',
        '--password' => 'secret-password',
    ])->assertSuccessful();

    expect(Role::where('name', User::superAdminRole())->exists())->toBeTrue();
});

// -------------------------------------------------------------------
// user-management:sync-permissions
// -------------------------------------------------------------------

test('sync-permissions generates a permission per configured policy method', function () {
    Permission::query()->delete();

    config()->set('user-management.policies.paths', [__DIR__.'/../fixtures/Policies']);
    config()->set('user-management.permissions.custom', []);

    $this->artisan('user-management:sync-permissions')->assertSuccessful();

    $names = Permission::pluck('name');

    expect($names)->toContain('view-articles')   // viewAny maps to view
        ->and($names)->toContain('create-articles')
        ->and($names)->toContain('delete-articles')
        // `publish` is not in permissions.methods, so it must not appear.
        ->and($names)->not->toContain('publish-articles');
});

test('sync-permissions also creates the configured custom permissions', function () {
    Permission::query()->delete();

    config()->set('user-management.policies.discover', false);
    config()->set('user-management.permissions.custom', ['manage-roles', 'manage-permissions']);

    $this->artisan('user-management:sync-permissions')->assertSuccessful();

    expect(Permission::pluck('name'))
        ->toContain('manage-roles')
        ->toContain('manage-permissions');
});

test('sync-permissions is idempotent', function () {
    config()->set('user-management.policies.paths', [__DIR__.'/../fixtures/Policies']);

    $this->artisan('user-management:sync-permissions')->assertSuccessful();
    $first = Permission::count();

    $this->artisan('user-management:sync-permissions')->assertSuccessful();

    expect(Permission::count())->toBe($first);
});

test('sync-permissions --fresh never deletes a protected permission', function () {
    config()->set('user-management.policies.discover', false);
    config()->set('user-management.permissions.custom', []);
    config()->set('user-management.permissions.protected', ['keep-me']);

    Permission::findOrCreate('keep-me', 'web');
    Permission::findOrCreate('prune-me', 'web');

    $this->artisan('user-management:sync-permissions', ['--fresh' => true])->assertSuccessful();

    expect(Permission::where('name', 'keep-me')->exists())->toBeTrue()
        ->and(Permission::where('name', 'prune-me')->exists())->toBeFalse();
});
