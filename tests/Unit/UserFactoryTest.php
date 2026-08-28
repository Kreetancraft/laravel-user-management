<?php

use Kreetancraft\UserManagement\Models\User;

beforeEach(function () {
    seedRolesAndPermissions();
});

test('default factory creates an active, verified user', function () {
    $user = User::factory()->create();

    expect($user->is_active)->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->email)->toBeString()
        ->and($user->name)->toBeString();
});

test('unverified state clears email_verified_at', function () {
    $user = User::factory()->unverified()->create();

    expect($user->email_verified_at)->toBeNull();
});

test('inactive state flips is_active to false', function () {
    $user = User::factory()->inactive()->create();

    expect($user->is_active)->toBeFalse();
});

test('withTwoFactor sets up two-factor fields', function () {
    $user = User::factory()->withTwoFactor()->create();

    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_recovery_codes)->not->toBeNull()
        ->and($user->two_factor_confirmed_at)->not->toBeNull();
});

test('withRole assigns the given enum role', function () {
    $user = User::factory()->withRole('manager')->create();

    expect($user->hasRole('manager'))->toBeTrue();
});

test('withRole also accepts a string role name', function () {
    $user = User::factory()->withRole('finance-admin')->create();

    expect($user->hasRole('finance-admin'))->toBeTrue();
});

test('superAdmin shortcut assigns the configured super-admin role', function () {
    $user = User::factory()->superAdmin()->create();

    expect($user->isSuperAdmin())->toBeTrue();
});

test('withRole assigns an arbitrary role, creating it if needed', function (string $role) {
    $user = User::factory()->withRole($role)->create();

    expect($user->hasRole($role))->toBeTrue();
})->with(['editor', 'manager', 'viewer', 'some-role-nobody-predefined']);
