<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Kreetancraft\UserManagement\Livewire\CreateUser;
use Kreetancraft\UserManagement\Livewire\EditUser;
use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Support\Avatar;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * Avatars could be displayed and never set.
 *
 * User::avatarUrl() read through `avatar_resolver`, and that was the whole
 * seam — the user forms had no image option and no way to gain one. These cover
 * the write half, and the rule that a form on an install with no media package
 * is exactly as it was.
 */
class StubAvatarResolver
{
    /** @var array<string, list<array{id: int, url: string, name: string}>> */
    public static array $stored = [];

    public static function reset(): void
    {
        self::$stored = [];
    }

    public function avatarFor(Model $user): ?string
    {
        return self::$stored[self::key($user)][0]['url'] ?? null;
    }

    public function listFor(Model $user, string $collection): array
    {
        return self::$stored[self::key($user)] ?? [];
    }

    public function syncFor(Model $user, string $collection, array $ids): void
    {
        self::$stored[self::key($user)] = array_map(
            fn ($id) => ['id' => (int) $id, 'url' => '/stub/'.$id.'.jpg', 'name' => 'a.jpg'],
            array_values($ids),
        );
    }

    private static function key(Model $user): string
    {
        return $user->getMorphClass().':'.$user->getKey();
    }
}

beforeEach(function (): void {
    StubAvatarResolver::reset();
    collect(packagePermissions())->each(fn ($p) => Permission::findOrCreate($p, 'web'));

    $admin = User::factory()->create();
    $admin->givePermissionTo(packagePermissions());
    $this->actingAs($admin);
});

function withAvatarSeam(): void
{
    config()->set('user-management.avatar_resolver', StubAvatarResolver::class);
    config()->set('user-management.media_picker_view', 'fixtures-picker');
}

it('reports the seam off until both halves are configured', function (): void {
    expect(Avatar::enabled())->toBeFalse();

    // A resolver that can only read is still not enough to set one — which is
    // exactly the state this package shipped in.
    config()->set('user-management.avatar_resolver', new class
    {
        public function avatarFor(Model $user): ?string
        {
            return null;
        }
    });

    expect(Avatar::enabled())->toBeFalse();

    withAvatarSeam();

    expect(Avatar::enabled())->toBeTrue();
});

it('saves an avatar chosen on the edit form', function (): void {
    withAvatarSeam();
    $user = User::factory()->create();

    Livewire::test(EditUser::class, ['user' => $user])
        ->call('onMediaPicked', [7], 'user-avatar', [['id' => 7, 'url' => '/stub/7.jpg', 'name' => 'a.jpg']])
        ->call('save');

    expect(Avatar::list($user->fresh()))->toHaveCount(1)
        ->and($user->fresh()->avatarUrl())->toBe('/stub/7.jpg');
});

it('saves an avatar chosen while creating a user', function (): void {
    // The attachment is polymorphic on the key, so it can only be written once
    // the row exists — the create path has to sync after the action, not before.
    withAvatarSeam();

    Livewire::test(CreateUser::class)
        ->set('name', 'Avatared')
        ->set('email', 'avatared@example.com')
        ->call('onMediaPicked', [9], 'user-avatar', [['id' => 9, 'url' => '/stub/9.jpg', 'name' => 'a.jpg']])
        ->call('save');

    $user = User::where('email', 'avatared@example.com')->firstOrFail();

    expect($user->avatarUrl())->toBe('/stub/9.jpg');
});

it('ignores a pick meant for another field on the same page', function (): void {
    withAvatarSeam();
    $user = User::factory()->create();

    Livewire::test(EditUser::class, ['user' => $user])
        ->call('onMediaPicked', [3], 'something-else', [['id' => 3, 'url' => '/stub/3.jpg', 'name' => 'x']])
        ->assertSet('avatarMedia', []);
});

it('seeds the field with the avatar already stored', function (): void {
    withAvatarSeam();
    $user = User::factory()->create();
    Avatar::sync($user, [4]);

    Livewire::test(EditUser::class, ['user' => $user])
        ->assertSet('avatarMedia', [['id' => 4, 'url' => '/stub/4.jpg', 'name' => 'a.jpg']]);
});

it('renders nothing when no picker view is configured', function (): void {
    // The forms must be exactly as they were on an install with no media
    // package, not show a control with nothing behind it.
    $html = Blade::render('<x-user-management::avatar-field />');

    expect(trim($html))->toBe('');
});

it('lets the user forms save normally with the seam off', function (): void {
    $user = User::factory()->create();

    Livewire::test(EditUser::class, ['user' => $user])
        ->set('name', 'Renamed')
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('Renamed');
});
