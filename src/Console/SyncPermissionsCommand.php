<?php

namespace Kreetancraft\UserManagement\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use ReflectionClass;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Generate permissions from the policies this application knows about.
 *
 * Modelled on Filament Shield: packages do not announce permission names. Shield
 * discovers subjects from the Filament panel's registry and derives the names
 * itself. We have no panel, so the equivalent registry is Laravel's own —
 * Gate::policies(), which every package populates when it calls Gate::policy().
 *
 * That matters because scanning directories alone only ever finds the host
 * application's policies. A policy shipped inside a package would be invisible,
 * and its screens would sit behind a permission nobody ever created.
 */
class SyncPermissionsCommand extends Command
{
    protected $signature = 'user-management:sync-permissions
        {--fresh : Remove permissions that are no longer discovered}
        {--dry-run : Show what would change without touching the database}';

    protected $description = 'Generate permissions from discovered policies and sync them (idempotent)';

    public function handle(): int
    {
        $discovered = $this->discover();
        $custom = collect(config('user-management.permissions.custom', []));

        $all = $discovered->flatten()->merge($custom)->unique()->sort()->values();

        if ($this->option('dry-run')) {
            $this->report($discovered, $custom);
            $this->info("Would sync {$all->count()} permissions.");

            return self::SUCCESS;
        }

        foreach ($all as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $removed = $this->option('fresh') ? $this->prune($all) : collect();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->report($discovered, $custom, $removed);
        $this->info("Synced {$all->count()} permissions.");

        return self::SUCCESS;
    }

    /**
     * Permission names grouped by the subject that produced them.
     *
     * @return Collection<string, list<string>>
     */
    private function discover(): Collection
    {
        $found = collect();

        if (! config('user-management.policies.discover', true)) {
            return $found;
        }

        // 1. Every policy registered through Gate::policy(), wherever it lives.
        //    This is what makes a package's policy discoverable at all.
        if (config('user-management.policies.discover_registered', true)) {
            foreach (Gate::policies() as $model => $policy) {
                if ($this->excluded($model) || $this->excluded($policy)) {
                    continue;
                }

                // Gate::policies() contains every policy in the application,
                // including third-party ones. livewire-filemanager registers its
                // own MediaPolicy, and taking that would invent permissions for a
                // dependency nobody meant to expose. A package opts in by naming
                // its subject; this application's own policies are always in.
                if (! $this->participates($policy)) {
                    continue;
                }

                $subject = $this->subjectFor($model, $policy);
                $names = $this->namesFor($policy, $subject);

                if ($names !== []) {
                    $found[$subject] = array_values(array_unique(
                        array_merge($found[$subject] ?? [], $names)
                    ));
                }
            }
        }

        // 2. Policy files on disk that were never bound through Gate::policy().
        foreach ((array) config('user-management.policies.paths', [app_path('Policies')]) as $path) {
            if (! File::isDirectory($path)) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $policy = $this->classFromFile($file->getPathname());

                if (! $policy || ! class_exists($policy) || $this->excluded($policy)) {
                    continue;
                }

                // Derive the subject from the policy name when there is no model
                // to read it from: UserPolicy -> user.
                $subject = $this->subjectFor(
                    Str::of(class_basename($policy))->replaceEnd('Policy', '')->toString(),
                    $policy,
                );

                $names = $this->namesFor($policy, $subject);

                if ($names !== []) {
                    $found[$subject] = array_values(array_unique(
                        array_merge($found[$subject] ?? [], $names)
                    ));
                }
            }
        }

        return $found;
    }

