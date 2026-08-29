<?php

namespace Kreetancraft\UserManagement\Livewire;

use Flux\Flux;
use Kreetancraft\UserManagement\Actions\CreateUserAction;
use Kreetancraft\UserManagement\Data\StoreUserData;
use Kreetancraft\UserManagement\Layout;
use Kreetancraft\UserManagement\Livewire\Concerns\HasAvailableRoles;
use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Support\Avatar;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use SanderMuller\FluentValidation\FluentRule as Rule;

class CreateUser extends Component
{
    use HasAvailableRoles;

    public string $name = '';

    public string $email = '';

    public bool $is_active = true;

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

    public function mount(): void
    {
        $this->authorize('create', User::class);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => Rule::string('Full Name')->required()->max(255),
            'email' => Rule::email('Email Address')->required()->max(255)->unique('users', 'email'),
            'is_active' => Rule::boolean()->required(),
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
        $this->authorize('create', User::class);

        $validated = $this->validate();

        foreach ($validated['selectedRoles'] ?? [] as $roleName) {
            $this->authorize('assignRole', [User::class, $roleName]);
        }

        $user = CreateUserAction::run(new StoreUserData(
            name: $validated['name'],
            email: $validated['email'],
            roles: $validated['selectedRoles'] ?? [],
            is_active: $validated['is_active'],
        ));

        // Attachments are polymorphic on the user's key, so this can only
        // happen once the row exists.
        if ($user instanceof User) {
            Avatar::sync($user, array_map(fn ($m) => $m['id'], $this->avatarMedia));
        }

        Flux::toast(variant: 'success', text: __('Invitation sent to :email.', ['email' => $validated['email']]));

        $this->redirect(route(config('user-management.routes.names.users.index', 'admin.users')), navigate: true);
    }

    #[Title('Create User - Admin')]
    public function render()
    {
        return view('user-management::livewire.create-user', [
            'roles' => $this->availableRoles(),
        ])->layout(Layout::admin());
    }
}
