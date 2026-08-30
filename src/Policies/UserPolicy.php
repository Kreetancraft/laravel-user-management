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
     *
     * A delegated administrator may only assign roles whose permissions are a
     * subset of their own. This prevents privilege escalation: you cannot grant
     * what you do not have.
     */
    public function assignRole(Authenticatable $user, string $role): bool
    {
        // Super-admins bypass all checks via Gate::before in the service provider,
        // but we explicitly allow super-admin role assignment here for clarity.
        if ($role === User::superAdminRole()) {
            return Actor::isSuperAdmin($user);
        }

        // User must have basic user-management permission
        if (! $user->can('update-users') && ! $user->can('create-users')) {
            return false;
        }

        // Super-admins can assign any non-super-admin role
        if (Actor::isSuperAdmin($user)) {
            return true;
        }

        // Non-super-admins can only assign roles whose permissions they possess.
        // Load the role and check that every permission it carries is one the
        // actor already has. This prevents a delegated administrator from granting
        // manage-roles, delete-users, or other administrative permissions they lack.
        $roleModel = \Spatie\Permission\Models\Role::findByName($role, 'web');
        
        // Get all permission names from the role
        $rolePermissions = $roleModel->permissions->pluck('name')->all();
        
        // If the role has no permissions, it's safe to assign
        if (empty($rolePermissions)) {
            return true;
        }
        
        // Check if the user has all permissions that the role carries
        foreach ($rolePermissions as $permission) {
            if (! $user->can($permission)) {
                return false;
            }
        }

        return true;
    }
}
