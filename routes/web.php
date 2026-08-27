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
    Route::get('set-password/{token}', SetPassword::class)
        ->name('user.invitation.set-password');
});

// Admin impersonation (super-admins only, gated in the User model). Hard dependency.
if (method_exists(\Illuminate\Support\Facades\Route::class, 'impersonate') || \Illuminate\Support\Facades\Route::hasMacro('impersonate')) {
    Route::middleware(['auth', 'verified', 'can:view-users'])->group(function () {
        Route::impersonate();
    });
}

$prefix = config('user-management.routes.prefix', 'admin');
$middleware = config('user-management.routes.middleware', ['auth', 'verified', 'ensure.2fa.enforced']);

Route::middleware($middleware)->prefix($prefix)->group(function () {
    // Users
    Route::middleware('can:create-users')->group(function () {
        Route::get('users/create', CreateUser::class)->name('admin.users.create');
    });

    Route::middleware('can:view-users')->group(function () {
        Route::get('users', ManageUsers::class)->name('admin.users');
        Route::get('users/{user}', ShowUser::class)->name('admin.users.show');
    });

    Route::middleware('can:edit-users')->group(function () {
        Route::get('users/{user}/edit', EditUser::class)->name('admin.users.edit');
    });

    // Roles
    Route::middleware('can:manage-roles')->group(function () {
        Route::get('roles', ManageRoles::class)->name('admin.roles');
        Route::get('roles/create', CreateRole::class)->name('admin.roles.create');
        Route::get('roles/{role}/edit', EditRole::class)->name('admin.roles.edit');
    });
});
