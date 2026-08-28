<?php

use Kreetancraft\UserManagement\Actions\DeletePermissionAction;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    seedRolesAndPermissions();
});

test('a custom permission can be deleted', function () {
    $permission = Permission::create(['name' => 'expendable']);

    $result = DeletePermissionAction::run($permission);

    expect($result)->toBeTrue();
    expect(Permission::where('name', 'expendable')->exists())->toBeFalse();
});

test('a protected core permission cannot be deleted', function () {
    $permission = Permission::findByName('view-users');

    expect(fn () => DeletePermissionAction::run($permission))
        ->toThrow(RuntimeException::class, "Core permission 'view-users' cannot be deleted.");

    expect(Permission::where('name', 'view-users')->exists())->toBeTrue();
});

test('every seeded core permission is protected', function () {
    $coreNames = [
        'view-users', 'create-users', 'update-users', 'delete-users',
        'manage-roles', 'manage-permissions',
        'view-users',

    ];

    foreach ($coreNames as $name) {
        $permission = Permission::findByName($name);

        expect(fn () => DeletePermissionAction::run($permission))
            ->toThrow(RuntimeException::class);
    }
});
