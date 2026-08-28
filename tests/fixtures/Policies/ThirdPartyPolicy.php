<?php

namespace Kreetancraft\UserManagement\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * A policy arriving with an unrelated dependency.
 *
 * Deliberately declares no PERMISSION_SUBJECT and sits outside the application
 * namespace, so discovery must leave it alone. This is the livewire-filemanager
 * case: it registers its own MediaPolicy and FolderPolicy, and inventing
 * permissions for them would expose a dependency nobody meant to.
 */
class ThirdPartyPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    public function restore(Authenticatable $user): bool
    {
        return true;
    }
}
