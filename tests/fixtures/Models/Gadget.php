<?php

namespace Kreetancraft\UserManagement\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

/** A model whose policy names a different subject than the model would. */
class Gadget extends Model
{
    protected $guarded = [];
}
