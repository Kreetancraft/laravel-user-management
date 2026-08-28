<?php

namespace Kreetancraft\UserManagement\Livewire;

use Flux\Flux;
use Illuminate\Auth\Access\AuthorizationException;
use Kreetancraft\UserManagement\Actions\DeleteUserAction;
use Kreetancraft\UserManagement\Contracts\UserContract;
use Kreetancraft\UserManagement\Models\User;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class ManageUsers extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $roleFilter = '';

    #[Url(except: '')]
    public string $statusFilter = '';

    #[Url(except: 'name')]
    public string $sort = 'name';

    public ?int $pendingDeleteId = null;

    private UserContract $users;

    public function boot(UserContract $users): void
    {
        $this->users = $users;
    }

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Reset every filter at once and return to the first page.
     */
    public function clearFilters(): void
    {
        $this->reset(['search', 'roleFilter', 'statusFilter']);
        $this->resetPage();
    }

    public function confirmDelete(int $userId): void
    {
        $this->pendingDeleteId = $userId;
        Flux::modal('confirm-delete-user')->show();
    }

    public function delete(): void
    {
        if ($this->pendingDeleteId === null) {
            return;
        }

        $user = User::findOrFail($this->pendingDeleteId);

        if ($user->id === auth()->id()) {
            throw new AuthorizationException(__('You cannot delete your own account.'));
        }

        if ($user->isSuperAdmin() && User::superAdmins()->count() <= 1) {
            throw new AuthorizationException(__('Cannot delete the last super-admin.'));
        }

        $this->authorize('delete', $user);

        DeleteUserAction::run($user);

        $this->pendingDeleteId = null;
        Flux::modal('confirm-delete-user')->close();
        Flux::toast(variant: 'success', text: __('User deleted successfully.'));
    }

    #[Title('User Management - Admin')]
    public function render()
    {
        $layout = config('user-management.layouts.admin', 'components.layouts.app');

        $users = $this->users->paginated(
            $this->search,
            $this->roleFilter,
            $this->statusFilter,
            $this->sort,
            15
        );

        $roleOptions = Role::orderBy('name')->pluck('name', 'name')->mapWithKeys(fn ($name) => [$name => str($name)->headline()->toString()])->toArray();

        return view('user-management::livewire.manage-users', [
            'users' => $users,
            'roleOptions' => $roleOptions,
            'activeCount' => $this->users->activeCount(),
        ])->layout($layout);
    }
}
