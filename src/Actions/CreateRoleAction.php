<?php

namespace Kreetancraft\UserManagement\Actions;

use Kreetancraft\UserManagement\Contracts\RoleContract;
use Kreetancraft\UserManagement\Events\RoleCreated;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Permission\Models\Role;

class CreateRoleAction
{
    use AsAction;

    public function __construct(
        private RoleContract $roles,
    ) {}

    /**
     * Create a new role and assign permissions.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data): Role
    {
        $role = $this->roles->create($data);

        RoleCreated::dispatch($role);

        return $role;
    }
}
