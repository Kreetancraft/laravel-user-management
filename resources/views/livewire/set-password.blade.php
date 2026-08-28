<div class="w-full max-w-md space-y-12">
        @if ($invalid)
            <flux:card>
                <div class="p-6 text-center space-y-4">
                    <flux:icon name="exclamation-triangle" class="mx-auto size-12 opacity-40" />
                    <flux:heading size="lg">{{ __('Invalid or expired invitation') }}</flux:heading>
                    <flux:text variant="subtle">
                        {{ __('This invitation link is invalid or has expired. Please ask an administrator to send a new one.') }}
                    </flux:text>
                    <flux:button href="{{ route(config('user-management.routes.names.login', 'login')) }}" variant="primary" wire:navigate>
                        {{ __('Back to Sign In') }}
                    </flux:button>
                </div>
            </flux:card>
        @else
            <div class="text-center space-y-2">
                <flux:heading size="xl" level="1">{{ __('Set your password') }}</flux:heading>
                <flux:subheading>{{ __('Welcome, :name. Choose a password to activate your account.', ['name' => $user->name]) }}</flux:subheading>
            </div>

            <flux:card>
                <form wire:submit.prevent="save" class="p-6 space-y-12">
                    <flux:field>
                        <flux:label required>{{ __('Password') }}</flux:label>
                        <flux:input
                            wire:model.blur="password"
                            type="password"
                            icon="lock-closed"
                            autofocus
                            autocomplete="new-password"
                            viewable
                        />
                        <flux:error name="password" />
                    </flux:field>

                    <flux:field>
                        <flux:label required>{{ __('Confirm Password') }}</flux:label>
                        <flux:input
                            wire:model.blur="password_confirmation"
                            type="password"
                            icon="lock-closed"
                            autocomplete="new-password"
                            viewable
                        />
                        <flux:error name="password_confirmation" />
                    </flux:field>

                    <flux:button type="submit" variant="primary" class="w-full" icon="check" wire:loading.attr="disabled" wire:target="save">
                        {{ __('Activate Account') }}
                    </flux:button>
                </form>
            </flux:card>
        @endif
    </div>
