<?php

namespace Kreetancraft\UserManagement\Contracts;

use Kreetancraft\UserManagement\Data\StoreUserData;
use Kreetancraft\UserManagement\Data\UpdateUserData;
use Kreetancraft\UserManagement\Models\User;

/**
 * Write side of user persistence.
 *
 * Actions depend on this rather than the full contract so a host application
 * can replace how users are written without also having to reimplement every
 * query method.
 */
interface ManagesUsers
{
    public function createWithInvitation(StoreUserData $data): User;

    public function update(User $user, UpdateUserData $data): User;

    public function delete(User $user): void;

    public function setPasswordFromInvitation(User $user, string $password): void;

    public function refreshInvitation(User $user): void;
}
