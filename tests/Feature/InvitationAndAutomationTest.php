<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Kreetancraft\UserManagement\Actions\CreateUserAction;
use Kreetancraft\UserManagement\Actions\DeactivateUserAction;
use Kreetancraft\UserManagement\Actions\DeleteUserAction;
use Kreetancraft\UserManagement\Actions\SetUserPasswordAction;
use Kreetancraft\UserManagement\Actions\UpdateUserAction;
use Kreetancraft\UserManagement\Contracts\UserContract;
use Kreetancraft\UserManagement\Data\StoreUserData;
use Kreetancraft\UserManagement\Data\UpdateUserData;
use Kreetancraft\UserManagement\Events\UserDeactivated;
use Kreetancraft\UserManagement\Events\UserInvited;
use Kreetancraft\UserManagement\Exceptions\CannotDeleteLastSuperAdmin;
use Kreetancraft\UserManagement\Exceptions\CannotDeleteOwnAccount;
use Kreetancraft\UserManagement\Livewire\EditUser;
use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Notifications\AccountCreated;
use Kreetancraft\UserManagement\Notifications\Invitation;
use Kreetancraft\UserManagement\Notifications\UserDeactivated as UserDeactivatedNotification;
use Laravel\Fortify\Features;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    seedRolesAndPermissions();
});

// -------------------------------------------------------------------
// Invitation flow (public set-password)
// -------------------------------------------------------------------

test('CreateUserAction creates an invited user with no password and a token', function () {
    $user = CreateUserAction::run(new StoreUserData(
        name: 'Jane Doe',
        email: 'jane@example.com',
        roles: [],
        is_active: true,
    ));

    expect($user->password)->toBeNull()
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->invitation_token)->not->toBeNull()
        ->and($user->invitation_sent_at)->not->toBeNull();
});

test('CreateUserAction sends the Invitation notification', function () {
    Notification::fake();

    $user = CreateUserAction::run(new StoreUserData(
        name: 'Jane Doe',
        email: 'jane@example.com',
    ));

    Notification::assertSentTo($user, Invitation::class);
});

test('invitation token resolves the user via the repository', function () {
    $user = User::factory()->invited()->create();
    $token = $user->invitation_token;

    $found = app(UserContract::class)->findByInvitationToken($token);

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($user->id);
});

test('setting a password from an invitation activates the account', function () {
    Notification::fake();

    $user = User::factory()->invited()->create();
    SetUserPasswordAction::run($user, 'brand-new-pass-123');

    $user->refresh();
    expect(Hash::check('brand-new-pass-123', $user->password))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->invitation_token)->toBeNull()
        ->and($user->invitation_sent_at)->toBeNull();

    Notification::assertSentTo($user, AccountCreated::class);
});

// -------------------------------------------------------------------
// Event + notification automation
// -------------------------------------------------------------------

test('deactivating a user dispatches UserDeactivated and notifies them', function () {
    Notification::fake();

    $target = User::factory()->create();
    $admin = actingAsSuperAdmin();

    DeactivateUserAction::run($target);

    Notification::assertSentTo($target, UserDeactivatedNotification::class);

    expect($target->fresh()->is_active)->toBeFalse();
});

test('deactivating via the edit form fires UserDeactivated', function () {
    Event::fake();

    $target = User::factory()->withRole('manager')->create(['is_active' => true]);
    actingAsSuperAdmin();

    Livewire::test(EditUser::class, ['user' => $target])
        ->set('is_active', false)
        ->call('save')
        ->assertHasNoErrors();

    Event::assertDispatched(UserDeactivated::class);
});

// -------------------------------------------------------------------
// DI + repository
// -------------------------------------------------------------------

test('CreateUserAction resolves the UserRepository via constructor injection', function () {
    $action = app(CreateUserAction::class);
    expect($action)->toBeInstanceOf(CreateUserAction::class);
});

test('UpdateUserAction persists changes through the repository transaction', function () {
    $target = User::factory()->create(['name' => 'Old Name']);

    UpdateUserAction::run($target, UpdateUserData::from([
        'name' => 'New Name',
        'email' => $target->email,
        'is_active' => true,
        'enforce_2fa' => true,
        'roles' => [],
    ]));

    $target->refresh();
    expect($target->name)->toBe('New Name')
        ->and($target->enforce_2fa)->toBeTrue();
});

test('DeleteUserAction soft-deletes and detaches roles', function () {
    $target = User::factory()->withRole('manager')->create();

    DeleteUserAction::run($target);

    expect($target->fresh()->trashed())->toBeTrue()
        ->and($target->fresh()->roles)->toBeEmpty();
});

