<?php

namespace Kreetancraft\UserManagement\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\View;
use Kreetancraft\UserManagement\Support\Avatar;

/**
 * Says why the avatar field is not showing.
 *
 * The field renders nothing unless five separate things are true, and every one
 * of them fails the same way — a form with no avatar section and no error. That
 * is correct behaviour on an install with no media package, and indistinguishable
 * from a misconfiguration, which has cost more than one round of guessing.
 *
 * This reports which of the five is false.
 */
class AvatarDoctorCommand extends Command
{
    protected $signature = 'user-management:avatar-doctor';

    protected $description = 'Report why the avatar field is or is not rendering';

    public function handle(): int
    {
        $this->line(' <fg=gray>installed:</> '.$this->version('kreetancraft/laravel-user-management')
            .'  <fg=gray>media:</> '.$this->version('kreetancraft/laravel-media-manager'));
        $this->newLine();

        $checks = [
            $this->resolverConfigured(),
            $this->resolverExists(),
            $this->resolverCanWrite(),
            $this->pickerConfigured(),
            $this->pickerViewExists(),
            $this->uploaderChosen(),
            $this->uploaderRegistered(),
            $this->formsAreCurrent(),
            $this->profileViewIsCurrent(),
        ];

        $failed = array_filter($checks, fn (array $c) => ! $c['ok']);

        $this->newLine();

        if ($failed === []) {
            $this->info('The avatar field should be rendering. If it is not, the view is cached: php artisan view:clear');

            return self::SUCCESS;
        }

        $this->warn('Fix the first ✗ above, then run this again.');

        return self::FAILURE;
    }

    /**
     * @return array{ok: bool}
     */
    private function report(bool $ok, string $label, string $detail): array
    {
        $this->line(sprintf(
            ' %s %s',
            $ok ? '<fg=green>✓</>' : '<fg=red>✗</>',
            $ok ? $label : "<fg=red>{$label}</> — {$detail}",
        ));

        return ['ok' => $ok];
    }

    private function resolverConfigured(): array
    {
        $value = config('user-management.avatar_resolver');

        return $this->report(
            $value !== null && $value !== '',
            'avatar_resolver is set',
            "it is null. Set it in config/user-management.php:\n     'avatar_resolver' => ".
            '\\Kreetancraft\\Media\\Support\\MediaAvatarResolver::class,',
        );
    }

    private function resolverExists(): array
    {
        $value = config('user-management.avatar_resolver');

        if (! is_string($value) || $value === '') {
            return $this->report(is_object($value), 'avatar_resolver resolves', 'set it first');
        }

        return $this->report(
            class_exists($value),
            "avatar_resolver class exists ({$value})",
            'that class is not installed. composer require kreetancraft/laravel-media-manager',
        );
    }

    private function resolverCanWrite(): array
    {
        return $this->report(
            Avatar::enabled(),
            'the resolver can store an avatar',
            'it has no syncFor()/listFor(). Older media-manager could only read; '.
            'needs 0.8.0 or later: composer update kreetancraft/laravel-media-manager',
        );
    }

    private function pickerConfigured(): array
    {
        return $this->report(
            Avatar::pickerView() !== null,
            'media_picker_view is set',
            "it is null. Set it in config/user-management.php:\n     'media_picker_view' => 'media::picker-field',",
        );
    }

    private function pickerViewExists(): array
    {
        $view = Avatar::pickerView();

        if ($view === null) {
            return $this->report(false, 'the picker view exists', 'set media_picker_view first');
        }

        return $this->report(
            View::exists($view),
            "the picker view exists ({$view})",
            'no such view. media::picker-field ships with kreetancraft/laravel-media-manager 0.6.0+',
        );
    }

    /**
     * The uploader is optional, so this is a note rather than a failure — but a
     * chooser appearing on a profile page is almost always this being unset.
     */
    private function uploaderChosen(): array
    {
        $uploader = Avatar::uploader();

        if ($uploader !== null) {
            return $this->report(true, "the profile page uploads ({$uploader})", '');
        }

        $this->line(' <fg=yellow>!</> the profile page opens the media library — set '.
            "'avatar_uploader' => 'media.avatar-uploader' to upload instead");

        return ['ok' => true];
    }

    private function uploaderRegistered(): array
    {
        $uploader = Avatar::uploader();

        if ($uploader === null) {
            return ['ok' => true];
        }

        $registered = true;

        try {
            app('livewire')->getClass($uploader);
        } catch (\Throwable) {
            $registered = false;
        }

        return $this->report(
            $registered,
            "the uploader component is registered ({$uploader})",
            'no such Livewire component. media.avatar-uploader ships with '.
            'kreetancraft/laravel-media-manager 0.9.0: composer update kreetancraft/laravel-media-manager',
        );
    }

    /**
     * The profile picker's own view, published before the uploader existed,
     * shadows the package's and keeps opening the library.
     */
    private function profileViewIsCurrent(): array
    {
        $published = resource_path('views/vendor/user-management/livewire/avatar.blade.php');

        if (! is_file($published)) {
            return $this->report(true, 'the profile picker comes from the package', '');
        }

        return $this->report(
            str_contains((string) file_get_contents($published), 'Avatar::uploader'),
            'your published copy of the profile picker knows about the uploader',
            "it predates 0.15.0 and wins over the package's. Republish:\n".
            '     php artisan vendor:publish --tag=user-management-views --force',
        );
    }

    private function version(string $package): string
    {
        $installed = base_path('vendor/composer/installed.json');

        if (! is_file($installed)) {
            return 'unknown';
        }

        $data = json_decode((string) file_get_contents($installed), true);

        foreach ($data['packages'] ?? [] as $entry) {
            if (($entry['name'] ?? null) === $package) {
                return (string) ($entry['version'] ?? 'unknown');
            }
        }

        return 'not installed';
    }

    /**
     * A published copy of the user forms wins over the package's, so one taken
     * before 0.12.0 has no avatar section however well the rest is configured.
     */
    private function formsAreCurrent(): array
    {
        $published = resource_path('views/vendor/user-management/livewire/edit-user.blade.php');

        if (! is_file($published)) {
            return $this->report(true, 'the user forms come from the package', '');
        }

        return $this->report(
            str_contains((string) file_get_contents($published), 'avatar-field'),
            'your published copy of edit-user has the avatar field',
            "it predates 0.12.0 and wins over the package's. Republish:\n".
            '     php artisan vendor:publish --tag=user-management-views --force',
        );
    }
}
