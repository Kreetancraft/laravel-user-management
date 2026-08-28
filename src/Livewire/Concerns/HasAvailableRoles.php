<?php

namespace Kreetancraft\UserManagement\Livewire\Concerns;

use Illuminate\Support\Collection;
use Kreetancraft\UserManagement\Models\User;
use Spatie\Permission\Models\Role;

trait HasAvailableRoles
{
    /**
     * Get roles available to the current user (non-super-admins cannot assign super-admin).
     *
     * @return Collection<int, Role>
     */
    private function availableRoles(): Collection
    {
        // withCount, not a lazy $role->permissions->count() in the view: one
        // query for the whole list instead of one per row.
        $query = Role::query()->withCount('permissions')->orderBy('name');

        if (! auth()->user()->isSuperAdmin()) {
            $query->where('name', '!=', User::superAdminRole());
        }

        return $query->get();
    }
}