test('a transaction rollback keeps the user when the delete fails', function () {
    $target = User::factory()->create();
    $originalId = $target->id;

    DB::partialMock()->shouldReceive('transaction')->andThrow(new RuntimeException('boom'));

    try {
        DeleteUserAction::run($target);
    } catch (RuntimeException) {
        // expected
    }

    expect(User::withTrashed()->find($originalId))->not->toBeNull();
});

// -------------------------------------------------------------------
// RBAC catalog integrity
// -------------------------------------------------------------------

test('the seeder and the permission catalog agree on user permissions', function () {
    $seeded = Permission::where('name', 'like', '%-users')
        ->orWhere('name', 'like', '%-roles')
        ->pluck('name')->sort()->values()->all();

    expect($seeded)->not->toBeEmpty()
        ->and($seeded)->toContain('view-users', 'create-users', 'edit-users', 'delete-users', 'manage-roles');
});

// -------------------------------------------------------------------
// EnsureUserIsActive + 2FA gate
// -------------------------------------------------------------------

test('a deactivated user is redirected to login on the next request', function () {
    $user = User::factory()->inactive()->create();
    $this->actingAs($user);

    $this->get(route(config('user-management.routes.names.users.index', 'admin.users')))->assertRedirect(route(config('user-management.routes.names.login', 'login')));
});

test('a user with enforce_2fa but no 2FA enrolled is blocked by the gate', function () {
    $role = Role::create(['name' => 'gate-viewer', 'guard_name' => 'web']);
    $role->givePermissionTo('view-users');
    $user = User::factory()->requiringTwoFactor()->create();
    $user->assignRole($role);
    $this->actingAs($user);

    $this->get(route(config('user-management.routes.names.users.index', 'admin.users')))
        ->assertRedirect(route('security.edit'));
});

test('a user with enforce_2fa and 2FA enrolled passes the gate', function () {
    $role = Role::create(['name' => 'gate-viewer', 'guard_name' => 'web']);
    $role->givePermissionTo('view-users');
    $user = User::factory()->requiringTwoFactor()->withTwoFactor()->create();
    $user->assignRole($role);
    $this->actingAs($user);

    $this->get(route(config('user-management.routes.names.users.index', 'admin.users')))->assertOk();
});

// -------------------------------------------------------------------
// UserPolicy
// -------------------------------------------------------------------

test('a user cannot delete their own account', function () {
    $admin = actingAsSuperAdmin();

    // Enforced in the action, not the policy: super admins bypass policies
    // via Gate::before, so a policy check would never run for them.
    expect(fn () => DeleteUserAction::run($admin))
        ->toThrow(CannotDeleteOwnAccount::class);
});

test('the last super-admin cannot be deleted', function () {
    $admin = actingAsSuperAdmin();
    $other = User::factory()->create();
    $this->actingAs($other);

    expect(fn () => DeleteUserAction::run($admin))
        ->toThrow(CannotDeleteLastSuperAdmin::class);
});

test('non-super-admins cannot assign the super-admin role', function () {
    $user = User::factory()->withRole('manager')->create();
    $this->actingAs($user);

    expect(Gate::forUser($user)->check('assignRole', [User::class, User::superAdminRole()]))
        ->toBeFalse();
});

test('assigning super-admin role is allowed for super-admins', function () {
    $admin = actingAsSuperAdmin();
    $other = User::factory()->create();

    expect(Gate::forUser($admin)->check('assignRole', [User::class, User::superAdminRole()]))
        ->toBeTrue();
});

// -------------------------------------------------------------------
// Activity log
// -------------------------------------------------------------------

test('creating a user dispatches UserInvited for an audit listener to consume', function () {
    Event::fake([UserInvited::class]);
    actingAsSuperAdmin();

    $user = CreateUserAction::run(new StoreUserData(
        name: 'Logged User',
        email: 'logged@example.com',
    ));

    // This package logs nothing itself; it emits events instead.
    Event::assertDispatched(UserInvited::class, fn ($e) => $e->user->is($user));
});

// -------------------------------------------------------------------
// Two-factor enrollment
// -------------------------------------------------------------------

test('a user can enroll two-factor authentication', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    $user = User::factory()->create();
    $user->password = bcrypt('password');
    $user->save();
    $this->actingAs($user);

    $response = $this->post(route('two-factor.enable'), [
        'password' => 'password',
    ]);

    // Fortify requires password confirmation before enabling 2FA.
    if ($response->status() === 302 && str_contains((string) $response->headers->get('Location'), 'confirm-password')) {
        $this->post(route('password.confirm.store'), [
            'password' => 'password',
        ])->assertRedirect();

        $response = $this->post(route('two-factor.enable'), [
            'password' => 'password',
        ]);
    }

    $response->assertRedirect();

    expect($user->fresh()->hasEnabledTwoFactor())->toBeTrue();
});
