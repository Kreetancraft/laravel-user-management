<?php

use Illuminate\Support\Facades\Route;
use Kreetancraft\UserManagement\Livewire\CreateRole;
use Kreetancraft\UserManagement\Livewire\CreateUser;
use Kreetancraft\UserManagement\Livewire\EditRole;
use Kreetancraft\UserManagement\Livewire\EditUser;
use Kreetancraft\UserManagement\Livewire\ManageRoles;
use Kreetancraft\UserManagement\Livewire\ManageUsers;
use Kreetancraft\UserManagement\Livewire\SetPassword;
use Kreetancraft\UserManagement\Livewire\ShowUser;

// Public: complete an invitation by setting your own password.
Route::middleware('throttle:6,1')->group(function () {
    $invitationRoute = config('user-management.routes.names.invitation', 'user.invitation.set-password');

    Route::get('set-password/{token}', SetPassword::class)
        ->name($invitationRoute);
});

// Admin impersonation (super-admins only, gated in the User model). Hard dependency.
if (method_exists(Route::class, 'impersonate') || Route::hasMacro('impersonate')) {
    Route::middleware(['auth', 'verified', 'can:view-users'])->group(function () {
        Route::impersonate();
    });
}

$prefix = config('user-management.routes.prefix', 'admin');
$middleware = config('user-management.routes.middleware', ['auth', 'verified', 'ensure.2fa.enforced']);
$names = config('user-management.routes.names', []);

Route::middleware($middleware)->prefix($prefix)->group(function () use ($names) {
    // Users
    Route::middleware('can:create-users')->group(function () use ($names) {
        Route::get('users/create', CreateUser::class)->name($names['users']['create'] ?? 'admin.users.create');
    });

    Route::middleware('can:view-users')->group(function () use ($names) {
        Route::get('users', ManageUsers::class)->name($names['users']['index'] ?? 'admin.users');
        Route::get('users/{user}', ShowUser::class)->name($names['users']['show'] ?? 'admin.users.show');
    });

    Route::middleware('can:edit-users')->group(function () use ($names) {
        Route::get('users/{user}/edit', EditUser::class)->name($names['users']['edit'] ?? 'admin.users.edit');
    });

    // Roles
    Route::middleware('can:manage-roles')->group(function () use ($names) {
        Route::get('roles', ManageRoles::class)->name($names['roles']['index'] ?? 'admin.roles');
        Route::get('roles/create', CreateRole::class)->name($names['roles']['create'] ?? 'admin.roles.create');
        Route::get('roles/{role}/edit', EditRole::class)->name($names['roles']['edit'] ?? 'admin.roles.edit');
    });
});
