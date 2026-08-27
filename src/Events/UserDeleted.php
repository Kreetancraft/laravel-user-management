<?php

namespace Kreetancraft\UserManagement\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kreetancraft\UserManagement\Models\User;

/**
 * A user was soft-deleted.
 *
 * The name is carried separately because listeners may run after the model has
 * left the result set, and deletion is the one action an audit trail cannot
 * afford to lose.
 */
class UserDeleted
{
    use Dispatchable;

    public function __construct(
        public User $user,
        public string $name,
        public ?User $deletedBy = null,
    ) {}
}
