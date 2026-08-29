<?php

namespace Kreetancraft\UserManagement\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Support\Actor;

class UserPolicy
{
    /**
     * Opts this policy into permission discovery, and names the subject its
     * abilities are about: view-users, create-users, update-users, delete-users.
     */
    public const PERMISSION_SUBJECT = 'user';

    /**
     * Super admins bypass every check via Gate::before in the package's service
     * provider.
     *
     * Note the last-super-admin guard is deliberately NOT here: because super
     * admins short-circuit policies, a check in this class would never run for
     * the only people who could trigger it. It lives in DeleteUserAction.
     */
    public function viewAny(Authenticatable $user): bool
    {
        return $user->can('view-users');
    }

    public function view(Authenticatable $user, Authenticatable $target): bool
    {
        return $user->can('view-users');
    }

    public function create(Authenticatable $user): bool
    {
        return $user->can('create-users');
    }

    public function update(Authenticatable $user, Authenticatable $target): bool
    {
        if (! $user->can('update-users')) {
            return false;
        }

        return ! (Actor::isSuperAdmin($target) && ! Actor::isSuperAdmin($user));
    }

    public function delete(Authenticatable $user, Authenticatable $target): bool
    {
        if (! $user->can('delete-users')) {
            return false;
        }

        if ($user->getAuthIdentifier() === $target->getAuthIdentifier()) {
            return false;
        }

        return ! (Actor::isSuperAdmin($target) && ! Actor::isSuperAdmin($user));
    }

    /**
     * Authorize assigning a specific role to a user.
     *
     * Roles are database rows created at runtime, so this takes a role name
     * rather than an enum case.
     */
    public function assignRole(Authenticatable $user, string $role): bool
    {
        if ($role === User::superAdminRole()) {
            return Actor::isSuperAdmin($user);
        }

        return $user->can('update-users') || $user->can('create-users');
    }
}
