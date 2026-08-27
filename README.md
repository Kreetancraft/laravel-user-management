# kreetancraft/laravel-user-management

Complete user management for Laravel — **Livewire 4 + Flux UI**, **Laravel Fortify** (2FA, passkeys, email verification), **roles & permissions** (spatie/laravel-permission), **impersonation**, and **login history with GeoIP**.

Standalone package — no `nwidart/laravel-modules`. Drops into any Laravel 12/13 app (including `laravel new --using=livewire` starter kit).

> **Flux is a hard dependency.** All admin views use `<flux:*>` 200+ times. Consumers must have `livewire/flux` installed (paid).

## Requirements

- PHP `^8.2`, Laravel `^12|^13`
- `livewire/livewire ^4`, `livewire/flux ^2`, `laravel/fortify ^1.37`
- `spatie/laravel-permission ^8`, `spatie/laravel-data ^4`, `spatie/laravel-query-builder ^7`
- `lab404/laravel-impersonate ^1.7` and `torann/geoip ^3.0` are **required** (installed with this package)

## Installation

```bash
composer require kreetancraft/laravel-user-management
php artisan vendor:publish --tag=user-management-config
php artisan vendor:publish --tag=user-management-migrations
php artisan migrate
php artisan user-management:super-admin
php artisan user-management:sync-permissions
```

### Point auth to the package User (optional)

In `config/auth.php`:

```php
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => Kreetancraft\UserManagement\Models\User::class,
    ],
],
```

Or extend it in your app:

```php
class User extends \Kreetancraft\UserManagement\Models\User {}
```

## Features

- **Users:** CRUD, soft-delete, invitation flow (`/set-password/{token}` with 48h expiry + `throttle:6,1`), active/inactive, `enforce_2fa`
- **Roles & Permissions:** Livewire `ManageRoles` / `CreateRole` / `EditRole` + permission CRUD, seeded via `user-management:sync-permissions`
- **Auth:** Fortify views at `user-management::auth.*`, 2FA challenge, passkeys, password reset, email verification
- **Login history:** `user_login_histories` table, `RecordUserLogin` listener with GeoIP enrichment
- **Impersonation:** `Route::impersonate()` via `lab404/laravel-impersonate`, super-admin only (`canImpersonate` / `canBeImpersonated`)
- **UI:** 8 Livewire components, Flux tables/badges/modals, layouts `user-management::layouts.app` / `auth`

## Configuration

`config/user-management.php` (publishable):

```php
'super_admin' => ['role' => 'super-admin'],
'features' => ['two_factor' => true, 'passkeys' => true, 'impersonation' => true, 'login_history' => true],
'routes' => ['prefix' => 'admin', 'middleware' => ['web','auth','verified','ensure.2fa.enforced']],
'views' => ['login' => 'user-management::auth.login', /* ... */],
'layouts' => ['admin' => 'user-management::layouts.app', 'auth' => 'user-management::layouts.auth'],
'invitation_expiry_hours' => 48,
```

All view/layout/route names are overridable so a host app can point at its own.

## Commands

- `php artisan user-management:super-admin {--user=}` — create or promote a super-admin (prohibited in production)
- `php artisan user-management:sync-permissions` — scan policy paths and upsert permissions per policy method (idempotent)

## Migrations

Publish and run:

- `create_users_table` (adds `invitation_token`, `invitation_sent_at`, `enforce_2fa`, `is_active`, `last_login_at`, `last_login_ip`)
- `create_permission_tables` (spatie)
- `create_passkeys_table` + `add_two_factor_columns_to_users_table`
- `create_user_login_histories_table`

The package does **not** re-create `password_reset_tokens` / `sessions` — those come from the Laravel skeleton.

## Testing

```bash
composer install
vendor/bin/pest
```

## License

MIT
