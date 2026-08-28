<?php

namespace Kreetancraft\UserManagement\Tests\Fixtures\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Kreetancraft\UserManagement\Tests\Fixtures\Models\Widget;

/**
 * A policy as another package would ship it.
 *
 * Deliberately has NO update() method, so the tests can prove only declared
 * methods produce permissions.
 */
class WidgetPolicy
{
    /** How a package opts into discovery. */
    public const PERMISSION_SUBJECT = 'widget';

    public function viewAny(Authenticatable $user): bool
    {
        return $user->can('view-widgets');
    }

    public function view(Authenticatable $user, Widget $widget): bool
    {
        return $user->can('view-widgets');
    }

    public function create(Authenticatable $user): bool
    {
        return $user->can('create-widgets');
    }

    public function delete(Authenticatable $user, Widget $widget): bool
    {
        return $user->can('delete-widgets');
    }
}
