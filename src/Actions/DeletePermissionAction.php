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
     * Core permissions that cannot be deleted.
     *
     * @var array<string>
     */
    private const PROTECTED = [
        'view-users', 'create-users', 'edit-users', 'delete-users',
        'manage-roles', 'manage-permissions',
        'view-trips', 'create-trips', 'edit-trips', 'delete-trips', 'publish-trips',
        'view-bookings', 'create-bookings', 'edit-bookings', 'cancel-bookings',
        'view-payments', 'record-payments', 'issue-refunds', 'export-financials',
        'view-inquiries', 'create-quotes', 'send-quotes',
    ];

    /**
     * Delete a permission.
     *
     * @throws RuntimeException When attempting to delete a protected core permission.
     */
    public function handle(Permission $permission): bool
    {
        if (in_array($permission->name, self::PROTECTED, true)) {
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
