<?php

namespace Kreetancraft\UserManagement\Actions;

use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Spatie\Permission\Models\Permission;

class CreatePermissionAction
{
    use AsAction;

    /**
     * Create a new permission.
     *
     * @param  array{name: string}  $data
     */
    public function handle(array $data): Permission
    {
        $permission = Permission::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        Log::info('Permission created', [
            'permission_id' => $permission->id,
            'name' => $permission->name,
            'created_by' => auth()->id(),
        ]);

        return $permission;
    }
}
