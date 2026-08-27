<?php

namespace Kreetancraft\UserManagement\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Kreetancraft\UserManagement\Events\UserInvited;
use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Repositories\UserRepository;

class ResendInvitationAction
{
    use AsAction;

    public function __construct(
        private UserRepository $users,
    ) {}

    /**
     * Re-issue an invitation token and re-dispatch the invitation event.
     */
    public function handle(User $user): void
    {
        $this->users->refreshInvitation($user);

        UserInvited::dispatch($user);
    }
}
