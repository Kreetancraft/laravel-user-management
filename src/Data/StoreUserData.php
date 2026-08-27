<?php

namespace Kreetancraft\UserManagement\Data;

use Spatie\LaravelData\Data;

class StoreUserData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        /** @var array<int, string> */
        public array $roles = [],
        public bool $is_active = true,
    ) {}
}
