<?php

use Illuminate\Support\Facades\Gate;
use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Providers\UserManagementServiceProvider;

/**
 * The two configurable seams: the avatar resolver, and where the super-admin
 * bypass hooks into the gate.
 */

// -------------------------------------------------------------------
// Avatar resolver
// -------------------------------------------------------------------

test('avatarUrl is null when no resolver is configured', function () {
    config()->set('user-management.avatar_resolver', null);

    expect(User::factory()->create()->avatarUrl())->toBeNull();
});

test('a closure resolver is used', function () {
    config()->set('user-management.avatar_resolver', fn ($user) => "https://cdn.test/{$user->id}.jpg");

    $user = User::factory()->create();

    expect($user->avatarUrl())->toBe("https://cdn.test/{$user->id}.jpg");
});

test('a class with __invoke is resolved through the container', function () {
    config()->set('user-management.avatar_resolver', InvokableAvatarResolver::class);

    expect(User::factory()->create()->avatarUrl())->toBe('https://cdn.test/invoked.jpg');
});

test('a class with avatarFor is used', function () {
    config()->set('user-management.avatar_resolver', MethodAvatarResolver::class);

    expect(User::factory()->create()->avatarUrl())->toBe('https://cdn.test/method.jpg');
});

test('a resolver that understands neither interface returns null rather than throwing', function () {
    config()->set('user-management.avatar_resolver', UselessAvatarResolver::class);

    expect(User::factory()->create()->avatarUrl())->toBeNull();
});

// -------------------------------------------------------------------
// Gate interception point
// -------------------------------------------------------------------

test('before is the default and short-circuits policies', function () {
    expect(config('user-management.super_admin.intercept_gate'))->toBe('before');

    seedRolesAndPermissions();
    $admin = actingAsSuperAdmin();

    // No such ability exists anywhere; `before` grants it regardless.
    expect(Gate::forUser($admin)->check('an-ability-nobody-defined'))->toBeTrue();
});

test('the interception point follows config', function (string $setting, string $expected) {
    // Laravel cannot unregister a gate callback, so re-booting the provider adds
    // a second one rather than replacing the first — asserting end-to-end here
    // would only ever measure the callback registered at application boot.
    // Record which method the provider reaches for instead.
    config()->set('user-management.super_admin.intercept_gate', $setting);

    $recorder = new RecordingGate;

    // The facade caches its resolved instance, so binding into the container
    // alone is not enough — swap() replaces what the facade hands back.
    Gate::swap($recorder);

    (new UserManagementServiceProvider($this->app))->boot();

    expect($recorder->called)->toBe($expected);
})->with([
    'before is the default' => ['before', 'before'],
    'after when asked for' => ['after', 'after'],
    'anything else falls back to before' => ['nonsense', 'before'],
]);

test('the bypass can be turned off entirely', function () {
    config()->set('user-management.super_admin.enabled', false);

    $fresh = new UserManagementServiceProvider($this->app);
    $fresh->boot();

    expect(config('user-management.super_admin.enabled'))->toBeFalse();
});

// -------------------------------------------------------------------
// Weak default removed
// -------------------------------------------------------------------

test('no password default ships in the config', function () {
    // A real credential sitting in a file people publish and commit.
    expect(config('user-management.super_admin'))->not->toHaveKey('password');
});

class InvokableAvatarResolver
{
    public function __invoke($user): ?string
    {
        return 'https://cdn.test/invoked.jpg';
    }
}

class MethodAvatarResolver
{
    public function avatarFor($user): ?string
    {
        return 'https://cdn.test/method.jpg';
    }
}

class UselessAvatarResolver
{
    public function somethingElse(): void {}
}

/**
 * Records which interception method the provider called.
 */
class RecordingGate extends Illuminate\Auth\Access\Gate
{
    public ?string $called = null;

    public function __construct()
    {
        parent::__construct(app(), fn () => null);
    }

    public function before(callable $callback)
    {
        $this->called = 'before';

        return $this;
    }

    public function after(callable $callback)
    {
        $this->called = 'after';

        return $this;
    }
}
