<?php

use Livewire\Livewire;
use Kreetancraft\UserManagement\Enums\UserRole;
use Kreetancraft\UserManagement\Livewire\CreateRole;
use Kreetancraft\UserManagement\Livewire\EditRole;
use Kreetancraft\UserManagement\Livewire\ManageRoles;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    seedRolesAndPermissions();
});

// -------------------------------------------------------------------
// Access control
// -------------------------------------------------------------------

test('guests are redirected to login on role index', function () {
    $this->get(route(config('user-management.routes.names.roles.index', 'admin.roles')))->assertRedirect(route(config('user-management.routes.names.login', 'login')));
});

test('users without manage-roles permission are forbidden', function () {
    actingAsRole(UserRole::BookingManager);

    $this->get(route(config('user-management.routes.names.roles.index', 'admin.roles')))->assertForbidden();
});

test('super admin can view the roles index', function () {
    actingAsSuperAdmin();

    $this->get(route(config('user-management.routes.names.roles.index', 'admin.roles')))
        ->assertOk()
        ->assertSeeLivewire(ManageRoles::class);
});

// -------------------------------------------------------------------
// Create role
// -------------------------------------------------------------------

test('super admin creates a role with permissions', function () {
    actingAsSuperAdmin();

    Livewire::test(CreateRole::class)
        ->set('name', 'content-editor')
        ->set('selectedPermissions', ['view-trips', 'edit-trips'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route(config('user-management.routes.names.roles.index', 'admin.roles')));

    $role = Role::findByName('content-editor');
    expect($role->hasPermissionTo('view-trips'))->toBeTrue()
        ->and($role->hasPermissionTo('edit-trips'))->toBeTrue();
});

test('create role validates unique role name', function () {
    actingAsSuperAdmin();

    Livewire::test(CreateRole::class)
        ->set('name', UserRole::PackageManager->value)
        ->call('save')
        ->assertHasErrors(['name']);
});

test('create role requires a name', function () {
    actingAsSuperAdmin();

    Livewire::test(CreateRole::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
});

// -------------------------------------------------------------------
// Edit role
// -------------------------------------------------------------------

test('edit role loads target data into the form', function () {
    actingAsSuperAdmin();
    $role = Role::findByName(UserRole::PackageManager->value);

    Livewire::test(EditRole::class, ['role' => $role])
        ->assertSet('name', UserRole::PackageManager->value)
        ->assertViewHas('isSystemRole', true);
});

test('edit role updates permissions', function () {
    actingAsSuperAdmin();
    $role = Role::create(['name' => 'custom-role']);

    Livewire::test(EditRole::class, ['role' => $role])
        ->set('selectedPermissions', ['view-bookings'])
        ->call('save')
        ->assertHasNoErrors();

    $role->refresh();
    expect($role->hasPermissionTo('view-bookings'))->toBeTrue();
});

test('edit role prevents renaming a system role', function () {
    actingAsSuperAdmin();
    $role = Role::findByName(UserRole::PackageManager->value);

    Livewire::test(EditRole::class, ['role' => $role])
        ->set('name', 'something-else')
        ->call('save')
        ->assertHasErrors(['name']);

    $role->refresh();
    expect($role->name)->toBe(UserRole::PackageManager->value);
});

// -------------------------------------------------------------------
// Delete role
// -------------------------------------------------------------------

test('super admin can delete a non-system role', function () {
    actingAsSuperAdmin();
    $role = Role::create(['name' => 'temp-role']);

    Livewire::test(ManageRoles::class)
        ->call('confirmDeleteRole', $role->id)
        ->call('deleteRole')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

test('system roles cannot be deleted', function () {
    actingAsSuperAdmin();
    $role = Role::findByName(UserRole::SuperAdmin->value);

    Livewire::test(ManageRoles::class)
        ->call('confirmDeleteRole', $role->id)
        ->call('deleteRole');

    $this->assertDatabaseHas('roles', ['name' => UserRole::SuperAdmin->value]);
});

// -------------------------------------------------------------------
// Permissions
// -------------------------------------------------------------------

test('super admin creates a custom permission', function () {
    actingAsSuperAdmin();

    Livewire::test(ManageRoles::class)
        ->set('permissionName', 'custom-permission')
        ->call('savePermission')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('permissions', ['name' => 'custom-permission']);
});

test('duplicate permission names are rejected', function () {
    actingAsSuperAdmin();

    Livewire::test(ManageRoles::class)
        ->set('permissionName', 'view-users')
        ->call('savePermission')
        ->assertHasErrors(['permissionName']);
});

test('non-protected permissions can be deleted', function () {
    actingAsSuperAdmin();
    $permission = Permission::create(['name' => 'temporary-perm']);

    Livewire::test(ManageRoles::class)
        ->call('confirmDeletePermission', $permission->id)
        ->call('deletePermission')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
});

test('protected core permissions cannot be deleted', function () {
    actingAsSuperAdmin();
    $permission = Permission::findByName('view-users');

    Livewire::test(ManageRoles::class)
        ->call('confirmDeletePermission', $permission->id)
        ->call('deletePermission');

    $this->assertDatabaseHas('permissions', ['name' => 'view-users']);
});
