<?php

namespace Kreetancraft\UserManagement\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kreetancraft\UserManagement\Models\User;

/**
 * A user's attributes changed.
 *
 * `$changes` carries the before/after diff so an audit listener can record what
 * actually changed without re-querying.
 *
 * @param  array<string, mixed>  $changes
 */
class UserUpdated
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $changes
     */
    public function __construct(
        public User $user,
        public array $changes = [],
        public ?User $updatedBy = null,
    ) {}
}
