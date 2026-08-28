<?php

namespace Kreetancraft\UserManagement\Actions;

use Kreetancraft\UserManagement\Events\UserDeactivated;
use Kreetancraft\UserManagement\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class DeactivateUserAction
{
    use AsAction;

    /**
     * Deactivate a user and notify them. Sessions are purged by UserObserver.
     */
    public function handle(User $user): void
    {
        $user->update(['is_active' => false]);

        UserDeactivated::dispatch($user, auth()->user());
    }
}
