<?php

namespace Kreetancraft\UserManagement\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Kreetancraft\UserManagement\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        $roleName = config('user-management.super_admin.role', 'super-admin');

        $role = Role::findOrCreate($roleName, 'web');

        // Ensure super-admin has core permissions so Gate::before demo still has real perms
        $core = config('user-management.permissions.custom', []);
        foreach ($core as $perm) {
            Permission::findOrCreate($perm, 'web');
        }
        if ($core) {
            $role->syncPermissions($core);
        }

        $registrar->forgetCachedPermissions();

        // Create default super-admin user if not exists (only in non-production or when explicitly seeded)
        if (! app()->environment('production')) {
            $email = config('user-management.super_admin.email', 'superadmin@example.com');
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => config('user-management.super_admin.name', 'Super Admin'),
                    'password' => Hash::make(config('user-management.super_admin.password', 'password')),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );
            $user->assignRole($role);
        }
    }
}
