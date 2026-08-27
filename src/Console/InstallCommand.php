<?php

namespace Kreetancraft\UserManagement\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'user-management:install {--force : Overwrite without confirmation}';

    protected $description = 'Install user-management: publish config/migrations and inject sidebar nav';

    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'user-management-config']);
        // Migrations are published explicitly only if user wants — we don't auto-publish to avoid duplicates
        // $this->call('vendor:publish', ['--tag' => 'user-management-migrations']);

        $this->injectNav();

        $this->info('User management installed. Run: php artisan migrate && php artisan user-management:super-admin');
        $this->line('Nav injected via <x-user-management::nav /> — or manually add to resources/views/components/layouts/app/sidebar.blade.php');

        return self::SUCCESS;
    }

    private function injectNav(): void
    {
        $sidebar = resource_path('views/components/layouts/app/sidebar.blade.php');

        if (! File::exists($sidebar)) {
            $this->warn("Sidebar not found at {$sidebar} — skipping auto-inject. Add <x-user-management::nav /> manually.");
            return;
        }

        $content = File::get($sidebar);

        if (str_contains($content, 'user-management::nav') || str_contains($content, "route('admin.users')")) {
            $this->line('Sidebar already contains user-management nav — skipping.');
            return;
        }

        // Try to inject after Dashboard item, else before </flux:navlist.group>, else append
        $navSnippet = "        <x-user-management::nav />\n";

        if (str_contains($content, "route('dashboard')")) {
            $content = preg_replace(
                "/(route\('dashboard'\).*?wire:navigate.*?\n.*?\@?\<\/flux:navlist\.item\>)/s",
                "$1\n".$navSnippet,
                $content,
                1
            );
        } elseif (str_contains($content, '</flux:navlist.group>')) {
            $content = str_replace('</flux:navlist.group>', $navSnippet.'        </flux:navlist.group>', $content);
        } else {
            $content .= "\n".$navSnippet;
        }

        File::put($sidebar, $content);
        $this->info('Injected <x-user-management::nav /> into sidebar.');
    }
}
