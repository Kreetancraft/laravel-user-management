<?php

namespace Kreetancraft\UserManagement\Data;

use Kreetancraft\UserManagement\Models\User;
use Spatie\LaravelData\Data;

class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public bool $is_active,
        public bool $enforce_2fa,
        public ?string $email_verified_at,
        public ?string $created_at,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            is_active: $user->is_active,
            enforce_2fa: $user->enforce_2fa,
            email_verified_at: $user->email_verified_at?->toIso8601String(),
            created_at: $user->created_at?->toIso8601String(),
        );
    }
}
