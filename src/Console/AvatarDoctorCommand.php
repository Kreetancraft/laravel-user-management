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
        $this->newLine();

        $checks = [
            $this->resolverConfigured(),
            $this->resolverExists(),
            $this->resolverCanWrite(),
            $this->pickerConfigured(),
            $this->pickerViewExists(),
            $this->formsAreCurrent(),
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
