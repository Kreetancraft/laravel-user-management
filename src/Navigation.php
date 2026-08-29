<?php

namespace Kreetancraft\UserManagement;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * The admin sidebar registry.
 *
 * A package cannot add a sidebar link by declaring a dependency on this one —
 * that would defeat the point of the packages being independent. So the seam is
 * Laravel's own container tag, which costs nothing when nobody collects it:
 *
 *     // in any package's service provider — no mention of user-management
 *     $this->app->bind('acme.navigation', fn () => [
 *         'label' => 'Invoices',
 *         'icon'  => 'document-text',
 *         'route' => 'admin.invoices',
 *         'ability' => 'viewAny',
 *         'model' => Invoice::class,
 *         'sort'  => 30,
 *     ]);
 *
 *     $this->app->tag('acme.navigation', self::TAG);
 *
 * Tags are collected at render time rather than at boot, so provider order does
 * not matter and a package registering after this one is still picked up. If
 * user-management is not installed, nothing ever calls `tagged()` and the
 * binding is simply never resolved.
 *
 * A host application that wants its own links can call `add()` instead.
 */
class Navigation
{
    /**
     * The container tag a package binds its item against.
     */
    public const TAG = 'admin.navigation';

    /** @var list<array<string, mixed>> */
    private array $items = [];

    public function __construct(private readonly Container $container) {}

    /**
     * Add one or more items directly. For host applications; packages should
     * use the tag so they keep working when this package is absent.
     *
     * @param  array<string, mixed>  ...$items
     */
    public function add(array ...$items): self
    {
        foreach ($items as $item) {
            $this->items[] = $item;
        }

        return $this;
    }

    /**
     * Every item the current user may see, in display order.
     *
     * @return list<array{label: string, icon: string, href: string, active: bool, sort: int, group: ?string}>
     */
    public function items(): array
    {
        $items = array_merge($this->items, $this->tagged());

        $visible = [];

        foreach ($items as $item) {
            $prepared = $this->prepare($item);

            if ($prepared !== null) {
                $visible[] = $prepared;
            }
        }

        usort($visible, fn (array $a, array $b) => [$a['sort'], $a['label']] <=> [$b['sort'], $b['label']]);

        return $visible;
    }

    /**
     * The same items, arranged into the sections the sidebar renders.
     *
     * A package with one screen contributes a loose item and it sits at the top
     * level; a package with six contributes them under a `group` and they get a
     * heading. That decision belongs to the package that owns the screens, not
     * to whoever renders the sidebar.
     *
     * Ungrouped items come first, under an empty key. Each group takes the
     * position of its earliest item, so a package cannot land its heading
     * halfway up the sidebar by giving one link a low sort.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function grouped(): array
    {
        $groups = [];

        foreach ($this->items() as $item) {
            $groups[(string) ($item['group'] ?? '')][] = $item;
        }

        uksort($groups, function (string $a, string $b) use ($groups): int {
            // Loose items always lead, whatever they sort as.
            if ($a === '' || $b === '') {
                return $a === '' ? -1 : 1;
            }

            return [$groups[$a][0]['sort'], $a] <=> [$groups[$b][0]['sort'], $b];
        });

        return $groups;
    }

    /**
     * Items contributed by other packages through the container tag.
     *
     * A binding may return one item or a list of them, so a package with two
     * screens does not need two bindings.
     *
     * @return list<array<string, mixed>>
     */
    private function tagged(): array
    {
        $items = [];

        foreach ($this->container->tagged(self::TAG) as $contribution) {
            if ($contribution instanceof \Closure) {
                $contribution = $contribution();
            }

            if (! is_array($contribution)) {
                continue;
            }

            // One item, or a list of them.
            $items = array_merge($items, array_is_list($contribution) ? $contribution : [$contribution]);
        }

        return $items;
    }

    /**
     * Resolve an item, or null when it should not appear.
     *
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    private function prepare(array $item): ?array
    {
        $route = $item['route'] ?? null;

        if (! is_string($route) || $route === '') {
            return null;
        }

        // A package whose admin routes are switched off still binds its item.
        // Rendering a link to a route that does not exist would throw.
        if (! Route::has($route)) {
            return null;
        }

        if (! $this->permitted($item)) {
            return null;
        }

        return [
            'label' => (string) ($item['label'] ?? Str::headline($route)),
            'icon' => (string) ($item['icon'] ?? 'square-2-stack'),
            'href' => route($route, $item['parameters'] ?? []),
            'active' => request()->routeIs($route, $route.'.*'),
            'sort' => (int) ($item['sort'] ?? 50),
            'group' => isset($item['group']) ? (string) $item['group'] : null,
        ];
    }

    /**
     * Whether the current user may see this item.
     *
     * With a model, this is the ordinary policy question — the same one the
     * package's own route middleware asks, so the link appears exactly when the
     * page behind it is reachable. Without one it is a bare ability check.
     *
     * @param  array<string, mixed>  $item
     */
    private function permitted(array $item): bool
    {
        $ability = $item['ability'] ?? null;

        if ($ability === null) {
            return true;
        }

        return isset($item['model'])
            ? Gate::allows($ability, $item['model'])
            : Gate::allows($ability);
    }
}
