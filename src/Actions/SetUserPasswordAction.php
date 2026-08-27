<?php

namespace Kreetancraft\UserManagement\Actions;

use Kreetancraft\UserManagement\Events\UserCreated;
use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Repositories\UserRepository;
use Lorisleiva\Actions\Concerns\AsAction;

class SetUserPasswordAction
{
    use AsAction;

    public function __construct(
        private UserRepository $users,
    ) {}

    /**
     * Complete an invitation: set the user's password and activate the account.
     */
    public function handle(User $user, string $password): void
    {
        $this->users->setPasswordFromInvitation($user, $password);

        UserCreated::dispatch($user);
    }
}
