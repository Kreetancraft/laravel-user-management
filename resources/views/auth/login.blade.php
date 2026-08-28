<x-dynamic-component :component="config('user-management.layouts.auth', 'layouts.auth')" :title="__('Log in')">
    <div class="flex flex-col gap-6">
        <x-user-management::auth-header :title="__('Welcome back')" :description="__('Sign in to your account to continue')" />

        <!-- Session Status -->
        <x-user-management::auth-session-status class="text-center" :status="session('status')" />

        @if (\Illuminate\Support\Facades\View::exists('components.passkey-verify'))
            <x-dynamic-component component="passkey-verify" />
        @endif
        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email address')"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="you@example.com"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Enter your password')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('Forgot password?') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Remember me')" :checked="old('remember')" />

            <div>
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Sign in') }}
                </flux:button>
            </div>
        </form>

    </div>
</x-dynamic-component>
