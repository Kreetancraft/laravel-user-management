<?php

namespace Kreetancraft\UserManagement\Support;

use Illuminate\Support\Str;
use Kreetancraft\UserManagement\Models\User;

/**
 * Presentation helpers for roles.
 *
 * Roles are arbitrary database rows created at runtime, so there is no enum to
 * carry a label or colour. Labels are humanised from the name; colours come
 * from the configured palette, chosen deterministically by hashing the name so
 * the same role always renders the same colour without anyone configuring it.
 *
 * @see config/user-management.php  ('ui' block)
 */
final class RolePresenter
{
    /**
     * Fallback palette, used when config is unavailable (e.g. a unit test that
     * boots no application).
     *
     * @var list<string>
     */
    private const FALLBACK_PALETTE = ['blue', 'emerald', 'violet', 'amber', 'cyan'];

    /**
     * Human-readable label: `content-editor` becomes `Content Editor`.
     */
    public static function label(string $role): string
    {
        return Str::of($role)->replace(['-', '_'], ' ')->title()->toString();
    }

    /**
     * A stable Flux badge colour for a role name.
     */
    public static function color(string $role): string
    {
        if (self::isSuperAdmin($role)) {
            return (string) config('user-management.ui.super_admin_color', 'red');
        }

        $palette = config('user-management.ui.role_palette');

        if (! is_array($palette) || $palette === []) {
            $palette = self::FALLBACK_PALETTE;
        }

        $palette = array_values($palette);

        // crc32 keeps this stable across requests and machines, unlike spl hashes.
        return (string) $palette[crc32($role) % count($palette)];
    }

    /**
     * Colour for an active/inactive status badge.
     */
    public static function statusColor(bool $active): string
    {
        return (string) config(
            'user-management.ui.status.'.($active ? 'active' : 'inactive'),
            $active ? 'emerald' : 'zinc',
        );
    }

    /**
     * Icon for a role — super admins get a distinct one.
     */
    public static function icon(string $role): string
    {
        return self::isSuperAdmin($role) ? 'shield-exclamation' : 'shield-check';
    }

    public static function isSuperAdmin(string $role): bool
    {
        return $role === User::superAdminRole();
    }

    /**
     * Roles the package protects from renaming or deletion.
     *
     * @return list<string>
     */
    public static function systemRoles(): array
    {
        return [User::superAdminRole()];
    }
}
