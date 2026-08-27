<?php

namespace Kreetancraft\UserManagement\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Spatie\Permission\Models\Role;

class RoleCreated
{
    use Dispatchable;

    public function __construct(
        public Role $role,
    ) {}
}
