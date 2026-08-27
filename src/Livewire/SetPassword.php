<?php

namespace Kreetancraft\UserManagement\Livewire;

use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Kreetancraft\UserManagement\Actions\SetUserPasswordAction;
use Kreetancraft\UserManagement\Contracts\UserContract;
use Kreetancraft\UserManagement\Models\User;
use SanderMuller\FluentValidation\FluentRule as Rule;

class SetPassword extends Component
{
    public ?User $user = null;

    public string $token = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $invalid = false;

    private UserContract $users;

    public function boot(UserContract $users): void
    {
        $this->users = $users;
    }

    public function mount(string $token): void
    {
        $this->token = $token;

        $this->user = $this->users->findByInvitationToken($token);

        if ($this->user === null) {
            $this->invalid = true;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'password' => Rule::password(defaults: false)->required()->min(8),
            'password_confirmation' => Rule::string('Confirm Password')->required()->same('password'),
        ];
    }

    public function updated($propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    public function save(): void
    {
        if ($this->user === null) {
            $this->invalid = true;

            return;
        }

        $validated = $this->validate();

        SetUserPasswordAction::run($this->user, $validated['password']);

        Flux::toast(variant: 'success', text: __('Your password has been set. You can now sign in.'));

        $this->redirect(route(config('user-management.routes.names.login', 'login')), navigate: true);
    }

    #[Title('Set Your Password')]
    public function render()
    {
        return view('user-management::livewire.set-password')
            ->layout(config('user-management.layouts.auth', 'user-management::layouts.auth'));
    }
}
