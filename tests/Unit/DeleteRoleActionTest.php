<?php

use Kreetancraft\UserManagement\Actions\DeleteRoleAction;
use Kreetancraft\UserManagement\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    seedRolesAndPermissions();
});

test('a custom role can be deleted', function () {
    $role = Role::create(['name' => 'expendable']);

    $result = DeleteRoleAction::run($role);

    expect($result)->toBeTrue();
    expect(Role::where('name', 'expendable')->exists())->toBeFalse();
});

test('a deleted role detaches its permissions', function () {
    $role = Role::create(['name' => 'temp-with-perms']);
    $role->givePermissionTo('view-users');

    expect($role->permissions()->count())->toBe(1);

    DeleteRoleAction::run($role);

    expect(Role::where('name', 'temp-with-perms')->exists())->toBeFalse();
});

test('system roles cannot be deleted', function () {
    $roleName = User::superAdminRole();
    $role = Role::findByName($roleName);

    expect(fn () => DeleteRoleAction::run($role))
        ->toThrow(RuntimeException::class, "System role '{$roleName}' cannot be deleted.");

    expect(Role::where('name', $roleName)->exists())->toBeTrue();
});
