<?php

namespace Kreetancraft\UserManagement\Listeners;

use Kreetancraft\UserManagement\Events\UserCreated;
use Kreetancraft\UserManagement\Notifications\AccountCreated;

class SendWelcomeEmail
{
    public function handle(UserCreated $event): void
    {
        $event->user->notify(new AccountCreated($event->user));
    }
}
