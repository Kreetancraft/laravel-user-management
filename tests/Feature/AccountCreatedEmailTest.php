<?php

use Illuminate\Support\Facades\Notification;
use Kreetancraft\UserManagement\Actions\CreateUserAction;
use Kreetancraft\UserManagement\Data\StoreUserData;
use Kreetancraft\UserManagement\Notifications\Invitation;

beforeEach(function () {
    Notification::fake();
});

test('CreateUserAction creates an invited user without a password', function () {
    $user = CreateUserAction::run(new StoreUserData(
        name: 'John Doe',
        email: 'john@example.com',
        roles: [],
        is_active: true,
    ));

    expect($user->password)->toBeNull()
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->invitation_token)->not->toBeNull()
        ->and(Hash::check('anything', $user->password ?? ''))->toBeFalse();
});

test('CreateUserAction dispatches the invitation notification', function () {
    $user = CreateUserAction::run(new StoreUserData(
        name: 'John Doe',
        email: 'john@example.com',
    ));

    Notification::assertSentTo($user, Invitation::class);
});
