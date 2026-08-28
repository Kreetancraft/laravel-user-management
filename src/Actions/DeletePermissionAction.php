<?php

namespace Kreetancraft\UserManagement\Actions;

use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use RuntimeException;
use Spatie\Permission\Models\Permission;

class DeletePermissionAction
{
    use AsAction;

    /**
     * Permissions this package's own screens depend on.
     *
     * Deleting one would lock an administrator out of the very interface
     * used to restore it, so they are protected. Host applications add
     * their own via the `permissions.protected` config key; nothing here
     * assumes anything about the host's domain.
     *
     * @return list<string>
     */
    protected function protectedPermissions(): array
    {
        return array_values(array_unique(array_merge(
            ['view-users', 'create-users', 'edit-users', 'delete-users', 'manage-roles', 'manage-permissions'],
            (array) config('user-management.permissions.protected', []),
        )));
    }

    /**
     * Delete a permission.
     *
     * @throws RuntimeException When attempting to delete a protected core permission.
     */
    public function handle(Permission $permission): bool
    {
        if (in_array($permission->name, $this->protectedPermissions(), true)) {
            throw new RuntimeException("Core permission '{$permission->name}' cannot be deleted.");
        }

        $permission->delete();

        Log::info('Permission deleted', [
            'permission_id' => $permission->id,
            'name' => $permission->name,
            'deleted_by' => auth()->id(),
        ]);

        return true;
    }
}
