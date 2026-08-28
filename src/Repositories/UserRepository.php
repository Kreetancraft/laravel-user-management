<?php

namespace Kreetancraft\UserManagement\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Kreetancraft\UserManagement\Contracts\UserContract;
use Kreetancraft\UserManagement\Data\StoreUserData;
use Kreetancraft\UserManagement\Data\UpdateUserData;
use Kreetancraft\UserManagement\Models\User;
use Spatie\QueryBuilder\QueryBuilder;

class UserRepository implements UserContract
{
    /**
     * Create a user via invitation (no password) and return the model.
     */
    public function createWithInvitation(StoreUserData $data): User
    {
        return DB::transaction(function () use ($data): User {
            /** @var User $user */
            $user = User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => null,
                'is_active' => $data->is_active,
                'email_verified_at' => null,
                'invitation_token' => encrypt(\Str::random(40)),
                'invitation_sent_at' => now(),
            ]);

            if ($data->roles !== []) {
                $user->syncRoles($data->roles);
            }

            return $user;
        });
    }

    public function update(User $user, UpdateUserData $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $user->name = $data->name;
            $user->email = $data->email;
            $user->is_active = $data->is_active;
            $user->enforce_2fa = $data->enforce_2fa;

            if ($data->password !== null) {
                $user->password = $data->password;
            }

            $user->save();

            $user->syncRoles($data->roles);

            return $user;
        });
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->syncRoles([]);
            $user->delete();
        });
    }

    public function setPasswordFromInvitation(User $user, string $password): void
    {
        DB::transaction(function () use ($user, $password): void {
            $user->password = $password;
            $user->email_verified_at = now();
            $user->invitation_token = null;
            $user->invitation_sent_at = null;
            $user->save();
        });
    }

    /**
     * Re-issue a fresh invitation token (used by "Resend invitation").
     */
    public function refreshInvitation(User $user): void
    {
        $user->forceFill([
            'invitation_token' => encrypt(\Str::random(40)),
            'invitation_sent_at' => now(),
        ])->save();
    }

    /**
     * Paginate users with Spatie Query Builder filtering/sorting.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function paginated(
        ?string $search,
        ?string $role,
        ?string $status,
        ?string $sort = 'name',
        int $perPage = 15
    ): LengthAwarePaginator {
        return QueryBuilder::for(User::class)
            ->with(['roles'])
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $search);
                $q->where('name', 'like', "%{$escaped}%")
                    ->orWhere('email', 'like', "%{$escaped}%");
            }))
            ->when($role, fn ($q) => $q->role($role))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($status === 'trashed', fn ($q) => $q->onlyTrashed())
            ->allowedSorts('name', 'email', 'created_at', 'last_login_at')
            ->defaultSort('name')
            ->paginate($perPage);
    }

    public function findByInvitationToken(string $token): ?User
    {
        $expiryHours = (int) config('user-management.invitation_expiry_hours', 48);

        return User::query()
            ->where('invitation_token', $token)
            ->where('invitation_sent_at', '>', now()->subHours($expiryHours))
            ->first();
    }

    public function find(int $id): ?User
    {
        return User::find($id);
    }

    public function activeCount(): int
    {
        return User::where('is_active', true)->count();
    }

    /**
     * Get active count from a paginated result (to avoid extra query).
     *
     * @deprecated count via paginator no longer uses withCount; use activeCount()
     */
    public function activeCountFromPaginator(LengthAwarePaginator $paginator): int
    {
        return $this->activeCount();
    }
}
