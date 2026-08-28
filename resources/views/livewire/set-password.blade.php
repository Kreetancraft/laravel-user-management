<div class="flex w-full max-w-md flex-col gap-6">
    @if ($invalid)
        <x-user-management::empty-state
            icon="exclamation-triangle"
            :heading="__('This link has expired')"
            :description="__('Invitation links are single-use and time-limited. Ask an administrator to send a new one.')"
        >
            <flux:button
                href="{{ route(config('user-management.routes.names.login', 'login')) }}"
                variant="primary"
                wire:navigate
            >{{ __('Back to sign in') }}</flux:button>
        </x-user-management::empty-state>
    @else
        <x-user-management::auth-header
            :title="__('Choose a password')"
            :description="__('Welcome, :name. This is the last step.', ['name' => $user->name])"
        />

        <x-user-management::form-errors />

        <form wire:submit.prevent="save" class="flex flex-col gap-5">
            <flux:field>
                <flux:label>{{ __('Password') }}</flux:label>
                <flux:input
                    wire:model.blur="password"
                    type="password"
                    autofocus
                    autocomplete="new-password"
                    viewable
                />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Confirm password') }}</flux:label>
                <flux:input
                    wire:model.blur="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    viewable
                />
                <flux:error name="password_confirmation" />
            </flux:field>

            <flux:button
                type="submit"
                variant="primary"
                class="w-full"
                wire:loading.attr="disabled"
                wire:target="save"
            >
                <span wire:loading.remove wire:target="save">{{ __('Activate account') }}</span>
                <span wire:loading wire:target="save">{{ __('Activating…') }}</span>
            </flux:button>
        </form>
    @endif
</div>
