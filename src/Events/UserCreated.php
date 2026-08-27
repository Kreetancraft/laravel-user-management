<?php

namespace Kreetancraft\UserManagement\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Kreetancraft\UserManagement\Models\User;

class UserCreated
{
    use Dispatchable;

    public function __construct(
        public User $user,
    ) {}
}
