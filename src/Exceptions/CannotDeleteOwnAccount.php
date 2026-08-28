<?php

namespace Kreetancraft\UserManagement\Exceptions;

use RuntimeException;

/**
 * Thrown when a user tries to delete their own account.
 *
 * Enforced in the action rather than the policy because super admins
 * short-circuit policies via Gate::before — so a policy check would never run
 * for the people most able to trigger it.
 */
class CannotDeleteOwnAccount extends RuntimeException
{
    public static function make(): self
    {
        return new self('You cannot delete your own account.');
    }
}
