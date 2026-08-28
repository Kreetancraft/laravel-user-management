<?php

use Kreetancraft\UserManagement\Actions\DeleteRoleAction;
use Kreetancraft\UserManagement\Enums\UserRole;
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
    $role->givePermissionTo('view-trips');

    expect($role->permissions()->count())->toBe(1);

    DeleteRoleAction::run($role);

    expect(Role::where('name', 'temp-with-perms')->exists())->toBeFalse();
});

test('system roles defined in UserRole enum cannot be deleted', function () {
    $roleEnum = UserRole::SuperAdmin;
    $role = Role::findByName($roleEnum->value);

    expect(fn () => DeleteRoleAction::run($role))
        ->toThrow(RuntimeException::class, "System role '{$roleEnum->value}' cannot be deleted.");

    expect(Role::where('name', $roleEnum->value)->exists())->toBeTrue();
});
