<?php

namespace Kreetancraft\UserManagement\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Kreetancraft\UserManagement\Data\StoreUserData;
use Kreetancraft\UserManagement\Data\UpdateUserData;
use Kreetancraft\UserManagement\Models\User;

interface UserContract
{
    public function createWithInvitation(StoreUserData $data): User;

    public function update(User $user, UpdateUserData $data): User;

    public function delete(User $user): void;

    public function setPasswordFromInvitation(User $user, string $password): void;

    public function refreshInvitation(User $user): void;

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginated(
        ?string $search,
        ?string $role,
        ?string $status,
        ?string $sort = 'name',
        int $perPage = 15
    ): LengthAwarePaginator;

    public function activeCount(): int;

    public function findByInvitationToken(string $token): ?User;

    public function find(int $id): ?User;
}
