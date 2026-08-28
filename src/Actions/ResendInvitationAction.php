<?php

namespace Kreetancraft\UserManagement\Actions;

use Kreetancraft\UserManagement\Contracts\ManagesUsers;
use Kreetancraft\UserManagement\Events\UserInvited;
use Kreetancraft\UserManagement\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class ResendInvitationAction
{
    use AsAction;

    public function __construct(
        private ManagesUsers $users,
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
