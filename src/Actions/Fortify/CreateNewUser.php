<?php

namespace Kreetancraft\UserManagement\Actions\Fortify;

use Illuminate\Support\Facades\Validator;
use Kreetancraft\UserManagement\Concerns\PasswordValidationRules;
use Kreetancraft\UserManagement\Concerns\ProfileValidationRules;
use Kreetancraft\UserManagement\Models\User;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
    }
}
