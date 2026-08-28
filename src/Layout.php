<?php

namespace Kreetancraft\UserManagement;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use RuntimeException;

/**
 * Where this package's screens render, and where "Dashboard" points.
 *
 * This package ships no layout — its screens render into yours. That is the one
 * setup step that cannot be skipped, and until now getting it wrong produced
 * Livewire's MissingLayoutException, which names a view but not the config key
 * that chose it or the layouts you actually have.
 *
 * So: try what is configured, then the conventions, then fail with something
 * worth reading.
 */
class Layout
{
    /**
     * Layout names to try when the configured one does not resolve.
     *
     * `components.layouts.app` is the current Laravel starter-kit convention;
     * `layouts.app` is what older applications use. Trying both means the
     * package works on either without configuration.
     *
     * @var list<string>
     */
    public const CONVENTIONS = [
        'components.layouts.app',
        'layouts.app',
        'components.layouts.admin',
        'layouts.admin',
    ];

    public static function admin(): string
    {
        return self::resolve(
            config('user-management.layouts.admin'),
            'user-management.layouts.admin',
            self::CONVENTIONS,
        );
    }

    /**
     * The auth screens are plain Blade wrapped in <x-dynamic-component>, so this
     * is a COMPONENT name where admin() is a VIEW name. The asymmetry is
     * Laravel's, not ours.
     */
    public static function auth(): string
    {
        return (string) config('user-management.layouts.auth', 'components.layouts.auth');
    }

    /**
     * Where the "Dashboard" breadcrumb points.
     *
     * Accepts a route name or a URL, because people reach for both. A route
     * name is preferable — it survives the route moving — but `/admin` has to
     * keep working for anyone who set it that way.
     */
    public static function home(): string
    {
        $home = (string) config('user-management.routes.home', 'dashboard');

        if ($home === '') {
            return '/';
        }

        return Route::has($home) ? route($home) : $home;
    }

    /**
     * @param  list<string>  $conventions
     */
    private static function resolve(?string $configured, string $key, array $conventions): string
    {
        if (is_string($configured) && $configured !== '' && View::exists($configured)) {
            return $configured;
        }

        foreach ($conventions as $candidate) {
            if (View::exists($candidate)) {
                return $candidate;
            }
        }

        throw new RuntimeException(sprintf(
            'No layout to render into. Set `%s` in config/%s.php to one of your layout views. '
            .'Tried: %s. This package ships no layout by design — its screens render into yours.',
            $key,
            explode('.', $key)[0],
            implode(', ', array_values(array_unique(array_filter(
                array_merge([$configured], $conventions)
            )))),
        ));
    }
}
