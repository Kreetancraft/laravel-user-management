<?php

namespace Kreetancraft\UserManagement\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Kreetancraft\UserManagement\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

use function Laravel\Prompts\password as promptPassword;
use function Laravel\Prompts\text;

class SuperAdminCommand extends Command
{
    protected $signature = 'user-management:super-admin'
        .' {--user= : Existing user ID or email to promote}'
        .' {--name= : Name, for non-interactive creation}'
        .' {--email= : Email, for non-interactive creation}'
        .' {--password= : Password, for non-interactive creation}'
        .' {--force : Force in production}';

    protected $description = 'Create or promote a super-admin user';

    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Cannot run in production without --force.');

            return self::FAILURE;
        }

        $registrar = app(PermissionRegistrar::class);
        $roleName = config('user-management.super_admin.role', 'super-admin');
        $guard = config('permission.defaults.guard', config('auth.defaults.guard', 'web'));
        $role = Role::findOrCreate($roleName, $guard);
        $registrar->forgetCachedPermissions();

        $userModel = config('auth.providers.users.model', User::class);
        $userOption = $this->option('user');

        if ($userOption) {
            $user = is_numeric($userOption)
                ? $userModel::find($userOption)
                : $userModel::where('email', $userOption)->first();

            if (! $user) {
                $this->error("User [{$userOption}] not found.");

                return self::FAILURE;
            }

            $user->assignRole($role);
            $this->info("Promoted [{$user->email}] to [{$roleName}].");

            return self::SUCCESS;
        }

        // Non-interactive path: every value supplied on the command line, so this
        // works in CI, a Dockerfile, or a provisioning script.
        $name = $this->option('name');
        $email = $this->option('email');
        $pwd = $this->option('password');

        if ($name === null || $email === null || $pwd === null) {
            if (! $this->input->isInteractive()) {
                $this->error('Non-interactive run needs --name, --email and --password (or --user to promote).');

                return self::FAILURE;
            }

            $name ??= text('Name', default: config('user-management.super_admin.name', 'Super Admin'), required: true);
            $email ??= text('Email', default: config('user-management.super_admin.email', 'superadmin@example.com'), required: true, validate: fn ($v) => filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'Invalid email');
            $pwd ??= promptPassword('Password', required: true);
        }

        $userModel = config('auth.providers.users.model', User::class);
        $user = $userModel::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($pwd),
                'is_active' => true,
            ]
        );

        // email_verified_at is deliberately not fillable, so mass assignment drops
        // it silently. Without this the very first super admin of a fresh install
        // lands on the verify-email wall with no way past it.
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        // If user existed but password supplied, update it
        if (! $user->wasRecentlyCreated && $pwd) {
            $user->forceFill(['password' => Hash::make($pwd)])->save();
        }

        $user->assignRole($role);
        $this->info("Super-admin [{$user->email}] ready with role [{$roleName}].");

        return self::SUCCESS;
    }
}
