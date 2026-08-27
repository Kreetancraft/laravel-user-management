<?php

namespace Kreetancraft\UserManagement\Actions;

use Kreetancraft\UserManagement\Data\StoreUserData;
use Kreetancraft\UserManagement\Events\UserInvited;
use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Repositories\UserRepository;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateUserAction
{
    use AsAction;

    public function __construct(
        private UserRepository $users,
    ) {}

    /**
     * Invite a new user. The user receives an email with a link to set their
     * own password; no password is set here.
     *
     * Authorization is enforced by the caller (UserPolicy::create + assignRole).
     */
    public function handle(StoreUserData $data): User
    {
        $user = $this->users->createWithInvitation($data);

        UserInvited::dispatch($user);

        return $user;
    }
}
