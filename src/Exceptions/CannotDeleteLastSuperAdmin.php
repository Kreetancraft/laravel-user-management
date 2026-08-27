<?php

namespace Kreetancraft\UserManagement\Exceptions;

use RuntimeException;

/**
 * Thrown when deleting a user would leave the application with no super admin.
 *
 * This lives in the action rather than the policy so that it cannot be bypassed
 * by the Gate::before super-admin short-circuit.
 */
class CannotDeleteLastSuperAdmin extends RuntimeException
{
    public static function make(): self
    {
        return new self('Cannot delete the last super admin.');
    }
}
