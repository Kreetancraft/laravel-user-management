<?php

use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Providers\FortifyServiceProvider;
use Laravel\Fortify\Features;

/**
 * Regression cover for a silent breakage.
 *
 * An earlier syncFortifyFeatures() rebuilt fortify.features from scratch inside
 * register(), before Fortify's own config had been merged. It read an empty list
 * and wrote back only what this package explicitly enabled — quietly removing
 * password reset and email verification from any host app. Nothing failed
 * loudly; /forgot-password simply 404'd.
 */
test('features this package does not disable are left alone', function () {
    $features = (array) config('fortify.features');

    expect($features)
        ->toContain(Features::resetPasswords())
        ->toContain(Features::emailVerification());
});

test('registration is withdrawn while the package feature flag is off', function () {
    expect(config('user-management.features.registration'))->toBeFalse()
        ->and((array) config('fortify.features'))->not->toContain(Features::registration());
});

test('the sync never adds a feature the host had not enabled', function () {
    // The host's fortify config is authoritative for what is ON. This package
    // may only withdraw a feature whose view it declines to register.
    config()->set('fortify.features', [Features::resetPasswords()]);
    config()->set('user-management.features.two_factor', true);

    (new FortifyServiceProvider($this->app))->boot();

    expect((array) config('fortify.features'))
        ->toBe([Features::resetPasswords()]);
});

test('users can be created without a password so invitations work', function () {
    // The invitation flow persists a user with no password; they set it via the
    // emailed link. Laravel's stock users table declares password NOT NULL, so
    // the package ships a migration making it nullable.
    $user = User::create([
        'name' => 'Invited',
        'email' => 'invited@example.com',
    ]);

    expect($user->password)->toBeNull()
        ->and($user->exists)->toBeTrue();
});
