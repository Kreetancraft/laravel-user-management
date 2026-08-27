<?php

namespace Kreetancraft\UserManagement\Livewire;

use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Kreetancraft\UserManagement\Actions\CreateRoleAction;
use SanderMuller\FluentValidation\FluentRule as Rule;
use Spatie\Permission\Models\Permission;

class CreateRole extends Component
{
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

        $this->redirect(route('admin.roles'), navigate: true);
    }

    #[Title('Create Role - Admin')]
    public function render()
    {
        return view('user-management::livewire.create-role', [
            'permissions' => Permission::orderBy('name')->get(),
        ])->layout(config('user-management.layouts.admin', 'layouts.app'));
    }
}
