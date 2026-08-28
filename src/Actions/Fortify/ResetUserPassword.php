<?php

namespace Kreetancraft\UserManagement\Actions\Fortify;

use Illuminate\Support\Facades\Validator;
use Kreetancraft\UserManagement\Concerns\PasswordValidationRules;
use Kreetancraft\UserManagement\Models\User;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => $input['password'],
        ])->save();
    }
}
