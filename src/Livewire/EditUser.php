<?php

namespace Kreetancraft\UserManagement\Livewire;

use Flux\Flux;
use Kreetancraft\UserManagement\Actions\UpdateUserAction;
use Kreetancraft\UserManagement\Data\UpdateUserData;
use Kreetancraft\UserManagement\Layout;
use Kreetancraft\UserManagement\Livewire\Concerns\HasAvailableRoles;
use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Support\Avatar;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use SanderMuller\FluentValidation\FluentRule as Rule;

class EditUser extends Component
{
    use HasAvailableRoles;

    public User $user;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public bool $is_active = true;

    public bool $enforce_2fa = false;

    /**
     * @var array<int, string>
     */
    public array $selectedRoles = [];

    /**
     * The chosen avatar, held until save.
     *
     * @var array<int, array{id: int|string, url: ?string, name: ?string}>
     */
    public array $avatarMedia = [];

    #[On('media-picked')]
    public function onMediaPicked(array $ids, string $group, array $items = []): void
    {
        if ($group !== 'user-avatar') {
            return;
        }

        $this->avatarMedia = $items;
    }

    public function mount(User $user): void
    {
        $this->authorize('update', $user);

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->is_active = $user->is_active;
        $this->enforce_2fa = $user->enforce_2fa;
        $this->selectedRoles = $user->roles->pluck('name')->toArray();
        $this->avatarMedia = Avatar::list($user);
    }

    /**
     * Fluent validation rules.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => Rule::string('Full Name')->required()->max(255),
            'email' => Rule::email('Email Address')->required()->max(255)->unique('users', 'email', fn ($r) => $r->ignore($this->user->id)),
            'password' => Rule::password(defaults: false)->nullable()->min(8),
            'is_active' => Rule::boolean()->required(),
            'enforce_2fa' => Rule::boolean()->required(),
            'selectedRoles' => Rule::array()->nullable(),
            'selectedRoles.*' => Rule::string()->exists('roles', 'name'),
        ];
    }

    public function updated($propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    public function save(): void
    {
        $this->authorize('update', $this->user);

        $validated = $this->validate();
        $roles = $validated['selectedRoles'] ?? [];

        foreach ($roles as $roleName) {
            $this->authorize('assignRole', [User::class, $roleName]);
        }

        if ($this->user->isSuperAdmin() && ! in_array(User::superAdminRole(), $roles, true)) {
            $superAdminCount = User::superAdmins()->count();
            if ($superAdminCount <= 1) {
                Flux::toast(variant: 'danger', text: __('Cannot demote the last super-admin.'));

                return;
            }
        }

        UpdateUserAction::run($this->user, UpdateUserData::from([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'] ?: null,
            'is_active' => $validated['is_active'],
            'enforce_2fa' => $validated['enforce_2fa'] ?? false,
            'roles' => $roles,
        ]));

        // The avatar lives in a media package, not in this one's tables, so it
        // is stored through the configured resolver rather than the action.
        Avatar::sync($this->user, array_map(fn ($m) => $m['id'], $this->avatarMedia));

        Flux::toast(variant: 'success', text: __('User updated successfully.'));

        $this->redirect(route(config('user-management.routes.names.users.index', 'admin.users')), navigate: true);
    }

    #[Title('Edit User - Admin')]
    public function render()
    {
        return view('user-management::livewire.edit-user', [
            'roles' => $this->availableRoles(),
        ])->layout(Layout::admin());
    }
}
