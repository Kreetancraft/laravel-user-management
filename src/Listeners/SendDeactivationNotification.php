<?php

namespace Kreetancraft\UserManagement\Listeners;

use Kreetancraft\UserManagement\Events\UserDeactivated;
use Kreetancraft\UserManagement\Notifications\UserDeactivated as UserDeactivatedNotification;

class SendDeactivationNotification
{
    public function handle(UserDeactivated $event): void
    {
        $event->user->notify(
            new UserDeactivatedNotification($event->user, $event->deactivatedBy)
        );
    }
}
