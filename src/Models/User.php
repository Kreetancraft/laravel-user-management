<?php

namespace Kreetancraft\UserManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Kreetancraft\UserManagement\Database\Factories\UserFactory;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use Impersonate;
    use Notifiable;
    use PasskeyAuthenticatable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    /**
     * Spatie guard - must match Role/Permission guard (web).
     * Without this, getGuardNames() can return empty on some auth configs.
     */
    protected string $guard_name = 'web';

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Declared as arrays rather than the #[Fillable]/#[Hidden] attributes:
     * those are Laravel 13+ only, and this package supports ^12 as well,
     * where the attributes are silently ignored and leave the model totally
     * guarded.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name', 'email', 'password', 'is_active', 'enforce_2fa',
        'invitation_token', 'invitation_sent_at',
    ];

    /**
     * Mirrors the column defaults in the migration so an unsaved model reports
     * the same values the database would assign.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'enforce_2fa' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'enforce_2fa' => 'boolean',
            'last_login_at' => 'datetime',
            'invitation_sent_at' => 'datetime',
        ];
    }

    /**
     * Use the package's factory.
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /**
     * The name of the super-admin role, from config.
     */
    public static function superAdminRole(): string
    {
        return (string) config('user-management.super_admin.role', 'super-admin');
    }

    /**
     * Avatar URL for this user, or null when none is available.
     *
     * This package ships no media handling. It is an extension point: a media
     * package can override this on a subclass (or via `resolveRelationUsing`
     * plus an accessor) without editing this package's views.
     */
    /**
     * The type name this user is stored under in polymorphic tables.
     *
     * Always the configured user model, never whichever class happened to run
     * the query. An application that extends this model — the documented way
     * to add relations — saves an avatar as App\\Models\\User from its
     * profile page, while this package's own screens query this class and look
     * the attachment up under a different name. The row exists, nothing finds
     * it, and the avatar appears on one screen and not another.
     *
     * spatie/laravel-permission writes model_has_roles.model_type through this
     * same method, so this also stops role assignments splitting across two
     * names for one person.
     */
    public function getMorphClass(): string
    {
        $configured = config('auth.providers.users.model');

        return is_string($configured) && $configured !== '' ? $configured : static::class;
    }

    public function avatarUrl(): ?string
    {
        $resolver = config('user-management.avatar_resolver');

        if ($resolver === null) {
            return null;
        }

        // A callable, or a class with a __invoke / avatarFor method. Resolved
        // through the container so it can take dependencies.
        $resolver = is_string($resolver) ? app($resolver) : $resolver;

        if (is_callable($resolver)) {
            return $resolver($this);
        }

        if (method_exists($resolver, 'avatarFor')) {
            return $resolver->avatarFor($this);
        }

        return null;
    }

    /**
     * Scope: only active users.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: filter by role name.
     */
    public function scopeWithRole(Builder $query, string $role): Builder
    {
        return $query->role($role);
    }

    /**
     * Scope: only super admins.
     */
    public function scopeSuperAdmins(Builder $query): Builder
    {
        return $query->role(self::superAdminRole());
    }

    /**
     * Check if the user is a super-admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::superAdminRole());
    }

    /**
     * Only super-admins may impersonate others.
     */
    public function canImpersonate(): bool
    {
        return $this->isSuperAdmin();
    }

    /**
     * Any active user may be impersonated (except super-admins for safety).
     */
    public function canBeImpersonated(): bool
    {
        return $this->is_active && ! $this->isSuperAdmin();
    }

    /**
     * Whether this user must satisfy the 2FA enforcement gate.
     */
    public function requiresTwoFactorEnforcement(): bool
    {
        return $this->enforce_2fa;
    }

    /**
     * Whether the user has completed two-factor enrollment.
     */
    public function hasEnabledTwoFactor(): bool
    {
        return ! is_null($this->two_factor_secret);
    }

    /**
     * The user's primary role name, or null if they have none.
     */
    public function primaryRole(): ?string
    {
        return $this->roles->first()?->name;
    }

    /**
     * Get the user's initials (first letter of first two names).
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Relationship: User login histories.
     */
    public function loginHistories(): HasMany
    {
        return $this->hasMany(UserLoginHistory::class)->latest();
    }
}
