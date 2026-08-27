<?php

namespace Kreetancraft\UserManagement\Policies;

use Kreetancraft\UserManagement\Models\User;

class UserPolicy
{
    /**
     * Super admins bypass every check via Gate::before in the package's service
     * provider.
     *
     * Note the last-super-admin guard is deliberately NOT here: because super
     * admins short-circuit policies, a check in this class would never run for
     * the only people who could trigger it. It lives in DeleteUserAction.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-users');
    }

    public function view(User $user, User $target): bool
    {
        return $user->can('view-users');
    }

    public function create(User $user): bool
    {
        return $user->can('create-users');
    }

    public function update(User $user, User $target): bool
    {
        if (! $user->can('edit-users')) {
            return false;
        }

        return ! ($target->isSuperAdmin() && ! $user->isSuperAdmin());
    }

    public function delete(User $user, User $target): bool
    {
        if (! $user->can('delete-users')) {
            return false;
        }

        if ($user->id === $target->id) {
            return false;
        }

        return ! ($target->isSuperAdmin() && ! $user->isSuperAdmin());
    }

    /**
     * Authorize assigning a specific role to a user.
     *
     * Roles are database rows created at runtime, so this takes a role name
     * rather than an enum case.
     */
    public function assignRole(User $user, string $role): bool
    {
        if ($role === User::superAdminRole()) {
            return $user->isSuperAdmin();
        }

        return $user->can('edit-users') || $user->can('create-users');
    }
}
