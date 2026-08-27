<?php

namespace Kreetancraft\UserManagement\Observers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Kreetancraft\UserManagement\Models\User;

class UserObserver
{
    /**
     * Handle the User "saved" event.
     */
    public function saved(User $user): void
    {
        if ($user->wasChanged('is_active') && ! $user->is_active) {
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->delete();
            }
        }
    }
}
