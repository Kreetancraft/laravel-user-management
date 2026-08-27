<?php

namespace Kreetancraft\UserManagement\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Kreetancraft\UserManagement\Enums\UserRole;
use RuntimeException;
use Spatie\Permission\Models\Role;

class DeleteRoleAction
{
    use AsAction;

    /**
     * Delete a role and detach permissions.
     *
     * @throws RuntimeException When attempting to delete a system role.
     */
    public function handle(Role $role): bool
    {
        if ($this->isSystemRole($role)) {
            throw new RuntimeException("System role '{$role->name}' cannot be deleted.");
        }

        DB::transaction(function () use ($role): void {
            $role->syncPermissions([]);

            $role->delete();

            Log::info('Role deleted', [
                'role_id' => $role->id,
                'name' => $role->name,
                'deleted_by' => auth()->id(),
            ]);
        });

        return true;
    }

    /**
     * System roles are protected from deletion.
     */
    private function isSystemRole(Role $role): bool
    {
        return in_array(
            $role->name,
            array_column(UserRole::cases(), 'value'),
            true,
        );
    }
}
