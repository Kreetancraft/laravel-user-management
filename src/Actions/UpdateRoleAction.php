<?php

namespace Kreetancraft\UserManagement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Permission\Models\Role;

class UpdateRoleAction
{
    use AsAction;

    /**
     * Update a role and sync its permissions.
     *
     * @param  array{name: string, permissions?: array<string>}  $data
     */
    public function handle(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $originalName = $role->name;

            $role->name = $data['name'];
            $role->save();

            $role->syncPermissions($data['permissions'] ?? []);

            Log::info('Role updated', [
                'role_id' => $role->id,
                'from' => $originalName,
                'to' => $role->name,
                'permissions' => $data['permissions'] ?? [],
                'updated_by' => auth()->id(),
            ]);

            return $role;
        });
    }
}
