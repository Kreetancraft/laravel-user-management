<?php

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Kreetancraft\UserManagement\Enums\UserRole;
use Kreetancraft\UserManagement\Models\User;
use Kreetancraft\UserManagement\Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature', 'Unit');

function seedRolesAndPermissions(): void
{
    $perms = [
        'view-users','create-users','edit-users','delete-users',
        'manage-roles','manage-permissions',
        'manage-media',
        'view-trips','create-trips','edit-trips','delete-trips','publish-trips',
        'view-bookings','create-bookings','edit-bookings','cancel-bookings',
        'view-payments','record-payments','issue-refunds','export-financials',
        'view-inquiries','create-quotes','send-quotes',
        'view-customers','create-customers','edit-customers','delete-customers',
        'view-coupons','create-coupons','edit-coupons',
        'view-invoices','create-invoices','edit-invoices',
        'view-blogs','create-blogs','edit-blogs','delete-blogs','publish-blogs','moderate-blog-comments',
        'view-content','manage-content',
        'view-newsletter','manage-seo','view-settings','manage-settings',
    ];
    foreach ($perms as $p) {
        Permission::findOrCreate($p, 'web');
    }
    // Ensure all UserRole enum values exist as roles with appropriate perms
    foreach (UserRole::cases() as $role) {
        $r = Role::findOrCreate($role->value, 'web');
        if ($role === UserRole::SuperAdmin) {
            $r->syncPermissions(Permission::all());
        } elseif ($role === UserRole::PackageManager) {
            $r->syncPermissions(['view-trips','create-trips','edit-trips','delete-trips','publish-trips']);
        } elseif ($role === UserRole::BookingManager) {
            $r->syncPermissions(['view-trips','view-users']);
        } elseif ($role === UserRole::FinanceAdmin) {
            $r->syncPermissions(['view-trips']);
        }
    }
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
}

function actingAsRole(UserRole|string $role): User
{
    seedRolesAndPermissions();
    $name = $role instanceof UserRole ? $role->value : $role;
    $user = User::factory()->withRole($name)->create();
    test()->actingAs($user);
    return $user;
}

function actingAsSuperAdmin(): User
{
    return actingAsRole(UserRole::SuperAdmin);
}
