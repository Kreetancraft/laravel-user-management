<?php

namespace Kreetancraft\UserManagement\Livewire;

use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Kreetancraft\UserManagement\Actions\CreatePermissionAction;
use Kreetancraft\UserManagement\Actions\DeletePermissionAction;
use Kreetancraft\UserManagement\Actions\DeleteRoleAction;
use Kreetancraft\UserManagement\Models\User;
use RuntimeException;
use SanderMuller\FluentValidation\FluentRule as Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ManageRoles extends Component
{
    use WithPagination;

    #[Url(except: 'roles')]
    public string $tab = 'roles';

    #[Url(except: '')]
    public string $searchRoles = '';

    #[Url(except: '')]
    public string $searchPermissions = '';

    public string $permissionName = '';

    public ?int $pendingDeleteRoleId = null;

    public ?int $pendingDeletePermissionId = null;

    public function updatingSearchRoles(): void
    {
        $this->resetPage('rolesPage');
    }

    public function updatingSearchPermissions(): void
    {
        $this->resetPage('permissionsPage');
    }

    public function setTab(string $tabName): void
    {
        $this->tab = $tabName;
    }

    public function openCreatePermissionModal(): void
    {
        $this->authorize('manage-roles');
        $this->resetPermissionForm();
        Flux::modal('create-permission')->show();
    }

    public function savePermission(): void
    {
        $this->authorize('manage-roles');

        $validated = $this->validate([
            'permissionName' => Rule::string('Permission Name')->required()->max(255)->unique('permissions', 'name'),
        ]);

        CreatePermissionAction::run([
            'name' => $validated['permissionName'],
        ]);

        Flux::toast(variant: 'success', text: __('Permission created successfully.'));
        $this->resetPermissionForm();
        Flux::modal('create-permission')->close();
    }

    public function confirmDeleteRole(int $roleId): void
    {
        $this->authorize('manage-roles');
        $this->pendingDeleteRoleId = $roleId;
        Flux::modal('confirm-delete-role')->show();
    }

    public function deleteRole(): void
    {
        $this->authorize('manage-roles');

        if ($this->pendingDeleteRoleId === null) {
            return;
        }

        try {
            DeleteRoleAction::run(Role::findOrFail($this->pendingDeleteRoleId));
            Flux::toast(variant: 'success', text: __('Role deleted successfully.'));
        } catch (RuntimeException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        } finally {
            $this->pendingDeleteRoleId = null;
            Flux::modal('confirm-delete-role')->close();
        }
    }

    public function confirmDeletePermission(int $permissionId): void
    {
        $this->authorize('manage-roles');
        $this->pendingDeletePermissionId = $permissionId;
        Flux::modal('confirm-delete-permission')->show();
    }

    public function deletePermission(): void
    {
        $this->authorize('manage-roles');

        if ($this->pendingDeletePermissionId === null) {
            return;
        }

        try {
            DeletePermissionAction::run(Permission::findOrFail($this->pendingDeletePermissionId));
            Flux::toast(variant: 'success', text: __('Permission deleted successfully.'));
        } catch (RuntimeException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        } finally {
            $this->pendingDeletePermissionId = null;
            Flux::modal('confirm-delete-permission')->close();
        }
    }

    private function resetPermissionForm(): void
    {
        $this->permissionName = '';
        $this->resetValidation();
    }

    #[Title('Roles & Permissions - Admin')]
    public function render()
    {
        $layout = config('user-management.layouts.admin', 'layouts.app');

        $roles = Role::with('permissions')
            ->where('name', 'like', '%'.$this->searchRoles.'%')
            ->orderBy('name')
            ->paginate(10, ['*'], 'rolesPage');

        $permissions = Permission::where('name', 'like', '%'.$this->searchPermissions.'%')
            ->orderBy('name')
            ->paginate(10, ['*'], 'permissionsPage');

        $systemRoles = [User::superAdminRole()];

        return view('user-management::livewire.manage-roles', [
            'roles' => $roles,
            'permissions' => $permissions,
            'systemRoles' => $systemRoles,
        ])->layout($layout);
    }
}
