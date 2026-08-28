<?php

namespace Kreetancraft\UserManagement\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stands in for a model shipped by ANOTHER package.
 *
 * The point of the discovery tests is that nothing here declares a permission
 * name — registering the policy is the whole integration.
 */
class Widget extends Model
{
    protected $guarded = [];
}
