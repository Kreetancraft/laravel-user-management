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

### Let Tailwind see this package

Required. Tailwind v4 generates only the classes it finds by scanning files, and
it does not scan `vendor/`. In `resources/css/app.css`:

```css
@source '../../vendor/kreetancraft/laravel-user-management/resources/views';
```

Skipping it fails confusingly rather than loudly — classes shared with your own
views still work and only the ones unique to this package go missing.

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

### The sidebar

Include the nav once, anywhere in your layout:

```blade
<flux:navlist.group heading="Admin">
    <x-user-management::nav />
</flux:navlist.group>
```

That renders every admin link — this package's, and any other package's. There
is no list to maintain: `user-management:install` will inject the line for you,
or add it by hand.

Links carrying a `group` render under a heading. This package puts its own two
under **Users**; rename or remove that heading with `user-management.navigation.group`.

> **Do not publish the nav.** It has its own tag (`user-management-nav`) and is
> excluded from `user-management-views` on purpose. A published copy wins over
> the package's, so one taken before a feature existed keeps rendering the old
> way after an upgrade — which is how sidebar grouping silently went missing for
> anyone who published views at 0.7.0. Publish the screens freely; publish the
> nav only if you mean to own it forever.
>
> If your sidebar is not grouping, that copy is why:
>
> ```bash
> php artisan vendor:publish --tag=user-management-nav --force
> ```

#### Adding your own links

```php
app(\Kreetancraft\UserManagement\Navigation::class)->add([
    'label' => 'Reports',
    'icon' => 'chart-bar',
    'route' => 'admin.reports',
    'ability' => 'view-reports',   // optional
    'sort' => 40,
]);
```

#### Adding links from another package

A package cannot depend on this one just to appear in a sidebar, so the seam is
a container tag — bind an item, tag it `admin.navigation`, done:

```php
// in any service provider. No mention of this package anywhere.
$this->app->bind('acme.navigation.items', fn () => [[
    'label' => __('Invoices'),
    'icon' => 'document-text',
    'route' => 'admin.invoices',
    'ability' => 'viewAny',
    'model' => Invoice::class,
    'sort' => 30,
]]);

$this->app->tag('acme.navigation.items', 'admin.navigation');
```

Why a tag rather than a facade call: tags are collected at render time, so
provider order does not matter, and a binding nobody collects is never resolved.
The contributing package keeps working unchanged when this one is not installed.

`kreetancraft/laravel-media-manager` does exactly this — install it and a
**Media** link appears, with nothing declared on either side.

| Key | |
|---|---|
| `label` | Required. Already translated — pass `__('…')` yourself. |
| `route` | Required, a route **name**. Skipped silently if the route does not exist, so a package whose routes are switched off cannot break the sidebar. |
| `ability` | Optional. With `model`, the ordinary policy question; without, a bare ability check. Omit to always show. |
| `model` | Optional. Pass it when a policy decides, so the link appears exactly when the page behind it is reachable. |
| `icon` | Optional, a Flux/Heroicon name. Defaults to `square-2-stack`. |
| `sort` | Optional, defaults to 50. This package uses 10 (Users) and 20 (Roles). |

### Avatars

This package ships no image handling. Point it at one that does and an avatar field appears on
the user forms:

```php
// config/user-management.php
'avatar_resolver'   => \Kreetancraft\Media\Support\MediaAvatarResolver::class,
'media_picker_view' => 'media::picker-field',
```

Both halves are needed — one to store the avatar, one to choose it. With either missing the
field renders nothing and the forms are exactly as they were, rather than showing a control with
nothing behind it.

**On a page with no form of its own** — a profile page, say — use the Livewire component. It
listens for the pick and saves for itself:

```blade
<livewire:user-management.avatar />                        {{-- the signed-in user --}}
<livewire:user-management.avatar :user="$someone" />       {{-- someone else; needs update-users --}}
```

Dropping the Blade field there instead would render a picker that quietly did nothing: the field
relies on a surrounding component to hear `media-picked`, which the user forms provide and a
profile page does not.

The package ships no profile screen on purpose — yours is already yours.

**Inside a Livewire form of your own**, use the Blade field and bind it, so an unsaved choice
survives until submit:

```blade
<x-user-management::avatar-field :items="$avatarMedia" />
```

### Why is there no avatar field?

The field renders nothing unless several separate things are true, and every one fails the same
way: a form with no avatar section and no error. That is correct on an install with no media
package, and indistinguishable from a misconfiguration. Ask:

```bash
php artisan user-management:avatar-doctor
```

It checks the resolver, whether its class is installed, whether that resolver can *write* and not
only read, the picker view, and whether a published copy of the user forms is shadowing the
package's — and names the first thing that is false.

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
