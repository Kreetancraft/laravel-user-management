<?php

use Illuminate\Auth\Events\Login;
use Kreetancraft\UserManagement\Enums\UserRole;
use Kreetancraft\UserManagement\Listeners\RecordUserLogin;
use Kreetancraft\UserManagement\Models\User;

beforeEach(function () {
    seedRolesAndPermissions();
});

test('initials returns first letter of first two name parts', function () {
    $user = User::factory()->make(['name' => 'Jane Doe']);
    expect($user->initials())->toBe('JD');
});

test('initials handles single names', function () {
    $user = User::factory()->make(['name' => 'Madonna']);
    expect($user->initials())->toBe('M');
});

test('initials caps at two letters for long names', function () {
    $user = User::factory()->make(['name' => 'Maria Anna Bella Costa']);
    expect($user->initials())->toBe('MA');
});

test('isSuperAdmin returns true only for users with the super-admin role', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    $bookingManager = User::factory()->bookingManager()->create();

    expect($superAdmin->isSuperAdmin())->toBeTrue()
        ->and($bookingManager->isSuperAdmin())->toBeFalse();
});

test('primaryRole returns the user role enum', function () {
    $user = User::factory()->packageManager()->create();

    expect($user->primaryRole())->toBe(UserRole::PackageManager->value);
});

test('primaryRole returns null for a user without roles', function () {
    $user = User::factory()->create();

    expect($user->primaryRole())->toBeNull();
});

test('active scope only returns active users', function () {
    User::factory()->count(3)->create(['is_active' => true]);
    User::factory()->count(2)->inactive()->create();

    expect(User::active()->count())->toBe(3);
});

test('withRole scope filters by enum', function () {
    User::factory()->superAdmin()->create();
    User::factory()->bookingManager()->count(2)->create();

    expect(User::withRole(UserRole::BookingManager->value)->count())->toBe(2)
        ->and(User::withRole(UserRole::SuperAdmin->value)->count())->toBe(1);
});

test('soft delete keeps the user discoverable via withTrashed', function () {
    $user = User::factory()->create();
    $user->delete();

    expect(User::find($user->id))->toBeNull()
        ->and(User::withTrashed()->find($user->id))->not->toBeNull()
        ->and(User::withTrashed()->find($user->id)->trashed())->toBeTrue();
});

test('password is hashed when set', function () {
    $user = User::factory()->create(['password' => 'plain-text-pass']);

    expect($user->password)->not->toBe('plain-text-pass')
        ->and(password_verify('plain-text-pass', $user->password))->toBeTrue();
});

test('RecordUserLogin listener stamps the user with current time and ip', function () {
    $user = User::factory()->create([
        'last_login_at' => null,
        'last_login_ip' => null,
    ]);

    $event = new Login(
        guard: 'web',
        user: $user,
        remember: false,
    );

    (new RecordUserLogin)->handle($event);

    $user->refresh();
    expect($user->last_login_at)->not->toBeNull()
        ->and($user->last_login_ip)->not->toBeNull();
});

test('password and two_factor fields are hidden from serialization', function () {
    $user = User::factory()->create();
    $array = $user->toArray();

    expect($array)->not->toHaveKey('password')
        ->and($array)->not->toHaveKey('two_factor_secret')
        ->and($array)->not->toHaveKey('two_factor_recovery_codes')
        ->and($array)->not->toHaveKey('remember_token');
});
