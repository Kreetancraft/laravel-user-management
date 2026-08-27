<?php

namespace Kreetancraft\UserManagement\Listeners;

use Kreetancraft\UserManagement\Events\UserInvited;
use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Notifications\Invitation;

class SendInvitationEmail
{
    public function handle(UserInvited $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $user->notify(new Invitation($user, (string) $user->invitation_token));
    }
}
