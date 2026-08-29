<?php

namespace Kreetancraft\UserManagement\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Kreetancraft\UserManagement\Models\User;

/**
 * The person performing an action — whoever `config('auth.providers.users.model')`
 * names, which is not necessarily this package's User.
 *
 * Typing the actor as that User is what broke: an app whose App\Models\User extends
 * Illuminate\Foundation\Auth\User rather than ours crashes with a TypeError the
 * moment auth()->user() reaches an event constructor or a policy. Extending is a
 * reasonable thing to recommend; it is not reasonable to require it just to record
 * who pressed the button.
 *
 * The *subject* of an action is route-bound and stays typed. The actor is only ever
 * asked two things, so ask them by duck type — the same seam the media, blog and seo
 * packages already use.
 */
final class Actor
{
    /**
     * Equivalent to User::isSuperAdmin(), which is itself only a hasRole() call, but
     * answerable for any model using Spatie's HasRoles.
     */
    public static function isSuperAdmin(?Authenticatable $user): bool
    {
        return $user !== null
            && method_exists($user, 'hasRole')
            && (bool) $user->hasRole(User::superAdminRole());
    }

    /**
     * A display name for attribution, when the app's user model has one.
     */
    public static function name(?Authenticatable $user): ?string
    {
        $name = data_get($user, 'name');

        return is_string($name) && $name !== '' ? $name : null;
    }
}
