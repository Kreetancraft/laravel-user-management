<?php

namespace Kreetancraft\UserManagement\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Kreetancraft\UserManagement\Contracts\RoleContract;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleRepository implements RoleContract
{
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data): Role {
            /** @var Role $role */
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web',
            ]);

            if (! empty($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            }

            return $role;
        });
    }

    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data): Role {
            $role->name = $data['name'];
            $role->save();
            $role->syncPermissions($data['permissions'] ?? []);

            return $role;
        });
    }

    /**
     * @return Collection<int, Role>
     */
    public function all(): Collection
    {
        return Role::query()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Permission>
     */
    public function permissions(): Collection
    {
        return Permission::query()->orderBy('name')->get();
    }
}
