<x-dynamic-component :component="config('user-management.layouts.auth', 'layouts.auth')" :title="__('Confirm password')">
    <div class="flex flex-col gap-6">
        <x-user-management::auth-header
            :title="__('Confirm password')"
            :description="__('This is a secure area of the application. Please confirm your password before continuing.')"
        />

        <x-user-management::auth-session-status class="text-center" :status="session('status')" />

        @if (\Illuminate\Support\Facades\View::exists('components.passkey-verify'))
                <x-dynamic-component component="passkey-verify"
            options-route="passkey.confirm-options"
            submit-route="passkey.confirm"
            :label="__('Confirm with passkey')"
            :loading-label="__('Confirming...')"
            :separator="__('Or confirm with password')"
        />
            @endif
        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            {{-- Hidden username for accessibility / password managers --}}
            <input
                type="text"
                name="username"
                autocomplete="username"
                value="{{ auth()->user()?->email }}"
                class="sr-only"
                tabindex="-1"
                readonly
            />

            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Password')"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
                {{ __('Confirm') }}
            </flux:button>
        </form>
    </div>
</x-dynamic-component>
