<?php

use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Support\Avatar;

/**
 * An avatar set on the profile page did not appear in the users list.
 *
 * The documented way to add relations is to extend this package's User model.
 * The application then saves from its profile page as App\Models\User, while
 * this package's own screens query its own class — so the attachment was
 * written under one type name and looked up under another. The row existed and
 * nothing found it.
 */
class HostUser extends User
{
    // What an application's own App\Models\User is: an extension of ours, on
    // the same table.
    protected $table = 'users';
}

it('reports the configured user model, not the class that ran the query', function (): void {
    config()->set('auth.providers.users.model', HostUser::class);

    $viaPackageClass = new User;
    $viaHostClass = new HostUser;

    expect($viaPackageClass->getMorphClass())
        ->toBe(HostUser::class)
        ->toBe($viaHostClass->getMorphClass());
});

it('finds an avatar saved by the host class when the package class reads it', function (): void {
    config()->set('auth.providers.users.model', HostUser::class);
    config()->set('user-management.avatar_resolver', StubAvatarResolver::class);
    config()->set('user-management.media_picker_view', 'fixtures-picker');

    // Saved the way the profile page saves it.
    $asHost = HostUser::create([
        'name' => 'Pictured',
        'email' => 'pictured@example.com',
        'password' => bcrypt('secret-password'),
    ]);
    Avatar::sync($asHost, [21]);

    // Read the way the users list reads it: through this package's own class.
    $asPackage = User::find($asHost->getKey());

    expect($asPackage->avatarUrl())->toBe('/stub/21.jpg');
});

it('falls back to itself when no user model is configured', function (): void {
    config()->set('auth.providers.users.model', null);

    expect((new User)->getMorphClass())->toBe(User::class);
});
