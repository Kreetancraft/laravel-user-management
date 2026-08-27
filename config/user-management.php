<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Super Admin
    |--------------------------------------------------------------------------
    |
    | The role that bypasses all permission checks via Gate::before.
    | Change the name if your app uses a different super-admin role.
    |
    */
    'super_admin' => [
        'role' => 'super-admin',
        'email' => env('UM_SUPER_ADMIN_EMAIL', 'superadmin@example.com'),
        'name' => env('UM_SUPER_ADMIN_NAME', 'Super Admin'),
        'password' => env('UM_SUPER_ADMIN_PASSWORD', 'password'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Each feature can be disabled. When disabled, its routes/migrations/
    | listeners are not registered. geoip and impersonate packages are now
    | hard requirements, so these flags only control behavior, not whether
    | the package is installed.
    |
    */
    'features' => [
        'two_factor' => true,
        'passkeys' => true,
        'impersonation' => true,
        'login_history' => true,
        'registration' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'prefix' => 'admin',
        'middleware' => ['web', 'auth', 'verified', 'ensure.2fa.enforced'],
        'home' => '/', // used for route('admin') fallback, override to your dashboard
        'security_edit' => null, // optional link to security settings page
    ],

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | All Fortify views are configurable so the host app can override them.
    | Defaults point to the package's auth views (user-management::auth.*).
    |
    */
    'views' => [
        'login' => 'user-management::auth.login',
        'register' => 'user-management::auth.register',
        'forgot-password' => 'user-management::auth.forgot-password',
        'reset-password' => 'user-management::auth.reset-password',
        'verify-email' => 'user-management::auth.verify-email',
        'confirm-password' => 'user-management::auth.confirm-password',
        'two-factor-challenge' => 'user-management::auth.two-factor-challenge',
    ],

    /*
    |--------------------------------------------------------------------------
    | Layouts
    |--------------------------------------------------------------------------
    */
    'layouts' => [
        'admin' => 'user-management::layouts.app',
        'auth' => 'user-management::layouts.auth',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    |
    | Methods that will be used to generate permissions per model policy.
    | Custom permissions that are not tied to a policy method.
    |
    */
    'permissions' => [
        'separator' => '-',
        'case' => 'kebab',
        'methods' => ['viewAny', 'view', 'create', 'update', 'delete', 'restore', 'forceDelete'],
        'custom' => ['manage-roles', 'manage-permissions', 'view-users', 'create-users', 'edit-users', 'delete-users'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Policies
    |--------------------------------------------------------------------------
    */
    'policies' => [
        'paths' => [app_path('Policies')],
        'discover' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Invitation
    |--------------------------------------------------------------------------
    */
    'invitation_expiry_hours' => 48,
];
