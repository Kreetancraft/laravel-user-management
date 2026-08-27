<?php

namespace Kreetancraft\UserManagement\Providers;

use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Kreetancraft\UserManagement\Events\UserCreated;
use Kreetancraft\UserManagement\Events\UserDeactivated;
use Kreetancraft\UserManagement\Events\UserInvited;
use Kreetancraft\UserManagement\Listeners\RecordUserLogin;
use Kreetancraft\UserManagement\Listeners\SendDeactivationNotification;
use Kreetancraft\UserManagement\Listeners\SendInvitationEmail;
use Kreetancraft\UserManagement\Listeners\SendWelcomeEmail;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class => [
            RecordUserLogin::class,
        ],
        UserInvited::class => [
            SendInvitationEmail::class,
        ],
        UserCreated::class => [
            SendWelcomeEmail::class,
        ],
        UserDeactivated::class => [
            SendDeactivationNotification::class,
        ],
    ];
}
