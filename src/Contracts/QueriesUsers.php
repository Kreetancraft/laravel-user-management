<?php

namespace Kreetancraft\UserManagement\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Kreetancraft\UserManagement\Models\User;

/**
 * Read side of user persistence.
 *
 * Listing screens depend on this alone, so swapping in a different query
 * strategy (a search index, a read replica) does not drag the write methods
 * along with it.
 */
interface QueriesUsers
{
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
