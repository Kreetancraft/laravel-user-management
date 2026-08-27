<?php

use Kreetancraft\UserManagement\Enums\UserRole;
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
    $user = User::factory()->withRole(UserRole::BookingManager)->create();

    expect($user->hasRole(UserRole::BookingManager->value))->toBeTrue();
});

test('withRole also accepts a string role name', function () {
    $user = User::factory()->withRole('finance-admin')->create();

    expect($user->hasRole('finance-admin'))->toBeTrue();
});

test('superAdmin shortcut assigns the SuperAdmin role', function () {
    $user = User::factory()->superAdmin()->create();

    expect($user->isSuperAdmin())->toBeTrue();
});

test('packageManager shortcut assigns the PackageManager role', function () {
    $user = User::factory()->packageManager()->create();

    expect($user->hasRole(UserRole::PackageManager->value))->toBeTrue();
});

test('bookingManager shortcut assigns the BookingManager role', function () {
    $user = User::factory()->bookingManager()->create();

    expect($user->hasRole(UserRole::BookingManager->value))->toBeTrue();
});

test('financeAdmin shortcut assigns the FinanceAdmin role', function () {
    $user = User::factory()->financeAdmin()->create();

    expect($user->hasRole(UserRole::FinanceAdmin->value))->toBeTrue();
});
