<?php

use Kreetancraft\UserManagement\Support\Avatar;

/**
 * The avatar field renders nothing unless several separate things are true, and
 * every one of them fails identically: a form with no avatar section and no
 * error. That is correct on an install with no media package and
 * indistinguishable from a misconfiguration, which cost real time to diagnose
 * by guessing.
 *
 * The command exists so the answer is one line instead of a conversation.
 */
it('names the missing resolver', function (): void {
    config()->set('user-management.avatar_resolver', null);

    $this->artisan('user-management:avatar-doctor')
        ->expectsOutputToContain('avatar_resolver is set')
        ->assertFailed();
});

it('names a resolver class that is not installed', function (): void {
    config()->set('user-management.avatar_resolver', 'Acme\\Nope\\MissingResolver');

    $this->artisan('user-management:avatar-doctor')
        ->expectsOutputToContain('not installed')
        ->assertFailed();
});

it('catches a resolver that can only read', function (): void {
    // The state this package actually shipped in: an avatar could be displayed
    // and nothing could ever store one.
    config()->set('user-management.avatar_resolver', new class
    {
        public function avatarFor($user): ?string
        {
            return null;
        }
    });
    config()->set('user-management.media_picker_view', 'fixtures-picker');

    expect(Avatar::enabled())->toBeFalse();

    $this->artisan('user-management:avatar-doctor')
        ->expectsOutputToContain('syncFor')
        ->assertFailed();
});

it('names a picker view that does not exist', function (): void {
    config()->set('user-management.avatar_resolver', StubAvatarResolver::class);
    config()->set('user-management.media_picker_view', 'acme::no-such-picker');

    $this->artisan('user-management:avatar-doctor')
        ->expectsOutputToContain('no such view')
        ->assertFailed();
});

it('passes when both halves are configured', function (): void {
    config()->set('user-management.avatar_resolver', StubAvatarResolver::class);
    config()->set('user-management.media_picker_view', 'fixtures-picker');

    $this->artisan('user-management:avatar-doctor')
        ->expectsOutputToContain('should be rendering')
        ->assertSuccessful();
});

it('says when the profile page still opens the library', function (): void {
    config()->set('user-management.avatar_resolver', StubAvatarResolver::class);
    config()->set('user-management.media_picker_view', 'fixtures-picker');
    config()->set('user-management.avatar_uploader', null);

    $this->artisan('user-management:avatar-doctor')
        ->expectsOutputToContain('opens the media library')
        ->assertSuccessful();
});

it('names an uploader component that is not registered', function (): void {
    config()->set('user-management.avatar_resolver', StubAvatarResolver::class);
    config()->set('user-management.media_picker_view', 'fixtures-picker');
    config()->set('user-management.avatar_uploader', 'media.avatar-uploader');

    // The media package is not installed in this suite, so the component is not
    // registered — which is exactly the skew this reports.
    $this->artisan('user-management:avatar-doctor')
        ->expectsOutputToContain('no such Livewire component')
        ->assertFailed();
});
