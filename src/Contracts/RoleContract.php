<?php

namespace Kreetancraft\UserManagement\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

interface RoleContract
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Role;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Role $role, array $data): Role;

    /**
     * @return Collection<int, Role>
     */
    public function all(): Collection;

    /**
     * @return Collection<int, Permission>
     */
    public function permissions(): Collection;
}
