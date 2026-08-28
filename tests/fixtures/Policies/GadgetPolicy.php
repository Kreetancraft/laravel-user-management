<?php

namespace Kreetancraft\UserManagement\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Declares its own subject, as a package policy does when the model name would
 * make an unhelpful permission.
 */
class GadgetPolicy
{
    public const PERMISSION_SUBJECT = 'thingamy';

    public function viewAny(Authenticatable $user): bool
    {
        return $user->can('view-thingamies');
    }
}
