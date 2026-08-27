<?php

namespace Kreetancraft\UserManagement\Actions;

use Kreetancraft\UserManagement\Events\UserDeleted;
use Kreetancraft\UserManagement\Exceptions\CannotDeleteLastSuperAdmin;
use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Repositories\UserRepository;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteUserAction
{
    use AsAction;

    public function __construct(
        private UserRepository $users,
    ) {}

    /**
     * Soft-delete a user and detach their roles.
     *
     * Authorization is enforced by the caller (UserPolicy::delete). The
     * last-super-admin guard is enforced *here* rather than in the policy: super
     * admins bypass policies via Gate::before, so a policy-level check would be
     * short-circuited for exactly the people able to trigger it.
     *
     * @throws CannotDeleteLastSuperAdmin
     */
    public function handle(User $user): void
    {
        if ($user->isSuperAdmin() && User::superAdmins()->count() <= 1) {
            throw CannotDeleteLastSuperAdmin::make();
        }

        $name = $user->name;

        $this->users->delete($user);

        UserDeleted::dispatch($user, $name, auth()->user());
    }
}
