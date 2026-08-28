# kreetancraft/laravel-user-management

Users, roles and permissions for Laravel — **Livewire 4 + Flux UI**, **Fortify** (2FA, passkeys,
email verification, password reset), **spatie/laravel-permission**, **impersonation**, and
**login history**.

Standalone package. No `nwidart/laravel-modules`, no bundled CSS, no bundled layouts.

## Design decisions worth knowing before you install

**It ships no CSS and no layouts.** The admin screens render into *your* layout and inherit your
Tailwind + Flux theme. Buttons use `variant="primary"`, so they follow your Flux accent colour
automatically. You must provide the two layouts named in `config('user-management.layouts')`.

**It seeds nothing but the super admin.** Permissions are *generated* from your policies by
`user-management:sync-permissions`; roles are created at runtime through the UI by the super
admin. This follows Filament Shield's model. Nothing in the package assumes anything about your
application's domain.

**It logs nothing.** There is no audit trail here. Instead it emits domain events — `UserCreated`,
`UserInvited`, `UserUpdated`, `UserDeleted`, `UserDeactivated`, `RoleCreated` — for an audit
package to subscribe to.

**It handles no images.** There are no avatars and no media library. `User::avatarUrl()` returns
`null` and is the extension point: override it on a subclass and the views pick it up.

> **Flux is a hard dependency.** Every admin screen uses `<flux:*>`. The package uses only free-tier
> Flux components, but `livewire/flux` must be installed.

## Requirements

- PHP `^8.2`, Laravel `^12|^13`
- `livewire/livewire ^4`, `livewire/flux ^2`, `laravel/fortify ^1.37`
- `spatie/laravel-permission ^8`, `spatie/laravel-data ^4`, `spatie/laravel-query-builder ^7`
- `lorisleiva/laravel-actions`, `sandermuller/laravel-fluent-validation`

Optional, enabled per feature flag: `lab404/laravel-impersonate`, `torann/geoip`.

## Installation

```bash
composer require kreetancraft/laravel-user-management
```
```bash
php artisan vendor:publish --tag=user-management-config
```
```bash
php artisan migrate
```
```bash
php artisan user-management:super-admin
```
```bash
php artisan user-management:sync-permissions
```

### Point auth at the package's User model

In `config/auth.php`:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => Kreetancraft\UserManagement\Models\User::class,
    ],
],
```

Or extend it, which is how you add avatars or your own relations:

```php
class User extends \Kreetancraft\UserManagement\Models\User {}
```

### Provide the layouts

Defaults match a stock Laravel starter kit. Note the asymmetry — it is Laravel's, not ours: the
admin screens are Livewire pages and `->layout()` takes a **view** name, while the Fortify auth
screens are plain Blade wrapped in `<x-dynamic-component>`, which takes a **component** name.

```php
'layouts' => [
    'admin' => 'components.layouts.app', // view name
    'auth'  => 'layouts.auth',           // component name
],
```

## Configuration

`config/user-management.php`:

```php
'super_admin' => ['role' => 'super-admin', 'enabled' => true],

'features' => [
    'two_factor' => true, 'passkeys' => true,
    'impersonation' => true, 'login_history' => true,
    'registration' => false,
],

// Colours. Roles are runtime rows, so a colour is chosen by hashing the role
// name against this palette — stable everywhere, nothing to configure per role.
'ui' => [
    'role_palette' => ['blue', 'emerald', 'violet', 'amber', 'cyan', 'pink', 'lime', 'indigo', 'teal', 'orange'],
    'super_admin_color' => 'red',
    'status' => ['active' => 'emerald', 'inactive' => 'zinc'],
],

'routes' => [
    'prefix' => 'admin',
    'middleware' => ['web', 'auth', 'verified', 'ensure.2fa.enforced'],
    'names' => [
        // Required only if you enforce 2FA per user: the route where they enable it.
        'security_edit' => null,
    ],
],

'permissions' => ['protected' => []], // your permission names the UI must refuse to delete
'invitation_expiry_hours' => 48,
```

Every view, layout and route name is overridable.

## Features

- **Users** — CRUD, soft deletes, active/inactive, `enforce_2fa` per user
- **Invitations** — invite by email, user sets their own password at `/set-password/{token}`;
  tokens are single-use, expire after `invitation_expiry_hours`, and the route is throttled
- **Roles & permissions** — full runtime CRUD; permissions generated from your policies
- **Auth** — Fortify-backed 2FA (TOTP + recovery codes), passkeys, password reset, email
  verification, with dedicated rate limiters for login / two-factor / passkeys
- **Login history** — IP, user agent, derived browser and platform, optional GeoIP country
- **Impersonation** — super admin only, double-gated

## Commands

```bash
php artisan user-management:super-admin
```
Creates or promotes a super admin. Prohibited in production.

```bash
php artisan user-management:sync-permissions
```
Scans the configured policy paths and upserts a permission per policy method. Idempotent — safe
to run on every deploy.

## Architecture

Contracts are split so consumers depend only on what they use:

- `ManagesUsers` — the write side (create, update, delete, invitation)
- `QueriesUsers` — the read side (pagination, lookups, counts)
- `UserContract` — both, for the rare class that genuinely needs it
- `RoleContract` — role and permission persistence

All four are bound to the same repository, so you can replace one side without reimplementing
the other.

Two guards live in `DeleteUserAction` rather than `UserPolicy` — you cannot delete your own
account, and you cannot delete the last super admin. That is deliberate: super admins bypass
policies via `Gate::before`, so a policy check would never run for exactly the people able to
trigger it.

## Testing

```bash
vendor/bin/pest
```

The suite runs against `orchestra/testbench` on in-memory SQLite. `tests/fixtures/views` stands
in for the host application's layouts, and `TestCase::defineRoutes()` provides the host routes
the package references by configurable name.

## License

MIT
