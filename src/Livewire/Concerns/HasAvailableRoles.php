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
        $query = Role::query()->orderBy('name');

        if (! auth()->user()->isSuperAdmin()) {
            $query->where('name', '!=', User::superAdminRole());
        }

        return $query->get();
    }
}
