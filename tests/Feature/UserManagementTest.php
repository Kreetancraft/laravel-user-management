<?php

use Kreetancraft\UserManagement\Livewire\CreateUser;
use Kreetancraft\UserManagement\Livewire\EditUser;
use Kreetancraft\UserManagement\Livewire\ManageUsers;
use Kreetancraft\UserManagement\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    seedRolesAndPermissions();
});

// -------------------------------------------------------------------
// Access control
// -------------------------------------------------------------------

test('guests are redirected to login', function () {
    $this->get(route(config('user-management.routes.names.users.index', 'admin.users')))->assertRedirect(route(config('user-management.routes.names.login', 'login')));
});

test('users without view-users permission are forbidden', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route(config('user-management.routes.names.users.index', 'admin.users')))->assertForbidden();
});

test('super admin can view the user index', function () {
    actingAsSuperAdmin();

    $this->get(route(config('user-management.routes.names.users.index', 'admin.users')))
        ->assertOk()
        ->assertSeeLivewire(ManageUsers::class);
});

test('a role without view-users cannot view the user index', function () {
    actingAsRole('manager');

    $this->get(route(config('user-management.routes.names.users.index', 'admin.users')))->assertForbidden();
});

// -------------------------------------------------------------------
// Create user
// -------------------------------------------------------------------

test('super admin creates a user via livewire component', function () {
    actingAsSuperAdmin();

    Livewire::test(CreateUser::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('selectedRoles', ['manager'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route(config('user-management.routes.names.users.index', 'admin.users')));

    $this->assertDatabaseHas('users', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'is_active' => true,
        'password' => null,
    ]);

    $user = User::where('email', 'jane@example.com')->first();
    expect($user->hasRole('manager'))->toBeTrue();
});

test('create user validates required fields', function () {
    actingAsSuperAdmin();

    Livewire::test(CreateUser::class)
        ->set('name', '')
        ->set('email', '')
        ->call('save')
        ->assertHasErrors(['name', 'email']);
});

test('create user validates unique email', function () {
    actingAsSuperAdmin();
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::test(CreateUser::class)
        ->set('name', 'Test')
        ->set('email', 'taken@example.com')
        ->call('save')
        ->assertHasErrors(['email']);
});

test('non-super-admin with create-users permission cannot assign super-admin role', function () {
    // Create a custom role that has create-users but is not super-admin.
    $custom = Role::create(['name' => 'user-creator', 'guard_name' => 'web']);
    $custom->givePermissionTo(['view-users', 'create-users']);

    $user = User::factory()->create();
    $user->assignRole('user-creator');
    $this->actingAs($user);

    Livewire::test(CreateUser::class)
        ->set('name', 'Jane')
        ->set('email', 'jane@example.com')
        ->set('selectedRoles', [User::superAdminRole()])
        ->call('save')
        ->assertForbidden();

    $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
});

// -------------------------------------------------------------------
// Edit user
// -------------------------------------------------------------------

test('edit user loads target data into the form', function () {
    actingAsSuperAdmin();

    $target = User::factory()->withRole('manager')->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);

    Livewire::test(EditUser::class, ['user' => $target])
        ->assertSet('name', 'Original Name')
        ->assertSet('email', 'original@example.com')
        ->assertSet('selectedRoles', ['manager']);
});

test('edit user updates details and roles', function () {
    actingAsSuperAdmin();

    $target = User::factory()->withRole('manager')->create();

    Livewire::test(EditUser::class, ['user' => $target])
        ->set('name', 'Updated Name')
        ->set('email', 'updated@example.com')
        ->set('selectedRoles', ['editor'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route(config('user-management.routes.names.users.index', 'admin.users')));

    $target->refresh();
    expect($target->name)->toBe('Updated Name')
        ->and($target->email)->toBe('updated@example.com')
        ->and($target->hasRole('editor'))->toBeTrue()
        ->and($target->hasRole('manager'))->toBeFalse();
});

test('edit user prevents demoting the last super-admin', function () {
    $superAdmin = actingAsSuperAdmin();

    Livewire::test(EditUser::class, ['user' => $superAdmin])
        ->set('name', $superAdmin->name)
        ->set('email', $superAdmin->email)
        ->set('selectedRoles', ['editor'])
        ->call('save');

    $superAdmin->refresh();
    expect($superAdmin->isSuperAdmin())->toBeTrue();
});

// -------------------------------------------------------------------
// Delete user
// -------------------------------------------------------------------

test('super admin can delete another user', function () {
    actingAsSuperAdmin();

    $target = User::factory()->withRole('manager')->create();

    Livewire::test(ManageUsers::class)
        ->call('confirmDelete', $target->id)
        ->call('delete')
        ->assertHasNoErrors();

    $this->assertSoftDeleted('users', ['id' => $target->id]);
});

test('users cannot delete themselves', function () {
    $admin = actingAsSuperAdmin();

    Livewire::test(ManageUsers::class)
        ->call('confirmDelete', $admin->id)
        ->call('delete')
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $admin->id, 'deleted_at' => null]);
});

test('the last super-admin cannot be deleted', function () {
    $first = actingAsSuperAdmin();
    $second = User::factory()->superAdmin()->create();
    $this->actingAs($second);

    // Delete the first super-admin — still leaves one.
    Livewire::test(ManageUsers::class)
        ->call('confirmDelete', $first->id)
        ->call('delete')
        ->assertHasNoErrors();

    // Try to delete the last remaining super-admin — should fail.
    Livewire::test(ManageUsers::class)
        ->call('confirmDelete', $second->id)
        ->call('delete')
        ->assertForbidden();
});

// -------------------------------------------------------------------
// Status filter + deactivation (covers the trimmed browser flows)
// -------------------------------------------------------------------

test('the user index filters by active status', function () {
    actingAsSuperAdmin();
    User::factory()->withRole('manager')->create(['name' => 'Active Bob', 'is_active' => true]);
    User::factory()->withRole('manager')->create(['name' => 'Inactive Ivy', 'is_active' => false]);

    Livewire::test(ManageUsers::class)
        ->set('statusFilter', 'inactive')
        ->assertSee('Inactive Ivy')
        ->assertDontSee('Active Bob');
});

test('editing a user can deactivate them', function () {
    actingAsSuperAdmin();
    $target = User::factory()->withRole('manager')->create(['is_active' => true]);

    Livewire::test(EditUser::class, ['user' => $target])
        ->set('is_active', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($target->fresh()->is_active)->toBeFalse();
});
