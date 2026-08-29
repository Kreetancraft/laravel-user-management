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
        'enabled' => true,
        'role' => 'super-admin',

        // Where the bypass hooks in, as Filament Shield's does:
        //
        //   'before' — super admins skip policies entirely. Anything a policy
        //              would refuse them is granted, so guards that must apply
        //              even to super admins belong in the action, not the policy.
        //   'after'  — policies answer first; the bypass only grants what they
        //              left undecided.
        //
        'intercept_gate' => 'before',

        // Defaults for `user-management:super-admin` prompts. There is no
        // password default on purpose — a real credential sitting in a published
        // config file is not something to ship. Pass --password, or be prompted.
        'email' => env('UM_SUPER_ADMIN_EMAIL', 'superadmin@example.com'),
        'name' => env('UM_SUPER_ADMIN_NAME', 'Super Admin'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Avatar resolver
    |--------------------------------------------------------------------------
    |
    | This package ships no image handling. `User::avatarUrl()` returns null
    | unless something is named here — a callable, or a class with __invoke()
    | or avatarFor(User $user): ?string, resolved through the container.
    |
    | With kreetancraft/laravel-media-manager installed:
    |
    |     'avatar_resolver' => \Kreetancraft\Media\Support\MediaAvatarResolver::class,
    |
    | Neither package requires the other; this one line is the whole wiring.
    |
    */
    'avatar_resolver' => null,

    /*
    | The view rendered to pick an avatar, given $items (already resolved),
    | $group and $multiple. Install kreetancraft/laravel-media-manager and point
    | this at the field it ships:
    |
    |     'media_picker_view' => 'media::picker-field',
    |
    | Null hides the avatar field: the user forms render and save exactly as
    | they did, rather than showing a control with nothing behind it.
    */
    'media_picker_view' => null,

    /*
    | The control on a person's own profile page. An uploader rather than the
    | library chooser: someone setting their own picture should not be shown
    | everyone else's files, nor need a permission over the media library to do
    | it. With kreetancraft/laravel-media-manager 0.9.0 or later:
    |
    |     'avatar_uploader' => 'media.avatar-uploader',
    |
    | Null falls back to media_picker_view, so an install that only has the
    | chooser still works.
    */
    'avatar_uploader' => null,

    // The media collection an avatar is stored in.
    'avatar_collection' => 'avatar',

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
    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    |
    | The heading this package's own links sit under. Set it to null to leave
    | them loose at the top level.
    |
    */
    'navigation' => [
        'group' => 'Users',
    ],

    'routes' => [
        'prefix' => 'admin',
        'middleware' => ['web', 'auth', 'verified', 'ensure.2fa.enforced'],
        // Where the "Dashboard" breadcrumb points. A route name or a URL —
        // a route name is better, since it survives the route moving.
        'home' => 'dashboard',
        'security_edit' => null, // optional link to security settings page
        'names' => [
            'dashboard' => 'dashboard',

            // Route name of YOUR page where a user enables 2FA. Required only when
            // features.two_factor is on and you enforce it per-user.
            'security_edit' => null,

            'users' => [
                'index' => 'admin.users',
                'create' => 'admin.users.create',
                'show' => 'admin.users.show',
                'edit' => 'admin.users.edit',
            ],
            'roles' => [
                'index' => 'admin.roles',
                'create' => 'admin.roles.create',
                'edit' => 'admin.roles.edit',
            ],
            'invitation' => 'user.invitation.set-password',
            'login' => 'login',
        ],
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
    // This package ships no layouts and no CSS: it renders into YOUR layout
    // and inherits your Tailwind/Flux theme. Defaults match a stock Laravel
    // starter kit; point them at whatever your app actually uses.
    'layouts' => [
        'admin' => 'components.layouts.app',
        // NOTE the asymmetry, which is Laravel's not ours: the admin screens are
        // Livewire pages and ->layout() wants a VIEW name, while the Fortify auth
        // screens are plain Blade wrapped in <x-dynamic-component>, which wants a
        // COMPONENT name. Same file on disk, two ways of naming it.
        'auth' => 'layouts.auth',
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
        // Only abilities with NO policy behind them belong here. Everything a
        // policy declares is discovered — listing it twice is how the
        // `edit-users` / `update-users` split went unnoticed.
        'custom' => ['manage-roles', 'manage-permissions'],

        // Permission names the UI refuses to delete, on top of this package's
        // own. Add yours here so an admin cannot lock themselves out.
        'protected' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Policies
    |--------------------------------------------------------------------------
    */
    'policies' => [
        'discover' => true,

        // Scan Gate::policies() — Laravel's own registry of model => policy.
        // This is what makes a policy shipped INSIDE a package discoverable;
        // scanning directories alone only ever finds this application's own.
        'discover_registered' => true,

        // Policy files never bound through Gate::policy().
        'paths' => [app_path('Policies')],

        // Gate::policies() holds every policy in the app, third-party ones
        // included. A package opts in by declaring PERMISSION_SUBJECT on its
        // policy; policies in these namespaces are always discovered.
        'namespaces' => ['App\\'],

        // Model or policy classes to skip entirely.
        'exclude' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Invitation
    |--------------------------------------------------------------------------
    */
    'invitation_expiry_hours' => 48,
    /*
    |--------------------------------------------------------------------------
    | UI Colours
    |--------------------------------------------------------------------------
    |
    | Global colour configuration for every badge and accent this package
    | renders. Buttons and links use `variant="primary"`, which follows the
    | host application's Flux accent colour automatically — set that in your
    | app's CSS (`--color-accent`) and this package follows along.
    |
    | Roles are created at runtime, so their colours cannot be configured one
    | by one. Instead a colour is picked deterministically from `role_palette`
    | using a hash of the role name: the same role always gets the same colour,
    | and no two deployments need to agree on anything. Reorder, extend or
    | shrink the palette to match your brand — a single-entry palette makes
    | every role one colour.
    |
    | Any Flux badge colour is valid: zinc, red, orange, amber, yellow, lime,
    | green, emerald, teal, cyan, sky, blue, indigo, violet, purple, fuchsia,
    | pink, rose.
    |
    */
    'ui' => [
        'role_palette' => [
            'blue', 'emerald', 'violet', 'amber', 'cyan',
            'pink', 'lime', 'indigo', 'teal', 'orange',
        ],

        // Reserved so elevated access always reads the same way.
        'super_admin_color' => 'red',

        'status' => [
            'active' => 'emerald',
            'inactive' => 'zinc',
        ],
    ],
];