    /**
     * Permission names for the methods a policy actually declares.
     *
     * @return list<string>
     */
    private function namesFor(string $policy, string $subject): array
    {
        if (! class_exists($policy)) {
            return [];
        }

        $reflection = new ReflectionClass($policy);
        $methods = (array) config('user-management.permissions.methods', [
            'viewAny', 'view', 'create', 'update', 'delete', 'restore', 'forceDelete',
        ]);

        $names = [];

        foreach ($methods as $method) {
            if ($reflection->hasMethod($method) && $reflection->getMethod($method)->isPublic()) {
                $names[] = $this->permissionName($method, $subject, $policy);
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * The noun a permission is about.
     *
     * `policies.subjects` lets a host rename an awkward one — left alone,
     * MediaAttachment would produce `view-media-attachments`, which names a link
     * table rather than anything a person recognises.
     */
    /**
     * The noun a permission is about.
     *
     * A policy may name its own subject with a PERMISSION_SUBJECT constant.
     * That is the single source of truth: the policy that checks the ability
     * and the command that creates it read the same declaration, so there is no
     * pair of config keys to keep in sync and nothing to drift.
     *
     *     class MediaPolicy
     *     {
     *         public const PERMISSION_SUBJECT = 'media';
     *     }
     *
     * Without it the subject falls back to the model name, which is right for
     * most policies: UserPolicy on User gives view-users.
     */
    private function subjectFor(string $modelOrName, ?string $policy = null): string
    {
        if ($policy !== null && defined($policy.'::PERMISSION_SUBJECT')) {
            return (string) constant($policy.'::PERMISSION_SUBJECT');
        }

        return Str::of(class_basename($modelOrName))->kebab()->toString();
    }

    /**
     * The plural of a subject.
     *
     * Derived by default, because that is right almost always: user -> users.
     * A policy may declare PERMISSION_SUBJECT_PLURAL when it is not — SEO is a
     * mass noun, and `view-seos` is not a phrase anyone would write.
     */
    private function pluralFor(string $subject, ?string $policy): string
    {
        if ($policy !== null && defined($policy.'::PERMISSION_SUBJECT_PLURAL')) {
            $plural = (string) constant($policy.'::PERMISSION_SUBJECT_PLURAL');

            return (string) config('user-management.permissions.case', 'kebab') === 'kebab'
                ? Str::kebab($plural)
                : Str::snake($plural);
        }

        return Str::plural($subject);
    }

    private function permissionName(string $method, string $subject, ?string $policy = null): string
    {
        $separator = (string) config('user-management.permissions.separator', '-');
        $case = (string) config('user-management.permissions.case', 'kebab');

        $action = $case === 'kebab' ? Str::kebab($method) : Str::snake($method);
        $subject = $case === 'kebab' ? Str::kebab($subject) : Str::snake($subject);

        // viewAny collapses to view: `view-users` reads better than
        // `view-any-users`, and nothing distinguishes them in practice.
        $action = match ($action) {
            'view-any', 'view_any' => 'view',
            default => $action,
        };

        return $action.$separator.$this->pluralFor($subject, $policy);
    }

    /**
     * Whether a registered policy is one we should generate permissions for.
     *
     * Two ways in: declare a PERMISSION_SUBJECT constant (how a package opts in),
     * or live in this application's own namespace. Everything else — the policies
     * that arrive with unrelated dependencies — is left alone.
     */
    private function participates(string $policy): bool
    {
        if (defined($policy.'::PERMISSION_SUBJECT')) {
            return true;
        }

        foreach ((array) config('user-management.policies.namespaces', ['App\\']) as $namespace) {
            if (str_starts_with($policy, $namespace)) {
                return true;
            }
        }

        return false;
    }

    private function excluded(string $class): bool
    {
        return in_array($class, (array) config('user-management.policies.exclude', []), true);
    }

    /**
     * @param  Collection<int, string>  $keep
     * @return Collection<int, string>
     */
    private function prune(Collection $keep): Collection
    {
        // --fresh must never delete a permission the admin UI itself depends on,
        // or an admin can lock themselves out of the screen that restores it.
        $protected = collect(config('user-management.permissions.protected', []))
            ->merge(config('user-management.permissions.custom', []));

        $toDelete = Permission::pluck('name')->diff($keep)->diff($protected);

        Permission::whereIn('name', $toDelete)->delete();

        return $toDelete->values();
    }

    /**
     * @param  Collection<string, list<string>>  $discovered
     * @param  Collection<int, string>  $custom
     * @param  Collection<int, string>  $removed
     */
    private function report(
        Collection $discovered,
        Collection $custom,
        Collection $removed = new Collection,
    ): void {
        foreach ($discovered->sortKeys() as $subject => $names) {
            $this->line("  <fg=cyan>{$subject}</>");
            foreach ($names as $name) {
                $this->line("    <fg=green>✓</> {$name}");
            }
        }

        if ($custom->isNotEmpty()) {
            $this->line('  <fg=cyan>custom</>');
            foreach ($custom as $name) {
                $this->line("    <fg=green>✓</> {$name}");
            }
        }

        foreach ($removed as $name) {
            $this->line("  <fg=red>✗</> {$name} removed");
        }
    }

    private function classFromFile(string $path): ?string
    {
        $contents = File::get($path);

        if (! preg_match('/namespace\s+(.+);/', $contents, $namespace)) {
            return null;
        }

        if (! preg_match('/class\s+(\w+)/', $contents, $class)) {
            return null;
        }

        return trim($namespace[1]).'\\'.trim($class[1]);
    }
}
