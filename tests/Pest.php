<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature', 'Unit');

/**
 * Every permission this package ships behaviour for.
 *
 * The package seeds no permissions of its own at runtime — they are generated
 * from policies by `user-management:sync-permissions`. Tests declare them
 * explicitly so the suite is self-contained and carries no host-app vocabulary.
 *
 * @return list<string>
 */
function packagePermissions(): array
{
    return [
        'view-users', 'create-users', 'update-users', 'delete-users',
        'manage-roles', 'manage-permissions',
    ];
}

/**
 * Create every package permission plus the super-admin role.
 */
function seedRolesAndPermissions(): void
{
    foreach (packagePermissions() as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    Role::findOrCreate(User::superAdminRole(), 'web')
        ->syncPermissions(Permission::all());

    // Generic sample roles for tests that need *a* role to reference. They carry
    // no permissions: the package seeds none, and tests grant what they need.
    foreach (['editor', 'manager', 'viewer'] as $role) {
        Role::findOrCreate($role, 'web');
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();
}

/**
 * Create a role granting exactly the given permissions, and act as a user in it.
 *
 * Passing no permissions creates a role with none — useful for asserting that
 * an unauthorised user is refused.
 *
 * @param  list<string>  $permissions
 */
function actingAsRole(string $role, array $permissions = []): User
{
    seedRolesAndPermissions();

    $model = Role::findOrCreate($role, 'web');

    if ($permissions !== []) {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $model->syncPermissions($permissions);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::factory()->withRole($role)->create();
    test()->actingAs($user);

    return $user;
}

/**
 * Act as a user who can do everything (via the Gate::before bypass).
 */
function actingAsSuperAdmin(): User
{
    return actingAsRole(User::superAdminRole());
}

/**
 * Act as a user holding every package permission but WITHOUT the super-admin
 * role — the case that actually exercises the policies rather than the bypass.
 */
function actingAsAdmin(): User
{
    return actingAsRole('admin', packagePermissions());
}
