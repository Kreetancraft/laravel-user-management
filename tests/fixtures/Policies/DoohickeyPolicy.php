<?php

namespace Kreetancraft\UserManagement\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * A policy whose subject does not pluralise the way the inflector expects.
 */
class DoohickeyPolicy
{
    public const PERMISSION_SUBJECT = 'kit';

    public const PERMISSION_SUBJECT_PLURAL = 'kit';

    public function viewAny(Authenticatable $user): bool
    {
        return $user->can('view-kit');
    }
}
