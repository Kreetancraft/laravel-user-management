<?php

namespace Kreetancraft\UserManagement\Livewire\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

/**
 * Group permissions by the resource they act on, and toggle a whole group.
 *
 * Permissions generated from policies are named `{action}-{resource}` — the
 * resource is the part worth grouping on, because that is how administrators
 * think about access ("what can this role do to users?").
 */
trait InteractsWithPermissionGroups
{
    /**
     * @return Collection<string, Collection<int, Permission>>
     */
    public function permissionGroups(): Collection
    {
        return Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $p) => Str::afterLast($p->name, '-'));
    }

    /**
     * Every permission in a group, selected or cleared in one action.
     */
    public function toggleGroup(string $group): void
    {
        $names = $this->permissionGroups()->get($group, collect())->pluck('name')->all();

        $this->selectedPermissions = $this->allSelected($names)
            ? array_values(array_diff($this->selectedPermissions, $names))
            : array_values(array_unique(array_merge($this->selectedPermissions, $names)));
    }

    /**
     * Select everything, or clear everything if it is already all selected.
     */
    public function toggleAllPermissions(): void
    {
        $names = Permission::query()->pluck('name')->all();

        $this->selectedPermissions = $this->allSelected($names) ? [] : $names;
    }

    /**
     * @param  list<string>  $names
     */
    private function allSelected(array $names): bool
    {
        return $names !== [] && array_diff($names, $this->selectedPermissions) === [];
    }
}
