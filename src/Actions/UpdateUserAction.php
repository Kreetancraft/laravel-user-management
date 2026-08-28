<?php

namespace Kreetancraft\UserManagement\Actions;

use Kreetancraft\UserManagement\Contracts\ManagesUsers;
use Kreetancraft\UserManagement\Data\UpdateUserData;
use Kreetancraft\UserManagement\Events\UserDeactivated;
use Kreetancraft\UserManagement\Events\UserUpdated;
use Kreetancraft\UserManagement\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateUserAction
{
    use AsAction;

    /**
     * Attributes worth reporting as changed to listeners.
     */
    private const TRACKED = ['name', 'email', 'is_active', 'enforce_2fa'];

    public function __construct(
        private ManagesUsers $users,
    ) {}

    /**
     * Update a user and sync their roles.
     *
     * Authorization is enforced by the caller (UserPolicy::update + assignRole).
     */
    public function handle(User $user, UpdateUserData $data): User
    {
        $original = $user->only(self::TRACKED);
        $wasActive = $user->is_active;

        $user = $this->users->update($user, $data);

        UserUpdated::dispatch(
            $user,
            array_diff_assoc($user->only(self::TRACKED), $original),
            auth()->user(),
        );

        // A transition into an inactive state is a deactivation: fire the
        // event so listeners can notify the user (sessions are purged
        // by UserObserver).
        if ($wasActive && ! $user->is_active) {
            UserDeactivated::dispatch($user, auth()->user());
        }

        return $user;
    }
}
