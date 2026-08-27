<?php

namespace Kreetancraft\UserManagement\Livewire;

use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Kreetancraft\UserManagement\Actions\UpdateRoleAction;
use Kreetancraft\UserManagement\Enums\UserRole;
use SanderMuller\FluentValidation\FluentRule as Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EditRole extends Component
{
    public Role $role;

    public string $name = '';

    /**
     * @var array<int, string>
     */
    public array $selectedPermissions = [];

    public function mount(Role $role): void
    {
        $this->authorize('manage-roles');

        $this->role = $role;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
    }

    protected function rules(): array
    {
        $rules = [
            'name' => Rule::string('Name')->required()->max(255)->unique('roles', 'name', fn ($r) => $r->ignore($this->role->id)),
            'selectedPermissions' => Rule::array(),
            'selectedPermissions.*' => Rule::string()->exists('permissions', 'name'),
        ];

        if ($this->isSystemRole()) {
            $rules['name'] = Rule::string('Name')->required()->in([$this->role->name]);
        }

        return $rules;
    }

    public function updated($propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    public function save(): void
    {
        $this->authorize('manage-roles');

        $validated = $this->validate();

        UpdateRoleAction::run($this->role, [
            'name' => $validated['name'],
            'permissions' => $validated['selectedPermissions'],
        ]);

        Flux::toast(variant: 'success', text: __('Role updated successfully.'));

        $this->redirect(route('admin.roles'), navigate: true);
    }

    #[Title('Edit Role - Admin')]
    public function render()
    {
        return view('user-management::livewire.edit-role', [
            'permissions' => Permission::orderBy('name')->get(),
            'isSystemRole' => $this->isSystemRole(),
        ])->layout(config('user-management.layouts.admin', 'user-management::layouts.app'));
    }

    private function isSystemRole(): bool
    {
        return in_array(
            $this->role->name,
            array_column(UserRole::cases(), 'value'),
            true,
        );
    }
}
