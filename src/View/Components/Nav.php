<?php

namespace Kreetancraft\UserManagement\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Kreetancraft\UserManagement\Navigation;

/**
 * The admin sidebar: <x-user-management::nav />
 *
 * A class rather than a plain Blade partial for two reasons.
 *
 * The registry arrives by constructor injection instead of an `app()` call
 * inside a template, which is the framework's own preference and makes the
 * dependency visible.
 *
 * More importantly it keeps the *decisions* out of a publishable file. The view
 * is published to `resources/views/vendor/user-management/` by anyone
 * restyling the admin, and a copy taken before grouping existed silently
 * rendered a flat list — a feature disappearing with nothing to explain it.
 * Grouping now happens here, where publishing cannot reach.
 *
 * Both shapes are passed for the same reason: a published view still written
 * against `$items` keeps working, flat, instead of erroring on an undefined
 * variable. An old copy should degrade, not explode.
 */
class Nav extends Component
{
    public function __construct(private readonly Navigation $navigation) {}

    public function render(): View
    {
        return view('user-management::components.nav', [
            'sections' => $this->navigation->grouped(),
            'items' => $this->navigation->items(),
        ]);
    }
}
