<?php

namespace Kreetancraft\UserManagement\Support;

use Illuminate\Database\Eloquent\Model;

/**
 * The avatar seam, both directions.
 *
 * This package ships no image handling. `User::avatarUrl()` reads through
 * `user-management.avatar_resolver`, and until now that was the whole seam —
 * an avatar could be displayed and nothing could ever set one, so the user
 * forms had no image option and no way to gain one.
 *
 * Writing goes through the same configured resolver, duck-typed exactly as the
 * read side is: a host can point the config at its own class without
 * implementing an interface from a package it does not depend on. A resolver
 * that cannot do a thing simply is not asked to.
 *
 * @internal Consumed by the avatar-field component and the user forms.
 */
class Avatar
{
    /**
     * Whether anything is configured that can supply and store avatars.
     *
     * The forms read this to decide whether to render the field at all: one
     * with nothing behind it is worse than none.
     */
    public static function enabled(): bool
    {
        $resolver = self::resolver();

        return $resolver !== null
            && method_exists($resolver, 'listFor')
            && method_exists($resolver, 'syncFor');
    }

    /**
     * The current avatar, shaped for a picker.
     *
     * @return list<array{id: int|string, url: ?string, name: ?string}>
     */
    public static function list(Model $user): array
    {
        $resolver = self::resolver();

        if ($resolver === null || ! method_exists($resolver, 'listFor')) {
            return [];
        }

        return $resolver->listFor($user, self::collection());
    }

    /**
     * Set or clear the avatar. An empty list removes it.
     *
     * @param  list<int|string>  $ids
     */
    public static function sync(Model $user, array $ids): void
    {
        $resolver = self::resolver();

        if ($resolver === null || ! method_exists($resolver, 'syncFor')) {
            return;
        }

        $resolver->syncFor($user, self::collection(), $ids);
    }

    /**
     * The picker view a host has pointed us at, when there is one to render.
     */
    public static function pickerView(): ?string
    {
        $view = config('user-management.media_picker_view');

        return is_string($view) && $view !== '' ? $view : null;
    }

    /**
     * The Livewire component that uploads without browsing, when one is
     * configured. Null means fall back to the library chooser.
     */
    public static function uploader(): ?string
    {
        $component = config('user-management.avatar_uploader');

        return is_string($component) && $component !== '' ? $component : null;
    }

    /**
     * The collection an avatar is stored in. Public so the uploader component
     * can be handed the same one this package reads.
     */
    public static function collectionName(): string
    {
        return self::collection();
    }

    private static function collection(): string
    {
        return (string) config('user-management.avatar_collection', 'avatar');
    }

    private static function resolver(): ?object
    {
        $resolver = config('user-management.avatar_resolver');

        if ($resolver === null || $resolver === '') {
            return null;
        }

        if (is_object($resolver)) {
            return $resolver;
        }

        // A class that no longer exists must not take a page down — the same
        // forgiveness the read side already has.
        return is_string($resolver) && class_exists($resolver) ? app($resolver) : null;
    }
}
