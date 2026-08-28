<?php

namespace Kreetancraft\UserManagement\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'user-management:sync-permissions {--fresh : Remove permissions not discovered anymore}';

    protected $description = 'Generate permissions from discovered policy methods and sync to database (idempotent)';

    public function handle(): int
    {
        $separator = config('user-management.permissions.separator', '-');
        $case = config('user-management.permissions.case', 'kebab');
        $methods = config('user-management.permissions.methods', ['viewAny', 'view', 'create', 'update', 'delete', 'restore', 'forceDelete']);
        $custom = config('user-management.permissions.custom', []);
        $paths = config('user-management.policies.paths', [app_path('Policies')]);
        $discover = config('user-management.policies.discover', true);

        $generated = collect();

        if ($discover) {
            foreach ($paths as $path) {
                if (! File::isDirectory($path)) {
                    continue;
                }
                foreach (File::allFiles($path) as $file) {
                    if ($file->getExtension() !== 'php') {
                        continue;
                    }
                    $class = $this->classFromFile($file->getPathname());
                    if (! $class || ! class_exists($class)) {
                        continue;
                    }
                    $ref = new ReflectionClass($class);
                    foreach ($methods as $method) {
                        if ($ref->hasMethod($method) && $ref->getMethod($method)->isPublic()) {
                            $perm = $this->permissionName($ref->getShortName(), $method, $separator, $case);
                            $generated->push($perm);
                        }
                    }
                }
            }
        }

        $all = $generated->merge($custom)->unique()->values();

        foreach ($all as $perm) {
            Permission::findOrCreate($perm, 'web');
            $this->line("  <fg=green>✓</> {$perm}");
        }

        if ($this->option('fresh')) {
            // Never prune a protected permission: --fresh must not be able to
            // delete the permissions the admin UI itself depends on.
            $protected = collect(config('user-management.permissions.protected', []))
                ->merge(config('user-management.permissions.custom', []));

            $existing = Permission::pluck('name');
            $toDelete = $existing->diff($all)->diff($protected);
            foreach ($toDelete as $perm) {
                Permission::where('name', $perm)->delete();
                $this->line("  <fg=red>✗</> {$perm} removed");
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("Synced {$all->count()} permissions.");

        return self::SUCCESS;
    }

    private function permissionName(string $policyShortName, string $method, string $separator, string $case): string
    {
        // Policy "UserPolicy" -> "user", "TripPolicy" -> "trip"
        $model = Str::of($policyShortName)->replaceEnd('Policy', '')->kebab()->toString();
        // Actually respect case config
        $methodKebab = $case === 'kebab' ? Str::kebab($method) : Str::snake($method);
        $modelKebab = $case === 'kebab' ? $model : Str::snake($model);

        // Standard: view-users, create-users, delete-users — we generate viewAny as view
        $map = [
            'view-any' => 'view',
            'view_any' => 'view',
        ];
        $methodKebab = $map[$methodKebab] ?? $methodKebab;

        return $methodKebab.$separator.Str::plural($modelKebab);
    }

    private function classFromFile(string $path): ?string
    {
        $content = File::get($path);
        if (! preg_match('/namespace\s+(.+);/', $content, $nsMatch)) {
            return null;
        }
        if (! preg_match('/class\s+(\w+)/', $content, $classMatch)) {
            return null;
        }

        return trim($nsMatch[1]).'\\'.trim($classMatch[1]);
    }
}
