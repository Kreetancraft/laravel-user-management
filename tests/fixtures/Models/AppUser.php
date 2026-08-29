<?php

namespace Kreetancraft\UserManagement\Tests\Fixtures\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * An app's own User, extending Laravel's rather than this package's — which is
 * what a default Laravel skeleton gives you and what most installs will keep.
 *
 * The package must work with this. It once did not: auth()->user() reaching an
 * event constructor or a policy typed against Kreetancraft's User was a
 * TypeError, so saving a user from the edit screen returned a 500.
 */
class AppUser extends Authenticatable
{
    use HasRoles;

    protected $table = 'users';

    protected $guarded = [];

    protected $hidden = ['password'];
}
