<?php

namespace Kreetancraft\UserManagement\Livewire;

use Flux\Flux;
use Kreetancraft\UserManagement\Actions\CreateRoleAction;
use Kreetancraft\UserManagement\Layout;
use Kreetancraft\UserManagement\Livewire\Concerns\InteractsWithPermissionGroups;
use Livewire\Attributes\Title;
use Livewire\Component;
use SanderMuller\FluentValidation\FluentRule as Rule;

class CreateRole extends Component
{
    use InteractsWithPermissionGroups;

    public string $name = '';

    /**
     * @var array<int, string>
     */
    public array $selectedPermissions = [];

    public function mount(): void
    {
        $this->authorize('manage-roles');
    }

    protected function rules(): array
    {
        return [
            'name' => Rule::string()->required()->max(255)->unique('roles', 'name'),
            'selectedPermissions' => Rule::array()->nullable(),
            'selectedPermissions.*' => Rule::string()->exists('permissions', 'name'),
        ];
    }

    public function updated($propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    public function save(): void
    {
        $this->authorize('manage-roles');

        $validated = $this->validate();

        CreateRoleAction::run([
            'name' => $validated['name'],
            'permissions' => $validated['selectedPermissions'],
        ]);

        Flux::toast(variant: 'success', text: __('Role created successfully.'));

        $this->redirect(route(config('user-management.routes.names.roles.index', 'admin.roles')), navigate: true);
    }

    #[Title('Create Role - Admin')]
    public function render()
    {
        return view('user-management::livewire.create-role', [
            'permissionGroups' => $this->permissionGroups(),
        ])->layout(Layout::admin());
    }
}
