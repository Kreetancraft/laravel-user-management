<?php

use Illuminate\Support\Facades\Event;
use Kreetancraft\UserManagement\Actions\UpdateUserAction;
use Kreetancraft\UserManagement\Data\UpdateUserData;
use Kreetancraft\UserManagement\Events\UserUpdated;
use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Support\Actor;
use Kreetancraft\UserManagement\Tests\Fixtures\Models\AppUser;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Most apps keep the App\Models\User a fresh Laravel install gives them, which
 * extends Illuminate\Foundation\Auth\User and not this package's model.
 *
 * The package used to type the *actor* — whoever pressed the button — as its own
 * User. Saving from the edit screen then died with a TypeError inside an event
 * constructor, and the same hint on the policy would have denied or crashed for
 * anyone who was not a super admin (they got past it only because Gate::before
 * short-circuits policies for super admins, hiding the bug behind the one role
 * most likely to be testing).
 *
 * The subject of an action is route-bound and stays typed. The actor is asked
 * only what any authenticatable can answer.
 */
beforeEach(function (): void {
    // The actor's model is the app's, not the package's.
    config()->set('auth.providers.users.model', AppUser::class);
});

it('updates a user when the actor is the app\'s own User model', function (): void {
    Event::fake([UserUpdated::class]);

    Role::findOrCreate('super-admin', 'web');
    $actor = AppUser::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => 'x']);
    $actor->assignRole('super-admin');
    $this->actingAs($actor);

    $subject = User::create(['name' => 'Before', 'email' => 'subject@example.com', 'password' => 'x']);

    UpdateUserAction::run($subject, new UpdateUserData(name: 'After', email: 'subject@example.com'));

    expect($subject->fresh()->name)->toBe('After');

    Event::assertDispatched(UserUpdated::class, fn (UserUpdated $e): bool => $e->updatedBy?->getAuthIdentifier() === $actor->getKey());
});

it('runs the policy for a non-super-admin actor of the app\'s model', function (): void {
    // Not a super admin, so Gate::before does not short-circuit and the policy
    // itself has to accept this actor.
    Permission::findOrCreate('update-users', 'web');
    $role = Role::findOrCreate('editor', 'web');
    $role->givePermissionTo('update-users');

    $actor = AppUser::create(['name' => 'Editor', 'email' => 'editor@example.com', 'password' => 'x']);
    $actor->assignRole($role);
    $this->actingAs($actor);

    $subject = User::create(['name' => 'Target', 'email' => 'target@example.com', 'password' => 'x']);

    expect($actor->can('update', $subject))->toBeTrue();
});

it('still refuses to let a non-super-admin touch a super admin', function (): void {
    Permission::findOrCreate('update-users', 'web');
    $role = Role::findOrCreate('editor', 'web');
    $role->givePermissionTo('update-users');
    Role::findOrCreate('super-admin', 'web');

    $actor = AppUser::create(['name' => 'Editor', 'email' => 'editor2@example.com', 'password' => 'x']);
    $actor->assignRole($role);

    $target = User::create(['name' => 'Boss', 'email' => 'boss@example.com', 'password' => 'x']);
    $target->assignRole('super-admin');

    expect($actor->can('update', $target))->toBeFalse();
});

it('recognises a super admin on either model', function (): void {
    Role::findOrCreate('super-admin', 'web');

    $app = AppUser::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'x']);
    $app->assignRole('super-admin');

    $own = User::create(['name' => 'B', 'email' => 'b@example.com', 'password' => 'x']);
    $own->assignRole('super-admin');

    $plain = AppUser::create(['name' => 'C', 'email' => 'c@example.com', 'password' => 'x']);

    expect(Actor::isSuperAdmin($app))->toBeTrue()
        ->and(Actor::isSuperAdmin($own))->toBeTrue()
        ->and(Actor::isSuperAdmin($plain))->toBeFalse()
        ->and(Actor::isSuperAdmin(null))->toBeFalse();
});
