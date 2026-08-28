<?php

namespace Kreetancraft\UserManagement\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Kreetancraft\UserManagement\Actions\Fortify\CreateNewUser;
use Kreetancraft\UserManagement\Actions\Fortify\ResetUserPassword;
use Kreetancraft\UserManagement\Models\User;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Turn OFF any Fortify feature this package is configured not to support.
     *
     * Subtractive only, and deliberately so. An earlier version rebuilt the
     * list, which silently dropped resetPasswords and emailVerification when it
     * ran before Fortify's own config had been merged. The host's fortify
     * config decides what is enabled; all this package may do is withdraw a
     * feature whose view it declines to register, so Fortify cannot route to a
     * view that does not exist.
     */
    private function syncFortifyFeatures(): void
    {
        $disabled = [];

        if (! config('user-management.features.registration', false)) {
            $disabled[] = Features::registration();
        }

        if (! config('user-management.features.two_factor', true)) {
            $disabled[] = Features::twoFactorAuthentication();
        }

        if ($disabled === []) {
            return;
        }

        config(['fortify.features' => array_values(array_diff(
            (array) config('fortify.features', []),
            $disabled,
        ))]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->syncFortifyFeatures();
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::authenticateUsing(function (Request $request): ?User {
            $user = User::where('email', $request->email)->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return null;
            }

            if (! $user->is_active) {
                throw ValidationException::withMessages([
                    Fortify::username() => [__('Your account has been deactivated. Please contact an administrator.')],
                ]);
            }

            return $user;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view(config('user-management.views.login', 'user-management::auth.login')));
        Fortify::verifyEmailView(fn () => view(config('user-management.views.verify-email', 'user-management::auth.verify-email')));
        Fortify::twoFactorChallengeView(fn () => view(config('user-management.views.two-factor-challenge', 'user-management::auth.two-factor-challenge')));
        Fortify::confirmPasswordView(fn () => view(config('user-management.views.confirm-password', 'user-management::auth.confirm-password')));
        Fortify::resetPasswordView(fn () => view(config('user-management.views.reset-password', 'user-management::auth.reset-password')));
        Fortify::requestPasswordResetLinkView(fn () => view(config('user-management.views.forgot-password', 'user-management::auth.forgot-password')));

        if (config('user-management.features.registration', false)) {
            Fortify::registerView(fn () => view(config('user-management.views.register', 'user-management::auth.register')));
        }
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip(),
            );
        });
    }
}
