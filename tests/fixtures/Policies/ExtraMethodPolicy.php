<?php

namespace Kreetancraft\UserManagement\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * A policy with an ability outside the CRUD set, declared so it is generated.
 */
class ExtraMethodPolicy
{
    public const PERMISSION_SUBJECT = 'widget';

    public const PERMISSION_EXTRA_METHODS = ['publish'];

    public function viewAny(Authenticatable $user): bool
    {
        return $user->can('view-widgets');
    }

    public function publish(Authenticatable $user): bool
    {
        return $user->can('publish-widgets');
    }
}
