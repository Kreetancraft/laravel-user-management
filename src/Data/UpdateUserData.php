<?php

namespace Kreetancraft\UserManagement\Data;

use Spatie\LaravelData\Data;

class UpdateUserData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $password = null,
        /** @var array<int, string> */
        public array $roles = [],
        public bool $is_active = true,
        public bool $enforce_2fa = false,
    ) {}
}
