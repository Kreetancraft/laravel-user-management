<?php

namespace Kreetancraft\UserManagement\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kreetancraft\UserManagement\Models\User;

class UserDeactivated
{
    use Dispatchable;

    public function __construct(
        public User $user,
        public ?User $deactivatedBy = null,
    ) {}
}
